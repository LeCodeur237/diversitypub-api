<?php

namespace Tests\Feature;

use App\Models\RouletteParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouletteParticipationAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_paginated_roulette_participations_in_descending_order(): void
    {
        RouletteParticipation::create([
            'game' => 'roulette',
            'device_id' => '11111111-1111-1111-1111-111111111111',
            'first_name' => 'Alice',
            'last_name' => 'Doe',
            'age' => 28,
            'phone_number' => '+225 0101010101',
            'won' => true,
            'prize_label' => 'Bonus 100',
        ]);

        RouletteParticipation::create([
            'game' => 'roulette',
            'device_id' => '22222222-2222-2222-2222-222222222222',
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'age' => 32,
            'phone_number' => '+225 0202020202',
            'won' => false,
            'prize_label' => null,
        ]);

        $response = $this->getJson('/api/roulette/participations');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.first_name', 'Bob');
        $response->assertJsonPath('data.1.last_name', 'Doe');
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('per_page', 20);
        $response->assertJsonPath('total', 2);
    }

    public function test_admin_can_choose_ascending_order_and_page_size(): void
    {
        foreach (range(1, 3) as $index) {
            RouletteParticipation::create([
                'game' => 'roulette',
                'device_id' => sprintf('00000000-0000-0000-0000-%012d', $index),
                'first_name' => 'Joueur',
                'last_name' => (string) $index,
                'age' => 20 + $index,
                'phone_number' => sprintf('+225 01010101%02d', $index),
                'won' => false,
                'prize_label' => null,
            ]);
        }

        $response = $this->getJson('/api/roulette/participations?order=asc&per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('last_page', 2);
        $response->assertJsonPath('per_page', 2);
        $response->assertJsonPath('total', 3);
        $this->assertLessThan(
            $response->json('data.1.id'),
            $response->json('data.0.id'),
        );
    }
}
