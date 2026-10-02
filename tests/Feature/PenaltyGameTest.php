<?php

namespace Tests\Feature;

use App\Models\PenaltyParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenaltyGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_complete_five_shots_and_win_with_two_goals(): void
    {
        $start = $this->postJson('/api/penalty/start', [
            'deviceId' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'firstName' => 'Awa',
            'lastName' => 'Kone',
            'phoneNumber' => '0701020304',
            'team' => 'ivory-coast',
            'acceptedTerms' => true,
        ]);

        $start->assertOk()
            ->assertJsonPath('alreadyPlayed', false)
            ->assertJsonPath('attempts', 0)
            ->assertJsonPath('team', 'ivory-coast');

        $participationId = $start->json('participationId');
        $playToken = $start->json('playToken');
        $roundToken = $start->json('round.token');
        $this->assertDatabaseHas('penalty_participations', [
            'id' => $participationId,
            'phone_number' => '0701020304',
            'attempts' => 0,
            'goals' => 0,
        ]);
        $targets = [50, 84, 50, 84, 50];
        foreach ($targets as $index => $targetX) {
            $attempt = $index + 1;
            $shot = $this->postJson('/api/penalty/shoot', [
                'playToken' => $playToken,
                'roundToken' => $roundToken,
                'patrolElapsedMs' => 0,
                'targetX' => $targetX,
                'targetY' => 20,
            ]);

            $shot->assertOk()
                ->assertJsonPath('attempts', $attempt)
                ->assertJsonPath('completed', $attempt === 5);
            $this->assertIsNumeric($shot->json('keeperY'));
            if ($attempt < 5) $roundToken = $shot->json('round.token');
        }

        $participation = PenaltyParticipation::findOrFail($participationId);
        $this->assertSame(5, $participation->attempts);
        $this->assertGreaterThanOrEqual(2, $participation->goals);
        $this->assertNotNull($participation->prize_label);

        $this->postJson('/api/penalty/shoot', [
            'playToken' => $playToken,
            'roundToken' => $roundToken,
            'patrolElapsedMs' => 0,
            'targetX' => 50,
            'targetY' => 20,
        ])->assertUnprocessable();
    }

    public function test_phone_number_can_only_start_one_game(): void
    {
        $payload = [
            'deviceId' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'firstName' => 'Ama',
            'lastName' => 'Mensah',
            'phoneNumber' => '0501020304',
            'team' => 'cameroon',
            'acceptedTerms' => true,
        ];

        $this->postJson('/api/penalty/start', $payload)
            ->assertOk()
            ->assertJsonPath('alreadyPlayed', false);

        $payload['deviceId'] = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbc';
        $this->postJson('/api/penalty/start', $payload)
            ->assertOk()
            ->assertJsonPath('alreadyPlayed', true)
            ->assertJsonPath('playToken', null);

        $this->assertDatabaseCount('penalty_participations', 1);
    }

    public function test_player_can_only_start_once_per_device_even_with_another_phone(): void
    {
        $deviceId = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
        $base = [
            'deviceId' => $deviceId,
            'firstName' => 'Awa',
            'lastName' => 'Kone',
            'team' => 'ivory-coast',
            'acceptedTerms' => true,
        ];

        $this->postJson('/api/penalty/start', $base + ['phoneNumber' => '0701020311'])
            ->assertOk()
            ->assertJsonPath('alreadyPlayed', false);

        $this->postJson('/api/penalty/start', $base + ['phoneNumber' => '0701020312'])
            ->assertOk()
            ->assertJsonPath('alreadyPlayed', true)
            ->assertJsonPath('playToken', null);

        $this->assertDatabaseCount('penalty_participations', 1);
    }

    public function test_shot_outside_the_goal_is_always_missed(): void
    {
        $start = $this->postJson('/api/penalty/start', [
            'deviceId' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
            'firstName' => 'Kofi',
            'lastName' => 'Asante',
            'phoneNumber' => '0101020304',
            'team' => 'cameroon',
            'acceptedTerms' => true,
        ]);

        $this->postJson('/api/penalty/shoot', [
            'playToken' => $start->json('playToken'),
            'roundToken' => $start->json('round.token'),
            'patrolElapsedMs' => 0,
            'targetX' => 50,
            'targetY' => 80,
        ])->assertOk()
            ->assertJsonPath('goal', false)
            ->assertJsonPath('outcome', 'missed')
            ->assertJsonPath('message', 'Tir hors cadre.');
    }

    public function test_high_central_shot_is_saved_by_a_high_keeper_dive(): void
    {
        $start = $this->postJson('/api/penalty/start', [
            'deviceId' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
            'firstName' => 'Mireille',
            'lastName' => 'Fomo',
            'phoneNumber' => '0702030405',
            'team' => 'ivory-coast',
            'acceptedTerms' => true,
        ]);

        $this->postJson('/api/penalty/shoot', [
            'playToken' => $start->json('playToken'),
            'roundToken' => $start->json('round.token'),
            'patrolElapsedMs' => 0,
            'targetX' => 50,
            'targetY' => 20,
        ])->assertOk()
            ->assertJsonPath('goal', false)
            ->assertJsonPath('outcome', 'saved')
            ->assertJsonPath('keeperMotion', 1)
            ->assertJsonPath('keeperAction', 'save');
    }

    public function test_well_aimed_corner_shot_can_score(): void
    {
        $start = $this->postJson('/api/penalty/start', [
            'deviceId' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
            'firstName' => 'Nadia',
            'lastName' => 'Kouame',
            'phoneNumber' => '0503040506',
            'team' => 'ivory-coast',
            'acceptedTerms' => true,
        ]);

        $this->postJson('/api/penalty/shoot', [
            'playToken' => $start->json('playToken'),
            'roundToken' => $start->json('round.token'),
            'patrolElapsedMs' => 0,
            'targetX' => 15,
            'targetY' => 15,
        ])->assertOk()
            ->assertJsonPath('goal', true)
            ->assertJsonPath('outcome', 'goal');
    }

    public function test_shot_that_crosses_the_goalkeeper_is_a_save(): void
    {
        $start = $this->postJson('/api/penalty/start', [
            'deviceId' => '11111111-1111-4111-8111-111111111112',
            'firstName' => 'Mariam',
            'lastName' => 'Traore',
            'phoneNumber' => '0503040507',
            'team' => 'ivory-coast',
            'acceptedTerms' => true,
        ]);

        $this->postJson('/api/penalty/shoot', [
            'playToken' => $start->json('playToken'),
            'roundToken' => $start->json('round.token'),
            'patrolElapsedMs' => 0,
            'targetX' => 35,
            'targetY' => 20,
            'pitchWidth' => 700,
            'pitchHeight' => 500,
        ])->assertOk()
            ->assertJsonPath('goal', false)
            ->assertJsonPath('outcome', 'saved');
    }

    public function test_round_cannot_be_forged_replayed_or_used_with_a_future_patrol_time(): void
    {
        $start = $this->postJson('/api/penalty/start', [
            'deviceId' => '22222222-2222-4222-8222-222222222222',
            'firstName' => 'Awa', 'lastName' => 'Kone', 'phoneNumber' => '0701020313',
            'team' => 'cameroon', 'acceptedTerms' => true,
        ])->assertOk();
        $shot = [
            'playToken' => $start->json('playToken'),
            'roundToken' => $start->json('round.token'),
            'patrolElapsedMs' => 0, 'targetX' => 50, 'targetY' => 25,
        ];
        $this->postJson('/api/penalty/shoot', array_replace($shot, ['roundToken' => 'forged']))
            ->assertUnprocessable();
        $this->postJson('/api/penalty/shoot', array_replace($shot, ['patrolElapsedMs' => 60000]))
            ->assertUnprocessable();
        $this->postJson('/api/penalty/shoot', $shot)->assertOk()->assertJsonPath('attempts', 1);
        $this->postJson('/api/penalty/shoot', $shot)->assertUnprocessable();
        $this->assertDatabaseHas('penalty_participations', ['id' => $start->json('participationId'), 'attempts' => 1]);
    }

    public function test_patrol_phase_is_used_for_the_authoritative_shot_trajectory(): void
    {
        $this->freezeTime();
        $start = $this->postJson('/api/penalty/start', [
            'deviceId' => '33333333-3333-4333-8333-333333333333',
            'firstName' => 'Awa', 'lastName' => 'Kone', 'phoneNumber' => '0701020314',
            'team' => 'ivory-coast', 'acceptedTerms' => true,
        ])->assertOk();
        $this->travel(2)->seconds();
        $result = $this->postJson('/api/penalty/shoot', [
            'playToken' => $start->json('playToken'), 'roundToken' => $start->json('round.token'),
            'patrolElapsedMs' => 1050, 'targetX' => 76, 'targetY' => 25,
            'pitchWidth' => 700, 'pitchHeight' => 500,
        ])->assertOk()->assertJsonPath('outcome', 'saved');
        $pose = (new \App\Services\PenaltyMotion)->patrol(1050, 0, 0, 700, 500);
        $this->assertEqualsWithDelta($pose['keeperX'], $result->json('trajectory.0.keeperX'), .0001);
        $this->assertSame(50, $result->json('trajectory.0.ballX'));
        $this->assertNotSame($start->json('round.token'), $result->json('round.token'));
    }

    public function test_admin_can_list_paginated_penalty_players(): void
    {
        PenaltyParticipation::create([
            'play_token' => '11111111-1111-4111-8111-111111111111',
            'first_name' => 'Awa',
            'last_name' => 'Kone',
            'phone_number' => '0701020304',
            'team' => 'ivory-coast',
            'attempts' => 5,
            'goals' => 3,
            'completed' => true,
            'prize_label' => 'Casquette',
            'accepted_terms' => true,
        ]);

        $response = $this->getJson('/api/penalty/participations');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Awa')
            ->assertJsonPath('data.0.team', 'ivory-coast')
            ->assertJsonPath('data.0.goals', 3)
            ->assertJsonPath('data.0.won', true)
            ->assertJsonPath('data.0.prize_label', 'Casquette')
            ->assertJsonPath('total', 1);
    }
}
