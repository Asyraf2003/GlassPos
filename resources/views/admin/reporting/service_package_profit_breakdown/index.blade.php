@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Laba Paket Service')
@section('heading', 'Laba Paket Service')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'service-package-profit-breakdown-filter-form',
    'action' => route('admin.reports.service_package_profit_breakdown.index'),
    'resetUrl' => route('admin.reports.service_package_profit_breakdown.index'),
    'rangeLabelText' => 'Rentang transaksi aktif',
    'basisDateLabel' => 'Tanggal transaksi nota',
    'supportsCustomRange' => true,
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => route('admin.reports.service_package_profit_breakdown.export_excel', request()->query()),
            'class' => 'btn btn-outline-success text-nowrap',
        ],
    ],
])

<div class="mb-3">
    <h5 class="mb-1">Ringkasan Utama</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Jumlah Paket</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['total_packages'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nilai Paket Terjual</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['package_sold_amount_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Total Sparepart</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['parts_total_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">HPP Sparepart</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['sparepart_cogs_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-2">Rincian Ringkas</h5>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Margin Sparepart</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['sparepart_margin_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Bagian Jasa 20%</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['service_fee_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Keuntungan Toko dari Jasa 80%</div>
            <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['package_profit_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Total Nilai Jasa</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['total_service_component_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Refund Komponen Produk</div>
            <div class="fs-5 fw-bold text-warning">Rp {{ number_format($summary['refunded_product_component_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Refund Komponen Service</div>
            <div class="fs-5 fw-bold text-warning">Rp {{ number_format($summary['refunded_service_component_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Laba Kotor Paket untuk Toko</div>
            <div class="small text-muted mb-1">Margin sparepart + keuntungan toko dari jasa 80%</div>
            <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['total_package_gross_profit_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>
@endsection
