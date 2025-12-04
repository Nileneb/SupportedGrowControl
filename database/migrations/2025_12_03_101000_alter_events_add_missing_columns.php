<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            // Add columns if missing
            if (! Schema::hasColumn('events', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->index(['user_id']);
            }
            if (! Schema::hasColumn('events', 'device_id')) {
                if (Schema::hasTable('devices')) {
                    $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('device_id')->nullable();
                    $table->index(['device_id']);
                }
            }
            if (! Schema::hasColumn('events', 'calendar_id')) {
                if (Schema::hasTable('calendars')) {
                    $table->foreignId('calendar_id')->nullable()->constrained()->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('calendar_id')->nullable();
                    $table->index(['calendar_id']);
                }
            }
            if (! Schema::hasColumn('events', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('events', 'start_at')) {
                $table->dateTime('start_at')->nullable();
            }
            if (! Schema::hasColumn('events', 'end_at')) {
                $table->dateTime('end_at')->nullable();
            }
            if (! Schema::hasColumn('events', 'all_day')) {
                $table->boolean('all_day')->default(false);
            }
            if (! Schema::hasColumn('events', 'status')) {
                $table->string('status')->default('planned');
            }
            if (! Schema::hasColumn('events', 'color')) {
                $table->string('color')->nullable();
            }
            // Only attempt to add index if it doesn't already exist
            // We already have the events_user_id_start_at_index from a previous migration
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }
        Schema::table('events', function (Blueprint $table) {
            // We won't drop columns in down to avoid data loss; this migration is corrective.
        });
    }
};
