@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Arus Kas Transaksi')
@section('heading', 'Arus Kas Transaksi')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'transaction-cash-ledger-filter-form',
    'action' => route('admin.reports.transaction_cash_ledger.index'),
    'resetUrl' => route('admin.reports.transaction_cash_ledger.index'),
    'rangeLabelText' => 'Rentang kejadian aktif',
    'basisDateLabel' => 'Tanggal kejadian kas',
    'supportsCustomRange' => true,
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => route('admin.reports.transaction_cash_ledger.export_excel', request()->query()),
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.transaction_cash_ledger.export_pdf', request()->query()),
            'class' => 'btn btn-outline-danger text-nowrap',
        ],
    ],
])

<div class="mb-3">
    <h5 class="mb-1">Ringkasan Utama</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Kejadian</div>
                <div class="fs-4 fw-bold">{{ number_format($summary['total_events'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Kas Masuk</div>
                <div class="fs-4 fw-bold text-success">Rp {{ number_format($summary['total_cash_in_rupiah'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Tunai Masuk</div>
                <div class="fs-4 fw-bold text-success">Rp {{ number_format($summary['cash_in_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Transfer Masuk</div>
                <div class="fs-4 fw-bold text-success">Rp {{ number_format($summary['transfer_in_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Kas Keluar</div>
                <div class="fs-4 fw-bold text-danger">Rp {{ number_format($summary['total_cash_out_rupiah'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Nilai Bersih</div>
                <div class="fs-4 fw-bold {{ $summary['net_amount_rupiah'] >= 0 ? 'text-primary' : 'text-danger' }}">
                    Rp {{ number_format($summary['net_amount_rupiah'], 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-2">Rincian Ringkas</h5>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Kejadian Kas</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['total_events'], 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Tunai Masuk</div>
            <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['cash_in_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Transfer Masuk</div>
            <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['transfer_in_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nilai Bersih</div>
            <div class="fs-5 fw-bold {{ $summary['net_amount_rupiah'] >= 0 ? 'text-primary' : 'text-danger' }}">
                Rp {{ number_format($summary['net_amount_rupiah'], 0, ',', '.') }}
            </div>
        </div></div>
    </div>
</div>
@endsection
