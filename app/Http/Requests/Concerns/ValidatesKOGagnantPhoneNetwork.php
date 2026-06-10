<?php

namespace App\Http\Requests\Concerns;

trait ValidatesKOGagnantPhoneNetwork
{
    protected function addKOGagnantPhoneNetworkValidation($validator): void
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
