<?php

namespace App\Services;

class PenaltyMotion
{
    public function patrol(float $elapsedMs, int $goals, int $attempt, float $width, float $height): array
    {
        $size = min(144, $width * .26, $height * .26);
        $amplitude = 39 - ($size / 2 + 7) / $width * 100;
        $period = $this->period($goals);
        $direction = $attempt % 2 === 0 ? 1 : -1;

        return [
            'keeperX' => 50 + $direction * $amplitude * sin($elapsedMs / $period * 2 * M_PI),
            'keeperY' => 49 - ($size / 2 + 10) / $height * 100,
        ];
    }

    public function period(int $goals): int
    {
        return match (min(3, max(0, $goals))) {
            0 => 4200,
            1 => 3100,
            2 => 2600,
            default => 2300,
        };
    }

    public function simulate(array $data, int $goals, int $attempt): array
    {
        $width = (float) ($data['pitchWidth'] ?? 700);
        $height = (float) ($data['pitchHeight'] ?? 500);
        $elapsed = (float) $data['patrolElapsedMs'];
        $targetX = (float) $data['targetX'];
        $targetY = (float) $data['targetY'];
        $size = min(144, $width * .26, $height * .26);
        $ballSize = min(44, $width * .07);
        $start = $this->patrol($elapsed, $goals, $attempt, $width, $height);
        $reactionMs = 140;
        $durationMs = 680;
        $reaction = $this->patrol($elapsed + $reactionMs, $goals, $attempt, $width, $height);
        $reach = 16 + min(3, $goals) * 1.5;
        $left = 11 + ($size / 2 + 7) / $width * 100;
        $top = 11 + ($size / 2 + 7) / $height * 100;
        $endX = max($left, min(100 - $left, $reaction['keeperX'] + max(-$reach, min($reach, $targetX - $reaction['keeperX']))));
        $endY = max($top, min($start['keeperY'], $targetY));
        $frames = [];
        $saved = false;

        // These same frames drive the browser: no independent CSS dive or ball path.
        for ($step = 0; $step <= 120; $step++) {
            $progress = $step / 120;
            $ms = $progress * $durationMs;
            if ($ms <= $reactionMs) {
                $keeper = $this->patrol($elapsed + $ms, $goals, $attempt, $width, $height);
            } else {
                $dive = ($ms - $reactionMs) / ($durationMs - $reactionMs);
                $dive = $dive * $dive * (3 - 2 * $dive);
                $keeper = [
                    'keeperX' => $reaction['keeperX'] + ($endX - $reaction['keeperX']) * $dive,
                    'keeperY' => $reaction['keeperY'] + ($endY - $reaction['keeperY']) * $dive,
                ];
            }
            $frame = $keeper + [
                'ms' => $ms,
                'ballX' => 50 + ($targetX - 50) * $progress,
                'ballY' => 82 + ($targetY - 82) * $progress,
                'ballScale' => 1 - .52 * $progress,
            ];
            if ($step > 0) {
                $previous = $frames[array_key_last($frames)];
                $contact = $this->contact($previous, $frame, $width, $height, $size, $ballSize);
                if ($contact !== null) {
                    foreach ($frame as $key => $value) {
                        $frame[$key] = $previous[$key] + ($value - $previous[$key]) * $contact;
                    }
                    $frames[] = $frame;
                    $saved = true;
                    break;
                }
            }
            $frames[] = $frame;
        }

        $radius = $ballSize * .48 / 2;
        $inside = $targetX >= 11 + (7 + $radius) / $width * 100
            && $targetX <= 89 - (7 + $radius) / $width * 100
            && $targetY >= 11 + (7 + $radius) / $height * 100
            && $targetY <= 49 - (10 + $radius) / $height * 100;

        return [
            'outcome' => $saved ? 'saved' : ($inside ? 'goal' : 'missed'),
            'trajectory' => $frames,
        ];
    }

    private function contact(array $from, array $to, float $width, float $height, float $size, float $ballSize): ?float
    {
        $ballRadius = $ballSize * $from['ballScale'] / 2;
        $first = null;
        // Body/head, outstretched gloves and legs, in sprite-local coordinates.
        foreach ([[0, .36, .5], [-.12, .5, .22], [.25, .44, .25]] as [$offsetY, $halfWidth, $halfHeight]) {
            $rx = $size * $halfWidth + $ballRadius;
            $ry = $size * $halfHeight + $ballRadius;
            $x = ($from['ballX'] - $from['keeperX']) * $width / 100 / $rx;
            $y = (($from['ballY'] - $from['keeperY']) * $height / 100 - $offsetY * $size) / $ry;
            $dx = (($to['ballX'] - $to['keeperX']) - ($from['ballX'] - $from['keeperX'])) * $width / 100 / $rx;
            $dy = (($to['ballY'] - $to['keeperY']) - ($from['ballY'] - $from['keeperY'])) * $height / 100 / $ry;
            $a = $dx * $dx + $dy * $dy;
            $b = 2 * ($x * $dx + $y * $dy);
            $c = $x * $x + $y * $y - 1;
            if ($c <= 0) {
                return 0;
            }
            $discriminant = $b * $b - 4 * $a * $c;
            if ($a > 0 && $discriminant >= 0) {
                $t = (-$b - sqrt($discriminant)) / (2 * $a);
                if ($t >= 0 && $t <= 1) {
                    $first = $first === null ? $t : min($first, $t);
                }
            }
        }

        return $first;
    }
}
