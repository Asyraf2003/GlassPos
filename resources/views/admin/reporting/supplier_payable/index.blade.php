@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Hutang Pemasok')
@section('heading', 'Hutang Pemasok')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'supplier-payable-report-filter-form',
    'action' => route('admin.reports.supplier_payable.index'),
    'resetUrl' => route('admin.reports.supplier_payable.index'),
    'rangeLabelText' => 'Rentang pengiriman aktif',
    'basisDateLabel' => 'Tanggal pengiriman invoice',
    'basisDateNote' => 'Data faktur masuk dihitung dari tanggal pengiriman.',
    'noteText' => 'Status jatuh tempo dievaluasi terhadap tanggal referensi ' . \App\Support\ViewDateFormatter::display($filters['reference_date'] ?? null) . '.',
    'supportsCustomRange' => true,
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => route('admin.reports.supplier_payable.export_excel', request()->query()),
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.supplier_payable.export_pdf', request()->query()),
            'class' => 'btn btn-outline-danger text-nowrap',
        ],
    ],
])
<div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
    <div aria-hidden="true">🔔</div>
    <div>
        <div class="fw-semibold">Notifikasi hutang faktur belum aktif.</div>
        <div class="small mb-0">Template UI reminder hutang faktur masih hardcoded; pengiriman notifikasi otomatis belum diaktifkan pada flow produksi.</div>
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-1">Ringkasan Utama</h5>
    <div class="text-muted small">
        Laporan ini merangkum tagihan pemasok, pembayaran yang sudah dicatat,
        dan sisa yang belum dibayar berdasarkan tanggal referensi.
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Faktur</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Tagihan</div>
                <div class="fs-5 fw-bold">Rp {{ number_format($summary['grand_total_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Dibayar</div>
                <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['total_paid_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Outstanding</div>
                <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['outstanding_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Belum Jatuh Tempo</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['not_due_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Jatuh Tempo Hari Ini</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['due_today_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Lewat Jatuh Tempo</div>
                <div class="fs-5 fw-bold text-danger">{{ number_format($summary['overdue_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Sisa Hutang Lewat Tempo</div>
                <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['overdue_outstanding_rupiah'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Belum Lunas</div>
                <div class="fs-5 fw-bold">{{ number_format($summary['open_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Lunas</div>
                <div class="fs-5 fw-bold text-success">{{ number_format($summary['settled_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <h5 class="mb-2">Catatan Laporan</h5>
    <div class="text-muted">
        Gunakan bagian ini untuk melihat pemasok mana yang perlu diprioritaskan.
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
                                <th class="text-end">Invoice</th>
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periodRows as $row)
                                <tr>
                                    <td>{{ $row['period_label'] }}</td>
                                    <td class="text-end">{{ number_format($row['total_rows'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['outstanding_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada faktur pada periode ini.</td>
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
                <h5 class="card-title mb-3">Rincian Pemasok</h5>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th class="text-end">Invoice</th>
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($supplierRows as $row)
                                <tr>
                                    <td>{{ $row['supplier_name'] ?? $row['supplier_id'] }}</td>
                                    <td class="text-end">{{ number_format($row['total_rows'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['outstanding_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada pemasok pada periode ini.</td>
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
                <h5 class="card-title mb-3">Detail Hutang Pemasok</h5>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No Faktur</th>
                                <th>Supplier</th>
                                <th>Tanggal Kirim</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th class="text-end">Total Tagihan</th>
                                <th class="text-end">Dibayar</th>
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody id="supplier-payable-report-table-body">
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row['nomor_faktur'] ?? $row['supplier_invoice_id'] }}</td>
                                    <td>{{ $row['supplier_name'] ?? $row['supplier_id'] }}</td>
                                    <td>{{ \App\Support\ViewDateFormatter::display($row['shipment_date'] ?? null) }}</td>
                                    <td>{{ \App\Support\ViewDateFormatter::display($row['due_date'] ?? null) }}</td>
                                    <td>
                                        @if (($row['due_status'] ?? '') === 'settled')
                                            <span class="badge bg-success">{{ $row['due_status_label'] }}</span>
                                        @elseif (($row['due_status'] ?? '') === 'overdue')
                                            <span class="badge bg-danger">{{ $row['due_status_label'] }}</span>
                                        @elseif (($row['due_status'] ?? '') === 'due_today')
                                            <span class="badge bg-warning text-dark">{{ $row['due_status_label'] }}</span>
                                        @else
                                            <span class="badge bg-primary">{{ $row['due_status_label'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format($row['grand_total_rupiah'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['total_paid_rupiah'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['outstanding_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada faktur pada periode ini.</td>
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
