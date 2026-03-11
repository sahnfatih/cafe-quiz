<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quiz = Quiz::create([
            'title' => 'Genel Kültür Gecesi',
            'description' => 'Kafede hızlı ve eğlenceli bir genel kültür yarışması.',
            'is_active' => true,
        ]);

        $questions = [
            [
                'text' => 'Türkiye\'nin başkenti neresidir?',
                'option_a' => 'Ankara',
                'option_b' => 'İstanbul',
                'option_c' => 'İzmir',
                'option_d' => 'Bursa',
                'correct_option' => 'A',
                'points' => 100,
            ],
            [
                'text' => 'Aşağıdakilerden hangisi bir gezegen değildir?',
                'option_a' => 'Mars',
                'option_b' => 'Venüs',
                'option_c' => 'Plüton',
                'option_d' => 'Ay',
                'correct_option' => 'D',
                'points' => 150,
            ],
            [
                'text' => 'YouTube start-end örnek sorusu: Videoya göre hangi yıl gösteriliyor?',
                'option_a' => '1990',
                'option_b' => '2000',
                'option_c' => '2010',
                'option_d' => '2020',
                'correct_option' => 'B',
                'points' => 200,
                'media_type' => 'youtube',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'youtube_start' => 30,
                'youtube_end' => 40,
            ],
        ];

        $position = 1;
        foreach ($questions as $q) {
            Question::create([
                'quiz_id' => $quiz->id,
                'position' => $position++,
                'text' => $q['text'],
                'option_a' => $q['option_a'],
                'option_b' => $q['option_b'],
                'option_c' => $q['option_c'] ?? null,
                'option_d' => $q['option_d'] ?? null,
                'correct_option' => $q['correct_option'],
                'points' => $q['points'],
                'media_type' => $q['media_type'] ?? 'none',
                'image_path' => $q['image_path'] ?? null,
                'youtube_url' => $q['youtube_url'] ?? null,
                'youtube_start' => $q['youtube_start'] ?? null,
                'youtube_end' => $q['youtube_end'] ?? null,
                'is_active' => true,
            ]);
        }
    }
}
