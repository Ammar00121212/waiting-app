<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            if (Schema::hasColumn('patients', 'served_at') && ! Schema::hasColumn('patients', 'serving_ended_at')) {
                DB::statement('ALTER TABLE `patients` CHANGE `served_at` `serving_ended_at` TIMESTAMP NULL DEFAULT NULL');
            }

            if (Schema::hasColumn('patients', 'service_time_minutes') && ! Schema::hasColumn('patients', 'actual_service_minutes')) {
                DB::statement('ALTER TABLE `patients` CHANGE `service_time_minutes` `actual_service_minutes` INT UNSIGNED NULL DEFAULT NULL');
            }

            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'served_at') && ! Schema::hasColumn('patients', 'serving_ended_at')) {
                $table->renameColumn('served_at', 'serving_ended_at');
            }
        });

        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'service_time_minutes') && ! Schema::hasColumn('patients', 'actual_service_minutes')) {
                $table->renameColumn('service_time_minutes', 'actual_service_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            if (Schema::hasColumn('patients', 'serving_ended_at') && ! Schema::hasColumn('patients', 'served_at')) {
                DB::statement('ALTER TABLE `patients` CHANGE `serving_ended_at` `served_at` TIMESTAMP NULL DEFAULT NULL');
            }

            if (Schema::hasColumn('patients', 'actual_service_minutes') && ! Schema::hasColumn('patients', 'service_time_minutes')) {
                DB::statement('ALTER TABLE `patients` CHANGE `actual_service_minutes` `service_time_minutes` INT UNSIGNED NULL DEFAULT NULL');
            }

            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'serving_ended_at') && ! Schema::hasColumn('patients', 'served_at')) {
                $table->renameColumn('serving_ended_at', 'served_at');
            }
        });

        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'actual_service_minutes') && ! Schema::hasColumn('patients', 'service_time_minutes')) {
                $table->renameColumn('actual_service_minutes', 'service_time_minutes');
            }
        });
    }
};
