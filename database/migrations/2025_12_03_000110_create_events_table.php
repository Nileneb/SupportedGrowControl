<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('calendar_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('start_at');
                $table->dateTime('end_at')->nullable();
                $table->boolean('all_day')->default(false);
                $table->string('status')->default('planned'); // planned|active|done|canceled
                $table->string('color')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'start_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
