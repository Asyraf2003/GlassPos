<details class="workspace-step-card" open>
    <summary class="workspace-step-header workspace-details-summary">
        <span class="workspace-step-number">3</span>
        <div class="flex-grow-1">
            <h4 class="workspace-step-title">Alasan & Keterangan Nota</h4>
            <p class="workspace-step-help">
                Isi setelah rincian dibuat supaya catatan mengikuti konteks transaksi.
            </p>
        </div>
        <span class="workspace-details-toggle" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
        </span>
    </summary>

    <div class="workspace-step-body">
        @if (($workspaceMode ?? 'create') === 'edit')
            <div class="workspace-note-card mb-3">
                <label for="note_revision_reason" class="form-label">Alasan Perubahan Nota</label>
                <textarea
                    id="note_revision_reason"
                    name="reason"
                    rows="3"
                    class="form-control @error('reason') is-invalid @enderror"
                    placeholder="Contoh: salah input harga, revisi item, atau koreksi setelah review"
                >{{ old('reason') }}</textarea>
                @error('reason')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Akan tampil di Riwayat Perubahan Nota.</div>
            </div>
        @endif

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
</details>
