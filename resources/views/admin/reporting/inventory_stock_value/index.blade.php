@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Stok dan Nilai Persediaan')
@section('heading', 'Stok dan Nilai Persediaan')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'inventory-stock-value-report-filter-form',
    'action' => route('admin.reports.inventory_stock_value.index'),
    'resetUrl' => route('admin.reports.inventory_stock_value.index'),
    'rangeLabelText' => 'Rentang movement aktif',
    'basisDateLabel' => 'Tanggal mutasi movement',
    'supportsCustomRange' => true,
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => route('admin.reports.inventory_stock_value.export_excel', request()->query()),
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.inventory_stock_value.export_pdf', request()->query()),
            'class' => 'btn btn-outline-danger text-nowrap',
        ],
    ],
])
<div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
    <div aria-hidden="true">🔔</div>
    <div>
        <div class="fw-semibold">Notifikasi stok belum aktif.</div>
        <div class="small mb-0">Template UI reminder stok masih hardcoded; pengiriman notifikasi otomatis belum diaktifkan pada flow produksi.</div>
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-1">Ringkasan Utama</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Produk Snapshot</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['snapshot_product_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Produk Bermutasi</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['movement_product_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Qty Tersedia</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['total_qty_on_hand'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nilai Persediaan</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['total_inventory_value_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Qty Masuk Pembelian</div>
            <div class="fs-5 fw-bold text-success">{{ number_format($summary['period_supply_in_qty'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Qty Keluar Penjualan</div>
            <div class="fs-5 fw-bold text-danger">{{ number_format($summary['period_sale_out_qty'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Qty Balik Refund/Reversal</div>
            <div class="fs-5 fw-bold text-success">{{ number_format($summary['period_refund_reversal_qty'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Qty Koreksi/Revisi</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['period_revision_correction_qty'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Selisih Qty Periode</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['period_net_qty_delta'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Selisih Nilai Pokok Periode</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['period_net_cost_delta_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-warning"><div class="card-body">
            <div class="text-muted small">Nilai Berdasar Avg x Qty</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['total_inventory_value_by_average_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-warning"><div class="card-body">
            <div class="text-muted small">Residual Pembulatan HPP</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['total_rounding_residual_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-info"><div class="card-body">
            <div class="text-muted small">Selisih Qty Ledger</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['total_ledger_qty_diff'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-info"><div class="card-body">
            <div class="text-muted small">Selisih Nilai Ledger</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['total_ledger_value_diff_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

</div>

<div class="mb-3">
    <h5 class="mb-2">Rincian Ringkas</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Produk Snapshot</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['snapshot_product_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Produk Bermutasi</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['movement_product_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Selisih Qty Periode</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['period_net_qty_delta'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Selisih Nilai Pokok Periode</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['period_net_cost_delta_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>
@endsection
