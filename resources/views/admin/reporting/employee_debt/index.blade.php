@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Laporan Hutang Karyawan')
@section('heading', 'Laporan Hutang Karyawan')

@section('content')
<p class="text-muted small">{{ $temporalContext ?? '' }}</p>
@include('admin.reporting.partials.period_filter', [
    'formId' => 'employee-debt-report-filter-form',
    'action' => route('admin.reports.employee_debt.index'),
    'resetUrl' => route('admin.reports.employee_debt.index'),
    'rangeLabelText' => 'Aktivitas periode dan posisi akhir',
    'basisDateLabel' => 'Pencatatan hutang / pembayaran; saldo per akhir periode',
    'supportsCustomRange' => true,
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => route('admin.reports.employee_debt.export_excel', request()->query()),
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.employee_debt.export_pdf', request()->query()),
            'class' => 'btn btn-outline-danger text-nowrap',
        ],
    ],
])

<div class="mb-3">
    <h5 class="mb-1">Ringkasan Utama</h5>
</div>

<div class="row g-3 mb-4">
    @foreach ($temporalSummaryRows as $metric)
        <div class="col-12 col-md-6 col-xl-2">
            <div class="card"><div class="card-body">
                <div class="text-muted small">{{ $metric['label'] }}</div>
                <div class="fs-5 fw-bold">Rp {{ number_format($metric['value'], 0, ',', '.') }}</div>
            </div></div>
        </div>
    @endforeach
</div>

<div class="mb-3">
    <h5 class="mb-2">Rincian Ringkas</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Jumlah Data</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Status Lunas</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['paid_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Status Belum Lunas</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['unpaid_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Sisa Hutang</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['total_remaining_balance'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>
@include('admin.reporting.partials.screen_detail_tables')
@endsection
