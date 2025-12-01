<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word() . '-' . fake()->randomNumber(4);

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'public_id' => (string) Str::uuid(),
            'bootstrap_id' => (string) Str::uuid(),
            'bootstrap_code' => strtoupper(Str::random(6)),
            'agent_token' => null, // Set via test when needed
            'ip_address' => fake()->ipv4(),
            'serial_port' => null,
            'status' => 'offline',
            'board_type' => 'esp32',
            'capabilities' => [
                'sensors' => ['water_level', 'tds'],
                'actuators' => ['spray_pump'],
            ],
            'last_state' => null,
            'last_seen_at' => now(),
            'paired_at' => null,
        ];
    }

    /**
     * Indicate that the device is paired.
     */
    public function paired(): static
    {
        return $this->state(fn (array $attributes) => [
            'paired_at' => now(),
            'agent_token' => hash('sha256', Str::random(64)),
        ]);
    }

    /**
     * Indicate that the device is unpaired.
     */
    public function unpaired(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'paired_at' => null,
            'agent_token' => null,
        ]);
    }
}
