<?php

namespace App\Services;

use App\Models\PenaltyParticipation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PenaltyGameService
{
    private const MAX_ATTEMPTS = 5;

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
            $insideGoal = $targetX >= 13 && $targetX <= 88
                && $targetY >= 13 && $targetY <= 49;

            $keeperX = (float) $participation->keeper_x;
            $keeperY = (float) $participation->keeper_y;
            $reachX = 13 + min(6, $participation->goals * 2);
            $reachY = 10 + min(4, $participation->goals);
            $pitchWidth = (float) ($data['pitchWidth'] ?? 700);
            $pitchHeight = (float) ($data['pitchHeight'] ?? 500);
            $keeperSize = min(144, $pitchWidth * 0.26, $pitchHeight * 0.26);
            $ballSize = min(44, $pitchWidth * 0.07);
            $hitRadiusX = (($keeperSize * 1.06 + $ballSize) / 2 + 21) / $pitchWidth * 100;
            $hitRadiusY = (($keeperSize * 1.06 + $ballSize) / 2 + 11) / $pitchHeight * 100;
            $keeperAction = 'reset';
            $ballX = $targetX;
            $ballY = $targetY;

            $pathX = $targetX - 50;
            $pathY = $targetY - 82;
            $keeperEndX = $keeperX;
            $keeperEndY = $keeperY;

            if ($insideGoal) {
                $diveX = $targetX - $keeperX;
                $diveY = $targetY - $keeperY;
                $diveDistance = sqrt(($diveX / $reachX) ** 2 + ($diveY / $reachY) ** 2);
                $keeperEndX += $diveDistance > 1 ? $diveX / $diveDistance : $diveX;
                $keeperEndY += $diveDistance > 1 ? $diveY / $diveDistance : $diveY;
            }

            $relativeStartX = (50 - $keeperX) / $hitRadiusX;
            $relativeStartY = (82 - $keeperY) / $hitRadiusY;
            $relativePathX = ($pathX - ($keeperEndX - $keeperX)) / $hitRadiusX;
            $relativePathY = ($pathY - ($keeperEndY - $keeperY)) / $hitRadiusY;
            $relativeLength = ($relativePathX * $relativePathX) + ($relativePathY * $relativePathY);
            $pathProgress = $relativeLength > 0
                ? max(0, min(1, -(($relativeStartX * $relativePathX) + ($relativeStartY * $relativePathY)) / $relativeLength))
                : 0;
            $pathHitsKeeper = (($relativeStartX + ($pathProgress * $relativePathX)) ** 2)
                + (($relativeStartY + ($pathProgress * $relativePathY)) ** 2) <= 1;
            $saved = $pathHitsKeeper;
            $goal = $insideGoal && ! $saved;

            if ($saved) {
                $keeperX += $pathProgress * ($keeperEndX - $keeperX);
                $keeperY += $pathProgress * ($keeperEndY - $keeperY);
                $ballX = 50 + ($pathProgress * $pathX);
                $ballY = 82 + ($pathProgress * $pathY);
                $keeperAction = 'save';
            } elseif ($goal) {
                $keeperX = $keeperEndX;
                $keeperY = $keeperEndY;
                $keeperAction = 'dive';
            }
            $outcome = $goal ? 'goal' : ($saved ? 'saved' : 'missed');
            $shotKeeperX = $keeperX;
            $shotKeeperY = $keeperY;
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
                'keeperX' => $shotKeeperX,
                'keeperY' => $shotKeeperY,
                'ballX' => $ballX,
                'ballY' => $ballY,
                'keeperMotion' => $goal ? round(1 + min(0.35, $goals * 0.08), 2) : 1,
                'keeperAction' => $keeperAction,
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

    private function format(PenaltyParticipation $participation, bool $alreadyPlayed): array
    {
        $participation->forceFill(['keeper_x' => 50, 'keeper_y' => 40])->save();

        return [
            'ok' => true,
            'alreadyPlayed' => $alreadyPlayed,
            'participationId' => $participation->id,
            'playToken' => $participation->play_token,
            'team' => $participation->team,
            'attempts' => $participation->attempts,
            'goals' => $participation->goals,
            'keeperX' => (float) $participation->keeper_x,
            'keeperY' => (float) $participation->keeper_y,
            'completed' => $participation->completed,
            'won' => $participation->prize_label !== null,
            'prize' => $participation->prize_label,
            'message' => $alreadyPlayed ? 'Ce numero a deja participe.' : 'La seance peut commencer.',
        ];
    }
}
