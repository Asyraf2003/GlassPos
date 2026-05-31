@extends('layouts.app')
@section('title', 'Dashboard Laporan')
@section('heading', 'Dashboard Laporan')
@section('heading_title_class', 'dashboard-heading-title')
@section('heading_actions')
    <span class="text-muted small fw-bold dashboard-heading-period">
        Periode:
        {{ \App\Support\ViewDateFormatter::range($dashboard['period']['date_from'] ?? null, $dashboard['period']['date_to'] ?? null) }}
    </span>

    <button
        type="button"
        id="admin-dashboard-filter-open-filter"
        class="btn btn-light-secondary fw-bold layout-heading-icon-action dashboard-heading-filter-action"
        aria-label="Filter dan cetak dashboard"
    >
        <span class="dashboard-heading-filter-text">Filter &amp; Cetak</span>
        <i class="dashboard-heading-filter-icon bi bi-funnel" aria-hidden="true"></i>
    </button>
@endsection


@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/apexcharts/apexcharts.css') }}?v={{ config('app.asset_version') }}">
    <style>
        .dashboard-heading-filter-icon {
            display: none;
        }

        @media (max-width: 767.98px) {
            .dashboard-heading-title {
                font-size: 1.12rem;
                line-height: 1.2;
                font-weight: 800;
                letter-spacing: -0.02em;
            }
        }


        @media (max-width: 767.98px) {
            .dashboard-heading-period,
            .dashboard-heading-filter-text {
                display: none !important;
            }

            .dashboard-heading-filter-icon {
                display: inline-block;
            }
        }
    </style>
@endpush

@section('content')
<style>
    :root {
        --dash-primary: #435ebe;
        --dash-primary-soft: #eef2ff;
        --dash-success: #28c76f;
        --dash-success-soft: #ecfdf3;
        --dash-warning: #fdac41;
        --dash-warning-soft: #fff7e8;
        --dash-danger: #ea5455;
        --dash-danger-soft: #fff1f2;
        --dash-info: #00cfe8;
        --dash-info-soft: #ecfeff;
    }

    .dashboard-report {
        --report-surface: var(--bs-body-bg);
        --report-surface-soft: var(--bs-tertiary-bg, var(--bs-secondary-bg));
        --report-border: color-mix(in srgb, var(--bs-border-color) 82%, var(--bs-primary) 18%);
        --report-text: color-mix(in srgb, var(--bs-body-color) 88%, white 12%);
        --report-text-muted: color-mix(in srgb, var(--bs-secondary-color) 72%, white 28%);
        --report-radius-xl: 1.6rem;
        --report-radius-lg: 1.25rem;
        --report-radius-md: 1rem;
        --report-shadow: 0 .5rem 1.2rem rgba(15, 23, 42, .05);
        --report-shadow-hover: 0 1rem 2rem rgba(15, 23, 42, .10);
    }

    .dashboard-report,
    .dashboard-report input,
    .dashboard-report button,
    .dashboard-report select,
    .dashboard-report textarea,
    .dashboard-report table {
        font-family: "Nunito", "Inter", "Segoe UI", sans-serif;
    }

    .dashboard-report * {
        box-sizing: border-box;
    }

    .dashboard-report .card,
    .dashboard-report .analytics-stage-card {
        border: 1px solid var(--report-border);
        border-radius: var(--report-radius-xl);
        box-shadow: var(--report-shadow);
        overflow: hidden;
        background: var(--report-surface);
        background-clip: padding-box;
        transform: translateZ(0);
    }

    .dashboard-report .card:hover,
    .dashboard-report .analytics-stage-card:hover {
        box-shadow: var(--report-shadow-hover);
        border-color: rgba(var(--bs-primary-rgb), .26);
        transform: translateY(-2px);
    }

    .dashboard-report .panel-card-body,
    .dashboard-report .chart-card-body,
    .dashboard-report .stat-card-body {
        padding: 1.35rem;
    }

    .dashboard-report .section-title {
        font-size: 1.03rem;
        font-weight: 800;
        color: var(--report-text);
        margin-bottom: .3rem;
        letter-spacing: .01em;
        line-height: 1.35;
    }

    .dashboard-report .section-subtitle {
        font-size: .92rem;
        color: var(--report-text-muted);
        margin-bottom: 0;
        font-weight: 700;
        line-height: 1.65;
    }

    .dashboard-report .card-head,
    .dashboard-report .analytics-stage-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .dashboard-report .badge-soft {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .42rem .74rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .dashboard-report .bg-soft-primary { background: var(--dash-primary-soft); color: var(--dash-primary); }
    .dashboard-report .bg-soft-success { background: var(--dash-success-soft); color: var(--dash-success); }
    .dashboard-report .bg-soft-warning { background: var(--dash-warning-soft); color: var(--dash-warning); }
    .dashboard-report .bg-soft-danger { background: var(--dash-danger-soft); color: var(--dash-danger); }
    .dashboard-report .bg-soft-info { background: var(--dash-info-soft); color: var(--dash-info); }

    .dashboard-report .hero-card {
        position: relative;
        background:
            radial-gradient(circle at top right, rgba(255,255,255,.22), transparent 32%),
            radial-gradient(circle at bottom left, rgba(255,255,255,.14), transparent 30%),
            linear-gradient(135deg, #435ebe 0%, #5f7df3 55%, #7c94ff 100%);
        color: #fff;
        border: 0;
        box-shadow: 0 1rem 2.2rem rgba(67, 94, 190, .18);
    }

    .dashboard-report .hero-card::after {
        content: "";
        position: absolute;
        right: -40px;
        top: -30px;
        width: 180px;
        height: 180px;
        background: rgba(255,255,255,.08);
        border-radius: 50%;
    }

    .dashboard-report .hero-card::before {
        content: "";
        position: absolute;
        right: 70px;
        bottom: -70px;
        width: 160px;
        height: 160px;
        background: rgba(255,255,255,.07);
        border-radius: 50%;
    }

    .dashboard-report .hero-content {
        position: relative;
        z-index: 2;
    }

    .dashboard-report .hero-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .dashboard-report .hero-metric {
        padding: 1rem 1.1rem;
        border-radius: 18px;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.12);
        backdrop-filter: blur(6px);
    }

    .dashboard-report .hero-metric-label {
        font-size: .79rem;
        opacity: .9;
        margin-bottom: .25rem;
        font-weight: 700;
    }

    .dashboard-report .hero-metric-value {
        font-size: 1.2rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: .2px;
        color: #fff;
    }

    .dashboard-report .profile-card {
        border-radius: var(--report-radius-xl);
        overflow: hidden;
        background: linear-gradient(180deg, var(--report-surface) 0%, var(--report-surface-soft) 100%);
    }

    .dashboard-report .admin-profile-top {
        position: relative;
        overflow: hidden;
        background: linear-gradient(
            135deg,
            rgba(var(--bs-primary-rgb), .96) 0%,
            rgba(var(--bs-primary-rgb), .78) 100%
        );
        padding: 1.35rem;
    }

    .dashboard-report .admin-profile-top::after {
        content: "";
        position: absolute;
        width: 170px;
        height: 170px;
        right: -55px;
        top: -70px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(255, 255, 255, .16) 0%, rgba(255, 255, 255, 0) 70%);
    }

    .dashboard-report .admin-avatar-wrap {
        width: 74px;
        height: 74px;
        flex-shrink: 0;
        overflow: hidden;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, .58);
        background: rgba(255, 255, 255, .16);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .12);
    }

    .dashboard-report .admin-avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .dashboard-report .profile-name {
        margin: 0;
        color: #fff;
        font-size: 1.12rem;
        font-weight: 800;
        line-height: 1.3;
        letter-spacing: .01em;
    }

    .dashboard-report .profile-mail {
        color: rgba(255, 255, 255, .88);
        font-size: .94rem;
        word-break: break-word;
        font-weight: 700;
    }

    .dashboard-report .summary-strip {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .9rem;
        margin-top: 0;
    }

    .dashboard-report .summary-mini {
        border-radius: var(--report-radius-lg);
        padding: 1rem 1.05rem;
        background:
            linear-gradient(180deg, rgba(var(--bs-primary-rgb), .025), rgba(var(--bs-primary-rgb), .06)),
            var(--report-surface);
        border: 1px solid var(--report-border);
    }

    .dashboard-report .summary-mini-label {
        margin-bottom: .28rem;
        font-size: .82rem;
        font-weight: 700;
        color: var(--report-text-muted);
    }

    .dashboard-report .summary-mini-value {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.4;
        color: var(--report-text);
    }

    .dashboard-report .admin-main-btn {
        min-height: 52px;
        border-radius: 999px;
        font-weight: 800;
        letter-spacing: .01em;
    }

    .dashboard-report .stat-card {
        height: 100%;
        background:
            linear-gradient(180deg, rgba(var(--bs-primary-rgb), .03), rgba(var(--bs-primary-rgb), .055)),
            var(--report-surface);
    }

    .dashboard-report .stat-card-body {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .dashboard-report .stat-title {
        font-size: .87rem;
        font-weight: 700;
        color: var(--report-text-muted);
        margin-bottom: .3rem;
        line-height: 1.45;
    }

    .dashboard-report .stat-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--report-text);
        line-height: 1.2;
        margin-bottom: .35rem;
    }

    .dashboard-report .stat-meta,
    .dashboard-report .inventory-meta,
    .dashboard-report .asset-subtitle,
    .dashboard-report .finance-label,
    .dashboard-report .product-sku,
    .dashboard-report .analytics-stage-range {
        font-size: .8rem;
        color: var(--report-text-muted);
        font-weight: 700;
        margin: 0;
        line-height: 1.5;
    }

    .dashboard-report .meta-up { color: var(--dash-success); }
    .dashboard-report .meta-down { color: var(--dash-danger); }
    .dashboard-report .meta-flat { color: var(--dash-warning); }

    .dashboard-report .finance-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .dashboard-report .report-export-shortcut-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .dashboard-report .report-export-shortcut-card {
        border: 1px solid var(--report-border);
        border-radius: var(--report-radius-lg);
        background:
            linear-gradient(180deg, rgba(var(--bs-primary-rgb), .02), rgba(var(--bs-primary-rgb), .045)),
            var(--report-surface);
        padding: 1rem;
    }

    .dashboard-report .report-export-shortcut-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .dashboard-report .finance-box,
    .dashboard-report .inventory-item,
    .dashboard-report .asset-item {
        border: 1px solid var(--report-border);
        border-radius: var(--report-radius-lg);
        background:
            linear-gradient(180deg, rgba(var(--bs-primary-rgb), .02), rgba(var(--bs-primary-rgb), .045)),
            var(--report-surface);
        padding: .95rem 1rem;
    }

    .dashboard-report .finance-value,
    .dashboard-report .asset-value,
    .dashboard-report .inventory-title,
    .dashboard-report .asset-title,
    .dashboard-report .product-name {
        color: var(--report-text);
        font-weight: 800;
    }

    .dashboard-report .finance-value {
        font-size: 1.15rem;
        margin-bottom: .35rem;
        line-height: 1.3;
    }

    .dashboard-report .finance-note {
        font-size: .79rem;
        font-weight: 700;
        margin: 0;
        line-height: 1.5;
    }

    .dashboard-report .asset-list {
        display: grid;
        gap: .85rem;
    }

    .dashboard-report .asset-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .8rem;
    }

    .dashboard-report .asset-left,
    .dashboard-report .product-inline {
        display: flex;
        align-items: center;
        gap: .8rem;
    }

    .dashboard-report .product-avatar {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        font-weight: 700;
    }

    .dashboard-report .stock-status-row,
    .dashboard-report .restock-priority-row {
        background:
            linear-gradient(90deg, rgba(var(--bs-primary-rgb), .025), transparent),
            var(--report-surface);
        transition: background-color .18s ease, border-color .18s ease, transform .18s ease;
    }

    .dashboard-report .stock-status-row:hover,
    .dashboard-report .restock-priority-row:hover {
        transform: translateX(2px);
    }

    .dashboard-report .stock-status-row.is-safe {
        border-color: color-mix(in srgb, var(--dash-success) 30%, var(--report-border) 70%) !important;
        background: linear-gradient(90deg, rgba(40, 199, 111, .09), transparent);
    }

    .dashboard-report .stock-status-row.is-warning,
    .dashboard-report .restock-priority-row.is-warning {
        border-color: color-mix(in srgb, var(--dash-warning) 34%, var(--report-border) 66%) !important;
        background: linear-gradient(90deg, rgba(253, 172, 65, .12), transparent);
    }

    .dashboard-report .stock-status-row.is-critical,
    .dashboard-report .restock-priority-row.is-critical {
        border-color: color-mix(in srgb, var(--dash-danger) 38%, var(--report-border) 62%) !important;
        background: linear-gradient(90deg, rgba(234, 84, 85, .12), transparent);
    }

    .dashboard-report .stock-status-row.is-unconfigured {
        border-color: color-mix(in srgb, var(--dash-info) 28%, var(--report-border) 72%) !important;
        background: linear-gradient(90deg, rgba(0, 207, 232, .08), transparent);
    }

    .dashboard-report .stock-status-label {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        color: var(--report-text);
    }

    .dashboard-report .stock-status-dot {
        width: .58rem;
        height: .58rem;
        border-radius: 999px;
        background: var(--dash-info);
        box-shadow: 0 0 0 .22rem rgba(0, 207, 232, .12);
    }

    .dashboard-report .stock-status-row.is-safe .stock-status-dot {
        background: var(--dash-success);
        box-shadow: 0 0 0 .22rem rgba(40, 199, 111, .12);
    }

    .dashboard-report .stock-status-row.is-warning .stock-status-dot {
        background: var(--dash-warning);
        box-shadow: 0 0 0 .22rem rgba(253, 172, 65, .14);
    }

    .dashboard-report .stock-status-row.is-critical .stock-status-dot {
        background: var(--dash-danger);
        box-shadow: 0 0 0 .22rem rgba(234, 84, 85, .14);
    }

    .dashboard-report .stock-status-value {
        min-width: 3.2rem;
        justify-content: center;
    }

    .dashboard-report .helper-note {
        padding: .95rem 1rem;
        border-radius: var(--report-radius-lg);
        background: linear-gradient(90deg, rgba(67, 94, 190, .08), rgba(6, 182, 212, .08));
        color: var(--report-text-muted);
        font-size: .84rem;
        font-weight: 700;
        border: 1px solid rgba(67, 94, 190, .10);
        line-height: 1.6;
    }

    .dashboard-report .table-modern {
        margin-bottom: 0;
        vertical-align: middle;
    }

    .dashboard-report .table-modern thead th {
        border-bottom: 1px solid var(--report-border);
        color: var(--report-text-muted);
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 800;
        padding-top: .85rem;
        padding-bottom: .85rem;
        white-space: nowrap;
    }

    .dashboard-report .table-modern tbody td {
        padding-top: 1rem;
        padding-bottom: 1rem;
        border-color: color-mix(in srgb, var(--report-border) 82%, transparent 18%);
        color: var(--report-text);
        font-weight: 700;
    }

    .dashboard-report .table-modern tbody tr:hover td {
        background: rgba(var(--bs-primary-rgb), .04);
    }

    .dashboard-report .analytics-stage-card {
        padding: 1.35rem;
        border-radius: 1.25rem;
        height: 100%;
        background:
            linear-gradient(180deg, rgba(var(--bs-primary-rgb), .025), rgba(var(--bs-primary-rgb), .055)),
            var(--report-surface);
    }

    .dashboard-report .chart-shell.chart-placeholder {
        min-height: 340px;
        padding: 0;
        border: 0;
        background: transparent;
        box-shadow: none;
    }

    .dashboard-report .chart-canvas-shell {
        min-height: 340px;
        padding: .2rem 0 0;
        border: 0;
        background: transparent;
    }

    .dashboard-report .analytics-json,
    .dashboard-report .analytics-summary-list {
        display: none !important;
    }

    .dashboard-report .analytics-stage-card .apexcharts-canvas,
    .dashboard-report .analytics-stage-card .apexcharts-svg {
        border-radius: 18px;
    }

    .dashboard-report .analytics-stage-card .apexcharts-text tspan,
    .dashboard-report .analytics-stage-card .apexcharts-legend-text {
        font-weight: 800 !important;
    }

    .dashboard-report .analytics-stage-card .apexcharts-xaxis-label,
    .dashboard-report .analytics-stage-card .apexcharts-yaxis-label {
        fill: var(--report-text-muted) !important;
    }

    .dashboard-report .analytics-stage-card .apexcharts-gridline {
        stroke-opacity: .85;
    }

    .dashboard-report .analytics-stage-card .apexcharts-toolbar {
        display: none !important;
    }

    @media (max-width: 1199.98px) {
        .dashboard-report .hero-grid,
        .dashboard-report .finance-grid,
        .dashboard-report .report-export-shortcut-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .dashboard-report .hero-grid,
        .dashboard-report .finance-grid,
        .dashboard-report .summary-strip,
        .dashboard-report .report-export-shortcut-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-report .card-head,
        .dashboard-report .analytics-stage-head {
            flex-direction: column;
        }

        .dashboard-report .panel-card-body,
        .dashboard-report .chart-card-body,
        .dashboard-report .stat-card-body {
            padding: 1.1rem;
        }
    }
</style>

<div class="dashboard-report">
        @include('admin.dashboard.partials.filter_drawer')





    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-9">
            <div class="card hero-card h-100">
                <div class="card-body p-4 p-lg-5 hero-content">
                    <div class="row g-4 align-items-start">
                        <div class="col-12">
                            <h2 class="mt-3 mb-2 fw-bold text-white">
                                Laporan stok, aset, penjualan, harga, dan perputaran keuangan dalam satu layar.
                            </h2>
                            <p class="mb-0 text-white" style="opacity:.88;">
                                Dashboard ini menampilkan ringkasan utama yang sudah terhubung ke report aktif. Panel analitik lanjutan ada di section bawah sesuai layout dashboard admin.
                            </p>

                            <div class="hero-grid">
                                <div class="hero-metric">
                                    <div class="hero-metric-label">Total Penjualan Bulan Ini</div>
                                    <h4 class="hero-metric-value">Rp {{ number_format($dashboard['hero']['monthly_gross_transaction_rupiah'] ?? 0, 0, ',', '.') }}</h4>
                                </div>
                                <div class="hero-metric">
                                    <div class="hero-metric-label">Net Cash Bulan Ini</div>
                                    <h4 class="hero-metric-value">Rp {{ number_format($dashboard['hero']['monthly_net_cash_collected_rupiah'] ?? 0, 0, ',', '.') }}</h4>
                                </div>
                                <div class="hero-metric">
                                    <div class="hero-metric-label">Outstanding Bulan Ini</div>
                                    <h4 class="hero-metric-value">Rp {{ number_format($dashboard['hero']['monthly_outstanding_rupiah'] ?? 0, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-3">
            <div class="card profile-card h-100">
                <div class="admin-profile-top">
                    <div class="d-flex align-items-center gap-3">
                        <div class="admin-avatar-wrap">
                            <img src="{{ asset('assets/compiled/jpg/1.jpg') }}" alt="Foto Pengguna">
                        </div>

                        <div class="min-w-0">
                            <div class="mb-1">
                                <div class="profile-name">{{ $appShell['actor_label'] ?? 'Admin' }}</div>
                            </div>
                            <div class="profile-mail">{{ $appShell['user_email'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="summary-strip">
                        <div class="summary-mini">
                            <div class="summary-mini-label">Role</div>
                            <p class="summary-mini-value">Admin</p>
                        </div>
                        <div class="summary-mini">
                            <div class="summary-mini-label">Status</div>
                            <p class="summary-mini-value">Online</p>
                        </div>
                    </div>

                    <div class="mt-4 mb-3">
                        <div class="summary-mini-label">Akun sedang digunakan</div>
                        <p class="section-subtitle mb-0">
                            Pastikan keluar dari akun setelah selesai menggunakan dashboard admin, terutama bila perangkat dipakai bergantian.
                        </p>
                    </div>

                    <form action="{{ route('logout') }}" method="post" class="d-grid">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger admin-main-btn">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Keluar Akun
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-card-body">
                    <div class="stats-icon blue mb-2">
                        <i class="bi-box-seam"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Qty On Hand</div>
                        <div class="stat-value">{{ number_format($dashboard['stats']['total_qty_on_hand'] ?? 0, 0, ',', '.') }} Unit</div>
                        <p class="stat-meta meta-flat">
                            <i class="bi bi-boxes"></i>
                            Snapshot stok saat ini
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-card-body">
                    <div class="stats-icon red mb-2">
                        <i class="bi-buildings"></i>
                    </div>
                    <div>
                        <div class="stat-title">Nilai Persediaan</div>
                        <div class="stat-value">Rp {{ number_format($dashboard['stats']['total_inventory_value_rupiah'] ?? 0, 0, ',', '.') }}</div>
                        <p class="stat-meta meta-flat">
                            <i class="bi bi-buildings"></i>
                            Nilai inventory snapshot saat ini
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-card-body">
                    <div class="stats-icon green mb-2">
                        <i class="bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="stat-title">Uang Masuk Hari Ini</div>
                        <div class="stat-value">Rp {{ number_format($dashboard['stats']['daily_cash_in_rupiah'] ?? 0, 0, ',', '.') }}</div>
                        <p class="stat-meta meta-flat">
                            <i class="bi bi-wallet2"></i>
                            Berdasarkan arus kas transaksi hari ini
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="stat-card-body">
                    <div class="stats-icon blue mb-2">
                        <i class="bi-repeat"></i>
                    </div>
                    <div>
                        <div class="stat-title">Laba Bulan Ini</div>
                        <div class="stat-value">Rp {{ number_format($dashboard['stats']['monthly_cash_operational_profit_rupiah'] ?? 0, 0, ',', '.') }}</div>
                        <p class="stat-meta meta-flat">
                            <i class="bi bi-graph-up-arrow"></i>
                            Ringkasan laba periode berjalan
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12">
            <div class="card h-100">
                <div class="panel-card-body">
                    <div class="card-head">
                        <div>
                            <h5 class="section-title">Posisi Keuangan Bulan Ini</h5>
                            <p class="section-subtitle">Ringkasan keuangan yang sudah terhubung ke data report aktif.</p>
                        </div>
                        <span class="badge-soft bg-soft-info">
                            <i class="bi bi-bank"></i>
                            Live
                        </span>
                    </div>

                    <div class="finance-grid">
                        <div class="finance-box">
                            <div class="finance-label">Kas Masuk Bulan Ini</div>
                            <div class="finance-value">Rp {{ number_format($dashboard['finance']['monthly_cash_in_rupiah'] ?? 0, 0, ',', '.') }}</div>
                            <p class="finance-note meta-up">
                                <i class="bi bi-arrow-up-right"></i>
                                Arus kas transaksi masuk periode berjalan
                            </p>
                        </div>

                        <div class="finance-box">
                            <div class="finance-label">Kas Keluar Bulan Ini</div>
                            <div class="finance-value">Rp {{ number_format($dashboard['finance']['monthly_cash_out_rupiah'] ?? 0, 0, ',', '.') }}</div>
                            <p class="finance-note meta-down">
                                <i class="bi bi-arrow-down-right"></i>
                                Refund transaksi pada periode berjalan
                            </p>
                        </div>

                        <div class="finance-box">
                            <div class="finance-label">Laba Kas Operasional Bulan Ini</div>
                            <div class="finance-value">Rp {{ number_format($dashboard['finance']['monthly_cash_operational_profit_rupiah'] ?? 0, 0, ',', '.') }}</div>
                            <p class="finance-note meta-flat">
                                <i class="bi bi-arrow-repeat"></i>
                                Laba kas operasional periode berjalan
                            </p>
                        </div>

                        <div class="finance-box">
                            <div class="finance-label">Net Cash Flow Bulan Ini</div>
                            <div class="finance-value">Rp {{ number_format($dashboard['finance']['monthly_net_cash_flow_rupiah'] ?? 0, 0, ',', '.') }}</div>
                            <p class="finance-note meta-up">
                                <i class="bi bi-graph-up-arrow"></i>
                                Selisih kas masuk dan kas keluar periode berjalan
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12">
            <div class="card h-100">
                <div class="panel-card-body">
                    <div class="card-head">
                        <div>
                            <h5 class="section-title">Aktivitas Ledger Periode Ini</h5>
                            <p class="section-subtitle">
                                Konteks aktivitas kas dan stok sebelum dibaca sebagai posisi aktif/current.
                            </p>
                        </div>
                        <span class="badge-soft bg-soft-warning">
                            <i class="bi bi-journal-check"></i>
                            Audit Context
                        </span>
                    </div>

                    @if((bool) ($dashboard['ledger_activity']['is_cash_fully_refunded_period'] ?? false))
                        <div class="helper-note mb-3">
                            Periode ini punya kas masuk Rp {{ number_format($dashboard['ledger_activity']['cash_in_before_refund_rupiah'] ?? 0, 0, ',', '.') }},
                            tetapi refund juga tercatat Rp {{ number_format($dashboard['ledger_activity']['cash_refund_out_rupiah'] ?? 0, 0, ',', '.') }}.
                            Karena itu net cash dan current sales bisa tampil 0 meskipun aktivitas ledger pernah terjadi.
                        </div>
                    @endif

                    <div class="finance-grid">
                        <div class="finance-box">
                            <div class="finance-label">Kas Masuk Sebelum Refund</div>
                            <div class="finance-value">Rp {{ number_format($dashboard['ledger_activity']['cash_in_before_refund_rupiah'] ?? 0, 0, ',', '.') }}</div>
                            <p class="finance-note meta-up">
                                <i class="bi bi-arrow-up-right"></i>
                                Gross kas masuk dari ledger periode aktif
                            </p>
                        </div>

                        <div class="finance-box">
                            <div class="finance-label">Refund Keluar Periode Ini</div>
                            <div class="finance-value">Rp {{ number_format($dashboard['ledger_activity']['cash_refund_out_rupiah'] ?? 0, 0, ',', '.') }}</div>
                            <p class="finance-note meta-down">
                                <i class="bi bi-arrow-down-right"></i>
                                Kas keluar karena refund periode aktif
                            </p>
                        </div>

                        <div class="finance-box">
                            <div class="finance-label">Qty Keluar Sebelum Reversal</div>
                            <div class="finance-value">{{ number_format($dashboard['ledger_activity']['stock_out_qty_before_reversal'] ?? 0, 0, ',', '.') }} Unit</div>
                            <p class="finance-note meta-flat">
                                <i class="bi bi-box-arrow-up"></i>
                                Stok keluar menurut inventory movement periode aktif
                            </p>
                        </div>

                        <div class="finance-box">
                            <div class="finance-label">Net Qty Setelah Reversal</div>
                            <div class="finance-value">{{ number_format($dashboard['ledger_activity']['net_stock_out_qty'] ?? 0, 0, ',', '.') }} Unit</div>
                            <p class="finance-note meta-flat">
                                <i class="bi bi-arrow-repeat"></i>
                                Qty keluar dikurangi reversal/refund stok
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="panel-card-body">
                    <div class="card-head">
                        <div>
                            <h5 class="section-title">Ringkasan Posisi Bulan Ini</h5>
                            <p class="section-subtitle">Bagian ini sudah memakai data report aktif yang paling aman untuk dibaca cepat</p>
                        </div>
                        <span class="badge-soft bg-soft-primary">
                            <i class="bi bi-clipboard-data"></i>
                            Asset Overview
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <div class="asset-list">
                                <div class="asset-item">
                                    <div class="asset-left">
                                        <span class="product-avatar bg-soft-primary"><i class="bi bi-box2-heart"></i></span>
                                        <div>
                                            <div class="asset-title">Persediaan Barang</div>
                                            <p class="asset-subtitle">Nilai inventory snapshot saat ini</p>
                                        </div>
                                    </div>
                                    <p class="asset-value">Rp {{ number_format($dashboard['position']['inventory_value_rupiah'] ?? 0, 0, ',', '.') }}</p>
                                </div>

                                <div class="asset-item">
                                    <div class="asset-left">
                                        <span class="product-avatar bg-soft-success"><i class="bi bi-cash-coin"></i></span>
                                        <div>
                                            <div class="asset-title">Outstanding Transaksi</div>
                                            <p class="asset-subtitle">Sisa tagihan transaksi bulan ini</p>
                                        </div>
                                    </div>
                                    <p class="asset-value">Rp {{ number_format($dashboard['position']['transaction_outstanding_rupiah'] ?? 0, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="asset-list">
                                <div class="asset-item">
                                    <div class="asset-left">
                                        <span class="product-avatar bg-soft-warning"><i class="bi bi-tools"></i></span>
                                        <div>
                                            <div class="asset-title">Outstanding Supplier</div>
                                            <p class="asset-subtitle">Hutang supplier bulan ini</p>
                                        </div>
                                    </div>
                                    <p class="asset-value">Rp {{ number_format($dashboard['position']['supplier_outstanding_rupiah'] ?? 0, 0, ',', '.') }}</p>
                                </div>

                                <div class="asset-item">
                                    <div class="asset-left">
                                        <span class="product-avatar bg-soft-info"><i class="bi bi-truck"></i></span>
                                        <div>
                                            <div class="asset-title">Hutang Karyawan</div>
                                            <p class="asset-subtitle">Sisa hutang karyawan bulan ini</p>
                                        </div>
                                    </div>
                                    <p class="asset-value">Rp {{ number_format($dashboard['position']['employee_debt_remaining_rupiah'] ?? 0, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="asset-list">
                                <div class="asset-item">
                                    <div class="asset-left">
                                        <span class="product-avatar bg-soft-danger"><i class="bi bi-receipt-cutoff"></i></span>
                                        <div>
                                            <div class="asset-title">Refund Bulan Ini</div>
                                            <p class="asset-subtitle">Refund transaksi yang sudah terjadi</p>
                                        </div>
                                    </div>
                                    <p class="asset-value">Rp {{ number_format($dashboard['position']['monthly_refunded_rupiah'] ?? 0, 0, ',', '.') }}</p>
                                </div>

                                <div class="asset-item">
                                    <div class="asset-left">
                                        <span class="product-avatar bg-soft-primary"><i class="bi bi-pie-chart"></i></span>
                                        <div>
                                            <div class="asset-title">Biaya Operasional</div>
                                            <p class="asset-subtitle">Biaya operasional bulan ini</p>
                                        </div>
                                    </div>
                                    <p class="asset-value">Rp {{ number_format($dashboard['position']['monthly_operational_expense_rupiah'] ?? 0, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="panel-card-body">
                    <div class="card-head">
                        <div>
                            <h5 class="section-title">Barang Paling Laku</h5>
                            <p class="section-subtitle">Produk dengan qty terjual dan omzet tertinggi pada periode berjalan</p>
                        </div>
                        <span class="badge-soft bg-soft-success">
                            <i class="bi bi-bar-chart-line"></i>
                            Live
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Terjual</th>
                                    <th>Omzet</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($dashboard['top_selling_rows'] ?? []) === 0)
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Belum ada data penjualan produk pada periode ini.
                                        </td>
                                    </tr>
                                @else
                                    @foreach (($dashboard['top_selling_rows'] ?? []) as $row)
                                        <tr>
                                            <td>
                                                <div class="product-inline">
                                                    <span class="product-avatar bg-soft-primary">{{ $loop->iteration }}</span>
                                                    <div>
                                                        <div class="product-name">{{ $row['nama_barang'] }}</div>
                                                        <p class="product-sku">{{ $row['kode_barang'] ?? 'Tanpa kode barang' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ number_format($row['sold_qty'], 0, ',', '.') }} Unit</td>
                                            <td>Rp {{ number_format($row['gross_revenue_rupiah'], 0, ',', '.') }}</td>
                                            <td>
                                                <span class="badge-soft bg-soft-success">
                                                    <i class="bi bi-bar-chart-line"></i>
                                                    Live
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="analytics-stage-card h-100">
                <div class="analytics-stage-head">
                    <div>
                        <h6 class="section-title mb-1">Top Produk Terjual Bulan Ini</h6>
                        <p class="analytics-stage-range" data-dashboard-analytics-target="top-selling-range">
                            Range:
                            {{ \App\Support\ViewDateFormatter::display($dashboard['analytics']['charts']['top_selling_bar']['range']['date_from'] ?? null) }}
                            s.d.
                            {{ \App\Support\ViewDateFormatter::display($dashboard['analytics']['charts']['top_selling_bar']['range']['date_to'] ?? null) }}
                        </p>
                    </div>
                    <span class="badge-soft bg-soft-warning" data-dashboard-analytics-target="top-selling-badge">
                        <i class="bi bi-bar-chart-line"></i>
                        {{ count($dashboard['analytics']['charts']['top_selling_bar']['categories'] ?? []) }} Produk
                    </span>
                </div>

                <div class="chart-shell chart-placeholder">
                    <div
                        class="chart-canvas-shell"
                        id="admin-chart-top-selling-bar"
                        data-chart-key="top_selling_bar"
                    ></div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="analytics-stage-card h-100">
                <div class="analytics-stage-head">
                    <div>
                        <h6 class="section-title mb-1">Status Stok Saat Ini</h6>
                        <p class="analytics-stage-range" data-dashboard-analytics-target="stock-range">
                            Snapshot stok pada {{ \App\Support\ViewDateFormatter::display($dashboard['analytics']['charts']['stock_status_donut']['snapshot_date'] ?? null) }}
                        </p>
                    </div>
                    <span class="badge-soft bg-soft-success" data-dashboard-analytics-target="stock-badge">
                        <i class="bi bi-pie-chart"></i>
                        {{ number_format((int) ($dashboard['analytics']['charts']['stock_status_donut']['total_value'] ?? 0), 0, ',', '.') }} Produk
                    </span>
                </div>

                <div class="chart-shell chart-placeholder">
                    <div
                        class="chart-canvas-shell"
                        id="admin-chart-stock-status-donut"
                        data-chart-key="stock_status_donut"
                    ></div>
                </div>

                <div class="mt-3 d-grid gap-2" data-dashboard-analytics-target="stock-segments">
                    @foreach ($dashboardStockStatusSegments as $segment)
                        <div class="stock-status-row {{ $segment['severity_class'] }} d-flex justify-content-between align-items-center border rounded px-3 py-2">
                            <span class="stock-status-label fw-semibold">
                                <span class="stock-status-dot" aria-hidden="true"></span>
                                {{ $segment['label'] }}
                            </span>
                            <span class="stock-status-value badge-soft {{ $segment['badge_class'] }}">{{ $segment['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="panel-card-body">
                    <div class="card-head">
                        <div>
                            <h5 class="section-title">Prioritas Restok</h5>
                            <p class="section-subtitle">Produk yang perlu diperhatikan lebih dulu berdasarkan batas mulai restok dan batas stok kritis.</p>
                        </div>
                        <span class="badge-soft bg-soft-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Live
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Qty Saat Ini</th>
                                    <th>Mulai Restok</th>
                                    <th>Batas Kritis</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($dashboard['restock_priority_rows'] ?? []) === 0)
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Belum ada produk yang masuk prioritas restok pada snapshot saat ini.
                                        </td>
                                    </tr>
                                @else
                                    @foreach (($dashboard['restock_priority_rows'] ?? []) as $row)
                                        <tr class="restock-priority-row {{ ($row['status'] ?? null) === 'critical' ? 'is-critical' : 'is-warning' }}">
                                            <td>
                                                <div class="product-inline">
                                                    <span class="product-avatar {{ ($row['status'] ?? null) === 'critical' ? 'bg-soft-danger' : 'bg-soft-warning' }}">
                                                        <i class="bi {{ ($row['status'] ?? null) === 'critical' ? 'bi-exclamation-triangle' : 'bi-arrow-repeat' }}"></i>
                                                    </span>
                                                    <div>
                                                        <div class="product-name">{{ $row['nama_barang'] }}</div>
                                                        <p class="product-sku">{{ $row['kode_barang'] ?? 'Tanpa kode barang' }}</p>
                                                        <a
                                                            href="{{ route('admin.products.show', ['productId' => $row['product_id']]) }}"
                                                            class="btn btn-sm btn-light-primary mt-2"
                                                        >
                                                            Lihat Detail
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ number_format($row['current_qty_on_hand'] ?? 0, 0, ',', '.') }} Unit</td>
                                            <td>{{ number_format($row['reorder_point_qty'] ?? 0, 0, ',', '.') }}</td>
                                            <td>{{ number_format($row['critical_threshold_qty'] ?? 0, 0, ',', '.') }}</td>
                                            <td>
                                                <span class="badge-soft {{ ($row['status'] ?? null) === 'critical' ? 'bg-soft-danger' : 'bg-soft-warning' }}">
                                                    <i class="bi {{ ($row['status'] ?? null) === 'critical' ? 'bi-exclamation-octagon' : 'bi-arrow-repeat' }}"></i>
                                                    {{ $row['status_label'] ?? '-' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="analytics-stage-card h-100">
                <div class="analytics-stage-head">
                    <div>
                        <h6 class="section-title mb-1">Laba Operasional Bulan Ini</h6>
                        <p class="analytics-stage-range" data-dashboard-analytics-target="operational-range">
                            Range:
                            {{ \App\Support\ViewDateFormatter::display($dashboard['analytics']['charts']['operational_performance_bar']['range']['date_from'] ?? null) }}
                            s.d.
                            {{ \App\Support\ViewDateFormatter::display($dashboard['analytics']['charts']['operational_performance_bar']['range']['date_to'] ?? null) }}
                        </p>
                    </div>
                    <span class="badge-soft bg-soft-info" data-dashboard-analytics-target="operational-badge">
                        <i class="bi bi-activity"></i>
                        {{ count($dashboard['analytics']['charts']['operational_performance_bar']['labels'] ?? []) }} Titik
                    </span>
                </div>

                <div class="chart-shell chart-placeholder">
                    <div
                        class="chart-canvas-shell"
                        id="admin-chart-operational-performance"
                        data-chart-key="operational_performance_bar"
                    ></div>
                </div>
                <div
                    class="finance-grid mt-3"
                    data-dashboard-analytics-target="operational-summary"
                >
                    <div class="finance-box">
                        <div class="finance-label">Laba Operasional</div>
                        <div class="finance-value">Rp {{ number_format((int) ($dashboard['analytics']['charts']['operational_performance_bar']['summary']['total_operational_profit_rupiah'] ?? 0), 0, ',', '.') }}</div>
                        <p class="finance-note meta-up">
                            <i class="bi bi-graph-up-arrow"></i>
                            Tren laba dari payload dashboard
                        </p>
                    </div>

                    <div class="finance-box">
                        <div class="finance-label">Biaya Operasional</div>
                        <div class="finance-value">Rp {{ number_format((int) ($dashboard['analytics']['charts']['operational_performance_bar']['summary']['total_operational_expense_rupiah'] ?? 0), 0, ',', '.') }}</div>
                        <p class="finance-note meta-flat">
                            <i class="bi bi-cash-stack"></i>
                            Beban periode aktif
                        </p>
                    </div>

                    <div class="finance-box">
                        <div class="finance-label">Refund</div>
                        <div class="finance-value">Rp {{ number_format((int) ($dashboard['analytics']['charts']['operational_performance_bar']['summary']['total_refund_rupiah'] ?? 0), 0, ',', '.') }}</div>
                        <p class="finance-note meta-down">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Kas keluar refund
                        </p>
                    </div>

                    <div class="finance-box">
                        <div class="finance-label">Potensi Kembalian</div>
                        <div class="finance-value">Rp {{ number_format((int) ($dashboard['analytics']['charts']['operational_performance_bar']['summary']['total_potential_change_rupiah'] ?? 0), 0, ',', '.') }}</div>
                        <p class="finance-note meta-flat">
                            <i class="bi bi-coin"></i>
                            Estimasi pecahan cash
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.dashboard.partials.cash-change-denominations', [
            'cashChangeDenominations' => $dashboard['analytics']['cash_change_denominations'] ?? [],
            'cashChangeRange' => $dashboard['analytics']['charts']['operational_performance_bar']['range'] ?? [],
        ])

    </section>

    <script type="application/json" id="admin-dashboard-analytics-payload" data-url="{{ route('admin.dashboard.analytics', ['month' => $dashboard['period']['active_month'] ?? now()->format('Y-m')]) }}">{}</script>


</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/extensions/apexcharts/apexcharts.min.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/admin/dashboard-analytics.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
