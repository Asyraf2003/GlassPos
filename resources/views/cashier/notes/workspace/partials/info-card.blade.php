<details class="workspace-step-card" open>
    <summary class="workspace-step-header workspace-details-summary">
        <span class="workspace-step-number">1</span>
        <div class="flex-grow-1">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h4 class="workspace-step-title">Info Nota</h4>
                    <p class="workspace-step-help">
                        Isi identitas customer dan tanggal kerja sebelum menambah rincian.
                    </p>
                </div>

                <span class="badge bg-light text-dark border">
                    {{ ($workspaceMode ?? 'create') === 'edit' ? 'Mode Edit' : 'Mode Buat' }}
                </span>
            </div>
        </div>
        <span class="workspace-details-toggle">Lipat</span>
    </summary>

    <div class="workspace-step-body">
        <div class="row g-3">
            <div class="col-12">
                <label for="note_customer_name" class="form-label">Nama Customer</label>
                <input
                    type="text"
                    id="note_customer_name"
                    name="note[customer_name]"
                    value="{{ $oldNote['customer_name'] }}"
                    class="form-control"
                    placeholder="Contoh: {{ $defaultCustomerName }}"
                >
            </div>

            <div class="col-12">
                <label for="note_customer_phone" class="form-label">No. HP Customer</label>
                <input
                    type="text"
                    id="note_customer_phone"
                    name="note[customer_phone]"
                    value="{{ $oldNote['customer_phone'] }}"
                    class="form-control"
                    placeholder="Contoh: 0812xxxx"
                >
            </div>

            <div class="col-12">
                <label for="note_transaction_date" class="form-label">Tanggal Nota</label>
                <input
                    type="date"
                    data-ui-date="single"
                    id="note_transaction_date"
                    name="note[transaction_date]"
                    value="{{ $oldNote['transaction_date'] }}"
                    class="form-control"
                >
            </div>
        </div>

        @if (($workspaceMode ?? 'create') === 'edit')
            <div class="small text-muted mt-3">
                Edit tetap memakai workspace yang sama agar struktur kerja tidak berubah saat revisi nota.
            </div>
        @endif
    </div>
</details>
