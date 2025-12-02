<?php

namespace Database\Seeders;

use App\Models\ActuatorType;
use Illuminate\Database\Seeder;

class ActuatorTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'id' => 'spray_pump',
                'display_name' => 'Spray Pump',
                'category' => 'irrigation',
                'command_type' => 'duration',
                'params_schema' => [
                    ['name' => 'seconds', 'type' => 'int', 'min' => 1, 'max' => 120]
                ],
                'min_interval' => 30,
                'critical' => true,
                'meta' => ['icon' => 'spray'],
            ],
            [
                'id' => 'fill_valve',
                'display_name' => 'Fill Valve',
                'category' => 'irrigation',
                'command_type' => 'target',
                'params_schema' => [
                    ['name' => 'target_level', 'type' => 'float', 'min' => 0, 'max' => 100, 'unit' => '%']
                ],
                'min_interval' => 60,
                'critical' => true,
                'meta' => ['icon' => 'valve'],
            ],
            [
                'id' => 'led_grow',
                'display_name' => 'Grow Light',
                'category' => 'lighting',
                'command_type' => 'toggle',
                'params_schema' => [
                    ['name' => 'state', 'type' => 'bool'],
                    ['name' => 'brightness', 'type' => 'int', 'min' => 0, 'max' => 100, 'unit' => '%']
                ],
                'min_interval' => 5,
                'critical' => false,
                'meta' => ['icon' => 'sun'],
            ],
        ];

        foreach ($types as $type) {
            ActuatorType::updateOrCreate(['id' => $type['id']], $type);
        }
    }
}
