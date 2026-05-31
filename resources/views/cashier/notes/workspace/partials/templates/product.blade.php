<template id="workspace-template-product">
    <div class="border rounded px-3 py-2 mb-2" data-line-item data-item-type="product">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
            <div>
                <h6 class="mb-0 small fw-semibold" data-line-title>Rincian</h6>
                <small class="text-muted">Produk dari stok toko.</small>
            </div>
            <button type="button" class="btn btn-sm btn-light-danger py-1" data-remove-line>Hapus</button>
        </div>

        <input type="hidden" name="items[__INDEX__][entry_mode]" value="product">
        <input type="hidden" name="items[__INDEX__][part_source]" value="none">
        <input type="hidden" name="items[__INDEX__][pay_now]" value="0" data-pay-now>
        <input type="hidden" name="items[__INDEX__][description]" value="">

        <div class="row g-2 align-items-start" data-product-line>
            <div class="col-12 col-lg-8">
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

            <div class="col-8 col-lg-3">
                <label class="form-label small mb-1">Qty</label>
                <div class="input-group input-group-sm workspace-qty-control rounded-pill overflow-hidden">
                    <button type="button" class="btn btn-outline-secondary px-3 fw-bold" data-qty-decrement>-</button>
                    <input
                        type="text"
                        inputmode="numeric"
                        name="items[__INDEX__][product_lines][0][qty]"
                        value="1"
                        class="form-control text-center px-1 fw-semibold"
                        data-qty-input
                    >
                    <button type="button" class="btn btn-outline-secondary px-3 fw-bold" data-qty-increment>+</button>
                </div>
            </div>

            <div class="col-12">
                <small class="text-muted me-3" data-stock-text>Stok tersedia: -</small>
                <small class="text-muted me-3" data-min-price-text>Harga produk mengikuti katalog.</small>
                <small class="text-danger d-none" data-stock-error>Qty melebihi stok tersedia.</small>
                <small class="text-danger d-none" data-min-price-warning>Harga tidak boleh di bawah minimum.</small>
            </div>
        </div>
    </div>
</template>
