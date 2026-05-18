# Handoff 0031 - Create Transaction Package Allocation Audit Metadata

Status: Fixed, focused verified, `make verify` PASS  
Date: 2026-05-18  
Repo: HyperPOS Laravel  
Root: `/home/asyraf/Code/laravel/bengkel2/app`

## Scope

This handoff closes the explicit package allocation audit metadata slice for create transaction service + store-stock package pricing.

Scope included:

- create transaction only
- service + store-stock only
- `package_auto_split` audit metadata
- existing `audit_logs.context` metadata
- compatibility with update/revision call-sites that reuse create work item persister
- file-size/audit-lines compliance

Scope excluded:

- dedicated package allocation audit table
- external purchase package pricing
- external purchase cost-vs-charge design
- edit/revision package recalculation blueprint
- refund package recalculation blueprint
- mixed payment
- pecahan/cash denomination
- browser/manual QA

## Locked facts from previous slice

From `0030_create_transaction_package_pricing_ui_contract_handoff.md`:

- Backend package pricing for service + store-stock is implemented.
- UI package pricing contract is implemented.
- UI default pricing mode is `manual_split`.
- `package_auto_split` is available as explicit option.
- `package_total_rupiah` is rendered in service + store-stock template.
- `rows.js` restores `pricing_mode` and `package_total_rupiah`.
- `summary.js` uses package total for package mode while preserving store-stock product total.
- Payment seam was not touched.
- External purchase package pricing remains out of scope.

## Starting gap

The create transaction audit event existed:

- event: `transaction_workspace_created`

But the audit context only contained summary-level metadata such as:

- `note_id`
- `customer_name`
- `items_count`
- `total_rupiah`
- `payment_decision`
- `amount_paid_rupiah`

It did not include explicit package allocation metadata.

This meant package auto split persisted correct financial rows, but the audit log did not explain how package total was split into sparepart total and service residual.

## RED proof

Command:

    php -l tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php
    php artisan test tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php

Output:

    No syntax errors detected in tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php

    FAIL  Tests\Feature\Note\CreateTransactionWorkspacePackageAllocationAuditFeatureTest
    ⨯ package auto split create transaction records explicit package allocation audit payload

Failure anchor:

    Failed asserting that null is identical to Array [
      [
        'work_item_id' => ...
        'store_stock_line_id' => ...
        'pricing_mode' => 'package_auto_split',
        'package_total_rupiah' => 150000,
        'sparepart_total_rupiah' => 40000,
        'service_price_rupiah' => 110000,
        'product_id' => 'product-package-audit-1',
        'qty' => 1,
        'product_unit_price_rupiah' => 40000,
      ],
    ].

Conclusion:

- `audit_logs.context.package_allocations` was missing.

## Files changed

Production files:

- `app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceAuditPayloadBuilder.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php`
- `app/Application/Note/Services/CreateTransactionWorkspacePersistResult.php`
- `app/Application/Note/Services/CreateTransactionWorkspacePackageAllocationAuditMapper.php`
- `app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php`

Test files:

- `tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php`

## Implementation summary

### Persist result DTO

Added:

- `CreateTransactionWorkspacePersistResult`

Purpose:

- return `itemsCount`
- return package allocation audit metadata
- avoid stuffing audit-specific data into note/payment seams

### Package allocation audit mapper

Added:

- `CreateTransactionWorkspacePackageAllocationAuditMapper`

Purpose:

- map package auto split result from the persisted `WorkItem`
- capture IDs after `WorkItem` and `StoreStockLine` have been created
- avoid estimating IDs before persistence

Captured metadata:

- `work_item_id`
- `store_stock_line_id`
- `pricing_mode`
- `package_total_rupiah`
- `sparepart_total_rupiah`
- `service_price_rupiah`
- `product_id`
- `qty`
- `product_unit_price_rupiah`

### Create work item persister

Changed:

- `CreateTransactionWorkspaceWorkItemPersister::persist(...)` now returns `CreateTransactionWorkspacePersistResult`.

Safety detail:

- item persistence behavior remains unchanged
- inventory issuing behavior remains unchanged
- package audit mapping happens after work item creation
- package audit metadata is collected only when `pricing_mode=package_auto_split`

### Create transaction handler

Changed:

- receives `CreateTransactionWorkspacePersistResult`
- passes `itemsCount()` and `packageAllocations()` into the create audit payload builder

### Audit payload builder

Changed:

- accepts optional package allocation metadata
- includes `package_allocations` only when non-empty

### Update/revision compatibility patch

Because `CreateTransactionWorkspaceWorkItemPersister` is reused by update/revision paths, `UpdateTransactionWorkspaceWorkItemPersister` was patched to unwrap:

    ->itemsCount()

This preserves its existing `int` return contract for update workspace audit payloads.

## Targeted GREEN proof

Command:

    php -l app/Application/Note/Services/CreateTransactionWorkspacePersistResult.php
    php -l app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php
    php -l app/Application/Note/Services/CreateTransactionWorkspaceAuditPayloadBuilder.php
    php -l app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php
    php -l tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php

    php artisan test tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php

Output:

    No syntax errors detected in app/Application/Note/Services/CreateTransactionWorkspacePersistResult.php
    No syntax errors detected in app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php
    No syntax errors detected in app/Application/Note/Services/CreateTransactionWorkspaceAuditPayloadBuilder.php
    No syntax errors detected in app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php
    No syntax errors detected in tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php

    PASS  Tests\Feature\Note\CreateTransactionWorkspacePackageAllocationAuditFeatureTest
    ✓ package auto split create transaction records explicit package allocation audit payload

    Tests: 1 passed (5 assertions)

## Compatibility proof

Call-site proof after compatibility patch:

    app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php
    31: return $this->createPersister->persist($note, $items, $nextLineNo)->itemsCount();

    app/Application/Note/UseCases/UpdateTransactionWorkspaceHandler.php
    66: $itemsCount = $this->items->persist($note, $payload['items'] ?? [], $note->transactionDate());

    app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php
    43: $this->workItems->persist($root, $trustedItems, $root->transactionDate());

    app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php
    53: $persistedItems = $this->items->persist($note, $payload['items'] ?? []);

Interpretation:

- create handler receives the new result DTO
- update wrapper preserves existing int return contract
- revision path ignores update wrapper return value
- no call-site remains expecting an int directly from create persister

## Focused blast-radius proof

Command:

    php artisan test \
      tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspaceTemplateContractFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspaceFullCashFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspaceFullTransferFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspaceSkipFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspacePartialTransferFeatureTest.php \
      tests/Feature/Note/CreateTransactionWorkspacePartialCashFeatureTest.php \
      tests/Feature/Note/UpdateTransactionWorkspaceFeatureTest.php \
      tests/Feature/Note/CashierClosedNoteWorkspaceReplacementSubmitFeatureTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundDueRaceInvariantTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php

Output:

    Tests: 31 passed (175 assertions)
    Duration: 7.55s

Coverage meaning:

- create transaction package audit path passed
- create transaction UI/backend package path passed
- create transaction payment-adjacent paths passed
- update workspace persister adjacency passed
- closed-note replacement guard adjacency passed
- revision surplus/refund adjacency passed

## File-size split proof

Initial `make verify` failed at audit-lines:

    ERROR: File berikut melebihi limit 100 baris tanpa label bypass:
    - [111 lines] app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php

Split result:

    53 app/Application/Note/Services/CreateTransactionWorkspacePackageAllocationAuditMapper.php
    68 app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php
    50 app/Application/Note/Services/CreateTransactionWorkspacePersistResult.php
    33 app/Application/Note/Services/CreateTransactionWorkspaceAuditPayloadBuilder.php

## Final verification proof

Command:

    git diff --check
    make verify

Output:

    ./vendor/bin/phpstan analyze --memory-limit=-1
    Note: Using configuration file /home/asyraf/Code/laravel/bengkel2/app/phpstan.neon.
    1699/1699 [100%]

    [OK] No errors

    SUCCESS: Semua file memenuhi standar limit baris (atau memiliki label bypass).
    SUCCESS: Tidak ditemukan PHP/directive PHP di Blade resources/views.
    Contract audit passed.
    php -d memory_limit=-1 vendor/bin/pest

    Tests: 1063 passed (5769 assertions)
    Duration: 49.21s

Interpretation:

- PHPStan passed.
- audit-lines passed.
- audit-blade passed.
- contract audit passed.
- full Pest suite passed.
- `git diff --check` produced no output.

## Closed facts

- Create transaction package auto split now records explicit package allocation metadata.
- Existing `audit_logs.context` is used; no schema migration was required.
- Payment seam was not touched.
- External purchase package pricing remains out of scope.
- Update/revision call-site compatibility was preserved.
- File-size audit is clean.
- Full `make verify` is green after this patch.

## Remaining gaps

Still intentionally open:

- no browser/manual QA
- no dedicated package allocation audit table
- no external purchase cost-vs-charge design
- no external purchase package pricing
- no edit/revision/refund package recalculation blueprint
- no pecahan/cash denomination work

## Progress interpretation

Final Goal Progress: 86%

Meaning:

- create transaction maturity for the current create-only foundation slice improved from 84% to 86%
- not global HyperPOS maturity
- not refund/edit/reporting maturity

Main Process Progress: 100%

Meaning:

- explicit package allocation audit metadata for create transaction service + store-stock package pricing is complete and verified

Sub-step Progress: 100%

Meaning:

- RED, targeted GREEN, compatibility proof, focused blast-radius, file-size compliance, and full `make verify` all passed

## Suggested next targets

Pick one explicitly. Do not start all.

Recommended order:

1. browser/manual QA checklist for create transaction package pricing
2. external purchase cost-vs-charge design note only
3. future edit/revision/refund package recalculation blueprint
4. dedicated package allocation audit table/read path only if a concrete read/report path is selected

Do not implement external purchase package pricing until cost-vs-charge is designed.

Do not touch edit/revision/refund package behavior without a blueprint first.

## Opening prompt for next session

Kita sedang di repo HyperPOS Laravel:

/home/asyraf/Code/laravel/bengkel2/app

Baca handoff terbaru dulu:

docs/99_archive/handoff/v2/edit_refund_sniper/0031_create_transaction_package_allocation_audit_metadata_handoff.md

Mode kerja wajib:

- Blueprint/proof-first.
- One active target per step.
- Jangan broad audit repo.
- Jangan mulai dari git status/log/push/remote sync.
- Owner handles commit/push/manual sync.
- Jangan klaim fixed/full green tanpa local proof.
- Local command output owner adalah source of truth tertinggi untuk test, make verify, runtime, dirty/local-only files, migration proof, dan working tree state.
- Source read-only inspection wajib pakai GitHub/repo connector dulu jika file sudah committed/pushed.
- Jangan minta saya paste sed panjang untuk source file committed yang bisa dibaca dari repo.
- GitHub remote/source read tidak boleh dipakai untuk mengklaim local test/make verify proof.
- Gunakan rg/fd/sed untuk command lokal; jangan grep -R/find.
- Jangan implement external purchase package pricing.
- Jangan lompat ke edit/revision/refund/mixed payment/pecahan.
- Jangan reopen payment seam kecuali ada regression proof.
- Jangan broad audit dari handoff lama kecuali ada konflik/proof baru.

Locked closed facts:

- Backend package pricing for service + store-stock is implemented.
- UI package pricing contract is implemented.
- UI default pricing mode is manual split.
- `package_auto_split` is available as explicit option.
- `package_total_rupiah` is rendered in service + store-stock template.
- `rows.js` restores `pricing_mode` and `package_total_rupiah`.
- `summary.js` uses package total for package mode while preserving store-stock product total.
- Payment seam was not touched.
- External purchase package pricing remains out of scope.
- Create transaction package auto split now records explicit package allocation metadata in `audit_logs.context.package_allocations`.
- Update/revision call-site compatibility was preserved.
- Full `make verify` passed after the package allocation audit metadata patch.

Latest owner-provided proof:

- RED package audit: failed because `package_allocations` was null.
- Targeted GREEN package audit: PASS, 1 test / 5 assertions.
- Focused create transaction + persister adjacency: PASS, 31 tests / 175 assertions.
- Full `make verify`: PASS.
- Final Pest summary: 1063 tests / 5769 assertions, duration 49.21s.
- PHPStan/audit-lines/audit-blade/contract audit all passed.

Files changed in the closed slice:

- app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php
- app/Application/Note/Services/CreateTransactionWorkspaceAuditPayloadBuilder.php
- app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php
- app/Application/Note/Services/CreateTransactionWorkspacePersistResult.php
- app/Application/Note/Services/CreateTransactionWorkspacePackageAllocationAuditMapper.php
- app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php
- tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php

Current remaining gaps:

- no browser/manual QA
- no dedicated package allocation audit table
- no external purchase cost-vs-charge design
- no external purchase package pricing
- no edit/revision/refund package recalculation blueprint
- no pecahan/cash denomination work

Current progress meaning:

- Final Goal Progress 86% = create transaction maturity for current create-only foundation slice, not global HyperPOS.
- Main Process Progress 100% = explicit package allocation audit metadata completed.
- Sub-step Progress 100% = RED/GREEN/focused/full verify completed.

Next target must be selected explicitly.

Recommended next target:

1. browser/manual QA checklist for create transaction package pricing

Alternative targets:

2. external purchase cost-vs-charge design note only
3. future edit/revision/refund package recalculation blueprint
4. dedicated package allocation audit table/read path only if a concrete read/report path is selected

Before implementing anything, inspect only the selected target’s current source/docs. Do not broad audit the repo.
