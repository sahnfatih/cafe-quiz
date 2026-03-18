<?php

namespace App\Events;

use App\Models\Participant;
use App\Models\PresentationSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnswerSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PresentationSession $session,
        public Participant $participant,
        public string $selectedOption,
        public int $questionId,
        public array $answerCounts,   // ['A' => n, 'B' => n, 'C' => n, 'D' => n]
        public int $totalAnswers,
        public array $scoreboard,     // top 10 katılımcı
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('quiz.session.' . $this->session->code),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AnswerSubmitted';
    }

    public function broadcastWith(): array
    {
        return [
            'participant' => [
                'id'                => $this->participant->id,
                'name'              => $this->participant->name,
                'total_score'       => $this->participant->total_score,
                'total_speed_bonus' => $this->participant->total_speed_bonus,
                'selected_option'   => $this->selectedOption,
            ],
            'question_id'   => $this->questionId,
            'answer_counts' => $this->answerCounts,
            'total_answers' => $this->totalAnswers,
            'scoreboard'    => $this->scoreboard,
        ];
    }
}
