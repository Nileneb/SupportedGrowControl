<?php

namespace Database\Seeders;

use App\Models\SensorType;
use App\Models\ActuatorType;
use App\Models\BoardTemplate;
use Illuminate\Database\Seeder;

class HardwareConfigSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSensorTypes();
        $this->seedActuatorTypes();
        $this->seedBoardTemplates();
    }

    private function seedSensorTypes(): void
    {
        $sensors = [
            [
                'key' => 'water_level',
                'name' => 'Water Level',
                'category' => 'environmental',
                'unit' => '%',
                'value_type' => 'float',
                'min_value' => 0,
                'max_value' => 100,
                'arduino_read_command' => 'Status',
                'response_pattern' => '/WaterLevel:\s*(\d+)/',
                'read_interval_seconds' => 60,
                'description' => 'Ultrasonic or float-based water level sensor',
            ],
            [
                'key' => 'tds',
                'name' => 'TDS (Total Dissolved Solids)',
                'category' => 'water_chemistry',
                'unit' => 'ppm',
                'value_type' => 'float',
                'min_value' => 0,
                'max_value' => 2000,
                'arduino_read_command' => 'TDS',
                'response_pattern' => '/TDS=(\d+)/',
                'read_interval_seconds' => 300,
                'description' => 'Measures nutrient concentration in water',
            ],
            [
                'key' => 'temperature',
                'name' => 'Temperature',
                'category' => 'environmental',
                'unit' => '°C',
                'value_type' => 'float',
                'min_value' => -10,
                'max_value' => 50,
                'arduino_read_command' => 'TDS', // Same command returns both
                'response_pattern' => '/TempC=([\d.]+)/',
                'read_interval_seconds' => 300,
                'description' => 'DS18B20 or similar temperature sensor',
            ],
            [
                'key' => 'ph',
                'name' => 'pH Level',
                'category' => 'water_chemistry',
                'unit' => 'pH',
                'value_type' => 'float',
                'min_value' => 0,
                'max_value' => 14,
                'arduino_read_command' => 'PH',
                'response_pattern' => '/PH=([\d.]+)/',
                'read_interval_seconds' => 300,
                'description' => 'pH sensor for water quality monitoring',
            ],
            [
                'key' => 'humidity',
                'name' => 'Humidity',
                'category' => 'environmental',
                'unit' => '%',
                'value_type' => 'float',
                'min_value' => 0,
                'max_value' => 100,
                'arduino_read_command' => 'Humidity',
                'response_pattern' => '/Humidity:\s*([\d.]+)/',
                'read_interval_seconds' => 120,
                'description' => 'DHT22 or similar humidity sensor',
            ],
        ];

        foreach ($sensors as $sensor) {
            SensorType::updateOrCreate(
                ['key' => $sensor['key']],
                $sensor
            );
        }
    }

    private function seedActuatorTypes(): void
    {
        $actuators = [
            [
                'key' => 'spray_pump',
                'name' => 'Spray Pump',
                'category' => 'pump',
                'command_type' => 'duration',
                'arduino_command_on' => 'SprayOn',
                'arduino_command_off' => 'SprayOff',
                'arduino_command_duration' => 'Spray {duration_ms}',
                'duration_unit' => 'ms',
                'duration_label' => 'Spray Duration',
                'min_duration' => 100,
                'max_duration' => 30000,
                'default_duration' => 1000,
                'duration_help' => 'Time to run spray pump in milliseconds',
                'description' => 'Misting/spray pump for hydroponics',
            ],
            [
                'key' => 'fill_valve',
                'name' => 'Fill Valve',
                'category' => 'valve',
                'command_type' => 'value',
                'arduino_command_value' => 'FillL {liters}',
                'arduino_command_off' => 'CancelFill',
                'amount_unit' => 'L',
                'amount_label' => 'Amount',
                'min_amount' => 0.1,
                'max_amount' => 10,
                'default_amount' => 1,
                'description' => 'Solenoid valve for filling water tank',
            ],
            [
                'key' => 'drain_valve',
                'name' => 'Drain Valve',
                'category' => 'valve',
                'command_type' => 'toggle',
                'arduino_command_on' => 'DrainOn',
                'arduino_command_off' => 'DrainOff',
                'description' => 'Valve for draining system',
            ],
            [
                'key' => 'grow_light',
                'name' => 'Grow Light',
                'category' => 'light',
                'command_type' => 'toggle',
                'arduino_command_on' => 'LightON',
                'arduino_command_off' => 'LightOFF',
                'description' => 'LED grow lights',
            ],
            [
                'key' => 'ventilation_fan',
                'name' => 'Ventilation Fan',
                'category' => 'fan',
                'command_type' => 'toggle',
                'arduino_command_on' => 'FanON',
                'arduino_command_off' => 'FanOFF',
                'description' => 'Circulation/ventilation fan',
            ],
            [
                'key' => 'heater',
                'name' => 'Water Heater',
                'category' => 'heating',
                'command_type' => 'toggle',
                'arduino_command_on' => 'HeaterON',
                'arduino_command_off' => 'HeaterOFF',
                'description' => 'Water heating element',
            ],
        ];

        foreach ($actuators as $actuator) {
            ActuatorType::updateOrCreate(
                ['key' => $actuator['key']],
                $actuator
            );
        }
    }

    private function seedBoardTemplates(): void
    {
        $boards = [
            [
                'key' => 'arduino_uno',
                'name' => 'Arduino Uno',
                'vendor' => 'Arduino',
                'mcu' => 'ATmega328P',
                'architecture' => 'AVR',
                'digital_pins' => 14,
                'analog_pins' => 6,
                'pwm_pins' => 6,
                'available_pins' => ['D2', 'D3', 'D4', 'D5', 'D6', 'D7', 'D8', 'D9', 'D10', 'D11', 'D12', 'D13', 'A0', 'A1', 'A2', 'A3', 'A4', 'A5'],
                'reserved_pins' => ['D0', 'D1'], // RX, TX
                'description' => 'Standard Arduino Uno board',
            ],
            [
                'key' => 'arduino_mega',
                'name' => 'Arduino Mega 2560',
                'vendor' => 'Arduino',
                'mcu' => 'ATmega2560',
                'architecture' => 'AVR',
                'digital_pins' => 54,
                'analog_pins' => 16,
                'pwm_pins' => 15,
                'available_pins' => array_merge(
                    array_map(fn($i) => "D$i", range(2, 53)),
                    array_map(fn($i) => "A$i", range(0, 15))
                ),
                'reserved_pins' => ['D0', 'D1'],
                'description' => 'Arduino Mega with more I/O pins',
            ],
            [
                'key' => 'esp32',
                'name' => 'ESP32 DevKit',
                'vendor' => 'Espressif',
                'mcu' => 'ESP32',
                'architecture' => 'Xtensa LX6',
                'digital_pins' => 36,
                'analog_pins' => 18,
                'pwm_pins' => 16,
                'available_pins' => ['GPIO2', 'GPIO4', 'GPIO5', 'GPIO12', 'GPIO13', 'GPIO14', 'GPIO15', 'GPIO16', 'GPIO17', 'GPIO18', 'GPIO19', 'GPIO21', 'GPIO22', 'GPIO23', 'GPIO25', 'GPIO26', 'GPIO27', 'GPIO32', 'GPIO33'],
                'reserved_pins' => ['GPIO0', 'GPIO1', 'GPIO3'], // Boot, TX, RX
                'description' => 'ESP32 with WiFi and Bluetooth',
            ],
        ];

        foreach ($boards as $board) {
            BoardTemplate::updateOrCreate(
                ['key' => $board['key']],
                $board
            );
        }
    }
}
