<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShootPenaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'playToken' => ['required', 'uuid', 'exists:penalty_participations,play_token'],
            'targetX' => ['required', 'numeric', 'between:0,100'],
            'targetY' => ['required', 'numeric', 'between:0,100'],
            'pitchWidth' => ['sometimes', 'numeric', 'between:280,1000'],
            'pitchHeight' => ['sometimes', 'numeric', 'between:300,900'],
        ];
    }
}
