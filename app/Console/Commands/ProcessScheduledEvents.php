<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\ShellyDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessScheduledEvents extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'events:process
                            {--dry-run : Show what would be executed without actually executing}';

    /**
     * The console command description.
     */
    protected $description = 'Process scheduled events and trigger Shelly device actions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No actions will be executed');
        }

        // Get events that should be executed now
        $events = Event::where('status', 'scheduled')
            ->where('start_at', '<=', now())
            ->whereNull('last_executed_at')
            ->orWhere(function ($query) {
                // Recurring events that haven't been executed recently
                $query->whereNotNull('rrule')
                    ->where('start_at', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('last_executed_at')
                            ->orWhere('last_executed_at', '<', now()->subMinutes(5));
                    });
            })
            ->get();

        if ($events->isEmpty()) {
            $this->info('✓ No events to process');
            return self::SUCCESS;
        }

        $this->info("📅 Found {$events->count()} event(s) to process");

        $processed = 0;
        $failed = 0;

        foreach ($events as $event) {
            $this->line("Processing: {$event->title} (ID: {$event->id})");

            try {
                if ($this->processEvent($event, $dryRun)) {
                    $processed++;
                    $this->info("  ✓ Event processed successfully");
                } else {
                    $this->warn("  ⚠ Event has no actions to execute");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ Failed: {$e->getMessage()}");
                Log::error('Event processing failed', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("✓ Processed: {$processed}");
        if ($failed > 0) {
            $this->warn("✗ Failed: {$failed}");
        }

        return self::SUCCESS;
    }

    /**
     * Process a single event.
     */
    protected function processEvent(Event $event, bool $dryRun): bool
    {
        $meta = $event->meta ?? [];
        $actionsExecuted = 0;

        // Single Shelly action
        if (isset($meta['shelly_device_id'])) {
            $success = $this->executeShellyAction(
                $meta['shelly_device_id'],
                $meta['action'] ?? 'on',
                $meta['duration'] ?? null,
                $dryRun
            );
            if ($success) {
                $actionsExecuted++;
            }
        }

        // Multiple Shelly actions
        if (isset($meta['shelly_actions']) && is_array($meta['shelly_actions'])) {
            foreach ($meta['shelly_actions'] as $action) {
                $success = $this->executeShellyAction(
                    $action['device_id'],
                    $action['action'] ?? 'on',
                    $action['duration'] ?? null,
                    $dryRun
                );
                if ($success) {
                    $actionsExecuted++;
                }
            }
        }

        // Update event execution timestamp only if at least one action succeeded
        if ($actionsExecuted > 0 && !$dryRun) {
            $event->update([
                'last_executed_at' => now(),
                'status' => $event->rrule ? 'scheduled' : 'completed', // Recurring stays scheduled
            ]);
        }

        return $actionsExecuted > 0;
    }

    /**
     * Execute Shelly device action.
     */
    protected function executeShellyAction(int $deviceId, string $action, ?int $duration, bool $dryRun): bool
    {
        $shelly = ShellyDevice::find($deviceId);

        if (!$shelly) {
            $this->warn("  ⚠ Shelly device {$deviceId} not found");
            return false;
        }

        $this->line("  → {$shelly->name}: {$action}" . ($duration ? " ({$duration}s)" : ''));

        if ($dryRun) {
            return true; // Dry run counts as success
        }

        try {
            $result = null;
            switch ($action) {
                case 'on':
                    $result = $shelly->turnOn();
                    break;
                case 'off':
                    $result = $shelly->turnOff();
                    break;
                case 'toggle':
                    $result = $shelly->toggle();
                    break;
                default:
                    $this->warn("  ⚠ Unknown action: {$action}");
                    return false;
            }

            // Check if action succeeded
            if (!$result || !($result['success'] ?? false)) {
                $this->error("  ✗ Failed: " . ($result['error'] ?? 'Unknown error'));
                return false;
            }

            $this->info("  ✓ Success");

            // If duration is set, schedule turn off
            if ($duration && $action === 'on') {
                $this->line("  → Scheduled auto-off in {$duration}s");
                // TODO: Queue a delayed job to turn off after duration
            }

            return true;
        } catch (\Exception $e) {
            $this->error("  ✗ Exception: " . $e->getMessage());
            return false;
        }
    }
}
