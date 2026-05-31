<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - AsyrafCloud Admin Dashboard</title>

    <link rel="shortcut icon" href="{{ asset('assets/compiled/svg/favicon.svg') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app.css') }}?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app-dark.css') }}?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/auth.css') }}?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="{{ asset('assets/extensions/flatpickr/flatpickr.css') }}?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @stack('styles')

    <style>
        #auth-right {
            background: url('{{ asset('assets/compiled/webp/bengkel-motor.webp') }}') center center / cover no-repeat !important;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <script src="{{ asset('assets/static/js/initTheme.js') }}?v={{ config('app.asset_version') }}"></script>
    <div id="auth">
        <div class="row h-100">
            <div class="col-lg-5 col-12">
                <div id="auth-left">
                    @include('layouts.partials.alerts')
                    @yield('content')
                </div>
            </div>
            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-right"></div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>