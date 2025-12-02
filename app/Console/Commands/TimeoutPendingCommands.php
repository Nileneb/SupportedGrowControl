<?php

namespace App\Console\Commands;

use App\Events\CommandStatusUpdated;
use App\Models\Command as CommandModel;
use Illuminate\Console\Command;

class TimeoutPendingCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commands:timeout {--minutes=5 : Minutes before a pending command times out}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark old pending commands as failed due to timeout';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $timeout = now()->subMinutes($minutes);

        $this->info("Looking for pending commands older than {$minutes} minutes...");

        $pendingCommands = CommandModel::where('status', 'pending')
            ->where('created_at', '<', $timeout)
            ->get();

        if ($pendingCommands->isEmpty()) {
            $this->info('No pending commands to timeout.');
            return Command::SUCCESS;
        }

        $this->warn("Found {$pendingCommands->count()} pending commands to timeout:");

        foreach ($pendingCommands as $command) {
            $this->line("  - Command #{$command->id} ({$command->type}) created {$command->created_at->diffForHumans()}");
            
            $command->update([
                'status' => 'failed',
                'result_message' => "Timeout - Agent did not pick up command within {$minutes} minutes",
                'completed_at' => now(),
            ]);

            // Broadcast WebSocket event for UI update
            broadcast(new CommandStatusUpdated($command));
        }

        $this->info("✓ Marked {$pendingCommands->count()} commands as failed.");

        return Command::SUCCESS;
    }
}
