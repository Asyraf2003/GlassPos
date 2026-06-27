@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Biaya Operasional')
@section('heading', 'Biaya Operasional')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'operational-expense-report-filter-form',
    'action' => route('admin.reports.operational_expense.index'),
    'resetUrl' => route('admin.reports.operational_expense.index'),
    'rangeLabelText' => 'Rentang biaya aktif',
    'basisDateLabel' => 'Tanggal biaya operasional',
    'basisDateNote' => 'Mode harian hanya menghitung biaya pada tanggal pengeluaran tersebut. Data tidak dibawa ke hari berikutnya.',
    'supportsCustomRange' => true,
    'noteText' => 'Report ini hanya membaca expense aktif dan mengabaikan row yang sudah soft delete.',
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => route('admin.reports.operational_expense.export_excel', request()->query()),
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.operational_expense.export_pdf', request()->query()),
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
                <div class="text-muted small">Jumlah Catatan</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Biaya</div>
                <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['total_amount_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Kategori Terbesar</div>
                <div class="fs-5 fw-bold">{{ $summary['top_category_name'] ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Nilai Kategori</div>
                <div class="fs-5 fw-bold">Rp {{ number_format($summary['top_category_amount_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Rata-rata Harian</div>
                <div class="fs-5 fw-bold">Rp {{ number_format($summary['average_daily_rupiah'] ?? 0, 0, ',', '.') }}</div>
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
                    <div class="text-muted small">Tanggal Biaya</div>
                    <div class="fw-semibold mb-3">{{ $row['period_label'] }}</div>

                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Jumlah Catatan</span>
                        <span class="fw-semibold">{{ number_format($row['total_rows'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Total Biaya</span>
                        <span class="fw-semibold text-danger">Rp {{ number_format($row['total_amount_rupiah'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted">
                    Belum ada biaya pada periode ini.
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="row g-3">
    @forelse ($categoryRows as $row)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Kategori Biaya</div>
                    <div class="fw-semibold mb-3">{{ $row['category_name'] }}</div>

                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Jumlah Catatan</span>
                        <span class="fw-semibold">{{ number_format($row['total_rows'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Total Biaya</span>
                        <span class="fw-semibold text-danger">Rp {{ number_format($row['total_amount_rupiah'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted">
                    Belum ada kategori pada periode ini.
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
