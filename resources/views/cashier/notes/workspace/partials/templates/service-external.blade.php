<template id="workspace-template-service_external">
    <article class="workspace-line-card" data-line-item data-item-type="service_external">
        <div class="workspace-line-header">
            <div>
                <div class="workspace-line-kind">Servis + Pembelian Luar</div>
                <h5 class="workspace-line-title" data-line-title>Servis + Pembelian Luar</h5>
            </div>
            <div class="workspace-line-header-actions">
                <strong class="workspace-line-total"><span class="workspace-currency">Rp</span><span data-line-total-text>0</span></strong>
                <button type="button" class="workspace-remove-button" data-remove-line aria-label="Hapus rincian">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>

        <input type="hidden" name="items[__INDEX__][entry_mode]" value="service">
        <input type="hidden" name="items[__INDEX__][part_source]" value="external_purchase">
        <input type="hidden" name="items[__INDEX__][pay_now]" value="0" data-pay-now>
        <input type="hidden" name="items[__INDEX__][service][notes]" value="">
        <input type="hidden" value="" data-service-catalog-id>
        <input type="hidden" value="" data-service-default-fee-rupiah>

        <div class="workspace-external-grid">
            <div class="workspace-search-stage" data-service-search-stage>
                <label class="form-label">Nama Servis</label>
                <div class="workspace-search-wrap">
                    <i class="bi bi-search workspace-search-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        name="items[__INDEX__][service][name]"
                        value=""
                        class="form-control"
                        placeholder="Cari atau ketik servis"
                        autocomplete="off"
                        data-service-name
                    >
                    <div class="workspace-search-results d-none" data-service-results></div>
                </div>
            </div>

            <div class="workspace-money-field" data-money-input-group>
                <label class="form-label">Harga Servis</label>
                <input type="hidden" name="items[__INDEX__][service][price_rupiah]" value="" data-money-raw data-service-price-raw>
                <div class="workspace-money-prefix">
                    <span>Rp</span>
                    <input type="text" inputmode="numeric" value="" class="form-control" placeholder="0" data-money-display data-service-price-display>
                </div>
            </div>

            <div class="workspace-field">
                <label class="form-label">Nama Part Luar</label>
                <input type="text" name="items[__INDEX__][external_purchase_lines][0][label]" value="" class="form-control" placeholder="Contoh: Bearing NTN">
            </div>

            <div class="workspace-money-field" data-money-input-group>
                <label class="form-label">Total Pembelian Luar</label>
                <input type="hidden" name="items[__INDEX__][external_purchase_lines][0][total_rupiah]" value="" data-money-raw data-external-total-rupiah>
                <div class="workspace-money-prefix">
                    <span>Rp</span>
                    <input type="text" inputmode="numeric" value="" class="form-control" placeholder="0" data-money-display>
                </div>
            </div>
        </div>
    </article>
</template>
