<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon as IlluminateCarbon;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'token_number',
        'patient_name',
        'category_id',
        'doctor_id',
        'status',
        'serving_started_at',
        'serving_ended_at',
        'actual_service_minutes',
    ];

    protected $casts = [
        'serving_started_at' => 'datetime',
        'serving_ended_at' => 'datetime',
        'actual_service_minutes' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Default department + doctor for walk-in / self-service queue assignment.
     *
     * @return array{0: int, 1: int}|null
     */
    public static function resolveDefaultCategoryAndDoctor(): ?array
    {
        $category = Category::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first()
            ?? Category::query()->orderBy('id')->first();

        if ($category === null) {
            return null;
        }

        $doctor = Doctor::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($doctor === null) {
            return null;
        }

        return [(int) $category->id, (int) $doctor->id];
    }

    /**
     * System-wide moving average for the dashboard: mean of the last up to $limit completed
     * service durations today (all doctors), ordered by serving end time — aligns with FYP methodology.
     */
    public static function getGlobalMovingAverageServiceMinutesToday(int $limit = 5): ?float
    {
        $today = IlluminateCarbon::today();

        $times = DB::table('service_times')
            ->whereNotNull('end_time')
            ->where('duration', '>', 0)
            ->whereDate('end_time', $today)
            ->orderByDesc('end_time')
            ->limit($limit)
            ->pluck('duration');

        if ($times->isEmpty()) {
            return null;
        }

        return round((float) $times->avg(), 1);
    }

    /**
     * Moving average of the last $limit completed visits for this doctor (positive durations only).
     * Returns 10 when there is no history yet.
     */
    public static function getMovingAverageServiceMinutes(int $doctorId, int $limit = 5): int
    {
        $times = DB::table('service_times')
            ->where('doctor_id', $doctorId)
            ->whereNotNull('end_time')
            ->where('duration', '>', 0)
            ->orderByDesc('end_time')
            ->limit($limit)
            ->pluck('duration')
            ->all();

        if (count($times) === 0) {
            return 10;
        }

        return (int) round(array_sum($times) / count($times));
    }

    public static function getEstimatedWaitTime(int $doctorId, int $waitingAhead): int
    {
        $avg = self::getMovingAverageServiceMinutes($doctorId, 5);

        return max(0, $avg * max(0, $waitingAhead));
    }

    public static function countWaitingAheadForPatient(
        self $patient,
        Carbon|IlluminateCarbon|null $forDate = null
    ): int {
        if (! $patient->doctor_id) {
            return 0;
        }

        $date = $forDate ?? IlluminateCarbon::today();

        return self::query()
            ->where('doctor_id', $patient->doctor_id)
            ->where('status', 'waiting')
            ->whereDate('created_at', $date)
            ->where(function ($query) use ($patient) {
                $query
                    ->where('created_at', '<', $patient->created_at)
                    ->orWhere(function ($q) use ($patient) {
                        $q->where('created_at', '=', $patient->created_at)
                            ->where('id', '<', $patient->id);
                    });
            })
            ->count();
    }


    public function applyStatusTransition(string $newStatus): void
    {
        $newStatus = trim($newStatus);
        $oldStatus = (string) ($this->status ?? 'waiting');

        if ($newStatus === $oldStatus) {
            return;
        }

        if ($newStatus === 'serving') {
            $this->status = 'serving';
            $this->serving_started_at = Carbon::now();
            $this->serving_ended_at = null;
            $this->actual_service_minutes = null;
            $this->save();

            return;
        }

        if ($newStatus === 'done') {
            $this->status = 'done';
            $this->serving_ended_at = Carbon::now();

            if ($this->serving_started_at) {
                $this->actual_service_minutes = max(
                    0,
                    (int) $this->serving_started_at->diffInMinutes($this->serving_ended_at)
                );
            }

            $this->save();

            return;
        }

        if ($newStatus === 'waiting') {
            $this->status = 'waiting';
            $this->serving_started_at = null;
            $this->serving_ended_at = null;
            $this->actual_service_minutes = null;
            $this->save();

            return;
        }

        if ($newStatus === 'no-show') {
            $this->status = 'no-show';
            $this->serving_started_at = null;
            $this->serving_ended_at = null;
            $this->actual_service_minutes = null;
            $this->save();

            return;
        }

        $this->status = $newStatus;
        $this->save();
    }

    /**
     * Delete every row in the patients table (next daily token starts at T-001).
     */
    public static function clearAllRecords(): int
    {
        // Keep service_times consistent even if FK isn't cascading (older DBs).
        DB::table('service_times')->delete();

        return (int) static::query()->delete();
    }
}
