<?php

namespace Tests\Feature;

use App\Models\RouletteParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouletteParticipationAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_roulette_participations(): void
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
        $response->assertJsonCount(2);
        $response->assertJsonPath('0.first_name', 'Bob');
        $response->assertJsonPath('1.last_name', 'Doe');
    }
}
