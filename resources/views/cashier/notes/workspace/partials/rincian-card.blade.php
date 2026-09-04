<section class="workspace-panel workspace-entry-panel">
    <div class="workspace-panel-heading" data-detail-only>
        <div>
            <div class="workspace-panel-eyebrow">Transaksi</div>
            <h4 class="workspace-panel-title">Pilih jenis transaksi</h4>
            <p class="workspace-panel-help mb-0">Pilihan langsung menambahkan satu rincian ke nota aktif.</p>
        </div>
    </div>

    <div class="workspace-type-grid" id="workspace-type-selector">
        @foreach ($itemTypeOptions as $option)
            <button
                type="button"
                class="workspace-type-choice"
                data-add-item-type="{{ $option['type'] }}"
            >
                <span>{{ $option['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="workspace-lines-heading" data-detail-only>
        <div>
            <div class="workspace-panel-eyebrow">Rincian aktif</div>
            <div class="small text-muted">Cari lalu pilih item yang akan masuk ke nota.</div>
        </div>
        <span class="workspace-line-count" id="workspace-line-count">0 item</span>
    </div>

    <div id="workspace-line-items" data-next-index="{{ count($oldItems) }}"></div>
</section>

@include('cashier.notes.workspace.partials.templates.product')
@include('cashier.notes.workspace.partials.templates.service')
@include('cashier.notes.workspace.partials.templates.service-store-stock')
@include('cashier.notes.workspace.partials.templates.service-external')
