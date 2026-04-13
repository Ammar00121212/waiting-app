<?php

namespace App\Console\Commands;

use App\Models\Doctor;
use App\Services\QueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoServeNext extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:serve-next';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-advance each doctor queue: if idle, set next waiting patient to serving.';

    public function __construct(private readonly QueueService $queueService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $doctorIds = Doctor::query()
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->pluck('id');

        foreach ($doctorIds as $doctorId) {
            DB::transaction(function () use ($doctorId) {
                $this->queueService->autoAdvanceDoctorQueueIfIdle((int) $doctorId);
            });
        }

        return self::SUCCESS;
    }
}

