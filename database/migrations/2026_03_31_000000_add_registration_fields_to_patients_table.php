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
        $hasToken = Schema::hasColumn('patients', 'token_number');
        $hasPatientName = Schema::hasColumn('patients', 'patient_name');
        $hasCategoryId = Schema::hasColumn('patients', 'category_id');
        $hasDoctorId = Schema::hasColumn('patients', 'doctor_id');
        $hasStatus = Schema::hasColumn('patients', 'status');

        Schema::table('patients', function (Blueprint $table) use (
            $hasToken,
            $hasPatientName,
            $hasCategoryId,
            $hasDoctorId,
            $hasStatus
        ) {
            if (!$hasToken) {
                $table->string('token_number', 20)->nullable()->after('id');
            }

            if (!$hasPatientName) {
                $table->string('patient_name')->nullable()->after('token_number');
            }

            if (!$hasCategoryId) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->constrained('categories')
                    ->nullOnDelete()
                    ->after('phone');
            }

            if (!$hasDoctorId) {
                $table->foreignId('doctor_id')
                    ->nullable()
                    ->constrained('doctors')
                    ->nullOnDelete()
                    ->after('category_id');
            }

            if (!$hasStatus) {
                $table->string('status', 50)->default('waiting')->after('doctor_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasToken = Schema::hasColumn('patients', 'token_number');
        $hasPatientName = Schema::hasColumn('patients', 'patient_name');
        $hasCategoryId = Schema::hasColumn('patients', 'category_id');
        $hasDoctorId = Schema::hasColumn('patients', 'doctor_id');
        $hasStatus = Schema::hasColumn('patients', 'status');

        Schema::table('patients', function (Blueprint $table) use (
            $hasToken,
            $hasPatientName,
            $hasCategoryId,
            $hasDoctorId,
            $hasStatus
        ) {
            if ($hasDoctorId) {
                $table->dropConstrainedForeignId('doctor_id');
            }

            if ($hasCategoryId) {
                $table->dropConstrainedForeignId('category_id');
            }

            if ($hasStatus) {
                $table->dropColumn('status');
            }

            if ($hasPatientName) {
                $table->dropColumn('patient_name');
            }

            if ($hasToken) {
                $table->dropColumn('token_number');
            }
        });
    }
};

