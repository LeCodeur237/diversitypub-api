<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ClaimKOGagnantRequest',
    type: 'object',
    required: ['participationId', 'waveNumber'],
    properties: [
        new OA\Property(property: 'participationId', type: 'integer', example: 123),
        new OA\Property(property: 'waveNumber', type: 'string', example: '0700000000'),
    ]
)]
class ClaimKOGagnantPrizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'participationId' => ['required', 'integer', 'min:1'],
            'waveNumber' => ['required', 'string', 'regex:/^(01|05|07)[0-9]{8}$/'],
        ];
    }
}
