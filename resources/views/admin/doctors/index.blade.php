@extends('layout.header')

@section('title', 'Doctors')

@section('content')
<div class="main-content">
    <section class="section">
        @php
            $isSuper = (bool) (auth()->user()?->is_super_admin);
        @endphp

        <div class="section-header">
            <div>
                <h1 class="h4 mb-1">Doctors</h1>
                <div class="text-muted small">Manage doctor accounts used for assignment and doctor login.</div>
            </div>
            <div class="section-header-breadcrumb">
                <a href="{{ route('admin.doctors.create') }}" class="btn btn-primary wa-action-btn">
                    <i class="fas fa-user-doctor mr-1"></i> Add Doctor
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <div id="waFlash" class="alert d-none" role="alert"></div>

        <div class="wa-card">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h4 class="mb-0">Doctor list</h4>
                    <span class="badge badge-light wa-pill px-3 py-2 border">{{ $doctors->total() }} total</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table wa-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Room</th>
                                <th>Availability</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($doctors as $doctor)
                                <tr>
                                    <td>{{ $loop->iteration + ($doctors->currentPage() - 1) * $doctors->perPage() }}</td>
                                    <td>
                                        <div class="font-weight-700">{{ $doctor->name }}</div>
                                        <div class="text-muted small">{{ $doctor->email ?: '—' }}</div>
                                    </td>
                                    <td>{{ $doctor->category?->name }}</td>
                                    <td>{{ $doctor->phone }}</td>
                                    <td>{{ $doctor->room_number }}</td>
                                    <td class="text-muted small">{{ $doctor->availability ?: '—' }}</td>
                                    <td>
                                        @if ($doctor->is_active)
                                            <span class="badge badge-success wa-pill px-3 py-2">Active</span>
                                        @else
                                            <span class="badge badge-secondary wa-pill px-3 py-2">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $doctor->created_at?->format('Y-m-d') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.doctors.edit', $doctor) }}" class="btn btn-sm btn-warning wa-action-btn" title="Edit doctor" aria-label="Edit doctor">
                                            <i class="fas fa-pen-to-square mr-1"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.doctors.destroy', $doctor) }}" method="POST" class="d-inline js-ajax-delete"
                                              onsubmit="return confirm('Are you sure you want to delete this doctor?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger wa-action-btn" type="submit" title="Delete doctor" aria-label="Delete doctor">
                                                <i class="fas fa-trash-can mr-1"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No doctors found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($doctors->hasPages())
                <div class="card-footer text-right">
                    {{ $doctors->links('pagination.no-arrows') }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function csrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    function showFlash(type, message) {
        var flash = document.getElementById('waFlash');
        if (!flash) return;
        flash.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');
        flash.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
        flash.textContent = message;
        try { flash.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch (e) {}
    }

    document.addEventListener('submit', async function (e) {
        var form = e.target;
        if (!form || !form.classList || !form.classList.contains('js-ajax-delete')) return;

        // Keep server-rendered confirm(), but if it returned true we still intercept the submit.
        e.preventDefault();

        var url = form.getAttribute('action');
        if (!url) return;

        var btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        try {
            var res = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            var data = null;
            try { data = await res.json(); } catch (err) {}

            if (!res.ok) {
                showFlash('error', (data && data.message) ? data.message : 'Delete failed.');
                return;
            }

            var tr = form.closest('tr');
            if (tr) tr.remove();
            showFlash('success', (data && data.message) ? data.message : 'Doctor deleted successfully.');
        } catch (err) {
            showFlash('error', 'Network error while deleting.');
        } finally {
            if (btn) btn.disabled = false;
        }
    });
})();
</script>
@endpush

