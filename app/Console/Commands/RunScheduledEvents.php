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

                // Check if this is a Shelly device - execute directly
                if ($event->device_id) {
                    $device = \App\Models\Device::find($event->device_id);
                    if ($device && $device->device_type === 'shelly') {
                        $this->executeShellyCommandForDevice($event, $device);
                        
                        // Mark event as executed for this occurrence
                        $event->last_executed_at = $occCarbon;
                        $event->save();
                        
                        $countTriggered++;
                        $this->line("✓ Shelly Event #{$event->id} executed at {$occCarbon->toDateTimeString()}");
                        continue;
                    }
                }

                // Regular device commands
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
        // Regular device commands
        if ($event->command_type) {
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
     * Execute Shelly command for a device-linked Shelly
     */
    private function executeShellyCommandForDevice(Event $event, \App\Models\Device $device): void
    {
        // Find linked ShellyDevice
        $shelly = \App\Models\ShellyDevice::where('device_id', $device->id)->first();
        
        if (!$shelly) {
            Log::warning('Shelly device link not found', [
                'event_id' => $event->id,
                'device_id' => $device->id,
            ]);
            return;
        }

        // Determine action from command_type
        $action = $event->command_type;
        
        if ($action === 'turn_on' || $action === 'relay_on') {
            $result = $shelly->turnOn();
        } elseif ($action === 'turn_off' || $action === 'relay_off') {
            $result = $shelly->turnOff();
        } else {
            Log::warning('Unknown Shelly command type', [
                'event_id' => $event->id,
                'command_type' => $action,
            ]);
            return;
        }

        if ($result['success'] ?? false) {
            Log::info('Shelly command executed via device', [
                'event_id' => $event->id,
                'device_id' => $device->id,
                'shelly_id' => $shelly->id,
                'action' => $action,
            ]);
        } else {
            Log::error('Shelly command failed via device', [
                'event_id' => $event->id,
                'device_id' => $device->id,
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
