<?php

namespace App\Console\Commands;

use App\Models\Patient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearPatientsCommand extends Command
{
    protected $signature = 'patients:clear {--force : Skip confirmation prompt}';

    protected $description = 'Remove all rows from the patients table (resets daily token sequence for new registrations)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Delete ALL patients? This cannot be undone.')) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $count = DB::transaction(fn () => Patient::clearAllRecords());

        $this->info("Deleted {$count} patient record(s).");

        return self::SUCCESS;
    }
}
