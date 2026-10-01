# Verification baseline repair

Base: main `1e38acd7`. Isolated branch: `fix/reporting-verify-baseline`.

FACT: clean main reproduces one PHPStan error in EmployeeDebtTemporalRowMapper:21 and 10 failed / 22 passed tests in nine affected classes.

Root causes are separate: `isset` already excludes null; older report tests do not reflect accepted owner-facing identifiers (#56) or web pagination (#54); the gauntlet creates September business transactions using today's system clock, violating the historical root-existence cutoff. Existing error log 0070 documents the same fixture defect and the need to control Carbon and ClockPort.

DECISION: remove only the redundant comparison. Update report assertions to verify readable identifiers, absence of internal IDs, and exact screen/PDF row parity after extracting paginator items, including the canonical 10-row page size. Include employee_name in exact dataset expectations. Freeze both fixture clocks within September for the gauntlet while retaining every financial assertion. Do not change production temporal, payment, stock, supplier or reporting calculation semantics.

ACTIVE STEP: make the baseline gate pass with this minimal production correction and contract-aligned regression tests.

PROOF: `make verify` fails at the mapper; affected classes reproduce all ten failures. Logs: storage/logs/baseline-reproduce-{verify,focused}.log.

NEXT: focused reporting/gauntlet regressions, full artisan suite, PHPStan, contract audit, diff check and make verify; commit/push/PR linked to a separate issue; merge only after GREEN.

PROGRESS: implementation complete. Focused affected/cutoff/pagination suite: 40 passed (519 assertions). Reporting/Exports + gauntlet + mapper/pagination regressions: 263 passed (2737 assertions). Full `php artisan test --compact`: 1847 passed (14273 assertions), 451.95 seconds. PHPStan and contract audit PASS. Canonical `make verify`: GREEN, exit 0; 1847 passed (14272 assertions), 964.89 seconds. No failures or skipped tests. Supplier feature stays in its independent worktree.

Issue: https://github.com/Asyraf2003/GlassPos/issues/68. Existing cash-ledger label issue #65 is independent and remains outside this baseline repair; event IDs are verified internally and hidden in the rendered table as required by #56.
