<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoNoShow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:no-show';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark long-waiting patients as no-show and sync service_times when applicable.';

    public function __construct(private readonly QueueService $queueService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = Carbon::now()->subMinutes(30);

        $patients = Patient::query()
            ->where('status', 'waiting')
            ->where('created_at', '<=', $cutoff)
            ->get();

        foreach ($patients as $patient) {
            DB::transaction(function () use ($patient) {
                $this->queueService->applyTransition($patient, 'no-show');
            });
        }

        return self::SUCCESS;
    }
}
