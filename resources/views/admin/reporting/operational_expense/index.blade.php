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
    <div class="text-muted small">
        Laporan ini merangkum biaya operasional aktif, kategori terbesar, dan
        rata-rata harian pada periode yang dipilih.
    </div>
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
    <h5 class="mb-2">Catatan Laporan</h5>
    <div class="text-muted">
        Gunakan ringkasan ini untuk membaca beban operasional dengan cepat.
        Detail lengkap tersedia di Excel.
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Rincian Per Tanggal</h5>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-end">Entry</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periodRows as $row)
                                <tr>
                                    <td>{{ $row['period_label'] }}</td>
                                    <td class="text-end">{{ number_format($row['total_rows'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['total_amount_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada biaya pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Rincian Kategori</h5>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Entry</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categoryRows as $row)
                                <tr>
                                    <td>{{ $row['category_name'] }}</td>
                                    <td class="text-end">{{ number_format($row['total_rows'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['total_amount_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada kategori pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Detail Biaya Operasional</h5>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Metode</th>
                                <th>Referensi</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody id="operational-expense-report-table-body">
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ \App\Support\ViewDateFormatter::display($row['expense_date'] ?? null) }}</td>
                                    <td>{{ $row['category_name'] }}</td>
                                    <td>{{ $row['description'] }}</td>
                                    <td>
                                        {{ match ($row['payment_method'] ?? '') {
                                            'cash' => 'Tunai',
                                            'transfer' => 'Transfer',
                                            'bank_transfer' => 'Transfer Bank',
                                            'debit' => 'Debit',
                                            'credit' => 'Kredit',
                                            'qris' => 'QRIS',
                                            default => strtoupper((string) ($row['payment_method'] ?? '-')),
                                        } }}
                                    </td>
                                    <td>{{ $row['reference_no'] ?? '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($row['amount_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada biaya pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    @include('layouts.partials.pagination', ['paginator' => $rows])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
