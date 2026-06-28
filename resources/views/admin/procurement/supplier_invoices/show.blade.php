@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Detail Nota Pemasok')
@section('heading', 'Detail Nota Pemasok')
@section('back_url', route('admin.procurement.supplier-invoices.index'))

@section('content')
    <section class="section">
        <div class="row g-4">
            <div class="col-12 col-xl-4 order-2">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <h4 class="card-title mb-1">Ringkasan Nota</h4>
                            </div>

                            <div class="d-flex gap-2">
                                @if (! $policyView['is_voided'])
                                    <a
                                        href="{{ route('admin.procurement.supplier-invoices.payment-proofs.show', ['supplierInvoiceId' => $summaryView['supplier_invoice_id']]) }}"
                                        class="btn btn-sm btn-light-primary"
                                    >
                                        Pembayaran
                                    </a>
                                @endif

                                @if (! $policyView['is_voided'] && $linesView !== [] && (int) ($summaryView['receipt_count'] ?? 0) < 1)
                                    <a href="#receipt-form-section" class="btn btn-sm btn-primary">
                                        Terima Barang
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Status Kebijakan</small>
                            <span class="badge {{ $policyView['badge_class'] }}">{{ $policyView['label'] }}</span>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Aksi yang Diizinkan</small>

                            @if ($policyView['allowed_actions'] === [])
                                <div class="text-muted">Tidak ada aksi yang tersedia.</div>
                            @else
                                <ul class="mb-0 ps-3">
                                    @foreach ($policyView['allowed_actions'] as $actionLabel)
                                        <li>{{ $actionLabel }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            @if (($policyView['primary_action'] ?? null) !== null)
                                <div class="d-grid mt-3">
                                    <a
                                        href="{{ $policyView['primary_action']['url'] }}"
                                        class="{{ $policyView['primary_action']['button_class'] }}"
                                    >
                                        {{ $policyView['primary_action']['label'] }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Alasan Penguncian</small>

                            @if ($policyView['lock_reasons'] === [])
                                <div class="text-muted">Belum ada efek turunan primer.</div>
                            @else
                                <ul class="mb-0 ps-3">
                                    @foreach ($policyView['lock_reasons'] as $reasonLabel)
                                        <li>{{ $reasonLabel }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        @if (! empty($summaryView['latest_revision_reason']))
                            <hr>

                            <div class="mb-3">
                                <small class="text-muted d-block">Perubahan Terakhir</small>
                                <strong>Revisi {{ $summaryView['last_revision_no'] }}</strong>
                                @if (! empty($summaryView['latest_revision_changed_at']))
                                    <small class="text-muted d-block">{{ $summaryView['latest_revision_changed_at'] }}</small>
                                @endif
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">Alasan Perubahan Terakhir</small>
                                <div class="fw-semibold">{{ $summaryView['latest_revision_reason'] }}</div>
                            </div>

                        @endif

                        <hr>

                        <div class="mb-3">
                            <small class="text-muted d-block">Nomor Faktur</small>
                            <strong>{{ $summaryView['nomor_faktur'] !== '' ? $summaryView['nomor_faktur'] : '-' }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">ID Nota Internal</small>
                            <strong>{{ $summaryView['supplier_invoice_id'] }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Nama Pemasok Saat Ini</small>
                            <strong>{{ $summaryView['supplier_nama_pt_pengirim_current'] !== '' ? $summaryView['supplier_nama_pt_pengirim_current'] : '-' }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Nama Saat Nota Dibuat</small>
                            <strong>{{ $summaryView['supplier_nama_pt_pengirim_snapshot'] !== '' ? $summaryView['supplier_nama_pt_pengirim_snapshot'] : '-' }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Tanggal Pengiriman</small>
                            <strong>{{ $summaryView['shipment_date'] ?? '-' }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Tanggal Jatuh Tempo</small>
                            <strong>{{ $summaryView['due_date'] ?? '-' }}</strong>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <small class="text-muted d-block">Subtotal Sebelum Pajak</small>
                            <strong>{{ $summaryView['subtotal_before_tax_label'] }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Pajak Supplier</small>
                            <strong>{{ $summaryView['tax_amount_label'] }}</strong>
                            @if (($summaryView['tax_input'] ?? null) !== null)
                                <small class="text-muted d-block">Input: {{ $summaryView['tax_input'] }}</small>
                            @endif
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Total Nota</small>
                            <strong>{{ $summaryView['grand_total_label'] }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Total Dibayar</small>
                            <strong>{{ $summaryView['total_paid_label'] }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Sisa Tagihan</small>
                            <strong>{{ $summaryView['outstanding_label'] }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Jumlah Penerimaan</small>
                            <strong>{{ $summaryView['receipt_count'] }}</strong>
                        </div>

                        <div>
                            <small class="text-muted d-block">Total Kuantitas Diterima</small>
                            <strong>{{ $summaryView['total_received_qty'] }}</strong>
                        </div>
                    </div>
                </div>

                @if (! $policyView['is_voided'] && $linesView !== [] && (int) ($summaryView['receipt_count'] ?? 0) < 1)
                    <div class="card" id="receipt-form-section">
                        <div class="card-header">
                            <h4 class="card-title mb-1">Terima Barang</h4>
                            <p class="mb-0 text-muted">
                                Gunakan checklist ini jika seluruh barang pada nota sudah datang sesuai jumlah invoice.
                            </p>
                        </div>

                        <div class="card-body">
                            @error('supplier_receipt')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror

                            <form
                                action="{{ route('admin.procurement.supplier-invoices.receive', ['supplierInvoiceId' => $summaryView['supplier_invoice_id']]) }}"
                                method="post"
                                id="supplier-receipt-form"
                            >
                                @csrf

                                <div class="form-group mb-4">
                                    <label for="tanggal_terima" class="form-label">Tanggal Terima</label>
                                    <input
                                        type="date"
                                        data-ui-date="single"
                                        id="tanggal_terima"
                                        name="tanggal_terima"
                                        value="{{ old('tanggal_terima', now()->format('Y-m-d')) }}"
                                        class="form-control @error('tanggal_terima') is-invalid @enderror"
                                        required
                                    >
                                    @error('tanggal_terima')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="border rounded p-3 bg-light-subtle">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            value="1"
                                            id="confirm_full_receive"
                                            data-confirm-full-receive
                                        >
                                        <label class="form-check-label" for="confirm_full_receive">
                                            Saya konfirmasi seluruh barang pada nota ini sudah diterima lengkap sesuai jumlah invoice.
                                        </label>
                                    </div>
                                </div>

                                <div class="alert alert-danger d-none mt-3 mb-0" data-receipt-form-error>
                                    Centang konfirmasi penerimaan penuh sebelum menyimpan penerimaan barang.
                                </div>

                                @foreach ($linesView as $index => $line)
                                    <input
                                        type="hidden"
                                        name="lines[{{ $index }}][supplier_invoice_line_id]"
                                        value="{{ $line['supplier_invoice_line_id'] ?? '' }}"
                                        data-receipt-line-id
                                    >
                                    <input
                                        type="hidden"
                                        name="lines[{{ $index }}][qty_diterima]"
                                        value="{{ $line['qty_pcs'] }}"
                                        data-receipt-line-qty
                                    >
                                @endforeach

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        Simpan Penerimaan Barang
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @elseif (! $policyView['is_voided'] && (int) ($summaryView['receipt_count'] ?? 0) > 0)
                    <div class="card" id="receipt-form-section">
                        <div class="card-header">
                            <h4 class="card-title mb-1">Status Penerimaan Barang</h4>
                            <p class="mb-0 text-muted">
                                Barang pada nota ini sudah diterima dan dicatat ke stok.
                            </p>
                        </div>

                        <div class="card-body">
                            <div class="alert alert-success mb-0">
                                Penerimaan barang sudah tercatat.
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-12 col-xl-8 order-1">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Rincian Nota</h4>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-lg">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode</th>
                                        <th>Nama Barang</th>
                                        <th>Merek</th>
                                        <th>Ukuran</th>
                                        <th>Jumlah</th>
                                        <th>Harga Satuan</th>
                                        <th>Subtotal Sebelum Pajak</th>
                                        <th>Pajak Rincian</th>
                                        <th>Total Rincian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($linesView as $index => $line)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $line['kode_barang'] ?? '-' }}</td>
                                            <td>{{ $line['nama_barang'] }}</td>
                                            <td>{{ $line['merek'] }}</td>
                                            <td>{{ $line['ukuran'] ?? '-' }}</td>
                                            <td>{{ $line['qty_pcs'] }}</td>
                                            <td>
                                                {{ $line['unit_cost_label'] }}
                                                @if (((int) ($line['rounding_residue_rupiah'] ?? 0)) > 0)
                                                    <small class="text-muted d-block">Modal per pcs dibulatkan. Selisih pembulatan disimpan agar total nota tetap sesuai dokumen supplier.</small>
                                                @endif
                                            </td>
                                            <td>{{ $line['line_subtotal_before_tax_label'] }}</td>
                                            <td>
                                                {{ $line['tax_amount_label'] }}
                                                @if (($line['tax_input'] ?? null) !== null)
                                                    <small class="text-muted d-block">Input: {{ $line['tax_input'] }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $line['line_total_label'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">
                                                Belum ada rincian nota.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('supplier-receipt-form');
            if (!form) return;

            const confirmInput = form.querySelector('[data-confirm-full-receive]');
            const errorBox = form.querySelector('[data-receipt-form-error]');

            form.addEventListener('submit', (event) => {
                const confirmed = Boolean(confirmInput?.checked);

                if (!confirmed) {
                    event.preventDefault();
                    if (errorBox) {
                        errorBox.classList.remove('d-none');
                    }
                    return;
                }

                if (errorBox) {
                    errorBox.classList.add('d-none');
                }
            });
        })();
    </script>
@endpush
