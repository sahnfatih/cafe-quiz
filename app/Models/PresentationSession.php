<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresentationSession extends Model
{
    protected $fillable = [
        'quiz_id',
        'code',
        'admin_token',
        'status',
        'time_limit',
        'answers_locked',
        'current_question_id',
        'current_question_started_at',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at'                  => 'datetime',
        'ended_at'                    => 'datetime',
        'current_question_started_at' => 'datetime',
        'answers_locked'              => 'boolean',
        'time_limit'                  => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
