<details class="workspace-step-card" open>
    <summary class="workspace-step-header workspace-details-summary">
        <span class="workspace-step-number">4</span>
        <div class="flex-grow-1">
            <h4 class="workspace-step-title">Review & Pembayaran</h4>
            <p class="workspace-step-help">
                Cek total, lalu pilih bayar penuh, bayar sebagian, atau simpan tanpa pembayaran.
            </p>
        </div>
        <span class="workspace-details-toggle" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
        </span>
    </summary>

    <div class="workspace-step-body">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <div class="small text-muted">Total Biaya Nota</div>
                <div class="fs-4 fw-bold lh-sm" id="workspace-note-total-text">0</div>
            </div>

            <div class="ui-form-actions justify-content-sm-end">
                <button type="button" class="btn btn-primary" id="workspace-open-payment-dialog">
                    Proses Nota
                </button>

                @if (($workspaceMode ?? 'create') === 'edit' && ($canShowRefundModal ?? false))
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#workspace-refund-modal">
                        Pengembalian Dana
                    </button>
                @endif

                <a href="{{ $cancelAction ?? route('cashier.notes.index') }}" class="btn btn-outline-secondary">
                    Batal
                </a>
            </div>
        </div>
    </div>
</details>
