@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Edit Nota Pemasok')
@section('heading', 'Edit Nota Pemasok')
@section('back_url', route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => $summary['supplier_invoice_id']]))

@section('content')
    <section class="section">
        <form
            action="{{ route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => $summary['supplier_invoice_id']]) }}"
            method="post"
            novalidate
            id="procurement-edit-form"
            data-procurement-edit-form="1"
            data-procurement-draft-key="admin.procurement.edit-supplier-invoice.{{ $summary['supplier_invoice_id'] }}.revision.{{ $formDefaults['expected_revision_no'] }}.draft.v1"
        >
            @csrf
            @method('PUT')
            <input
                type="hidden"
                name="tax_rounding_residue_confirmed"
                value="0"
                data-tax-rounding-residue-confirmed-input
            >

            <div
                class="alert alert-warning d-none"
                role="alert"
                data-tax-rounding-residue-message
            >
                Total setelah pajak tidak habis dibagi qty, sehingga modal per pcs akan dibulatkan dan selisih pembulatan akan dicatat. Lanjutkan?
            </div>


            <input
                type="hidden"
                name="expected_revision_no"
                value="{{ $formDefaults['expected_revision_no'] }}"
            >

            <div class="row g-4">
                <div class="col-12 col-xl-8 order-2 order-xl-1">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                                <div>
                                    <h4 class="card-title mb-1">Rincian Nota</h4>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" id="add-procurement-line" class="btn btn-primary">
                                        Tambah Rincian
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div
                                class="d-none d-xl-grid text-muted small fw-semibold border-bottom pb-2 mb-3"
                                style="grid-template-columns: minmax(0, 1fr) 72px 168px 144px 44px; gap: 16px;"
                            >
                                <div>Produk</div>
                                <div>Qty (Pcs)</div>
                                <div>Total Rincian</div>
                                <div>Pajak Rincian</div>
                                <div class="text-center">Aksi</div>
                            </div>

                            <div id="procurement-line-items" data-next-index="{{ count($lineItemsView) }}" class="d-flex flex-column gap-3">
                                @foreach ($lineItemsView as $lineView)
                                    <div class="border rounded p-3" data-line-item>
                                        <input
                                            type="hidden"
                                            name="lines[{{ $lineView['index'] }}][previous_line_id]"
                                            value="{{ $lineView['previous_line_id'] }}"
                                            data-previous-line-id
                                        >

                                        <input
                                            type="hidden"
                                            name="lines[{{ $lineView['index'] }}][line_no]"
                                            value="{{ $lineView['line_no'] }}"
                                            data-line-no
                                        >

                                        <div
                                            class="d-flex flex-column d-xl-grid gap-3 align-items-start"
                                            style="grid-template-columns: minmax(0, 1fr) 72px 168px 144px 44px;"
                                        >
                                            <div class="w-100 position-relative">
                                                <label class="form-label d-xl-none">Produk</label>

                                                <input
                                                    type="hidden"
                                                    name="lines[{{ $lineView['index'] }}][product_id]"
                                                    value="{{ $lineView['selected_product_id'] }}"
                                                    data-product-id
                                                >

                                                <input
                                                    type="text"
                                                    value="{{ $lineView['selected_label'] }}"
                                                    class="form-control @error('lines.' . $lineView['index'] . '.product_id') is-invalid @enderror"
                                                    placeholder="Ketik minimal 2 huruf untuk mencari produk"
                                                    autocomplete="off"
                                                    data-product-search
                                                >

                                                <div
                                                    class="list-group position-absolute w-100 shadow-sm d-none mt-1"
                                                    style="z-index: 20;"
                                                    data-product-results
                                                ></div>

                                                @error('lines.' . $lineView['index'] . '.product_id')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="w-100">
                                                <label class="form-label d-xl-none">Jumlah (Pcs)</label>
                                                <input
                                                    type="text"
                                                    inputmode="numeric"
                                                    name="lines[{{ $lineView['index'] }}][qty_pcs]"
                                                    value="{{ $lineView['qty_pcs'] }}"
                                                    class="form-control @error('lines.' . $lineView['index'] . '.qty_pcs') is-invalid @enderror"
                                                    placeholder="Contoh: 2"
                                                    data-qty-input
                                                    style="text-align: center;"
                                                    required
                                                >
                                                @error('lines.' . $lineView['index'] . '.qty_pcs')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="w-100">
                                                <label class="form-label d-xl-none">Total Rincian (Rupiah)</label>

                                                <input
                                                    type="hidden"
                                                    name="lines[{{ $lineView['index'] }}][line_total_rupiah]"
                                                    value="{{ $lineView['line_total_raw'] }}"
                                                    data-money-raw
                                                >

                                                <input
                                                    type="text"
                                                    inputmode="numeric"
                                                    value="{{ $lineView['line_total_display'] }}"
                                                    class="form-control @error('lines.' . $lineView['index'] . '.line_total_rupiah') is-invalid @enderror"
                                                    placeholder="Contoh: 150.000"
                                                    data-money-display
                                                    style="text-align: right;"
                                                    required
                                                >

                                                @error('lines.' . $lineView['index'] . '.line_total_rupiah')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="w-100" data-tax-line-group>
                                                <label class="form-label d-xl-none">Pajak Rincian</label>
                                                <input
                                                    type="text"
                                                    name="lines[{{ $lineView['index'] }}][tax_input]"
                                                    value="{{ $lineView['tax_input'] }}"
                                                    class="form-control @error('lines.' . $lineView['index'] . '.tax_input') is-invalid @enderror"
                                                    placeholder="11% / 15000"
                                                    data-tax-line-input
                                                >
                                                @error('lines.' . $lineView['index'] . '.tax_input')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="w-100">
                                                <label class="form-label d-xl-none">Aksi</label>
                                                <button
                                                    type="button"
                                                    class="btn icon btn-danger"
                                                    data-remove-line
                                                    aria-label="Hapus rincian"
                                                    title="Hapus rincian"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <template id="procurement-line-template">
                                <div class="border rounded p-3" data-line-item>
                                    <input
                                        type="hidden"
                                        name="lines[__INDEX__][previous_line_id]"
                                        value=""
                                        data-previous-line-id
                                    >

                                    <input
                                        type="hidden"
                                        name="lines[__INDEX__][line_no]"
                                        value="__LINE_NO__"
                                        data-line-no
                                    >

                                    <div
                                        class="d-flex flex-column d-xl-grid gap-3 align-items-start"
                                        style="grid-template-columns: minmax(0, 1fr) 72px 168px 144px 44px;"
                                    >
                                        <div class="w-100 position-relative">
                                            <label class="form-label d-xl-none">Produk</label>

                                            <input
                                                type="hidden"
                                                name="lines[__INDEX__][product_id]"
                                                value=""
                                                data-product-id
                                            >

                                            <input
                                                type="text"
                                                value=""
                                                class="form-control"
                                                placeholder="Ketik minimal 2 huruf untuk mencari produk"
                                                autocomplete="off"
                                                data-product-search
                                            >

                                            <div
                                                class="list-group position-absolute w-100 shadow-sm d-none mt-1"
                                                style="z-index: 20;"
                                                data-product-results
                                            ></div>
                                        </div>

                                        <div class="w-100">
                                            <label class="form-label d-xl-none">Jumlah (Pcs)</label>
                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                name="lines[__INDEX__][qty_pcs]"
                                                value="1"
                                                class="form-control"
                                                placeholder="Contoh: 2"
                                                data-qty-input
                                                style="text-align: center;"
                                                required
                                            >
                                        </div>

                                        <div class="w-100">
                                            <label class="form-label d-xl-none">Total Rincian (Rupiah)</label>

                                            <input
                                                type="hidden"
                                                name="lines[__INDEX__][line_total_rupiah]"
                                                value=""
                                                data-money-raw
                                            >

                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                value=""
                                                class="form-control"
                                                placeholder="Contoh: 150.000"
                                                data-money-display
                                                style="text-align: right;"
                                                required
                                            >
                                        </div>

                                        <div class="w-100" data-tax-line-group>
                                            <label class="form-label d-xl-none">Pajak Rincian</label>
                                            <input
                                                type="text"
                                                name="lines[__INDEX__][tax_input]"
                                                value=""
                                                class="form-control"
                                                placeholder="11% / 15000"
                                                data-tax-line-input
                                            >
                                        </div>

                                        <div class="w-100">
                                            <label class="form-label d-xl-none">Aksi</label>
                                            <button
                                                type="button"
                                                class="btn icon btn-danger"
                                                data-remove-line
                                                aria-label="Hapus rincian"
                                                title="Hapus rincian"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4 order-1 order-xl-2">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h4 class="card-title mb-1">Informasi Nota</h4>
                            </div>
                        </div>

                        <div class="card-body d-flex flex-column">
                            @error('supplier_invoice')
                                <div class="alert alert-danger">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="d-flex flex-column gap-3">
                                <div class="form-group">
                                    <label for="change_reason" class="form-label">Alasan Perubahan</label>
                                    <textarea
                                        id="change_reason"
                                        name="change_reason"
                                        rows="3"
                                        class="form-control @error('change_reason') is-invalid @enderror"
                                        placeholder="Contoh: Salah input qty dan kode product di faktur supplier."
                                        required
                                    >{{ $formDefaults['change_reason'] }}</textarea>
                                    @error('change_reason')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="nomor_faktur" class="form-label">Nomor Faktur</label>
                                    <input
                                        type="text"
                                        id="nomor_faktur"
                                        name="nomor_faktur"
                                        value="{{ $formDefaults['nomor_faktur'] }}"
                                        class="form-control @error('nomor_faktur') is-invalid @enderror"
                                        placeholder="Contoh: INV-SUP-2026-0001"
                                        data-procurement-header-field
                                        required
                                    >
                                    @error('nomor_faktur')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="nama_pt_pengirim" class="form-label">Nama PT Pengirim</label>
                                    <input
                                        type="text"
                                        id="nama_pt_pengirim"
                                        name="nama_pt_pengirim"
                                        value="{{ $formDefaults['nama_pt_pengirim'] }}"
                                        class="form-control @error('nama_pt_pengirim') is-invalid @enderror"
                                        placeholder="Contoh: PT Federal Abadi"
                                        data-procurement-header-field
                                        required
                                    >
                                    @error('nama_pt_pengirim')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="tanggal_pengiriman" class="form-label">Tanggal Pengiriman</label>
                                    <input
                                        type="date"
                                        data-ui-date="single"
                                        id="tanggal_pengiriman"
                                        name="tanggal_pengiriman"
                                        value="{{ $formDefaults['tanggal_pengiriman'] }}"
                                        class="form-control @error('tanggal_pengiriman') is-invalid @enderror"
                                        data-procurement-header-field
                                        required
                                    >
                                    @error('tanggal_pengiriman')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group" data-tax-header-group>
                                    <label for="tax_input" class="form-label">Pajak Supplier</label>
                                    <input
                                        type="text"
                                        id="tax_input"
                                        name="tax_input"
                                        value="{{ $formDefaults['tax_input'] }}"
                                        class="form-control @error('tax_input') is-invalid @enderror"
                                        placeholder="Contoh: 11% atau 15000"
                                        data-procurement-header-field
                                        data-tax-header-input
                                    >
                                    <small class="text-muted d-block mt-1">
                                        Isi ini untuk pajak faktur seragam. Jika terisi, pajak per rincian akan disembunyikan.
                                    </small>
                                    @error('tax_input')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="ui-form-actions mt-4">
                                <button type="submit" class="btn btn-primary">
                                    Simpan Perubahan Nota
                                </button>

                                <a
                                    href="{{ route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => $summary['supplier_invoice_id']]) }}"
                                    class="btn btn-light-secondary"
                                >
                                    Kembali ke Detail
                                </a>

                                <button
                                    type="button"
                                    class="btn btn-light-danger"
                                    id="procurement-start-new"
                                    onclick="window.localStorage.removeItem('admin.procurement.edit-supplier-invoice.{{ $summary['supplier_invoice_id'] }}.draft.v1'); window.location.reload();"
                                >
                                    Muat Ulang Draft
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script>
        window.procurementCreateConfig = {
            lookupEndpoint: @json(route('admin.procurement.products.lookup')),
            createProductUrl: @json(
                route('admin.products.create')
                    . '?return_to=' . urlencode(route($returnRouteName, ['supplierInvoiceId' => $summary['supplier_invoice_id']]))
                    . '&return_label=' . urlencode('Kembali ke Edit Nota Pemasok')
            )
        };
    </script>
    <script src="{{ asset('assets/static/js/pages/admin-procurement-edit.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
