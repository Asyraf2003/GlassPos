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

### Session Update - 2026-06-27 Slice 1 Payment Billing UI Indonesianized

#### Scope

- Issue source: `docs/04_lifecycle/error_log/0047_transaction_owner_facing_indonesian_language_gap.md`
- Active slice: Slice 1 - Detail note payment/billing owner-facing Indonesian label cleanup.
- Patch boundary: presentation only.
- Production logic change: none.
- Database enum/column/domain contract/route/request payload/API change: none.
- Mobile/API scope: untouched.
- Compiled assets scope: untouched.

#### Files Changed

- `resources/views/cashier/notes/partials/billing-table.blade.php`
- `resources/views/cashier/notes/partials/payment-modal.blade.php`
- `public/assets/static/js/pages/cashier-note-payment.js`

#### FACT

- Local focused scan before patch showed owner-facing/internal English terms in Slice 1:
  - `Billing Row`
  - `billing projection row`
  - `Line`
  - `Tipe Domain`
  - `existing`
  - `preset DP`
  - `clear`
  - `outstanding`
  - `Outstanding Terpilih`
  - `allocation`
  - `hidden`
  - `Default`
  - `service`
- Patch changed owner-facing labels only.
- Internal contracts were intentionally left unchanged:
  - `type="hidden"`
  - `aria-hidden`
  - `outstanding_rupiah`
  - `data-outstanding-rupiah`
  - `is_service_component`
  - `eligible_for_dp_preset`
  - `dataset.outstandingRupiah`
  - `event.preventDefault`
- Post-patch focused scan no longer shows Slice 1 owner-facing English/internal terms.
- Remaining scan hits are internal field/DOM/JS contract names only.
- `make verify` passed:
  - `1439 passed`
  - `8600 assertions`
  - duration `95.35s`

#### Changes Applied

- `Billing Row` -> `Baris Tagihan`
- `Line` -> `Baris`
- `Tipe Domain` -> `Jenis Tagihan`
- `Ikuti urutan tagihan existing.` -> `Ikuti urutan tagihan sebelumnya.`
- `Masuk prioritas preset DP.` -> `Masuk prioritas pengaturan DP.`
- `Bisa dipilih manual setelah komponen sebelumnya clear.` -> `Bisa dipilih manual setelah komponen sebelumnya lunas.`
- `Belum ada billing projection row untuk nota ini.` -> `Belum ada rincian tagihan untuk nota ini.`
- `Line ...` rendered payment row label -> `Baris ...`
- `Data billing row tetap dikirim hidden untuk allocation.` -> Indonesian owner-facing explanation.
- `Belum ada tagihan outstanding.` -> `Belum ada sisa tagihan.`
- `Outstanding Terpilih` -> `Sisa Tagihan Terpilih`
- `Default mengikuti komponen service... outstanding terpilih.` -> Indonesian owner-facing instruction using `servis` and `sisa tagihan`.

#### DECISION

- Slice 1 is complete and verified.
- Do not translate internal contract names just because they match the broad scan.
- Next slice should stay narrow and avoid reports/export/refund/versioning unless explicitly selected.

#### NEXT CANDIDATE SLICES

- Slice 2 candidate A: refund due/paid modal owner-facing labels.
- Slice 2 candidate B: transaction report PDF/Excel labels.
- Slice 2 candidate C: revision/versioning timeline labels.
- Slice 2 candidate D: cash ledger source metadata labels.

### Session Update - 2026-06-27 Slice 2 Surplus Refund Due/Paid UI Indonesianized

#### Scope

- Issue source: `docs/04_lifecycle/error_log/0047_transaction_owner_facing_indonesian_language_gap.md`
- Active slice: Slice 2 - surplus refund due/paid owner-facing UI and audit timeline labels.
- Patch boundary: presentation/presenter label only.
- Production logic change: none.
- Database enum/column/domain contract/route/request payload/API change: none.
- Report Excel/PDF scope: untouched.
- Mobile/API scope: untouched.
- Compiled assets scope: untouched.

#### Files Changed

- `resources/views/shared/notes/partials/payment-summary-actions.blade.php`
- `app/Application/Note/Services/NoteSurplusDispositionAuditTimelineRowMapper.php`
- `tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php`
- `tests/Feature/Note/AdminNoteSurplusRefundPaidUiFeatureTest.php`
- `tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php`

#### FACT

- Focused scan before patch showed owner-facing/internal English terms in Slice 2:
  - `Refund Due`
  - `Refund Paid`
  - `Amount`
  - `Reason`
- Patch changed owner-facing UI/audit labels only.
- Internal event names and storage contracts were intentionally left unchanged:
  - `note_revision_surplus_refund_due_created`
  - `note_revision_surplus_refund_paid_recorded`
  - `amount_rupiah`
  - `reason`
  - surplus refund payment/disposition identifiers
- Post-patch focused scan on the two production files returned no matches for:
  - `Refund Due`
  - `Refund Paid`
  - `Surplus Refund Paid`
  - `Amount`
  - `Reason`
  - `refund due`
  - `refund paid`
- Focused UI tests passed:
  - `6 passed`
  - `61 assertions`
  - duration `6.72s`

#### Changes Applied

- `Tandai Refund Due` -> `Tandai Pengembalian Belum Dibayar`
- `Refund Due` UI copy -> `Pengembalian Belum Dibayar`
- `Catat Refund Paid` -> `Catat Pengembalian Sudah Dibayar`
- `Refund Paid` UI copy -> `Pengembalian Sudah Dibayar`
- `Riwayat Refund Due` -> `Riwayat Pengembalian Belum Dibayar`
- `Refund Due Ditandai` -> `Pengembalian Belum Dibayar Ditandai`
- `Refund Paid Dicatat` -> `Pengembalian Sudah Dibayar Dicatat`
- `Amount ...` -> `Nominal ...`
- `Reason: ...` -> `Alasan: ...`
- `Sisa refund due` -> `Sisa pengembalian belum dibayar`

#### Tests

- `php artisan test tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php tests/Feature/Note/AdminNoteSurplusRefundPaidUiFeatureTest.php tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php`
- Result: PASS, `6 passed (61 assertions)`.

#### DECISION

- Slice 2 is complete and verified.
- Do not translate internal event names, DB fields, or request fields.
- Error log 0047 remains open/in progress.
- Next slice should stay narrow.

#### NEXT CANDIDATE SLICES

- Slice 3 candidate A: transaction report page labels.
- Slice 3 candidate B: transaction report Excel/PDF export labels.
- Slice 3 candidate C: revision/versioning timeline labels.
- Slice 3 candidate D: cash ledger source metadata labels.

### Session Update - 2026-06-27 Slice 3A Transaction Report Page Indonesianized

#### Scope

- Issue source: `docs/04_lifecycle/error_log/0047_transaction_owner_facing_indonesian_language_gap.md`
- Active slice: Slice 3A - transaction report screen/page owner-facing labels.
- Patch boundary: Blade report page label + related feature test assertion only.
- Production logic change: none.
- Database enum/column/domain contract/route/request payload/API change: none.
- Excel/PDF export scope: untouched.
- Mobile/API scope: untouched.
- Compiled assets scope: untouched.

#### Files Changed

- `resources/views/admin/reporting/transaction_summary/index.blade.php`
- `tests/Feature/Reporting/TransactionReportPageFeatureTest.php`

#### FACT

- Focused scan before patch showed owner-facing English/accounting labels in transaction report page:
  - `Nilai Bruto Transaksi`
  - `Refund Due`
  - `Surplus Refund Paid`
  - `Sisa Refund Due`
- Patch changed visible report page labels only.
- Internal report keys and fixture identifiers were intentionally left unchanged:
  - `allocated_payment_rupiah`
  - `outstanding_rupiah`
  - `outstanding_rows`
  - `payment_allocations`
  - `payment_component_allocations`
  - `allocation_priority`
- Post-patch focused scan returned no matches for:
  - `Refund Due`
  - `Refund Paid`
  - `Surplus Refund Paid`
  - `Sisa Refund Due`
  - `Nilai Bruto Transaksi`
- Focused page test passed:
  - `7 passed`
  - `52 assertions`
  - duration `6.44s`

#### Changes Applied

- `Nilai Bruto Transaksi` -> `Total Nilai Transaksi`
- `Refund Due` -> `Pengembalian Belum Dibayar`
- `Surplus Refund Paid` -> `Pengembalian Surplus Sudah Dibayar`
- `Sisa Refund Due` -> `Sisa Pengembalian Belum Dibayar`

#### Tests

- `php artisan test tests/Feature/Reporting/TransactionReportPageFeatureTest.php`
- Result: PASS, `7 passed (52 assertions)`.

#### DECISION

- Slice 3A is complete and verified.
- Do not translate internal report keys, DB table names, fixture ids, or query fields.
- Error log 0047 remains open/in progress.
- Next slice should stay narrow.

#### NEXT CANDIDATE SLICES

- Slice 3B: transaction report Excel/PDF export labels.
- Slice 4: revision/versioning timeline labels.
- Slice 5: cash ledger source metadata labels/export.

### Session Update - 2026-06-28 Slice 3B Transaction Report Excel/PDF Export Indonesianized

#### Scope

- Issue source: `docs/04_lifecycle/error_log/0047_transaction_owner_facing_indonesian_language_gap.md`
- Active slice: Slice 3B - transaction report Excel/PDF export owner-facing labels.
- Patch boundary: export label/view-data/header text + related export test assertions only.
- Production logic change: none.
- Database enum/column/domain contract/route/request payload/API change: none.
- Transaction report page/screen scope: already handled in Slice 3A.
- Mobile/API scope: untouched.
- Compiled assets scope: untouched.

#### Files Changed

- `app/Application/Reporting/Exports/TransactionReportPdfViewDataBuilder.php`
- `app/Application/Reporting/Exports/TransactionReportExcelSummarySheetWriter.php`
- `app/Application/Reporting/Exports/TransactionReportExcelDetailSheetWriter.php`
- `app/Application/Reporting/Exports/TransactionReportExcelPeriodSheetWriter.php`
- `app/Application/Reporting/Exports/TransactionReportExcelCustomerSheetWriter.php`
- `tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php`
- `tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php`
- `tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php`

#### FACT

- Focused scan before patch showed owner-facing English/accounting labels in transaction report exports:
  - `Nilai Bruto Transaksi`
  - `Total Bruto Transaksi`
  - `Refund Due`
  - `Surplus Refund Paid`
  - `Sisa Refund Due`
- Patch changed visible Excel/PDF export labels only.
- Internal report keys, DTO keys, DB fields, fixture names, and user/customer names were intentionally left unchanged:
  - `refund_due_rupiah`
  - `surplus_refund_paid_rupiah`
  - `remaining_refund_due_rupiah`
  - `gross_transaction_rupiah`
  - `Customer Export Refund Due`
  - `Customer Export Surplus Refund Paid`
- Post-patch focused scan on production export files and related export tests returned no matches for:
  - `Refund Due`
  - `Surplus Refund Paid`
  - `Sisa Refund Due`
  - `Nilai Bruto Transaksi`
  - `Total Bruto Transaksi`
- Focused export tests were run by owner and reported PASS.
- Exact assertion count was not captured in chat output.

#### Changes Applied

- `Nilai Bruto Transaksi` -> `Total Nilai Transaksi`
- `Total Bruto Transaksi` -> `Total Nilai Transaksi`
- `Refund Due` -> `Pengembalian Belum Dibayar`
- `Total Refund Due` -> `Total Pengembalian Belum Dibayar`
- `Surplus Refund Paid` -> `Pengembalian Surplus Sudah Dibayar`
- `Total Surplus Refund Paid` -> `Total Pengembalian Surplus Sudah Dibayar`
- `Sisa Refund Due` -> `Sisa Pengembalian Belum Dibayar`
- `Total Sisa Refund Due` -> `Total Sisa Pengembalian Belum Dibayar`

#### Tests

- `php artisan test tests/Feature/ReportingExports/TransactionReportExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionReportPdfExportFeatureTest.php tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php`
- Result: PASS, owner-reported.

#### DECISION

- Slice 3B is complete and verified by focused scan + owner-reported focused tests.
- Do not translate internal report keys, DB fields, fixture IDs, or customer/user-entered values.
- Error log 0047 remains open/in progress.
- Next slice should stay narrow.

#### NEXT CANDIDATE SLICES

- Slice 4: revision/versioning timeline labels.
- Slice 5: cash ledger source metadata labels/export.
- Slice 6: supplier/procurement report labels that appeared in broad scan, but keep separate from transaction note work.

### Session Update - 2026-06-28 Slice 4B Workspace Edit Revision Reason Input Bound

#### Scope

- Issue source: `docs/04_lifecycle/error_log/0047_transaction_owner_facing_indonesian_language_gap.md`
- Follow-up slice: Slice 4B - workspace edit revision reason input binding.
- Trigger: owner observed `Riwayat Perubahan Nota` always showing default reason `Revisi workspace nota admin` even after typing text in the workspace edit note section.
- Patch boundary: workspace edit UI field + focused feature assertions.
- Production logic change: small UX/data binding fix only.
- Backend request/controller/domain logic change: none.
- Database schema change: none.
- Route/API/mobile/compiled assets change: none.

#### Files Changed

- `resources/views/cashier/notes/workspace/partials/note-description-card.blade.php`
- `tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php`
- `tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php`

#### FACT

- Admin and cashier workspace edit routes submit `PATCH /{noteId}/workspace` to `StoreNoteRevisionController`.
- `StoreNoteRevisionRequest` already accepts top-level `reason`.
- If top-level `reason` is missing or blank, `StoreNoteRevisionRequest` falls back to:
  - `Revisi workspace nota admin` for admin routes.
  - `Revisi workspace nota kasir` for cashier routes.
- Existing workspace UI showed section title `Alasan & Keterangan Nota`, but the only textarea in that section submitted `note[operational_note]`.
- Therefore owner-typed text in that section updated note operational description, not `note_revisions.reason`.
- Patch added a dedicated edit-mode-only textarea:
  - label: `Alasan Perubahan Nota`
  - name: `reason`
  - helper: `Akan tampil di Riwayat Perubahan Nota.`
- Existing `Keterangan Nota` field remains unchanged as `note[operational_note]`.

#### Changes Applied

- Added edit-mode-only `reason` textarea in workspace note description card.
- Added UI assertions that edit workspace page renders:
  - `Alasan Perubahan Nota`
  - `name="reason"`
  - `Akan tampil di Riwayat Perubahan Nota.`
- Updated workspace revision submit test payload to send:
  - `reason => Koreksi manual dari workspace.`
- Added database assertion that the new `note_revisions` row stores:
  - `reason => Koreksi manual dari workspace.`

#### Tests

- `php artisan test tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php`
- Result: PASS, `7 passed (48 assertions)`.
- Duration: `7.02s`.

#### DECISION

- Slice 4B is complete and verified.
- Backend fallback reason remains intact for submissions that do not send `reason`.
- `note[operational_note]` remains separate from `note_revisions.reason`.
- Error log 0047 remains open/in progress unless remaining scan proves no owner-facing gaps remain.

#### NEXT CANDIDATE SLICES

- Slice 5: cash ledger source metadata labels/export.
- Slice 6: final broad owner-facing scan for remaining English/internal terms.

### Session Update - 2026-06-28 Slice 4C/5/6 Owner-Facing Transaction Language Cleanup

#### Scope

- Issue source: `docs/04_lifecycle/error_log/0047_transaction_owner_facing_indonesian_language_gap.md`
- Follow-up handoff: this file.
- Owner addition: every user edit/action that requires a reason must have an `alasan` input and that reason must be displayed in UI.
- Active execution focus: owner-facing Indonesian labels for note transaction UI, revision reason display, billing/detail labels, refund/payment labels, note history summaries, and cash ledger export source labels.
- Boundary kept:
  - no DB enum/column/key/route/input-name renames,
  - no user-entered text translation,
  - no schema change,
  - no compiled asset build.

#### Changes Applied

- Revision reason display:
  - `resources/views/cashier/notes/partials/note-revision-timeline.blade.php`
  - existing stored revision reason now has a visible `Alasan:` label in the timeline.
  - existing workspace edit reason textarea from Slice 4B remains the input source (`name="reason"`).

- Billing/payment/refund wording:
  - `Line/Open/Close/Refund/Cash/Customer/Grand Total/External/outstanding` visible labels were changed to Indonesian owner-facing wording in cashier/admin/shared note views and note payment/refund JS.
  - Examples:
    - `Line` -> `Rincian` / `Baris`
    - `Open` -> `Belum Selesai`
    - `Close` -> `Selesai`
    - `Refund` -> `Pengembalian Dana` / `Dikembalikan`
    - `Cash` -> `Tunai`
    - `Customer` -> `Pelanggan`
    - `Grand Total` -> `Total Nota`
    - `External` -> `Komponen Luar`

- Note history/presenter labels:
  - `app/Adapters/Out/Note/Queries/CashierNoteHistoryValueFormatter.php`
  - `app/Application/Note/Services/NoteLineSummaryBuilder.php`
  - history table JSON/UI labels now return:
    - `1 Belum Selesai`
    - `1 Selesai`
    - `1 Dikembalikan`
    - `Belum Selesai: n • Selesai: n • Batal: n`

- Cash ledger export/source labels:
  - `TransactionCashLedgerExcelDetailSheetWriter`
  - `TransactionCashLedgerPdfViewDataBuilder`
  - visible source headers changed from internal table wording to owner-facing wording:
    - `Tabel Sumber` -> `Asal Catatan`
    - `ID Sumber` -> `ID Asal Catatan`
    - `ID Disposisi Sumber` -> `ID Disposisi Asal`
  - source table values are mapped to labels such as:
    - `Pembayaran Nota`
    - `Pembayaran Rincian Nota`
    - `Pembayaran Pelanggan`
    - `Pengembalian Dana`
    - `Pengembalian Rincian Nota`

- Backend-generated detail/billing labels:
  - changed visible labels such as:
    - `Service` -> `Servis`
    - `Service + Part Toko` -> `Servis + Sparepart Toko`
    - `Service + Part External` -> `Servis + Sparepart Luar`
    - `Part External` -> `Sparepart Luar`
    - `Line Nota` -> `Rincian Nota`
    - `Total Line` / `Line Total` -> `Total Rincian`
    - `Correction Nominal Service` -> `Koreksi Nominal Servis`
    - `Customer payment berhasil dicatat.` -> `Pembayaran pelanggan berhasil dicatat.`
    - `Customer refund berhasil dicatat.` -> `Pengembalian dana pelanggan berhasil dicatat.`

#### Proof

- Reason/billing focused tests:
  - `php artisan test tests/Unit/Application/Note/Services/NoteBillingProjectionRowMapperTest.php tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php tests/Feature/Note/CashierNoteRevisionSmokeTest.php tests/Feature/Note/NoteDetailPageShowsNativeCorrectionHistoryFeatureTest.php`
  - Result: PASS, `13 passed (76 assertions)`.

- Cash ledger focused tests:
  - `php artisan test tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerExcelDetailPaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfDetailPaymentMethodTest.php tests/Unit/Application/Reporting/Exports/TransactionCashLedgerPdfBladePaymentMethodTest.php`
  - Result: PASS, `11 passed (93 assertions)`.

- Payment/refund/note detail focused tests:
  - `php artisan test tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php tests/Feature/Note/CashierOpenNoteRefundStandbyViewFeatureTest.php tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php tests/Feature/Note/CashierRefundSelectionFirstFeatureTest.php tests/Feature/Note/NoteDetailPageFeatureTest.php tests/Feature/Note/CashierNoteDetailSimplePaymentModalUxFeatureTest.php tests/Feature/Note/CashierWorkspacePaymentFlowJavascriptContractTest.php tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php`
  - Result: PASS, `30 passed (206 assertions)`.

- Note history/detail focused tests:
  - `php artisan test tests/Feature/Note/AdminNoteHistoryTableDataFeatureTest.php tests/Feature/Note/CashierNoteHistoryTableFeatureTest.php tests/Feature/Note/CashierNoteHistoryLegacyLineSummaryFeatureTest.php tests/Feature/Note/LegacyAllocatedNoteDetailFeatureTest.php tests/Feature/Note/NoteDetailPageFeatureTest.php tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php tests/Feature/Note/CashierNoteRevisionSubmitFeatureTest.php tests/Feature/Note/CashierClosedNoteRefundViewFeatureTest.php tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php`
  - Result: PASS, `20 passed (137 assertions)`.

- Backend label/correction focused tests:
  - `php artisan test tests/Unit/Application/Note/Services/NoteDetailRowMapperTest.php tests/Unit/Application/Note/Services/NoteBillingProjectionRowMapperTest.php tests/Feature/Note/NoteDetailPageShowsExternalPurchaseCorrectionHistoryFeatureTest.php tests/Feature/Note/NoteCorrectionHistoryBuilderFeatureTest.php tests/Feature/Note/CorrectPaidServiceWithExternalPurchaseServiceFeeOnlyFeatureTest.php tests/Feature/Note/CorrectPaidServiceWithStoreStockPartServiceFeeOnlyFeatureTest.php`
  - Result: PASS, `10 passed (54 assertions)`.

#### DECISION

- The required reason input/display path for workspace revision is now covered:
  - input: `Alasan Perubahan Nota` (`name="reason"`)
  - display: `Alasan:` in `Riwayat Perubahan`.
- Owner-facing note transaction language cleanup has progressed across UI, JS-rendered text, formatter/presenter labels, and export labels.
- Remaining broad scope still exists for unrelated modules and some internal exception messages. Continue with narrow slices only.

#### NEXT CANDIDATE SLICES

- Run a focused broad scan for remaining owner-facing note/report labels only.
- Continue replacing visible `Versioning` / `Revision` terms in note views with `Riwayat Perubahan` / `Revisi` if not already covered.
- Keep supplier/procurement report labels separate from transaction note/report work.

### Session Update - 2026-06-28 Slice 6B Revision/Versioning Visible Label Cleanup

#### Scope

- Follow-up from Slice 6 scan for visible `Versioning` / `Revision` labels in note detail pages.
- Kept internal keys, route parameters, DB fields, test fixture data, and user-entered service names unchanged.

#### Changes Applied

- `resources/views/shared/notes/partials/versioning-compact.blade.php`
  - `Riwayat Revisi` -> `Riwayat Perubahan`
  - existing labels already used:
    - `Riwayat Perubahan Nota`
    - `Perubahan Aktif`
    - `Isi Perubahan Aktif`
- `tests/Feature/Note/CashierNoteDetailUsesCurrentRevisionLinesFeatureTest.php`
  - UI assertion changed from `Revision Aktif` to `Perubahan Aktif`.
- `tests/Feature/Note/CashierNoteVersioningLineSnapshotViewFeatureTest.php`
  - UI assertion changed from `Line 1` to `Rincian 1`.

#### Proof

- Focused scan after patch:
  - `rg -n --no-heading -S '(Versioning Nota|Revision Aktif|Isi Revision Aktif|Riwayat Revisi)' resources/views/cashier/notes resources/views/shared/notes resources/views/admin/notes`
  - Result: no matches.
- Focused tests:
  - `php artisan test tests/Feature/Note/CashierNoteDetailUsesCurrentRevisionLinesFeatureTest.php tests/Feature/Note/CashierNoteVersioningLineSnapshotViewFeatureTest.php tests/Feature/Note/CashierNoteRevisionSmokeTest.php tests/Feature/Note/CashierNoteMutationHistoryViewFeatureTest.php tests/Feature/Note/AdminNoteDetailPageFeatureTest.php tests/Feature/Note/CashierHybridNoteDetailFeatureTest.php`
  - Result: PASS, `9 passed (53 assertions)`.

#### DECISION

- Visible note revision/versioning labels in active note views are now Indonesian.
- Remaining `revision` strings in code/tests are mostly internal identifiers, route names, DB fields, or user/test data and should not be renamed in this slice.

### Session Update - 2026-06-28 Cash Ledger Export Formatter Split Verified

#### Scope

- Active issue: `docs/04_lifecycle/error_log/0047_transaction_owner_facing_indonesian_language_gap.md`
- Handoff: `docs/04_lifecycle/error_log/0048_owner_facing_indonesian_language_gap_handoff.md`
- Follow-up after cash ledger export owner-facing label cleanup.
- Trigger: `make verify` failed at `audit-lines` because two export classes exceeded the 100-line limit.
- Patch type: small architecture cleanup/refactor, no DB/schema/route/API contract change.

#### Files Changed

- Added:
  - `app/Application/Reporting/Exports/TransactionCashLedgerExportLabelFormatter.php`
- Refactored:
  - `app/Application/Reporting/Exports/TransactionCashLedgerPdfViewDataBuilder.php`
  - `app/Application/Reporting/Exports/TransactionCashLedgerExcelDetailSheetWriter.php`
- Updated stale test expectation:
  - `tests/Feature/Note/TransactionCashLedgerAfterRevisionRefundFeatureTest.php`

#### FACT

- `TransactionCashLedgerPdfViewDataBuilder.php` previously exceeded line limit at 111 lines.
- `TransactionCashLedgerExcelDetailSheetWriter.php` previously exceeded line limit at 101 lines.
- Shared cash ledger export label mapping was extracted to `TransactionCashLedgerExportLabelFormatter`.
- After split:
  - `TransactionCashLedgerExportLabelFormatter.php`: 52 lines
  - `TransactionCashLedgerPdfViewDataBuilder.php`: 71 lines
  - `TransactionCashLedgerExcelDetailSheetWriter.php`: 62 lines
- No bypass label was added.
- Existing output contract remains owner-facing:
  - `payment_allocation` -> `Pembayaran Tercatat`
  - `payment_allocations` -> `Pembayaran Nota`
  - `customer_refunds` -> `Pengembalian Dana`
  - `refund` -> `Pengembalian Dana`
- Stale lifecycle Excel expectation was updated from raw/internal source label to owner-facing source label.

#### PROOF

- `make audit-lines`: PASS.
- Cash ledger focused/export tests passed after label expectation correction.
- Full verification:
  - `make verify`
  - Result: PASS
  - `1439 passed (8603 assertions)`
  - Duration: `91.00s`

#### DECISION

- Cash ledger export label formatter split is complete.
- Cash ledger export owner-facing labels remain centralized.
- No further bypass is needed for these files.
- Continue remaining 0047 work with broad owner-facing scan only; do not rename DB enums, DTO keys, route names, request fields, or user-provided fixture values.


## Session Update - 2026-06-28 Note Wrapper Help Text Cleaned

### Slice

- Active issue: 0047 owner-facing Indonesian language cleanup transaksi nota.
- Handoff: 0048 owner-facing Indonesian language gap handoff.
- Scope: transaksi nota visible UI only.
- Status: note detail wrapper help text cleaned.

### FACT

- `make verify` was reported PASS before this final wrapper patch.
- Final targeted wrapper scan found remaining visible English/internal terms in:
  - `resources/views/cashier/notes/show.blade.php`
  - `resources/views/shared/notes/show.blade.php`
- Remaining visible terms were limited to help text:
  - `Identitas customer`
  - `status setiap line`
  - `refund setelah`
  - `dampak refund`
  - `atau refund`
- These were visible owner-facing UI strings, not enum, key, route, request field, fixture, class, or method names.

### Files Changed

- `resources/views/cashier/notes/show.blade.php`
- `resources/views/shared/notes/show.blade.php`
- `docs/04_lifecycle/error_log/0048_owner_facing_indonesian_language_gap_handoff.md`

### Changes

- `Identitas customer, tanggal, status, dan ringkasan pembayaran.`
  -> `Identitas pelanggan, tanggal, status, dan ringkasan pembayaran.`
- `Daftar item, status, sisa tagihan, dan dampak refund per rincian.`
  -> `Daftar item, status, sisa tagihan, dan dampak pengembalian dana per rincian.`
- `Lanjut edit, pembayaran, atau refund setelah rincian nota dicek.`
  -> `Lanjut edit, pembayaran, atau pengembalian dana setelah rincian nota dicek.`
- `Identitas customer, tanggal, dan status nota.`
  -> `Identitas pelanggan, tanggal, dan status nota.`
- `Daftar rincian nota dan status setiap line.`
  -> `Daftar rincian nota dan status setiap rincian.`
- `Edit, pembayaran, dan refund setelah rincian nota dicek.`
  -> `Edit, pembayaran, dan pengembalian dana setelah rincian nota dicek.`

### Proof

Owner ran:

```bash
rg -n --no-heading -S '(Identitas customer|status setiap line|refund setelah|dampak refund|atau refund)' \
  resources/views/shared/notes/show.blade.php \
  resources/views/cashier/notes/show.blade.php
```

Result:

```text
(no output)
```

### Remaining Work

- Run full final classification scan for 0047.
- Run `make verify` after this wrapper patch.
- If no visible transaksi nota/report/PDF/Excel English/internal-facing string remains, close 0047 with final handoff update.
- Dashboard labels and supplier payable labels remain out of scope for 0047 and must be handled in separate issue/slice if needed.

### Boundary Reminder

Do not patch:

- enum database,
- column names,
- DTO keys,
- route names,
- request field names,
- hidden input names,
- class names,
- method names,
- fixture/user input,
- docs/99_archive,
- supplier/procurement,
- mobile API.


## Session Update - 2026-06-28 Final Runtime UI Label Cleanup Verified

### Slice

- Active issue: 0047 owner-facing Indonesian language cleanup transaksi nota.
- Handoff: 0048 owner-facing Indonesian language gap handoff.
- Scope: transaksi nota visible UI/report/export labels.
- Status: final runtime UI label cleanup verified.
- Closure state: candidate to close after owner records this update.

### FACT

- Previous wrapper help text cleanup was verified with targeted `rg` returning no output.
- Full `make verify` passed after wrapper help text cleanup.
- Final classification scan found one remaining valid runtime UI label source:
  - `app/Application/Note/Services/NoteCorrectionUiOptionsBuilder.php`
- The remaining labels were owner-facing UI option labels:
  - `Open`
  - `Done`
  - `Canceled`
  - `Part Bawaan Customer`
- Only `label` values were changed.
- `value` enum/internal values were not changed.

### Files Changed In This Update

- `app/Application/Note/Services/NoteCorrectionUiOptionsBuilder.php`
- `docs/04_lifecycle/error_log/0048_owner_facing_indonesian_language_gap_handoff.md`

### Changes

- `Open` -> `Belum Selesai`
- `Done` -> `Selesai`
- `Canceled` -> `Dibatalkan`
- `Part Bawaan Customer` -> `Part Bawaan Pelanggan`

### Proof

Owner ran targeted scan:

```bash
rg -n --no-heading -S "(label' => 'Open|label' => 'Done|label' => 'Canceled|Part Bawaan Customer)" \
  app/Application/Note/Services/NoteCorrectionUiOptionsBuilder.php
```

Result:

```text
(no output)
```

Owner ran full verification after this patch:

```bash
make verify
```

Result:

```text
PASS
```

### Final Classification

No remaining known 0047 blocker in transaksi nota visible UI/report/export labels.

Remaining scan hits are classified as non-blocking for 0047:

- internal class/interface/service/DTO names,
- enum values,
- request keys,
- route/controller/usecase names,
- database/source keys such as `source_table`,
- test fixture names or seeded names,
- dashboard finance labels,
- supplier/procurement payable labels,
- demo chart label `Free Cash Flow`.

### Out Of Scope For 0047

Admin dashboard labels:

- `Net Cash Bulan Ini`
- `Outstanding Bulan Ini`
- `Net Cash Flow Bulan Ini`
- `Outstanding Transaksi`
- `Outstanding Supplier`

Supplier payable/reporting labels:

- `Outstanding`
- Procurement/supplier invoice revision labels.
- Mobile API.
- `docs/99_archive`.

### Closure Recommendation

0047 can be closed from the transaksi nota language-cleanup side.

If owner wants further cleanup, create separate issues/slices for:

- dashboard finance label Indonesian cleanup,
- supplier payable/reporting label cleanup,
- procurement revision request/message cleanup.

