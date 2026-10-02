<?php

namespace Tests\Unit;

use App\Services\PenaltyMotion;
use PHPUnit\Framework\TestCase;

class PenaltyMotionTest extends TestCase
{
    public function test_patrol_reaches_both_posts_without_leaving_the_goal(): void
    {
        $motion = new PenaltyMotion;
        foreach ([[700, 500], [358, 515], [288, 390]] as [$width, $height]) {
            $size = min(144, $width * .26, $height * .26);
            $half = $size / 2 / $width * 100;
            $right = $motion->patrol(1050, 0, 0, $width, $height);
            $left = $motion->patrol(3150, 0, 0, $width, $height);
            $this->assertEqualsWithDelta(89 - 700 / $width, $right['keeperX'] + $half, .001);
            $this->assertEqualsWithDelta(11 + 700 / $width, $left['keeperX'] - $half, .001);
            $this->assertGreaterThan(45, $right['keeperX'] - $left['keeperX']);
        }
        $this->assertSame(4200, $motion->period(0));
        $this->assertSame(2500, $motion->period(1));
        $this->assertSame(1800, $motion->period(2));
        $this->assertSame(1500, $motion->period(3));
    }

    public function test_timing_changes_the_result_of_the_same_shot_on_desktop_and_mobile(): void
    {
        $motion = new PenaltyMotion;
        foreach ([[700, 500], [358, 515], [288, 390]] as [$width, $height]) {
            $shot = ['targetX' => 76, 'targetY' => 25, 'pitchWidth' => $width, 'pitchHeight' => $height];
            $covered = $motion->simulate($shot + ['patrolElapsedMs' => 1050], 0, 0);
            $open = $motion->simulate($shot + ['patrolElapsedMs' => 3150], 0, 0);
            $this->assertSame('saved', $covered['outcome']);
            $this->assertSame('goal', $open['outcome']);
            $this->assertLessThan(680, end($covered['trajectory'])['ms']);
            $this->assertEquals(680, end($open['trajectory'])['ms']);
        }
    }

    public function test_high_shots_are_reachable_and_the_animation_starts_at_the_patrol_position(): void
    {
        $motion = new PenaltyMotion;
        foreach ([0, 1050, 2100, 3150] as $elapsed) {
            $pose = $motion->patrol($elapsed, 0, 0, 700, 500);
            $result = $motion->simulate([
                'targetX' => $pose['keeperX'], 'targetY' => 18, 'patrolElapsedMs' => $elapsed,
                'pitchWidth' => 700, 'pitchHeight' => 500,
            ], 0, 0);
            $this->assertSame('saved', $result['outcome']);
            $this->assertSame($pose['keeperX'], $result['trajectory'][0]['keeperX']);
            $this->assertLessThan($pose['keeperY'], end($result['trajectory'])['keeperY']);
        }
    }
}
