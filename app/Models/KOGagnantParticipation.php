<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KOGagnantParticipation extends Model
{
    protected $table = 'ko_gagnant_participations';

    protected $attributes = [
        'game' => 'ko-gagnant',
        'won' => false,
        'prize_claimed' => false,
    ];

    protected $fillable = [
        'game',
        'first_name',
        'last_name',
        'commune',
        'phone_number',
        'reseau',
        'taps',
        'duration_ms',
        'won',
        'prize_label',
        'prize_type',
        'prize_icon',
        'wave_number',
        'accepted_terms',
        'prize_claimed',
        'claimed_at',
    ];

    protected $casts = [
        'taps' => 'integer',
        'duration_ms' => 'integer',
        'won' => 'boolean',
        'accepted_terms' => 'boolean',
        'prize_claimed' => 'boolean',
        'claimed_at' => 'datetime',
    ];
}
