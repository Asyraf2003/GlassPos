<section class="workspace-panel workspace-entry-panel" aria-label="Rincian transaksi">
    <div class="workspace-panel-heading" data-detail-only>
        <h4 class="workspace-panel-title">Jenis transaksi</h4>
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
        <h4 class="workspace-panel-title">Rincian</h4>
        <span class="workspace-line-count" id="workspace-line-count">0 item</span>
    </div>

    <div id="workspace-line-items" data-next-index="{{ count($oldItems) }}"></div>
</section>

@include('cashier.notes.workspace.partials.templates.product')
@include('cashier.notes.workspace.partials.templates.service')
@include('cashier.notes.workspace.partials.templates.service-store-stock')
@include('cashier.notes.workspace.partials.templates.service-external')
