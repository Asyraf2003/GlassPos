<template id="workspace-template-product">
    <article class="workspace-line-card" data-line-item data-item-type="product">
        <div class="workspace-line-header">
            <div>
                <div class="workspace-line-kind">Produk</div>
                <h5 class="workspace-line-title" data-line-title>Produk</h5>
            </div>
            <div class="workspace-line-header-actions">
                <strong class="workspace-line-total"><span class="workspace-currency">Rp</span><span data-line-total-text>0</span></strong>
                <button type="button" class="workspace-remove-button" data-remove-line aria-label="Hapus rincian">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>

        <input type="hidden" name="items[__INDEX__][entry_mode]" value="product">
        <input type="hidden" name="items[__INDEX__][part_source]" value="none">
        <input type="hidden" name="items[__INDEX__][pay_now]" value="0" data-pay-now>
        <input type="hidden" name="items[__INDEX__][description]" value="">

        <div data-product-line>
            <input type="hidden" name="items[__INDEX__][product_lines][0][product_id]" value="" data-product-id>
            <input type="hidden" name="items[__INDEX__][product_lines][0][price_basis]" value="current_catalog" data-price-basis>
            <input type="hidden" name="items[__INDEX__][product_lines][0][unit_price_rupiah]" value="" data-money-raw data-price-input>

            <div class="workspace-search-stage" data-product-search-stage>
                <label class="form-label" for="workspace-product-search-__INDEX__">Cari produk</label>
                <div class="workspace-search-wrap">
                    <i class="bi bi-search workspace-search-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="workspace-product-search-__INDEX__"
                        class="form-control"
                        placeholder="Ketik nama, merek, ukuran, atau kode"
                        autocomplete="off"
                        enterkeyhint="search"
                        data-product-search
                    >
                    <div class="workspace-search-results d-none" data-product-results></div>
                </div>
                <small class="workspace-search-hint">Ketik minimal 2 karakter.</small>
            </div>

            <div class="workspace-selected-card d-none" data-product-selected>
                <div class="workspace-selected-main">
                    <div class="workspace-selected-icon"><i class="bi bi-box-seam"></i></div>
                    <div class="workspace-selected-copy">
                        <strong data-selected-product-name>Produk terpilih</strong>
                        <span data-selected-product-meta></span>
                        <span data-selected-product-price-stock></span>
                    </div>
                    <button type="button" class="workspace-change-button" data-product-change>Ganti</button>
                </div>

                <div class="workspace-qty-row">
                    <span class="workspace-qty-label">Jumlah</span>
                    <div class="workspace-qty-control">
                        <button type="button" data-qty-decrement aria-label="Kurangi jumlah">−</button>
                        <input
                            type="text"
                            inputmode="numeric"
                            name="items[__INDEX__][product_lines][0][qty]"
                            value="1"
                            aria-label="Jumlah produk"
                            data-qty-input
                        >
                        <button type="button" data-qty-increment aria-label="Tambah jumlah">+</button>
                    </div>
                </div>

                <div class="workspace-line-warning-row">
                    <small class="text-muted" data-stock-text>Stok tersedia: -</small>
                    <small class="text-muted" data-min-price-text>Harga produk mengikuti katalog.</small>
                    <small class="text-danger d-none" data-stock-error>Qty melebihi stok tersedia.</small>
                    <small class="text-danger d-none" data-min-price-warning>Harga tidak boleh di bawah minimum.</small>
                </div>
            </div>
        </div>
    </article>
</template>
