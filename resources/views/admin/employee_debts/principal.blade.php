@extends('layouts.app')

@section('title', 'Tambah Hutang Karyawan')
@section('heading', 'Tambah Hutang Karyawan')

@section('content')
    <section class="section">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h4 class="mb-1">Tambah Hutang</h4>
                <p class="text-muted mb-0">Gunakan halaman ini untuk menambah nominal hutang karyawan dengan alasan yang jelas.</p>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2">
                <a
                    href="{{ route('admin.employee-debts.show', ['debtId' => $detail['summary']['id']]) }}"
                    class="btn btn-light-secondary"
                >
                    Kembali ke Detail Hutang
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-1">Form Tambah Hutang</h5>
                        <p class="mb-0 text-muted">Tambahkan nominal hutang baru ke data hutang yang sudah ada.</p>
                    </div>

                    <div class="card-body">
                        @if ($errors->has('debt_adjustment'))
                            <div class="alert alert-danger">
                                {{ $errors->first('debt_adjustment') }}
                            </div>
                        @endif

                        <form action="{{ route('admin.employee-debts.adjustments.store', ['debtId' => $detail['summary']['id']]) }}" method="post">
                            @csrf
                            <input type="hidden" name="adjustment_type" value="increase">

                            <div class="form-group mb-4" data-money-input-group>
                                <label for="adjustment_amount_display" class="form-label">Nominal Tambahan</label>
                                <input type="hidden" id="adjustment_amount" name="amount" value="{{ old('amount') }}" data-money-raw>
                                <input
                                    type="text"
                                    id="adjustment_amount_display"
                                    value="{{ old('amount') }}"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    placeholder="Contoh: 100.000"
                                    inputmode="numeric"
                                    data-money-display
                                    required
                                >
                                @error('amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label for="reason" class="form-label">Alasan Penambahan</label>
                                <textarea
                                    id="reason"
                                    name="reason"
                                    rows="3"
                                    class="form-control @error('reason') is-invalid @enderror"
                                    placeholder="Tulis alasan penambahan hutang"
                                    required
                                >{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-warning">Simpan Penambahan</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-1">Ringkasan Hutang</h5>
                        <p class="mb-0 text-muted">Informasi hutang saat ini sebelum Anda menambah nominal hutang.</p>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Nama Karyawan</small>
                            <div class="fw-semibold">{{ $detail['summary']['employee_name'] }}</div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Total Hutang</small>
                            <div class="fw-semibold">Rp{{ $detail['summary']['total_debt_formatted'] }}</div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Sudah Dibayar</small>
                            <div class="fw-semibold">Rp{{ $detail['summary']['total_paid_amount_formatted'] }}</div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Sisa Hutang</small>
                            <div class="fw-semibold">Rp{{ $detail['summary']['remaining_balance_formatted'] }}</div>
                        </div>

                        <div>
                            <small class="text-muted d-block">Status</small>
                            <div class="fw-semibold">{{ $detail['summary']['status_label'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-1">Riwayat Perubahan Hutang</h5>
                        <p class="mb-0 text-muted">Semua penambahan hutang tercatat di sini. Data lama pengurangan tetap ditampilkan sebagai riwayat lama.</p>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-lg">
                                <thead>
                                    <tr class="text-nowrap">
                                        <th style="width: 64px;">No</th>
                                        <th>Waktu</th>
                                        <th>Aksi</th>
                                        <th>Nominal</th>
                                        <th>Alasan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($adjustments as $adjustment)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $adjustment['recorded_at'] }}</td>
                                            <td>{{ $adjustment['adjustment_type_label'] }}</td>
                                            <td>Rp{{ $adjustment['amount_formatted'] }}</td>
                                            <td>{{ $adjustment['reason'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada perubahan hutang.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script>
        window.AdminMoneyInput?.bindBySelector(document);
    </script>
@endpush
