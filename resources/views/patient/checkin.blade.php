<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Patient Registration — Waiting App</title>

    <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/modules/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/waiting-app.css') }}">
</head>
<body class="wa-page">
    <div class="container px-3 py-4 py-md-5 wa-container">
        <div class="wa-card wa-card-hero">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                    <div class="d-flex align-items-center">
                        <div class="wa-hero-icon wa-hero-icon-patient mr-3">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div>
                            <div class="d-inline-flex align-items-center px-3 py-1 wa-pill small font-weight-700 mb-2 wa-status-serving">
                                No login required
                            </div>
                            <h1 class="h4 mb-1">Patient registration</h1>
                            <div class="text-muted small">Select a department and doctor, then get your token instantly.</div>
                        </div>
                    </div>
                    <div class="d-none d-md-flex gap-2">
                        <a class="btn wa-btn wa-btn-ghost" href="{{ route('staff.login') }}"><i class="fas fa-arrow-right-to-bracket mr-2"></i>Admin console</a>
                    </div>
                </div>

                @php
                    $submitRoute = route('patient.register.submit');
                @endphp
                <form method="POST" action="{{ $submitRoute }}" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input id="name"
                               type="text"
                               name="name"
                               class="form-control wa-input @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               required
                               autocomplete="name"
                               autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="category_id">Department <span class="text-danger">*</span></label>
                        <select id="category_id"
                                name="category_id"
                                class="form-control wa-select @error('category_id') is-invalid @enderror"
                                required>
                            <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Select a department</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="doctor_id">Doctor <span class="text-danger">*</span></label>
                        <select id="doctor_id"
                                name="doctor_id"
                                class="form-control wa-select @error('doctor_id') is-invalid @enderror"
                                required
                                disabled>
                            <option value="" selected disabled>Select a department first</option>
                        </select>
                        @error('doctor_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small id="doctorHelp" class="form-text text-muted d-none"></small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                        <label for="phone">Phone <span class="text-muted font-weight-normal">(optional)</span></label>
                        <input id="phone"
                               type="text"
                               name="phone"
                               class="form-control wa-input @error('phone') is-invalid @enderror"
                               value="{{ old('phone') }}"
                               autocomplete="tel">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                        <label for="email">Email <span class="text-muted font-weight-normal">(optional)</span></label>
                        <input id="email"
                               type="email"
                               name="email"
                               class="form-control wa-input @error('email') is-invalid @enderror"
                               value="{{ old('email') }}"
                               autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                                <small class="form-text text-muted">If provided, we prevent duplicate registrations today.</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-stretch align-items-sm-center mt-4">
                        <button type="submit" class="btn wa-btn wa-btn-primary btn-lg flex-grow-1">
                            <i class="fas fa-ticket-simple mr-2"></i>Get my token
                        </button>
                        <a class="btn wa-btn wa-btn-ghost btn-lg d-md-none" href="{{ route('staff.login') }}">
                            <i class="fas fa-arrow-right-to-bracket mr-2"></i>Admin console
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <div class="text-center text-muted small mt-3">
            You’ll receive a token and can track your estimated wait time.
        </div>
    </div>

    <script>
        (function () {
            const categoryEl = document.getElementById('category_id');
            const doctorEl = document.getElementById('doctor_id');
            const doctorHelp = document.getElementById('doctorHelp');
            const oldDoctorId = @json(old('doctor_id'));

            function setDoctorOptions(doctors) {
                doctorEl.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.disabled = true;
                placeholder.selected = true;
                placeholder.textContent = doctors.length ? 'Select a doctor' : 'No available doctors';
                doctorEl.appendChild(placeholder);

                doctors.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.name + (d.availability ? ` (${d.availability})` : '');
                    if (oldDoctorId && String(oldDoctorId) === String(d.id)) {
                        opt.selected = true;
                        placeholder.selected = false;
                    }
                    doctorEl.appendChild(opt);
                });

                doctorEl.disabled = doctors.length === 0;
                if (doctors.length) {
                    doctorHelp.classList.remove('d-none');
                    doctorHelp.textContent = 'Availability shown when provided.';
                } else {
                    doctorHelp.classList.add('d-none');
                    doctorHelp.textContent = '';
                }
            }

            async function fetchDoctors(categoryId) {
                doctorEl.disabled = true;
                doctorEl.innerHTML = '<option selected disabled>Loading doctors...</option>';

                try {
                    const res = await fetch(`/doctors/by-category/${categoryId}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error('Failed');
                    const data = await res.json();
                    setDoctorOptions(Array.isArray(data) ? data : []);
                } catch (e) {
                    doctorEl.innerHTML = '<option selected disabled>Unable to load doctors</option>';
                    doctorEl.disabled = true;
                    doctorHelp.classList.add('d-none');
                }
            }

            categoryEl.addEventListener('change', function () {
                const v = categoryEl.value;
                if (!v) return;
                fetchDoctors(v);
            });

            if (categoryEl.value) {
                fetchDoctors(categoryEl.value);
            }
        })();
    </script>
</body>
</html>

