<template id="workspace-template-service_external">
    <div class="workspace-answer-card" data-line-item data-item-type="service_external">
        <div class="workspace-answer-header">
            <div>
                <h6 class="mb-0" data-line-title>Rincian</h6>
                <small class="text-muted">Servis dengan pembelian sparepart dari luar.</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-line>Hapus</button>
        </div>

        <input type="hidden" name="items[__INDEX__][entry_mode]" value="service">
        <input type="hidden" name="items[__INDEX__][part_source]" value="external_purchase">
        <input type="hidden" name="items[__INDEX__][pay_now]" value="0" data-pay-now>
        <input type="hidden" name="items[__INDEX__][service][notes]" value="">
        <input type="hidden" value="" data-service-catalog-id>
        <input type="hidden" value="" data-service-default-fee-rupiah>

        <div class="workspace-answer-field">
            <label class="form-label">Nama Servis</label>
            <div class="position-relative">
                <input
                    type="text"
                    name="items[__INDEX__][service][name]"
                    value=""
                    class="form-control"
                    placeholder="Contoh: Setting In Kecil"
                    autocomplete="off"
                    data-service-name
                >
                <div class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 20;" data-service-results></div>
            </div>
        </div>

        <div class="workspace-answer-field" data-money-input-group>
            <label class="form-label">Harga Servis (Rupiah)</label>
            <input type="hidden" name="items[__INDEX__][service][price_rupiah]" value="" data-money-raw data-service-price-raw>
            <input type="text" inputmode="numeric" value="" class="form-control" placeholder="Contoh: 80.000" data-money-display data-service-price-display>
        </div>

        <div class="workspace-answer-field">
            <label class="form-label">Nama Part Luar</label>
            <input type="text" name="items[__INDEX__][external_purchase_lines][0][label]" value="" class="form-control" placeholder="Contoh: Bearing NTN">
        </div>

	        <div class="workspace-answer-field" data-money-input-group>
	            <label class="form-label">Total Biaya Keluar (Rupiah)</label>
	            <input type="hidden" name="items[__INDEX__][external_purchase_lines][0][total_rupiah]" value="" data-money-raw data-external-total-rupiah>
	            <input type="text" inputmode="numeric" value="" class="form-control" placeholder="Contoh: 120.000" data-money-display>
	        </div>
	    </div>
</template>
