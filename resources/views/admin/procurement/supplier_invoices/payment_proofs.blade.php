@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Pembayaran Nota Pemasok')
@section('heading', 'Pembayaran Nota Pemasok')
@section('back_url', route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => $summaryView['supplier_invoice_id']]))

@section('content')
    <section class="section">
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                @if ($policyView['is_voided'])
                    <div class="alert alert-secondary">
                        Nota ini sudah dibatalkan. Halaman pembayaran bersifat baca-saja.
                    </div>
                @endif

                @if ($summaryView['can_record_payment'] && ! $policyView['is_voided'])
                    <div class="card" id="payment-form-section">
                        <div class="card-header">
                            <h4 class="card-title mb-1">Catat Pembayaran</h4>
                            <p class="mb-0 text-muted">
                                Gunakan halaman ini untuk melihat status pembayaran, mencatat pembayaran baru, dan mengelola bukti bayar.
                            </p>
                        </div>

                        <div class="card-body">
                            @error('supplier_payment')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror

                            <form action="{{ route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => $summaryView['supplier_invoice_id']]) }}" method="post">
                                @csrf

                                <div class="form-group mb-4">
                                    <label for="payment_date" class="form-label">Tanggal Pembayaran</label>
                                    <input
                                        type="date"
                                        data-ui-date="single"
                                        id="payment_date"
                                        name="payment_date"
                                        value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                                        class="form-control @error('payment_date') is-invalid @enderror"
                                        required
                                    >
                                    @error('payment_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-4" data-money-input-group>
                                    <label for="amount_display" class="form-label">Nominal Pembayaran</label>

                                    <input
                                        type="hidden"
                                        id="amount"
                                        name="amount"
                                        value="{{ old('amount', $summaryView['outstanding_amount']) }}"
                                        data-money-raw
                                    >

                                    <input
                                        type="text"
                                        id="amount_display"
                                        value="{{ old('amount', $summaryView['outstanding_amount']) }}"
                                        class="form-control @error('amount') is-invalid @enderror"
                                        placeholder="Contoh: 150.000"
                                        inputmode="numeric"
                                        data-money-display
                                        required
                                    >

                                    @error('amount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="ui-form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        Simpan Pembayaran
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="card" id="payment-proof-section">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Bukti Pembayaran</h4>
                    </div>

                    <div class="card-body">
                        @error('supplier_payment_proof')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        @error('proof_files')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        @error('proof_files.*')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        @if ($paymentsView === [])
                            <div class="text-muted">Belum ada pembayaran pemasok.</div>
                        @else
                            <div class="d-flex flex-column gap-3">
                                @foreach ($paymentsView as $payment)
                                    <div class="border rounded p-3">
                                        <div class="ui-key-value mb-2">
                                            <small>ID Pembayaran</small>
                                            <strong>{{ $payment['id'] }}</strong>
                                        </div>

                                        <div class="ui-key-value mb-2">
                                            <small>Tanggal Pembayaran</small>
                                            <strong>{{ \App\Support\ViewDateFormatter::display($payment['paid_at'] ?? null) }}</strong>
                                        </div>

                                        <div class="ui-key-value mb-2">
                                            <small>Nominal</small>
                                            <strong>{{ $payment['amount_label'] }}</strong>
                                        </div>

                                        <div class="ui-key-value mb-2">
                                            <small>Status Bukti</small>
                                            <strong>{{ $payment['proof_status_label'] }}</strong>
                                        </div>

                                        <div class="ui-key-value mb-3">
                                            <small>Jumlah Lampiran</small>
                                            <strong>{{ $payment['attachment_count'] }}</strong>
                                        </div>

                                        @if (! $policyView['is_voided'])
                                            <form
                                                action="{{ route('admin.procurement.supplier-payments.proof.store', ['supplierPaymentId' => $payment['id']]) }}"
                                                method="post"
                                                enctype="multipart/form-data"
                                            >
                                                @csrf

                                                <div class="form-group mb-3">
                                                    <label class="form-label" for="proof_files_{{ $payment['id'] }}">File Bukti</label>
                                                    <input
                                                        type="file"
                                                        id="proof_files_{{ $payment['id'] }}"
                                                        name="proof_files[]"
                                                        class="form-control @error('proof_files') is-invalid @enderror @error('proof_files.*') is-invalid @enderror"
                                                        accept=".jpg,.jpeg,.png,.pdf"
                                                        multiple
                                                        required
                                                    >
                                                    <small class="text-muted d-block mt-1">
                                                        Maksimal 3 file per unggahan. Format: JPG, JPEG, PNG, PDF. Maksimal 2 MB per file.
                                                    </small>
                                                </div>

                                                <div class="ui-form-actions">
                                                    <button type="submit" class="btn btn-light-primary">
                                                        Unggah Bukti
                                                    </button>
                                                </div>
                                            </form>

                                            <hr>
                                        @endif

                                        <div>
                                            <small class="text-muted d-block mb-2">Riwayat Lampiran</small>

                                            @if ($payment['attachments'] === [])
                                                <div class="text-muted">Belum ada lampiran bukti.</div>
                                            @else
                                                <div class="d-flex flex-column gap-2">
                                                    @foreach ($payment['attachments'] as $attachment)
                                                        <div class="border rounded p-2">
                                                            @if (str_starts_with($attachment['mime_type'], 'image/'))
                                                                <div class="mb-2">
                                                                    <img
                                                                        src="{{ route('admin.procurement.supplier-payment-proof-attachments.show', ['attachmentId' => $attachment['id']]) }}"
                                                                        alt="{{ $attachment['original_filename'] }}"
                                                                        class="img-fluid rounded border"
                                                                        style="max-height: 180px;"
                                                                    >
                                                                </div>
                                                            @endif

                                                            <div><strong>{{ $attachment['original_filename'] }}</strong></div>
                                                            <div class="small text-muted">Tipe Berkas: {{ $attachment['mime_type'] }}</div>
                                                            <div class="small text-muted">Ukuran: {{ number_format($attachment['file_size_bytes']) }} byte</div>
                                                            <div class="small text-muted">Diunggah Pada: {{ $attachment['uploaded_at'] }}</div>
                                                            <div class="small text-muted">Diunggah Oleh: {{ $attachment['uploaded_by_actor_id'] }}</div>

                                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                                @if ($attachment['mime_type'] === 'application/pdf')
                                                                    <a
                                                                        href="{{ route('admin.procurement.supplier-payment-proof-attachments.show', ['attachmentId' => $attachment['id']]) }}"
                                                                        target="_blank"
                                                                        rel="noopener"
                                                                        class="btn btn-sm btn-outline-primary"
                                                                    >
                                                                        Lihat PDF
                                                                    </a>
                                                                @elseif (str_starts_with($attachment['mime_type'], 'image/'))
                                                                    <a
                                                                        href="{{ route('admin.procurement.supplier-payment-proof-attachments.show', ['attachmentId' => $attachment['id']]) }}"
                                                                        target="_blank"
                                                                        rel="noopener"
                                                                        class="btn btn-sm btn-outline-primary"
                                                                    >
                                                                        Lihat Gambar
                                                                    </a>
                                                                @else
                                                                    <a
                                                                        href="{{ route('admin.procurement.supplier-payment-proof-attachments.show', ['attachmentId' => $attachment['id']]) }}"
                                                                        target="_blank"
                                                                        rel="noopener"
                                                                        class="btn btn-sm btn-outline-primary"
                                                                    >
                                                                        Lihat Berkas
                                                                    </a>
                                                                @endif

                                                                <a
                                                                    href="{{ route('admin.procurement.supplier-payment-proof-attachments.show', ['attachmentId' => $attachment['id'], 'download' => 1]) }}"
                                                                    class="btn btn-sm btn-outline-secondary"
                                                                >
                                                                    Unduh
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Header Faktur</h4>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Status Pembayaran</small>
                            <span class="badge {{ $paymentStatusView['badge_class'] }}">{{ $paymentStatusView['label'] }}</span>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Jumlah Pembayaran</small>
                            <strong>{{ $paymentStatusView['payment_count'] }}</strong>
                        </div>

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
                            <small class="text-muted d-block">Total Nota</small>
                            <strong>{{ $summaryView['grand_total_label'] }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Total Dibayar</small>
                            <strong>{{ $summaryView['total_paid_label'] }}</strong>
                        </div>

                        <div>
                            <small class="text-muted d-block">Sisa Tagihan</small>
                            <strong>{{ $summaryView['outstanding_label'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script>
        window.AdminMoneyInput?.bindBySelector(document);
    </script>
@endpush
