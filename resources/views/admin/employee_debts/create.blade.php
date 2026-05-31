@extends('layouts.app')

@section('title', 'Catat Hutang Karyawan')
@section('heading', 'Catat Hutang Karyawan')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-row justify-content-between align-items-center gap-2">
                            <div>
                                <h4 class="card-title mb-1">Catat Hutang Karyawan</h4>
                                <p class="mb-0 text-muted">
                                    Catat hutang baru untuk karyawan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @if ($errors->has('employee_debt'))
                            <div class="alert alert-danger">
                                {{ $errors->first('employee_debt') }}
                            </div>
                        @endif

                        <form action="{{ route('admin.employee-debts.store') }}" method="post" id="employee-debt-create-form">
                            @csrf

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group mb-4 position-relative">
                                        <label for="employee_picker_query" class="form-label">Karyawan</label>

                                        <input
                                            type="hidden"
                                            id="employee_id"
                                            name="employee_id"
                                            value="{{ old('employee_id', $prefilledEmployeeId ?? '') }}"
                                        >

                                        <input
                                            type="text"
                                            id="employee_picker_query"
                                            name="employee_lookup"
                                            value="{{ old('employee_lookup', $prefilledEmployeeName ?? '') }}"
                                            class="form-control @error('employee_id') is-invalid @enderror"
                                            placeholder="Ketik minimal 2 huruf nama karyawan"
                                            autocomplete="off"
                                            spellcheck="false"
                                            required
                                        >

                                        <div id="employee-picker-results" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1050;"></div>

                                        <small id="employee-picker-summary" class="text-muted d-block mt-2">
                                            Pilih karyawan dari hasil pencarian. Data yang dikirim tetap employee_id berbentuk UUID.
                                        </small>

                                        @error('employee_id')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4" data-money-input-group>
                                        <label for="debt_amount_display" class="form-label">Nominal Hutang</label>

                                        <input
                                            type="hidden"
                                            id="debt_amount"
                                            name="debt_amount"
                                            value="{{ old('debt_amount') }}"
                                            data-money-raw
                                        >

                                        <input
                                            type="text"
                                            id="debt_amount_display"
                                            value="{{ old('debt_amount') }}"
                                            class="form-control @error('debt_amount') is-invalid @enderror"
                                            placeholder="Contoh: 1.000.000"
                                            inputmode="numeric"
                                            data-money-display
                                            required
                                        >

                                        @error('debt_amount')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label for="notes" class="form-label">Catatan</label>
                                        <textarea
                                            id="notes"
                                            name="notes"
                                            rows="3"
                                            class="form-control @error('notes') is-invalid @enderror"
                                            placeholder="Opsional"
                                        >{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-start gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Simpan Data Hutang
                                </button>
                                <a href="{{ route('admin.employee-debts.index') }}" class="btn btn-light-secondary">
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
    <script>
        window.employeeDebtCreateConfig = {
            employees: @json($employees),
        };
    </script>
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-employee-debt-create.js') }}?v={{ config('app.asset_version') }}"></script>
    <script>
        window.AdminMoneyInput?.bindBySelector(document);
    </script>
@endpush
