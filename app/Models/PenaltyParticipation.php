<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenaltyParticipation extends Model
{
    protected $attributes = [
        'attempts' => 0,
        'goals' => 0,
        'completed' => false,
    ];

    protected $fillable = [
        'first_name',
        'play_token',
        'last_name',
        'phone_number',
        'team',
        'attempts',
        'goals',
        'completed',
        'prize_label',
        'accepted_terms',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'goals' => 'integer',
        'completed' => 'boolean',
        'accepted_terms' => 'boolean',
    ];
}
