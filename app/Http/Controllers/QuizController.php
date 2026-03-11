<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::latest()->get();

        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function show(Quiz $quiz)
    {
        $quiz->load('questions');

        return view('admin.quizzes.show', compact('quiz'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $quiz = Quiz::create($data);

        return redirect()->route('admin.quizzes.show', $quiz);
    }

    public function addQuestion(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'text' => ['required', 'string'],
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'correct_option' => ['required', 'in:A,B,C,D'],
            'points' => ['required', 'integer', 'min:1'],
            'media_type' => ['required', 'in:none,image,youtube'],
            'image' => ['nullable', 'image'],
            'youtube_url' => ['nullable', 'url'],
            'youtube_start' => ['nullable', 'integer', 'min:0'],
            'youtube_end' => ['nullable', 'integer', 'min:0'],
        ]);

        $position = ($quiz->questions()->max('position') ?? 0) + 1;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('questions', 'public');
        }

        $question = new Question();
        $question->fill([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'text' => $data['text'],
            'option_a' => $data['option_a'],
            'option_b' => $data['option_b'],
            'option_c' => $data['option_c'] ?? null,
            'option_d' => $data['option_d'] ?? null,
            'correct_option' => $data['correct_option'],
            'points' => $data['points'],
            'media_type' => $data['media_type'],
            'image_path' => $imagePath,
            'youtube_url' => $data['youtube_url'] ?? null,
            'youtube_start' => $data['youtube_start'] ?? null,
            'youtube_end' => $data['youtube_end'] ?? null,
            'is_active' => true,
        ]);
        $question->save();

        return redirect()->route('admin.quizzes.show', $quiz)->with('status', 'Soru eklendi.');
    }
}
