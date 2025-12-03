<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (Schema::hasTable('events') && !Schema::hasColumn('events', 'rrule')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('rrule')->nullable()->after('meta');
            });
        }
    }
    public function down() {
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'rrule')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('rrule');
            });
        }
    }
};
