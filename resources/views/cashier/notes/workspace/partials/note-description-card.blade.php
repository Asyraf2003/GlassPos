<section class="workspace-panel workspace-detail-panel">
    <div class="workspace-panel-heading">
        <div>
            <div class="workspace-panel-eyebrow">Detail</div>
            <h4 class="workspace-panel-title">Keterangan nota</h4>
        </div>
    </div>

    @if (($workspaceMode ?? 'create') === 'edit')
        <div class="workspace-field mb-3">
            <label for="note_revision_reason" class="form-label">Alasan Perubahan Nota</label>
            <textarea
                id="note_revision_reason"
                name="reason"
                rows="3"
                class="form-control @error('reason') is-invalid @enderror"
                placeholder="Contoh: salah input harga, revisi item, atau koreksi setelah review"
                required
            >{{ old('reason', 'Revisi nota via workspace') }}</textarea>
            @error('reason')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Akan tampil di Riwayat Perubahan Nota.</div>
        </div>
    @endif

    <div class="workspace-field">
        <label for="note_operational_note" class="form-label">Keterangan Nota</label>
        <textarea
            id="note_operational_note"
            name="note[operational_note]"
            rows="3"
            class="form-control"
            placeholder="Keluhan, instruksi, atau catatan operasional"
        >{{ $oldNote['operational_note'] ?? '' }}</textarea>
    </div>
</section>
