<?php

namespace App\Events;

use App\Models\PresentationSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnswersLocked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PresentationSession $session,
        public bool $locked,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('quiz.session.' . $this->session->code),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AnswersLocked';
    }

    public function broadcastWith(): array
    {
        return [
            'locked' => $this->locked,
        ];
    }
}
