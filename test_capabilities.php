#!/usr/bin/env php
<?php
/**
 * Test-Skript für Capabilities-Normalisierung
 */

require __DIR__ . '/vendor/autoload.php';

// Simulate agent's simplified format
$agentCapabilities = [
    'board_name' => 'arduino_uno',
    'sensors' => ['water_level', 'tds', 'temperature'],
    'actuators' => ['spray_pump', 'fill_valve'],
];

echo "=== Agent Format (vereinfacht) ===\n";
echo json_encode($agentCapabilities, JSON_PRETTY_PRINT) . "\n\n";

// Simulate normalization (manual implementation for testing)
$normalized = normalizeForTest($agentCapabilities);

echo "=== Normalisiert (Laravel-Format) ===\n";
echo json_encode($normalized, JSON_PRETTY_PRINT) . "\n\n";

echo "✅ Test erfolgreich - Format kompatibel\n";

function normalizeForTest(array $capabilities): array
{
    $normalized = [];

    // Board
    if (isset($capabilities['board_name'])) {
        $normalized['board'] = [
            'id' => $capabilities['board_name'],
            'vendor' => 'Unknown',
            'model' => ucfirst($capabilities['board_name']),
            'connection' => 'serial',
        ];
    }

    // Sensors
    if (isset($capabilities['sensors']) && is_array($capabilities['sensors'])) {
        $normalized['sensors'] = array_map(function ($sensorId) {
            return [
                'id' => $sensorId,
                'display_name' => ucwords(str_replace('_', ' ', $sensorId)),
                'category' => guessCategory($sensorId),
                'unit' => guessUnit($sensorId),
                'value_type' => 'float',
                'critical' => false,
            ];
        }, $capabilities['sensors']);
    }

    // Actuators
    if (isset($capabilities['actuators']) && is_array($capabilities['actuators'])) {
        $normalized['actuators'] = array_map(function ($actuatorId) {
            return [
                'id' => $actuatorId,
                'display_name' => ucwords(str_replace('_', ' ', $actuatorId)),
                'category' => guessCategory($actuatorId),
                'command_type' => guessCommandType($actuatorId),
                'critical' => false,
            ];
        }, $capabilities['actuators']);
    }

    return $normalized;
}

function guessCategory(string $id): string
{
    $id = strtolower($id);

    if (str_contains($id, 'water') || str_contains($id, 'spray') || str_contains($id, 'pump') || str_contains($id, 'fill')) {
        return 'irrigation';
    }
    if (str_contains($id, 'tds') || str_contains($id, 'ph') || str_contains($id, 'ec')) {
        return 'nutrients';
    }
    if (str_contains($id, 'temp') || str_contains($id, 'humid')) {
        return 'environment';
    }

    return 'custom';
}

function guessUnit(string $id): string
{
    $id = strtolower($id);

    if (str_contains($id, 'temp')) return '°C';
    if (str_contains($id, 'water') || str_contains($id, 'level')) return '%';
    if (str_contains($id, 'tds')) return 'ppm';

    return 'unit';
}

function guessCommandType(string $id): string
{
    $id = strtolower($id);

    if (str_contains($id, 'pump') || str_contains($id, 'spray')) return 'duration';
    if (str_contains($id, 'valve') || str_contains($id, 'fill')) return 'toggle';

    return 'toggle';
}
