<?php

namespace App\Events;

use App\Models\PresentationSession;
use App\Models\Question;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PresentationSession $session,
        public ?Question $question,
        public string $mode, // next, prev, show_results, start, finish
        public array $topParticipants = [],
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('quiz.session.' . $this->session->code),
        ];
    }

    public function broadcastAs(): string
    {
        return 'QuizStateUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'session' => [
                'id' => $this->session->id,
                'code' => $this->session->code,
                'status' => $this->session->status,
                'current_question_id' => $this->session->current_question_id,
            ],
            'question' => $this->question ? [
                'id' => $this->question->id,
                'text' => $this->question->text,
                'option_a' => $this->question->option_a,
                'option_b' => $this->question->option_b,
                'option_c' => $this->question->option_c,
                'option_d' => $this->question->option_d,
                'media_type' => $this->question->media_type,
                'image_path' => $this->question->image_path,
                'youtube_url' => $this->question->youtube_url,
                'youtube_start' => $this->question->youtube_start,
                'youtube_end' => $this->question->youtube_end,
            ] : null,
            'mode' => $this->mode,
            'top_participants' => $this->topParticipants,
        ];
    }
}
