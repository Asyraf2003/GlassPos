# HANDOFF V2 UI / NOTE REFUND / A1 + T1-HARD
Status date: 2026-04-24  
Owner context: Project Kasir / nota pelanggan / UI-first lalu logic mengikuti UI  
This file is the current source of truth for next chat. Do not reopen old discussions unless a new blocker proves this handoff wrong.

---

# 1. Executive Summary

## Final target
Bagian **nota pelanggan** harus beres untuk:
- index UI
- detail UI
- edit workspace
- refund flow
- relasi + logic presisi
- tanpa bug
- siap dilanjutkan tanpa diskusi ulang arah

## Locked direction
Arah final yang sudah dipilih dan **jangan diperdebatkan ulang**:

1. **UI-first anchor** sudah dipilih dan sudah dijalankan.
2. Refund memakai **Opsi A**:
   - refund boleh dari selected line
   - tidak peduli unpaid / partial / fully paid
   - refund berarti **line itu tidak lagi transaksi aktif**
3. Ledger memakai **A1**:
   - auto split by payment source
   - user tidak pilih `customer_payment_id`
   - unpaid selected rows tidak membuat refund ledger `0`
4. Total mengikuti **T1-hard**:
   - grand total aktif note harus ikut turun sesuai line aktif terbaru
   - line canceled/refund tetap ada untuk history
   - note aktif/read model harus mencerminkan line aktif terbaru, bukan angka historis mentah

## Progress summary
### End-to-end target
**40%**

### UI-only target
**75%**

## Why 40% overall
Karena target awal bukan sekadar UI jadi. Target awal adalah UI + logic + relasi + presisi + tanpa bug.  
Saat handoff ini dibuat, full verify masih **belum hijau** dan ada cluster fail domain/refund/test migration yang belum selesai.

## Why UI is 75%
Karena anchor UI family untuk detail/refund/versioning sudah kebentuk kuat dan jauh lebih rapi dari awal, walaupun beberapa test UI lama masih memakai copy/contract lama.

---

# 2. Locked Decisions (Do Not Re-discuss)

## UI decisions
1. Hapus desc panjang dari halaman detail nota.
2. **Billing Projection** dipensiunkan dari UI detail.
3. Daftar line jadi pusat halaman.
4. Modal refund dua kolom:
   - kiri = selected lines
   - kanan = hasil refund + alasan
5. Versioning family dipertahankan, tapi wording lama berubah:
   - sekarang family baru memakai `Versioning Nota`
   - current block memakai `Revision Aktif`
6. Refund launcher tunggal, bukan ganda di kanan dan kiri.

## Domain decisions
1. Refund = selected line tidak lagi transaksi aktif.
2. `customer_payment_id` manual dari UI harus dihapus dari kontrak final.
3. `amount_rupiah` manual dari UI harus dihapus dari kontrak final.
4. Server resolve paid buckets otomatis dari allocation history.
5. Unpaid selected rows:
   - tetap boleh refund/cancel
   - tidak bikin refund ledger amount 0
6. T1-hard:
   - total aktif note harus mengikuti line aktif non-canceled
   - history tetap tersimpan

## Governance decisions
1. File > 100 lines harus dipecah, tidak boleh dibypass diam-diam.
2. Jangan klaim “hampir selesai” selama `make verify` belum hijau.
3. Jangan memperbaiki test hanya untuk menutup error kalau kontrak baru belum benar-benar selesai.

---

# 3. What Has Been Completed

## A. UI anchor completed
Files/UI family yang sudah banyak berubah dan secara arah **sudah benar**:
- `resources/views/shared/notes/show.blade.php`
- `resources/views/shared/notes/partials/line-workspace.blade.php`
- `resources/views/shared/notes/partials/header-summary.blade.php`
- `resources/views/shared/notes/partials/payment-summary-actions.blade.php`
- `resources/views/shared/notes/partials/versioning-compact.blade.php`
- `resources/views/cashier/notes/partials/note-rows-table.blade.php`
- `resources/views/cashier/notes/partials/refund-modal.blade.php`
- `public/assets/static/js/pages/cashier-note-refund.js`

### Result
- shell detail lebih ringkas
- action hierarchy lebih jelas
- versioning lebih informatif
- refund modal lebih modern
- UI detail tidak lagi terasa seperti halaman campur dokumentasi internal

## B. Additive backend groundwork completed
Additive services/DTO yang sudah ditambahkan:
- refund plan DTO
- refund bucket DTO
- selected-row refund plan resolver
- selected-row cancel + sync active total service
- aggregate refund transaction scaffolding
- note/history helpers hasil split governance

### Important
Ini **baru groundwork additive**, belum seluruhnya selesai dan belum seluruhnya tersambung secara merge-safe.

## C. Governance split completed
Banyak file oversized sudah dipecah agar `audit-lines` sempat hijau di batch-batch sebelumnya.

---

# 4. Current Real State

## Latest meaningful truth
Branch **belum merge-safe**.

### Full verify terakhir masih gagal
Ada **16 test gagal**.

### Ini artinya:
- UI anchor sudah maju
- backend A1/T1-hard sudah mulai masuk
- tetapi kontrak lama dan kontrak baru masih bertabrakan di beberapa area

---

# 5. Exact Failure Clusters From Latest Verify

## Cluster 1 - UI tests still expect old wording/old UI contract
Affected tests:
- `Tests\\Feature\\Note\\CashierHybridNoteDetailFeatureTest`
- `Tests\\Feature\\Note\\CashierNoteMutationHistoryViewFeatureTest`
- `Tests\\Feature\\Note\\CashierNoteRevisionSmokeTest`
- `Tests\\Feature\\Note\\NoteCorrectionHistoryPageFeatureTest`
- `Tests\\Feature\\Note\\NoteDetailPageShowsExternalPurchaseCorrectionHistoryFeatureTest`
- `Tests\\Feature\\Note\\NoteDetailPageShowsNativeCorrectionHistoryFeatureTest`

### Exact symptom
Tests still expect:
- `Current Revision`

But UI contract now uses:
- `Revision Aktif`

### Action needed
Update tests to new copy.  
Do **not** revert UI copy just to satisfy old tests.

---

## Cluster 2 - Billing Projection tests still expect removed UI
Affected test:
- `Tests\\Feature\\Note\\CashierNoteDetailBillingUsesCurrentRevisionFeatureTest`

### Exact symptom
Test still expects:
- `Billing Projection`

But this section was explicitly removed from UI contract.

### Action needed
Update/delete old assertion path.  
Do **not** re-add Billing Projection just to satisfy legacy assertion.

---

## Cluster 3 - Reader / aggregate / active-total migration still inconsistent
Affected tests:
- `Tests\\Feature\\Note\\AddWorkItemToPaidNoteFeatureTest`
- `Tests\\Feature\\Note\\UpdateWorkItemStatusFeatureTest`
- `Tests\\Feature\\Payment\\AllocateCustomerPaymentFeatureTest`

### Exact symptom
- domain behavior around active/canceled items shifted
- some tests still depend on old reader/aggregate assumptions
- `UpdateWorkItemStatusFeatureTest` previously showed item count mismatch after canceled filtering approach
- `AllocateCustomerPaymentFeatureTest` also drifted because total note mapping moved

### Root cause
Early T1-hard groundwork tried to solve active total by filtering canceled rows from `DatabaseNoteReaderAdapter`.  
That approach collides with other flows that still need all work items visible in aggregate/history operations.

### Correct direction
**Do not filter canceled rows out of the global reader.**  
Instead:
- reader should still load all work items
- active-total semantics must be handled in total calculation / mapper logic for active totals

### Locked recommendation
Use:
- reader returns all items
- note total for active workflows is calculated from non-canceled items
- historical rows remain visible for flows that need them

---

## Cluster 4 - Refund lifecycle full-state not finished
Affected tests:
- `Tests\\Feature\\Note\\ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest`
- `Tests\\Feature\\Note\\ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest`
- likely related full refund lifecycle tests in same family

### Exact symptoms
1. `note_state` not finalized as expected in some full refund cases
2. component refund allocations do not match expected selected-row semantics
3. legacy refund operation still over-applies allocations in some selected-row cases

### Root cause
`RecordSelectedRowsRefundPlanBucketProcessor` currently reuses legacy `RecordCustomerRefundOperation`, but legacy operation is still oriented around:
- payment source centric behavior
- legacy component allocation behavior
- not fully selected-row-aware at component granularity

### Consequence
For cases like:
- external purchase full refund
- store stock part full refund

legacy operation can over-refund or refund wrong component shapes relative to selected-row A1 intent.

### Locked conclusion
This area still needs a **selected-row-aware refund component allocation path**, not just reuse legacy one blindly.

---

## Cluster 5 - Redirect/validation behavior changed
Affected test:
- `Tests\\Feature\\Note\\CashierRefundRejectsOpenLineFeatureTest`

### Exact symptom
Legacy test expects redirect back to show page.
Current rewritten controller path may redirect to index route.

### Important
This should not be patched blindly.
First decide final controller error UX:
- keep redirect to show page
- or standardize to index/back flow

### Recommendation
For refund action errors on a specific note detail page, **redirect back to show page is safer UX**.  
But do not patch this until core A1 refund legality is stable.

---

## Cluster 6 - Revision line/current revision content tests still need migration
Affected tests:
- `Tests\\Feature\\Note\\CashierNoteDetailUsesCurrentRevisionLinesFeatureTest`
- plus several revision/history page tests above

### Exact symptom
Old expectations still assume prior revision wording/structure.
UI has moved, but tests are still half-legacy.

### Action needed
Test migration only after current revision rendering contract is frozen.

---

## Cluster 7 - Workspace/paid note rule drift
Affected test:
- `Tests\\Feature\\Note\\EditableWorkspaceNo...` (failed because expected DomainException not thrown)

### Meaning
There is at least one workspace/business-rule test still expecting old paid-note prohibition behavior.
Do not touch this immediately. It is secondary after reader + refund lifecycle stabilization.

---

# 6. Percentage Breakdown

## A. UI family
**75%**
Reason:
- detail shell
- refund modal
- row table
- versioning block
- action hierarchy
are already substantially aligned

### Remaining UI work
- migrate legacy tests to new copy/contract
- final UI cleanup after core domain stabilizes

## B. Backend refund A1/T1-hard
**25%**
Reason:
- additive boundary exists
- DTO/services exist
- some rewiring started
- but lifecycle, component allocation correctness, and aggregate consistency still not solved

## C. End-to-end project goal for note page
**40%**
Reason:
- a lot of UI completed
- backend has foundation
- but current branch still fails full verify and still mixes old/new contracts

---

# 7. Exact Next Step Order
This is the part next chat must follow. Do not reopen architecture debate unless a step below proves impossible.

## STEP 1 - Stabilize aggregate/read model for T1-hard
### Goal
Stop domain inconsistency between:
- all work items
- canceled work items
- active total note

### Do
1. Reader must keep loading **all** work items.
2. Active total must be derived from **non-canceled** items.
3. Do not solve T1-hard by hiding canceled rows globally from the reader.

### Files likely touched
- `app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php`
- `app/Adapters/Out/Note/Mappers/NoteMapper.php`
- `app/Core/Note/Note/NoteValidation.php`

### Verify after Step 1
Run:
- `tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php`
- `tests/Feature/Note/UpdateWorkItemStatusFeatureTest.php`
- `tests/Feature/Payment/AllocateCustomerPaymentFeatureTest.php`
- `tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php`

### Exit condition
These tests stop failing because reader/aggregate semantics are coherent again.

---

## STEP 2 - Finish selected-row-aware refund allocation behavior
### Goal
Stop reusing legacy refund operation in a way that over-refunds components.

### Do
Refactor selected-row refund bucket processing so component allocation is truly selected-row-aware.

### Important
Legacy `RecordCustomerRefundOperation` is not enough by itself for final A1 semantics.

### Files likely touched
- `app/Application/Payment/Services/RecordSelectedRowsRefundPlanBucketProcessor.php`
- maybe new service dedicated to selected-row component allocation
- possibly supporting DTO/service files around refund component allocation

### Verify after Step 2
Run:
- `tests/Feature/Note/ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php`
- `tests/Feature/Note/ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest.php`
- related full refund lifecycle tests

### Exit condition
Refund component allocations match selected-row expectations, not legacy payment-source-wide behavior.

---

## STEP 3 - Finalize note state after active total reaches zero
### Goal
When all active rows are gone, note must finalize correctly.

### Do
Finalize note state to `refunded` when:
- active total = 0
- all operational rows are gone from active transaction

### Files likely touched
- `app/Application/Note/Services/FinalizeRefundedNoteFromActiveRows.php`
- `app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php`

### Verify after Step 3
Run full refund lifecycle tests again.

### Exit condition
Tests expecting `note_state = refunded` pass.

---

## STEP 4 - Clean controller/request migration to A1
### Goal
Make HTTP flow fully consistent with final contract.

### Final request contract
Only:
- `selected_row_ids[]`
- `refunded_at`
- `reason`

### Remove from final contract
- `customer_payment_id`
- `amount_rupiah`

### Files likely touched
- `app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php`
- `app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`

### Verify after Step 4
Run:
- `tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`
- `tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php`
- `tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php`

### Exit condition
Controller behavior, redirect behavior, and validation behavior are stable.

---

## STEP 5 - Migrate legacy UI/detail/versioning tests to final UI contract
### Goal
Stop pretending old copy and removed sections still belong.

### Update tests from old expectations
Replace:
- `Current Revision` -> `Revision Aktif`

Remove expectations for:
- `Billing Projection`

### Files/tests affected
- `CashierHybridNoteDetailFeatureTest`
- `CashierNoteMutationHistoryViewFeatureTest`
- `CashierNoteRevisionSmokeTest`
- `NoteCorrectionHistoryPageFeatureTest`
- `NoteDetailPageShowsExternalPurchaseCorrectionHistoryFeatureTest`
- `NoteDetailPageShowsNativeCorrectionHistoryFeatureTest`
- `CashierNoteDetailBillingUsesCurrentRevisionFeatureTest`

### Exit condition
UI tests reflect actual locked UI contract.

---

## STEP 6 - Revisit remaining workspace/business rule drift
### Goal
Only after core note/refund logic stabilizes.

### Examples
- `EditableWorkspaceNo...` failure
- any remaining paid-note/add-work-item rule drift

---

# 8. Do Not Do
These are explicitly blocked.

1. **Do not** re-add Billing Projection to the detail UI just to make one test pass.
2. **Do not** restore `Current Revision` wording unless the entire UI contract is intentionally rolled back.
3. **Do not** solve T1-hard by globally hiding canceled rows from every reader flow.
4. **Do not** keep reusing legacy refund operation blindly for final A1 selected-row semantics.
5. **Do not** claim progress above 40% end-to-end until full verify is clean.
6. **Do not** switch focus to index/create polishing before core note/refund domain is stable.

---

# 9. Current File Groups That Matter Most

## UI files already heavily changed
- `resources/views/shared/notes/show.blade.php`
- `resources/views/shared/notes/partials/line-workspace.blade.php`
- `resources/views/shared/notes/partials/header-summary.blade.php`
- `resources/views/shared/notes/partials/payment-summary-actions.blade.php`
- `resources/views/shared/notes/partials/versioning-compact.blade.php`
- `resources/views/cashier/notes/partials/note-rows-table.blade.php`
- `resources/views/cashier/notes/partials/refund-modal.blade.php`
- `public/assets/static/js/pages/cashier-note-refund.js`

## Backend files most likely to continue changing
- `app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php`
- `app/Adapters/Out/Note/Mappers/NoteMapper.php`
- `app/Core/Note/Note/NoteValidation.php`
- `app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php`
- `app/Application/Note/Services/CancelSelectedRowsAndSyncActiveNoteTotal.php`
- `app/Application/Note/Services/FinalizeRefundedNoteFromActiveRows.php`
- `app/Application/Payment/Services/RecordSelectedRowsRefundPlanBucketProcessor.php`
- `app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php`
- `app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php`
- `app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`

---

# 10. Minimal Verify Order For Next Chat
Next chat should not start with full random coding. Start with this verify order after each step.

## After Step 1
~~~bash
php artisan test \
  tests/Feature/Note/AddWorkItemToPaidNoteFeatureTest.php \
  tests/Feature/Note/UpdateWorkItemStatusFeatureTest.php \
  tests/Feature/Payment/AllocateCustomerPaymentFeatureTest.php \
  tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php
~~~

## After Step 2 + 3
~~~bash
php artisan test \
  tests/Feature/Note/ClosedNoteFullRefundLifecycleFeatureTest.php \
  tests/Feature/Note/ClosedNoteFullRefundProductOnlyInventoryLifecycleFeatureTest.php \
  tests/Feature/Note/ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest.php \
  tests/Feature/Note/ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest.php
~~~

## After Step 4
~~~bash
php artisan test \
  tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php \
  tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php \
  tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php
~~~

## After Step 5
~~~bash
php artisan test \
  tests/Feature/Note/CashierHybridNoteDetailFeatureTest.php \
  tests/Feature/Note/CashierNoteDetailBillingUsesCurrentRevisionFeatureTest.php \
  tests/Feature/Note/CashierNoteDetailUsesCurrentRevisionLinesFeatureTest.php \
  tests/Feature/Note/CashierNoteMutationHistoryViewFeatureTest.php \
  tests/Feature/Note/CashierNoteRevisionSmokeTest.php \
  tests/Feature/Note/NoteCorrectionHistoryPageFeatureTest.php \
  tests/Feature/Note/NoteDetailPageShowsExternalPurchaseCorrectionHistoryFeatureTest.php \
  tests/Feature/Note/NoteDetailPageShowsNativeCorrectionHistoryFeatureTest.php
~~~

## Final gate
~~~bash
make verify
~~~

# 11. Handoff Conclusion
Status now
- UI direction: mostly locked and materially improved
- Backend direction: chosen, but still in migration
- Branch health: not yet safe
- Full target: not achieved yet

What next chat must do first
1. Do Step 1 from this document.
2. Do not reopen direction debate.
3. Do not start from UI cosmetics.
4. Do not jump to full suite before the targeted subset for Step 1 is green.

## Final reminder
The correct reading of current progress is:
- 40% overall
- 75% UI
Anything higher is fiction.