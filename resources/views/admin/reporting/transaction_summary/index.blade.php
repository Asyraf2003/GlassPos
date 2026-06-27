@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Laporan Transaksi')
@section('heading', 'Laporan Transaksi')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'transaction-report-filter-form',
    'action' => route('admin.reports.transaction_summary.index'),
    'resetUrl' => route('admin.reports.transaction_summary.index'),
    'rangeLabelText' => 'Rentang transaksi aktif',
    'basisDateLabel' => 'Tanggal transaksi nota',
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => $exportExcelUrl,
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.transaction_summary.export_pdf', request()->query()),
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
            <div class="text-muted small">Jumlah Nota</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nilai Bruto Transaksi</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['gross_transaction_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Pembayaran Dialokasikan</div>
            <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['allocated_payment_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Dana Dikembalikan</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['refunded_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Kas Bersih</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['net_cash_collected_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Refund Due</div>
            <div class="fs-5 fw-bold text-warning">Rp {{ number_format($summary['refund_due_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Surplus Refund Paid</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['surplus_refund_paid_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Sisa Refund Due</div>
            <div class="fs-5 fw-bold text-warning">Rp {{ number_format($summary['remaining_refund_due_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Sisa Tagihan</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['outstanding_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nota Lunas</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['settled_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nota Sisa Tagihan</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['outstanding_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-2">Rincian Ringkas</h5>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nota Lunas</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['settled_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nota Sisa Tagihan</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['outstanding_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Sisa Tagihan</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['outstanding_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Sisa Refund Due</div>
            <div class="fs-5 fw-bold text-warning">Rp {{ number_format($summary['remaining_refund_due_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>
@endsection
