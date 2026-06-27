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
    'basisDateNote' => 'Mode harian hanya menghitung nota pada tanggal transaksi tersebut.',
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
    <div class="text-muted small">
        Laporan ini merangkum jumlah nota, nilai transaksi, pembayaran, refund,
        dan sisa tagihan pada periode yang dipilih.
    </div>
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
    <h5 class="mb-2">Catatan Laporan</h5>
    <div class="text-muted">
        Gunakan ringkasan ini untuk membaca kondisi transaksi dengan cepat.
        Detail lengkap tersedia di Excel.
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-2">Rincian Ringkas</h5>
    <div class="text-muted small">
        Halaman ini menampilkan transaksi per tanggal dan customer secara
        ringkas. Nomor nota, status per nota, dan baris detail tersedia di
        Excel.
    </div>
</div>

<div class="row g-3 mb-4">
    @forelse ($periodRows as $row)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Tanggal Transaksi</div>
                    <div class="fw-semibold mb-3">{{ $row['period_label'] }}</div>

                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Jumlah Nota</span>
                        <span class="fw-semibold">{{ number_format($row['total_rows'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Nilai Transaksi</span>
                        <span class="fw-semibold">Rp {{ number_format($row['gross_transaction_rupiah'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Sisa Tagihan</span>
                        <span class="fw-semibold text-danger">Rp {{ number_format($row['outstanding_rupiah'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted">
                    Belum ada transaksi pada periode ini.
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="row g-3">
    @forelse ($customerRows as $row)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Customer</div>
                    <div class="fw-semibold mb-3">{{ $row['customer_name'] }}</div>

                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Jumlah Nota</span>
                        <span class="fw-semibold">{{ number_format($row['total_rows'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Nilai Transaksi</span>
                        <span class="fw-semibold">Rp {{ number_format($row['gross_transaction_rupiah'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Sisa Refund Due</span>
                        <span class="fw-semibold text-warning">Rp {{ number_format($row['remaining_refund_due_rupiah'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted">
                    Belum ada customer pada periode ini.
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
