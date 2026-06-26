@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Laporan Hutang Karyawan')
@section('heading', 'Laporan Hutang Karyawan')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'employee-debt-report-filter-form',
    'action' => route('admin.reports.employee_debt.index'),
    'resetUrl' => route('admin.reports.employee_debt.index'),
    'rangeLabelText' => 'Rentang pencatatan aktif',
    'basisDateLabel' => 'Tanggal pencatatan hutang',
    'basisDateNote' => 'Mode harian hanya menghitung hutang yang dicatat pada tanggal tersebut. Data tidak dibawa ke hari berikutnya.',
    'supportsCustomRange' => true,
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => route('admin.reports.employee_debt.export_excel', request()->query()),
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.employee_debt.export_pdf', request()->query()),
            'class' => 'btn btn-outline-danger text-nowrap',
        ],
    ],
])

<div class="mb-3">
    <h5 class="mb-1">Ringkasan Utama</h5>
    <div class="text-muted small">
        Laporan ini merangkum hutang karyawan yang dicatat, sudah dibayar, dan
        sisa yang masih perlu diselesaikan.
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Hutang</div>
                <div class="fs-5 fw-bold">Rp {{ number_format($summary['total_debt'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Sudah Dibayar</div>
                <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['total_paid_amount'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Sisa Hutang</div>
                <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['total_remaining_balance'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Jumlah Data</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Status Lunas</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['paid_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Status Belum Lunas</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['unpaid_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-2">Catatan Laporan</h5>
    <div class="text-muted">
        Gunakan ringkasan ini untuk melihat posisi hutang karyawan dengan cepat.
        Detail lengkap tersedia di Excel.
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-2">Rincian Ringkas</h5>
    <div class="text-muted small">
        Halaman ini menampilkan posisi hutang per tanggal dan status secara
        ringkas. Referensi hutang, employee ID, dan baris detail tersedia di
        Excel.
    </div>
</div>

<div class="row g-3 mb-4">
    @forelse ($periodRows as $row)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Tanggal Catat</div>
                    <div class="fw-semibold mb-3">{{ $row['period_label'] }}</div>

                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Jumlah Data</span>
                        <span class="fw-semibold">{{ number_format($row['total_rows'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Sisa Hutang</span>
                        <span class="fw-semibold text-danger">Rp {{ number_format($row['total_remaining_balance'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted">
                    Belum ada data hutang pada periode ini.
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="row g-3">
    @forelse ($statusRows as $row)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Status Hutang</div>
                    <div class="fw-semibold mb-3">{{ $row['status'] }}</div>

                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Jumlah Data</span>
                        <span class="fw-semibold">{{ number_format($row['total_rows'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Sisa Hutang</span>
                        <span class="fw-semibold text-danger">Rp {{ number_format($row['total_remaining_balance'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-muted">
                    Belum ada data status pada periode ini.
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
