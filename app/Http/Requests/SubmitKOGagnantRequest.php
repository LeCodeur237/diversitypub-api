<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SubmitKOGagnantRequest',
    type: 'object',
    required: ['firstName', 'lastName', 'commune', 'phoneNumber', 'reseau', 'taps', 'acceptedTerms'],
    properties: [
        new OA\Property(property: 'firstName', type: 'string', example: 'Jean'),
        new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
        new OA\Property(property: 'commune', type: 'string', example: 'Cocody'),
        new OA\Property(property: 'phoneNumber', type: 'string', example: '0701020304'),
        new OA\Property(property: 'reseau', type: 'string', enum: ['orange', 'mtn', 'moov'], example: 'orange'),
        new OA\Property(property: 'taps', type: 'integer', example: 75),
        new OA\Property(property: 'acceptedTerms', type: 'boolean', example: true),
        new OA\Property(property: 'durationMs', type: 'integer', nullable: true, example: 10000),
    ]
)]
class SubmitKOGagnantRequest extends FormRequest
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
            'commune' => ['required', 'string', 'max:255'],
            'phoneNumber' => ['required', 'string', 'regex:/^(01|05|07)[0-9]{8}$/'],
            'reseau' => ['required', 'string', 'in:orange,mtn,moov'],
            'taps' => ['required', 'integer', 'between:0,140'],
            'acceptedTerms' => ['required', 'accepted'],
            'durationMs' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $phoneNumber = (string) $this->input('phoneNumber');
            $reseau = (string) $this->input('reseau');

            $expectedPrefix = match ($reseau) {
                'orange' => '07',
                'mtn' => '05',
                'moov' => '01',
                default => null,
            };

            if ($expectedPrefix && ! str_starts_with($phoneNumber, $expectedPrefix)) {
                $validator->errors()->add('phoneNumber', 'Le numero de telephone ne correspond pas au reseau choisi.');
            }
        });
    }
}
