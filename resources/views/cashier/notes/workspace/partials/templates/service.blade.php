<template id="workspace-template-service">
    <article class="workspace-line-card" data-line-item data-item-type="service">
        <div class="workspace-line-header">
            <div>
                <div class="workspace-line-kind">Servis</div>
                <h5 class="workspace-line-title" data-line-title>Servis</h5>
            </div>
            <div class="workspace-line-header-actions">
                <strong class="workspace-line-total"><span class="workspace-currency">Rp</span><span data-line-total-text>0</span></strong>
                <button type="button" class="workspace-remove-button" data-remove-line aria-label="Hapus rincian">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>

        <input type="hidden" name="items[__INDEX__][entry_mode]" value="service">
        <input type="hidden" name="items[__INDEX__][part_source]" value="none">
        <input type="hidden" name="items[__INDEX__][pay_now]" value="0" data-pay-now>
        <input type="hidden" name="items[__INDEX__][service][notes]" value="">
        <input type="hidden" value="" data-service-catalog-id>
        <input type="hidden" value="" data-service-default-fee-rupiah>

        <div class="workspace-search-stage" data-service-search-stage>
            <label class="form-label">Cari servis</label>
            <div class="workspace-search-wrap">
                <i class="bi bi-search workspace-search-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    name="items[__INDEX__][service][name]"
                    value=""
                    class="form-control"
                    placeholder="Ketik nama servis"
                    autocomplete="off"
                    enterkeyhint="search"
                    data-service-name
                >
                <div class="workspace-search-results d-none" data-service-results></div>
            </div>
        </div>

        <div class="workspace-selected-card d-none" data-service-selected>
            <div class="workspace-selected-main">
                <div class="workspace-selected-icon"><i class="bi bi-tools"></i></div>
                <div class="workspace-selected-copy">
                    <strong data-selected-service-name>Servis terpilih</strong>
                    <span data-selected-service-price></span>
                </div>
                <button type="button" class="workspace-change-button" data-service-change>Ganti</button>
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
    </article>
</template>
