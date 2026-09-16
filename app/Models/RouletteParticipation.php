<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouletteParticipation extends Model
{
    protected $fillable = [
        'game',
        'device_id',
        'first_name',
        'last_name',
        'age',
        'phone_number',
        'won',
        'prize_label',
    ];

    protected $casts = [
        'age' => 'integer',
        'won' => 'boolean',
    ];
}
