<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_times', function (Blueprint $table) {
            // Recreate FK with cascade to allow deleting patients safely.
            $table->dropForeign(['patient_id']);
            $table->foreign('patient_id')
                ->references('id')
                ->on('patients')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_times', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->foreign('patient_id')
                ->references('id')
                ->on('patients');
        });
    }
};

