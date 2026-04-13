<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    public function __construct(private readonly QueueService $queueService)
    {
    }

    public function index()
    {
        $user = request()->user();
        $categoryScopeId = ($user && ! $user->is_super_admin) ? (int) $user->category_id : null;
        $q = trim((string) request()->query('q', ''));
        $status = trim((string) request()->query('status', ''));

        $patients = Patient::query()
            ->with(['doctor', 'category'])
            ->when($categoryScopeId, fn ($q) => $q->where('category_id', $categoryScopeId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('token_number', 'like', '%'.$q.'%')
                        ->orWhere('name', 'like', '%'.$q.'%')
                        ->orWhere('phone', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->when($categoryScopeId, fn ($q) => $q->where('id', $categoryScopeId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.patients.index', compact('patients', 'categories', 'q', 'status'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $categoryScopeId = ($user && ! $user->is_super_admin) ? (int) $user->category_id : null;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'category_id' => ['required', 'exists:categories,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
        ]);

        if ($categoryScopeId && (int) $data['category_id'] !== $categoryScopeId) {
            throw ValidationException::withMessages([
                'category_id' => __('You can only register patients in your department.'),
            ]);
        }

        $category = Category::query()
            ->where('id', $data['category_id'])
            ->where('is_active', true)
            ->first();
        if ($category === null) {
            throw ValidationException::withMessages([
                'category_id' => __('Please select a valid category.'),
            ]);
        }

        $doctor = Doctor::query()
            ->where('id', $data['doctor_id'])
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->first();
        if ($doctor === null) {
            throw ValidationException::withMessages([
                'doctor_id' => __('Please select an available doctor for the chosen category.'),
            ]);
        }

        $tokenNumber = DB::transaction(function () {
            $today = Carbon::today();

            $todayCount = Patient::query()
                ->whereDate('created_at', $today)
                ->lockForUpdate()
                ->count();

            return 'T-' . str_pad((string) ($todayCount + 1), 3, '0', STR_PAD_LEFT);
        });

        $patient = Patient::create([
            'name' => $data['name'],
            'patient_name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'category_id' => $category->id,
            'doctor_id' => $doctor->id,
            'status' => 'waiting',
            'token_number' => $tokenNumber,
        ]);

        $waitingAhead = Patient::countWaitingAheadForPatient($patient, Carbon::today());
        $estimated = Patient::getEstimatedWaitTime((int) $patient->doctor_id, $waitingAhead);

        return redirect()
            ->route('admin.patients.index')
            ->with('register_success', true)
            ->with('registered_token', $tokenNumber)
            ->with('estimated_wait_minutes', $estimated);
    }

    /**
     * Handle status changes from the patients list dropdown.
     *
     * Timestamp rules (implemented on {@see Patient::applyStatusTransition()}):
     * - serving: set serving_started_at to now, clear serving_ended_at and actual_service_minutes
     * - done: set serving_ended_at to now; if serving_started_at is set, set actual_service_minutes
     *   to the minute difference between serving_started_at and serving_ended_at
     *
     * The service_times table mirrors serving/done timing for reporting.
     */
    public function update(Request $request, Patient $patient)
    {
        $user = $request->user();
        $scopeCategoryId = ($user && ! $user->is_super_admin && ! is_null($user->category_id))
            ? (int) $user->category_id
            : null;
        if (! is_null($scopeCategoryId) && (int) $patient->category_id !== $scopeCategoryId) {
            $msg = 'You can only update patients in your own department.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 403);
            }

            return redirect()->route('admin.patients.index')->with('error', $msg);
        }

        $request->validate([
            'status' => ['required', 'string', Rule::in(['waiting', 'serving', 'done', 'no-show'])],
        ]);

        $newStatus = (string) $request->input('status');

        DB::transaction(function () use ($patient, $newStatus) {
            $this->queueService->applyTransition($patient, (string) $newStatus);
        });

        if ($request->expectsJson()) {
            $patient->refresh();

            $st = strtolower((string) ($patient->status ?? 'waiting'));
            $badge = match ($st) {
                'waiting' => 'badge badge-warning',
                'serving' => 'badge badge-primary',
                'done' => 'badge badge-success',
                'no-show' => 'badge badge-danger',
                default => 'badge badge-light',
            };

            $etaLabel = '—';
            if ($st === 'waiting' && $patient->doctor_id) {
                $ahead = Patient::countWaitingAheadForPatient($patient, Carbon::today());
                $eta = Patient::getEstimatedWaitTime((int) $patient->doctor_id, $ahead);
                $etaLabel = '~' . $eta . ' min';
            }

            return response()->json([
                'message' => 'Status updated.',
                'patient' => [
                    'id' => $patient->id,
                    'status' => $st,
                    'badge_class' => $badge,
                    'eta_label' => $etaLabel,
                ],
                'eta_updates' => (function () use ($patient) {
                    $doctorId = $patient->doctor_id ? (int) $patient->doctor_id : null;
                    if (! $doctorId) return [];

                    $waiting = Patient::query()
                        ->where('doctor_id', $doctorId)
                        ->whereDate('created_at', Carbon::today())
                        ->where('status', 'waiting')
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->get(['id']);

                    $updates = [];
                    foreach ($waiting as $idx => $p) {
                        $eta = Patient::getEstimatedWaitTime($doctorId, (int) $idx);
                        $updates[(int) $p->id] = '~' . $eta . ' min';
                    }

                    return $updates;
                })(),
            ]);
        }

        return redirect()->back()->with('success', 'Status updated.');
    }

    public function destroy(Request $request, Patient $patient)
    {
        $user = request()->user();
        $scopeCategoryId = ($user && ! $user->is_super_admin && ! is_null($user->category_id))
            ? (int) $user->category_id
            : null;
        if (! is_null($scopeCategoryId) && (int) $patient->category_id !== $scopeCategoryId) {
            $msg = 'You can only remove patients in your own department.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 403);
            }

            return redirect()->route('admin.patients.index')->with('error', $msg);
        }

        try {
            $patient->delete();
        } catch (QueryException $e) {
            $msg = 'Unable to remove patient.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 409);
            }

            return redirect()->back()->with('error', $msg);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Patient removed.']);
        }

        return redirect()->back()->with('success', 'Patient removed.');
    }

    public function clearAll()
    {
        $user = request()->user();
        if (! $user || ! $user->is_super_admin) {
            return redirect()
                ->route('admin.patients.index')
                ->with('error', 'Only Super Admin can clear all patients.');
        }

        DB::transaction(function () {
            Patient::clearAllRecords();
        });

        return redirect()
            ->route('admin.patients.index')
            ->with('success', 'All patients have been cleared. New registrations will start at T-001 for today.');
    }

    public function doctorsByCategory(Category $category)
    {
        $user = request()->user();
        if ($user && ! $user->is_super_admin && (int) $user->category_id !== (int) $category->id) {
            return response()->json([], 403);
        }

        $doctors = Doctor::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($doctors);
    }
}
