@extends('layout.header')

@section('title', 'Departments')

@section('content')
<div class="main-content">
    <section class="section">
        @php
            $isSuper = (bool) (auth()->user()?->is_super_admin);
        @endphp

        <div class="section-header">
            <div>
                <h1 class="h4 mb-1">Departments</h1>
                <div class="text-muted small">These departments appear in patient registration and are used to scope department-wise admin access.</div>
            </div>
            <div class="section-header-breadcrumb">
                <button type="button"
                        class="btn btn-primary wa-action-btn"
                        data-toggle="modal"
                        data-target="#addDepartmentModal">
                    <i class="fas fa-circle-plus mr-1"></i> Add Department
                </button>
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
                    <h4 class="mb-0">Department list</h4>
                    <span class="badge badge-light wa-pill px-3 py-2 border">{{ $categories->total() }} total</span>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info wa-radius-lg border-0 shadow-sm">
                    <div class="d-flex">
                        <div class="mr-3"><i class="fas fa-circle-info"></i></div>
                        <div>
                            <div class="font-weight-700">Example departments</div>
                            <div class="small mb-0">
                                General Medicine (OPD) · Dental Care · Pediatrics · ENT · Orthopedics · Eye Clinic
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table wa-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="deptTableBody">
                            @forelse ($categories as $category)
                                <tr>
                                    <td>{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                                    <td>
                                        <div class="font-weight-700">{{ $category->name }}</div>
                                    </td>
                                    <td class="text-muted small">{{ Str::limit($category->description, 80) }}</td>
                                    <td>
                                        @if ($category->is_active)
                                            <span class="badge badge-success wa-pill px-3 py-2">Active</span>
                                        @else
                                            <span class="badge badge-secondary wa-pill px-3 py-2">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $category->created_at?->format('Y-m-d') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-warning wa-action-btn" title="Edit department" aria-label="Edit department">
                                            <i class="fas fa-pen-to-square mr-1"></i> Edit
                                        </a>
                                        @if ($isSuper)
                                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete this department? Doctors in it will also be removed.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger wa-action-btn" type="submit" title="Delete department" aria-label="Delete department">
                                                    <i class="fas fa-trash-can mr-1"></i> Delete
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No departments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($categories->hasPages())
                <div class="card-footer text-right">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </section>
</div>

<div class="modal fade" id="addDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addDepartmentForm" action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addDepartmentModalLabel">Add department</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="dept_name">Department name <span class="text-danger">*</span></label>
                        <input id="dept_name" name="name" type="text" class="form-control wa-input" required>
                        <small class="form-text text-muted">Example: General Medicine, Dental Care, Pediatrics, ENT.</small>
                    </div>
                    <div class="form-group">
                        <label for="dept_description">Description</label>
                        <textarea id="dept_description" name="description" class="form-control wa-input" rows="3"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="dept_is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="dept_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light wa-action-btn" data-dismiss="modal">Cancel</button>
                    <button id="deptSaveBtn" type="submit" class="btn btn-primary wa-action-btn">
                        <i class="fas fa-floppy-disk mr-1"></i> Save department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var isSuper = @json($isSuper);

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

    function escapeHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function truncate(s, n) {
        s = String(s || '');
        if (s.length <= n) return s;
        return s.slice(0, n - 1) + '…';
    }

    var form = document.getElementById('addDepartmentForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        var btn = document.getElementById('deptSaveBtn');
        if (btn) btn.disabled = true;

        try {
            var fd = new FormData(form);
            var res = await fetch(form.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd
            });

            var data = null;
            try { data = await res.json(); } catch (err) {}

            if (!res.ok) {
                if (res.status === 422 && data && data.errors) {
                    var firstKey = Object.keys(data.errors)[0];
                    var firstMsg = firstKey ? (data.errors[firstKey] || [])[0] : null;
                    showFlash('error', firstMsg || 'Validation failed.');
                } else {
                    showFlash('error', (data && data.message) ? data.message : 'Unable to create department.');
                }
                return;
            }

            var c = data && data.category ? data.category : null;
            if (!c) {
                showFlash('success', 'Department created.');
                window.location.reload();
                return;
            }

            var tbody = document.getElementById('deptTableBody');
            if (tbody) {
                // Prepend new row; numbering will be slightly off until refresh, but the record is visible instantly.
                var row = document.createElement('tr');
                row.innerHTML =
                    '<td>—</td>' +
                    '<td><div class="font-weight-700">' + escapeHtml(c.name) + '</div></td>' +
                    '<td class="text-muted small">' + escapeHtml(truncate(c.description, 80)) + '</td>' +
                    '<td>' + (c.is_active
                        ? '<span class="badge badge-success wa-pill px-3 py-2">Active</span>'
                        : '<span class="badge badge-secondary wa-pill px-3 py-2">Inactive</span>') + '</td>' +
                    '<td>' + escapeHtml(c.created_at || '') + '</td>' +
                    '<td class="text-right">' +
                        '<a href="' + escapeHtml(c.edit_url) + '" class="btn btn-sm btn-warning wa-action-btn" title="Edit department" aria-label="Edit department">' +
                            '<i class="fas fa-pen-to-square mr-1"></i> Edit' +
                        '</a>' +
                        (isSuper ? (
                            ' <form action="' + escapeHtml(c.destroy_url) + '" method="POST" class="d-inline" onsubmit="return confirm(\'Delete this department? Doctors in it will also be removed.\');">' +
                                '<input type="hidden" name="_token" value="' + escapeHtml(csrfToken()) + '">' +
                                '<input type="hidden" name="_method" value="DELETE">' +
                                '<button class="btn btn-sm btn-danger wa-action-btn" type="submit" title="Delete department" aria-label="Delete department">' +
                                    '<i class="fas fa-trash-can mr-1"></i> Delete' +
                                '</button>' +
                            '</form>'
                        ) : '') +
                    '</td>';
                tbody.insertBefore(row, tbody.firstChild);
            }

            showFlash('success', data.message || 'Department created successfully.');

            // Reset + close modal
            form.reset();
            var active = document.getElementById('dept_is_active');
            if (active) active.checked = true;
            try { $('#addDepartmentModal').modal('hide'); } catch (err) {}
        } catch (err) {
            showFlash('error', 'Network error while saving department.');
        } finally {
            if (btn) btn.disabled = false;
        }
    });
})();
</script>
@endpush

