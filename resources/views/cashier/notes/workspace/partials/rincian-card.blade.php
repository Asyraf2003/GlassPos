<div class="workspace-step-card">
    <div class="workspace-step-header">
        <span class="workspace-step-number">2</span>
        <div class="flex-grow-1">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3">
                <div>
                    <h4 class="workspace-step-title">Rincian Nota</h4>
                    <p class="workspace-step-help">Tambah produk, servis, atau paket sesuai kasus customer.</p>
                </div>

                <div class="position-relative">
                    <button type="button" class="btn btn-primary w-100" id="workspace-add-button">
                        Tambah Rincian
                    </button>
                    @include('cashier.notes.workspace.partials.item-type-menu')
                </div>
            </div>
        </div>
    </div>

    <div class="workspace-step-body">
        <div id="workspace-line-items" data-next-index="{{ count($oldItems) }}"></div>

        <div id="workspace-empty-state" class="border rounded p-4 text-center text-muted">
            Belum ada rincian. Tekan tombol tambah dan pilih jenis rincian yang sesuai.
        </div>
    </div>
</div>

@include('cashier.notes.workspace.partials.templates.product')
@include('cashier.notes.workspace.partials.templates.service')
@include('cashier.notes.workspace.partials.templates.service-store-stock')
@include('cashier.notes.workspace.partials.templates.service-external')
