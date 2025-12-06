<?php

namespace Database\Seeders;

use App\Models\LogPattern;
use Illuminate\Database\Seeder;

class LogPatternSeeder extends Seeder
{
    /**
     * Seed default log patterns
     */
    public function run(): void
    {
        $patterns = [
            [
                'name' => 'Arduino Response',
                'regex_pattern' => '/Arduino Antwort:\s*(.+)/i',
                'icon' => '📊',
                'color' => 'text-green-300',
                'priority' => 10,
                'enabled' => true,
                'parser_config' => [
                    'extractor' => 'key_value_pairs',
                    'format' => 'Arduino: {parsed}',
                ],
                'description' => 'Parses Arduino sensor responses with key=value format (e.g., TDS=526, TempC=20.00)',
            ],
            [
                'name' => 'Status Update',
                'regex_pattern' => '/Status:\s*(.+)/i',
                'icon' => 'ℹ️',
                'color' => 'text-blue-300',
                'priority' => 20,
                'enabled' => true,
                'parser_config' => null,
                'description' => 'Matches status updates from device',
            ],
            [
                'name' => 'Error Message',
                'regex_pattern' => '/(error|fehler|failed):\s*(.+)/i',
                'icon' => '❌',
                'color' => 'text-red-300',
                'priority' => 5, // Higher priority - check errors first
                'enabled' => true,
                'parser_config' => null,
                'description' => 'Matches error messages in English and German',
            ],
            [
                'name' => 'Warning',
                'regex_pattern' => '/(warning|warnung|achtung):\s*(.+)/i',
                'icon' => '⚠️',
                'color' => 'text-amber-300',
                'priority' => 6,
                'enabled' => true,
                'parser_config' => null,
                'description' => 'Matches warning messages',
            ],
            [
                'name' => 'Command Execution',
                'regex_pattern' => '/(executing|ausführen|running):\s*(.+)/i',
                'icon' => '⚙️',
                'color' => 'text-purple-300',
                'priority' => 15,
                'enabled' => true,
                'parser_config' => null,
                'description' => 'Matches command execution logs',
            ],
        ];

        foreach ($patterns as $pattern) {
            LogPattern::updateOrCreate(
                ['name' => $pattern['name']],
                $pattern
            );
        }

        $this->command->info('✅ Seeded ' . count($patterns) . ' log patterns');
    }
}
