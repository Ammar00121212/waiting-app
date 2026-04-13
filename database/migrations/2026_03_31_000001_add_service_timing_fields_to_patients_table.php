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
        if (!Schema::hasColumn('patients', 'serving_started_at')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->timestamp('serving_started_at')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('patients', 'served_at')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->timestamp('served_at')->nullable()->after('serving_started_at');
            });
        }

        if (!Schema::hasColumn('patients', 'service_time_minutes')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->unsignedInteger('service_time_minutes')->nullable()->after('served_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('patients', 'service_time_minutes')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('service_time_minutes');
            });
        }

        if (Schema::hasColumn('patients', 'served_at')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('served_at');
            });
        }

        if (Schema::hasColumn('patients', 'serving_started_at')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('serving_started_at');
            });
        }
    }
};

