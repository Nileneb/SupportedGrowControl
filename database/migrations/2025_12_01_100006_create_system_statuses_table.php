<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->timestamp('measured_at')->useCurrent();
            $table->float('water_level')->nullable();
            $table->float('water_liters')->nullable();
            $table->boolean('spray_active')->default(false);
            $table->boolean('filling_active')->default(false);
            $table->float('last_tds')->nullable();
            $table->float('last_temperature')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'measured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_statuses');
    }
};
