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
        if (! Schema::hasColumn('patients', 'serving_started_at')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->timestamp('serving_started_at')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('patients', 'serving_ended_at')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->timestamp('serving_ended_at')->nullable();
            });
        }

        if (! Schema::hasColumn('patients', 'actual_service_minutes')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->unsignedInteger('actual_service_minutes')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'actual_service_minutes')) {
                $table->dropColumn('actual_service_minutes');
            }
        });

        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'serving_ended_at')) {
                $table->dropColumn('serving_ended_at');
            }
        });

        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'serving_started_at')) {
                $table->dropColumn('serving_started_at');
            }
        });
    }
};
