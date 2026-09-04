<template id="workspace-template-service_store_stock">
    <article class="workspace-line-card" data-line-item data-item-type="service_store_stock">
        <div class="workspace-line-header">
            <div>
                <div class="workspace-line-kind">Servis + Sparepart Toko</div>
                <h5 class="workspace-line-title" data-line-title>Servis + Sparepart Toko</h5>
            </div>
            <div class="workspace-line-header-actions">
                <strong class="workspace-line-total"><span class="workspace-currency">Rp</span><span data-line-total-text>0</span></strong>
                <button type="button" class="workspace-remove-button" data-remove-line aria-label="Hapus rincian">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>

        <input type="hidden" name="items[__INDEX__][entry_mode]" value="service">
        <input type="hidden" name="items[__INDEX__][part_source]" value="store_stock">
        <input type="hidden" name="items[__INDEX__][pay_now]" value="0" data-pay-now>
        <input type="hidden" name="items[__INDEX__][pricing_mode]" value="package_auto_split" data-pricing-mode>
        <input type="hidden" name="items[__INDEX__][requires_service_product_template]" value="1" data-requires-service-product-template>
        <input type="hidden" name="items[__INDEX__][service][notes]" value="">
        <input type="hidden" value="" data-service-catalog-id>
        <input type="hidden" value="" data-service-default-fee-rupiah>
        <input type="hidden" name="items[__INDEX__][service][name]" value="" data-service-name data-template-service-name>
        <input type="hidden" name="items[__INDEX__][service][price_rupiah]" value="0" data-money-raw data-service-price-raw>
        <input type="hidden" value="" data-service-price-display>

        <div class="workspace-search-stage" data-package-search-stage>
            <label class="form-label">Cari paket servis</label>
            <div class="workspace-search-wrap">
                <i class="bi bi-search workspace-search-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    class="form-control"
                    placeholder="Ketik servis atau sparepart"
                    autocomplete="off"
                    enterkeyhint="search"
                    data-package-search
                >
                <div class="workspace-search-results d-none" data-package-results></div>
            </div>
            <small class="workspace-search-hint" data-detail-only>Hanya paket aktif. Maksimal 3 sparepart per paket.</small>
            <small class="text-danger d-none" data-package-error>Paket wajib dipilih.</small>
        </div>

        <div class="workspace-selected-card d-none" data-package-selected-section>
            <div class="workspace-selected-main">
                <div class="workspace-selected-copy">
                    <strong data-package-title>Paket terpilih</strong>
                    <span data-package-description></span>
                    <span data-package-stock-text></span>
                </div>
                <button type="button" class="workspace-change-button" data-package-change>Ganti</button>
            </div>
            <div class="workspace-package-products" data-package-product-list></div>
        </div>

        <div class="d-none" data-product-lines>
            <div data-product-line>
                <input type="hidden" name="items[__INDEX__][product_lines][0][product_id]" value="" data-product-id>
                <input type="hidden" name="items[__INDEX__][product_lines][0][price_basis]" value="current_catalog" data-price-basis>
                <input type="hidden" name="items[__INDEX__][product_lines][0][unit_price_rupiah]" value="" data-money-raw data-price-input>
                <input type="hidden" name="items[__INDEX__][product_lines][0][qty]" value="1" data-qty-input>
                <input type="hidden" value="" data-product-search>
                <small class="text-danger d-none" data-stock-error>Qty melebihi stok tersedia.</small>
                <small class="text-danger d-none" data-min-price-warning>Harga tidak boleh di bawah minimum.</small>
                <small class="text-muted d-none" data-stock-text>Stok tersedia: -</small>
                <small class="text-muted d-none" data-min-price-text>Harga produk mengikuti katalog.</small>
            </div>

            <template data-product-line-template>
                <div data-product-line>
                    <input type="hidden" name="items[__INDEX__][product_lines][__PRODUCT_INDEX__][product_id]" value="" data-product-id>
                    <input type="hidden" name="items[__INDEX__][product_lines][__PRODUCT_INDEX__][price_basis]" value="current_catalog" data-price-basis>
                    <input type="hidden" name="items[__INDEX__][product_lines][__PRODUCT_INDEX__][unit_price_rupiah]" value="" data-money-raw data-price-input>
                    <input type="hidden" name="items[__INDEX__][product_lines][__PRODUCT_INDEX__][qty]" value="1" data-qty-input>
                    <input type="hidden" value="" data-product-search>
                    <small class="text-danger d-none" data-stock-error>Qty melebihi stok tersedia.</small>
                    <small class="text-danger d-none" data-min-price-warning>Harga tidak boleh di bawah minimum.</small>
                    <small class="text-muted d-none" data-stock-text>Stok tersedia: -</small>
                    <small class="text-muted d-none" data-min-price-text>Harga produk mengikuti katalog.</small>
                    <button type="button" class="d-none" data-remove-product-line>Hapus</button>
                </div>
            </template>
        </div>
    </article>
</template>
