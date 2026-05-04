# Handoff — Codex Finding: Refunded revisions undercount paid totals

## Status

Handoff for new session.

No production fix is approved yet for this issue.

## Repo State At Handoff

Known local state from user proof:

- repo path: `/home/asyraf/Code/laravel/bengkel2/app`
- branch: `main`
- HEAD short: `fce1bbfd`
- latest commit: `Prevent XLSX formula injection in report exports`

Known `git status --short` after cleanup:

~~~text
?? docs/adr/2026-05-04-note-revision-carry-forward-settlement.mdAudit doc exists on disk:

docs/audit/codex-security/2026-05-04-refunded-revisions-undercount-paid-totals-audit.md

ADR draft exists on disk:

docs/adr/2026-05-04-note-revision-carry-forward-settlement.md
Completed Before This Handoff
1. Previous Codex issue fixed and committed

The prior issue Supplier payable XLSX formula injection was fixed and committed at current HEAD:

fce1bbfd Prevent XLSX formula injection in report exports

Proof earlier in session:

supplier payable export regression passed
full reporting export suite passed
commit exists as HEAD
2. Codex issue triaged

Finding:

Refunded revisions undercount paid totals

Claim:

When a note with previous payment/refund is revised, active allocation can represent net carry-forward money, while status/outstanding logic may subtract historical refund again. This causes double subtraction and can make a settled note appear unpaid/open.

3. Existing project docs checked

Binding docs:

docs/blueprint/v2/note-finance/2026-04-29-note-finance-stabilization-blueprint.md
docs/blueprint/v2/note-finance/2026-04-29-note-finance-current-projection-addendum.md
docs/handoff/v2/note-finance/2026-04-30-adr-0016-completion-handoff.md

Key constraints:

note revision remains supported
cashier edit remains allowed by prior owner decision
payment/refund/history must not be destroyed
current projection and ledger/history must remain separated
refund engine must not be changed again without fresh proof of a finance ledger bug
4. Owner decision captured

Owner clarified the intended domain rule:

Previous money on the note becomes carry-forward money for the edited note.

Rules:

If revised total is greater than carry-forward money:
note becomes partial payment / bayar sebagian
outstanding is the difference
If revised total equals carry-forward money:
note becomes paid / lunas
outstanding is zero
If revised total is less than carry-forward money:
difference must become explicit kembalian / overpaid / refund due / customer credit
system must not pretend the note is unpaid
Carry-forward money must be allocated by priority:
product components first
service components after product components
Cashier edit remains allowed.
Do not solve this by blocking cashier edit unless owner explicitly chooses temporary containment.
5. ADR draft created

File:

docs/adr/2026-05-04-note-revision-carry-forward-settlement.md

Purpose:

Record the owner decision and prevent accidental reader-level patching without settlement/use-case proof.

6. Audit doc created

File:

docs/audit/codex-security/2026-05-04-refunded-revisions-undercount-paid-totals-audit.md

Purpose:

Record Codex claim, local proofs, assistant mistakes, touched files, options, and stop conditions.

Assistant Mistakes To Carry Forward

The previous assistant made process mistakes:

It moved too quickly from failing tests to a production patch hypothesis.
It suggested a gross-back reader patch before fully reconciling with blueprint/ADR.
It created tests that expected reader-level allocated amount to become 300000, which may be wrong under the owner's carry-forward model.
It incorrectly assumed a component reader test file was untracked and suggested deleting it.
It produced one invalid Python command using !==.

Correction:

Treat the earlier reader gross-back approach as a rejected/unapproved hypothesis.
Do not commit reader-level patch unless owner explicitly approves it after ADR review.
Next work must start from settlement/use-case tests based on carry-forward money.
Current Decision

Owner has not approved final fix implementation yet.

The correct next session posture:

audit first
ask for owner decision when needed
implement one active step at a time
do not silently change settlement semantics
Recommended Next Step For New Session

First active step:

Verify working tree and docs:

git status --short --untracked-files=all
git rev-parse --short HEAD
git branch --show-current

sed -n '1,260p' docs/adr/2026-05-04-note-revision-carry-forward-settlement.md
sed -n '1,260p' docs/audit/codex-security/2026-05-04-refunded-revisions-undercount-paid-totals-audit.md

Then ask owner to approve the ADR wording before writing production tests.

Proposed Test Direction After ADR Approval

Do not begin with generic reader tests.

Begin with settlement/use-case characterization:

Scenario 1 — revised total equals carry-forward money

Given:

previous payment: 300000
previous refund: 100000
carry-forward money: 200000
revised total: 200000

Expected:

paid/lunas
outstanding 0
no double subtraction
Scenario 2 — revised total greater than carry-forward money

Given:

carry-forward money: 200000
revised total: 250000

Expected:

partial payment / bayar sebagian
outstanding 50000
Scenario 3 — revised total less than carry-forward money

Given:

carry-forward money: 200000
revised total: 150000

Expected:

overpaid/change/refund-due/customer-credit signal exists
system does not mark note as unpaid due to double subtraction
Scenario 4 — allocation priority

Given:

carry-forward money exists
current note has product and service components

Expected:

carry-forward fills product components first
service components filled after product components
Open Decisions

Owner still needs to decide:

Final representation for overpaid/carry-forward surplus:
immediate refund due
customer credit
explicit overpaid balance
forced refund workflow
Whether immediate remediation should target:
settlement/status services
revision replay
current projection layer
explicit settlement service
Whether temporary containment is needed before final implementation.
Stop Conditions

Stop if:

a patch changes refund engine without new proof and owner approval
a patch blocks cashier edit without explicit owner decision
a patch rewrites/deletes payment/refund history
a test encodes gross reader semantics without validating carry-forward domain behavior
production fix is attempted before ADR review
