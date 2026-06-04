<template id="workspace-template-product">
    <div class="workspace-answer-card" data-line-item data-item-type="product">
        <div class="workspace-answer-header">
            <div>
                <h6 class="mb-0 small fw-semibold" data-line-title>Rincian</h6>
                <small class="text-muted">Produk dari stok toko.</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger py-1" data-remove-line>Hapus</button>
        </div>

        <input type="hidden" name="items[__INDEX__][entry_mode]" value="product">
        <input type="hidden" name="items[__INDEX__][part_source]" value="none">
        <input type="hidden" name="items[__INDEX__][pay_now]" value="0" data-pay-now>
        <input type="hidden" name="items[__INDEX__][description]" value="">

        <div class="workspace-answer-field" data-product-line>
            <div>
                <label class="form-label small mb-1">Produk</label>
                <div class="position-relative">
                    <input type="hidden" name="items[__INDEX__][product_lines][0][product_id]" value="" data-product-id>
                    <input type="hidden" name="items[__INDEX__][product_lines][0][price_basis]" value="current_catalog" data-price-basis>
                    <input type="hidden" name="items[__INDEX__][product_lines][0][unit_price_rupiah]" value="" data-money-raw data-price-input>
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        placeholder="Cari produk"
                        autocomplete="off"
                        data-product-search
                    >
                    <div class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 20;" data-product-results></div>
                </div>
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
</template>
