<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BoardTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $boardTypes = [
            [
                'name' => 'arduino_uno',
                'fqbn' => 'arduino:avr:uno',
                'vendor' => 'Arduino',
                'meta' => [
                    'core' => 'arduino:avr',
                    'cpu' => 'ATmega328P',
                    'upload_speed' => 115200,
                ],
            ],
            [
                'name' => 'arduino_mega',
                'fqbn' => 'arduino:avr:mega',
                'vendor' => 'Arduino',
                'meta' => [
                    'core' => 'arduino:avr',
                    'cpu' => 'ATmega2560',
                    'upload_speed' => 115200,
                ],
            ],
            [
                'name' => 'esp32',
                'fqbn' => 'esp32:esp32:esp32',
                'vendor' => 'Espressif',
                'meta' => [
                    'core' => 'esp32:esp32',
                    'cpu' => 'ESP32',
                    'upload_speed' => 921600,
                ],
            ],
            [
                'name' => 'esp8266',
                'fqbn' => 'esp8266:esp8266:generic',
                'vendor' => 'Espressif',
                'meta' => [
                    'core' => 'esp8266:esp8266',
                    'cpu' => 'ESP8266',
                    'upload_speed' => 921600,
                ],
            ],
            [
                'name' => 'arduino_nano',
                'fqbn' => 'arduino:avr:nano',
                'vendor' => 'Arduino',
                'meta' => [
                    'core' => 'arduino:avr',
                    'cpu' => 'ATmega328P',
                    'upload_speed' => 57600,
                ],
            ],
        ];

        foreach ($boardTypes as $boardType) {
            \App\Models\BoardType::updateOrCreate(
                ['name' => $boardType['name']],
                $boardType
            );
        }
    }
}
