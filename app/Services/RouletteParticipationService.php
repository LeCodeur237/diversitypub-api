<?php

namespace App\Services;

use App\Models\RouletteParticipation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RouletteParticipationService
{
    private const PRIZE_LIMIT = 50;

    private const PRIZES = ['Gourde', 't-shirt', 'Casquette', 'Stylo'];

    public function status(): array
    {
        return $this->withGameState($this->inventory());
    }

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

            $inventory = $this->withGameState($this->inventory(true));
            if ($inventory['closed']) {
                return [
                    'ok' => true,
                    'alreadyPlayed' => false,
                    'closed' => true,
                    'participationId' => null,
                    'won' => false,
                    'prize' => null,
                    'inventory' => $inventory,
                    'message' => 'La roulette est fermee : tous les lots ont ete remis.',
                ];
            }

            $prize = $inventory['available'][array_rand($inventory['available'])];

            try {
                $participation = RouletteParticipation::create([
                    'game' => 'roulette',
                    'device_id' => $data['deviceId'],
                    'first_name' => $data['firstName'],
                    'last_name' => $data['lastName'],
                    'age' => $data['age'],
                    'phone_number' => $data['phone'],
                    'won' => true,
                    'prize_label' => $prize,
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

            return $this->formatParticipation($participation, false, $this->withGameState($this->inventory(true)));
        });
    }

    private function formatExisting(RouletteParticipation $participation): array
    {
        return $this->formatParticipation($participation, true, $this->withGameState($this->inventory()));
    }

    private function formatParticipation(RouletteParticipation $participation, bool $alreadyPlayed, array $inventory): array
    {
        return [
            'ok' => true,
            'alreadyPlayed' => $alreadyPlayed,
            'closed' => $inventory['closed'],
            'participationId' => $participation->id,
            'won' => $participation->won,
            'prize' => $participation->prize_label,
            'inventory' => $inventory,
            'message' => $alreadyPlayed ? 'Cet appareil a deja participe.' : 'Participation enregistree.',
        ];
    }

    private function inventory(bool $lock = false): array
    {
        $query = RouletteParticipation::query()
            ->where('won', true)
            ->whereIn('prize_label', self::PRIZES);

        if ($lock) {
            $query->lockForUpdate();
        }

        $counts = $query->get(['prize_label'])->countBy('prize_label');
        $remaining = [];
        foreach (self::PRIZES as $prize) {
            $remaining[$prize] = max(0, self::PRIZE_LIMIT - $counts->get($prize, 0));
        }

        return [
            'limitPerPrize' => self::PRIZE_LIMIT,
            'remaining' => $remaining,
            'available' => array_keys(array_filter($remaining, fn (int $stock) => $stock > 0)),
            'closed' => ! array_filter($remaining, fn (int $stock) => $stock > 0),
        ];
    }

    private function withGameState(array $inventory): array
    {
        $inventory['closed'] = ! config('roulette.enabled') || $inventory['closed'];

        return $inventory;
    }

    private function isUniqueDeviceViolation(QueryException $exception): bool
    {
        return str_contains(strtolower($exception->getMessage()), 'roulette_participations_device_id_unique');
    }
}
