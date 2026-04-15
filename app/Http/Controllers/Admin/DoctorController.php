<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = request()->user();
        $departmentScopeId = ($user && ! $user->is_super_admin) ? (int) $user->department_id : null;

        $doctors = Doctor::with('department')
            ->when($departmentScopeId, fn ($q) => $q->where('department_id', $departmentScopeId))
            ->orderBy('name')
            ->paginate(10);

        return view('admin.doctors.index', compact('doctors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = request()->user();
        $departmentScopeId = ($user && ! $user->is_super_admin) ? (int) $user->department_id : null;

        $categories = Department::query()
            ->when($departmentScopeId, fn ($q) => $q->where('id', $departmentScopeId))
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('admin.doctors.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $departmentScopeId = ($user && ! $user->is_super_admin) ? (int) $user->department_id : null;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($departmentScopeId && (int) $data['department_id'] !== $departmentScopeId) {
            return redirect()
                ->route('admin.doctors.index')
                ->with('error', 'You can only add doctors in your own department.');
        }

        $data['is_active'] = $request->boolean('is_active');

        Doctor::create($data);

        return redirect()
            ->route('admin.doctors.index')
            ->with('success', 'Doctor created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Doctor $doctor)
    {
        $user = request()->user();
        if ($user && ! $user->is_super_admin && (int) $user->department_id !== (int) $doctor->department_id) {
            return redirect()
                ->route('admin.doctors.index')
                ->with('error', 'You can only edit doctors in your own department.');
        }

        $departmentScopeId = ($user && ! $user->is_super_admin) ? (int) $user->department_id : null;
        $categories = Department::query()
            ->when($departmentScopeId, fn ($q) => $q->where('id', $departmentScopeId))
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('admin.doctors.edit', compact('doctor', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Doctor $doctor)
    {
        $user = $request->user();
        if ($user && ! $user->is_super_admin && (int) $user->department_id !== (int) $doctor->department_id) {
            return redirect()
                ->route('admin.doctors.index')
                ->with('error', 'You can only update doctors in your own department.');
        }
        $departmentScopeId = ($user && ! $user->is_super_admin) ? (int) $user->department_id : null;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($departmentScopeId && (int) $data['department_id'] !== $departmentScopeId) {
            return redirect()
                ->route('admin.doctors.index')
                ->with('error', 'You can only assign doctors to your own department.');
        }

        $data['is_active'] = $request->boolean('is_active');

        $doctor->update($data);

        return redirect()
            ->route('admin.doctors.index')
            ->with('success', 'Doctor updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Doctor $doctor)
    {
        $user = request()->user();
        $scopeDepartmentId = ($user && ! $user->is_super_admin && ! is_null($user->department_id))
            ? (int) $user->department_id
            : null;
        if (! is_null($scopeDepartmentId) && (int) $doctor->department_id !== $scopeDepartmentId) {
            $msg = 'You can only delete doctors in your own department.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 403);
            }

            return redirect()->route('admin.doctors.index')->with('error', $msg);
        }

        try {
            $doctor->delete();
        } catch (QueryException $e) {
            $msg = 'Unable to delete doctor. Please remove related records first.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 409);
            }

            return redirect()->route('admin.doctors.index')->with('error', $msg);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Doctor deleted successfully.']);
        }

        return redirect()->route('admin.doctors.index')->with('success', 'Doctor deleted successfully.');
    }
}

