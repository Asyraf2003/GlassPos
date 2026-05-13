# Handoff FC-000 - System Ambiguity Inventory

## Metadata

- Repo: `/home/asyraf/Code/laravel/bengkel2/app`
- Branch: `audit-1461-selective-patch`
- Baseline HEAD: `c0ce90a6`
- Date: 2026-04-29
- Scope: Feature continuation bootstrap after audit 1461 selective patch closure.
- Status: CLOSED as inventory/handoff, not feature implementation.

## Context

Audit commit 1461 selective patch sudah selesai dan closed di commit:

- `6b2a4913 Persist note payment cash detail`
- `4229a797 Guard supplier payment outstanding under lock`
- `42bfabea Lock product inventory row during stock issue`
- `b3b344fb Split refund inventory reversal intent`
- `eb1c4f46 Split oversized audit files`
- `c0ce90a6 Allow negative COGS for refund corrections`

Final proof audit 1461:
- `make verify` pass.
- Tests: `773 passed (4040 assertions)`.
- UI label stash masih outside-audit:
  `stash@{0}: temp-ui-refund-label-outside-audit`

Setelah audit closed, ditemukan bahwa beberapa fitur yang sempat dikerjakan sebelum masalah hardware belum bisa dianggap closed karena belum punya proof closure lengkap.

## Final Decision

Feature continuation dipisahkan dari audit 1461.

Mulai sekarang semua kasus fitur tertinggal dikerjakan dengan sistem:

1. P0/P1/P2 priority.
2. Satu active step per sesi/respons.
3. Snapshot repo sebelum patch.
4. FACT/GAP/DECISION sebelum implementasi.
5. Focused tests.
6. `make verify`.
7. Commit kecil.
8. Handoff per kasus di folder ini.
9. Update ledger di `docs/blueprint/v2/feature-continuation/00-blueprint.md`.

## Latest Repo Analysis Summary

### Cash change / kembalian

Status: PARTIAL/CLOSED for persistence, OPEN for denomination calculator.

Facts:
- Cash payment detail sudah persisted di `customer_payment_cash_details`.
- Field tersedia:
  - `amount_paid_rupiah`
  - `amount_received_rupiah`
  - `change_rupiah`
- Existing writer menyimpan cash detail.
- Existing payment tests sudah assert `change_rupiah`.

Gaps:
- Belum ada proof kalkulator pecahan uang.
- Belum ada proof dashboard memakai `change_rupiah`.
- Belum ada decision final definisi "potensial uang kembalian".

### Dashboard Kinerja Operasional Bulan Ini

Status: OPEN for potential cash change metric.

Facts:
- Dashboard operational performance sudah ada.
- Chart title existing: `Kinerja Operasional Bulan Ini`.
- Dataset existing fokus ke operational performance/profit/cash/refund/cost.

Gaps:
- Belum ada metric potensi kembalian.
- Belum ada dashboard test untuk `change_rupiah`.
- Belum ada decision apakah dashboard hanya total, atau juga pecahan.

Default suggested decision:
- Dashboard menampilkan total `change_rupiah` bulan ini sebagai indikator.
- Kalkulator pecahan tetap fitur terpisah.

### Push notification

Status: customer due note reminder CLOSED/TESTED, supplier payable reminder OPEN.

Facts:
- Push infra sudah ada.
- Existing command:
  `push-notifications:send-due-note-reminders`
- Existing use case:
  `SendDueNoteReminderPushHandler`
- Existing payload bicara tentang nota pelanggan jatuh tempo.
- Existing tests push notification sudah pernah pass.

Gaps:
- Belum ada supplier payable push handler.
- Belum ada supplier payable push command.
- Belum ada reader khusus supplier invoice unpaid due H-5.
- Belum ada test H-6 excluded, H-5 included, due today included, overdue included, paid full excluded, voided excluded.

Required supplier reminder contract:
- `supplier_invoices.voided_at IS NULL`
- `jatuh_tempo <= today + 5 days`
- outstanding > 0
- tetap muncul sampai dibayar lunas
- lunas tidak muncul
- voided tidak muncul

### Supplier payable report

Status: CLOSED as reporting foundation, reusable as reference only.

Facts:
- Supplier payable report sudah ada.
- Due date dan outstanding sudah tersedia di reporting/procurement read model.
- Ada due status resolver/report tests.

Gaps:
- Reporting foundation belum otomatis berarti push notification supplier payable sudah ada.

### PDF / cetak

Status: OPEN / not discussed.

Facts:
- Search repo menemukan PDF attachment proof supplier.
- Itu bukan generate PDF nota/laporan.

Gaps:
- Belum ada kontrak PDF/cetak:
  - nota pelanggan,
  - laporan,
  - supplier payable,
  - atau semua.
- Belum ada decision output:
  - browser print,
  - generated PDF download,
  - stored artifact.
- Belum ada decision library.

### UI refund label stash

Status: DEFERRED.

Facts:
- Stash masih ada:
  `stash@{0}: temp-ui-refund-label-outside-audit`
- Perubahan label ini outside audit 1461.
- Pernah menyebabkan test false negative karena expected label lama.

Decision:
- Jangan pop/stage sampai ada keputusan UI wording sendiri.

## Priority Map

### P0

FC-001 - Supplier payable push notification H-5 sampai lunas.

Reason:
- Risiko finansial/operasional langsung.
- Hutang pemasok bisa terlambat dibayar.
- Fondasi supplier payable dan push infra sudah ada, jadi scope cukup jelas.

### P1

FC-002 - Dashboard potensi uang kembalian.

Reason:
- Penting untuk operasional kas.
- Bergantung pada `customer_payment_cash_details.change_rupiah`.

FC-003 - Kalkulator pecahan uang kembalian.

Reason:
- Membantu kasir/admin menyiapkan uang kecil.
- Lebih aman setelah definisi dashboard locked.

### P2

FC-004 - PDF/cetak nota/laporan.

Reason:
- Belum dibahas kontrak.
- Risiko scope melebar.

FC-005 - UI refund label stash.

Reason:
- UI wording only.
- Harus dipisah dari audit/feature logic.

## Files Added

- `docs/blueprint/v2/feature-continuation/00-blueprint.md`
- `docs/handoff/v2/feature-continuation/01-system-ambiguity-inventory.md`

## What Was Closed

- Ambiguity inventory after abandoned hardware-interrupted feature work.
- Priority map P0/P1/P2.
- Handoff workflow for future sessions.
- Ledger source path.

## What Was Not Closed

- FC-001 supplier payable notification implementation.
- FC-002 dashboard cash change metric.
- FC-003 denomination calculator.
- FC-004 PDF/cetak.
- FC-005 UI refund label stash.

## Known Caveats

- Snapshot was based on grep/reference inspection, not a full semantic implementation audit.
- Before each FC implementation, inspect exact files again.
- Do not rely only on this handoff if repo moved after `c0ce90a6`.

## Next Safe Step

Start FC-001.

First active step:
1. Snapshot repo.
2. Inspect:
   - `routes/console.php`
   - `app/Application/PushNotification/UseCases/SendDueNoteReminderPushHandler.php`
   - `app/Application/PushNotification/Services/DueNoteReminderPushPayloadFactory.php`
   - `app/Adapters/Out/Reporting/SupplierPayableReportingQueryFactory.php`
   - `app/Application/Reporting/Services/SupplierPayableDueStatusResolver.php`
   - supplier payable tests
   - push notification tests
3. Lock supplier payable reminder query contract.
4. Stop before patch if contract still ambiguous.

## Opening Prompt For Next Session

Lanjutkan feature continuation repo `/home/asyraf/Code/laravel/bengkel2/app`.

State terakhir:
- Branch baseline: `audit-1461-selective-patch`
- Baseline HEAD: `c0ce90a6`
- Audit 1461 selective patch sudah closed.
- Feature continuation blueprint:
  `docs/blueprint/v2/feature-continuation/00-blueprint.md`
- Handoff bootstrap:
  `docs/handoff/v2/feature-continuation/01-system-ambiguity-inventory.md`
- UI refund label stash masih outside-scope:
  `stash@{0}: temp-ui-refund-label-outside-audit`

Mulai dari FC-001:
Supplier payable push notification H-5 sampai lunas.

Aturan:
- Zero assumption.
- Blueprint first.
- One active step.
- Jangan klaim progress tanpa command output.
- Jangan pop/stage UI refund label stash.
- Snapshot dulu sebelum patch.

