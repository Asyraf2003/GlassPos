<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AsyrafCloud')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#435ebe">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="HyperPOS Kasir">
    <link rel="apple-touch-icon" href="{{ asset('assets/static/pwa/hyperpos-icon-192.png') }}">

    <link rel="shortcut icon" href="{{ asset('assets/compiled/svg/favicon.svg') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app.css') }}?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app-dark.css') }}?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/iconly.css') }}?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="{{ asset('assets/static/css/ui-foundation.css') }}?v={{ config('app.asset_version') }}">
    @stack('styles')
</head>

<body>
    <script src="{{ asset('assets/static/js/initTheme.js') }}?v={{ config('app.asset_version') }}"></script>

    <div id="app">
        @if (request()->routeIs('admin.*'))
            @include('layouts.partials.sidebar-admin')
        @elseif (request()->routeIs('cashier.*'))
            @include('layouts.partials.sidebar-cashier')
        @endif

        <main id="main" class="d-flex flex-column min-vh-100" role="main" tabindex="-1">

            <div class="page-content flex-grow-1">
                @include('layouts.partials.alerts')

                @hasSection('heading')
                    <div class="page-heading layout-page-heading d-flex align-items-center gap-3 flex-wrap @hasSection('heading_actions') has-heading-actions @endif">
                        <a href="#" class="burger-btn layout-heading-icon-action d-block d-xl-none" aria-label="Buka menu utama" title="Buka menu utama">
                            <i class="bi bi-justify fs-3"></i>
                        </a>

                        <h3 class="mb-0 me-auto @yield('heading_title_class')">@yield('heading')</h3>

                        @hasSection('heading_actions')
                            <div class="layout-heading-actions d-flex flex-wrap align-items-center justify-content-start justify-content-md-end gap-2 ms-md-auto">
                                @yield('heading_actions')
                            </div>
                        @elseif (!request()->routeIs('admin.dashboard') && !request()->routeIs('cashier.dashboard'))
                            @hasSection('back_url')
                                <a
                                    href="@yield('back_url')"
                                    class="btn btn-light-secondary"
                                    data-layout-smart-back
                                >
                                    Kembali
                                </a>
                            @else
                                <a
                                    href="{{ request()->routeIs('admin.*') ? route('admin.dashboard') : (request()->routeIs('cashier.*') ? route('cashier.dashboard') : url('/')) }}"
                                    class="btn btn-light-secondary"
                                    data-layout-smart-back
                                >
                                    Kembali
                                </a>
                            @endif
                        @endif
                    </div>
                @endif

                @yield('content')
            </div>

            @include('layouts.partials.footer')
        </main>
    </div>

    <script src="{{ asset('assets/static/js/components/dark.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/compiled/js/app.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/shared/page-freshness.js') }}?v={{ config('app.asset_version') }}"></script>
    <script
        id="push-notification-config"
        type="application/json"
        data-service-worker-url="{{ asset('service-worker.js') }}"
        data-service-worker-scope="/"
        data-subscribe-url="{{ route('push-notifications.subscriptions.store') }}"
        data-unsubscribe-url="{{ route('push-notifications.subscriptions.destroy') }}"
        data-vapid-public-key="{{ config('services.webpush.vapid_public_key', '') }}"
        data-default-icon="{{ asset('assets/compiled/svg/favicon.svg') }}"
        data-default-url="{{ route('admin.notes.index') }}"
    ></script>
    <script src="{{ asset('assets/static/js/shared/push-notifications.js') }}?v={{ config('app.asset_version') }}"></script>
    @stack('scripts')
    <script src="{{ asset('assets/static/js/layout-smart-back.js') }}?v={{ config('app.asset_version') }}"></script>
</body>
</html>
