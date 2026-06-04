<div class="card">
  <div class="card-header">
    <h4 class="card-title mb-1">Tindakan Nota</h4>
    <p class="mb-0 text-muted">Edit nota atau pilih refund dari rincian yang diklik.</p>
  </div>
  <div class="card-body">
    <div class="d-grid gap-2">
      @if ($note['can_edit_workspace'] ?? false)
        <a
          href="{{ route('cashier.notes.workspace.edit', ['noteId' => $note['id'] ?? ($note['note_header']['id'] ?? null)]) }}"
          class="btn btn-outline-secondary"
        >
          Edit Nota
        </a>
      @endif

      @if ($note['can_show_refund_form'] ?? false)
        <button
          type="button"
          class="btn btn-outline-warning opacity-50"
          id="note-refund-open-button"
          disabled
          aria-disabled="true"
          style="pointer-events:none;"
        >
          Refund Line Terpilih
        </button>
      @endif
    </div>

    @if ($note['can_show_refund_form'] ?? false)
      <div class="border rounded p-3 mt-3 bg-light">
        <div class="fw-semibold mb-1">Status Refund</div>
        <div class="small text-muted">Klik rincian pada tabel untuk mengaktifkan tombol refund.</div>
        <div class="small text-muted mt-2">Rincian terpilih akan ditandai lebih jelas.</div>
      </div>
    @endif
  </div>
</div>
