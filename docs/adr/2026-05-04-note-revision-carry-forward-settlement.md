# ADR — Note Revision Carry-Forward Settlement

## Status

Draft for owner review.

## Context

Codex reported `Refunded revisions undercount paid totals`.

The reported bug shape:

- A note has previous payment allocation.
- A refund exists before revision.
- The note is revised.
- Revision replay may allocate only the net remaining money to the active/current note.
- Some note-level status/outstanding paths still subtract historical refunds again.
- Result: refund can be subtracted twice, causing a fully settled revised note to appear unpaid/open.

Example:

- previous gross payment: 300000
- previous refund: 100000
- carry-forward money after refund: 200000
- revised active note total: 200000
- expected: paid/settled
- wrong result if refund is subtracted again: 100000 net paid and 100000 outstanding

## Owner Decision

Previous money on a note becomes the initial money or carry-forward settlement for the edited note.

After edit/revision:

1. If revised total is greater than carry-forward money:
   - note is partially paid
   - outstanding is the difference

2. If revised total equals carry-forward money:
   - note is paid/lunas
   - outstanding is zero

3. If revised total is less than carry-forward money:
   - the difference must be represented explicitly as overpaid/change/refund due/customer credit according to the next implementation decision
   - the system must not pretend the note is unpaid

4. Carry-forward money must be allocated intelligently by priority:
   - product components first
   - service components after product components

5. Cashier edit remains allowed according to the earlier product decision.
   - Final fix must not remove cashier edit/revision capability.
   - Temporary containment may only be added with explicit owner decision.

## Binding Existing Rules

This ADR follows:

- `docs/blueprint/v2/note-finance/2026-04-29-note-finance-stabilization-blueprint.md`
- `docs/blueprint/v2/note-finance/2026-04-29-note-finance-current-projection-addendum.md`
- `docs/handoff/v2/note-finance/2026-04-30-adr-0016-completion-handoff.md`

Relevant locked rules:

- payment/refund/inventory history must not be destroyed
- old work items that anchor payment/refund/history must remain auditable
- edit/revision must remain supported
- current operational projection must be separated from ledger/history
- refund engine should not be changed again unless fresh proof shows a real finance ledger bug

## Interpretation

Historical refund events remain valid ledger events.

When a revised note is rebuilt using carry-forward money, the active/current allocation may already represent money after historical refunds.

Therefore settlement/status/outstanding logic must not subtract the same historical refund again from current carry-forward allocation.

The system needs an explicit boundary between:

- ledger gross payment/refund history
- current active settlement after revision
- overpaid/change/refund-due state when carry-forward money exceeds revised total

## Non-Decision

This ADR does not yet choose the final storage model for overpaid/change/refund-due.

Possible future models:

- immediate refund due
- customer credit
- explicit overpaid balance
- forced refund workflow before final close

The final model requires separate owner decision.

## Immediate Fix Direction

Do not finalize a generic reader gross-back patch until consumer semantics are proven.

The next implementation step should target the actual settlement consumer behavior:

- paid status
- operational status
- outstanding resolver
- auto-close behavior
- carry-forward allocation priority

Minimum test cases before production patch:

1. revised total equals carry-forward money:
   - paid/lunas
   - outstanding zero

2. revised total greater than carry-forward money:
   - partial/open
   - outstanding equals difference

3. revised total less than carry-forward money:
   - overpaid/change state is detected
   - note is not marked unpaid because of double-subtracted refund

4. allocation priority:
   - carry-forward money fills product components before service components

## Files touched during audit before this ADR

The assistant temporarily touched these files as draft proof/hypothesis:

- `app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php`
- `tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php`
- `tests/Feature/Payment/DatabasePaymentComponentAllocationReaderAdapterFeatureTest.php`

Those changes were audit experiments and must not be treated as final production fix unless re-approved under this ADR.

## Assistant Audit Correction

During this audit, the assistant made several process mistakes that must be recorded so the next session does not inherit them as valid direction.

Mistakes:

1. The assistant moved too quickly from Codex finding and failing characterization tests toward a reader-level production patch.
2. The assistant treated a gross-basis reader patch as a likely fix before fully reconciling it with the active note finance blueprint, current projection addendum, and ADR-0016/ADR-0021 handoff.
3. The assistant temporarily added or suggested tests that encoded `300000` as the expected note-level allocated amount, which may push the design toward gross-back reader semantics too early.
4. The assistant suggested deleting `tests/Feature/Payment/DatabasePaymentComponentAllocationReaderAdapterFeatureTest.php` under the assumption that it was untracked. Local proof later showed it could appear as deleted in working tree, so it was restored.
5. The assistant produced one invalid Python command using `!==`, which failed before changing the intended test fixture.

Correction:

- Reader-level gross-back changes are not approved as final fix.
- Existing temporary reader patch/test experiments must be treated as audit artifacts only.
- Production fix must wait for owner decision and settlement-level characterization tests.
- The owner is the source of direction, rules, domain decisions, and final judgment.
- The assistant role is limited to audit, option analysis, asking for decisions, and executing the chosen fix.

Current safe direction:

- Keep the ADR as a decision record for carry-forward settlement.
- Do not commit reader-level production changes from the earlier experiment unless explicitly re-approved.
- Next implementation must begin from settlement/use-case-level tests, not generic reader semantics.

## Next Step

After owner approval, create characterization tests at the settlement/use-case level instead of locking the wrong reader-level gross basis.
