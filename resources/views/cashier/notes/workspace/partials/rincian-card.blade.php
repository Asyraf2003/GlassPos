<section class="workspace-panel workspace-entry-panel">
    <div class="workspace-panel-heading">
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
                <span class="workspace-type-icon" aria-hidden="true">
                    @switch($option['type'])
                        @case('product')
                            <i class="bi bi-box-seam"></i>
                            @break
                        @case('service')
                            <i class="bi bi-tools"></i>
                            @break
                        @case('service_store_stock')
                            <i class="bi bi-wrench-adjustable-circle"></i>
                            @break
                        @default
                            <i class="bi bi-bag-plus"></i>
                    @endswitch
                </span>
                <span>{{ $option['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="workspace-lines-heading">
        <div>
            <div class="workspace-panel-eyebrow">Rincian aktif</div>
            <div class="small text-muted">Cari lalu pilih item yang akan masuk ke nota.</div>
        </div>
        <span class="workspace-line-count" id="workspace-line-count">0 item</span>
    </div>

    <div id="workspace-line-items" data-next-index="{{ count($oldItems) }}"></div>

    <div id="workspace-empty-state" class="workspace-empty-state">
        <i class="bi bi-receipt" aria-hidden="true"></i>
        <span>Pilih salah satu jenis transaksi untuk mulai membuat nota.</span>
    </div>
</section>

@include('cashier.notes.workspace.partials.templates.product')
@include('cashier.notes.workspace.partials.templates.service')
@include('cashier.notes.workspace.partials.templates.service-store-stock')
@include('cashier.notes.workspace.partials.templates.service-external')
