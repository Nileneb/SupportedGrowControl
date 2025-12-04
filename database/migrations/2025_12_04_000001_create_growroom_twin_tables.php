<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growroom_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('width')->default(1000); // Canvas width in pixels
            $table->integer('height')->default(800); // Canvas height in pixels
            $table->string('background_color')->default('#1a1a1a');
            $table->string('background_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('growroom_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('growroom_layout_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // 'device', 'plant', 'light', 'fan', 'camera', 'shelf', 'label', etc.
            $table->string('label')->nullable();
            $table->integer('x_position');
            $table->integer('y_position');
            $table->integer('width')->default(100);
            $table->integer('height')->default(100);
            $table->integer('rotation')->default(0); // Degrees
            $table->string('color')->nullable();
            $table->string('icon')->nullable(); // SVG or emoji
            $table->json('properties')->nullable(); // Additional properties like sensor mappings
            $table->integer('z_index')->default(0);
            $table->timestamps();
        });

        Schema::create('webcam_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('stream_url');
            $table->string('snapshot_url')->nullable();
            $table->enum('type', ['mjpeg', 'hls', 'webrtc', 'image'])->default('mjpeg');
            $table->boolean('is_active')->default(true);
            $table->integer('refresh_interval')->default(1000); // For image type in ms
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growroom_elements');
        Schema::dropIfExists('growroom_layouts');
        Schema::dropIfExists('webcam_feeds');
    }
};
