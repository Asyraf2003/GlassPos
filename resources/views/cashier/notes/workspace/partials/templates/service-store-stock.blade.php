<template id="workspace-template-service_store_stock">
    <div class="workspace-answer-card" data-line-item data-item-type="service_store_stock">
        <div class="workspace-answer-header">
            <div>
                <h6 class="mb-0 small fw-semibold" data-line-title>Rincian</h6>
                <small class="text-muted">Paket servis + produk wajib dari template aktif.</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger py-1" data-remove-line>Hapus</button>
        </div>

        <input type="hidden" name="items[__INDEX__][entry_mode]" value="service">
        <input type="hidden" name="items[__INDEX__][part_source]" value="store_stock">
        <input type="hidden" name="items[__INDEX__][pay_now]" value="0" data-pay-now>
        <input type="hidden" name="items[__INDEX__][pricing_mode]" value="package_auto_split" data-pricing-mode>
        <input type="hidden" name="items[__INDEX__][requires_service_product_template]" value="1" data-requires-service-product-template>
        <input type="hidden" name="items[__INDEX__][service][price_rupiah]" value="0" data-money-raw data-service-price-raw>
        <input type="hidden" name="items[__INDEX__][service][notes]" value="">
        <input type="hidden" value="" data-service-catalog-id>
        <input type="hidden" value="" data-service-default-fee-rupiah>

        <div class="vstack gap-2" data-product-lines>
            <div class="workspace-answer-field" data-product-line>
                <div>
                    <label class="form-label small mb-1">Cari Produk Template</label>
                    <div class="position-relative">
                        <input type="hidden" name="items[__INDEX__][product_lines][0][product_id]" value="" data-product-id>
                        <input type="hidden" name="items[__INDEX__][product_lines][0][price_basis]" value="current_catalog" data-price-basis>
                        <input type="hidden" name="items[__INDEX__][product_lines][0][unit_price_rupiah]" value="" data-money-raw data-price-input>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Cari produk yang punya template aktif"
                            autocomplete="off"
                            data-product-search
                        >
                        <div class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 20;" data-product-results></div>
                    </div>
                    <small class="text-muted">Produk wajib dipilih dari hasil pencarian template aktif.</small>
                </div>

                <div class="mt-3">
                    <label class="form-label small mb-1">Qty</label>
                    <input
                        type="text"
                        inputmode="numeric"
                        name="items[__INDEX__][product_lines][0][qty]"
                        value="1"
                        class="form-control form-control-sm text-center px-1 fw-semibold"
                        style="width: 3rem;"
                        data-qty-input
                    >
                </div>

                <div class="mt-3">
                    <small class="text-muted me-3" data-stock-text>Stok tersedia: -</small>
                    <small class="text-muted me-3" data-min-price-text>Harga produk mengikuti katalog.</small>
                    <small class="text-danger d-none" data-stock-error>Qty melebihi stok tersedia.</small>
                    <small class="text-danger d-none" data-min-price-warning>Harga tidak boleh di bawah minimum.</small>
                </div>
            </div>
        </div>

        <div class="workspace-answer-field mt-3">
            <label class="form-label small mb-1">Nama Paket/Jasa dari Template</label>
            <input
                type="text"
                name="items[__INDEX__][service][name]"
                value=""
                class="form-control form-control-sm"
                placeholder="Terisi otomatis setelah produk dipilih"
                autocomplete="off"
                readonly
                data-service-name
                data-template-service-name
            >
            <div class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 20;" data-service-results></div>
            <small class="text-muted">Nama jasa dikunci dari template, bukan diketik manual.</small>
        </div>

        <div class="workspace-answer-field mt-3">
            <div data-money-input-group>
                <label class="form-label small mb-1">Total Paket</label>
                <input type="hidden" name="items[__INDEX__][package_total_rupiah]" value="" data-money-raw>
                <input
                    type="text"
                    inputmode="numeric"
                    value=""
                    class="form-control form-control-sm"
                    placeholder="Terisi otomatis dari template"
                    data-money-display
                    data-package-total-input
                >
            </div>
            <small class="text-muted">
                Default dari template. Boleh dinaikkan, tapi tidak boleh turun sampai jasa di bawah default template.
            </small>
        </div>

        <small class="text-muted d-block mt-2">
            Untuk darurat tanpa template, input sebagai 2 baris terpisah: Servis biasa + Produk biasa.
        </small>
    </div>
</template>
