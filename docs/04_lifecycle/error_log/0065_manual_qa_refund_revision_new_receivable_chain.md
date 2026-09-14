# 0065 - Manual QA Refund -> Revision -> New Receivable Chain

## Status

OPEN - second independent manual-QA forensic chain captured.

One production contract regression is confirmed from source + runtime evidence.

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
