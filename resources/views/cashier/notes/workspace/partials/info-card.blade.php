<section class="workspace-panel workspace-detail-panel" aria-label="Info nota">
    <div class="workspace-field-grid">
        <div class="workspace-field workspace-field-wide">
            <label for="note_customer_name" class="form-label">Nama Pelanggan</label>
            <input
                type="text"
                id="note_customer_name"
                name="note[customer_name]"
                value="{{ $oldNote['customer_name'] }}"
                class="form-control"
                placeholder="Contoh: {{ $defaultCustomerName }}"
            >
        </div>

        <div class="workspace-field">
            <label for="note_customer_phone" class="form-label">No. HP Pelanggan</label>
            <input
                type="tel"
                inputmode="tel"
                id="note_customer_phone"
                name="note[customer_phone]"
                value="{{ $oldNote['customer_phone'] }}"
                class="form-control"
                placeholder="Contoh: 0812xxxx"
            >
        </div>

        <div class="workspace-field">
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
</section>
