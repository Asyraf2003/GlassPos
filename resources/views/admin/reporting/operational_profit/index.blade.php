@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Ringkasan Kas Operasional')
@section('heading', 'Ringkasan Kas Operasional')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'operational-profit-report-filter-form',
    'action' => route('admin.reports.operational_profit.index'),
    'resetUrl' => route('admin.reports.operational_profit.index'),
    'basisDateLabel' => 'Tanggal kejadian komponen kas dan biaya',
    'useReportPeriodContext' => true,
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => route('admin.reports.operational_profit.export_excel', request()->query()),
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.operational_profit.export_pdf', request()->query()),
            'class' => 'btn btn-outline-danger text-nowrap',
        ],
    ],
])

<div class="mb-3">
    <h5 class="mb-1">Ringkasan Utama</h5>
    <p class="small text-muted mb-0">Ringkasan uang masuk dan keluar selama periode terkait. Angka akhir menunjukkan sisa kas setelah refund, modal produk, biaya, gaji, dan kasbon karyawan.</p>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Uang Diterima</div>
            <div class="fs-5 fw-bold text-success">Rp {{ number_format($row['cash_in_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Uang Dikembalikan</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($row['refunded_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Biaya Barang Luar</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($row['external_purchase_cost_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Modal Barang Stok</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($row['store_stock_cogs_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Total Modal Produk</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($row['product_purchase_cost_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Biaya Operasional</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($row['operational_expense_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Gaji Dibayar</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($row['payroll_disbursement_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Kasbon/Hutang Karyawan</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($row['employee_debt_cash_out_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Sisa Kas Operasional</div>
            <div class="fs-3 fw-bold {{ ($row['cash_operational_profit_rupiah'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                Rp {{ number_format($row['cash_operational_profit_rupiah'] ?? 0, 0, ',', '.') }}
            </div>
        </div></div>
    </div>
</div>

@endsection
