<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    protected $fillable = [
        'participant_id',
        'question_id',
        'presentation_session_id',
        'selected_option',
        'is_correct',
        'base_points',
        'speed_bonus_points',
        'response_time_ms',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'base_points' => 'integer',
        'speed_bonus_points' => 'integer',
        'response_time_ms' => 'integer',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PresentationSession::class, 'presentation_session_id');
    }
}
