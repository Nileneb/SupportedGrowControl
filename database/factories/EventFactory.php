<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Device;
use App\Models\Calendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');
        $end = (clone $start)->modify('+' . fake()->numberBetween(1, 4) . ' hours');

        return [
            'user_id' => User::factory(),
            'calendar_id' => Calendar::factory(),
            'device_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(10),
            'start_at' => $start,
            'end_at' => $end,
            'all_day' => false,
            'status' => 'scheduled',
            'color' => fake()->optional()->hexColor(),
            'meta' => null,
            'rrule' => null,
            'last_executed_at' => null,
        ];
    }

    /**
     * Indicate that the event is all day.
     */
    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'all_day' => true,
            'start_at' => now()->startOfDay(),
            'end_at' => now()->endOfDay(),
        ]);
    }

    /**
     * Indicate that the event is recurring.
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'rrule' => 'FREQ=DAILY;BYHOUR=8',
        ]);
    }

    /**
     * Indicate that the event is linked to a device.
     */
    public function withDevice(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_id' => Device::factory(),
        ]);
    }

    /**
     * Indicate that the event has Shelly action metadata.
     */
    public function withShellyAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta' => [
                'shelly_device_id' => 1,
                'action' => fake()->randomElement(['on', 'off', 'toggle']),
                'duration' => fake()->optional()->numberBetween(60, 3600),
            ],
        ]);
    }

    /**
     * Indicate that the event has been executed.
     */
    public function executed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_executed_at' => now(),
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the event is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
