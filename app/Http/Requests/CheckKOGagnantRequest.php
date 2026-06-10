<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesKOGagnantPhoneNetwork;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CheckKOGagnantRequest',
    type: 'object',
    required: ['phoneNumber', 'reseau'],
    properties: [
        new OA\Property(property: 'phoneNumber', type: 'string', example: '0700000000'),
        new OA\Property(property: 'reseau', type: 'string', enum: ['orange', 'mtn', 'moov'], example: 'orange'),
    ]
)]
class CheckKOGagnantRequest extends FormRequest
{
    use ValidatesKOGagnantPhoneNetwork;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phoneNumber' => ['required', 'string', 'regex:/^(01|05|07)[0-9]{8}$/'],
            'reseau' => ['required', 'string', 'in:orange,mtn,moov'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->addKOGagnantPhoneNetworkValidation($validator);
    }
}
