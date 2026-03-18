<?php

namespace App\Http\Controllers;

use App\Events\AnswersLocked;
use App\Events\AnswerSubmitted;
use App\Events\ParticipantJoined;
use App\Events\ParticipantKicked;
use App\Events\QuizStateUpdated;
use App\Models\Answer;
use App\Models\Participant;
use App\Models\PresentationSession;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PresentationController extends Controller
{
    /* ─────────────────────────────────────────────────────────────
       Oturum başlatma
    ───────────────────────────────────────────────────────────── */
    public function startSession(Quiz $quiz)
    {
        $session = PresentationSession::create([
            'quiz_id'     => $quiz->id,
            'code'        => strtoupper(Str::random(4)),
            'admin_token' => Str::uuid()->toString(),
            'status'      => 'pending',
            'time_limit'  => 30,
        ]);

        return redirect()->route('remote.show', $session->admin_token);
    }

    /* ─────────────────────────────────────────────────────────────
       Sunum ekranı (display)
    ───────────────────────────────────────────────────────────── */
    public function display(string $code)
    {
        $session = PresentationSession::where('code', $code)
            ->with(['quiz.questions' => fn ($q) => $q->orderBy('position')])
            ->firstOrFail();

        $currentQuestion = $session->quiz->questions->firstWhere('id', $session->current_question_id);

        return view('presentation.display', compact('session', 'currentQuestion'));
    }

    /* ─────────────────────────────────────────────────────────────
       Admin remote kumanda
    ───────────────────────────────────────────────────────────── */
    public function remote(string $adminToken)
    {
        $session = PresentationSession::where('admin_token', $adminToken)
            ->with(['quiz.questions' => fn ($q) => $q->orderBy('position'), 'participants'])
            ->firstOrFail();

        $currentQuestion = $session->quiz->questions->firstWhere('id', $session->current_question_id);

        // Anlık cevap istatistikleri
        $initialCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
        $initialTotal  = 0;

        if ($currentQuestion) {
            $answers = Answer::where('question_id', $currentQuestion->id)
                ->where('presentation_session_id', $session->id)
                ->get();

            $initialTotal = $answers->count();
            foreach ($answers as $answer) {
                if (isset($initialCounts[$answer->selected_option])) {
                    $initialCounts[$answer->selected_option]++;
                }
            }
        }

        return view('presentation.remote', compact('session', 'currentQuestion', 'initialCounts', 'initialTotal') + ['title' => 'Kumanda']);
    }

    /* ─────────────────────────────────────────────────────────────
       QR tam ekran sayfası
    ───────────────────────────────────────────────────────────── */
    public function qrFullscreen(string $adminToken)
    {
        $session = PresentationSession::where('admin_token', $adminToken)->firstOrFail();
        return view('presentation.qr-fullscreen', compact('session'));
    }

    /* ─────────────────────────────────────────────────────────────
       Kontrol (next, prev, goto, reveal, show_results)
    ───────────────────────────────────────────────────────────── */
    public function control(Request $request, string $adminToken)
    {
        $session = PresentationSession::where('admin_token', $adminToken)
            ->with('quiz.questions')
            ->firstOrFail();

        $mode      = $request->input('mode', 'next');
        $questions = $session->quiz->questions;
        $current   = $session->current_question_id
            ? $questions->firstWhere('id', $session->current_question_id)
            : null;

        $targetQuestion = null;

        if ($mode === 'next') {
            $targetQuestion = $current
                ? ($questions->firstWhere('position', '>', $current->position) ?? $current)
                : $questions->first();
            $session->status                       = 'running';
            $session->answers_locked               = false;
            $session->current_question_id          = $targetQuestion?->id;
            $session->current_question_started_at  = now();
            $session->save();

        } elseif ($mode === 'prev') {
            $targetQuestion = $current
                ? ($questions->where('position', '<', $current->position)->sortByDesc('position')->first() ?? $current)
                : $questions->first();
            $session->status                       = 'running';
            $session->answers_locked               = false;
            $session->current_question_id          = $targetQuestion?->id;
            $session->current_question_started_at  = now();
            $session->save();

        } elseif ($mode === 'show_results' || $mode === 'finish') {
            $session->status   = 'finished';
            $session->ended_at = now();
            $session->save();

        } elseif ($mode === 'goto') {
            $targetId       = (int) $request->input('question_id');
            $targetQuestion = $questions->firstWhere('id', $targetId);
            if ($targetQuestion) {
                $session->status                       = 'running';
                $session->answers_locked               = false;
                $session->current_question_id          = $targetQuestion->id;
                $session->current_question_started_at  = now();
                $session->save();
            }

        } elseif ($mode === 'reveal') {
            $targetQuestion = $current; // sadece cevabı yayınla

        } elseif ($mode === 'lobby') {
            // Bekleme ekranına geri dön
            $session->status                      = 'pending';
            $session->current_question_id         = null;
            $session->current_question_started_at = null;
            $session->answers_locked              = false;
            $session->save();

        } elseif ($mode === 'show_all_results') {
            // Tüm sonuçları göster — oturumu bitirmez, sadece broadcast eder
            // (Sunumu bitirmek için ayrıca 'finish' kullanılır)
        }

        $top = [];
        if (in_array($mode, ['show_results', 'finish', 'show_all_results'])) {
            $limit = ($mode === 'show_all_results') ? 999 : 3;
            $top = $session->participants()
                ->orderByDesc('total_score')
                ->orderByDesc('total_speed_bonus')
                ->take($limit)
                ->get(['name', 'team_name', 'total_score', 'total_speed_bonus'])
                ->map(fn ($p) => [
                    'name'              => $p->name,
                    'team_name'         => $p->team_name,
                    'total_score'       => $p->total_score,
                    'total_speed_bonus' => $p->total_speed_bonus,
                ])
                ->toArray();
        }

        // lobby modunda soru null — bekleme ekranına dön
        $questionModel = ($mode === 'lobby') ? null : ($targetQuestion ?? $current);

        event(new QuizStateUpdated($session->fresh(), $questionModel, $mode, $top));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'mode' => $mode]);
        }
        return back();
    }

    /* ─────────────────────────────────────────────────────────────
       Cevapları kilitle / aç
    ───────────────────────────────────────────────────────────── */
    public function lockAnswers(Request $request, string $adminToken)
    {
        $session = PresentationSession::where('admin_token', $adminToken)->firstOrFail();

        $locked = !$session->answers_locked;
        $session->answers_locked = $locked;
        $session->save();

        event(new AnswersLocked($session, $locked));

        return response()->json(['ok' => true, 'locked' => $locked]);
    }

    /* ─────────────────────────────────────────────────────────────
       Zamanlayıcı süresi güncelle
    ───────────────────────────────────────────────────────────── */
    public function setTimer(Request $request, string $adminToken)
    {
        $session = PresentationSession::where('admin_token', $adminToken)->firstOrFail();
        $data    = $request->validate(['time_limit' => ['required', 'integer', 'min:0', 'max:600']]);

        $session->update(['time_limit' => $data['time_limit']]);

        return response()->json(['ok' => true, 'time_limit' => $session->time_limit]);
    }

    /* ─────────────────────────────────────────────────────────────
       Katılımcı at (kick)
    ───────────────────────────────────────────────────────────── */
    public function kickParticipant(string $adminToken, Participant $participant)
    {
        $session = PresentationSession::where('admin_token', $adminToken)->firstOrFail();

        if ($participant->presentation_session_id !== $session->id) {
            abort(403);
        }

        $participantId = $participant->id;
        event(new ParticipantKicked($session, $participantId));
        $participant->delete();

        // Katılımcı sayısını güncelle
        $total = $session->participants()->count();

        return response()->json(['ok' => true, 'total' => $total]);
    }

    /* ─────────────────────────────────────────────────────────────
       CSV dışa aktarma
    ───────────────────────────────────────────────────────────── */
    public function exportResults(string $adminToken)
    {
        $session = PresentationSession::where('admin_token', $adminToken)
            ->with('participants')
            ->firstOrFail();

        $filename = 'quiz-sonuclari-' . $session->code . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($session) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Sıra', 'İsim', 'Takım', 'Toplam Puan', 'Hız Bonusu'], ';');

            $participants = $session->participants()
                ->orderByDesc('total_score')
                ->orderByDesc('total_speed_bonus')
                ->get();

            foreach ($participants as $i => $p) {
                fputcsv($out, [
                    $i + 1,
                    $p->name,
                    $p->team_name ?? '-',
                    $p->total_score,
                    $p->total_speed_bonus,
                ], ';');
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* ─────────────────────────────────────────────────────────────
       Analitik sayfası
    ───────────────────────────────────────────────────────────── */
    public function analytics(string $adminToken)
    {
        $session = PresentationSession::where('admin_token', $adminToken)
            ->with(['quiz.questions' => fn ($q) => $q->orderBy('position')])
            ->firstOrFail();

        // Her soru için istatistik
        $stats = $session->quiz->questions->map(function ($q) use ($session) {
            $answers = Answer::where('question_id', $q->id)
                ->where('presentation_session_id', $session->id)
                ->get();

            $total   = $answers->count();
            $correct = $answers->where('is_correct', true)->count();
            $avgMs   = $total > 0 ? (int) $answers->avg('response_time_ms') : 0;

            $distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
            foreach ($answers as $a) {
                $distribution[$a->selected_option]++;
            }

            return [
                'question'     => $q,
                'total'        => $total,
                'correct'      => $correct,
                'wrong'        => $total - $correct,
                'correct_pct'  => $total > 0 ? round($correct / $total * 100) : 0,
                'avg_ms'       => $avgMs,
                'distribution' => $distribution,
            ];
        });

        // Genel
        $overall = [
            'total_participants' => $session->participants()->count(),
            'total_answers'      => Answer::where('presentation_session_id', $session->id)->count(),
            'avg_score'          => (int) $session->participants()->avg('total_score'),
        ];

        return view('presentation.analytics', compact('session', 'stats', 'overall'));
    }

    /* ─────────────────────────────────────────────────────────────
       Katılım formu
    ───────────────────────────────────────────────────────────── */
    public function joinForm(string $code)
    {
        $session = PresentationSession::where('code', $code)->firstOrFail();
        return view('participant.join', compact('session'));
    }

    /* ─────────────────────────────────────────────────────────────
       Katılımcı kaydı
    ───────────────────────────────────────────────────────────── */
    public function registerParticipant(Request $request, string $code)
    {
        $session = PresentationSession::where('code', $code)->firstOrFail();

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:50'],
            'team_name' => ['nullable', 'string', 'max:50'],
        ]);

        $participant = Participant::create([
            'presentation_session_id' => $session->id,
            'name'                    => $data['name'],
            'team_name'               => $data['team_name'] ?? null,
        ]);

        event(new ParticipantJoined($session, $participant));

        return redirect()->route('participant.play', [$session->code, $participant->id]);
    }

    /* ─────────────────────────────────────────────────────────────
       Oyuncu ekranı (play)
    ───────────────────────────────────────────────────────────── */
    public function participantView(string $code, Participant $participant)
    {
        $session = PresentationSession::where('code', $code)
            ->with(['quiz.questions' => fn ($q) => $q->orderBy('position')])
            ->firstOrFail();

        $currentQuestion = $session->quiz->questions->firstWhere('id', $session->current_question_id);

        return view('participant.play', compact('session', 'participant', 'currentQuestion'));
    }

    /* ─────────────────────────────────────────────────────────────
       Cevap gönder
    ───────────────────────────────────────────────────────────── */
    public function submitAnswer(Request $request, string $code, Participant $participant)
    {
        $session = PresentationSession::where('code', $code)->firstOrFail();

        // Cevaplar kilitli mi?
        if ($session->answers_locked) {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => 'Cevaplar kilitli.'], 422)
                : back();
        }

        $data = $request->validate([
            'selected_option' => ['required', 'in:A,B,C,D'],
            'client_sent_at'  => ['nullable'],
        ]);

        $question = $session->quiz->questions()->where('id', $session->current_question_id)->first();
        if (!$question) {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => 'Aktif soru yok.'], 422)
                : back();
        }

        // Zaman sınırı kontrolü (server-side)
        if ($session->time_limit > 0 && $session->current_question_started_at) {
            $elapsedSeconds = $session->current_question_started_at->diffInSeconds(now());
            if ($elapsedSeconds > $session->time_limit) {
                return $request->wantsJson()
                    ? response()->json(['ok' => false, 'message' => 'Süre doldu.'], 422)
                    : back();
            }
        }

        // Aynı soruya tekrar cevap engelle
        $alreadyAnswered = Answer::where('participant_id', $participant->id)
            ->where('question_id', $question->id)
            ->exists();

        if ($alreadyAnswered) {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => 'Zaten cevaplandı.'], 422)
                : back();
        }

        $now        = now();
        $start      = $session->current_question_started_at ?? $now;
        $responseMs = max(0, $start->diffInMilliseconds($now));

        $isCorrect  = $data['selected_option'] === $question->correct_option;
        $basePoints = $isCorrect ? $question->points : 0;
        $speedBonus = 0;
        if ($isCorrect) {
            $speedBonus = max(0, 50 - intdiv($responseMs, 200));
        }

        Answer::create([
            'participant_id'          => $participant->id,
            'question_id'             => $question->id,
            'presentation_session_id' => $session->id,
            'selected_option'         => $data['selected_option'],
            'is_correct'              => $isCorrect,
            'base_points'             => $basePoints,
            'speed_bonus_points'      => $speedBonus,
            'response_time_ms'        => $responseMs,
        ]);

        if ($basePoints > 0 || $speedBonus > 0) {
            $participant->increment('total_score', $basePoints);
            $participant->increment('total_speed_bonus', $speedBonus);
        }
        $participant->refresh();

        // Cevap dağılımı
        $answerCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
        Answer::where('question_id', $question->id)
            ->where('presentation_session_id', $session->id)
            ->selectRaw('selected_option, count(*) as cnt')
            ->groupBy('selected_option')
            ->get()
            ->each(fn ($row) => $answerCounts[$row->selected_option] = (int) $row->cnt);

        $totalAnswers = array_sum($answerCounts);

        // Canlı sıralama (top 10)
        $scoreboard = $session->participants()
            ->orderByDesc('total_score')
            ->orderByDesc('total_speed_bonus')
            ->take(10)
            ->get(['id', 'name', 'team_name', 'total_score', 'total_speed_bonus'])
            ->map(fn ($p) => [
                'id'                => $p->id,
                'name'              => $p->name,
                'team_name'         => $p->team_name,
                'total_score'       => $p->total_score,
                'total_speed_bonus' => $p->total_speed_bonus,
            ])
            ->toArray();

        // Broadcast — Pusher hatası olsa bile cevap kaydedildi
        try {
            event(new AnswerSubmitted(
                $session,
                $participant,
                $data['selected_option'],
                $question->id,
                $answerCounts,
                $totalAnswers,
                $scoreboard,
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AnswerSubmitted broadcast failed: ' . $e->getMessage());
        }

        return $request->wantsJson()
            ? response()->json([
                'ok'          => true,
                'is_correct'  => $isCorrect,
                'base_points' => $basePoints,
                'speed_bonus' => $speedBonus,
            ])
            : back();
    }
}
