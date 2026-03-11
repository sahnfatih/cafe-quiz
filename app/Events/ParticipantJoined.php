<?php

namespace App\Events;

use App\Models\Participant;
use App\Models\PresentationSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantJoined implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PresentationSession $session,
        public Participant $participant,
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
        return 'ParticipantJoined';
    }

    public function broadcastWith(): array
    {
        return [
            'participant' => [
                'id' => $this->participant->id,
                'name' => $this->participant->name,
                'total_score' => $this->participant->total_score,
                'total_speed_bonus' => $this->participant->total_speed_bonus,
            ],
            'total' => $this->session->participants()->count(),
        ];
    }
}
