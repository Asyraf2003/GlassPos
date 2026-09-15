# 0065 - Manual QA Refund -> Revision -> New Receivable Chain

## Status

OPEN - second independent manual-QA forensic chain captured.

Operational reopen / second-close regression: **CLOSED — automated proof GREEN**.
See section 20 for the patch and exact execution evidence. Other issues in this
log remain open and were not part of this fix.

Primary note:

733bd893-8f14-4cbd-8fe7-b958a53e6823

Date:

2026-09-15

Related prior forensic log:

- 0064_manual_qa_revision_payment_surplus_forensic_snapshot.md

Do not patch unrelated finance behavior from this log without focused proof.

---

## 1. Why this chain matters

This is an independent second dataset covering:

create 4 transaction shapes
-> partial payment
-> upward revision
-> full settlement
-> ordinary full product refund
-> post-refund revision
-> new product / new receivable
-> new partial payment
-> final settlement

This chain is especially valuable because it independently tests the previously hardened invariant:

historical refund -> revision -> legitimate new payment -> final settlement

and exercises it through real browser/manual UI behavior.

---

## 2. Initial note

Initial rows:

1. Product only:
   - Busi Aspira Varian 3
   - qty 5
   - subtotal Rp 227.500

2. Service only:
   - Bosklep In (Kecil)
   - Rp 60.000

3. Service + store-stock package:
   - Rp 82.500

4. Service + external purchase:
   - Rp 260.000

Initial total:

Rp 630.000

Payment #1:

- amount paid Rp 293.500
- cash received Rp 300.000
- change Rp 6.500
- outstanding Rp 336.500

Arithmetic:

630.000 - 293.500 = 336.500

Initial cashier history:

- Dibayar Sebagian
- total Rp 630.000
- outstanding Rp 336.500
- 4 Belum Selesai
- work summary: Belum Selesai 4 / Selesai 0 / Batal 0

---

## 3. Upward revision R2

Product qty changed:

5 -> 10

Product subtotal:

227.500 -> 455.000

Revised total:

Rp 857.500

Existing payment:

Rp 293.500

Correct backend payable after revision:

857.500 - 293.500 = 564.000

UI displayed Tagihan Rp 564.000.

This payable amount is correct.

---

## 4. CONFIRMED PRODUCTION BUG - cash pay_full request validator uses raw grand total

### Runtime observation

At R2:

- current grand total = Rp 857.500
- backend/UI payable = Rp 564.000

Owner attempted full settlement with cash sufficient for payable.

Observed first attempt:

- Tagihan = Rp 564.000
- cash input was enough to cover Rp 564.000
- request rejected with:

"Uang masuk cash tidak boleh kurang dari total yang dibayar."

Owner then entered Rp 10.000.000.

Result:

- payment accepted
- change = Rp 9.436.000
- note closed successfully

### Source proof

Current source:

app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspacePaymentValidator.php

still computes:

- grandTotal from current payload items;
- cashTargetAmount for pay_full = grandTotal;
- rejects when received < grandTotal.

Therefore, for this note:

- received Rp 600.000 is >= backend payable Rp 564.000;
- but Rp 600.000 < raw grand total Rp 857.500;
- validator rejects before backend settlement logic can accept the correct payable.

Rp 10.000.000 passes only because it is also >= Rp 857.500.

### Contract proof

ADR 0030 explicitly says:

- existing note payable equals backend settlement outstanding, not raw grand total;
- cash after edit must be at least backend payable;
- request validator must not be financial truth;
- raw grand-total validation for edited/revised notes is rejected behavior.

ADR 0030 Implementation Verification also claims this exact boundary was previously fixed:

- pay_full cash should no longer compare amount_received_rupiah against payload grand total.

Current source contradicts that recorded contract.

### Classification

PRODUCTION BUG / CONTRACT REGRESSION

This is now source + runtime proven.

Do not broaden the fix beyond the request-validator boundary before a focused RED test reproduces this exact R2 scenario.

---

## 5. Full settlement of R2

Accepted payment #2:

- amount paid Rp 564.000
- cash received Rp 10.000.000
- change Rp 9.436.000
- outstanding Rp 0

Total payments:

293.500 + 564.000 = 857.500

This exactly equals R2 total.

Detail showed all four current rows closed and fully paid.

Revision history:

- R1 = Rp 630.000
- R2 = Rp 857.500

Mutation history recorded AUTO_CLOSE_ON_FULL_PAYMENT.

---

## 6. Ordinary full product refund

Owner refunded the product-only Busi row after note was fully settled.

Original Busi R2 subtotal:

Rp 455.000

UI later displayed two historical refund entries:

- Rp 361.500
- Rp 93.500

Total:

361.500 + 93.500 = 455.000

Therefore total ordinary refund equals the full refunded product subtotal exactly.

Mutation history recorded:

- Total before: Rp 857.500
- Total after: Rp 402.500
- reason: Pengembalian dana / pembatalan rincian

Check:

857.500 - 455.000 = 402.500

No arithmetic defect is proven here.

### Candidate interpretation

The two refund rows may represent allocation against multiple historical payment sources.

Do NOT classify them as duplicate refund merely because the UI renders two rows.

Next proof, if investigated:

- customer_refunds;
- refund_component_allocations;
- customer_payment_id linkage.

The invariant is total refund/component cardinality and source allocation, not raw row count in the UI.

---

## 7. Post-refund revision R3 and new receivable

After full refund of the old Busi product, owner revised the note and added a new product.

Final chosen new product:

- Bohlam Lampu Federal Varian 3
- qty 5
- subtotal Rp 665.000

Rows retained from the post-refund note:

- service only Rp 60.000
- service + store-stock package Rp 82.500
- service + external purchase Rp 260.000

Historical net carried settlement after refunded Busi:

Rp 402.500

R3 current total:

60.000 + 82.500 + 260.000 + 665.000 = 1.067.500

Expected new receivable before new payment:

1.067.500 - 402.500 = 665.000

Owner then paid Rp 20.000.

Expected outstanding:

665.000 - 20.000 = 645.000

Observed current detail:

- total Rp 1.067.500
- paid Rp 422.500
- outstanding Rp 645.000

Check:

402.500 historical net carry + 20.000 new payment = 422.500

1.067.500 - 422.500 = 645.000

This is strong manual proof that the core lifecycle:

refund -> revision -> legitimate new receivable -> new payment

is behaving coherently.

---

## 8. Final settlement after refund + revision

Final payment:

- Tagihan Rp 645.000
- cash received Rp 700.000
- change Rp 55.000
- outstanding Rp 0

Final current state:

- note_state: close in UI
- current total Rp 1.067.500
- current paid Rp 1.067.500
- outstanding Rp 0
- all four current rows rendered close/fully paid

Core finance arithmetic:

402.500 carried net after refund
+ 20.000 new partial payment
+ 645.000 final payment
= 1.067.500

This exactly equals R3 current total.

Manual lifecycle result:

GREEN for current-finance arithmetic.

This does NOT close all presentation/history candidates below.

---

## 9. Repeated candidate - payment suggestion/calculator is not current payable

After refund and while preparing R3, owner observed payment suggestions such as:

- Rp 620.000 on an intermediate draft total Rp 800.000;
- Rp 887.500 on the final R3 draft total Rp 1.067.500.

For final R3, correct outstanding before the new Rp 20.000 payment was Rp 665.000.

The application later proves this because after paying Rp 20.000 it reports outstanding Rp 645.000.

Therefore suggestion Rp 887.500 is not the authoritative backend payable.

This repeats the broad symptom already seen in 0064.

### Classification

At minimum:

UI / calculator candidate with repeated manual evidence.

Do not patch until the exact suggestion source is mapped.

The source of truth remains backend settlement payable per ADR 0030.

---

## 10. Repeated presentation inconsistency - cashier history work counts

Final cashier history card showed simultaneously:

- "4 Selesai"
- "Pekerjaan aktif"
- "Lunas"
- "Ada pekerjaan batal"
- "Belum Selesai: 4 • Selesai: 0 • Batal: 1"

This is the second independent manual dataset where line/effective status and work-status summary disagree strongly on the same card.

The formatter itself intentionally exposes two separate inputs:

- lineSummary(openCount, closeCount, refundCount)
- workSummary(openCount, doneCount, canceledCount)

So the immediate issue is not string formatting.

The source counts/read-model semantics differ.

### Classification

CONFIRMED UX/PRESENTATION DEFECT

Even if the underlying two concepts are intentionally different, presenting:

"4 Selesai"

next to:

"Belum Selesai: 4 • Selesai: 0"

without distinguishing what each means is contradictory to a cashier.

Do not mutate finance or work-item status merely to fix the label.

First map the two upstream count sources and rename/reframe if semantics are intentionally different.

---

## 11. Revision history identity is better in this chain but still generic

R3 history reports:

- Line 5 ditambahkan
- Line 1 dihapus

This is materially more truthful than positional replacement.

However the product-only revision rows still render generic labels such as:

- Rincian 5 · Line 5
- Rincian 1 · Line 1

rather than the actual product name in revision history.

This is a presentation quality gap, not a finance defect.

Potential future improvement:

revision snapshots should retain/use meaningful product display labels where available.

---

## 12. Refund history presentation candidate

UI shows two rows:

- Pengembalian 361.500
- Pengembalian 93.500

both for Busi Aspira Varian 3.

Their sum is exactly Rp 455.000.

Before calling this duplicate history, inspect raw tables.

Possible correct model:

one logical refunded component allocated back across multiple historical customer payments.

Potential UX issue remains:

if this is one logical refund action, UI may need grouping by refund operation/component while still preserving source allocation detail.

Classification:

NEEDS RAW DB PROOF

---

## 13. Manual authorization boundary

Owner attempted a post-refund edit as cashier and was rejected because the closed-note revision path requires admin.

Owner then used admin to perform the revision.

This is consistent with the existing closed-note revision boundary observed in 0064.

No authorization bug is proven here.

---

## 14. What this second dataset proves

High-confidence GREEN:

- initial partial payment arithmetic;
- upward revision total;
- backend payable after R2 = Rp 564.000;
- final settlement of R2;
- full ordinary product refund total = Rp 455.000;
- post-refund historical net = Rp 402.500;
- new revision total = Rp 1.067.500;
- new receivable = Rp 665.000;
- new partial payment Rp 20.000;
- resulting outstanding Rp 645.000;
- final payment Rp 645.000;
- final outstanding Rp 0.

Confirmed defect:

- pay_full cash validator still validates received cash against raw current grand total instead of backend payable.

Confirmed presentation defect:

- cashier history exposes contradictory finished/open work counts without semantic distinction.

Repeated candidate:

- UI recommended payment amount is not backend payable after revision/refund.

Needs DB proof:

- why one logical full product refund is rendered as two refund history rows;
- inventory movement/reversal exact cardinality;
- raw customer payment/refund allocation linkage after R3.

---

## 15. Recommended forensic snapshot before mutating this note again

Preserve raw DB state for note:

733bd893-8f14-4cbd-8fe7-b958a53e6823

Priority tables:

- notes
- work_items
- customer_payments
- payment_allocations
- payment_component_allocations
- customer_refunds
- refund_component_allocations
- note_revisions
- note_revision_lines
- note_revision_settlements
- note_revision_surplus_dispositions
- note_revision_surplus_refund_payments
- work_item_store_stock_lines
- inventory_movements
- product_inventory

The snapshot should specifically prove:

1. three/four payment records and their original amounts;
2. refunded Busi component total = Rp 455.000 exactly once at component level;
3. whether two customer_refunds correspond to two historical payment sources;
4. R3 current component allocations total = Rp 1.067.500 after final settlement;
5. refunded historical Busi is not revived as current payable/refundable;
6. old Busi stock was reversed exactly once net;
7. new Bohlam Federal qty 5 reduced current inventory by exactly 5 net.

---

## 16. Next engineering rule

Do not reopen the closed automated refund target from this log.

Handle issues independently:

A. pay_full raw-grand-total validator regression
-> focused RED
-> smallest validator boundary patch
-> focused GREEN
-> adjacent payment/revision regression
-> make verify

B. cashier history contradictory status presentation
-> source-map count semantics
-> choose wording/read-model fix without finance mutation

C. payment suggestion mismatch
-> map UI calculator source against backend settlement preview
-> characterize before patch

D. refund history split
-> raw DB proof first
-> decide whether data is wrong or grouping is only a UI concern


---

## 17. Raw MariaDB forensic proof captured

Owner supplied a full MariaDB snapshot after the final settlement of R3.

Source note:

733bd893-8f14-4cbd-8fe7-b958a53e6823

The raw paste is preserved in the originating QA conversation. Material rows and conclusions are captured below so future sessions do not need to reconstruct the chain from memory.

### Root note row

Final root row:

- note_state = closed
- closed_at = 2026-09-15 01:51:26
- closed_by_actor_id = system
- reopened_at = NULL
- reopened_by_actor_id = NULL
- total_rupiah = 1.067.500
- current_revision_id = ...-r003
- latest_revision_number = 3
- updated_at = 2026-09-15 01:57:09

Important:

The final payment that settled R3 occurred at approximately 01:58, but root closed_at still points to the first R2 closure at 01:51:26.

### Current/preserved work items

Rows physically present:

1. old Busi product-only row:
   - line 1
   - subtotal 455.000
   - status = canceled

2. current service-only:
   - line 2
   - subtotal 60.000
   - status = open

3. current service + store-stock:
   - line 3
   - subtotal 82.500
   - status = open

4. current service + external purchase:
   - line 4
   - subtotal 260.000
   - status = open

5. current new Bohlam product-only:
   - line 5
   - subtotal 665.000
   - status = open

So after the second full settlement, the root note is closed while all four active R3 work items remain persisted as open.

This directly explains the cashier-history work summary:

Belum Selesai: 4 • Selesai: 0 • Batal: 1

while finance/detail projection can simultaneously render the four active rows as settled/close.

### Four actual customer payments

Raw customer_payments proves exactly four payments:

1. Rp 293.500 at 01:46
2. Rp 564.000 at 01:51
3. Rp 20.000 at 01:57
4. Rp 645.000 at 01:58

Total cash-settlement records:

293.500 + 564.000 + 20.000 + 645.000 = 1.522.500

Ordinary refunds:

455.000

Net historical money:

1.522.500 - 455.000 = 1.067.500

This equals final R3 total exactly.

### Refund split is proven correct at ledger level

Raw customer_refunds:

1. Rp 93.500
   - linked to historical payment Rp 293.500
   - same Busi product-only work item/component

2. Rp 361.500
   - linked to historical payment Rp 564.000
   - same Busi product-only work item/component

Raw refund_component_allocations repeats the same component identity for both rows:

- component_type = product_only_work_item
- component_ref_id = old Busi work item id

Amounts:

93.500 + 361.500 = 455.000

Therefore:

NOT A DUPLICATE REFUND.

Classification:

LEDGER CORRECT / PRESENTATION GROUPING CANDIDATE.

One logical full component refund was allocated against two historical payment sources.

UI may still benefit from grouping these rows as one refund action with two source allocations underneath, but the underlying ledger split is correct.

### Revision chain proof

R1:

- total 630.000
- 4 lines

R2:

- total 857.500
- 4 lines

R3:

- total 1.067.500
- 4 lines

Revision snapshots retain product_name_snapshot correctly for product-only rows, including:

- Busi Aspira Varian 3
- Bohlam Lampu Federal Varian 3

Therefore generic UI labels such as "Line 5" are a presentation/read-model limitation, not missing snapshot data.

### R3 settlement row

Raw R3 settlement:

- gross_total_rupiah = 1.067.500
- carry_forward_paid_rupiah = 877.500
- carry_forward_refunded_rupiah = 455.000
- net_paid_rupiah = 422.500
- outstanding_rupiah = 645.000
- surplus_rupiah = 0
- settlement_status = underpaid

Check:

877.500 - 455.000 = 422.500

1.067.500 - 422.500 = 645.000

The 877.500 carry includes the new Rp 20.000 payment recorded in the same R3 transaction window:

857.500 historical paid before R3 + 20.000 new payment = 877.500

This is coherent.

### Final current component allocation proof

SUM(payment_component_allocations.allocated_amount_rupiah):

1.067.500

Exactly equal to final current note total.

Current allocation reconstruction:

- old historical net carried into current components = 402.500
- new Rp 20.000 payment = 20.000
- final Rp 645.000 payment = 645.000

Total:

402.500 + 20.000 + 645.000 = 1.067.500

Finance allocation reconstruction is GREEN.

### Inventory proof

Old refunded Busi current preserved stock line:

- stock_out qty -10 at 01:51
- reversal stock_in qty +10 at 01:52
- same source line identity
- reversal_source_id anchors the reversal

Net:

0

Current Busi inventory projection returns to:

31

which matches the owner's observed pre-sale availability.

New Bohlam Federal:

- stock_out qty -5
- current projection = 11

Owner observed availability before adding qty 5:

16

Check:

16 - 5 = 11

Package stock line:

- one stock_out qty -1
- current projection = 20

No duplicate current-note stock movement is visible in the captured source set.

Inventory behavior for the refunded Busi and new Bohlam is GREEN.

---

## 18. CONFIRMED PRODUCTION BUG - root operational state is not reopened for new receivable

This is a separate bug from the pay_full validator regression.

### Runtime + DB facts

After R2:

- note fully paid;
- root note closed at 01:51:26.

Then:

- old Busi product fully refunded;
- R3 adds a new product and creates legitimate new outstanding;
- UI correctly shows current note as open/underpaid;
- R3 settlement status = underpaid;
- outstanding = 645.000 after the Rp 20.000 payment.

But final raw root row still shows:

- note_state = closed
- closed_at = 01:51:26
- reopened_at = NULL

After final Rp 645.000 payment at 01:58:

- root remains closed with old closed_at;
- no second AUTO_CLOSE_ON_FULL_PAYMENT mutation exists;
- active R3 work_items remain persisted open.

### Source proof

ApplyNoteRevisionAsActiveReplacement:

- updates header;
- rebuilds payment allocations;
- persists replacement work items;
- updates total;
- does NOT call Note::reopen();
- does NOT update operational state.

AutoCloseNoteWhenFullyPaid starts with:

if ($note->isClosed()) {
    return;
}

Therefore a previously closed root that gets a legitimate new receivable through revision can remain physically closed.

When the new receivable is later fully paid, AutoCloseNoteWhenFullyPaid exits immediately because the stale root state is already closed.

### Domain proof

NoteOperationalStateMutations explicitly defines:

- close(): open -> closed
- reopen(): closed -> open and records reopened_at / reopened_by_actor_id

The schema and DB audit classify closed_at / reopened_at as operational/action dates.

Thus current persisted state is not merely a harmless historical marker.

### Classification

PRODUCTION BUG - OPERATIONAL STATE MACHINE / AUDIT METADATA

Symptoms caused by this bug include:

- root note says closed while current settlement is underpaid;
- reopened_at remains null;
- final second settlement does not generate a new close transition;
- closed_at remains the first closure timestamp;
- current active work_items remain open after final settlement;
- cashier history can show "4 Selesai" beside "Belum Selesai: 4".

### Guardrail

Do NOT patch by blindly forcing all work_items to close.

The first focused question is:

When a closed/refunded historical note receives a legitimate new current receivable via authorized revision, what exact transition should happen to the root note?

Expected direction from current domain model:

closed -> reopen -> open

then, after new outstanding reaches zero:

open -> close

But this must be locked by a focused lifecycle test before production mutation.

---

## 19. Updated classification after MariaDB proof

### GREEN / ledger correct

- four customer payments exist exactly as observed;
- ordinary refund total = 455.000;
- refund split across two payment sources is legitimate;
- R3 settlement math is coherent;
- current component allocations total exactly 1.067.500;
- old Busi refund reverses stock qty 10 exactly once for the captured R2 source;
- new Bohlam qty 5 stock-out matches current projection;
- refunded Busi is not revived as current payable.

### CONFIRMED PRODUCTION BUGS

1. pay_full cash request validator still uses raw grand total instead of backend payable after revision.

2. authorized post-close revision that creates a new receivable does not reopen root operational state, leaving stale closed_at/reopened_at and preventing a true second close transition.

### CONFIRMED PRESENTATION / READ-MODEL DEFECT

Cashier history presents effective financial completion and raw operational work status without semantic distinction:

- "4 Selesai"
- "Belum Selesai: 4 • Selesai: 0 • Batal: 1"

The root operational-state bug materially contributes to this mismatch.

Do not treat this as a CSS-only issue.

### UX IMPROVEMENT, NOT LEDGER BUG

Two refund rows for Rp 93.500 and Rp 361.500 are correct source allocations for one Rp 455.000 component refund.

Potential improvement:

group by logical refunded component/action, then show payment-source allocation detail beneath it.

### PRESENTATION GAP

Revision snapshots already contain actual product names, but revision UI renders generic labels such as "Line 5".

This can be improved without changing revision storage semantics.

---

## 20. Operational reopen / second close — focused fix and closure

Target status: **CLOSED** (2026-09-15).

Scope: closed/paid -> historical component refund -> authorized revision creates
new outstanding -> persisted root reopen -> final payment -> a new close transition.
The finance/inventory forensic conclusions above remain accepted; they were not
re-audited or rewritten.

### Ownership and root cause

Classification: **PRODUCTION BUG — operational state machine / audit metadata**.

`CreateNoteRevisionWorkflow` owns the transaction sequence: replacement and
allocation rebuild, inline payment, revision/settlement commit, then projection.
`ApplyNoteRevisionAsActiveReplacement` previously changed the active rows/header/
total without an operational reopen. Domain `NoteOperationalStateMutations`
already implements `reopen()` and `close()`, and `NoteWriterPort` persists their
metadata. The workflow never invoked the reopen transition.

After correcting reopen, the same focused test exposed the second blocker in
`AutoCloseNoteWhenFullyPaid`: it subtracted historical refund from allocations
already rebuilt as net carried settlement. In the focused fixture, final current
allocations are 865000, gross linked payments are 965000, and refund is 100000.
The old close eligibility computed 765000 instead of 865000 and left root open.

### One focused lifecycle regression and exact RED evidence

Execution context for all commands: `/home/asyraf/projects/laravel/GlassPos`,
using the local test database with approved access outside the sandbox.

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php --compact
```

- First RED: **1 failed / 11 assertions / 5.80s**. Settlement underpaid,
  outstanding 645000; expected root open with reopen metadata, actual closed,
  first closed_at retained, reopened_at and reopened_by_actor_id null.
- After reopen patch: **1 failed / 15 assertions / 5.97s**. Persisted reopen
  passed; final payment succeeded, but root remained open with the first
  closed_at. Classified as the auto-close eligibility production bug above.
- Focused GREEN: **1 passed / 26 assertions / 5.84s** after both fixes.

The fixture uses actual create/refund/admin revision requests and the existing
payment handler. It retains a 200000 net historical service settlement, creates
a revised 865000 obligation, pays 20000 inline, then settles the remaining
645000. An injected `ClockPort` advances between operations. It verifies persisted
reopen actor/time, preserved first close until settlement, two distinct close
events/payment references, second close timestamp, and final outstanding zero.
It is a reduced lifecycle reproduction, not a replay of the production note.

### Smallest transition patch

- `app/Application/Note/Services/ReopenNoteForRevisionOutstanding.php`: uses the
  existing `BuildCreateNoteRevisionSettlement` to check new outstanding; for a
  closed root only, calls domain reopen, persists operational state, and records
  `note_reopened` with before/after snapshots. Uses the revision operation's
  clock timestamp and actor; missing optional actor uses system attribution.
- `app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php`: invokes that
  transition after replacement/rebuild and before inline payment, inside the
  existing transaction. Already-open and zero-outstanding roots are not reopened.
- `app/Application/Note/Services/AutoCloseNoteWhenFullyPaid.php`: eligibility uses
  `max(allocated, gross linked paid) - refunded`, matching the existing inline
  payment amount resolver's basis. Allocation/refund writers and carry-forward
  math are unchanged. Existing domain close and timeline recording remain owners
  of the second close transition.
- `tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php`: one new
  focused regression test.

No timestamps are hardcoded in production. No work-item force-close, inventory
change, refund allocation change, or ADR-0044 change was made.

### Adjacent GREEN

```bash
php -d memory_limit=-1 vendor/bin/pest \
  tests/Feature/Note/RefundRevisionOperationalReopenFeatureTest.php \
  tests/Feature/Note/ClosedNoteRevisionPolicyFeatureTest.php \
  tests/Feature/Note/RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest.php \
  tests/Feature/Note/PaymentAfterRevisionSettlementFeatureTest.php \
  tests/Feature/Note/CashierNoteRevisionInlinePaymentContractTest.php \
  tests/Feature/Note/NoteRevisionRollbackFeatureTest.php \
  tests/Feature/Note/NoteOperationalStatePersistenceFeatureTest.php \
  tests/Feature/Note/ReopenClosedNoteFeatureTest.php \
  tests/Feature/Note/AdminReopenClosedNoteHttpFeatureTest.php \
  tests/Feature/Payment/AutoClosePaidNoteOnFullPaymentFeatureTest.php \
  tests/Unit/Core/Note/NoteOperationalStateTransitionsTest.php --compact
```

Result: **21 passed / 159 assertions / 6.36s**, including the focused test.
No adjacent RED or expectation changes were needed.

### Final verify GREEN

```bash
make verify > /tmp/glasspos-0065-final-verify.log 2>&1
```

Actual result: exit code **0**; PHPStan, line audit, Blade audit, and contract
audit PASS; **1707 passed / 11520 assertions / 78.50s**. Duration is the reported
Pest duration. An earlier verify session lost its process handle during the
interruption; no final result is claimed for that run. The completed rerun above
is the final proof.

Source and focused test were found committed locally as `d255bd52` (`Update
project`) on continuation. This section is a subsequent documentation update.

### Residual / stop boundary

- Existing historical production metadata, including the captured note's missed
  reopen/second-close events, is not backfilled by this patch. No production data
  repair was performed and no historical timestamp was invented.
- Manual browser/device validation of the patched lifecycle remains separate
  from the executed automated proof.
- Work-item status presentation, pay_full validator, payment suggestions, refund
  grouping, and generic revision labels remain outside this target. The whole
  log is not closed. No next implementation target is opened by this closure.
