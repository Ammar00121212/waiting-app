<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatientCheckInController extends Controller
{
    public function create(): View
    {
        $categories = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('patient.checkin', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
        ]);

        // Prevent duplicate joins on the same day for the same email (when provided).
        if (! empty($data['email'])) {
            $alreadyJoined = Patient::query()
                ->whereDate('created_at', Carbon::today())
                ->where('email', $data['email'])
                ->exists();

            if ($alreadyJoined) {
                throw ValidationException::withMessages([
                    'email' => __('You already joined the queue today with this email.'),
                ]);
            }
        }

        $doctor = Doctor::query()
            ->where('id', $data['doctor_id'])
            ->where('department_id', $data['department_id'])
            ->where('is_active', true)
            ->first();

        if ($doctor === null) {
            throw ValidationException::withMessages([
                'doctor_id' => __('Please select an available doctor for the chosen department.'),
            ]);
        }

        $department = Department::query()
            ->where('id', $data['department_id'])
            ->where('is_active', true)
            ->first();

        if ($department === null) {
            throw ValidationException::withMessages([
                'department_id' => __('Please select a valid department.'),
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
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'department_id' => $department->id,
            'doctor_id' => $doctor->id,
            'status' => 'waiting',
            'token_number' => $tokenNumber,
        ]);

        return redirect()->route('patient.ticket', $patient);
    }

    public function ticket(Patient $patient): View
    {
        $today = Carbon::today();

        // Safety: only show tickets for today's queue entries.
        if (! Carbon::parse($patient->created_at)->isSameDay($today)) {
            abort(404);
        }

        $patient->load(['doctor', 'department']);

        $estimatedMinutes = null;
        if (($patient->status ?? 'waiting') === 'waiting' && $patient->doctor_id) {
            $ahead = Patient::countWaitingAheadForPatient($patient, $today);
            $estimatedMinutes = Patient::getEstimatedWaitTime((int) $patient->doctor_id, $ahead);
        }

        return view('patient.ticket', [
            'patient' => $patient,
            'estimatedMinutes' => $estimatedMinutes,
        ]);
    }

    public function ticketJson(Patient $patient)
    {
        $today = Carbon::today();
        if (! Carbon::parse($patient->created_at)->isSameDay($today)) {
            abort(404);
        }

        $patient->load(['doctor', 'department']);

        $estimatedMinutes = null;
        if ((($patient->status ?? 'waiting') === 'waiting') && $patient->doctor_id) {
            $ahead = Patient::countWaitingAheadForPatient($patient, $today);
            $estimatedMinutes = Patient::getEstimatedWaitTime((int) $patient->doctor_id, $ahead);
        }

        return response()->json([
            'id' => $patient->id,
            'token_number' => $patient->token_number,
            'status' => $patient->status,
            'estimated_minutes' => $estimatedMinutes,
            'doctor' => $patient->doctor ? [
                'id' => $patient->doctor->id,
                'name' => $patient->doctor->name,
            ] : null,
            'department' => $patient->department ? [
                'id' => $patient->department->id,
                'name' => $patient->department->name,
            ] : null,
            'updated_at' => optional($patient->updated_at)?->toIso8601String(),
        ]);
    }

    public function downloadTicket(Patient $patient)
    {
        $today = Carbon::today();
        if (! Carbon::parse($patient->created_at)->isSameDay($today)) {
            abort(404);
        }

        $patient->load(['doctor', 'department']);

        $estimatedMinutes = null;
        if ((($patient->status ?? 'waiting') === 'waiting') && $patient->doctor_id) {
            $ahead = Patient::countWaitingAheadForPatient($patient, $today);
            $estimatedMinutes = Patient::getEstimatedWaitTime((int) $patient->doctor_id, $ahead);
        }

        $pdf = Pdf::loadView('patient.ticket-pdf', [
            'patient' => $patient,
            'estimatedMinutes' => $estimatedMinutes,
        ])->setPaper('a4');

        $filename = 'WaitingApp-Ticket-'.$patient->token_number.'.pdf';

        return $pdf->download($filename);
    }

    public function doctorsByCategory(Department $category)
    {
        $doctors = Doctor::query()
            ->where('department_id', $category->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'availability']);

        return response()->json($doctors);
    }
}

