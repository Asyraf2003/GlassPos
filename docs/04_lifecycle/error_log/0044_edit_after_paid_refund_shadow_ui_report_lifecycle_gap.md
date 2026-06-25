# 0044 Edit After Paid Refund Shadow UI Report Lifecycle Gap

## Status

Forensic policy gap. Belum patch.

## Scope

Manual/domain review menemukan lifecycle edit/refund/payment/report yang harus dikunci sebelum patch UI atau report besar.

Target policy:

- Nota hutang dan lunas tetap boleh diedit lewat revision path yang resmi.
- Refund tidak boleh diperlakukan sebagai line biasa yang ikut tertimpa edit.
- Refund harus tetap menjadi ledger/shadow historical truth.
- Edit setelah refund tidak boleh menghapus, reset, atau menggandakan efek refund, stok, payment, allocation, cash ledger, atau projection.
- Jika nota sudah lunas, sudah ada refund, lalu diedit turun atau seluruh line aktif dihapus, uang lebih harus menjadi status eksplisit:
  - overpaid_pending
  - refund_due
  - refund_paid
  - atau future customer credit setelah customer identity contract stabil
- UI harus menjelaskan status uang, status refund, status stok, status transaksi, dan action yang tersedia/tidak tersedia.
- Browser refresh, Ctrl+R, dan Ctrl+Shift+R tidak boleh membuat UI menampilkan state/action yang bertentangan dengan backend.
- Laporan screen, PDF, dan Excel harus membaca source resmi yang sama dan menampilkan efek lifecycle secara presisi.

## FACT

Existing docs sudah mencatat sebagian arah:

- Edit/refund target architecture adalah Ledger + Revision Snapshot + Current Projection.
- Payment/refund adalah financial ledger events.
- Inventory movements adalah stock ledger events.
- UI/API adalah transport adapters.
- Surplus/refund_due/refund_paid harus eksplisit dan tidak boleh hilang diam-diam.
- Full browser UI dan full report/export after edit/refund/surplus/refund_paid masih pernah dicatat sebagai gap.

## GAP

Belum ada satu error log aktif yang mengunci seluruh policy berikut:

1. Edit after paid/refund behavior.
2. Refund shadow/historical truth behavior.
3. UI action visibility after refresh.
4. Downward edit surplus display and lifecycle.
5. Report/PDF/Excel parity for paid/refund/edit/delete-all lifecycle.
6. Stock transaction status after edit/refund.
7. Financial status wording that is clear for user, not only internally correct.

## DECISION

Jangan patch UI dulu.

Langkah aman pertama adalah characterization test dan source-map untuk backend state yang harus ditampilkan UI.

## NEXT SAFE STEP

Buat test pertama untuk memastikan note detail UI tidak menampilkan action pembayaran yang backend allocator akan tolak, dan menampilkan alasan/status yang informatif.

## Workflow Control

Canonical workflow:

- docs/04_lifecycle/workflow/0044_edit_after_paid_refund_shadow_ui_report_lifecycle_workflow.md

Active handoff:

- docs/04_lifecycle/handoff/0016_0044_edit_after_paid_refund_shadow_ui_report_lifecycle_handoff.md

Rule:

- Every implementation session must update the active handoff checklist.
- Do not mark this error log fixed until the workflow DoD is satisfied.
- Do not execute direct GitHub connector write actions for this workflow unless owner explicitly overrides in the same session.

