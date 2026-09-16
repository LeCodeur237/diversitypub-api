<?php

namespace App\Services;

use App\Models\RouletteParticipation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RouletteParticipationService
{
    public function submit(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $existing = RouletteParticipation::query()
                ->where('device_id', $data['deviceId'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $this->formatExisting($existing);
            }

            try {
                $participation = RouletteParticipation::create([
                    'game' => 'roulette',
                    'device_id' => $data['deviceId'],
                    'first_name' => $data['firstName'],
                    'last_name' => $data['lastName'],
                    'age' => $data['age'],
                    'phone_number' => $data['phone'],
                    'won' => $data['won'],
                    'prize_label' => $data['prize'] ?? null,
                ]);
            } catch (QueryException $exception) {
                if (! $this->isUniqueDeviceViolation($exception)) {
                    throw $exception;
                }

                $existing = RouletteParticipation::query()
                    ->where('device_id', $data['deviceId'])
                    ->firstOrFail();

                return $this->formatExisting($existing);
            }

            return [
                'ok' => true,
                'alreadyPlayed' => false,
                'participationId' => $participation->id,
                'won' => $participation->won,
                'prize' => $participation->prize_label,
                'message' => 'Participation enregistree.',
            ];
        });
    }

    private function formatExisting(RouletteParticipation $participation): array
    {
        return [
            'ok' => true,
            'alreadyPlayed' => true,
            'participationId' => $participation->id,
            'won' => $participation->won,
            'prize' => $participation->prize_label,
            'message' => 'Cet appareil a deja participe.',
        ];
    }

    private function isUniqueDeviceViolation(QueryException $exception): bool
    {
        return str_contains(strtolower($exception->getMessage()), 'roulette_participations_device_id_unique');
    }
}
