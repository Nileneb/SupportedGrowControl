<?php

namespace Database\Seeders;

use App\Models\SensorType;
use Illuminate\Database\Seeder;

class SensorTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'id' => 'water_level',
                'display_name' => 'Water Level',
                'category' => 'environment',
                'default_unit' => '%',
                'value_type' => 'float',
                'default_range' => [0, 100],
                'critical' => true,
                'meta' => ['icon' => 'water', 'chart' => 'line'],
            ],
            [
                'id' => 'tds',
                'display_name' => 'TDS',
                'category' => 'nutrients',
                'default_unit' => 'ppm',
                'value_type' => 'int',
                'default_range' => null,
                'critical' => false,
                'meta' => ['icon' => 'beaker', 'chart' => 'line'],
            ],
            [
                'id' => 'temperature',
                'display_name' => 'Temperature',
                'category' => 'environment',
                'default_unit' => '°C',
                'value_type' => 'float',
                'default_range' => [-10, 50],
                'critical' => true,
                'meta' => ['icon' => 'thermometer', 'chart' => 'line'],
            ],
            [
                'id' => 'ph',
                'display_name' => 'pH',
                'category' => 'nutrients',
                'default_unit' => 'pH',
                'value_type' => 'float',
                'default_range' => [0, 14],
                'critical' => false,
                'meta' => ['icon' => 'flask', 'chart' => 'line'],
            ],
            [
                'id' => 'ec',
                'display_name' => 'EC',
                'category' => 'nutrients',
                'default_unit' => 'mS/cm',
                'value_type' => 'float',
                'default_range' => [0, 10],
                'critical' => false,
                'meta' => ['icon' => 'bolt', 'chart' => 'line'],
            ],
        ];

        foreach ($types as $type) {
            SensorType::updateOrCreate(['id' => $type['id']], $type);
        }
    }
}
