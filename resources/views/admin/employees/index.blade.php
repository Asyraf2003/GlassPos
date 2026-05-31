@extends('layouts.app')

@section('title', 'Karyawan')
@section('heading', 'Karyawan')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Tabel karyawan interaktif untuk admin</h4>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-2">
                        <form id="employee-search-form" class="d-flex flex-column gap-1">
                            <input
                                type="text"
                                id="employee-search-input"
                                class="form-control"
                                placeholder="Cari nama, telepon, basis gaji, atau status"
                                autocomplete="off"
                            >
                        </form>
                        <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">Tambah Karyawan</a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-lg" id="employee-table">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 64px;">No</th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="employee_name">
                                        Nama Karyawan
                                        <span class="ms-1 text-muted" data-sort-indicator="employee_name">↕</span>
                                    </button>
                                </th>
                                <th>Telepon</th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="default_salary_amount">
                                        Default Gaji
                                        <span class="ms-1 text-muted" data-sort-indicator="default_salary_amount">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="salary_basis_type">
                                        Basis Gaji
                                        <span class="ms-1 text-muted" data-sort-indicator="salary_basis_type">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="employment_status">
                                        Status
                                        <span class="ms-1 text-muted" data-sort-indicator="employment_status">↕</span>
                                    </button>
                                </th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="employee-table-body">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Sedang memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <small id="employee-table-summary" class="text-muted">Total: -</small>
                    <div id="employee-table-pagination"></div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="employee-action-modal"
            tabindex="-1"
            aria-labelledby="employee-action-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="employee-action-modal-title">Aksi Karyawan</h3>
                            <p class="mb-0 text-muted fs-6" id="employee-action-modal-subtitle">
                                Pilih tindakan untuk data karyawan.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="employee-action-detail-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4"
                                >
                                    <div class="fw-bold fs-5 mb-1">Detail Karyawan</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="employee-action-edit-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4"
                                >
                                    <div class="fw-bold fs-5 mb-1">Edit Karyawan</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="employee-action-payroll-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4"
                                >
                                    <div class="fw-bold fs-5 mb-1">Detail Gaji</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a
                                    href="#"
                                    id="employee-action-debt-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4"
                                >
                                    <div class="fw-bold fs-5 mb-1">Detail Hutang</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        window.employeeTableConfig = {
            endpoint: @json(route('admin.employees.table')),
            detailBaseUrl: @json(route('admin.employees.show', ['employeeId' => '__ID__'])),
            editBaseUrl: @json(route('admin.employees.edit', ['employeeId' => '__ID__'])),
            payrollDetailBaseUrl: @json(route('admin.employees.payrolls.show', ['employeeId' => '__ID__'])),
            debtShowBaseUrl: @json(route('admin.employee-debts.show', ['debtId' => '__ID__'])),
            createDebtUrl: @json(route('admin.employee-debts.create'))
        };
    </script>
    <script src="{{ asset('assets/static/js/pages/admin-employees-table.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-employee-table-actions.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
