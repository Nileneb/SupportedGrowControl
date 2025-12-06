<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_pairings', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();
            $table->string('pairing_code', 12)->index();
            $table->string('status')->default('pending')->index();
            $table->text('agent_token')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('device_info')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_pairings');
    }
};
