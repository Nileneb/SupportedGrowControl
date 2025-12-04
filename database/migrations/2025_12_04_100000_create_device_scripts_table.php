<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_scripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('language')->default('cpp');
            $table->text('description')->nullable();
            $table->longText('code');
            $table->enum('status', ['draft','compiling','compiled','uploading','flashed','error'])->default('draft');
            $table->text('compile_log')->nullable();
            $table->text('flash_log')->nullable();
            $table->timestamp('compiled_at')->nullable();
            $table->timestamp('flashed_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('device_scripts');
    }
};
