<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_times', function (Blueprint $table) {
            if (! Schema::hasColumn('service_times', 'start_time')) {
                $table->dateTime('start_time')->nullable()->after('doctor_id');
            }
            if (! Schema::hasColumn('service_times', 'end_time')) {
                $table->dateTime('end_time')->nullable()->after('start_time');
            }
            if (! Schema::hasColumn('service_times', 'duration')) {
                $table->integer('duration')->nullable()->after('end_time');
            }
            if (! Schema::hasColumn('service_times', 'estimated_time')) {
                $table->integer('estimated_time')->nullable()->after('duration');
            }
        });

        Schema::table('service_times', function (Blueprint $table) {
            // Add indexes after columns exist (safe even if already present).
            $table->index(['doctor_id', 'end_time'], 'service_times_doctor_end_time_idx');
            $table->index(['end_time'], 'service_times_end_time_idx');
        });
    }

    public function down(): void
    {
        Schema::table('service_times', function (Blueprint $table) {
            $table->dropIndex('service_times_doctor_end_time_idx');
            $table->dropIndex('service_times_end_time_idx');

            if (Schema::hasColumn('service_times', 'estimated_time')) {
                $table->dropColumn('estimated_time');
            }
            if (Schema::hasColumn('service_times', 'duration')) {
                $table->dropColumn('duration');
            }
            if (Schema::hasColumn('service_times', 'end_time')) {
                $table->dropColumn('end_time');
            }
            if (Schema::hasColumn('service_times', 'start_time')) {
                $table->dropColumn('start_time');
            }
        });
    }
};

