<?php

namespace App\Livewire\Display;

use App\Models\PresentationSession;
use App\Models\Question;
use Livewire\Component;

class Board extends Component
{
    public PresentationSession $session;

    /**
     * @var array|null {
     *   id, text, option_a, option_b, option_c, option_d,
     *   media_type, image_path, youtube_url, youtube_start, youtube_end, points
     * }
     */
    public ?array $question = null;

    public string $mode = 'pending';

    /** @var array<int, array{name:string,total_score:int,total_speed_bonus:int}> */
    public array $topParticipants = [];

    // Alpine ile iframe src'sini güncellemek için
    public ?string $videoUrl = null;

    public function mount(string $code): void
    {
        $this->session = PresentationSession::with(['quiz.questions' => fn ($q) => $q->orderBy('position')])
            ->where('code', $code)
            ->firstOrFail();

        if ($this->session->current_question_id) {
            $q = $this->session->quiz->questions->firstWhere('id', $this->session->current_question_id);
            $this->setFromQuestion($q, 'initial');
        }
    }

    protected function setFromQuestion(?Question $q, string $mode): void
    {
        $this->mode = $mode;

        if (! $q) {
            $this->question = null;
            $this->videoUrl = null;
            return;
        }

        $this->question = [
            'id' => $q->id,
            'text' => $q->text,
            'option_a' => $q->option_a,
            'option_b' => $q->option_b,
            'option_c' => $q->option_c,
            'option_d' => $q->option_d,
            'media_type' => $q->media_type,
            'image_path' => $q->image_path,
            'youtube_url' => $q->youtube_url,
            'youtube_start' => $q->youtube_start,
            'youtube_end' => $q->youtube_end,
            'points' => $q->points,
        ];

        $this->videoUrl = $this->buildYoutubeEmbedUrl(
            $q->youtube_url,
            $q->youtube_start,
            $q->youtube_end
        );
    }

    protected function buildYoutubeEmbedUrl(?string $url, ?int $start, ?int $end): ?string
    {
        if (! $url) {
            return null;
        }

        try {
            $parts = parse_url($url);
            $videoId = null;

            if (! empty($parts['query'])) {
                parse_str($parts['query'], $qs);
                $videoId = $qs['v'] ?? null;
            }

            if (! $videoId && ! empty($parts['path'])) {
                $segments = explode('/', trim($parts['path'], '/'));
                $videoId = end($segments);
            }

            if (! $videoId) {
                return null;
            }

            $params = ['autoplay' => 1];
            if ($start !== null) {
                $params['start'] = $start;
            }
            if ($end !== null) {
                $params['end'] = $end;
            }

            return 'https://www.youtube.com/embed/'.$videoId.'?'.http_build_query($params);
        } catch (\Throwable) {
            return null;
        }
    }

    // JS tarafında Echo ile yakalanan QuizStateUpdated payload'ını Livewire'a köprülemek için
    public function handleQuizUpdate(array $payload): void
    {
        $this->session->status = $payload['session']['status'] ?? $this->session->status;
        $this->session->current_question_id = $payload['session']['current_question_id'] ?? $this->session->current_question_id;

        $this->mode = $payload['mode'] ?? $this->mode;
        $this->topParticipants = $payload['top_participants'] ?? [];

        if (! empty($payload['question'])) {
            $qData = $payload['question'];
            $this->question = $qData;

            $this->videoUrl = $this->buildYoutubeEmbedUrl(
                $qData['youtube_url'] ?? null,
                $qData['youtube_start'] ?? null,
                $qData['youtube_end'] ?? null
            );
        } else {
            $this->question = null;
            $this->videoUrl = null;
        }
    }

    public function render()
    {
        return view('livewire.display.board')
            ->layout('layouts.display');
    }
}

