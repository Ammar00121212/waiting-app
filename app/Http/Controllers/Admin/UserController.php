<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with('category')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.users.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_super_admin' => ['nullable', 'boolean'],
        ]);

        $isSuper = (bool) ($request->user()?->is_super_admin);
        $isSuperAdmin = $isSuper ? $request->boolean('is_super_admin') : false;
        $categoryId = $data['category_id'] ?? null;

        // If not super admin, force department-scoped users only.
        if (! $isSuper) {
            $categoryId = $request->user()->category_id;
        }

        // Super admins are global (no department restriction).
        if ($isSuperAdmin) {
            $categoryId = null;
        }

        User::create([
            'category_id' => $categoryId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_super_admin' => $isSuperAdmin,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user created.');
    }

    public function edit(User $user)
    {
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.users.edit', compact('user', 'categories'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_super_admin' => ['nullable', 'boolean'],
        ]);

        $isSuper = (bool) ($request->user()?->is_super_admin);
        $isSuperAdmin = $isSuper ? $request->boolean('is_super_admin') : (bool) $user->is_super_admin;

        $categoryId = $data['category_id'] ?? $user->category_id;
        if (! $isSuper) {
            // Non-super admins can only manage users in their department.
            $isSuperAdmin = false;
            $categoryId = $request->user()->category_id;
        }
        if ($isSuperAdmin) {
            $categoryId = null;
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->category_id = $categoryId;
        $user->is_super_admin = $isSuperAdmin;
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return redirect()
                ->back()
                ->with('success', 'You cannot delete the currently logged-in admin.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user deleted.');
    }
}

