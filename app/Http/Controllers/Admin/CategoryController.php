<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = request()->user();
        $categoryScopeId = ($user && ! $user->is_super_admin) ? (int) $user->category_id : null;

        $categories = Category::query()
            ->when($categoryScopeId, fn ($q) => $q->where('id', $categoryScopeId))
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

        $category = Category::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Department created successfully.',
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'is_active' => (bool) $category->is_active,
                    'created_at' => optional($category->created_at)->format('Y-m-d'),
                    'edit_url' => route('admin.categories.edit', $category),
                    'destroy_url' => route('admin.categories.destroy', $category),
                ],
            ], 201);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Department created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $user = request()->user();
        if ($user && ! $user->is_super_admin && (int) $user->category_id !== (int) $category->id) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'You can only access your own department.');
        }

        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $user = $request->user();
        if ($user && ! $user->is_super_admin && (int) $user->category_id !== (int) $category->id) {
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
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
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
            ->with('success', 'Category deleted successfully.');
    }
}

