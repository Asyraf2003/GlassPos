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
    'basisDateNote' => 'Laporan ini memakai tanggal transaksi nota dan sumber historis inventory movement, bukan harga modal produk saat ini.',
    'noteText' => 'Breakdown ini dipisahkan dari Laba Kas Operasional agar keuntungan paket service + sparepart tidak tercampur dengan laporan kas.',
    'supportsCustomRange' => true,
])

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

    <div class="col-12 col-md-6 col-xl-4">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Margin Sparepart</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['sparepart_margin_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Komponen Service</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['total_service_component_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Gross Profit Paket</div>
            <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['total_package_gross_profit_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-3">Detail Paket Service</h5>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nota</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th class="text-end">Paket</th>
                        <th class="text-end">Sparepart</th>
                        <th class="text-end">HPP</th>
                        <th class="text-end">Margin Sparepart</th>
                        <th class="text-end">Service</th>
                        <th class="text-end">Profit Paket</th>
                        <th class="text-end">Gross Profit</th>
                    </tr>
                </thead>
                <tbody id="service-package-profit-breakdown-table-body">
                    @forelse ($rows as $row)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $row['note_id'] }}</div>
                                <div class="small text-muted">{{ $row['work_item_id'] }}</div>
                            </td>
                            <td>{{ \App\Support\ViewDateFormatter::display($row['transaction_date'] ?? null) }}</td>
                            <td>{{ $row['customer_name'] }}</td>
                            <td class="text-end">Rp {{ number_format($row['package_sold_amount_rupiah'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row['parts_total_rupiah'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row['sparepart_cogs_rupiah'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row['sparepart_margin_rupiah'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">
                                <div>Rp {{ number_format($row['service_price_rupiah'] ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-muted">
                                    Base Rp {{ number_format($row['package_base_service_price_rupiah'] ?? 0, 0, ',', '.') }}
                                    · Extra Rp {{ number_format($row['package_service_extra_rupiah'] ?? 0, 0, ',', '.') }}
                                </div>
                            </td>
                            <td class="text-end">Rp {{ number_format($row['package_profit_rupiah'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($row['total_package_gross_profit_rupiah'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">Belum ada paket service pada periode ini.</td>
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
@endsection
