# HANDOFF — ADR-0016 / ADR-0021 Payment-Refund-Revision Stabilization Completion

- Date: 2026-04-30
- Scope: Note / Payment / Refund / Revision / Inventory / Reporting / Dashboard
- Repo: `/home/asyraf/Code/laravel/bengkel2/app`
- Branch: `main`
- Completion Status: Completed
- Latest Verified Remote Commit: `96bc9280 Guard dashboard cash change denomination breakdown`

## Summary

ADR-0016 post-close note correction and refund flexibility is considered completed for the current stabilization slice.

Closed, paid, and refunded notes are no longer treated as terminal mutation locks in the implemented flow. The current implementation supports audited post-close refund/revision behavior, payment allocation hardening, reporting projection correction, and dashboard safety for cash-change denomination edge cases.

This handoff closes the ADR-0016/ADR-0021 stabilization work completed around commits:

- `95f50358 Harden revised note refund pair allocation cap`
- `f5d41ba9 Net refunded external purchase cost in operational profit`
- `96bc9280 Guard dashboard cash change denomination breakdown`

## Final Remote State

Latest known final log:

~~~text
96bc9280 (HEAD -> main, origin/main, origin/HEAD) Guard dashboard cash change denomination breakdown
f5d41ba9 Net refunded external purchase cost in operational profit
95f50358 Harden revised note refund pair allocation cap
7b030a03 commit 1495

Final working tree after local cleanup:

git status --short
# clean
Completed Code Changes
1. Payment / Refund / Revision Engine Stabilization

Commit:

95f50358 Harden revised note refund pair allocation cap

Completed behavior:

Refund/revision/payment pair allocation cap hardened.
Revised note refund pair cap is component-refund-aware.
Cap is bounded by actual customer_payments.amount_rupiah.
Historical refunded components and active revised components are handled without over-allocation.
Refund engine should not be changed again unless new proof shows an actual finance ledger bug.

Important invariant:

pair_cap = min(customer_payment.amount_rupiah, active_component_allocated + component_refunded)
2. Operational Profit External Purchase Netting

Commit:

f5d41ba9 Net refunded external purchase cost in operational profit

Files changed:

app/Adapters/Out/Reporting/Queries/OperationalProfit/ProductCostMetricQuery.php
tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php

Completed behavior:

Operational profit external purchase cost now nets issued external purchase cost against refunded service_external_purchase_part components.
Fully refunded notes no longer keep external purchase cost in operational profit.
Full refund neutrality is covered by regression test.

Final intended behavior:

external_purchase_cost = issued_external_purchase_cost - refunded_external_purchase_component_amount
3. Dashboard Cash-Change Denomination Guard

Commit:

96bc9280 Guard dashboard cash change denomination breakdown

Files changed:

app/Application/Reporting/Services/CashChangeDenominationCalculator.php
app/Application/Reporting/UseCases/GetDashboardOperationalPerformanceDatasetHandler.php
tests/Feature/Reporting/GetDashboardOperationalPerformanceDatasetFeatureTest.php
tests/Unit/Application/Reporting/Services/CashChangeDenominationCalculatorTest.php

Completed behavior:

CashChangeDenominationCalculator::aggregate() remains strict.
CashChangeDenominationCalculator::calculate() remains strict.
Smallest configured denomination remains 500.
Dashboard uses aggregateRepresentable() for denomination breakdown.
Dashboard summary keeps exact total_potential_change_rupiah.
Dashboard no longer crashes when cash change is not exactly representable by configured denominations.

Example proven case:

change_rupiah = 54.200
summary total_potential_change_rupiah = 54.200
denomination breakdown = 54.000
unrepresented remainder = 200

This prevents dashboard failure without weakening calculator strictness.

Final Verification Proof

Before f5d41ba9:

make verify
phpstan: OK
audit-lines: SUCCESS
Blade PHP audit: SUCCESS
contract audit: passed
Pest: 791 passed, 4119 assertions

Before 96bc9280:

make verify
phpstan: OK
audit-lines: SUCCESS
Blade PHP audit: SUCCESS
contract audit: passed
Pest: 793 passed, 4123 assertions

Targeted tests proven during the slice:

tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php
tests/Feature/Reporting/OperationalProfitSummaryHardeningFeatureTest.php
tests/Feature/Reporting/OperationalProfitReportPageFeatureTest.php
tests/Feature/Reporting/GetDashboardOperationalPerformanceDatasetFeatureTest.php
tests/Feature/Admin/AdminDashboardPageFeatureTest.php
tests/Unit/Application/Reporting/Services/CashChangeDenominationCalculatorTest.php
Manual / Local Data Cleanup Proof

Local April report initially still showed non-zero operational profit because of polluted manual/test data from old note:

7cf611fe-766d-4220-8ac9-32e1422bc371

This note had already been marked invalid as final proof in earlier handoff context.

Cleanup 1

Backup:

/tmp/hyperpos-polluted-april-report-cleanup-backup-20260430-194503.json

Cleanup result:

DELETED_PAYMENTS=2
DELETED_REFUNDS=3
DELETED_ORPHAN_STOCK_SOURCE_IDS=3

After cleanup 1:

cash_in = 710800
refund = 710800
cash_minus_refund = 0
issued_store_stock_cogs = 90000
returned_store_stock_cogs = 90000
net_store_stock_cogs = 0

Remaining issue after cleanup 1:

external_purchase_cost_rupiah = 16000
cash_operational_profit_rupiah = -16000
Cleanup 2

Backup:

/tmp/hyperpos-remaining-external-purchase-16000-backup-20260430-204954.json

Deleted polluted external purchase line:

work_item_external_purchase_lines.id = a9598486-b05c-4793-b3e5-bc07f76130f7
note_id = 7cf611fe-766d-4220-8ac9-32e1422bc371
issued = 16000
refunded = 0
net = 16000

Final local April operational profit row:

from_date = 2026-04-01
to_date = 2026-04-30
cash_in_rupiah = 710800
refunded_rupiah = 710800
external_purchase_cost_rupiah = 0
store_stock_cogs_rupiah = 0
product_purchase_cost_rupiah = 0
operational_expense_rupiah = 0
payroll_disbursement_rupiah = 0
employee_debt_cash_out_rupiah = 0
cash_operational_profit_rupiah = 0

Git remained clean after local cleanup.

Valid Manual Proof Note

Final valid manual proof note:

f0a74e9f-1252-4384-8225-bc64dd517f41

Manual flow proven earlier:

create fresh note
partial payment
refund selected product row
edit/revision
settle/lunasi
refund all active remaining rows
final note became refunded
all work items became canceled
note total became 0
report eventually netted to 0 after local polluted data was cleaned

Old polluted note that must not be used as final proof:

7cf611fe-766d-4220-8ac9-32e1422bc371
Locked Decisions
ADR-0016 is considered implemented/completed for this stabilization slice.
Closed, paid, and refunded notes are not terminal mutation locks.
Refund stays in the same note, not a new note.
Refund may apply to paid, partial, or unpaid rows.
Money refund and row neutralization remain separate concepts.
Payment records must not be silently rewritten.
Refunds are financial events.
Inventory reversal remains event/reversal-based.
Negative profit must not be globally hidden.
Do not exclude all refunds or all costs.
Do not patch dashboard text before fixing projection/query behavior.
Calculator remains strict; dashboard handles non-representable denomination breakdown safely.
Admin must not bypass audit.
Kasir must not bypass audit.
Known Non-Issue / Important Warning

If local report becomes non-zero again, inspect data first before changing code.

Known causes from this slice:

old polluted note data
orphan inventory movements
old external purchase lines without matching refund component
local manual/browser test data
stale payments/refunds from pre-patch flows

Do not change the refund engine again unless a fresh, post-96bc9280 reproduction proves an actual engine bug.

Recommended Next Step

Run only manual browser sanity check:

Open Laba Kas Operasional for 2026-04-01 to 2026-04-30.
Confirm:
Uang Masuk = Rp 710.800
Pengembalian Dana = Rp 710.800
Pembelian Eksternal = Rp 0
HPP Stok Toko = Rp 0
Harga Beli Produk = Rp 0
Laba Kas Operasional = Rp 0
Open admin dashboard.
Confirm dashboard no longer crashes from cash-change denomination.
If UI matches, move to the next roadmap item in a new session.

No code patch is recommended unless new proof appears.
MD

python3 <<'PY'
from pathlib import Path

path = Path("docs/adr/0016-post-close-note-correction-and-refund-flexibility.md")
text = path.read_text()

text = text.replace(
"- Status: Accepted\n",
"- Status: Accepted\n- Implementation Status: Completed\n- Completed At: 2026-04-30\n",
1,
)

insert_before = "\n## Related Decisions\n"
completion = """

Completion Proof

ADR-0016 implementation is completed for the current stabilization slice.

Completion handoff:

docs/handoff/v2/note-finance/2026-04-30-adr-0016-completion-handoff.md

Completed commits:

95f50358 Harden revised note refund pair allocation cap
f5d41ba9 Net refunded external purchase cost in operational profit
96bc9280 Guard dashboard cash change denomination breakdown

Final verification proof:

make verify passed before commit 96bc9280
phpstan passed
audit-lines passed
Blade PHP audit passed
contract audit passed
Pest passed with 793 passed, 4123 assertions

Final local April operational profit proof after cleanup:

cash_in_rupiah = 710800
refunded_rupiah = 710800
external_purchase_cost_rupiah = 0
store_stock_cogs_rupiah = 0
product_purchase_cost_rupiah = 0
cash_operational_profit_rupiah = 0

Old polluted note 7cf611fe-766d-4220-8ac9-32e1422bc371 must not be used as final proof.

Valid manual proof note:

f0a74e9f-1252-4384-8225-bc64dd517f41

"""

if insert_before not in text:
raise SystemExit("RELATED_DECISIONS_ANCHOR_NOT_FOUND")

if "## Completion Proof" not in text:
text = text.replace(insert_before, completion + insert_before, 1)

path.write_text(text)
print("Wrote ADR-0016 completion marker and repo handoff")
PY

echo
echo "== DOCS STATUS =="
git status --short

echo
echo "== DIFF STAT =="
git diff --stat

echo
echo "== ADR-0016 STATUS PREVIEW =="
sed -n '1,18p' docs/adr/0016-post-close-note-correction-and-refund-flexibility.md

echo
echo "== HANDOFF PREVIEW =="
sed -n '1,80p' docs/handoff/v2/note-finance/2026-04-30-adr-0016-completion-handoff.md
