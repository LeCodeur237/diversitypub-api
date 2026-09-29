<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartPenaltyGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'phoneNumber' => ['required', 'string', 'regex:/^(01|05|07)[0-9]{8}$/'],
            'team' => ['required', 'string', 'in:ivory-coast,ghana'],
            'acceptedTerms' => ['required', 'accepted'],
        ];
    }
}
