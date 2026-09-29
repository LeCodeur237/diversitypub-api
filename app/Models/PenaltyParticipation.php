<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenaltyParticipation extends Model
{
    protected $attributes = [
        'attempts' => 0,
        'goals' => 0,
        'keeper_x' => 50,
        'keeper_y' => 40,
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
        'keeper_x',
        'keeper_y',
        'completed',
        'prize_label',
        'accepted_terms',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'goals' => 'integer',
        'keeper_x' => 'float',
        'keeper_y' => 'float',
        'completed' => 'boolean',
        'accepted_terms' => 'boolean',
    ];
}
