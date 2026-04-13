<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_times', function (Blueprint $table) {
            // Recreate FK with cascade to allow deleting doctors safely.
            $table->dropForeign(['doctor_id']);
            $table->foreign('doctor_id')
                ->references('id')
                ->on('doctors')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_times', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->foreign('doctor_id')
                ->references('id')
                ->on('doctors');
        });
    }
};

