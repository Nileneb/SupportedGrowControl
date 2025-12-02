<?php

namespace App\Console\Commands;

use App\Events\CommandStatusUpdated;
use App\Models\Command as CommandModel;
use Illuminate\Console\Command;

class TimeoutExecutingCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commands:timeout-executing {--minutes=10 : Minutes before an executing command times out}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark old executing commands as failed due to execution timeout';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $timeout = now()->subMinutes($minutes);

        $this->info("Looking for executing commands older than {$minutes} minutes...");

        $executingCommands = CommandModel::where('status', 'executing')
            ->where('updated_at', '<', $timeout)
            ->get();

        if ($executingCommands->isEmpty()) {
            $this->info('No executing commands to timeout.');
            return Command::SUCCESS;
        }

        $this->warn("Found {$executingCommands->count()} executing commands to timeout:");

        foreach ($executingCommands as $command) {
            $this->line("  - Command #{$command->id} ({$command->type}) started {$command->updated_at->diffForHumans()}");
            
            $command->update([
                'status' => 'failed',
                'result_message' => "Execution timeout - Command stuck for more than {$minutes} minutes",
                'completed_at' => now(),
            ]);

            // Broadcast WebSocket event for UI update
            broadcast(new CommandStatusUpdated($command));
        }

        $this->info("✓ Marked {$executingCommands->count()} commands as failed.");

        return Command::SUCCESS;
    }
}
