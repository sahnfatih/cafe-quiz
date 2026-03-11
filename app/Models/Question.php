<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'quiz_id',
        'position',
        'text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'points',
        'media_type',
        'image_path',
        'youtube_url',
        'youtube_start',
        'youtube_end',
        'is_active',
    ];

    protected $casts = [
        'points' => 'integer',
        'youtube_start' => 'integer',
        'youtube_end' => 'integer',
        'is_active' => 'boolean',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
