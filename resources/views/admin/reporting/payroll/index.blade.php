@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Laporan Gaji')
@section('heading', 'Laporan Gaji')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'payroll-report-filter-form',
    'action' => route('admin.reports.payroll.index'),
    'resetUrl' => route('admin.reports.payroll.index'),
    'rangeLabelText' => 'Rentang pencairan aktif',
    'basisDateLabel' => 'Tanggal pencairan gaji',
    'basisDateNote' => 'Mode harian hanya menghitung payroll yang cair pada tanggal tersebut. Payroll yang direversal tidak ikut dihitung.',
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
    @forelse ($periodRows as $row)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Tanggal</div>
                    <div class="fw-semibold mb-3">{{ $row['period_label'] }}</div>

                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Jumlah Pencairan</span>
                        <span class="fw-semibold">{{ number_format($row['total_rows'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Total Pencairan</span>
                        <span class="fw-semibold text-danger">Rp {{ number_format($row['total_amount_rupiah'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted">
                    Belum ada payroll pada periode ini.
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="row g-3">
    @forelse ($modeRows as $row)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Mode Pencairan</div>
                    <div class="fw-semibold mb-3">{{ $row['mode_label'] }}</div>

                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Jumlah Pencairan</span>
                        <span class="fw-semibold">{{ number_format($row['total_rows'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Total Pencairan</span>
                        <span class="fw-semibold text-danger">Rp {{ number_format($row['total_amount_rupiah'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted">
                    Belum ada mode pencairan pada periode ini.
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
