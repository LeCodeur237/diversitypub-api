<?php

namespace App\Services;

use App\Models\PenaltyParticipation;
use Illuminate\Database\QueryException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PenaltyGameService
{
    private const MAX_ATTEMPTS = 5;

    public function start(array $data): array
    {
        try {
            return DB::transaction(function () use ($data): array {
                $existing = PenaltyParticipation::query()
                    ->where('phone_number', $data['phoneNumber'])
                    ->orWhere('device_id', $data['deviceId'])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $this->format($existing, true);
                }

                $participation = PenaltyParticipation::create([
                    'play_token' => (string) Str::uuid(),
                    'device_id' => $data['deviceId'],
                    'first_name' => $data['firstName'],
                    'last_name' => $data['lastName'],
                    'phone_number' => $data['phoneNumber'],
                    'team' => $data['team'],
                    'accepted_terms' => (bool) $data['acceptedTerms'],
                ]);

                return $this->format($participation, false);
            });
        } catch (QueryException $exception) {
            if (! in_array($exception->getCode(), ['23000', '23505', '19'], true)) {
                throw $exception;
            }

            $existing = PenaltyParticipation::query()
                ->where('phone_number', $data['phoneNumber'])
                ->orWhere('device_id', $data['deviceId'])
                ->first();

            if (! $existing) {
                throw $exception;
            }

            return $this->format($existing, true);
        }
    }

    public function shoot(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $participation = PenaltyParticipation::query()
                ->where('play_token', $data['playToken'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($participation->completed || $participation->attempts >= self::MAX_ATTEMPTS) {
                throw ValidationException::withMessages([
                    'playToken' => 'Cette seance de tirs au but est deja terminee.',
                ]);
            }

            $this->validateRound($participation, $data);
            $motion = (new PenaltyMotion)->simulate($data, $participation->goals, $participation->attempts);
            $outcome = $motion['outcome'];
            $goal = $outcome === 'goal';
            $lastFrame = $motion['trajectory'][array_key_last($motion['trajectory'])];
            $attempts = $participation->attempts + 1;
            $goals = $participation->goals + ($goal ? 1 : 0);
            $completed = $attempts === self::MAX_ATTEMPTS;
            $prize = $completed ? $this->determinePrize($goals) : null;

            $participation->forceFill([
                'attempts' => $attempts,
                'goals' => $goals,
                'keeper_x' => 50,
                'keeper_y' => 40,
                'completed' => $completed,
                'prize_label' => $prize,
            ])->save();

            return [
                'ok' => true,
                'goal' => $goal,
                'outcome' => $outcome,
                'attempts' => $attempts,
                'goals' => $goals,
                'completed' => $completed,
                'won' => $prize !== null,
                'prize' => $prize,
                'keeperX' => $lastFrame['keeperX'],
                'keeperY' => $lastFrame['keeperY'],
                'ballX' => $lastFrame['ballX'],
                'ballY' => $lastFrame['ballY'],
                'trajectory' => $motion['trajectory'],
                'round' => $completed ? null : $this->round($participation),
                'keeperMotion' => $goal ? round(1 + min(0.35, $goals * 0.08), 2) : 1,
                'keeperAction' => $outcome === 'saved' ? 'save' : 'dive',
                'message' => match ($outcome) {
                    'goal' => 'But !',
                    'saved' => 'Arret du gardien.',
                    default => 'Tir hors cadre.',
                },
            ];
        });
    }

    private function determinePrize(int $goals): ?string
    {
        if ($goals >= 2) {
            return collect(['Gourde', 'T-shirt'])->random();
        }

        return null;
    }

    private function round(PenaltyParticipation $participation): array
    {
        return [
            'token' => Crypt::encryptString(json_encode([
                'id' => $participation->id,
                'attempt' => $participation->attempts,
                'issuedAt' => now()->getTimestampMs(),
            ], JSON_THROW_ON_ERROR)),
            'periodMs' => (new PenaltyMotion)->period($participation->goals),
            'direction' => $participation->attempts % 2 === 0 ? 1 : -1,
        ];
    }

    private function validateRound(PenaltyParticipation $participation, array $data): void
    {
        try {
            $round = json_decode(Crypt::decryptString($data['roundToken']), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $exception) {
            throw ValidationException::withMessages(['roundToken' => 'Ce tir est invalide.']);
        }

        // Bind a shot to one participant and attempt; never accept an arbitrary keeper position.
        if ($round['id'] !== $participation->id || $round['attempt'] !== $participation->attempts
            || $data['patrolElapsedMs'] > now()->getTimestampMs() - $round['issuedAt'] + 250) {
            throw ValidationException::withMessages(['roundToken' => 'Ce tir a deja ete joue ou son horodatage est invalide.']);
        }
    }

    private function format(PenaltyParticipation $participation, bool $alreadyPlayed): array
    {
        return [
            'ok' => true,
            'alreadyPlayed' => $alreadyPlayed,
            'participationId' => $participation->id,
            'playToken' => $alreadyPlayed ? null : $participation->play_token,
            'round' => $alreadyPlayed ? null : $this->round($participation),
            'team' => $participation->team,
            'attempts' => $participation->attempts,
            'goals' => $participation->goals,
            'keeperX' => (float) $participation->keeper_x,
            'keeperY' => (float) $participation->keeper_y,
            'completed' => $participation->completed,
            'won' => $participation->prize_label !== null,
            'prize' => $participation->prize_label,
            'message' => $alreadyPlayed
                ? 'Ce numéro ou cet appareil a déjà participé.'
                : 'La séance peut commencer.',
        ];
    }
}
