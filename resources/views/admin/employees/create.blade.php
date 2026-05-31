@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Tambah Karyawan')
@section('heading', 'Tambah Karyawan')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-row justify-content-between align-items-center gap-2">
                            <div>
                                <h4 class="card-title mb-1">Tambah Karyawan</h4>
                                <p class="mb-0 text-muted">
                                    Isi data master karyawan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.employees.store') }}" method="post" id="employee-master-form" data-employee-master-form="1">
                            @csrf

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="employee_name" class="form-label">Nama Karyawan</label>
                                        <input
                                            type="text"
                                            id="employee_name"
                                            name="employee_name"
                                            value="{{ old('employee_name', old('name')) }}"
                                            class="form-control @error('employee_name') is-invalid @enderror"
                                            placeholder="Contoh: Budi Santoso"
                                            required
                                        >
                                        @error('employee_name')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="phone" class="form-label">Nomor Telepon</label>
                                        <input
                                            type="text"
                                            id="phone"
                                            name="phone"
                                            value="{{ old('phone') }}"
                                            class="form-control @error('phone') is-invalid @enderror"
                                            placeholder="Opsional"
                                        >
                                        @error('phone')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="salary_basis_type" class="form-label">Basis Gaji</label>
                                        <select
                                            id="salary_basis_type"
                                            name="salary_basis_type"
                                            class="form-select @error('salary_basis_type') is-invalid @enderror"
                                            required
                                        >
                                            <option value="monthly" @selected(old('salary_basis_type', old('pay_period_value', 'monthly')) === 'monthly')>Bulanan</option>
                                            <option value="weekly" @selected(old('salary_basis_type', old('pay_period_value')) === 'weekly')>Mingguan</option>
                                            <option value="daily" @selected(old('salary_basis_type', old('pay_period_value')) === 'daily')>Harian</option>
                                            <option value="manual" @selected(old('salary_basis_type', old('pay_period_value')) === 'manual')>Manual</option>
                                        </select>
                                        @error('salary_basis_type')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4" data-money-input-group>
                                        <label for="default_salary_amount_display" class="form-label">Default Gaji</label>

                                        <input
                                            type="hidden"
                                            id="default_salary_amount"
                                            name="default_salary_amount"
                                            value="{{ old('default_salary_amount', old('base_salary_amount')) }}"
                                            data-money-raw
                                        >

                                        <input
                                            type="text"
                                            id="default_salary_amount_display"
                                            value="{{ old('default_salary_amount', old('base_salary_amount')) }}"
                                            class="form-control @error('default_salary_amount') is-invalid @enderror"
                                            placeholder="Opsional. Contoh: 5.000.000"
                                            inputmode="numeric"
                                            data-money-display
                                        >

                                        @error('default_salary_amount')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group mb-4">
                                        <label for="started_at" class="form-label">Mulai Kerja</label>
                                        <input
                                            type="date"
                                            data-ui-date="single"
                                            id="started_at"
                                            name="started_at"
                                            value="{{ old('started_at') }}"
                                            class="form-control @error('started_at') is-invalid @enderror"
                                        >
                                        @error('started_at')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-group mb-4">
                                        <label for="ended_at" class="form-label">Berakhir</label>
                                        <input
                                            type="date"
                                            data-ui-date="single"
                                            id="ended_at"
                                            name="ended_at"
                                            value="{{ old('ended_at') }}"
                                            class="form-control @error('ended_at') is-invalid @enderror"
                                        >
                                        @error('ended_at')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                        <div class="form-text">
                                            Jika tanggal akhir kerja diisi, status karyawan otomatis menjadi Nonaktif.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-start gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Simpan Karyawan
                                </button>
                                <a href="{{ route('admin.employees.index') }}" class="btn btn-light-secondary">
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
    <script src="{{ asset('assets/static/js/pages/admin-employee-master-form.js') }}?v={{ config('app.asset_version') }}"></script>
    <script>
        window.AdminMoneyInput?.bindBySelector(document);
    </script>
@endpush
