<?php

namespace App\Services;

use App\Models\PenaltyParticipation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PenaltyGameService
{
    private const MAX_ATTEMPTS = 3;

    public function start(array $data): array
    {
        try {
            $participation = PenaltyParticipation::create([
                'play_token' => (string) Str::uuid(),
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'phone_number' => $data['phoneNumber'],
                'team' => $data['team'],
                'accepted_terms' => (bool) $data['acceptedTerms'],
            ]);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            $existing = PenaltyParticipation::query()
                ->where('phone_number', $data['phoneNumber'])
                ->firstOrFail();

            return $this->format($existing, true);
        }

        return $this->format($participation, false);
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

            $targetX = (float) $data['targetX'];
            $targetY = (float) $data['targetY'];
            $keeperX = random_int(18, 82);
            $insideGoal = $targetX >= 4 && $targetX <= 96
                && $targetY >= 10 && $targetY <= 54;
            $saved = $insideGoal
                && abs($targetX - $keeperX) <= 18
                && $targetY >= 18
                && $targetY <= 49;
            $goal = $insideGoal && ! $saved;
            $outcome = $goal ? 'goal' : ($saved ? 'saved' : 'missed');
            $attempts = $participation->attempts + 1;
            $goals = $participation->goals + ($goal ? 1 : 0);
            $completed = $attempts === self::MAX_ATTEMPTS;
            $prize = $completed ? $this->determinePrize($goals) : null;

            $participation->forceFill([
                'attempts' => $attempts,
                'goals' => $goals,
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
                'keeperX' => $keeperX,
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
        if ($goals === 3) {
            return collect(['Gourde', 'T-shirt'])->random();
        }

        if ($goals === 2) {
            return collect(['Stylo', 'Casquette'])->random();
        }

        return null;
    }

    private function format(PenaltyParticipation $participation, bool $alreadyPlayed): array
    {
        return [
            'ok' => true,
            'alreadyPlayed' => $alreadyPlayed,
            'participationId' => $participation->id,
            'playToken' => $participation->play_token,
            'team' => $participation->team,
            'attempts' => $participation->attempts,
            'goals' => $participation->goals,
            'completed' => $participation->completed,
            'won' => $participation->prize_label !== null,
            'prize' => $participation->prize_label,
            'message' => $alreadyPlayed ? 'Ce numero a deja participe.' : 'La seance peut commencer.',
        ];
    }
}
