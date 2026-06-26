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
    'basisDateNote' => 'Filter memengaruhi ringkasan movement periodik. Snapshot stok saat ini tetap menampilkan posisi stok terbaru.',
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
    <div class="text-muted small">
        Laporan ini merangkum posisi stok terbaru, nilai persediaan, dan mutasi
        utama pada rentang movement yang dipilih.
    </div>
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
</div>

<div class="mb-3">
    <h5 class="mb-2">Catatan Laporan</h5>
    <div class="text-muted">
        Gunakan ringkasan ini untuk membaca posisi persediaan dengan cepat.
        Detail lengkap tersedia di Excel.
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Snapshot Stok Saat Ini</h5>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga Pokok Rata-rata</th>
                                <th class="text-end">Inventory Value</th>
                            </tr>
                        </thead>
                        <tbody id="inventory-stock-value-snapshot-table-body">
                            @forelse ($snapshotRows as $row)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $row['nama_barang'] }}</div>
                                        <div class="small text-muted">{{ $row['kode_barang'] ?? 'Tanpa kode barang' }}</div>
                                    </td>
                                    <td>{{ $row['kode_barang'] ?? '-' }}</td>
                                    <td>{{ $row['nama_barang'] }}</td>
                                    <td class="text-end">{{ number_format($row['current_qty_on_hand'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['current_avg_cost_rupiah'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['current_inventory_value_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada snapshot persediaan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    @include('layouts.partials.pagination', ['paginator' => $snapshotRows])
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Ringkasan Mutasi Periode</h5>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th class="text-end">Supply In</th>
                                <th class="text-end">Sale Out</th>
                                <th class="text-end">Refund/Reversal</th>
                                <th class="text-end">Koreksi/Revisi</th>
                                <th class="text-end">Net Qty</th>
                                <th class="text-end">Net Cost</th>
                            </tr>
                        </thead>
                        <tbody id="inventory-stock-value-movement-table-body">
                            @forelse ($movementRows as $row)
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            {{ $row['nama_barang'] ?? '-' }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.85rem;">
                                            {{ $row['kode_barang'] ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-end">{{ number_format($row['supply_in_qty'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['sale_out_qty'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['refund_reversal_qty'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['revision_correction_qty'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['net_qty_delta'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['net_cost_delta_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada mutasi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    @include('layouts.partials.pagination', ['paginator' => $movementRows])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
