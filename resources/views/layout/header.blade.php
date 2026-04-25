<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#6777ef">
<title>Waiting App &mdash; </title>

<!-- General CSS Files -->
<link rel="stylesheet" href="{{ asset('assets/modules/bootstrap/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/modules/fontawesome/css/all.min.css') }}">

<!-- CSS Libraries -->
<link rel="stylesheet" href="{{ asset('assets/modules/jqvmap/dist/jqvmap.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/modules/summernote/summernote-bs4.css') }}">
<link rel="stylesheet" href="{{ asset('assets/modules/owlcarousel2/dist/assets/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/modules/owlcarousel2/dist/assets/owl.theme.default.min.css') }}">

<!-- Template CSS -->
<link rel="stylesheet" href="{{ asset('assets/css/style.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/components.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/waiting-app.css') }}">

</head>
<body class="layout-4">
<!-- Page Loader -->
<div class="page-loader-wrapper">
    <span class="loader"><span class="loader-inner"></span></span>
</div>

<div id="app">
    <div class="main-wrapper main-wrapper-1">
        <div class="navbar-bg"></div>
        
        <!-- Top navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark wa-topbar">
            <div class="container-fluid px-3">
                <a class="wa-brand" href="{{ route('dashboard') }}">
                    <span class="d-inline-flex align-items-center justify-content-center bg-white bg-opacity-10 wa-pill" style="width:38px;height:38px;">
                        <i class="fas fa-heart-pulse"></i>
                    </span>
                    <span>Waiting App</span>
                </a>

                <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#waTopNav"
                        aria-controls="waTopNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="waTopNav">
                    <ul class="navbar-nav ml-auto align-items-lg-center">
                        <li class="nav-item mr-lg-2">
                            <a class="nav-link text-white-50" href="{{ route('patient.register') }}">
                                <i class="fas fa-clipboard-list mr-1"></i>Patient registration
                            </a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('staff.logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-light wa-pill px-3">
                                    <i class="fas fa-right-from-bracket mr-1"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Start main left sidebar menu -->
        <div class="main-sidebar sidebar-style-3 wa-sidebar">
            <aside id="sidebar-wrapper">
                <div class="sidebar-brand">
                    <a href="{{ route('dashboard') }}">Waiting App</a>
                </div>
                <div class="sidebar-brand sidebar-brand-sm">
                    <a href="{{ route('dashboard') }}">WA</a>
                </div>
                <ul class="sidebar-menu">
                    <li class="menu-header">Dashboard</li>
                    <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" class="nav-link"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
                    </li>
                    <li class="menu-header">Admin</li>
                    <li class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.categories.index') }}" class="nav-link">
                            <i class="fas fa-building-columns"></i><span>Departments</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.doctors.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.doctors.index') }}" class="nav-link">
                            <i class="fas fa-user-doctor"></i><span>Doctors</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.patients.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.patients.index') }}" class="nav-link">
                            <i class="fas fa-users"></i><span>Patients</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.users.index') }}" class="nav-link">
                            <i class="fas fa-users-gear"></i><span>Admin Users</span>
                        </a>
                    </li>
                </ul>
            </aside>
        </div>

        <div class="content">
            @if (session('error'))
                <div class="container-fluid px-3 pt-3">
                    <div class="alert alert-danger wa-radius-lg border-0 shadow-sm mb-3">
                        <div class="d-flex">
                            <div class="mr-3"><i class="fas fa-triangle-exclamation"></i></div>
                            <div class="mb-0">{{ session('error') }}</div>
                        </div>
                    </div>
                </div>
            @endif
            @yield('content')
        </div>

    </div>
</div>

<!-- General JS Scripts -->
<script src="{{ asset('assets/bundles/lib.vendor.bundle.js') }}"></script>
<script src="{{ asset('js/CodiePie.js') }}"></script>

<!-- JS Libraries -->
<script src="{{ asset('assets/modules/jquery.sparkline.min.js') }}"></script>
<script src="{{ asset('assets/modules/chart.min.js') }}"></script>
<script src="{{ asset('assets/modules/owlcarousel2/dist/owl.carousel.min.js') }}"></script>
<script src="{{ asset('assets/modules/summernote/summernote-bs4.js') }}"></script>
<script src="{{ asset('assets/modules/chocolat/dist/js/jquery.chocolat.min.js') }}"></script>

<!-- Page Specific JS File -->
<script src="{{ asset('js/page/index.js') }}"></script>

<!-- Template JS File -->
<script src="{{ asset('js/scripts.js') }}"></script>
<script src="{{ asset('js/custom.js') }}"></script>
@stack('scripts')
<script>
setInterval(function() {
    fetch('/run-scheduler');
}, 60000);
</script>
</body>

</html>