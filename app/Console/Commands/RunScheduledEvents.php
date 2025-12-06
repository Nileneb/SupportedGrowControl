<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Command as CommandModel;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RRule\RRule;

class RunScheduledEvents extends Command
{
    protected $signature = 'events:run-scheduled {--window=2 : Minutes window around now to trigger events}';
    protected $description = 'Trigger device commands for scheduled calendar events (including RRULE recurrence)';

    public function handle(): int
    {
        $windowMin = (int) $this->option('window');
        $now = Carbon::now();
        $from = $now->copy()->subMinutes($windowMin);
        $to = $now->copy()->addMinutes($windowMin);

        $this->info("Scanning events window: {$from->toDateTimeString()} .. {$to->toDateTimeString()}");

        // Fetch candidate events with device link
        $events = Event::query()
            ->whereNotNull('device_id')
            ->whereIn('status', ['planned','active'])
            ->whereNotNull('start_at')
            ->get();

        $countTriggered = 0;

        foreach ($events as $event) {
            $occurrences = [];

            try {
                if (!empty($event->rrule)) {
                    $rrule = new RRule($event->rrule, $event->start_at?->toDateTimeString());
                    // Get occurrences in the window
                    $occurrences = $rrule->getOccurrencesBetween($from->toDateTimeString(), $to->toDateTimeString());
                } else {
                    // Single event: check if start_at within window
                    if ($event->start_at->between($from, $to)) {
                        $occurrences = [$event->start_at->toDateTimeString()];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to compute RRULE occurrences', [
                    'event_id' => $event->id,
                    'rrule' => $event->rrule,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            foreach ($occurrences as $occurrence) {
                $occCarbon = Carbon::parse($occurrence);

                // Skip if already executed for this occurrence time
                if ($event->last_executed_at && $event->last_executed_at->equalTo($occCarbon)) {
                    continue;
                }

                $commandPayload = $this->buildCommandFromEvent($event);
                if (!$commandPayload) {
                    Log::warning('Event missing command; skipping', ['event_id' => $event->id]);
                    continue;
                }

                // Create the command for the device
                $cmd = CommandModel::create([
                    'device_id' => $event->device_id,
                    'created_by_user_id' => $event->user_id,
                    'type' => $commandPayload['type'],
                    'params' => $commandPayload['params'],
                    'status' => 'pending',
                ]);

                // Mark event as executed for this occurrence
                $event->last_executed_at = $occCarbon;
                $event->save();

                $countTriggered++;
                $this->line("✓ Event #{$event->id} triggered at {$occCarbon->toDateTimeString()} -> Command #{$cmd->id}");
            }
        }

        $this->info("Total commands queued: {$countTriggered}");
        return Command::SUCCESS;
    }

    private function buildCommandFromEvent(Event $event): ?array
    {
        // New: Use command_type and command_params directly from Event model
        if ($event->command_type) {
            // Handle Shelly commands directly instead of queuing to device
            if (in_array($event->command_type, ['shelly_on', 'shelly_off'])) {
                $this->executeShellyCommand($event);
                return null; // Don't create device command
            }
            
            return [
                'type' => $event->command_type,
                'params' => $event->command_params ?? [],
            ];
        }

        // Fallback: Old system (kept for backward compat)
        $meta = $event->meta ?? [];
        if (!isset($meta['command_type'])) {
            return null;
        }

        return [
            'type' => $meta['command_type'],
            'params' => $meta['command_params'] ?? [],
        ];
    }

    /**
     * Execute Shelly command (ON/OFF) immediately
     */
    private function executeShellyCommand(Event $event): void
    {
        $params = $event->command_params ?? [];
        $shellyId = $params['shelly_id'] ?? null;

        if (!$shellyId) {
            Log::warning('Shelly command missing shelly_id', ['event_id' => $event->id]);
            return;
        }

        $shelly = \App\Models\ShellyDevice::find($shellyId);
        if (!$shelly) {
            Log::warning('Shelly device not found', ['shelly_id' => $shellyId, 'event_id' => $event->id]);
            return;
        }

        $result = $event->command_type === 'shelly_on' 
            ? $shelly->turnOn() 
            : $shelly->turnOff();

        if ($result['success'] ?? false) {
            Log::info('Shelly command executed', [
                'event_id' => $event->id,
                'shelly_id' => $shellyId,
                'action' => $event->command_type,
            ]);
        } else {
            Log::error('Shelly command failed', [
                'event_id' => $event->id,
                'shelly_id' => $shellyId,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }
    }

    private function buildLegacySerialCommand(string $type, array $params): ?string
    {

        // Map actuator type to Arduino serial command (mirror logic from API controller)
        return match($type) {
            'spray_pump' => $this->buildSprayCommand($params),
            'fill_valve' => $this->buildFillCommand($params),
            'pump' => $this->buildPumpCommand($params),
            'valve' => $this->buildValveCommand($params),
            'light' => $this->buildLightCommand($params),
            'fan' => $this->buildFanCommand($params),
            default => null,
        };
    }

    private function buildSprayCommand(array $params): string
    {
        $durationMs = (int)($params['duration_ms'] ?? 1000);
        return "Spray {$durationMs}";
    }

    private function buildFillCommand(array $params): string
    {
        if (isset($params['target_liters'])) {
            $liters = $params['target_liters'];
            return "FillL {$liters}";
        }
        $durationMs = (int)($params['duration_ms'] ?? 5000);
        $durationSec = $durationMs / 1000;
        $estimatedLiters = ($durationSec / 60) * 6.0;
        return "FillL " . number_format($estimatedLiters, 2);
    }

    private function buildPumpCommand(array $params): string
    {
        $durationMs = (int)($params['duration_ms'] ?? 1000);
        return "Spray {$durationMs}";
    }

    private function buildValveCommand(array $params): string
    {
        $state = $params['state'] ?? 'on';
        return $state === 'on' ? "TabON" : "TabOFF";
    }

    private function buildLightCommand(array $params): string
    {
        $state = $params['state'] ?? 'on';
        return $state === 'on' ? "LightON" : "LightOFF";
    }

    private function buildFanCommand(array $params): string
    {
        $durationMs = (int)($params['duration_ms'] ?? 5000);
        return "Fan {$durationMs}";
    }
}
