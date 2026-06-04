<div class="workspace-step-card">
    <div class="workspace-step-header">
        <span class="workspace-step-number">2</span>
        <div class="flex-grow-1">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3">
                <div>
                    <h4 class="workspace-step-title">Buat Rincian Nota</h4>
                    <p class="workspace-step-help">Setiap rincian tampil seperti jawaban form yang bisa ditambah sesuai kebutuhan.</p>
                </div>

                <div class="position-relative workspace-add-question-wrap">
                    <button type="button" class="btn workspace-add-question-button w-100" id="workspace-add-button">
                        <span class="workspace-add-question-icon" aria-hidden="true">+</span>
                        Tambah Rincian
                    </button>
                    @include('cashier.notes.workspace.partials.item-type-menu')
                </div>
            </div>
        </div>
    </div>

    <div class="workspace-step-body">
        <div id="workspace-line-items" data-next-index="{{ count($oldItems) }}"></div>

        <div id="workspace-empty-state" class="workspace-empty-answer text-center text-muted">
            Belum ada rincian. Tekan tombol tambah dan pilih jenis rincian yang sesuai.
        </div>
    </div>
</div>

@include('cashier.notes.workspace.partials.templates.product')
@include('cashier.notes.workspace.partials.templates.service')
@include('cashier.notes.workspace.partials.templates.service-store-stock')
@include('cashier.notes.workspace.partials.templates.service-external')
