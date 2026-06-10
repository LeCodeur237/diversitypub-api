<?php

namespace App\Services;

use App\Models\KOGagnantParticipation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KOGagnantService
{
    private const MIN_TAPS_TO_WIN = 60;

    private array $prizes = [
        [
            'label' => 'Depot Wave de 100 Fcfa',
            'type' => 'wave',
            'icon' => 'bi-wallet2',
            'min_taps' => 60,
            'max_taps' => 89,
        ],
        [
            'label' => 'Depot Wave de 200 Fcfa',
            'type' => 'wave',
            'icon' => 'bi-wallet2',
            'min_taps' => 90,
            'max_taps' => 119,
        ],
        [
            'label' => '500 Mo de Data',
            'type' => 'data',
            'icon' => 'bi-wifi',
            'min_taps' => 120,
            'max_taps' => 140,
        ],
    ];

    public function submit(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $existing = KOGagnantParticipation::query()
                ->where('phone_number', $data['phoneNumber'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return [
                    'ok' => true,
                    'alreadyPlayed' => true,
                    'participationId' => $existing->id,
                    'won' => (bool) $existing->won,
                    'prize' => $this->formatPrize($existing),
                    'message' => 'Ce numero a deja participe.',
                ];
            }

            $prize = $this->determinePrize((int) $data['taps']);
            $won = $prize !== null;

            try {
                $participation = KOGagnantParticipation::create([
                    'game' => 'ko-gagnant',
                    'first_name' => $data['firstName'],
                    'last_name' => $data['lastName'],
                    'commune' => $data['commune'],
                    'phone_number' => $data['phoneNumber'],
                    'reseau' => $data['reseau'],
                    'taps' => (int) $data['taps'],
                    'duration_ms' => $data['durationMs'] ?? null,
                    'won' => $won,
                    'prize_label' => $prize['label'] ?? null,
                    'prize_type' => $prize['type'] ?? null,
                    'prize_icon' => $prize['icon'] ?? null,
                    'accepted_terms' => (bool) $data['acceptedTerms'],
                ]);
            } catch (QueryException $e) {
                if (! $this->isUniquePhoneConstraintViolation($e)) {
                    throw $e;
                }

                $existing = KOGagnantParticipation::query()
                    ->where('phone_number', $data['phoneNumber'])
                    ->first();

                return [
                    'ok' => true,
                    'alreadyPlayed' => true,
                    'participationId' => $existing?->id,
                    'won' => (bool) ($existing?->won ?? false),
                    'prize' => $this->formatPrize($existing),
                    'message' => 'Ce numero a deja participe.',
                ];
            }

            return [
                'ok' => true,
                'alreadyPlayed' => false,
                'participationId' => $participation->id,
                'won' => $won,
                'prize' => $prize,
                'message' => $won ? 'Participation gagnante.' : 'Participation enregistree.',
            ];
        });
    }

    public function claimPrize(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $participation = KOGagnantParticipation::query()
                ->whereKey($data['participationId'])
                ->lockForUpdate()
                ->first();

            if (! $participation) {
                throw (new ModelNotFoundException())->setModel(KOGagnantParticipation::class, [$data['participationId']]);
            }

            if (! $participation->won) {
                throw ValidationException::withMessages([
                    'participationId' => 'Cette participation n\'est pas gagnante.',
                ]);
            }

            if ($participation->prize_type !== 'wave') {
                throw ValidationException::withMessages([
                    'participationId' => 'Ce lot ne peut pas etre reclame avec un numero Wave.',
                ]);
            }

            if ($participation->prize_claimed) {
                throw ValidationException::withMessages([
                    'participationId' => 'Ce lot a deja ete reclame.',
                ]);
            }

            $participation->forceFill([
                'wave_number' => $data['waveNumber'],
                'prize_claimed' => true,
                'claimed_at' => now(),
            ])->save();
        });
    }

    private function determinePrize(int $taps): ?array
    {
        if ($taps < self::MIN_TAPS_TO_WIN) {
            return null;
        }

        foreach ($this->prizes as $prize) {
            if ($taps >= $prize['min_taps'] && $taps <= $prize['max_taps']) {
                return [
                    'label' => $prize['label'],
                    'type' => $prize['type'],
                    'icon' => $prize['icon'],
                ];
            }
        }

        return [
            'label' => '500 Mo de Data',
            'type' => 'data',
            'icon' => 'bi-wifi',
        ];
    }

    private function formatPrize(?KOGagnantParticipation $participation): ?array
    {
        if (! $participation || ! $participation->prize_label) {
            return null;
        }

        return [
            'label' => $participation->prize_label,
            'type' => $participation->prize_type,
            'icon' => $participation->prize_icon,
        ];
    }

    private function isUniquePhoneConstraintViolation(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return $exception->getCode() === '23000'
            || str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'Duplicate entry');
    }
}
