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
        $targets = [50, 20, 50, 80, 50];
        foreach ($targets as $index => $targetX) {
            $attempt = $index + 1;
            $shot = $this->postJson('/api/penalty/shoot', [
                'playToken' => $playToken,
                'targetX' => $targetX,
                'targetY' => 20,
            ]);

            $shot->assertOk()
                ->assertJsonPath('attempts', $attempt)
                ->assertJsonPath('completed', $attempt === 5);
            $this->assertIsNumeric($shot->json('keeperY'));
        }

        $participation = PenaltyParticipation::findOrFail($participationId);
        $this->assertSame(5, $participation->attempts);
        $this->assertGreaterThanOrEqual(2, $participation->goals);
        $this->assertNotNull($participation->prize_label);

        $this->postJson('/api/penalty/shoot', [
            'playToken' => $playToken,
            'targetX' => 50,
            'targetY' => 20,
        ])->assertUnprocessable();
    }

    public function test_phone_number_can_only_start_one_game(): void
    {
        $payload = [
            'firstName' => 'Ama',
            'lastName' => 'Mensah',
            'phoneNumber' => '0501020304',
            'team' => 'cameroon',
            'acceptedTerms' => true,
        ];

        $this->postJson('/api/penalty/start', $payload)
            ->assertOk()
            ->assertJsonPath('alreadyPlayed', false);

        $this->postJson('/api/penalty/start', $payload)
            ->assertOk()
            ->assertJsonPath('alreadyPlayed', true);

        $this->assertDatabaseCount('penalty_participations', 1);
    }

    public function test_shot_outside_the_goal_is_always_missed(): void
    {
        $start = $this->postJson('/api/penalty/start', [
            'firstName' => 'Kofi',
            'lastName' => 'Asante',
            'phoneNumber' => '0101020304',
            'team' => 'cameroon',
            'acceptedTerms' => true,
        ]);

        $this->postJson('/api/penalty/shoot', [
            'playToken' => $start->json('playToken'),
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
            'firstName' => 'Mireille',
            'lastName' => 'Fomo',
            'phoneNumber' => '0702030405',
            'team' => 'ivory-coast',
            'acceptedTerms' => true,
        ]);

        $this->postJson('/api/penalty/shoot', [
            'playToken' => $start->json('playToken'),
            'targetX' => 50,
            'targetY' => 20,
        ])->assertOk()
            ->assertJsonPath('goal', false)
            ->assertJsonPath('outcome', 'saved')
            ->assertJsonPath('keeperX', 50)
            ->assertJsonPath('keeperMotion', 1)
            ->assertJsonPath('keeperAction', 'save');
    }

    public function test_well_aimed_corner_shot_can_score(): void
    {
        $start = $this->postJson('/api/penalty/start', [
            'firstName' => 'Nadia',
            'lastName' => 'Kouame',
            'phoneNumber' => '0503040506',
            'team' => 'ivory-coast',
            'acceptedTerms' => true,
        ]);

        $this->postJson('/api/penalty/shoot', [
            'playToken' => $start->json('playToken'),
            'targetX' => 15,
            'targetY' => 15,
        ])->assertOk()
            ->assertJsonPath('goal', true)
            ->assertJsonPath('outcome', 'goal');
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
