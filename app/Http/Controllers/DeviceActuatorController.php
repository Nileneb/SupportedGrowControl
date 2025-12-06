<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceActuatorController extends Controller
{
    /**
     * Get all available command types for a device (based on capabilities)
     */
    public function getCommands(string $devicePublicId)
    {
        $device = Device::where('public_id', $devicePublicId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $capabilities = $device->capabilities ?? [];
        $actuators = $capabilities['actuators'] ?? [];

        $commands = [];

        // Add actuator-based commands
        foreach ($actuators as $actuator) {
            $commands[] = [
                'type' => 'actuator',
                'id' => $actuator['id'],
                'label' => $actuator['display_name'] ?? $actuator['id'],
                'category' => $actuator['category'] ?? 'general',
                'command_type' => 'serial_command',
                'params_template' => $this->getActuatorParamsTemplate($actuator),
            ];
        }

        // Add generic command types
        $commands[] = [
            'type' => 'serial_command',
            'id' => 'serial_command',
            'label' => 'Custom Serial Command',
            'category' => 'general',
            'command_type' => 'serial_command',
            'params_template' => [
                ['name' => 'command', 'type' => 'text', 'required' => true, 'label' => 'Command Text'],
            ],
        ];

        $commands[] = [
            'type' => 'arduino_compile_upload',
            'id' => 'arduino_compile_upload',
            'label' => 'Upload Arduino Sketch',
            'category' => 'firmware',
            'command_type' => 'arduino_compile_upload',
            'params_template' => [
                ['name' => 'sketch_code', 'type' => 'textarea', 'required' => true, 'label' => 'Sketch Code'],
            ],
        ];

        return response()->json([
            'success' => true,
            'commands' => $commands,
        ]);
    }

    private function getActuatorParamsTemplate(array $actuator): array
    {
        $template = [];

        // Parse command_type to determine parameters
        $commandType = $actuator['command_type'] ?? 'toggle';

        switch ($commandType) {
            case 'duration':
                $template[] = [
                    'name' => 'duration_ms',
                    'type' => 'number',
                    'required' => true,
                    'label' => 'Duration (ms)',
                    'default' => 1000,
                ];
                break;

            case 'toggle':
                $template[] = [
                    'name' => 'state',
                    'type' => 'select',
                    'required' => true,
                    'label' => 'State',
                    'options' => ['on', 'off'],
                    'default' => 'on',
                ];
                break;

            case 'value':
                $params = $actuator['params'] ?? [];
                foreach ($params as $param) {
                    $template[] = [
                        'name' => $param['name'],
                        'type' => $param['type'] === 'int' ? 'number' : 'text',
                        'required' => true,
                        'label' => ucfirst($param['name']),
                        'min' => $param['min'] ?? null,
                        'max' => $param['max'] ?? null,
                    ];
                }
                break;
        }

        return $template;
    }
}
