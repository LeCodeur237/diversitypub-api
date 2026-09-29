<?php

namespace Tests\Feature;

use App\Models\PenaltyParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenaltyGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_start_and_complete_a_three_shot_game(): void
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
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $shot = $this->postJson('/api/penalty/shoot', [
                'playToken' => $playToken,
                'targetX' => 25 * $attempt,
                'targetY' => 20,
            ]);

            $shot->assertOk()
                ->assertJsonPath('attempts', $attempt)
                ->assertJsonPath('completed', $attempt === 3);
        }

        $participation = PenaltyParticipation::findOrFail($participationId);
        $this->assertSame(3, $participation->attempts);
        $this->assertSame($participation->goals >= 2, $participation->prize_label !== null);

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
            'team' => 'ghana',
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
            'team' => 'ghana',
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

    public function test_admin_can_list_paginated_penalty_players(): void
    {
        PenaltyParticipation::create([
            'play_token' => '11111111-1111-4111-8111-111111111111',
            'first_name' => 'Awa',
            'last_name' => 'Kone',
            'phone_number' => '0701020304',
            'team' => 'ivory-coast',
            'attempts' => 3,
            'goals' => 2,
            'completed' => true,
            'prize_label' => 'Casquette',
            'accepted_terms' => true,
        ]);

        $response = $this->getJson('/api/penalty/participations');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Awa')
            ->assertJsonPath('data.0.team', 'ivory-coast')
            ->assertJsonPath('data.0.goals', 2)
            ->assertJsonPath('data.0.won', true)
            ->assertJsonPath('data.0.prize_label', 'Casquette')
            ->assertJsonPath('total', 1);
    }
}
