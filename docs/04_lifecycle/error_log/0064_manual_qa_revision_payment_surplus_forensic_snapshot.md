# 0064 - Manual QA Revision / Payment / Surplus Forensic Snapshot

## Status

OPEN - forensic evidence captured, no production patch authorized from this log alone.

Date: 2026-09-15

Primary note:

7a129d82-3887-40bf-bb24-aebd0cc9286c

Purpose:

Preserve one real manual-QA lifecycle chain, its observed UI state, and its MariaDB snapshot before the note is mutated again.

This file is intentionally an evidence log, not a claim that every suspicious presentation is a production bug.

Use the usual classification loop before any patch:

FACT -> CONTRACT -> TEST WRONG / PRODUCTION BUG / CONTRACT GAP -> smallest action -> proof.

---

## 1. Manual QA chain captured

The owner created one note containing four transaction shapes:

1. store-stock product only;
2. service only;
3. service + store-stock package;
4. service + external purchase.

Initial total:

Rp 1.093.000

Initial material examples:

- Bearing Roda Aspira Varian 3, qty 3, Rp 99.500 each = Rp 298.500;
- Bosklep In (Besar) service = Rp 75.000;
- service + store-stock package total = Rp 614.500;
- service + external purchase total = Rp 105.000.

Payment #1:

- settlement Rp 302.000;
- cash received Rp 400.000;
- change Rp 98.000;
- resulting outstanding shown Rp 791.000.

Revision R2:

- Bearing qty 3 -> qty 2;
- note total Rp 1.093.000 -> Rp 993.500.

Payment #2:

- settlement Rp 232.000;
- cash received Rp 300.000;
- change Rp 68.000;
- resulting outstanding shown Rp 459.500.

Attempted ordinary refund while note was still open / partially paid:

- rejected;
- owner interpretation: current refund contract correctly requires a closed/settled note for ordinary refund.

Payment #3:

- settlement Rp 459.500;
- cash received Rp 500.000;
- change Rp 40.500;
- note reached outstanding Rp 0 and closed.

Cashier then attempted to edit the closed note:

- access denied / 403;
- closed-note direct cashier revision remains blocked.

Admin performed revision R3:

- removed service-only row;
- removed service + external purchase row;
- retained Bearing qty 2;
- retained service + store-stock package;
- current total Rp 993.500 -> Rp 813.500;
- over-settlement/surplus = Rp 180.000;
- system created revision-surplus refund due and refund-paid records.

Current UI after R3 reported:

- status Lunas;
- current total Rp 813.500;
- current paid Rp 813.500;
- current outstanding Rp 0;
- revision surplus refund Rp 180.000.

---

## 2. High-confidence arithmetic checkpoints

These are arithmetic facts from the observed chain and database snapshot.

### R1

Grand total:

1.093.000

Payment #1:

302.000

Outstanding:

791.000

Check:

1.093.000 - 302.000 = 791.000

### R2

Grand total:

993.500

Total original cash settlement after payment #2:

302.000 + 232.000 = 534.000

Expected outstanding:

993.500 - 534.000 = 459.500

This matches the owner-observed UI after payment #2.

### Full settlement before R3

Payment #3:

459.500

Total original payment settlement:

302.000 + 232.000 + 459.500 = 993.500

This exactly settles R2.

### R3

Current grand total:

813.500

Historical settled amount before downward revision:

993.500

Surplus:

993.500 - 813.500 = 180.000

MariaDB records exactly one active revision-surplus disposition for Rp 180.000 and one active surplus refund payment for Rp 180.000.

The current payment-component allocations sum to Rp 813.500, equal to the R3 current total.

Breakdown by payment id after reconciliation:

- payment id 12d2394e... = 199.000 + 33.000 = 232.000;
- payment id dacc6c1d... = 152.500 + 127.000 = 279.500;
- payment id f56bc197... = 111.000 + 162.500 + 28.500 = 302.000.

Total current allocations:

232.000 + 279.500 + 302.000 = 813.500

The remaining Rp 180.000 is represented by the revision-surplus refund path, not ordinary customer_refunds.

---

## 3. Inventory checkpoint

Observed product availability before the transaction was recorded by the owner as:

- Bearing: 14 available, selected qty 3;
- Bohlam: 39 available, package qty 1;
- CDI ECU: 28 available, package qty 1;
- Ring Piston: 17 available, package qty 1.

After R3, MariaDB current projection reports:

- Bearing qty_on_hand = 12;
- Bohlam qty_on_hand = 38;
- CDI ECU qty_on_hand = 27;
- Ring Piston qty_on_hand = 16.

This is consistent with the final current note:

- Bearing final qty 2 -> 14 - 2 = 12;
- Bohlam qty 1 -> 39 - 1 = 38;
- CDI qty 1 -> 28 - 1 = 27;
- Ring Piston qty 1 -> 17 - 1 = 16.

This is evidence that the final stock projection is coherent with the current revision.

It does NOT yet prove the full movement ledger is free from duplicate reversal/reissue rows because inventory_movements was not included in this snapshot.

Future stock forensic proof should inspect movement sources and net qty deltas before classifying revision inventory behavior as fully proven.

---

## 4. Candidate findings preserved for later audit

No patch should be made from these candidates until source/read-model evidence is inspected.

### QA-CANDIDATE-01 - payment suggestion mismatch

At one point after R2, the owner remembers a suggested partial-payment amount of Rp 716.500.

Given:

- R2 total = Rp 993.500;
- payment #1 = Rp 302.000;

simple outstanding arithmetic would be:

Rp 691.500

The remembered Rp 716.500 therefore needs source/payload proof.

Possible classifications remain open:

- owner/UI observation mismatch;
- stale suggestion/read-model;
- different selected-component basis;
- production presentation bug.

Do not patch from memory alone.

### QA-CANDIDATE-02 - job status summary disagreement

The UI was observed showing both:

- "3 Belum Selesai, 1 Selesai";
- and "Belum Selesai: 4 • Selesai: 0 • Batal: 0"

on the same lifecycle phase.

If reproduced from one response payload, this is a presentation/read-model inconsistency.

Needs focused reproduction.

### QA-CANDIDATE-03 - revision diff may be positional, not identity-based

R3 history narrated approximately:

Line 2: Bosklep In -> Default Service ...

while the actual owner action was closer to:

- remove previous service-only line;
- retain the previous package line;
- line numbering shifts after deletion.

The revision history may therefore be comparing by visible position/line number rather than stable semantic identity.

Financial state can still be correct while the audit narrative is wrong.

Needs source and snapshot identity inspection.

### QA-CANDIDATE-04 - payment timeline meaning changes after revision

Before R3, payment #3 was presented as Pelunasan Rp 459.500.

After downward R3, the same historical payment appeared as Bayar Sebagian Rp 459.500 with only Rp 279.500 allocated to the current note and Rp 180.000 becoming surplus.

Open contract question:

Should payment history preserve event-time meaning, while current-revision reconciliation is presented separately?

Or is current-revision reinterpretation intentionally authoritative for the timeline?

Do not decide from UI preference alone.

### QA-CANDIDATE-05 - "Sisa" semantics in payment history are unclear

After R3, historical rows displayed balances that are difficult to read as one chronological outstanding sequence.

Need to determine whether each displayed "Sisa" means:

- outstanding at original event time;
- current-revision replay balance;
- unallocated money;
- or another settlement projection.

If current semantics are correct, the label may still be misleading.

### QA-CANDIDATE-06 - ordinary refund summary vs revision-surplus refund

Current summary showed ordinary Pengembalian = 0 while a separate section showed revision-surplus refund = Rp 180.000.

Backend separation can be correct:

- ordinary component refunds use customer_refunds;
- revision surplus uses note_revision_surplus_dispositions and note_revision_surplus_refund_payments.

However, owner-facing presentation can read as contradictory.

Potential UX follow-up:

- distinguish "Refund Komponen" from "Pengembalian Surplus Revisi";
- do not merge their ledgers merely to make the UI look simpler.

### QA-CANDIDATE-07 - refund surplus timestamp presentation

R3 was created at approximately 00:41:45.

Both surplus rows carry:

occurred_at = 2026-09-15 00:00:00

while:

created_at = 2026-09-15 00:41:45

This strongly suggests occurred_at currently represents date-level effective semantics, not a precise action timestamp.

If UI renders 00:00 as though it were the actual event clock time, presentation is misleading even if ledger data is contractually valid.

Needs contract/source classification before patch.

### QA-CANDIDATE-08 - note closed while persisted current work items are open

MariaDB current state:

- notes.note_state = closed;
- current work_items.status = open.

The owner-facing detail renders those current lines operationally as close/settled.

This may be intentional projection semantics, especially after revision replacement and payment settlement.

Do not "fix" persisted work_items merely because the UI says close.

Needs contract/source proof if this becomes a problem.

---

## 5. Evidence limitation discovered in the forensic query

The captured SQL section:

CUSTOMER PAYMENTS

returned no rows.

This does NOT prove customer_payments are absent.

The forensic query selected customer_payments by joining through payment_allocations, while current state has no note-level payment_allocations rows and does have payment_component_allocations referencing three customer_payment_id values.

Therefore the forensic command itself was incomplete for raw customer payment retrieval.

Future forensic query should fetch customer_payments from the union of payment_allocations and payment_component_allocations payment ids, or query the three referenced ids directly.

Likewise, the raw snapshot did not include:

- note_revision_lines;
- note_revision_settlements;
- inventory_movements;
- audit_events/snapshots for the R3 surplus;
- note mutation events.

Those are the next evidence sources only if a candidate above is actively investigated.

---

## 6. Critical-engineering verification protocol candidate

For finance/inventory lifecycle work with long manual chains, a future session may use an independent recall cross-check before classification.

Example pattern:

1. preserve raw DB/UI evidence first;
2. without revealing the expected answer, ask the owner to restate checkpoint A;
3. separately ask what A looked like after event B;
4. compare the independent owner recollection against stored raw evidence;
5. use database/source/tests, not memory, as final authority.

This can help detect context drift in long human + AI engineering sessions.

It is NOT a substitute for database/runtime evidence and should never be the sole basis for a production patch.

---

## 7. UI cleanup notes observed in the same manual-QA campaign

These are presentation backlog notes, not lifecycle bug classifications.

### Service package actions

Desired top-level actions:

- Edit;
- Detail;
- Nonaktifkan.

Do not invent a fourth action only to make the UI symmetrical.

Inside package detail:

- show every linked product directly;
- if three products are linked, show all three.

Remove owner-facing placeholder text:

"Harga modal produk dan riwayat pemakaian paket akan masuk slice berikutnya setelah relasi detail produk dipasang."

Bottom/header actions should be limited to the package-level actions actually needed, especially:

- Edit Paket;
- Nonaktifkan.

Avoid redundant "Lihat Produk" / "Edit Jasa" if equivalent navigation already exists in linked-entity sections.

### Product detail destructive-action consistency

Nonaktifkan should have a stable location at the lower-right action area.

Color semantics should be normalized across admin detail pages:

- normal edit/detail: neutral/primary;
- reversible destructive disable: one consistent destructive style;
- delete: strongest danger/red style.

Avoid arbitrary yellow vs red destructive semantics across pages.

### Supplier detail action hierarchy

Move Pembayaran into the main action flow above/before Koreksi instead of isolating it at the upper-right.

### Global table/action consistency

Audit shared admin surfaces for:

- table structure;
- padding;
- headers;
- action-button style;
- borders;
- card nesting;
- badge/status treatment.

Equivalent tables/actions should use equivalent visual grammar.

Reduce unnecessary nested bordered containers where they obscure information hierarchy.

---

## 8. Raw MariaDB forensic snapshot

Captured from the owner local database after R3 and before further mutation.

~~~text
=== NOTE ===
id: 7a129d82-3887-40bf-bb24-aebd0cc9286c
customer_name: Pelanggan baru
transaction_date: 2026-09-15
note_state: closed
closed_at: 2026-09-15 00:38:38
closed_by_actor_id: system
total_rupiah: 813500
current_revision_id: 7a129d82-3887-40bf-bb24-aebd0cc9286c-r003
latest_revision_number: 3
due_date: 2026-10-15
created_at: 2026-09-15 00:33:21
updated_at: 2026-09-15 00:41:45

=== CURRENT WORK ITEMS ===
line 1:
id: be872546-88ad-45e3-bb08-9994c8a81e50
transaction_type: store_stock_sale_only
status: open
subtotal_rupiah: 199000
created_at: 2026-09-15 00:41:45

line 2:
id: 47b3a602-a405-4379-9860-c00102a42e36
transaction_type: service_with_store_stock_part
status: open
subtotal_rupiah: 614500
created_at: 2026-09-15 00:41:45

=== PAYMENT ALLOCATIONS ===
no rows returned by captured query

=== PAYMENT COMPONENT ALLOCATIONS ===
payment 12d2394e-5fd1-4972-a31a-7f67727f3d8c
- product_only_work_item / current line 1: 199000
- package Bohlam component e16cf57c...: 33000

payment dacc6c1d-52b3-43e7-8e87-a9397f98b587
- package Ring component 416c6373...: 152500
- service_fee current line 2: 127000

payment f56bc197-8418-4c28-80bf-5dbe1f7e1f13
- package Bohlam component e16cf57c...: 111000
- package CDI component 9112bfa5...: 162500
- package Ring component 416c6373...: 28500

Current payment-component allocation total: 813500

=== CUSTOMER PAYMENTS ===
no rows returned by captured query
NOTE: query limitation; component allocations prove three payment ids are referenced.

=== ORDINARY REFUNDS ===
no rows

=== REFUND COMPONENT ALLOCATIONS ===
no rows

=== REVISIONS ===
R1:
id: ...-r001
parent: NULL
grand_total_rupiah: 1093000
line_count: 4
created_at: 2026-09-15 00:33:21

R2:
id: ...-r002
parent: ...-r001
grand_total_rupiah: 993500
line_count: 4
created_at: 2026-09-15 00:35:32

R3:
id: ...-r003
parent: ...-r002
grand_total_rupiah: 813500
line_count: 2
created_at: 2026-09-15 00:41:45

=== SURPLUS DISPOSITION ===
id: e5049f37-eff6-43e3-814f-b584c24456c4
settlement: ...-r003-settlement
revision: ...-r003
disposition_type: refund_due
amount_rupiah: 180000
before_pending_rupiah: 180000
after_pending_rupiah: 0
status: active
occurred_at: 2026-09-15 00:00:00
created_at: 2026-09-15 00:41:45

=== SURPLUS REFUND PAYMENT ===
id: a8fd2af5-5dcb-42b5-84f2-3d4b706546d8
disposition_id: e5049f37-eff6-43e3-814f-b584c24456c4
settlement: ...-r003-settlement
revision: ...-r003
amount_rupiah: 180000
effective_date: 2026-09-15
occurred_at: 2026-09-15 00:00:00
status: active
idempotency_key: auto-refund-paid:7a129d82-3887-40bf-bb24-aebd0cc9286c-r003-settlement
created_at: 2026-09-15 00:41:45

=== CURRENT STORE STOCK LINES ===
line 1 / Bearing:
qty: 2
line_total_rupiah: 199000
qty_on_hand: 12

line 2 / Ring:
qty: 1
line_total_rupiah: 181000
qty_on_hand: 16

line 2 / CDI:
qty: 1
line_total_rupiah: 162500
qty_on_hand: 27

line 2 / Bohlam:
qty: 1
line_total_rupiah: 144000
qty_on_hand: 38
~~~

Full raw MariaDB evidence is preserved in:

docs/04_lifecycle/error_log/evidence/0064_manual_qa_mariadb_raw.txt

The summarized rows above are for reasoning convenience; the companion file is the evidence source when exact ids/amounts need to be rechecked. This error log preserves the material rows and explicit limitations required for later classification.

---

## 9. Next action

Do not mutate this note further until the owner intentionally resumes the manual-QA chain.

When resumed, choose exactly one candidate and prove it.

Recommended first forensic target:

payment/revision history presentation after R3, because finance arithmetic currently reconciles while the history wording may be reinterpreting historical payment meaning.

Do not reopen already-closed automated refund hardening merely because this manual-QA log exists.
