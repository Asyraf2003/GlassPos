# 021 - Refunds can be recorded on open notes

## Status

Fixed and locally verified for whole-note close-state enforcement before refund mutation.

RED/GREEN proof confirms a selected-row refund against an open parent `Nota` is rejected before customer refund, refund allocation, row cancellation, or note-total mutation.

Focused regression proof confirms legitimate closed-note selected-row refunds still work.

## Severity

High.

## Summary

The refund endpoint allowed refunds on an open parent `Nota` when selected rows were already `close`.

Sebelum perubahan vulnerable, `RecordClosedNoteRefundController` menolak refund kecuali seluruh nota sudah closed secara operasional. Flow yang berubah memindahkan validasi hanya ke selected rows. `SelectedNoteRowsRefundAmountResolver` memeriksa apakah selected rows berstatus close dan membatasi nominal refund berdasarkan net paid line terpilih, tetapi tidak memeriksa apakah parent `Nota` itu sendiri sudah closed.

Karena cashier refund route diregistrasikan sebelum group `EnsureCashierNoteAccess`, controller menjadi titik enforcement state-policy server-side yang efektif untuk route ini.

UI juga mengekspos form refund setiap kali ada line close, bahkan ketika parent note masih memiliki line open.

## Update Log

### Update 1

Fixed and locally verified in current Slice 5 remediation.

Reason:

- Current source contradicted the older patch claim.
- `RecordClosedNoteRefundController` still resolved selected-row refund plans and ran refund transaction without reading the parent note or checking whole-note close status.
- RED characterization proved a selected close/paid row on an open parent `Nota` still created `customer_refunds`.
- Production patch now loads the parent note and rejects refund unless `NoteOperationalStatusResolver::isClose($note)` is true.
- Stale selected-row refund test was updated so open parent note with close selected row is rejected.
- Focused regression passed across selected-row refund, customer refund, partial-paid/open rejection, and closed-note refund controller tests.

Proof:

- RED:
  - `php artisan test tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php --filter=test_close_selected_row_refund_is_rejected_when_parent_note_is_open`
  - Failed as expected.
  - Failure: expected `customer_refunds` count 0, actual count 1.
- Targeted GREEN:
  - Same targeted test passed.
  - 1 test, 5 assertions.
- Selected-row refund file GREEN:
  - `php artisan test tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php`
  - 5 tests, 28 assertions passed.
- Focused regression GREEN:
  - `php artisan test tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`
  - 13 tests, 77 assertions passed.

## Vulnerable Path

Authenticated cashier session
-> open note has one paid/close line and one open/unpaid line
-> cashier submits refund for selected close line
-> `cashier.notes.refunds.store` is reached
-> route is outside `EnsureCashierNoteAccess`
-> controller no longer checks whole-note close status
-> selected-row resolver accepts close selected row
-> refund transaction records refund/allocation
-> open parent note financial state is mutated by refund

## Root Cause

The refund policy was narrowed from whole-note closed validation to selected-row closed validation.

Validasi selected-row tidak cukup untuk business rule ini karena endpoint refund tersebut dinamai dan dimaksudkan sebagai perilaku closed-note refund. Sebuah line bisa close sementara parent note masih open.

## Patch Summary

Patch diterapkan pada:

app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php

Perubahan:

- controller sekarang menerima dependency:
  - `NoteReaderPort`
  - `NoteOperationalStatusResolver`
- sebelum resolve selected-row refund plan, controller membaca parent note dengan:
  - `$notes->getById(trim($noteId))`
- jika note tidak ditemukan, request dikembalikan dengan error refund
- jika parent note belum operationally close, request dikembalikan dengan error refund:
  - `Refund hanya bisa dicatat untuk nota yang sudah close/lunas.`
- `SelectedNoteRowsRefundPlanResolver` dan `RecordSelectedRowsRefundPlanTransaction` hanya dipanggil setelah whole-note close-state guard lolos

Test diubah pada:

tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php

Test intent:

- selected close/paid row pada parent `Nota` yang masih open harus ditolak
- tidak ada `customer_refunds`
- tidak ada `refund_component_allocations`
- selected rows tetap open
- note state dan total tetap unchanged
- tidak ada timeline `note_rows_canceled_via_refund`
- legitimate closed-note selected-row refunds tetap sukses pada focused regression

## Verification

Current Slice 5 proof:

Source anchor:

- `app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`
- imports:
  - `NoteOperationalStatusResolver`
  - `NoteReaderPort`
- invoke dependencies:
  - `NoteReaderPort $notes`
  - `NoteOperationalStatusResolver $statuses`
- guard:
  - `$note = $notes->getById(trim($noteId));`
  - `if (!$statuses->isClose($note))`
  - error message: `Refund hanya bisa dicatat untuk nota yang sudah close/lunas.`

RED proof:

- Command:
  - `php artisan test tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php --filter=test_close_selected_row_refund_is_rejected_when_parent_note_is_open`
- Result:
  - 1 failed
- Failure:
  - expected `customer_refunds` count 0
  - actual entries found: 1
- Interpretation:
  - refund endpoint still mutated an open parent note when selected row was close/paid.

Targeted GREEN proof:

- Command:
  - `php artisan test tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php --filter=test_close_selected_row_refund_is_rejected_when_parent_note_is_open`
- Result:
  - 1 passed, 5 assertions

Selected-row refund file GREEN proof:

- Command:
  - `php artisan test tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php`
- Result:
  - 5 passed, 28 assertions

Focused regression proof:

- Command:
  - `php artisan test tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php tests/Feature/Note/RecordClosedNoteRefundControllerFeatureTest.php`
- Result:
  - 13 passed, 77 assertions

Changed production file:

- app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php

Changed test file:

- tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php

## Verification Gap

Closed by current proof:

- open parent note with close selected row is rejected before refund mutation
- no customer refund is created in the rejected open-parent scenario
- no refund component allocation is created in the rejected open-parent scenario
- selected rows are not canceled in the rejected open-parent scenario
- note state and total remain unchanged in the rejected open-parent scenario
- legitimate closed-note selected-row refund behavior remains covered by focused regression
- interaction with #013 and #014 refund guards remains covered by focused regression

Remaining gaps:

- browser/manual QA not run
- full global `make verify` not run in this step
- route access placement remains covered separately by #022
- refunded terminal-state/edit-entry issues remain covered separately by #018 and #015

## Current Conclusion

Laporan #021 valid sebagai High severity refund lifecycle issue.

RED proof confirmed the bug: selected close/paid rows could be refunded while parent `Nota` was still open.

The current fix closes the #021 parent-note eligibility gap by requiring whole-note operational close before selected-row refund plan resolution and refund transaction execution.

This does not claim full Slice 5 closure. Cashier refund route access, refunded terminal state, and refunded UI/edit entry remain covered by separate Slice 5 issues.

## Relations

Direct follow-up to #014.

#014 covers refund endpoint validation allowing open/unpaid rows to be canceled or refunded.

#021 covers a different refund-state gap: selected rows can be close, but the parent `Nota` is still open. The required invariant is whole-note closed before refund mutation.

Related to #013 because forged or invalid refund flows can produce incorrect refund/finalization behavior.

Related to #018 because refund lifecycle state affects whether notes remain mutable or terminal.

Terkait dengan #009 dan #011 karena route mutasi harus menegakkan state guard sebelum menjalankan perubahan note/payment/refund.

## Related #022 - Cashier refund route bypasses note access guard

#022 is a direct follow-up on the same cashier refund endpoint. #021 covers missing whole-note close-state enforcement in the controller. #022 covers route placement outside `EnsureCashierNoteAccess`, allowing direct refund mutation against notes the cashier should not be able to access.
