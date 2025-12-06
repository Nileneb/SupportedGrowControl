<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShellyDevice>
 */
class ShellyDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isGen2 = fake()->boolean();
        $deviceType = $isGen2 ? 'shellyplus1pm' : 'shelly1pm';

        return [
            'user_id' => User::factory(),
            'device_id' => null, // Optional link to GrowControl device
            'name' => fake()->words(2, true) . ' Switch',
            'shelly_device_id' => $deviceType . '-' . fake()->regexify('[a-f0-9]{12}'),
            'ip_address' => fake()->localIpv4(),
            'auth_token' => Str::random(32),
            'model' => $isGen2 ? 'Shelly Plus 1PM' : 'Shelly 1PM',
            'config' => null,
            'last_webhook_at' => null,
            'last_seen_at' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the device is Gen2.
     */
    public function gen2(): static
    {
        return $this->state(fn (array $attributes) => [
            'shelly_device_id' => 'shellyplus1pm-' . fake()->regexify('[a-f0-9]{12}'),
            'model' => 'Shelly Plus 1PM',
        ]);
    }

    /**
     * Indicate that the device is Gen1.
     */
    public function gen1(): static
    {
        return $this->state(fn (array $attributes) => [
            'shelly_device_id' => 'shelly1pm-' . fake()->regexify('[a-f0-9]{12}'),
            'model' => 'Shelly 1PM',
        ]);
    }

    /**
     * Indicate that the device is linked to a GrowControl device.
     */
    public function linked(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_id' => Device::factory(),
        ]);
    }

    /**
     * Indicate that the device has config.
     */
    public function withConfig(): static
    {
        return $this->state(fn (array $attributes) => [
            'config' => [
                'switch:0' => ['name' => 'Main Switch', 'initial_state' => 'off'],
                'wifi' => ['ssid' => 'TestNetwork'],
            ],
        ]);
    }

    /**
     * Indicate that the device is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
