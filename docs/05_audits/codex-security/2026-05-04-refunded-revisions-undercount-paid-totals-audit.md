# Audit — Refunded revisions undercount paid totals

## Status

Draft audit. Belum final fix. Belum commit.

## Issue

Codex finding:

Refunded revisions undercount paid totals.

Claim utama:

Saat Nota yang sudah punya refund direvisi, allocation replay dapat menyimpan alokasi active sebagai net value. Tetapi note-level settlement/status/outstanding masih menghitung allocated minus refunded. Akibatnya refund historis bisa tersubtract dua kali.

Contoh arithmetic:

- customer payment gross: 300000
- historical refund: 100000
- revised active note total: 200000
- expected net settlement: 300000 - 100000 = 200000
- bug shape: rebuilt active allocation 200000, then settlement subtracts refund again
- actual net settlement: 200000 - 100000 = 100000

Impact:

Nota yang harusnya settled bisa terlihat unpaid/open/outstanding. Ini dapat membuka mutation/payment/status flow yang seharusnya terkunci atau berubah behavior.

## Source of truth

Repo local baseline:

- branch: main
- HEAD at audit baseline: fce1bbfd
- latest commit at baseline: Prevent XLSX formula injection in report exports

Binding docs:

- docs/AI_RULES/00-index.md
- docs/03-blueprints/v2/note-finance/2026-04-29-note-finance-stabilization-blueprint.md
- docs/03-blueprints/v2/note-finance/2026-04-29-note-finance-current-projection-addendum.md
- docs/99-archive/handoff/v2/note-finance/2026-04-30-adr-0016-completion-handoff.md

## Locked project/domain rules

From note finance blueprint:

- Nota revision tetap harus didukung.
- Payment/refund/inventory movement adalah financial/historical events.
- Work item lama yang menjadi anchor payment/refund/history tidak boleh dihancurkan.
- Current operational projection harus dipisahkan dari ledger/history.
- Reporting harus membedakan current projection dan ledger/history.

From current projection addendum:

- Edit always available.
- Refund always available for current version.
- Legacy rows are not current rows.
- Existing ledger events remain valid.
- Final direction: immutable ledger/history + current projection table.
- Refund baru hanya boleh terhadap current active projection.
- Edit tidak boleh destructive terhadap ledger/history.

From ADR-0016/ADR-0021 handoff:

- Refund engine should not be changed again unless new proof shows an actual finance ledger bug.
- Pair cap invariant:
  pair_cap = min(customer_payment.amount_rupiah, active_component_allocated + component_refunded)

## User decisions captured during this audit

- User is the owner of direction, rules, decision, and final judgment.
- Assistant role is audit, ask for decisions, and execute fixes after decision.
- Fix must not proceed without blueprint/DoD/ADR basis.
- Previous payment/refund/revision history must remain precise and auditable.
- Revision should not silently destroy prior money/history.
- Previous money should be reallocated intelligently by priority when note is revised.
- If edited total is lower than previous paid amount, the domain must explicitly represent overpaid/change/refund/customer-credit behavior instead of pretending the note is unpaid.
- Codex criticism is accepted as a valid risk signal, but final fix direction must be decided by user after audit.

## Files touched by assistant so far

### Modified

- app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php
- tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php

### Created / untracked

- tests/Feature/Payment/DatabasePaymentComponentAllocationReaderAdapterFeatureTest.php

### Not modified yet

- app/Adapters/Out/Payment/DatabasePaymentComponentAllocationReaderAdapter.php
- app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php
- app/Application/Note/Policies/NotePaidStatusPolicy.php
- app/Application/Note/Services/NoteOperationalStatusResolver.php
- app/Application/Note/Services/NoteOutstandingPaymentAmountResolver.php
- app/Application/Note/Services/AutoCloseNoteWhenFullyPaid.php
- revision use cases
- refund engine
- current projection tables/services

## Local proof collected

### Payment allocation reader red proof

Test added:

tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php

Test name:

test_note_total_includes_component_refunds_for_revised_note_component_flow

Before patch:

- expected: 300000
- actual: 200000

Meaning:

DatabasePaymentAllocationReaderAdapter::getTotalAllocatedAmountByNoteId() only returned current component allocation and ignored historical component refund add-back.

### Payment allocation reader green proof after draft patch

Commands proven:

- php -l app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php
- php -l tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php
- filtered Pest test
- full DatabasePaymentAllocationReaderAdapterFeatureTest

Results proven:

- filtered test: passed
- adapter feature test: 3 passed
- payment feature suite: 20 passed, 76 assertions

### Component allocation reader red proof

Test added:

tests/Feature/Payment/DatabasePaymentComponentAllocationReaderAdapterFeatureTest.php

Test name:

test_note_total_includes_component_refunds_for_revised_note_component_flow

Before patch:

- expected: 300000
- actual: 200000

Meaning:

DatabasePaymentComponentAllocationReaderAdapter::getTotalAllocatedAmountByNoteId() also reads only current component allocation.

This matters because AutoCloseNoteWhenFullyPaid uses PaymentComponentAllocationReaderPort, not PaymentAllocationReaderPort.

## Current draft patch hypothesis

Draft patch currently applied only to:

app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php

Hypothesis:

For note-level settlement, component allocation reader should expose gross basis:

component_total + component_refund_total

Then existing settlement code can safely compute:

net_paid = allocated_gross - refunded_total

This avoids double subtraction when revision replay stored active allocation net of historical refund.

## Why this is not final yet

This is a finance lifecycle issue.

The patch may be correct for note-level status/outstanding, but it can be wrong if another flow expects getTotalAllocatedAmountByNoteId() to mean active/current allocated only.

Known risk:

- DatabasePaymentAllocationReaderAdapter and DatabasePaymentComponentAllocationReaderAdapter have overlapping but different consumers.
- AutoCloseNoteWhenFullyPaid uses PaymentComponentAllocationReaderPort.
- Some projection/current services may already compute net/current settlement differently.
- Blueprint says current projection and ledger/history must be separated.
- A reader-level patch can hide domain meaning if "allocated" is not named gross/current explicitly.

## Options for final fix

### Option A — Gross-back in note-level allocation readers

Patch:

- DatabasePaymentAllocationReaderAdapter::getTotalAllocatedAmountByNoteId()
- DatabasePaymentComponentAllocationReaderAdapter::getTotalAllocatedAmountByNoteId()

Rule:

If component allocation/refund rows exist, return component allocated + component refunded.

Pros:

- Minimal.
- Matches existing pair_cap invariant.
- Keeps downstream allocated minus refunded logic unchanged.
- Directly fixes observed undercount shape.

Cons:

- Method name does not reveal gross basis.
- May affect consumers expecting active allocation only.
- Still mixes ledger semantics into generic reader.

### Option B — Change revision replay to store gross allocation

Patch:

- NoteReplacementPaymentAllocationReconciler::captureAllocatedAmounts()
- Possibly rebuild allocation logic.

Rule:

Replay allocations should preserve gross paid basis and let refund subtraction happen only in settlement.

Pros:

- Keeps reader meaning closer to stored allocation.
- May align better with gross ledger semantics.

Cons:

- Bigger blast radius.
- Could over-allocate active components if not bounded by current total and pair cap.
- Risky around partial payment/refund/revision.
- Touches refund/revision engine, which ADR handoff warns not to change without strong proof.

### Option C — Dedicated settlement reader/service

Patch:

Create explicit service/method such as:

- getGrossAllocatedAmountByNoteId()
- getCurrentSettlementByNoteId()
- getLedgerSettlementByNoteId()

Pros:

- Strong semantics.
- Avoids hiding gross/current distinction.
- Aligns with blueprint separation.

Cons:

- Bigger implementation.
- Requires consumer refactor.
- Needs broader tests.

### Option D — Current projection settlement fix

Patch current projection/current note services only.

Pros:

- Aligns with Hybrid C+ final direction.

Cons:

- May not fix existing note-level policy/outstanding/auto-close if they still use old readers.
- Bigger scope.
- Needs projection contract review.

### Option E — Containment guard only

Patch mutation guards to prevent mutation when undercount shape detected.

Pros:

- Low immediate mutation-risk.

Cons:

- Does not fix outstanding/status math.
- Not final.
- Can block valid user flow.
- Blueprint says edit/revision must remain available.

## Recommended direction for user decision

Recommendation for review, not final decision:

Option A as a narrow immediate remediation can be acceptable only if we explicitly document note-level reader semantics as gross basis and prove all direct consumers.

Minimum extra proof required before commit:

1. Component reader regression red then green.
2. Payment suite pass.
3. Note feature suite pass.
4. Relevant revision/refund tests pass.
5. At least one consumer-level test proves:
   - paid status becomes paid
   - operational status becomes close/settled
   - outstanding resolver returns PAYMENT_ALREADY_PAID or outstanding 0
   - auto-close path does not undercount

If user rejects Option A, revert draft patch and choose Option B/C/D.

## Stop conditions

Stop immediately if:

- test failure shows existing current projection expects active-only allocation from the patched method
- report totals change unexpectedly
- revision/refund test fails
- payment cap exceeds customer payment amount
- mutation guard becomes less strict for unpaid notes
- any patch requires rewriting historical refund/payment rows without explicit ADR update

## Next decision required from user

Choose one:

A. Continue with Option A narrow gross-back reader remediation.
B. Revert draft reader patch and investigate revision replay basis.
C. Design explicit settlement reader/service before patching.
D. Move fix into current projection layer.
E. Apply temporary containment only while designing final model.

No final patch or commit should happen until this decision is made.
