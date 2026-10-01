@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Ringkasan Toko')
@section('heading', 'Ringkasan Toko')
@section('heading_title_class', 'dashboard-heading-title')
@section('heading_actions')
    <span class="dashboard-heading-period">{{ \App\Support\ViewDateFormatter::range($dashboard['period']['date_from'] ?? null, $dashboard['period']['date_to'] ?? null) }}</span>
    <button type="button" id="admin-dashboard-filter-open-filter" class="btn btn-outline-primary dashboard-filter-button" aria-controls="admin-dashboard-filter-drawer" aria-expanded="false">
        <i class="bi bi-funnel" aria-hidden="true"></i> Filter &amp; Cetak
    </button>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/apexcharts/apexcharts.css') }}?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="{{ asset('assets/static/css/admin-dashboard.css') }}?v={{ config('app.asset_version') }}">
@endpush

@section('content')
<div id="admin-dashboard" class="dashboard-report" data-admin-dashboard>
    @include('admin.dashboard.partials.filter_drawer')
    <section aria-label="Indikator utama periode terpilih">
        <dl class="dashboard-kpis">
            <div class="dashboard-metric">
                <dt>Total Nilai Nota</dt>
                <dd>Rp {{ number_format($dashboard['hero']['monthly_gross_transaction_rupiah'] ?? 0, 0, ',', '.') }}
                    <span class="dashboard-caption">Nota bertanggal dalam periode</span>
                </dd>
            </div>
            <div class="dashboard-metric">
                <dt>Uang Bersih Diterima</dt>
                <dd>Rp {{ number_format($dashboard['hero']['monthly_net_cash_collected_rupiah'] ?? 0, 0, ',', '.') }}
                    <span class="dashboard-caption">Pembayaran bersih untuk nota periode ini</span>
                </dd>
            </div>
            <div class="dashboard-metric">
                <dt>Sisa Tagihan</dt>
                <dd>Rp {{ number_format($dashboard['hero']['monthly_outstanding_rupiah'] ?? 0, 0, ',', '.') }}
                    <span class="dashboard-caption">Sisa tagihan nota periode ini</span>
                </dd>
            </div>
            <div class="dashboard-metric">
                <dt>Sisa Kas Operasional</dt>
                <dd>Rp {{ number_format($dashboard['finance']['monthly_cash_operational_profit_rupiah'] ?? 0, 0, ',', '.') }}
                    <span class="dashboard-caption">Setelah refund, modal, biaya, gaji & kasbon</span>
                </dd>
            </div>
        </dl>
    </section>

    <div class="dashboard-grid dashboard-attention">
        @include('admin.dashboard.partials.restock')
        @include('admin.dashboard.partials.stock')
    </div>
    @include('admin.dashboard.partials.finance')
    @include('admin.dashboard.partials.finance-insights')
    <div class="dashboard-grid dashboard-analysis">
        @include('admin.dashboard.partials.analytics')
        @include('admin.dashboard.partials.top-selling')
    </div>
    @include('admin.dashboard.partials.audit')
    <script type="application/json" id="admin-dashboard-analytics-payload" data-url="{{ route('admin.dashboard.analytics', ['month' => $dashboard['period']['active_month'] ?? now()->format('Y-m')]) }}">{}</script>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/extensions/apexcharts/apexcharts.min.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/admin/dashboard-analytics.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/admin/dashboard-finance.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/admin/dashboard-drawer.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
