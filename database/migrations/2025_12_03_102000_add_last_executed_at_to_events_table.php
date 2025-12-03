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
            if (! Schema::hasColumn('events', 'last_executed_at')) {
                $table->dateTime('last_executed_at')->nullable()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'last_executed_at')) {
                $table->dropColumn('last_executed_at');
            }
        });
    }
};
