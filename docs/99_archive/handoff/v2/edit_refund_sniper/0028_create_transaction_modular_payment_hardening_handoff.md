# Create Transaction Modular Payment Hardening Handoff

Status: Session-safe local proof recorded
Scope: create transaction payment seam only
Date: 2026-05-16

## Purpose

This handoff records the create transaction payment hardening session.

The session goal was not to implement edit, refund, mixed payment, denomination, or package pricing.

The goal was to make create transaction safer as a modular foundation before future features such as:

- service + sparepart package price entered once
- separate cash and transfer handling
- no-payment / hutang create flow
- partial payment create flow
- future pricing allocation module
- future cash denomination / pecahan module

## Locked Direction

Create transaction must act as a modular composer.

Create transaction must not absorb every domain responsibility.

Create transaction should compose these seams:

1. Transaction header module

Responsible for customer, phone, date, actor, note identity, and audit context.

2. Item entry module

Responsible for service only, product/store stock only, service with store stock, service with external purchase, and future service with store stock package price.

3. Pricing module

Responsible for normal line pricing, future package total allocation, price snapshot, allocation method, and rounding residual.

4. Payment intent module

Responsible for skip, pay_full, and pay_partial.

5. Payment instrument module

Responsible for cash and transfer.

Cash owns amount paid, amount received, change, and future denomination detail.

Transfer owns transfer method persistence and future transfer metadata.

6. Settlement allocation module

Responsible for payment allocation into payment_component_allocations.

7. Inventory issue module

Responsible for stock out through inventory movement.

8. Audit module

Responsible for actor, source, note id, payment method, amount received, change, and create source metadata.

## Important Domain Decision

Hutang and lunas are not payment methods.

Payment methods are instruments such as:

- cash
- transfer

Payment intent is:

- skip
- pay_full
- pay_partial

Settlement result is derived from obligation versus payment:

- unpaid / hutang / outstanding
- partial
- paid / lunas
- overpaid / refund due

Future package service + sparepart pricing must belong to the pricing allocation module.

It must not be embedded directly into payment, settlement, inventory, or create handler branching.

## Starting Blueprint Source

Primary blueprint source:

- docs/03_blueprints/finance/0009_create_transaction_domain_risk_handoff.md

That blueprint identified the create transaction high-risk seam as cash/payment audit fidelity, not basic note creation.

The target gaps were:

- customer_payments.payment_method was not proven for cash
- customer_payment_cash_details was not proven for cash
- amount_paid_rupiah was not proven in cash detail
- amount_received_rupiah was not proven in cash detail
- change_rupiah was not proven in cash detail
- transfer payment method proof was weak
- skip/no-payment proof was weak
- partial transfer proof was weak

## Local Proof Summary

The owner reported local targeted tests PASS after each step.

Exact assertion counts were not pasted for the final PASS messages, so this handoff records them as owner-reported local PASS, not exact-count proof.

### Step 1 - Full Cash Create Payment

File hardened:

- tests/Feature/Note/CreateTransactionWorkspaceFullCashFeatureTest.php

Production file patched:

- app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php

RED proof before patch:

- Create full cash persisted customer_payments.payment_method as unknown.
- Expected cash.
- Actual similar row showed amount_rupiah 150000, payment_method unknown, paid_at 2026-03-15.

Patch intent:

- Pass resolved payment method into CustomerPayment::create.
- Build CustomerPaymentCashDetail for cash payments.
- Persist customer payment with cash detail through CustomerPaymentWriterPort.
- Add audit metadata for payment_method, amount_received, and change.

Owner-reported GREEN:

- CreateTransactionWorkspaceFullCashFeatureTest PASS.

### Step 2 - Full Transfer Create Payment

File added/hardened:

- tests/Feature/Note/CreateTransactionWorkspaceFullTransferFeatureTest.php

RED proof before expectation correction:

- Create full transfer persisted customer_payments.payment_method as tf.
- Test expected transfer.

Source contract found:

- CustomerPaymentMethod::TRANSFER canonical value is tf.
- CustomerPaymentMethod::normalize('transfer') returns tf.

Decision:

- UI/request may send transfer.
- Domain/database canonical value remains CustomerPayment::METHOD_TRANSFER.
- Test should assert CustomerPayment::METHOD_TRANSFER, not literal transfer.

Owner-reported GREEN:

- CreateTransactionWorkspaceFullTransferFeatureTest PASS.
- Combined full cash + full transfer PASS.

### Step 3 - Skip / No-Payment Create Flow

File hardened:

- tests/Feature/Note/CreateTransactionWorkspaceSkipFeatureTest.php

Existing proof before hardening:

- create skip redirected to history
- notes row was created
- customer_payments count was 0

Additional assertions added:

- payment_component_allocations missing for note
- payment_allocations missing for note

Owner-reported GREEN:

- CreateTransactionWorkspaceSkipFeatureTest PASS.
- Combined full cash + full transfer + skip PASS.

### Step 4 - Partial Transfer Create Flow

File hardened:

- tests/Feature/Note/CreateTransactionWorkspacePartialTransferFeatureTest.php

Existing proof before hardening:

- amount_rupiah 50000 persisted
- paid_at persisted

Additional assertions added:

- note total_rupiah 150000
- payment_method CustomerPayment::METHOD_TRANSFER
- customer_payment_cash_details missing
- payment_component_allocations allocated_amount_rupiah 50000
- legacy payment_allocations missing

Owner-reported GREEN:

- CreateTransactionWorkspacePartialTransferFeatureTest PASS.
- Combined full cash + full transfer + skip + partial transfer PASS.

## Files Changed In This Session

Production:

- app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php

Tests:

- tests/Feature/Note/CreateTransactionWorkspaceFullCashFeatureTest.php
- tests/Feature/Note/CreateTransactionWorkspaceFullTransferFeatureTest.php
- tests/Feature/Note/CreateTransactionWorkspaceSkipFeatureTest.php
- tests/Feature/Note/CreateTransactionWorkspacePartialTransferFeatureTest.php

Docs from this command:

- docs/99_archive/handoff/v2/edit_refund_sniper/0028_create_transaction_modular_payment_hardening_handoff.md
- docs/03_blueprints/finance/README.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md

## Files Not Touched Intentionally

Do not infer work was done on:

- edit/revision
- refund
- mixed cash + transfer
- denomination / pecahan
- service + sparepart package price implementation
- external purchase lifecycle
- PostgreSQL migration
- Go API
- reporting rewrite
- inventory reversal rules

## Current Status

Create transaction payment seam is stronger than before.

The following create payment paths are now owner-reported PASS:

- pay_full + cash
- pay_full + transfer
- skip / no payment
- pay_partial + transfer

This does not mean create transaction is fully mature.

It means the payment seam now has better proof and can support the next design step.

## Remaining Gaps

Exact final assertion counts were not pasted.

No make verify proof was recorded in this handoff.

No commit/push proof is recorded here because owner handles commit and push manually.

Partial cash create flow now has focused proof in Step 5.

Payment validation for full cash insufficient received amount is still a P2 seam from 0009.

Mixed cash + transfer remains out of scope.

Package service + sparepart single nominal remains design-only.

Item modularity is not fully proven yet across:

- service only
- product/store stock only
- service with store stock
- service with external purchase
- future package pricing allocation

## Recommended Next Active Step

Do not implement service + sparepart package pricing yet.

Next safest step:

1. Harden partial cash create proof.
2. Then run the focused create payment matrix:
   - full cash
   - full transfer
   - skip
   - partial transfer
   - partial cash
3. Then inspect item compiler/composer seams.
4. Then draft package pricing allocation blueprint.

## Future Package Pricing Direction

Future service + sparepart one-nominal feature should be implemented as pricing allocation, not payment logic.

Possible safe model:

- User enters one package_total_rupiah.
- Item rows still preserve service and sparepart facts.
- Pricing module allocates package_total_rupiah into:
  - allocated_service_revenue
  - allocated_sparepart_revenue per line
  - original_package_total_rupiah
  - allocation_method
  - rounding_residual_rupiah
  - reference price snapshot
- Sum of allocated values must exactly equal package_total_rupiah.
- Inventory still issues by sparepart quantity.
- COGS still comes from inventory/costing.
- Refund/edit must use persisted allocation snapshot, not latest catalog price.

Preferred initial allocation option:

- part-first residual if sparepart sale price must stay traceable
- proportional allocation if package discount must spread across service and sparepart

This decision is not locked yet.

## Opening Prompt For Next Session

Kita sedang di repo HyperPOS Laravel:

/home/asyraf/Code/laravel/bengkel2/app

Baca dulu:

docs/03_blueprints/finance/0009_create_transaction_domain_risk_handoff.md
docs/99_archive/handoff/v2/edit_refund_sniper/0028_create_transaction_modular_payment_hardening_handoff.md

Mode kerja wajib:

- Blueprint/proof-first.
- One active target per step.
- Jangan broad audit repo.
- Jangan mulai dari git status/log/push/remote sync/make verify.
- Owner handles commit/push/manual sync.
- Local command output owner adalah source of truth tertinggi.
- Jangan klaim fixed/full green tanpa proof command lokal.
- Jangan patch sebelum target terbukti dari docs/source/test/output.
- Gunakan rg/fd/sed, jangan grep -R/find.
- Jangan implement service + sparepart package pricing dulu.

Locked latest facts:

- Create transaction should be modular composer.
- Hutang/lunas are settlement results, not payment methods.
- cash/transfer are payment instruments.
- skip/pay_full/pay_partial are payment intent.
- Package service+sparepart one nominal belongs to pricing allocation module, not payment logic.

Owner-reported PASS in previous session:

- CreateTransactionWorkspaceFullCashFeatureTest
- CreateTransactionWorkspaceFullTransferFeatureTest
- CreateTransactionWorkspaceSkipFeatureTest
- CreateTransactionWorkspacePartialTransferFeatureTest
- Combined focused payment matrix for those four tests

Production file patched previously:

- app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php

Tests hardened previously:

- tests/Feature/Note/CreateTransactionWorkspaceFullCashFeatureTest.php
- tests/Feature/Note/CreateTransactionWorkspaceFullTransferFeatureTest.php
- tests/Feature/Note/CreateTransactionWorkspaceSkipFeatureTest.php
- tests/Feature/Note/CreateTransactionWorkspacePartialTransferFeatureTest.php

Next safest active step:

Harden partial cash create proof only.

Do not implement package pricing yet.

First inspect whether a partial cash test already exists. If absent, add the smallest focused test that proves:

- decision pay_partial
- payment_method cash
- payment amount persisted
- customer_payment_cash_details exists
- amount_paid_rupiah persisted
- amount_received_rupiah persisted
- change_rupiah persisted
- payment_component_allocations allocated only partial amount
- payment_allocations legacy missing

Then run only targeted partial cash test and the focused create payment matrix.

## Step 5 - Partial cash create proof

Status: Focused GREEN.

Scope:
- Harden create transaction payment seam for `pay_partial` + `cash`.
- This is payment proof only.
- Package service+sparepart pricing allocation is intentionally not implemented here.

Production files changed:
- None in this step.

Test files changed:
- `tests/Feature/Note/CreateTransactionWorkspacePartialCashFeatureTest.php`

Scenario proven:
- Service-only note total remains full obligation: `150.000`.
- Payment decision: `pay_partial`.
- Payment instrument: `cash`.
- Customer payment amount persisted: `50.000`.
- `customer_payments.payment_method` persisted as cash.
- `customer_payment_cash_details` persisted:
  - `amount_paid_rupiah`: `50.000`
  - `amount_received_rupiah`: `100.000`
  - `change_rupiah`: `50.000`
- `payment_component_allocations` allocated only the partial amount: `50.000`.
- Legacy `payment_allocations` row is not created.

Local proof:
- `php -l tests/Feature/Note/CreateTransactionWorkspacePartialCashFeatureTest.php`
  - PASS: no syntax errors.
- `php artisan test --filter=CreateTransactionWorkspacePartialCashFeatureTest`
  - PASS: 1 test, 8 assertions.
- `php artisan test --filter='CreateTransactionWorkspaceFullCashFeatureTest|CreateTransactionWorkspaceFullTransferFeatureTest|CreateTransactionWorkspaceSkipFeatureTest|CreateTransactionWorkspacePartialTransferFeatureTest|CreateTransactionWorkspacePartialCashFeatureTest'`
  - PASS: 5 tests, 33 assertions.

Current create transaction payment matrix coverage:
- full cash: covered.
- full transfer: covered.
- skip payment: covered.
- partial transfer: covered.
- partial cash: covered.

Remaining gaps:
- No `make verify` proof for this docs/test-only step.
- No browser/manual QA.
- Package service+sparepart single-price allocation remains design-only and out of scope.
- Next technical target is item compiler/composer seam inspection before package pricing implementation.

