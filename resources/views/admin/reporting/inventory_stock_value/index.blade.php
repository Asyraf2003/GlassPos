@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Stok dan Nilai Persediaan')
@section('heading', 'Stok dan Nilai Persediaan')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'inventory-stock-value-report-filter-form',
    'action' => route('admin.reports.inventory_stock_value.index'),
    'resetUrl' => route('admin.reports.inventory_stock_value.index'),
    'basisDateLabel' => 'Tanggal mutasi movement',
    'useReportPeriodContext' => true,
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
            <div class="text-muted small">Produk Tercatat di Stok</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['snapshot_product_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Produk Bergerak</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['movement_product_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Total Stok Tersedia</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['total_qty_on_hand'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nilai Modal Stok</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['total_inventory_value_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Barang Masuk dari Supplier</div>
            <div class="fs-5 fw-bold text-success">{{ number_format($summary['period_supply_in_qty'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Barang Keluar Terjual/Dipakai</div>
            <div class="fs-5 fw-bold text-danger">{{ number_format($summary['period_sale_out_qty'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Barang Balik dari Refund</div>
            <div class="fs-5 fw-bold text-success">{{ number_format($summary['period_refund_reversal_qty'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Barang Koreksi/Revisi</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['period_revision_correction_qty'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Perubahan Stok Bersih</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['period_net_qty_delta'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Perubahan Modal Stok Bersih</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['period_net_cost_delta_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12">
        <div class="small fw-semibold text-info">Validasi Sistem</div>
        <div class="small text-muted">Bagian ini mengecek apakah ringkasan stok saat ini cocok dengan riwayat keluar-masuk barang. Nilai sehat untuk selisih stok dan nilai adalah 0.</div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-warning"><div class="card-body">
            <div class="text-muted small">Nilai Pembanding Avg x Qty</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['total_inventory_value_by_average_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-info"><div class="card-body">
            <div class="text-muted small">Selisih Pembulatan Modal</div>
            <div class="fs-5 fw-bold text-muted">Rp {{ number_format($summary['total_rounding_residual_rupiah'] ?? 0, 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">Selisih kecil akibat pembulatan modal rata-rata. Nilai stok resmi tetap memakai Nilai Modal Stok.</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card {{ \App\Support\InventoryStockValueValidationStatusPresenter::cardClass($summary, 'total_ledger_qty_diff') }}"><div class="card-body">
            <div class="text-muted small">Selisih Stok vs Riwayat</div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="fs-5 fw-bold {{ \App\Support\InventoryStockValueValidationStatusPresenter::textClass($summary, 'total_ledger_qty_diff') }}">{{ number_format($summary['total_ledger_qty_diff'] ?? 0, 0, ',', '.') }}</div>
                <span class="badge {{ \App\Support\InventoryStockValueValidationStatusPresenter::badgeClass($summary, 'total_ledger_qty_diff') }}">{{ \App\Support\InventoryStockValueValidationStatusPresenter::badgeText($summary, 'total_ledger_qty_diff') }}</span>
            </div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card {{ \App\Support\InventoryStockValueValidationStatusPresenter::cardClass($summary, 'total_ledger_value_diff_rupiah') }}"><div class="card-body">
            <div class="text-muted small">Selisih Nilai vs Riwayat</div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="fs-5 fw-bold {{ \App\Support\InventoryStockValueValidationStatusPresenter::textClass($summary, 'total_ledger_value_diff_rupiah') }}">Rp {{ number_format($summary['total_ledger_value_diff_rupiah'] ?? 0, 0, ',', '.') }}</div>
                <span class="badge {{ \App\Support\InventoryStockValueValidationStatusPresenter::badgeClass($summary, 'total_ledger_value_diff_rupiah') }}">{{ \App\Support\InventoryStockValueValidationStatusPresenter::badgeText($summary, 'total_ledger_value_diff_rupiah') }}</span>
            </div>
        </div></div>
    </div>

</div>

<div class="mb-3">
    <h5 class="mb-2">Rincian Ringkas</h5>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Produk Tercatat di Stok</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['snapshot_product_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Produk Bergerak</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['movement_product_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Perubahan Stok Bersih</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['period_net_qty_delta'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Perubahan Modal Stok Bersih</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['period_net_cost_delta_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>
@endsection
