<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientResetSeeder extends Seeder
{
    /**
     * Remove all patient records (same effect as the "Clear All Patients" button / patients:clear).
     */
    public function run(): void
    {
        DB::transaction(fn () => Patient::clearAllRecords());
    }
}
