<?php

namespace App\Events;

use App\Models\PresentationSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantKicked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PresentationSession $session,
        public int $participantId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('quiz.session.' . $this->session->code),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ParticipantKicked';
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participantId,
        ];
    }
}
