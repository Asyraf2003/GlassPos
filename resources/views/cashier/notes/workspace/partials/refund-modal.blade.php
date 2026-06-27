@if (($workspaceMode ?? 'create') === 'edit' && ($canShowRefundModal ?? false))
    <div class="modal fade" id="workspace-refund-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Pengembalian Dana Nota</h5>
                        <p class="mb-0 text-muted small">
                            Pilih rincian yang ingin dikembalikan dananya. Sumber pembayaran dan nominal pengembalian diselesaikan otomatis oleh sistem.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <form method="POST" action="{{ $refundAction }}">
                    @csrf

                    <div class="modal-body">
                        @if ($errors->has('refund') || $errors->has('selected_row_ids') || $errors->has('refunded_at') || $errors->has('reason'))
                            <div class="alert alert-danger py-2 px-3 mb-4">
                                @if ($errors->has('refund'))
                                    <div>{{ $errors->first('refund') }}</div>
                                @endif
                                @if ($errors->has('selected_row_ids'))
                                    <div>{{ $errors->first('selected_row_ids') }}</div>
                                @endif
                                @if ($errors->has('refunded_at'))
                                    <div>{{ $errors->first('refunded_at') }}</div>
                                @endif
                                @if ($errors->has('reason'))
                                    <div>{{ $errors->first('reason') }}</div>
                                @endif
                            </div>
                        @endif

                        <div class="border rounded p-3 mb-4">
                            <div class="fw-semibold mb-2">Rincian yang Bisa Dikembalikan Dananya</div>
                            <div class="d-flex flex-column gap-2">
                                @foreach ($workspaceRefundRows as $row)
                                    <label class="border rounded px-3 py-2 d-flex align-items-start gap-2">
                                        <input
                                            type="checkbox"
                                            class="form-check-input mt-1"
                                            name="selected_row_ids[]"
                                            value="{{ $row['id'] }}"
                                            {{ in_array($row['id'], old('selected_row_ids', []), true) ? 'checked' : '' }}
                                        >
                                        <span>
                                            <span class="d-block fw-semibold">
                                                Rincian {{ $row['line_no'] }} · {{ $row['type_label'] }}
                                            </span>
                                            <small class="text-muted d-block">
                                                Status: {{ $row['line_status'] }} ·
                                                Dibayar: {{ number_format((int) ($row['net_paid_rupiah'] ?? 0), 0, ',', '.') }} ·
                                                Subtotal: {{ number_format((int) ($row['subtotal_rupiah'] ?? 0), 0, ',', '.') }}
                                            </small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label for="workspace_refund_refunded_at" class="form-label">Tanggal Pengembalian Dana</label>
                            <input
                                type="date"
                                data-ui-date="single"
                                id="workspace_refund_refunded_at"
                                name="refunded_at"
                                value="{{ old('refunded_at', $refundDateDefault) }}"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="form-group mb-0">
                            <label for="workspace_refund_reason" class="form-label">Alasan Pengembalian Dana</label>
                            <textarea
                                id="workspace_refund_reason"
                                name="reason"
                                rows="3"
                                class="form-control"
                                placeholder="Jelaskan alasan pengembalian dana"
                                required
                            >{{ old('reason') }}</textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <div class="ui-form-actions w-100 justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Batal
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Catat Pengembalian Dana
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
