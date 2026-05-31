@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Catat Pencairan Gaji')
@section('heading', 'Catat Pencairan Gaji')

@section('content')
    <section class="section">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h4 class="card-title mb-1">Form Pencairan Gaji</h4>
                        </div>
                    </div>

                    <div class="card-body">
                        <form id="payroll-create-form" action="{{ route('admin.payrolls.store') }}" method="post">
                            @csrf

                            <input
                                type="hidden"
                                id="employee_id"
                                name="employee_id"
                                value="{{ old('employee_id', request('employee_id')) }}"
                            >

                            <div class="form-group mb-4">
                                <label for="employee_search" class="form-label">Cari Karyawan</label>
                                <input
                                    type="text"
                                    id="employee_search"
                                    class="form-control @error('employee_id') is-invalid @enderror"
                                    placeholder="Ketik minimal 2 huruf nama atau telepon"
                                    autocomplete="off"
                                    autofocus
                                >
                                <small class="text-muted">
                                    Tekan Enter untuk memilih hasil aktif lalu lanjut otomatis ke nominal.
                                </small>
                                @error('employee_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div
                                id="payroll-employee-search-results"
                                class="list-group mb-4 d-none"
                                aria-live="polite"
                            ></div>

                            <div class="card bg-light-subtle border mb-4">
                                <div class="card-body">
                                    <h6 class="mb-2">Karyawan Terpilih</h6>
                                    <div id="payroll-selected-employee" class="text-muted">
                                        Belum ada karyawan dipilih.
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group mb-4" data-money-input-group>
                                        <label for="amount_display" class="form-label">Nominal Pencairan</label>
                                        <input
                                            type="hidden"
                                            id="amount"
                                            name="amount"
                                            value="{{ old('amount') }}"
                                            data-money-raw
                                        >
                                        <input
                                            type="text"
                                            id="amount_display"
                                            value="{{ old('amount') }}"
                                            class="form-control @error('amount') is-invalid @enderror"
                                            placeholder="Contoh: 1.500.000"
                                            inputmode="numeric"
                                            data-money-display
                                            required
                                        >
                                        @error('amount')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group mb-4">
                                        <label for="disbursement_date_string" class="form-label">Tanggal Pencairan</label>
                                        <input
                                            type="date"
                                            data-ui-date="single"
                                            id="disbursement_date_string"
                                            name="disbursement_date_string"
                                            value="{{ old('disbursement_date_string', now()->format('Y-m-d')) }}"
                                            class="form-control @error('disbursement_date_string') is-invalid @enderror"
                                            required
                                        >
                                        @error('disbursement_date_string')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="mode_value" class="form-label">Mode Pencairan</label>
                                <select
                                    id="mode_value"
                                    name="mode_value"
                                    class="form-select @error('mode_value') is-invalid @enderror"
                                    required
                                >
                                    <option value="monthly" @selected(old('mode_value', 'monthly') === 'monthly')>Bulanan</option>
                                    <option value="weekly" @selected(old('mode_value') === 'weekly')>Mingguan</option>
                                    <option value="daily" @selected(old('mode_value') === 'daily')>Harian</option>
                                </select>
                                @error('mode_value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label for="notes" class="form-label">Catatan</label>
                                <input
                                    type="text"
                                    id="notes"
                                    name="notes"
                                    value="{{ old('notes') }}"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    placeholder="Opsional"
                                >
                                <small class="text-muted">
                                    Tekan Enter di field ini untuk langsung menyimpan pencairan.
                                </small>
                                @error('notes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-start gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Simpan Pencairan Gaji
                                </button>
                                <a href="{{ route('admin.payrolls.index') }}" class="btn btn-light-secondary">
                                    Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script>
        window.payrollCreateConfig = {
            employees: @json($employees),
        };
        window.AdminMoneyInput?.bindBySelector(document);
    </script>
    <script src="{{ asset('assets/static/js/pages/admin-payroll-create.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
