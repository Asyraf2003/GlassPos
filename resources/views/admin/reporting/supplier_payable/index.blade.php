@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Hutang Pemasok')
@section('heading', 'Hutang Pemasok')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'supplier-payable-report-filter-form',
    'action' => route('admin.reports.supplier_payable.index'),
    'resetUrl' => route('admin.reports.supplier_payable.index'),
    'rangeLabelText' => 'Rentang pengiriman aktif',
    'basisDateLabel' => 'Tanggal pengiriman invoice',
    'basisDateNote' => 'Data faktur masuk dihitung dari tanggal pengiriman.',
    'noteText' => 'Status jatuh tempo dievaluasi terhadap tanggal referensi ' . \App\Support\ViewDateFormatter::display($filters['reference_date'] ?? null) . '.',
    'supportsCustomRange' => true,
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => route('admin.reports.supplier_payable.export_excel', request()->query()),
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.supplier_payable.export_pdf', request()->query()),
            'class' => 'btn btn-outline-danger text-nowrap',
        ],
    ],
])
<div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
    <div aria-hidden="true">🔔</div>
    <div>
        <div class="fw-semibold">Notifikasi hutang faktur belum aktif.</div>
        <div class="small mb-0">Template UI reminder hutang faktur masih hardcoded; pengiriman notifikasi otomatis belum diaktifkan pada flow produksi.</div>
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-1">Ringkasan Utama</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Faktur</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Tagihan</div>
                <div class="fs-5 fw-bold">Rp {{ number_format($summary['grand_total_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Dibayar</div>
                <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['total_paid_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Outstanding</div>
                <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['outstanding_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Belum Jatuh Tempo</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['not_due_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Jatuh Tempo Hari Ini</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['due_today_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Lewat Jatuh Tempo</div>
                <div class="fs-5 fw-bold text-danger">{{ number_format($summary['overdue_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Sisa Hutang Lewat Tempo</div>
                <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['overdue_outstanding_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Belum Lunas</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['open_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Lunas</div>
                <div class="fs-5 fw-bold text-success">{{ number_format($summary['settled_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
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
                    <div class="text-muted small">Tanggal Kirim</div>
                    <div class="fw-semibold mb-3">{{ $row['period_label'] }}</div>

                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Total Faktur</span>
                        <span class="fw-semibold">{{ number_format($row['total_rows'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Sisa Hutang</span>
                        <span class="fw-semibold text-danger">Rp {{ number_format($row['outstanding_rupiah'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted">
                    Belum ada faktur pada periode ini.
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="row g-3">
    @forelse ($supplierRows as $row)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Pemasok</div>
                    <div class="fw-semibold mb-3">{{ $row['supplier_name'] ?? $row['supplier_id'] }}</div>

                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Total Faktur</span>
                        <span class="fw-semibold">{{ number_format($row['total_rows'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Sisa Hutang</span>
                        <span class="fw-semibold text-danger">Rp {{ number_format($row['outstanding_rupiah'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted">
                    Belum ada pemasok pada periode ini.
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
