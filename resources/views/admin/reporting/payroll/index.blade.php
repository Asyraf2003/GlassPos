@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Laporan Gaji')
@section('heading', 'Laporan Gaji')

@section('content')
<p class="text-muted small">{{ $temporalContext ?? '' }}</p>
@include('admin.reporting.partials.period_filter', [
    'formId' => 'payroll-report-filter-form',
    'action' => route('admin.reports.payroll.index'),
    'resetUrl' => route('admin.reports.payroll.index'),
    'rangeLabelText' => 'Rentang pencairan aktif',
    'basisDateLabel' => 'Tanggal pencairan gaji',
    'supportsCustomRange' => true,
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => route('admin.reports.payroll.export_excel', request()->query()),
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.payroll.export_pdf', request()->query()),
            'class' => 'btn btn-outline-danger text-nowrap',
        ],
    ],
])

<div class="mb-3">
    <h5 class="mb-1">Ringkasan Utama</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Jumlah Pencairan</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Total Nominal</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['total_amount_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Tanggal Terakhir</div>
            <div class="fs-5 fw-bold">{{ \App\Support\ViewDateFormatter::display($summary['latest_disbursement_date'] ?? null) }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Mode Terbesar</div>
            <div class="fs-5 fw-bold">{{ $summary['top_mode_label'] ?? '-' }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Rata-rata Harian</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['average_daily_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-2">Rincian Ringkas</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Jumlah Pencairan</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Total Nominal</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['total_amount_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Mode Terbesar</div>
            <div class="fs-5 fw-bold">{{ $summary['top_mode_label'] ?? '-' }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Rata-rata Harian</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['average_daily_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>
@include('admin.reporting.partials.screen_detail_tables')
@endsection
