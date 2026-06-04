<div class="workspace-step-card">
    <div class="workspace-step-header">
        <span class="workspace-step-number">3</span>
        <div class="flex-grow-1">
            <h4 class="workspace-step-title">Alasan & Keterangan Nota</h4>
            <p class="workspace-step-help">
                Isi setelah rincian dibuat supaya catatan mengikuti konteks transaksi.
            </p>
        </div>
    </div>

    <div class="workspace-step-body">
        <div class="workspace-note-card">
            <label for="note_operational_note" class="form-label">Keterangan Nota</label>
            <textarea
                id="note_operational_note"
                name="note[operational_note]"
                rows="4"
                class="form-control"
                placeholder="Contoh: alasan, keluhan, instruksi, atau catatan umum nota"
            >{{ $oldNote['operational_note'] ?? '' }}</textarea>
        </div>
    </div>
</div>
