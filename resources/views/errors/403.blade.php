<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Access denied — Waiting App</title>
    <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/modules/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/waiting-app.css') }}">
</head>
<body class="wa-page">
    <div class="container px-3 py-4 py-md-5 wa-container">
        <div class="wa-card wa-card-hero">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center mb-3">
                    <div class="wa-hero-icon wa-hero-icon-admin mr-3">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div>
                        <h1 class="h4 mb-1">Access denied</h1>
                        <div class="text-muted small">You don’t have permission to open this page.</div>
                    </div>
                </div>

                <div class="alert alert-danger wa-radius-lg border-0 shadow-sm mb-4">
                    <div class="d-flex">
                        <div class="mr-3"><i class="fas fa-triangle-exclamation"></i></div>
                        <div class="mb-0">
                            If you think this is a mistake, sign in with the correct department admin or Super Admin account.
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column flex-sm-row gap-2">
                    <a href="{{ route('dashboard') }}" class="btn wa-btn wa-btn-primary">
                        <i class="fas fa-gauge-high mr-1"></i> Back to dashboard
                    </a>
                    <a href="{{ route('staff.login') }}" class="btn wa-btn wa-btn-ghost">
                        <i class="fas fa-arrow-right-to-bracket mr-1"></i> Admin console login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

