<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actuator_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('display_name');
            $table->string('category'); // environment, nutrients, irrigation, lighting, system, custom
            $table->string('command_type'); // toggle, duration, target, custom
            $table->json('params_schema')->nullable(); // [{name,type,min,max,unit}]
            $table->integer('min_interval')->nullable();
            $table->boolean('critical')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actuator_types');
    }
};
