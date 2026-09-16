<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitRouletteParticipationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deviceId' => ['required', 'string', 'size:36', 'regex:/^[a-f0-9-]{36}$/i'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'between:25,100'],
            'phone' => ['required', 'string', 'max:30'],
            'won' => ['required', 'boolean'],
            'prize' => ['nullable', 'string', 'max:255'],
        ];
    }
}
