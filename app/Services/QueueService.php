<?php

namespace App\Services;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QueueService
{
    /**
     * Admin console allows free status overrides.
     */
    public function isValidTransition(string $oldStatus, string $newStatus): bool
    {
        return true;
    }

    /**
     * Single entrypoint for status change side-effects (service_times, auto-advance, constraints).
     * Must be called inside a DB transaction.
     */
    public function applyTransition(Patient $patient, string $newStatus): void
    {
        $newStatus = trim($newStatus);

        // Reload/lock row to avoid races.
        $patient = Patient::query()->whereKey($patient->id)->lockForUpdate()->firstOrFail();
        $oldStatus = (string) ($patient->status ?? 'waiting');

        $patient->applyStatusTransition($newStatus);

        if ($newStatus === $oldStatus) {
            return;
        }

        if ($newStatus === 'serving') {
            $this->startServiceTimeRow($patient);
        }

        if ($newStatus === 'done') {
            $this->finishOpenServiceTimeRow($patient);
        }

        if ($newStatus === 'no-show') {
            // If service already started, close it (audit/accuracy).
            $this->finishOpenServiceTimeRow($patient);
        }

        // If admin moves away from 'serving' to any other state, close the open service row.
        if ($oldStatus === 'serving' && $newStatus !== 'serving') {
            $this->finishOpenServiceTimeRow($patient);
        }

        // Auto-advance for that doctor if they became idle.
        if ($patient->doctor_id && $oldStatus === 'serving' && in_array($newStatus, ['done', 'no-show'], true)) {
            $this->autoAdvanceDoctorQueueIfIdle((int) $patient->doctor_id);
        }
    }

    private function startServiceTimeRow(Patient $patient): void
    {
        // Avoid duplicates if a row already exists open.
        $open = DB::table('service_times')
            ->where('patient_id', $patient->id)
            ->whereNull('serving_ended_at')
            ->lockForUpdate()
            ->exists();

        if ($open) {
            return;
        }

        $now = Carbon::now();
        $ahead = Patient::countWaitingAheadForPatient($patient, Carbon::today());
        $estimated = $patient->doctor_id
            ? Patient::getEstimatedWaitTime((int) $patient->doctor_id, $ahead)
            : null;

        DB::table('service_times')->insert([
            'patient_id' => $patient->id,
            'doctor_id' => $patient->doctor_id,
            'start_time' => $now,
            'end_time' => null,
            'duration' => null,
            'estimated_time' => $estimated,
            'serving_started_at' => $now,
            'serving_ended_at' => null,
            'actual_service_minutes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function finishOpenServiceTimeRow(Patient $patient): void
    {
        $row = DB::table('service_times')
            ->where('patient_id', $patient->id)
            ->whereNull('serving_ended_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($row === null) {
            return;
        }

        $endedAt = Carbon::now();
        $minutes = null;
        if ($row->serving_started_at !== null) {
            $minutes = max(0, (int) Carbon::parse($row->serving_started_at)->diffInMinutes($endedAt));
        }

        DB::table('service_times')
            ->where('id', $row->id)
            ->update([
                'end_time' => $endedAt,
                'duration' => $minutes,
                'serving_ended_at' => $endedAt,
                'actual_service_minutes' => $minutes,
                'updated_at' => $endedAt,
            ]);
    }

    public function autoAdvanceDoctorQueueIfIdle(int $doctorId): void
    {
        // Lock all candidate rows for this doctor today to prevent races.
        $hasServing = Patient::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'serving')
            ->lockForUpdate()
            ->exists();

        if ($hasServing) {
            return;
        }

        $next = Patient::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'waiting')
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if ($next === null) {
            return;
        }

        $next->applyStatusTransition('serving');
        $this->startServiceTimeRow($next);
    }
}

