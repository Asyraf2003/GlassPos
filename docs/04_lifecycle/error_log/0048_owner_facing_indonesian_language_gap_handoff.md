# Handoff 0047 — Owner-Facing Indonesian Language Gap

## Status

Analisis awal sudah dilakukan via connector GitHub dan diperkuat dengan output lokal `rg` dari owner. Output lokal sangat besar, jadi file ini sengaja merangkum area eksekusi agar sesi berikutnya tidak tenggelam di banjir data. Karena tentu saja repo memilih bicara seperti database yang ikut rapat operasional.

## Source Proof

Owner menjalankan command pencarian bahasa Inggris/internal-facing terhadap area:

```bash
fd -t f '\.(php|js)$' app resources public/assets/static public/assets/compiled tests \
  | rg -v '(/vendor/|/node_modules/|/storage/|/bootstrap/cache/)' \
  | xargs -r rg -n --no-heading -S --max-columns 120 --max-columns-preview '(Refund Due|Refund Paid|Surplus Refund Paid|billing row|Billing Row|billing projection row|outstanding|Outstanding|allocation|allocated|Alokasi Pembayaran|Pembayaran Dialokasikan|Bruto|Gross|Net|Versioning|Revision|Reason:|Amount\b|Line\b|Tipe Domain|source_table|Tabel Sumber|ID Sumber|Disposisi Sumber|existing|clear|preset|refund flow|refunded|close\.|Close)'
```

## Decision

Eksekusi harus dilanjutkan di sesi baru, slice kecil, satu area per langkah. Jangan patch semua sekaligus.

Prioritas aman:

1. Slice 1: detail note payment/billing UI.
2. Slice 2: refund due/refund paid UI dan audit timeline.
3. Slice 3: transaction report screen + Excel/PDF.
4. Slice 4: versioning/revision labels.
5. Slice 5: cash ledger source metadata labels/export.
6. Slice 6: supplier/procurement report labels yang ikut ketemu dari scan, tapi jangan dicampur dengan transaksi nota.

## Hard Boundaries

Jangan ubah:

- enum database,
- nama kolom,
- DTO keys,
- route names,
- hidden input names,
- request field names,
- public contract test yang memang memvalidasi struktur data internal,
- input user seperti nama customer, nama service, produk, deskripsi, reason/catatan yang user tulis sendiri.

Yang boleh diubah:

- Blade text visible,
- JS-rendered visible text,
- export sheet title/header visible,
- PDF view labels,
- presenter/formatter label mapping,
- feature test assertion untuk teks UI/export.

## Slice 1 Target — Detail Note Payment/Billing UI

Target utama:

- `resources/views/cashier/notes/partials/billing-table.blade.php`
- `resources/views/cashier/notes/partials/payment-modal.blade.php`
- `public/assets/static/js/pages/cashier-note-payment.js`
- tests yang assert label lama terkait billing/payment detail.

### Known bad visible strings

Ubah hanya teks visible, bukan key data.

| Lama | Rekomendasi Baru |
|---|---|
| `Billing Row` | `Rincian Tagihan` |
| `Line` | `Rincian` / `Baris` |
| `Tipe Domain` | `Jenis Transaksi` |
| `billing row` | `rincian tagihan` |
| `billing projection row` | `rincian tagihan` |
| `allocation` | `pencatatan pembayaran` / `pembayaran tercatat` |
| `outstanding` | `sisa tagihan` |
| `Outstanding Terpilih` | `Sisa Tagihan Terpilih` |
| `existing` | `yang sudah ada` |
| `preset DP` | `prioritas DP` |
| `clear` | `lunas/selesai` sesuai konteks |

### Notes

- `data-outstanding-rupiah`, `outstanding_rupiah`, `eligible_for_dp_preset` tetap, karena itu internal attribute/key.
- `data-label="Line ..."` perlu hati-hati: itu visible lewat JS render row summary. Boleh ubah ke `Rincian ...`.
- `service` boleh sementara tetap, karena bengkel umum paham. Kalau ingin Indonesia penuh, jadikan slice terpisah.

## Slice 1 Command Plan

Jalankan dulu command fokus ini di sesi baru:

```bash
rg -n --no-heading -S '(Billing Row|billing row|billing projection row|Outstanding|outstanding|allocation|Line\b|Tipe Domain|existing|preset|clear)'   resources/views/cashier/notes/partials/billing-table.blade.php   resources/views/cashier/notes/partials/payment-modal.blade.php   public/assets/static/js/pages/cashier-note-payment.js   tests/Feature/Note
```

## Slice 1 Expected Patch

Minimal patch:

- Blade payment modal:
  - `Data billing row tetap dikirim hidden untuk allocation.` → `Rincian tagihan aktif disiapkan otomatis untuk pencatatan pembayaran.`
  - `Belum ada tagihan outstanding.` → `Belum ada sisa tagihan.`
  - `Outstanding Terpilih` → `Sisa Tagihan Terpilih`
  - `Default mengikuti ... outstanding terpilih.` → `Default mengikuti ... sisa tagihan terpilih.`
  - `Line { ... }` visible label → `Rincian { ... }`

- JS payment modal:
  - `Belum ada tagihan outstanding.` → `Belum ada sisa tagihan.`

- Billing table:
  - `Billing Row` → `Rincian Tagihan`
  - `Line` → `Rincian`
  - `Tipe Domain` → `Jenis Transaksi`
  - `Ikuti urutan tagihan existing.` → `Ikuti urutan tagihan yang sudah ada.`
  - `Masuk prioritas preset DP.` → `Masuk prioritas DP.`
  - `Bisa dipilih manual setelah komponen sebelumnya clear.` → `Bisa dipilih manual setelah komponen sebelumnya selesai.`
  - `Belum ada billing projection row untuk nota ini.` → `Belum ada rincian tagihan untuk nota ini.`

## Slice 1 Proof

Setelah patch, jalankan:

```bash
rg -n --no-heading -S '(Billing Row|billing row|billing projection row|Outstanding|outstanding|allocation|Line\b|Tipe Domain|existing|preset|clear)'   resources/views/cashier/notes/partials/billing-table.blade.php   resources/views/cashier/notes/partials/payment-modal.blade.php   public/assets/static/js/pages/cashier-note-payment.js   tests/Feature/Note
```

Lalu jalankan test yang relevan dari hasil `rg`, minimal kandidat:

```bash
php artisan test   tests/Feature/Note/CashierDetailRenderedBillingRowsPaymentFeatureTest.php   tests/Feature/Note/CashierNoteDetailSimplePaymentModalUxFeatureTest.php   tests/Feature/Note/NoteDetailPageFeatureTest.php
```

Kalau test gagal karena assertion teks lama, update assertion ke teks baru selama assertion itu memang UI/export visible text.

## Other High-Signal Findings From Owner rg

```text
  | xargs -r rg -n --no-heading -S --max-columns 120 --max-columns-preview '(Refund Due|Refund Paid|Surplus Refund Paid|billing row|Billing Row|billing projection row|outstanding|Outstanding|allocation|allocated|Alokasi Pembayaran|Pembayaran Dialokasikan|Bruto|Gross|Net|Versioning|Revision|Reason:|Amount\b|Line\b|Tipe Domain|source_table|Tabel Sumber|ID Sumber|Disposisi Sumber|existing|clear|preset|refund flow|refunded|close\.|Close)'
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:61:        $this->assertSame('Tabel Sumber', $detail->getCell('K1')->getValue());
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:62:        $this->assertSame('ID Sumber', $detail->getCell('L1')->getValue());
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:63:        $this->assertSame('ID Disposisi Sumber', $detail->getCell('M1')->getValue());
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:66:        $this->assertSame('Alokasi Pembayaran', $detail->getCell('E2')->getValue());
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:70:        $this->assertSame('payment_allocations', $detail->getCell('K2')->getValue());
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:154:        DB::table('payment_allocations')->insert([
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:155:            'id' => 'payment-allocation-' . $paymentId,
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:161:        DB::table('payment_component_allocations')->insert([
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:169:            'allocated_amount_rupiah' => $amountRupiah,
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:170:            'allocation_priority' => 1,
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:179:        string $refundedAt,
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:188:            'refunded_at' => $refundedAt,
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:192:        DB::table('refund_component_allocations')->insert([
tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php:200:            'refunded_amount_rupiah' => $amountRupiah,
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:75:                    'event_type' => 'Alokasi Pembayaran',
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:79:                    'source_table' => 'customer_payments',
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:95:        $this->assertStringNotContainsString('Tabel Sumber', $html);
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:116:                    'event_type' => 'Alokasi Pembayaran',
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:120:                    'source_table' => 'customer_payments',
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:168:        DB::table('payment_allocations')->insert([
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:169:            'id' => 'payment-allocation-' . $paymentId,
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:175:        DB::table('payment_component_allocations')->insert([
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:183:            'allocated_amount_rupiah' => $amountRupiah,
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:184:            'allocation_priority' => 1,
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:193:        string $refundedAt,
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:202:            'refunded_at' => $refundedAt,
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:206:        DB::table('refund_component_allocations')->insert([
tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php:214:            'refunded_amount_rupiah' => $amountRupiah,
tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php:27:        $this->seedPaymentAllocation('allocation-1', 'payment-1', 'note-1', 99999);
tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php:28:        $this->seedPaymentAllocation('allocation-2', 'payment-2', 'note-2', 50000);
tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php:29:        $this->seedPaymentAllocation('allocation-outside', 'payment-outside', 'note-outside', 30000);
tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php:63:        $this->assertSame('Total Surplus Refund Paid', $summary->getCell('A11')->getValue());
tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php:65:        $this->assertSame('Total Sisa Refund Due', $summary->getCell('A12')->getValue());
tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php:77:        $this->assertSame('Surplus Refund Paid', $detail->getCell('I1')->getValue());
tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php:79:        $this->assertSame('Sisa Refund Due', $detail->getCell('J1')->getValue());
tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php:152:        DB::table('payment_allocations')->insert([
tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php:165:        string $refundedAt,
tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php:173:            'refunded_at' => $refundedAt,
tests/Feature/Note/CashierNoteDetailUsesCurrentRevisionLinesFeatureTest.php:55:            ->assertSee('Revision Aktif');
tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php:232:            'name' => 'Refund Paid Actor',
tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php:266:            'customer_name' => 'Customer Refund Paid HTTP',
tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php:284:            'customer_name' => 'Customer Refund Paid HTTP',
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:26:        $this->seedPaymentAllocation('allocation-1', 'payment-1', 'note-1', 99999);
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:27:        $this->seedPaymentAllocation('allocation-2', 'payment-2', 'note-2', 50000);
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:28:        $this->seedPaymentAllocation('allocation-outside', 'payment-outside', 'note-outside', 30000);
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:80:                ['label' => 'Refund Due', 'value' => 'Rp 0'],
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:81:                ['label' => 'Surplus Refund Paid', 'value' => 'Rp 0'],
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:82:                ['label' => 'Sisa Refund Due', 'value' => 'Rp 0'],
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:83:                ['label' => 'Net Dibayar', 'value' => 'Rp 140.999'],
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:98:                    'outstanding' => 'Rp 9.001',
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:107:        $this->assertStringContainsString('Refund Due', $html);
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:108:        $this->assertStringContainsString('Surplus Refund Paid', $html);
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:109:        $this->assertStringContainsString('Sisa Refund Due', $html);
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:125:                ['label' => 'Nilai Bruto Transaksi', 'value' => 'Rp 150.000'],
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:141:                    'outstanding' => 'Rp 9.001',
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:191:        DB::table('payment_allocations')->insert([
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:204:        string $refundedAt,
tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php:212:            'refunded_at' => $refundedAt,
tests/Feature/Note/CashierNoteRevisionCleanupFeatureTest.php:29:            ->assertSee('Versioning Nota')
tests/Feature/Note/NoteDetailPageShowsExternalPurchaseCorrectionHistoryFeatureTest.php:28:        $response->assertSee('Versioning Nota');
tests/Feature/Note/NoteDetailPageShowsExternalPurchaseCorrectionHistoryFeatureTest.php:29:        $response->assertSee('Revision Aktif');
tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php:248:            'name' => 'Refund Due Actor',
tests/Feature/Note/CashierNoteRevisionSmokeTest.php:28:            ->assertSee('Versioning Nota')
tests/Feature/Note/CashierNoteRevisionSmokeTest.php:29:            ->assertSee('Revision Aktif');
tests/Feature/Note/CashierNoteRevisionSmokeTest.php:66:            ->assertSee('Versioning Nota')
tests/Feature/Note/CashierNoteRevisionSmokeTest.php:67:            ->assertSee('Revision Aktif');
tests/Feature/Note/CashierDetailRenderedBillingRowsPaymentFeatureTest.php:121:            'name' => 'Kasir Rendered Billing Rows',
tests/Feature/Note/CashierNoteVersioningLineSnapshotViewFeatureTest.php:29:        $response->assertSee('Versioning Nota');
tests/Feature/Note/CashierNoteVersioningLineSnapshotViewFeatureTest.php:30:        $response->assertSee('Isi Revision Aktif');
tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php:29:                    'customer_name' => 'Budi Refund Due Revised',
tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php:39:                            'name' => 'Servis Later Refund Due Revision',
tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php:79:        $this->seedNoteBase('note-refund-due-001', 'Budi Refund Due', '2026-05-13', 143000, 'open');
tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php:92:            'Servis Before Refund Due',
tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php:101:            'Budi Refund Due',
tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php:104:            'Servis Before Refund Due',
tests/Feature/Note/CashierNoteDetailSimplePaymentModalUxFeatureTest.php:41:        $response->assertDontSee('Billing Row yang Bisa Dipilih');
tests/Feature/Note/CashierNoteDetailSimplePaymentModalUxFeatureTest.php:45:        $response->assertDontSee('Billing Row Dipilih');
tests/Feature/Note/CashierEditPageUsesCurrentRevisionFeatureTest.php:39:                        'name' => 'Servis Revision Aktif',
tests/Feature/Note/CashierEditPageUsesCurrentRevisionFeatureTest.php:56:            ->assertSee('Servis Revision Aktif');
tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php:53:        $response->assertSee('Riwayat Refund Due');
tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php:54:        $response->assertSee('Refund Due Ditandai');
tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php:113:    $response->assertSee('Refund Due Ditandai');
tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php:114:    $response->assertSee('Refund Paid Dicatat');
tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php:137:        $response->assertDontSee('Riwayat Refund Due');
tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php:138:        $response->assertDontSee('Refund Due Ditandai');
resources/views/cashier/notes/partials/billing-table.blade.php:8:      <span class="badge border">{{ count($note['billing_rows'] ?? []) }} Billing Row</span>
resources/views/cashier/notes/partials/billing-table.blade.php:16:            <th>Line</th>
resources/views/cashier/notes/partials/billing-table.blade.php:17:            <th>Tipe Domain</th>
resources/views/cashier/notes/partials/billing-table.blade.php:41:              <td class="text-end">{{ number_format((int) ($row['refunded_rupiah'] ?? 0), 0, ',', '.') }}</td>
resources/views/cashier/notes/partials/billing-table.blade.php:42:              <td class="text-end">{{ number_format((int) ($row['outstanding_rupiah'] ?? 0), 0, ',', '.') }}</td>
resources/views/cashier/notes/partials/billing-table.blade.php:47:                  <div class="small text-muted">{{ $row['selection_blocked_reason'] ?? 'Ikuti urutan tagihan existing.'  [... 0 more matches]
resources/views/cashier/notes/partials/billing-table.blade.php:48:                @elseif ($row['eligible_for_dp_preset'] ?? false)
resources/views/cashier/notes/partials/billing-table.blade.php:49:                  <div class="small text-muted">Masuk prioritas preset DP.</div>
resources/views/cashier/notes/partials/billing-table.blade.php:51:                  <div class="small text-muted">Bisa dipilih manual setelah komponen sebelumnya clear.</div>
resources/views/cashier/notes/partials/billing-table.blade.php:57:              <td colspan="9" class="text-center text-muted py-4">Belum ada billing projection row untuk nota ini.</td>
tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php:88:            'Servis Before Refund Paid',
tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php:99:            'Servis Before Refund Paid',
tests/Feature/Note/AdminNoteDetailPageFeatureTest.php:37:            ->assertSee('Versioning Nota');
tests/Feature/Note/AdminNoteDetailPageFeatureTest.php:62:            ->assertSee('Versioning Nota');
tests/Feature/Note/CashierNoteMutationHistoryViewFeatureTest.php:28:            ->assertSee('Versioning Nota')
tests/Feature/Note/CashierNoteMutationHistoryViewFeatureTest.php:29:            ->assertSee('Revision Aktif')
tests/Feature/Note/CashierHybridNoteDetailFeatureTest.php:28:            ->assertSee('Versioning Nota')
tests/Feature/Note/CashierHybridNoteDetailFeatureTest.php:29:            ->assertSee('Revision Aktif')
tests/Feature/Note/CashierHybridNoteDetailFeatureTest.php:43:            ->assertSee('Versioning Nota')
tests/Feature/Note/CashierHybridNoteDetailFeatureTest.php:44:            ->assertSee('Revision Aktif');
tests/Feature/Note/AdminNoteSurplusRefundPaidUiFeatureTest.php:50:        $response->assertSee('Catat Refund Paid');
tests/Feature/Note/NoteCorrectionHistoryPageFeatureTest.php:28:        $response->assertSee('Versioning Nota');
tests/Feature/Note/NoteCorrectionHistoryPageFeatureTest.php:29:        $response->assertSee('Revision Aktif');
tests/Feature/Note/NoteDetailPageShowsNativeCorrectionHistoryFeatureTest.php:28:        $response->assertSee('Versioning Nota');
tests/Feature/Note/NoteDetailPageShowsNativeCorrectionHistoryFeatureTest.php:29:        $response->assertSee('Revision Aktif');
resources/views/cashier/notes/partials/payment-modal.blade.php:35:            @if (!($row['is_paid'] ?? false) && (int) ($row['outstanding_rupiah'] ?? 0) > 0)
resources/views/cashier/notes/partials/payment-modal.blade.php:41:                data-label="Line {{ $row['line_no'] }} · {{ $row['component_label'] }}"
resources/views/cashier/notes/partials/payment-modal.blade.php:43:                data-outstanding-rupiah="{{ (int) ($row['outstanding_rupiah'] ?? 0) }}"
resources/views/cashier/notes/partials/payment-modal.blade.php:46:                data-eligible-dp="{{ ($row['eligible_for_dp_preset'] ?? false) ? '1' : '0' }}"
resources/views/cashier/notes/partials/payment-modal.blade.php:61:                        Tagihan aktif dipilih otomatis. Data billing row tetap dikirim hidden untuk allocation.
resources/views/cashier/notes/partials/payment-modal.blade.php:70:                    <div class="p-3 text-muted small">Belum ada tagihan outstanding.</div>
resources/views/cashier/notes/partials/payment-modal.blade.php:79:                    <span class="text-muted">Outstanding Terpilih</span>
resources/views/cashier/notes/partials/payment-modal.blade.php:99:                      Default mengikuti komponen service jika ada. Jika tidak ada service, default mengikuti outstanding [... 0 more matches]
tests/Feature/Note/CashierNoteSurplusRefundDueUiAccessFeatureTest.php:41:        $response->assertDontSee('Tandai Refund Due');
tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php:40:        $response->assertSee('Tandai Refund Due');
tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php:52:        $response->assertSee('data-loading-text="Menyimpan Refund Due..."', false);
tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php:73:        $response->assertDontSee('Tandai Refund Due');
public/assets/static/js/pages/cashier-note-payment.js:49:    selectedRows().reduce((sum, row) => sum + digits(row.dataset.outstandingRupiah), 0);
public/assets/static/js/pages/cashier-note-payment.js:51:  const typedPartialAmount = () => {
public/assets/static/js/pages/cashier-note-payment.js:56:  const payableAmount = () => {
public/assets/static/js/pages/cashier-note-payment.js:63:    const typed = typedPartialAmount();
public/assets/static/js/pages/cashier-note-payment.js:84:    const typed = typedPartialAmount();
public/assets/static/js/pages/cashier-note-payment.js:100:      target.innerHTML = '<div class="p-3 text-muted small">Belum ada tagihan outstanding.</div>';
public/assets/static/js/pages/cashier-note-payment.js:112:            <strong>${format(digits(row.dataset.outstandingRupiah))}</strong>
public/assets/static/js/pages/cashier-note-payment.js:180:    const payable = payableAmount();
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php:28:                'event_type' => 'payment_allocation',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php:34:                'source_table' => 'payment_component_allocations',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php:35:                'source_id' => 'allocation-cash-001',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php:42:                'event_type' => 'payment_allocation',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php:48:                'source_table' => 'payment_component_allocations',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php:49:                'source_id' => 'allocation-transfer-001',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php:37:                    'event_type' => 'payment_allocation',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php:43:                    'source_table' => 'payment_component_allocations',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php:44:                    'source_id' => 'allocation-cash-001',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php:51:                    'event_type' => 'payment_allocation',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php:57:                    'source_table' => 'payment_component_allocations',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php:58:                    'source_id' => 'allocation-transfer-001',
resources/views/admin/reporting/transaction_summary/index.blade.php:42:            <div class="text-muted small">Nilai Bruto Transaksi</div>
resources/views/admin/reporting/transaction_summary/index.blade.php:49:            <div class="text-muted small">Pembayaran Dialokasikan</div>
resources/views/admin/reporting/transaction_summary/index.blade.php:50:            <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['allocated_payment_rupiah'] ?? 0, 0, ',' [... 0 more matches]
resources/views/admin/reporting/transaction_summary/index.blade.php:57:            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['refunded_rupiah'] ?? 0, 0, ',', '.') }}< [... 0 more matches]
resources/views/admin/reporting/transaction_summary/index.blade.php:70:            <div class="text-muted small">Refund Due</div>
resources/views/admin/reporting/transaction_summary/index.blade.php:77:            <div class="text-muted small">Surplus Refund Paid</div>
resources/views/admin/reporting/transaction_summary/index.blade.php:84:            <div class="text-muted small">Sisa Refund Due</div>
resources/views/admin/reporting/transaction_summary/index.blade.php:92:            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['outstanding_rupiah'] ?? 0, 0, ',', '.')  [... 0 more matches]
resources/views/admin/reporting/transaction_summary/index.blade.php:108:            <div class="fs-5 fw-bold">{{ number_format($summary['outstanding_rows'] ?? 0, 0, ',', '.') }}</div>
resources/views/admin/reporting/transaction_summary/index.blade.php:128:            <div class="fs-5 fw-bold">{{ number_format($summary['outstanding_rows'] ?? 0, 0, ',', '.') }}</div>
resources/views/admin/reporting/transaction_summary/index.blade.php:135:            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['outstanding_rupiah'] ?? 0, 0, ',', '.')  [... 0 more matches]
resources/views/admin/reporting/transaction_summary/index.blade.php:141:            <div class="text-muted small">Sisa Refund Due</div>
tests/Feature/Reporting/TransactionReportPageFeatureTest.php:179:        $response->assertSee('Surplus Refund Paid');
tests/Feature/Reporting/TransactionReportPageFeatureTest.php:180:        $response->assertSee('Sisa Refund Due');
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:46:        $this->assertSame('Total Refund Due', $summary->getCell('A10')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:49:        $this->assertSame('Refund Due', $detail->getCell('H1')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:52:        $this->assertSame('Refund Due', $period->getCell('F1')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:55:        $this->assertSame('Refund Due', $customer->getCell('F1')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:76:            ['label' => 'Refund Due', 'value' => 'Rp 7.000'],
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:115:        $this->assertSame('Total Surplus Refund Paid', $summary->getCell('A11')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:117:        $this->assertSame('Total Sisa Refund Due', $summary->getCell('A12')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:120:        $this->assertSame('Surplus Refund Paid', $detail->getCell('I1')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:122:        $this->assertSame('Sisa Refund Due', $detail->getCell('J1')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:125:        $this->assertSame('Surplus Refund Paid', $period->getCell('G1')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:127:        $this->assertSame('Sisa Refund Due', $period->getCell('H1')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:130:        $this->assertSame('Surplus Refund Paid', $customer->getCell('G1')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:132:        $this->assertSame('Sisa Refund Due', $customer->getCell('H1')->getValue());
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:150:            ['label' => 'Surplus Refund Paid', 'value' => 'Rp 3.000'],
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:154:            ['label' => 'Sisa Refund Due', 'value' => 'Rp 4.000'],
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:179:        $this->assertStringContainsString('Surplus Refund Paid', $html);
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:180:        $this->assertStringContainsString('Sisa Refund Due', $html);
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:202:                'customer_name' => 'Customer Export Refund Due',
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:222:                'customer_name' => 'Customer Export Refund Due',
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:252:                'customer_name' => 'Customer Export Surplus Refund Paid',
tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php:276:                'customer_name' => 'Customer Export Surplus Refund Paid',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfBladePaymentMethodTest.php:29:                    'event_type' => 'Alokasi Pembayaran',
tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfBladePaymentMethodTest.php:35:                    'source_table' => 'payment_component_allocations',
```

## Prompt Sesi Berikutnya

Gunakan prompt ini di sesi baru:

```text
Kita lanjut eksekusi error log 0047 HyperPOS owner-facing Indonesian language gap.

Konteks: jangan patch semua. Eksekusi 1 slice dulu: detail note payment/billing UI.

Target file slice 1:
- resources/views/cashier/notes/partials/billing-table.blade.php
- resources/views/cashier/notes/partials/payment-modal.blade.php
- public/assets/static/js/pages/cashier-note-payment.js
- tests Feature/Note yang assert label lama terkait billing/payment detail.

Hard boundary:
- Jangan ubah enum/database/DTO key/route/hidden input/request key.
- Jangan ubah input user.
- Hanya ubah visible copy di Blade/JS/export/presenter/test assertion UI.

Mulai dengan validasi data dulu. Beri 1 command rg fokus untuk membuktikan string lama di slice 1. Setelah saya kirim output, baru beri patch command satu langkah. Jangan lompat ke report/refund/versioning dulu.
```

## Context Advice

Sesi ini sudah padat. Lanjut eksekusi di sesi baru lebih aman agar model tidak kebawa semua hasil scan besar dan mulai patch liar.
