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
                    $rrule = new RRule([
                        'RULE' => $event->rrule,
                        'DTSTART' => $event->start_at?->toDateTimeString(),
                    ]);
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

                $commandPayload = $this->buildCommandFromEventMeta($event);
                if (!$commandPayload) {
                    Log::warning('Event missing command meta; skipping', ['event_id' => $event->id]);
                    continue;
                }

                // Create the command for the device (serial_command expected by agent)
                $cmd = CommandModel::create([
                    'device_id' => $event->device_id,
                    'created_by_user_id' => $event->user_id,
                    'type' => 'serial_command',
                    'params' => ['command' => $commandPayload],
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

    private function buildCommandFromEventMeta(Event $event): ?string
    {
        $meta = $event->meta ?? [];
        $type = $meta['command_type'] ?? null;
        $params = $meta['params'] ?? [];
        if (!$type) return null;

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
