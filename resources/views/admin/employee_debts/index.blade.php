@extends('layouts.app')

@section('title', 'Hutang Karyawan')
@section('heading', 'Hutang Karyawan')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Tabel ringkasan hutang interaktif yang mengarah ke detail karyawan</h4>
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                        <form id="employee-debt-search-form" class="m-0 d-flex">
                            <input
                                type="text"
                                id="employee-debt-search-input"
                                class="form-control py-2"
                                placeholder="Cari nama karyawan"
                                autocomplete="off"
                                style="min-height: 40px;"
                            >
                        </form>

                        <a href="{{ route('admin.employee-debts.create') }}" class="btn btn-primary py-2 d-inline-flex align-items-center">
                            Catat Hutang Karyawan
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-lg" id="employee-debt-table">
                        <thead>
                            <tr class="text-nowrap">
                                <th style="width: 64px;">No</th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="employee_name">
                                        Karyawan
                                        <span class="ms-1 text-muted" data-sort-indicator="employee_name">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="latest_recorded_at">
                                        Terakhir Dicatat
                                        <span class="ms-1 text-muted" data-sort-indicator="latest_recorded_at">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="total_debt_records">
                                        Total Record
                                        <span class="ms-1 text-muted" data-sort-indicator="total_debt_records">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="total_debt_amount">
                                        Total Hutang
                                        <span class="ms-1 text-muted" data-sort-indicator="total_debt_amount">↕</span>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="total_remaining_balance">
                                        Total Sisa
                                        <span class="ms-1 text-muted" data-sort-indicator="total_remaining_balance">↕</span>
                                    </button>
                                </th>
                                <th>Status Hutang</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="employee-debt-table-body">
                            <tr><td colspan="8" class="text-center text-muted py-4">Sedang memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <small id="employee-debt-table-summary" class="text-muted">Total: -</small>
                    <div id="employee-debt-table-pagination"></div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="employee-debt-action-modal"
            tabindex="-1"
            aria-labelledby="employee-debt-action-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="employee-debt-action-modal-title">Aksi Hutang Karyawan</h3>
                            <p class="mb-0 text-muted fs-6" id="employee-debt-action-modal-subtitle">
                                Pilih tindakan untuk ringkasan hutang karyawan.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <a
                                    href="#"
                                    id="employee-debt-action-detail-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100"
                                >
                                    <div class="fw-bold fs-5 mb-1">Detail Karyawan</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-3">
                                <a
                                    href="#"
                                    id="employee-debt-action-add-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100"
                                >
                                    <div class="fw-bold fs-5 mb-1">Tambah Hutang</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-3">
                                <button
                                    type="button"
                                    id="employee-debt-action-pay-button"
                                    class="btn btn-outline-success w-100 text-start py-3 px-4 h-100"
                                >
                                    <div class="fw-bold fs-5 mb-1">Bayar Hutang</div>
                                </button>
                            </div>

                            <div class="col-12 col-md-3">
                                <a
                                    href="#"
                                    id="employee-debt-action-debt-link"
                                    class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100"
                                >
                                    <div class="fw-bold fs-5 mb-1">Detail Hutang</div>
                                </a>
                            </div>
                        </div>

                        <div id="employee-debt-action-pay-empty" class="alert alert-warning mt-3 d-none mb-0">
                            Karyawan ini belum punya hutang aktif yang bisa dibayar.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="employee-debt-payment-modal"
            tabindex="-1"
            aria-labelledby="employee-debt-payment-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="employee-debt-payment-modal-title">Bayar Hutang</h3>
                            <p class="mb-0 text-muted fs-6" id="employee-debt-payment-modal-subtitle">
                                Catat pembayaran hutang karyawan.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <form id="employee-debt-payment-form" method="post" action="#">
                            @csrf

                            <div class="form-group mb-4" data-money-input-group>
                                <label for="employee-debt-payment-amount-display" class="form-label">Nominal Bayar</label>
                                <input
                                    type="hidden"
                                    id="employee-debt-payment-amount"
                                    name="payment_amount"
                                    value=""
                                    data-money-raw
                                >
                                <input
                                    type="text"
                                    id="employee-debt-payment-amount-display"
                                    value=""
                                    class="form-control"
                                    placeholder="Contoh: 100.000"
                                    inputmode="numeric"
                                    data-money-display
                                    required
                                >
                            </div>

                            <div class="form-group mb-4">
                                <label for="employee-debt-payment-notes" class="form-label">Catatan</label>
                                <textarea
                                    id="employee-debt-payment-notes"
                                    name="notes"
                                    rows="3"
                                    class="form-control"
                                    placeholder="Contoh: Cicilan minggu ini"
                                ></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success">Simpan Pembayaran</button>
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
        window.employeeDebtTableConfig = {
            endpoint: @json(route('admin.employee-debts.table')),
            detailBaseUrl: @json(route('admin.employees.show', ['employeeId' => '__ID__'])),
            createDebtUrl: @json(route('admin.employee-debts.create')),
            debtShowBaseUrl: @json(route('admin.employee-debts.show', ['debtId' => '__ID__'])),
            principalBaseUrl: @json(route('admin.employee-debts.principal', ['debtId' => '__ID__'])),
            paymentStoreBaseUrl: @json(route('admin.employee-debts.payments.store', ['debtId' => '__ID__']))
        };
    </script>
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-employee-debts-table.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/admin-employee-debt-table-actions.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush