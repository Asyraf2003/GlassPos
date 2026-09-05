@extends('layouts.app')
@section('title', 'Detail Hutang Karyawan')
@section('heading', 'Detail Hutang Karyawan')

@section('content')
    @php
        $payments = $detail['payments'] ?? [];
        $hasPayments = $payments !== [];
        $hasPaymentReversals = $paymentReversals !== [];
        $hasAdjustments = $adjustments !== [];
        $hasHistory = $hasPayments || $hasPaymentReversals || $hasAdjustments;
    @endphp

    <section class="section">
        <div class="row g-4 align-items-start">
            <div class="{{ $hasHistory ? 'col-12 col-xl-5' : 'col-12 col-xl-7 mx-xl-auto' }}">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Ringkasan Hutang</h4>
                    </div>

                    <div class="card-body">
                        <dl class="row mb-0 gy-2">
                            <dt class="col-sm-5">Karyawan</dt>
                            <dd class="col-sm-7">{{ $detail['summary']['employee_name'] }}</dd>

                            <dt class="col-sm-5">Tanggal Catat</dt>
                            <dd class="col-sm-7">{{ $detail['summary']['recorded_at'] }}</dd>

                            <dt class="col-sm-5">Total Hutang</dt>
                            <dd class="col-sm-7">Rp{{ $detail['summary']['total_debt_formatted'] }}</dd>

                            <dt class="col-sm-5">Sudah Dibayar</dt>
                            <dd class="col-sm-7">Rp{{ $detail['summary']['total_paid_amount_formatted'] }}</dd>

                            <dt class="col-sm-5">Sisa Hutang</dt>
                            <dd class="col-sm-7 fw-semibold">Rp{{ $detail['summary']['remaining_balance_formatted'] }}</dd>

                            <dt class="col-sm-5">Status</dt>
                            <dd class="col-sm-7">
                                <span class="badge border">{{ $detail['summary']['status_label'] }}</span>
                            </dd>

                            <dt class="col-sm-5">Catatan</dt>
                            <dd class="col-sm-7 mb-0">{{ $detail['summary']['notes'] ?? '-' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            @if ($hasHistory)
                <div class="col-12 col-xl-7">
                    @if (! empty($detail['payments']))
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Riwayat Pembayaran</h4>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-lg mb-0">
                                        <thead>
                                            <tr class="text-nowrap">
                                                <th style="width: 64px;">No</th>
                                                <th>Tanggal Bayar</th>
                                                <th>Nominal</th>
                                                <th>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($payments as $payment)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ \App\Support\ViewDateFormatter::display($payment['payment_date'] ?? null) }}</td>
                                                    <td>Rp{{ $payment['amount_formatted'] }}</td>
                                                    <td>{{ $payment['notes'] ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($paymentReversals !== [])
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Riwayat Reversal Pembayaran</h4>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-lg mb-0">
                                        <thead>
                                            <tr class="text-nowrap">
                                                <th style="width: 64px;">No</th>
                                                <th>Waktu Reversal</th>
                                                <th>Tanggal Bayar</th>
                                                <th>Nominal</th>
                                                <th>Catatan Pembayaran</th>
                                                <th>Alasan Reversal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($paymentReversals as $reversal)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $reversal['recorded_at'] }}</td>
                                                    <td>{{ \App\Support\ViewDateFormatter::display($reversal['payment_date'] ?? null) }}</td>
                                                    <td>Rp{{ $reversal['amount_formatted'] }}</td>
                                                    <td>{{ $reversal['payment_notes'] ?? '-' }}</td>
                                                    <td>{{ $reversal['reason'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($adjustments !== [])
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Riwayat Koreksi Hutang</h4>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-lg mb-0">
                                        <thead>
                                            <tr class="text-nowrap">
                                                <th style="width: 64px;">No</th>
                                                <th>Waktu</th>
                                                <th>Tipe</th>
                                                <th>Nominal</th>
                                                <th>Alasan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($adjustments as $adjustment)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $adjustment['recorded_at'] }}</td>
                                                    <td>{{ $adjustment['adjustment_type_label'] }}</td>
                                                    <td>Rp{{ $adjustment['amount_formatted'] }}</td>
                                                    <td>{{ $adjustment['reason'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
