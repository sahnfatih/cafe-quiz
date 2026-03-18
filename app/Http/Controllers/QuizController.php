<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuizController extends Controller
{
    /* ─── Quiz Listesi ─── */
    public function index()
    {
        $quizzes = Quiz::withCount('questions')->latest()->get();
        return view('admin.quizzes.index', compact('quizzes'));
    }

    /* ─── Quiz Detayı ─── */
    public function show(Quiz $quiz)
    {
        $quiz->load('questions');
        return view('admin.quizzes.show', compact('quiz'));
    }

    /* ─── Quiz Oluştur ─── */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $quiz = Quiz::create($data);
        return redirect()->route('admin.quizzes.show', $quiz)->with('status', 'Quiz oluşturuldu!');
    }

    /* ─── Quiz Güncelle ─── */
    public function update(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $quiz->update($data);
        return redirect()->route('admin.quizzes.show', $quiz)->with('status', 'Quiz güncellendi!');
    }

    /* ─── Quiz Sil ─── */
    public function destroy(Quiz $quiz)
    {
        // Soruların medya dosyalarını temizle
        foreach ($quiz->questions as $q) {
            if ($q->image_path)  Storage::disk('public')->delete($q->image_path);
            if ($q->video_path)  Storage::disk('public')->delete($q->video_path);
        }
        $quiz->delete();
        return redirect()->route('admin.quizzes.index')->with('status', 'Quiz silindi.');
    }

    /* ─── Soru Ekle ─── */
    public function addQuestion(Request $request, Quiz $quiz)
    {
        $data = $this->validateQuestion($request);
        $position = ($quiz->questions()->max('position') ?? 0) + 1;

        [$imagePath, $videoPath] = $this->handleMediaUploads($request, null);

        Question::create([
            'quiz_id'        => $quiz->id,
            'position'       => $position,
            'text'           => $data['text'],
            'option_a'       => $data['option_a'],
            'option_b'       => $data['option_b'],
            'option_c'       => $data['option_c'] ?? null,
            'option_d'       => $data['option_d'] ?? null,
            'correct_option' => $data['correct_option'],
            'points'         => $data['points'],
            'media_type'     => $data['media_type'],
            'image_path'     => $imagePath,
            'video_path'     => $videoPath,
            'youtube_url'    => $data['youtube_url'] ?? null,
            'youtube_start'  => $data['youtube_start'] ?? null,
            'youtube_end'    => $data['youtube_end'] ?? null,
            'is_active'      => true,
        ]);

        return redirect()->route('admin.quizzes.show', $quiz)->with('status', 'Soru eklendi!');
    }

    /* ─── Soru Güncelle ─── */
    public function updateQuestion(Request $request, Quiz $quiz, Question $question)
    {
        $data = $this->validateQuestion($request);

        [$imagePath, $videoPath] = $this->handleMediaUploads($request, $question);

        $question->update([
            'text'           => $data['text'],
            'option_a'       => $data['option_a'],
            'option_b'       => $data['option_b'],
            'option_c'       => $data['option_c'] ?? null,
            'option_d'       => $data['option_d'] ?? null,
            'correct_option' => $data['correct_option'],
            'points'         => $data['points'],
            'media_type'     => $data['media_type'],
            'image_path'     => $imagePath,
            'video_path'     => $videoPath,
            'youtube_url'    => $data['youtube_url'] ?? null,
            'youtube_start'  => $data['youtube_start'] ?? null,
            'youtube_end'    => $data['youtube_end'] ?? null,
        ]);

        return redirect()->route('admin.quizzes.show', $quiz)->with('status', 'Soru güncellendi!');
    }

    /* ─── Soru Sil ─── */
    public function destroyQuestion(Quiz $quiz, Question $question)
    {
        if ($question->image_path) Storage::disk('public')->delete($question->image_path);
        if ($question->video_path) Storage::disk('public')->delete($question->video_path);
        $question->delete();

        // Pozisyonları yeniden sırala
        $quiz->questions()->orderBy('position')->get()->each(function ($q, $i) {
            $q->update(['position' => $i + 1]);
        });

        return redirect()->route('admin.quizzes.show', $quiz)->with('status', 'Soru silindi.');
    }

    /* ─── Yardımcı: Validasyon ─── */
    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'text'           => ['required', 'string'],
            'option_a'       => ['required', 'string'],
            'option_b'       => ['required', 'string'],
            'option_c'       => ['nullable', 'string'],
            'option_d'       => ['nullable', 'string'],
            'correct_option' => ['required', 'in:A,B,C,D'],
            'points'         => ['required', 'integer', 'min:1'],
            'media_type'     => ['required', 'in:none,image,video,youtube'],
            'image'          => ['nullable', 'image', 'max:51200'],   // 50 MB
            'video'          => ['nullable', 'mimetypes:video/mp4,video/webm,video/ogg', 'max:204800'], // 200 MB
            'youtube_url'    => ['nullable', 'url'],
            'youtube_start'  => ['nullable', 'integer', 'min:0'],
            'youtube_end'    => ['nullable', 'integer', 'min:0'],
        ]);
    }

    /* ─── Yardımcı: Medya Yükleme ─── */
    private function handleMediaUploads(Request $request, ?Question $existing): array
    {
        $imagePath = $existing?->image_path;
        $videoPath = $existing?->video_path;

        if ($request->hasFile('image')) {
            if ($imagePath) Storage::disk('public')->delete($imagePath);
            $imagePath = $request->file('image')->store('questions/images', 'public');
        }

        if ($request->hasFile('video')) {
            if ($videoPath) Storage::disk('public')->delete($videoPath);
            $videoPath = $request->file('video')->store('questions/videos', 'public');
        }

        // Medya tipi değiştiyse eski dosyaları temizle
        $newType = $request->input('media_type', 'none');
        if ($existing) {
            if ($newType !== 'image' && $existing->image_path && !$request->hasFile('image')) {
                Storage::disk('public')->delete($existing->image_path);
                $imagePath = null;
            }
            if ($newType !== 'video' && $existing->video_path && !$request->hasFile('video')) {
                Storage::disk('public')->delete($existing->video_path);
                $videoPath = null;
            }
        }

        return [$imagePath, $videoPath];
    }
}
