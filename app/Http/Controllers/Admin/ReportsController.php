<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index()
    {
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
            ->paginate(20);

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

        return view('admin.reports.index', [
            'rows' => $rows,
            'avgDuration' => $avgDuration !== null ? round((float) $avgDuration, 1) : null,
            'avgEstimate' => $avgEstimate !== null ? round((float) $avgEstimate, 1) : null,
            'avgAbsError' => $avgAbsError !== null ? round((float) $avgAbsError, 1) : null,
        ]);
    }
}

