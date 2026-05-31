@extends('layouts.app')

@section('title', 'Gaji')
@section('heading', 'Gaji')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Tabel riwayat pencairan gaji manual</h4>
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                        <form id="payroll-search-form" class="m-0 d-flex">
                            <input 
                                type="text" 
                                id="payroll-search-input" 
                                class="form-control py-2" 
                                placeholder="Cari nama, catatan, mode, atau tanggal" 
                                autocomplete="off"
                                style="min-height: 40px;"
                            >
                        </form>

                        <a href="{{ route('admin.payrolls.create') }}" class="btn btn-primary py-2 d-inline-flex align-items-center">
                            Catat Gaji
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-lg" id="payroll-table">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 64px;">No</th>
                                <th><button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="disbursement_date">Tanggal <span class="ms-1 text-muted" data-sort-indicator="disbursement_date">↕</span></button></th>
                                <th><button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="employee_name">Nama Karyawan <span class="ms-1 text-muted" data-sort-indicator="employee_name">↕</span></button></th>
                                <th><button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="amount">Nominal <span class="ms-1 text-muted" data-sort-indicator="amount">↕</span></button></th>
                                <th><button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="mode">Mode Pencairan <span class="ms-1 text-muted" data-sort-indicator="mode">↕</span></button></th>
                                <th>Catatan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="payroll-table-body">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Sedang memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <small id="payroll-table-summary" class="text-muted">Total: -</small>
                    <div id="payroll-table-pagination"></div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="payroll-action-modal"
            tabindex="-1"
            aria-labelledby="payroll-action-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="payroll-action-modal-title">Aksi Riwayat Gaji</h3>
                            <p class="mb-0 text-muted fs-6" id="payroll-action-modal-subtitle">
                                Pilih tindakan untuk riwayat gaji.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="payroll-action-detail-employee-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4"
                                >
                                    <div class="fw-bold fs-5 mb-1">Detail Karyawan</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="payroll-action-detail-payroll-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4"
                                >
                                    <div class="fw-bold fs-5 mb-1">Detail Gaji</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <button
                                    type="button"
                                    id="payroll-action-reversal-button"
                                    class="btn btn-outline-danger w-100 text-start py-3 px-4"
                                >
                                    <div class="fw-bold fs-5 mb-1">Reversal</div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="payroll-reversal-modal"
            tabindex="-1"
            aria-labelledby="payroll-reversal-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <form id="payroll-reversal-form" method="POST" action="#">
                        @csrf

                        <div class="modal-header border-0 pb-0 px-4 pt-4">
                            <div class="w-100">
                                <h3 class="modal-title fw-bold mb-1" id="payroll-reversal-modal-title">Reversal Gaji</h3>
                                <p class="mb-0 text-muted fs-6" id="payroll-reversal-modal-subtitle">
                                    Isi alasan pembatalan pencairan gaji.
                                </p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body px-4 pb-3 pt-3">
                            <div class="mb-3">
                                <label for="payroll-reversal-reason" class="form-label">Alasan</label>
                                <textarea
                                    name="reason"
                                    id="payroll-reversal-reason"
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
        window.payrollTableConfig = {
            endpoint: @json(route('admin.payrolls.table')),
            employeeDetailBaseUrl: @json(route('admin.employees.show', ['employeeId' => '__ID__'])),
            employeePayrollDetailBaseUrl: @json(route('admin.employees.payrolls.show', ['employeeId' => '__ID__'])),
            reverseStoreBaseUrl: @json(route('admin.payrolls.reverse.store', ['payrollId' => '__ID__']))
        };
    </script>
    <script src="{{ asset('assets/static/js/pages/admin-payrolls-table.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
