@extends('layouts.app')
@section('title', 'Detail Gaji Karyawan')
@section('heading', 'Detail Gaji Karyawan')

@section('content')
    <section class="section">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h4 class="mb-1">{{ $employee['employee_name'] }}</h4>
                <p class="text-muted mb-0">{{ $page['subtitle'] }}</p>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2">
                <a
                    href="{{ route('admin.employees.show', ['employeeId' => $detail['summary']['id']]) }}"
                    class="btn btn-light-secondary"
                >
                    Detail Karyawan
                </a>
                <a
                    href="{{ route('admin.payrolls.create', ['employee_id' => $detail['summary']['id']]) }}"
                    class="btn btn-primary"
                >
                    Catat Gaji
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Basis Gaji</h5>
                    </div>
                    <div class="card-body">
                        <div class="fw-semibold">{{ $employee['salary_basis_label'] }}</div>
                        <div class="small text-muted mt-1">Status: {{ $employee['employment_status_label'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Default Gaji</h5>
                    </div>
                    <div class="card-body">
                        <div class="fw-semibold">{{ $employee['default_salary_amount_label'] }}</div>
                        <div class="small text-muted mt-1">Telepon: {{ $employee['phone'] ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Total Riwayat</h5>
                    </div>
                    <div class="card-body">
                        <div class="fw-semibold">{{ $payrollSummary['total_payroll_records'] }}</div>
                        <div class="small text-muted mt-1">Payroll aktif karyawan ini</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Total Dicairkan</h5>
                    </div>
                    <div class="card-body">
                        <div class="fw-semibold">Rp{{ $payrollSummary['total_disbursed_amount_formatted'] }}</div>
                        <div class="small text-muted mt-1">
                            Terakhir: {{ \App\Support\ViewDateFormatter::display($payrollSummary['latest_disbursement_date'] ?? null) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h5 class="card-title mb-1">Riwayat Gaji Karyawan</h5>
                        <p class="mb-0 text-muted">Daftar riwayat gaji khusus {{ $employee['employee_name'] }}.</p>
                    </div>
                    <a href="{{ route('admin.payrolls.create', ['employee_id' => $detail['summary']['id']]) }}" class="btn btn-primary">Catat Gaji</a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-lg" id="employee-payroll-history-table">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 64px;">No</th>
                                <th>Tanggal</th>
                                <th>Nominal</th>
                                <th>Mode Pencairan</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="employee-payroll-history-body">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Sedang memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <small id="employee-payroll-history-summary" class="text-muted">Total: -</small>
                    <div id="employee-payroll-history-pagination"></div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="employee-payroll-reversal-modal"
            tabindex="-1"
            aria-labelledby="employee-payroll-reversal-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <form id="employee-payroll-reversal-form" method="POST" action="#">
                        @csrf

                        <div class="modal-header border-0 pb-0 px-4 pt-4">
                            <div class="w-100">
                                <h3 class="modal-title fw-bold mb-1" id="employee-payroll-reversal-modal-title">Reversal Gaji</h3>
                                <p class="mb-0 text-muted fs-6" id="employee-payroll-reversal-modal-subtitle">
                                    Isi alasan pembatalan pencairan gaji.
                                </p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body px-4 pb-3 pt-3">
                            <div class="mb-3">
                                <label for="employee-payroll-reversal-reason" class="form-label">Alasan</label>
                                <textarea
                                    name="reason"
                                    id="employee-payroll-reversal-reason"
                                    class="form-control"
                                    rows="4"
                                    required
                                    placeholder="Tulis alasan pembatalan pencairan gaji"
                                >{{ old('reason') }}</textarea>
                                <div class="form-text">Alasan wajib diisi untuk kebutuhan audit koreksi payroll.</div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 px-4 pb-4 pt-0">
                            <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-danger">Simpan Reversal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        window.employeePayrollHistoryConfig = {
            endpoint: @json(route('admin.employees.payroll-table', ['employeeId' => $detail['summary']['id']])),
            reverseStoreBaseUrl: @json(route('admin.payrolls.reverse.store', ['payrollId' => '__ID__'])),
            employeeDetailUrl: @json(route('admin.employees.show', ['employeeId' => $detail['summary']['id']]))
        };
    </script>
    <script src="{{ asset('assets/static/js/pages/admin-employee-payroll-history.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
