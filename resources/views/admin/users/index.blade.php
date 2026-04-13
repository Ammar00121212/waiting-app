@extends('layout.header')

@section('title', 'Admin Users')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <div>
                <h1 class="h4 mb-1">Admin users</h1>
                <div class="text-muted small">Manage who can access the Admin Console.</div>
            </div>
            <div class="section-header-breadcrumb">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary wa-action-btn">
                    <i class="fas fa-user-plus"></i> Add admin
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="wa-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table wa-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Created</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td class="font-weight-700">
                                        {{ $user->name }}
                                        @if ($user->is_super_admin)
                                            <span class="badge badge-primary wa-pill px-3 py-2 ml-2">Super</span>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ $user->email }}</td>
                                    <td class="text-muted">
                                        {{ $user->is_super_admin ? 'All departments' : ($user->category?->name ?? '—') }}
                                    </td>
                                    <td class="text-muted small">{{ $user->created_at?->format('Y-m-d') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning wa-action-btn" title="Edit admin user" aria-label="Edit admin user">
                                            <i class="fas fa-pen-to-square mr-1"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this admin user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger wa-action-btn" type="submit" title="Delete admin user" aria-label="Delete admin user">
                                                <i class="fas fa-trash-can mr-1"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No admin users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($users->hasPages())
                <div class="card-footer text-right">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

