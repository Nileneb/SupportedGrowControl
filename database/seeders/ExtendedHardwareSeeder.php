<?php

namespace Database\Seeders;

use App\Models\SensorType;
use App\Models\ActuatorType;
use Illuminate\Database\Seeder;

class ExtendedHardwareSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdditionalSensors();
        $this->seedAdditionalActuators();
    }

    private function seedAdditionalSensors(): void
    {
        $sensors = [
            // Bodenfeuchte / Soil Moisture
            [
                'key' => 'soil_moisture_capacitive',
                'name' => 'Soil Moisture (Capacitive)',
                'category' => 'soil',
                'unit' => '%',
                'value_type' => 'float',
                'min_value' => 0,
                'max_value' => 100,
                'arduino_read_command' => 'SoilMoisture',
                'response_pattern' => '/SoilMoisture:\s*([\d.]+)/',
                'read_interval_seconds' => 300,
                'description' => 'Capacitive soil moisture sensor (non-corrosive)',
            ],
            
            // Luftdruck / Air Pressure (BME280)
            [
                'key' => 'air_pressure',
                'name' => 'Air Pressure',
                'category' => 'environmental',
                'unit' => 'hPa',
                'value_type' => 'float',
                'min_value' => 300,
                'max_value' => 1100,
                'arduino_read_command' => 'BME280',
                'response_pattern' => '/Pressure:\s*([\d.]+)/',
                'read_interval_seconds' => 300,
                'description' => 'BME280 barometric pressure sensor',
            ],
            
            // CO2 Sensor
            [
                'key' => 'co2',
                'name' => 'CO2 Concentration',
                'category' => 'air_quality',
                'unit' => 'ppm',
                'value_type' => 'float',
                'min_value' => 0,
                'max_value' => 5000,
                'arduino_read_command' => 'CO2',
                'response_pattern' => '/CO2:\s*(\d+)/',
                'read_interval_seconds' => 60,
                'description' => 'MH-Z19B or similar CO2 sensor',
            ],
            
            // Gassensor (MQ-2)
            [
                'key' => 'gas_mq2',
                'name' => 'Gas/Smoke Sensor (MQ-2)',
                'category' => 'air_quality',
                'unit' => 'ppm',
                'value_type' => 'float',
                'min_value' => 0,
                'max_value' => 10000,
                'arduino_read_command' => 'GasMQ2',
                'response_pattern' => '/GasMQ2:\s*([\d.]+)/',
                'read_interval_seconds' => 60,
                'description' => 'Detects smoke, LPG, propane, methane',
            ],
            
            // Lichtsensor
            [
                'key' => 'light_intensity',
                'name' => 'Light Intensity',
                'category' => 'environmental',
                'unit' => 'lux',
                'value_type' => 'float',
                'min_value' => 0,
                'max_value' => 100000,
                'arduino_read_command' => 'LightIntensity',
                'response_pattern' => '/LightIntensity:\s*([\d.]+)/',
                'read_interval_seconds' => 120,
                'description' => 'BH1750 or LDR light sensor',
            ],
            
            // EC Sensor (Electrical Conductivity)
            [
                'key' => 'ec',
                'name' => 'Electrical Conductivity (EC)',
                'category' => 'water_chemistry',
                'unit' => 'µS/cm',
                'value_type' => 'float',
                'min_value' => 0,
                'max_value' => 20000,
                'arduino_read_command' => 'EC',
                'response_pattern' => '/EC:\s*([\d.]+)/',
                'read_interval_seconds' => 300,
                'description' => 'Measures nutrient solution conductivity',
            ],
            
            // Ultraschall-Abstandssensor
            [
                'key' => 'ultrasonic_distance',
                'name' => 'Ultrasonic Distance',
                'category' => 'proximity',
                'unit' => 'cm',
                'value_type' => 'float',
                'min_value' => 2,
                'max_value' => 400,
                'arduino_read_command' => 'Distance',
                'response_pattern' => '/Distance:\s*([\d.]+)/',
                'read_interval_seconds' => 30,
                'description' => 'HC-SR04 ultrasonic sensor',
            ],
            
            // PIR Bewegungssensor
            [
                'key' => 'motion_pir',
                'name' => 'Motion Sensor (PIR)',
                'category' => 'security',
                'unit' => 'bool',
                'value_type' => 'boolean',
                'min_value' => 0,
                'max_value' => 1,
                'arduino_read_command' => 'Motion',
                'response_pattern' => '/Motion:\s*(\d)/',
                'read_interval_seconds' => 5,
                'description' => 'HC-SR501 PIR motion detector',
            ],
            
            // Flow Sensor (Durchflussmesser)
            [
                'key' => 'water_flow',
                'name' => 'Water Flow Rate',
                'category' => 'flow',
                'unit' => 'L/min',
                'value_type' => 'float',
                'min_value' => 0,
                'max_value' => 30,
                'arduino_read_command' => 'FlowRate',
                'response_pattern' => '/FlowRate:\s*([\d.]+)/',
                'read_interval_seconds' => 10,
                'description' => 'YF-S201 water flow sensor',
            ],
        ];

        foreach ($sensors as $sensor) {
            SensorType::updateOrCreate(
                ['key' => $sensor['key']],
                $sensor
            );
        }
    }

    private function seedAdditionalActuators(): void
    {
        $actuators = [
            // Dosier-Pumpe (Peristaltik)
            [
                'key' => 'peristaltic_pump',
                'name' => 'Peristaltic Dosing Pump',
                'category' => 'pump',
                'command_type' => 'duration',
                'arduino_command_duration' => 'DosingPump {duration_ms}',
                'arduino_command_on' => 'DosingPumpOn',
                'arduino_command_off' => 'DosingPumpOff',
                'duration_unit' => 'ms',
                'duration_label' => 'Pump Duration',
                'min_duration' => 100,
                'max_duration' => 60000,
                'default_duration' => 1000,
                'description' => 'Precision dosing for nutrients or pH correction',
            ],
            
            // Servo Motor
            [
                'key' => 'servo_motor',
                'name' => 'Servo Motor',
                'category' => 'motor',
                'command_type' => 'value',
                'arduino_command_value' => 'Servo {angle}',
                'amount_unit' => '°',
                'amount_label' => 'Angle',
                'min_amount' => 0,
                'max_amount' => 180,
                'default_amount' => 90,
                'description' => 'Standard servo for valve or mechanism control',
            ],
            
            // DC Motor (PWM)
            [
                'key' => 'dc_motor_pwm',
                'name' => 'DC Motor (Speed Control)',
                'category' => 'motor',
                'command_type' => 'value',
                'arduino_command_value' => 'MotorSpeed {speed}',
                'arduino_command_off' => 'MotorOff',
                'amount_unit' => '%',
                'amount_label' => 'Speed',
                'min_amount' => 0,
                'max_amount' => 100,
                'default_amount' => 50,
                'description' => 'DC motor with PWM speed control',
            ],
            
            // RGB LED Strip
            [
                'key' => 'rgb_led_strip',
                'name' => 'RGB LED Strip',
                'category' => 'light',
                'command_type' => 'value',
                'arduino_command_value' => 'RGB {r},{g},{b}',
                'arduino_command_off' => 'RGBOff',
                'description' => 'Addressable RGB LED strip for grow lighting',
            ],
            
            // Elektromagnetisches Ventil (Solenoid)
            [
                'key' => 'solenoid_valve_nutrient',
                'name' => 'Nutrient Solenoid Valve',
                'category' => 'valve',
                'command_type' => 'toggle',
                'arduino_command_on' => 'NutrientValveOn',
                'arduino_command_off' => 'NutrientValveOff',
                'description' => 'Solenoid valve for nutrient solution',
            ],
            
            // Buzzer/Alarm
            [
                'key' => 'buzzer',
                'name' => 'Buzzer/Alarm',
                'category' => 'alert',
                'command_type' => 'duration',
                'arduino_command_duration' => 'Buzzer {duration_ms}',
                'arduino_command_on' => 'BuzzerOn',
                'arduino_command_off' => 'BuzzerOff',
                'duration_unit' => 'ms',
                'duration_label' => 'Beep Duration',
                'min_duration' => 50,
                'max_duration' => 5000,
                'default_duration' => 500,
                'description' => 'Alert buzzer for warnings',
            ],
            
            // Stepper Motor
            [
                'key' => 'stepper_motor',
                'name' => 'Stepper Motor',
                'category' => 'motor',
                'command_type' => 'value',
                'arduino_command_value' => 'StepperMove {steps}',
                'amount_unit' => 'steps',
                'amount_label' => 'Steps',
                'min_amount' => -10000,
                'max_amount' => 10000,
                'default_amount' => 200,
                'description' => 'Stepper motor for precise positioning',
            ],
            
            // UV Light
            [
                'key' => 'uv_light',
                'name' => 'UV Sterilization Light',
                'category' => 'light',
                'command_type' => 'duration',
                'arduino_command_duration' => 'UVLight {duration_ms}',
                'arduino_command_on' => 'UVOn',
                'arduino_command_off' => 'UVOff',
                'duration_unit' => 's',
                'duration_label' => 'UV Duration',
                'min_duration' => 1,
                'max_duration' => 3600,
                'default_duration' => 60,
                'description' => 'UV-C light for water sterilization',
            ],
            
            // Cooling Fan
            [
                'key' => 'cooling_fan',
                'name' => 'Cooling Fan',
                'category' => 'fan',
                'command_type' => 'value',
                'arduino_command_value' => 'FanSpeed {speed}',
                'arduino_command_off' => 'FanOff',
                'amount_unit' => '%',
                'amount_label' => 'Speed',
                'min_amount' => 0,
                'max_amount' => 100,
                'default_amount' => 50,
                'description' => 'Variable speed cooling fan',
            ],
        ];

        foreach ($actuators as $actuator) {
            ActuatorType::updateOrCreate(
                ['key' => $actuator['key']],
                $actuator
            );
        }
    }
}
