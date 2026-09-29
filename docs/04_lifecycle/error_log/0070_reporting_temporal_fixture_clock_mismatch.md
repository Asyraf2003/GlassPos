# Reporting temporal fixture clock mismatch

## Context

Final `make verify` on PR #53 exposed one remaining failure in `TransactionEditRefundPaymentStockReportingHardeningTest::test_store_stock_transaction_keeps_historical_line_price_after_master_product_price_change`.

## Failure

The test creates a transaction during the current test runtime, but assigns business `transaction_date = 2026-05-20` and then queries historical reporting for May 2026. After temporal root-existence hardening, the note is correctly excluded because its system `created_at` is after the May cutoff. The previous expectation of Rp250,000 therefore depended on `transaction_date` implicitly acting as creation evidence.

## Decision

Do not weaken the production root-existence invariant and do not change the expected rupiah amounts. The test fixture must control both Laravel/Carbon time and `ClockPort` while the transaction workspace is created so the modern root and initial revision truly exist on 2026-05-20.

`Carbon::setTestNow()` alone is insufficient because `SystemClockAdapter` uses `DateTimeImmutable('now')`; both clocks must be aligned for the create fixture.

## Required proof

1. targeted failing test GREEN with Rp250,000 expectations unchanged;
2. reporting temporal regression tests remain GREEN;
3. final `make verify` GREEN on the resulting branch HEAD.
