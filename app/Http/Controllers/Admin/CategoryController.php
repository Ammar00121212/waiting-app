<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = request()->user();
        $departmentScopeId = ($user && ! $user->is_super_admin) ? (int) $user->department_id : null;

        $categories = Department::query()
            ->when($departmentScopeId, fn ($q) => $q->where('id', $departmentScopeId))
            ->orderBy('name')
            ->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $department = Department::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Department created successfully.',
                'category' => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'description' => $department->description,
                    'is_active' => (bool) $department->is_active,
                    'created_at' => optional($department->created_at)->format('Y-m-d'),
                    'edit_url' => route('admin.categories.edit', $department),
                    'destroy_url' => route('admin.categories.destroy', $department),
                ],
            ], 201);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Department created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $category)
    {
        $user = request()->user();
        if ($user && ! $user->is_super_admin && (int) $user->department_id !== (int) $category->id) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'You can only access your own department.');
        }

        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $category)
    {
        $user = $request->user();
        if ($user && ! $user->is_super_admin && (int) $user->department_id !== (int) $category->id) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'You can only update your own department.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $category)
    {
        $user = request()->user();
        if (! $user || ! $user->is_super_admin) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Only Super Admin can delete departments.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Department deleted successfully.');
    }
}

