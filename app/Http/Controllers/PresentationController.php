<?php

namespace App\Http\Controllers;

use App\Events\QuizStateUpdated;
use App\Events\ParticipantJoined;
use App\Models\Answer;
use App\Models\Participant;
use App\Models\PresentationSession;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PresentationController extends Controller
{
    public function startSession(Quiz $quiz)
    {
        $session = PresentationSession::create([
            'quiz_id' => $quiz->id,
            'code' => strtoupper(Str::random(4)),
            'admin_token' => Str::uuid()->toString(),
            'status' => 'pending',
        ]);

        return redirect()->route('remote.show', $session->admin_token);
    }

    public function display(string $code)
    {
        $session = PresentationSession::where('code', $code)
            ->with(['quiz.questions' => fn ($q) => $q->orderBy('position')])
            ->firstOrFail();

        $currentQuestion = $session->quiz->questions->firstWhere('id', $session->current_question_id);

        return view('presentation.display', compact('session', 'currentQuestion'));
    }

    public function remote(string $adminToken)
    {
        $session = PresentationSession::where('admin_token', $adminToken)
            ->with(['quiz.questions' => fn ($q) => $q->orderBy('position'), 'participants'])
            ->firstOrFail();

        $currentQuestion = $session->quiz->questions->firstWhere('id', $session->current_question_id);

        return view('presentation.remote', compact('session', 'currentQuestion'));
    }

    public function control(Request $request, string $adminToken)
    {
        $session = PresentationSession::where('admin_token', $adminToken)->with('quiz.questions')->firstOrFail();
        $mode = $request->input('mode', 'next');

        $questions = $session->quiz->questions;
        $current = $session->current_question_id
            ? $questions->firstWhere('id', $session->current_question_id)
            : null;

        $targetQuestion = null;

        if ($mode === 'next') {
            if (!$current) {
                $targetQuestion = $questions->first();
            } else {
                $targetQuestion = $questions->firstWhere('position', '>', $current->position) ?? $current;
            }
            $session->status = 'running';
            $session->current_question_id = $targetQuestion?->id;
            $session->current_question_started_at = now();
            $session->save();
        } elseif ($mode === 'prev') {
            if ($current) {
                $targetQuestion = $questions->where('position', '<', $current->position)->sortByDesc('position')->first() ?? $current;
            } else {
                $targetQuestion = $questions->first();
            }
            $session->status = 'running';
            $session->current_question_id = $targetQuestion?->id;
            $session->current_question_started_at = now();
            $session->save();
        } elseif ($mode === 'show_results' || $mode === 'finish') {
            $session->status = 'finished';
            $session->ended_at = now();
            $session->save();
        } elseif ($mode === 'goto') {
            $targetId = (int) $request->input('question_id');
            $targetQuestion = $questions->firstWhere('id', $targetId);
            if ($targetQuestion) {
                $session->status = 'running';
                $session->current_question_id = $targetQuestion->id;
                $session->current_question_started_at = now();
                $session->save();
            }
        }

        $top = [];
        if ($mode === 'show_results' || $mode === 'finish') {
            $top = $session->participants()
                ->orderByDesc('total_score')
                ->orderByDesc('total_speed_bonus')
                ->take(3)
                ->get(['name', 'total_score', 'total_speed_bonus'])
                ->map(fn ($p) => [
                    'name' => $p->name,
                    'total_score' => $p->total_score,
                    'total_speed_bonus' => $p->total_speed_bonus,
                ])
                ->toArray();
        }

        $questionModel = $targetQuestion ?? $current;

        event(new QuizStateUpdated($session->fresh(), $questionModel, $mode, $top));

        return back();
    }

    public function joinForm(string $code)
    {
        $session = PresentationSession::where('code', $code)->firstOrFail();

        return view('participant.join', compact('session'));
    }

    public function registerParticipant(Request $request, string $code)
    {
        $session = PresentationSession::where('code', $code)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        $participant = Participant::create([
            'presentation_session_id' => $session->id,
            'name' => $data['name'],
        ]);

        event(new ParticipantJoined($session, $participant));

        return redirect()->route('participant.play', [$session->code, $participant->id]);
    }

    public function participantView(string $code, Participant $participant)
    {
        $session = PresentationSession::where('code', $code)
            ->with(['quiz.questions' => fn ($q) => $q->orderBy('position')])
            ->firstOrFail();

        $currentQuestion = $session->quiz->questions->firstWhere('id', $session->current_question_id);

        return view('participant.play', compact('session', 'participant', 'currentQuestion'));
    }

    public function submitAnswer(Request $request, string $code, Participant $participant)
    {
        $session = PresentationSession::where('code', $code)->firstOrFail();

        $data = $request->validate([
            'selected_option' => ['required', 'in:A,B,C,D'],
            'client_sent_at' => ['nullable'],
        ]);

        $question = $session->quiz->questions()->where('id', $session->current_question_id)->first();
        if (! $question) {
            return back();
        }

        $now = now();
        $start = $session->current_question_started_at ?? $now;
        $responseMs = max(0, $start->diffInMilliseconds($now));

        $isCorrect = $data['selected_option'] === $question->correct_option;
        $basePoints = $isCorrect ? $question->points : 0;

        $speedBonus = 0;
        if ($isCorrect) {
            $speedBonus = max(0, 50 - intdiv($responseMs, 200));
        }

        Answer::create([
            'participant_id' => $participant->id,
            'question_id' => $question->id,
            'presentation_session_id' => $session->id,
            'selected_option' => $data['selected_option'],
            'is_correct' => $isCorrect,
            'base_points' => $basePoints,
            'speed_bonus_points' => $speedBonus,
            'response_time_ms' => $responseMs,
        ]);

        if ($basePoints > 0 || $speedBonus > 0) {
            $participant->increment('total_score', $basePoints);
            $participant->increment('total_speed_bonus', $speedBonus);
        }

        return back();
    }
}
