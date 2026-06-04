<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-1">Edit Rincian</h5>

        @if (($note['can_edit_workspace'] ?? false) && $note['can_add_rows'])
            <div class="border rounded p-3 bg-light mb-3">
                <div class="fw-semibold mb-1">Edit nota tersedia</div>
                <div class="text-muted small">
                    Gunakan form edit untuk menambah atau memperbaiki rincian nota.
                </div>
            </div>

            <div class="ui-form-actions">
                <a
                    href="{{ route('cashier.notes.workspace.edit', ['noteId' => $note['id']]) }}"
                    class="btn btn-outline-primary"
                >
                    Lanjut Edit Nota
                </a>
            </div>
        @else
            <div class="border rounded p-3 bg-light">
                <div class="fw-semibold mb-1">Workspace tidak aktif</div>

                @if ($note['is_closed'])
                    <div class="text-muted small">
                        Edit rincian tidak aktif untuk nota yang sudah close. Gunakan pembayaran dan refund dari halaman detail ini.
                    </div>
                @elseif ($note['correction_notice'] !== null)
                    <div class="text-muted small">{{ $note['correction_notice'] }}</div>
                @else
                    <div class="text-muted small">
                        Gunakan tindakan yang tersedia pada halaman detail nota ini.
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
