@extends('layout.header')

@section('title', 'Patients')

@section('content')
<div class="main-content">
    <section class="section">
        @php
            $isSuper = (bool) (auth()->user()?->is_super_admin);
            $deptName = $isSuper ? null : (auth()->user()?->category?->name ?? null);
            $activeStatus = request('status', '');
        @endphp

        <div class="section-header">
            <div>
                <h1 class="h4 mb-1">Patients</h1>
                <div class="text-muted small">
                    Manage queue, update statuses, and monitor estimates.
                    @if ($deptName)
                        <span class="ml-1">Department: <strong>{{ $deptName }}</strong></span>
                    @endif
                </div>
            </div>
            <div class="section-header-breadcrumb">
                @if ($isSuper)
                    <form action="{{ route('admin.patients.clear_all') }}" method="POST" class="d-inline mr-2"
                          onsubmit="return confirm('Delete ALL patients? This cannot be undone. Tokens will restart at T-001 for new registrations today.');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger wa-action-btn">
                            <i class="fas fa-broom mr-1"></i> Clear All Patients
                        </button>
                    </form>
                @endif
                <button type="button" class="btn btn-primary wa-action-btn" data-toggle="modal" data-target="#registerPatientModal">
                    <i class="fas fa-user-plus mr-1"></i> Register Patient
                </button>
            </div>
        </div>

        @if (session('register_success'))
            <div class="alert alert-success">
                Your Token: <strong>{{ session('registered_token') }}</strong>
                @if (! is_null(session('estimated_wait_minutes')))
                    | Estimated Wait: <strong>~{{ session('estimated_wait_minutes') }} minutes</strong>
                @endif
            </div>
        @endif

        @if (session('success') && ! session('register_success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div id="waFlash" class="alert d-none" role="alert"></div>

        <div class="wa-card">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h4 class="mb-0">Queue list</h4>
                    <span class="badge badge-light wa-pill px-3 py-2 border">{{ $patients->total() }} total</span>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-2 mb-3">
                    <div class="btn-group wa-pill" role="group" aria-label="Status filters">
                        <a class="btn btn-light wa-action-btn {{ $activeStatus === '' ? 'active' : '' }}"
                           href="{{ route('admin.patients.index', array_filter(['q' => request('q'), 'status' => null])) }}">
                            All
                        </a>
                        <a class="btn btn-light wa-action-btn {{ $activeStatus === 'waiting' ? 'active' : '' }}"
                           href="{{ route('admin.patients.index', array_filter(['q' => request('q'), 'status' => 'waiting'])) }}">
                            Waiting
                        </a>
                        <a class="btn btn-light wa-action-btn {{ $activeStatus === 'serving' ? 'active' : '' }}"
                           href="{{ route('admin.patients.index', array_filter(['q' => request('q'), 'status' => 'serving'])) }}">
                            Serving
                        </a>
                        <a class="btn btn-light wa-action-btn {{ $activeStatus === 'done' ? 'active' : '' }}"
                           href="{{ route('admin.patients.index', array_filter(['q' => request('q'), 'status' => 'done'])) }}">
                            Done
                        </a>
                        <a class="btn btn-light wa-action-btn {{ $activeStatus === 'no-show' ? 'active' : '' }}"
                           href="{{ route('admin.patients.index', array_filter(['q' => request('q'), 'status' => 'no-show'])) }}">
                            No-show
                        </a>
                    </div>

                    <form method="GET" action="{{ route('admin.patients.index') }}" class="d-flex align-items-center gap-2">
                        @if ($activeStatus !== '')
                            <input type="hidden" name="status" value="{{ $activeStatus }}">
                        @endif
                        <input type="text"
                               name="q"
                               value="{{ request('q') }}"
                               class="form-control wa-input"
                               placeholder="Search token / name / phone / email"
                               style="min-width: 280px;">
                        <button type="submit" class="btn btn-primary wa-action-btn">
                            <i class="fas fa-magnifying-glass mr-1"></i> Search
                        </button>
                        @if (request('q'))
                            <a class="btn btn-light wa-action-btn" href="{{ route('admin.patients.index', array_filter(['status' => $activeStatus ?: null])) }}">
                                Clear
                            </a>
                        @endif
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table wa-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Token</th>
                                <th>Name</th>
                                <th>Phone</th>
                                @if ($isSuper)
                                    <th>Department</th>
                                @endif
                                <th>Doctor</th>
                                <th>Status</th>
                                <th>Est. Wait</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($patients as $patient)
                                @php
                                    $st = $patient->status ?? 'waiting';
                                    $badge = match (strtolower($st)) {
                                        'waiting' => 'badge badge-warning',
                                        'serving' => 'badge badge-primary',
                                        'done' => 'badge badge-success',
                                        'no-show' => 'badge badge-danger',
                                        default => 'badge badge-light',
                                    };
                                    $etaLabel = '—';
                                    if ($st === 'waiting' && $patient->doctor_id) {
                                        $ahead = \App\Models\Patient::countWaitingAheadForPatient($patient, \Illuminate\Support\Carbon::today());
                                        $eta = \App\Models\Patient::getEstimatedWaitTime((int) $patient->doctor_id, $ahead);
                                        $etaLabel = '~' . $eta . ' min';
                                    }
                                @endphp
                                <tr data-patient-id="{{ $patient->id }}">
                                    <td>
                                        <span class="badge badge-info wa-pill px-3 py-2">{{ $patient->token_number ?? '—' }}</span>
                                    </td>
                                    <td>
                                        <div class="font-weight-700">{{ $patient->name }}</div>
                                        <div class="text-muted small">{{ $patient->email ?: '' }}</div>
                                    </td>
                                    <td>{{ $patient->phone ?: '—' }}</td>
                                    @if ($isSuper)
                                        <td>{{ $patient->category?->name ?? '—' }}</td>
                                    @endif
                                    <td>{{ $patient->doctor?->name ?? '—' }}</td>
                                    <td style="min-width: 10rem;">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="{{ $badge }} wa-pill px-3 py-2 text-white text-capitalize js-status-badge"
                                                  data-patient-id="{{ $patient->id }}">{{ $st }}</span>
                                        <form action="{{ route('admin.patients.update', $patient) }}" method="POST" class="mb-0 js-status-form">
                                            @csrf
                                            @method('PUT')
                                            <select name="status"
                                                    class="form-control form-control-sm js-status-select"
                                                    aria-label="Patient status">
                                                @php $stLower = strtolower((string) ($st ?? 'waiting')); @endphp
                                                <option value="waiting" {{ $stLower === 'waiting' ? 'selected' : '' }}>waiting</option>
                                                <option value="serving" {{ $stLower === 'serving' ? 'selected' : '' }}>serving</option>
                                                <option value="done" {{ $stLower === 'done' ? 'selected' : '' }}>done</option>
                                                <option value="no-show" {{ $stLower === 'no-show' ? 'selected' : '' }}>no-show</option>
                                            </select>
                                        </form>
                                        </div>
                                    </td>
                                    <td class="js-eta" data-patient-id="{{ $patient->id }}">{{ $etaLabel }}</td>
                                    <td class="text-right">
                                        <form action="{{ route('admin.patients.destroy', $patient) }}" method="POST" class="d-inline js-ajax-delete"
                                              onsubmit="return confirm('Remove this patient?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger wa-action-btn" title="Remove patient from queue" aria-label="Remove patient from queue">
                                                <i class="fas fa-trash-can mr-1"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isSuper ? 8 : 7 }}" class="text-center">No patients found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($patients->hasPages())
                <div class="card-footer text-right">
                    {{ $patients->links() }}
                </div>
            @endif
        </div>
    </section>
</div>

<div class="modal fade" id="registerPatientModal" tabindex="-1" role="dialog" aria-labelledby="registerPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.patients.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="registerPatientModalLabel">Register patient</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Walk-in registration — no account. Select category and doctor to assign the patient.</p>
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required autocomplete="name">
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Department <span class="text-danger">*</span></label>
                        <select id="admin_category_id" name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                            <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Select a department</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (string) old('category_id') === (string) $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Doctor <span class="text-danger">*</span></label>
                        <select id="admin_doctor_id" name="doctor_id" class="form-control @error('doctor_id') is-invalid @enderror" required disabled>
                            <option value="" selected disabled>Select a department first</option>
                        </select>
                        @error('doctor_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group mb-0">
                        <label>Phone <span class="text-muted font-weight-normal">(optional)</span></label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone') }}" autocomplete="tel">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Register &amp; get token</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->has('name') || $errors->has('phone'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#registerPatientModal').modal('show');
    });
</script>
@endif

@push('scripts')
<script>
(function () {
    // Automation: keep staff queue view fresh.
    // Avoid reload while modal is open to prevent losing input.
    setInterval(function () {
        try {
            var modalOpen = document.querySelector('.modal.show');
            if (modalOpen) return;
            window.location.reload();
        } catch (e) {}
    }, 15000);
})();
</script>

<script>
(function () {
    const cat = document.getElementById('admin_category_id');
    const doc = document.getElementById('admin_doctor_id');
    const oldDoctorId = @json(old('doctor_id'));

    function setDoctors(doctors) {
        doc.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.disabled = true;
        ph.selected = true;
        ph.textContent = doctors.length ? 'Select a doctor' : 'No available doctors';
        doc.appendChild(ph);

        doctors.forEach(d => {
            const o = document.createElement('option');
            o.value = d.id;
            o.textContent = d.name;
            if (oldDoctorId && String(oldDoctorId) === String(d.id)) {
                o.selected = true;
                ph.selected = false;
            }
            doc.appendChild(o);
        });

        doc.disabled = doctors.length === 0;
    }

    async function fetchDoctors(categoryId) {
        doc.disabled = true;
        doc.innerHTML = '<option selected disabled>Loading doctors...</option>';
        try {
            const res = await fetch(@json(url('/admin/patients/doctors/by-category')) + '/' + categoryId, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('failed');
            const data = await res.json();
            setDoctors(Array.isArray(data) ? data : []);
        } catch (e) {
            doc.innerHTML = '<option selected disabled>Unable to load doctors</option>';
            doc.disabled = true;
        }
    }

    cat?.addEventListener('change', function () {
        if (!cat.value) return;
        fetchDoctors(cat.value);
    });

    if (cat?.value) {
        fetchDoctors(cat.value);
    }
})();
</script>

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
                showFlash('error', (data && data.message) ? data.message : 'Remove failed.');
                return;
            }

            var tr = form.closest('tr');
            if (tr) tr.remove();
            showFlash('success', (data && data.message) ? data.message : 'Patient removed.');
        } catch (err) {
            showFlash('error', 'Network error while removing.');
        } finally {
            if (btn) btn.disabled = false;
        }
    });
})();
</script>

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
    }

    function applyBadge(patientId, badgeClass, statusText) {
        var badge = document.querySelector('.js-status-badge[data-patient-id="' + patientId + '"]');
        if (!badge) return;
        badge.className = badgeClass + ' wa-pill px-3 py-2 text-white text-capitalize js-status-badge';
        badge.setAttribute('data-patient-id', patientId);
        badge.textContent = statusText;
    }

    function applyEta(patientId, etaLabel) {
        var td = document.querySelector('.js-eta[data-patient-id="' + patientId + '"]');
        if (!td) return;
        td.textContent = etaLabel;
    }

    function setSelected(selectEl, currentStatus) {
        if (!selectEl) return;
        var st = String(currentStatus || '').toLowerCase();
        selectEl.value = st;
        selectEl.defaultValue = st;
        selectEl.setAttribute('data-prev', st);
    }

    document.addEventListener('change', async function (e) {
        var sel = e.target;
        if (!sel || !sel.classList || !sel.classList.contains('js-status-select')) return;

        var form = sel.closest('form');
        var tr = sel.closest('tr');
        var patientId = tr ? tr.getAttribute('data-patient-id') : null;
        if (!form || !patientId) return;

        var prev = sel.getAttribute('data-prev') || sel.defaultValue || sel.value;
        sel.setAttribute('data-prev', prev);

        try {
            sel.disabled = true;

            // Laravel parses form bodies for POST + _method, but not multipart bodies on raw PUT.
            var body = new URLSearchParams();
            body.append('_method', 'PUT');
            body.append('status', sel.value);

            var res = await fetch(form.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString()
            });

            var data = null;
            try { data = await res.json(); } catch (err) {}

            if (!res.ok) {
                showFlash('error', (data && data.message) ? data.message : 'Unable to update status.');
                sel.value = prev;
                return;
            }

            var p = data && data.patient ? data.patient : null;
            if (p) {
                applyBadge(String(p.id), p.badge_class, p.status);
                applyEta(String(p.id), p.eta_label || '—');
                setSelected(sel, p.status);
            }

            if (data && data.eta_updates) {
                Object.keys(data.eta_updates).forEach(function (pid) {
                    applyEta(String(pid), data.eta_updates[pid]);
                });
            }

            showFlash('success', (data && data.message) ? data.message : 'Status updated.');
            sel.setAttribute('data-prev', sel.value);
        } catch (err) {
            showFlash('error', 'Network error while updating status.');
            sel.value = prev;
        } finally {
            sel.disabled = false;
        }
    });
})();
</script>
@endpush
@endsection
