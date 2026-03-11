<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'presentation_session_id',
        'name',
        'total_score',
        'total_speed_bonus',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PresentationSession::class, 'presentation_session_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
