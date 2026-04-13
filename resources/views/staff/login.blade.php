<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Console Login — Waiting App</title>

    <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/modules/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/waiting-app.css') }}">
</head>
<body class="wa-page">
    <div class="container px-3 py-4 py-md-5 wa-container">
        <div class="wa-card wa-card-hero">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center mb-4">
                    <div class="wa-hero-icon wa-hero-icon-admin mr-3">
                        <i class="fas fa-user-lock"></i>
                    </div>
                    <div>
                        <h1 class="h4 mb-1">Admin console</h1>
                        <div class="text-muted small">Sign in to manage the queue, doctors, and dashboard.</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('staff.login.submit') }}" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email"
                               type="email"
                               name="email"
                               class="form-control wa-input @error('email') is-invalid @enderror"
                               value="{{ old('email') }}"
                               required
                               autocomplete="email"
                               autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input id="password"
                               type="password"
                               name="password"
                               class="form-control wa-input @error('password') is-invalid @enderror"
                               required
                               autocomplete="current-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="remember" name="remember" value="1">
                            <label class="custom-control-label" for="remember">Remember me</label>
                        </div>
                        <button type="submit" class="btn wa-btn wa-btn-primary">
                            Sign in
                        </button>
                    </div>
                </form>

                <hr class="my-4">
                <a class="btn wa-btn wa-btn-ghost btn-block" href="{{ route('patient.register') }}">
                    <i class="fas fa-arrow-left mr-2"></i>Back to patient registration
                </a>
            </div>
        </div>
    </div>
</body>
</html>

