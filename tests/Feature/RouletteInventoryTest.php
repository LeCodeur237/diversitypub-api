<?php

namespace Tests\Feature;

use App\Models\RouletteParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouletteInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['roulette.enabled' => true]);
    }

    public function test_roulette_awards_only_prizes_with_remaining_stock(): void
    {
        $this->seedPrize('Gourde', 50);

        $response = $this->submit('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '0701020304');

        $response->assertOk()
            ->assertJsonPath('won', true)
            ->assertJsonPath('inventory.remaining.Gourde', 0);
        $this->assertNotSame('Gourde', $response->json('prize'));
        $this->assertContains($response->json('prize'), ['t-shirt', 'Casquette', 'Stylo']);
    }

    public function test_roulette_closes_only_after_all_two_hundred_prizes_are_awarded(): void
    {
        foreach (['Gourde', 't-shirt', 'Casquette', 'Stylo'] as $prize) {
            $this->seedPrize($prize, 50);
        }

        $this->getJson('/api/roulette/status')
            ->assertOk()
            ->assertJsonPath('closed', true)
            ->assertJsonPath('available', []);

        $this->submit('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '0701020305')
            ->assertOk()
            ->assertJsonPath('closed', true)
            ->assertJsonPath('won', false)
            ->assertJsonPath('prize', null);

        $this->assertDatabaseCount('roulette_participations', 200);
    }

    public function test_roulette_can_be_closed_even_when_prizes_remain(): void
    {
        config(['roulette.enabled' => false]);

        $this->getJson('/api/roulette/status')
            ->assertOk()
            ->assertJsonPath('closed', true);

        $this->submit('dddddddd-dddd-4ddd-8ddd-dddddddddddd', '0701020307')
            ->assertOk()
            ->assertJsonPath('closed', true)
            ->assertJsonPath('prize', null);

        $this->assertDatabaseCount('roulette_participations', 0);
    }

    public function test_client_cannot_choose_its_own_roulette_prize(): void
    {
        $response = $this->postJson('/api/roulette/participate', [
            'deviceId' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            'firstName' => 'Awa',
            'lastName' => 'Kone',
            'age' => 28,
            'phone' => '0701020306',
            'won' => false,
            'prize' => 'Lot invente',
        ])->assertOk();

        $this->assertTrue($response->json('won'));
        $this->assertContains($response->json('prize'), ['Gourde', 't-shirt', 'Casquette', 'Stylo']);
    }

    private function submit(string $deviceId, string $phone)
    {
        return $this->postJson('/api/roulette/participate', [
            'deviceId' => $deviceId,
            'firstName' => 'Awa',
            'lastName' => 'Kone',
            'age' => 28,
            'phone' => $phone,
        ]);
    }

    private function seedPrize(string $prize, int $quantity): void
    {
        foreach (range(1, $quantity) as $index) {
            RouletteParticipation::create([
                'game' => 'roulette',
                'device_id' => sprintf('%08d-0000-4000-8000-%012d', crc32($prize), $index),
                'first_name' => 'Test',
                'last_name' => $prize,
                'age' => 28,
                'phone_number' => sprintf('070%07d', $index + crc32($prize) % 1000000),
                'won' => true,
                'prize_label' => $prize,
            ]);
        }
    }
}
