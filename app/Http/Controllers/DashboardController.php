<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Live queue dashboard: totals, moving-average-based service time, estimated wait for next patient (FCFS).
     * Statistics use today's queue; estimates use per-doctor moving average of last 5 completed services.
     */
    public function __invoke()
    {
        $today = Carbon::today();

        $base = Patient::query()->whereDate('created_at', $today);

        $waitingCount = (clone $base)->where('status', 'waiting')->count();
        $servingCount = (clone $base)->where('status', 'serving')->count();
        $doneCount = (clone $base)->where('status', 'done')->count();
        $totalToday = (clone $base)->count();

        $movAvgGlobal = Patient::getGlobalMovingAverageServiceMinutesToday(5);
        $avgServiceLabel = $movAvgGlobal !== null
            ? $movAvgGlobal.' min'
            : '—';

        /** Earliest waiting patient today (FCFS) — "next" in line by registration time. */
        $nextWaiting = Patient::query()
            ->whereDate('created_at', $today)
            ->where('status', 'waiting')
            ->whereNotNull('doctor_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        $estWaitLabel = '—';
        if ($nextWaiting !== null) {
            $ahead = Patient::countWaitingAheadForPatient($nextWaiting, $today);
            $est = Patient::getEstimatedWaitTime((int) $nextWaiting->doctor_id, $ahead);
            $estWaitLabel = '~'.$est.' min';
        }

        // Reports section (merged into dashboard)
        $rows = DB::table('service_times')
            ->join('patients', 'patients.id', '=', 'service_times.patient_id')
            ->leftJoin('doctors', 'doctors.id', '=', 'service_times.doctor_id')
            ->select([
                'service_times.id',
                'service_times.start_time',
                'service_times.end_time',
                'service_times.duration',
                'service_times.estimated_time',
                'patients.token_number',
                'patients.name as patient_name',
                'patients.status as patient_status',
                'doctors.name as doctor_name',
            ])
            ->orderByDesc('service_times.id')
            ->paginate(12);

        $done = DB::table('service_times')
            ->whereNotNull('end_time')
            ->whereNotNull('duration')
            ->where('duration', '>', 0);

        $avgDuration = (clone $done)->avg('duration');
        $avgEstimate = (clone $done)->whereNotNull('estimated_time')->avg('estimated_time');
        $avgAbsError = (clone $done)
            ->whereNotNull('estimated_time')
            ->selectRaw('AVG(ABS(duration - estimated_time)) as v')
            ->value('v');

        return view('admin.dashboard', compact(
            'waitingCount',
            'servingCount',
            'doneCount',
            'totalToday',
            'avgServiceLabel',
            'estWaitLabel',
            'rows',
            'avgDuration',
            'avgEstimate',
            'avgAbsError'
        ));
    }
}
