# Create Transaction Full Surface Audit Matrix

Status: Source map, test matrix, and targeted automated verification complete. No runtime patch yet.
Date: 2026-06-25
Scope: Create transaction workspace only. Edit, refund, and reports are downstream scopes.

## FACT

- Route create page: `GET /cashier/notes/workspace/create` -> `CreateTransactionWorkspacePageController`, route name `cashier.notes.workspace.create`.
- Route store: `POST /notes/workspace/store` -> `StoreTransactionWorkspaceController`, route name `notes.workspace.store`.
- Blade entry point: `resources/views/cashier/notes/workspace/create.blade.php`.
- Create form includes note info, line item details, note description, review/payment card, payment modal, and refund modal partial. Refund modal is not active in create mode.
- Create form submits `idempotency_key` only in create mode.
- JS entry order: `rows.js`, `search.js`, `summary.js`, `service-catalog.js`, `package-search.js`, `payment-flow.js`, `draft.js`, `boot.js`.
- Request boundary: `StoreTransactionWorkspaceRequest` normalizes input, applies `StoreTransactionWorkspaceRules`, then runs `StoreTransactionWorkspaceValidator`.
- Use case boundary: `CreateTransactionWorkspaceHandler` wraps note create, item persistence, total update, initial revision bootstrap, inline payment, audit log, projection sync, and idempotency success in one transaction.

## Current UI Contract

| UI line type | Payload contract | Backend work item | Money contract | Stock contract |
| --- | --- | --- | --- | --- |
| Produk | `entry_mode=product`, `part_source=none`, one `product_lines[0]` | `store_stock_sale_only` | subtotal = qty * unit price | issues one stock movement |
| Servis | `entry_mode=service`, `part_source=none`, `service.name`, `service.price_rupiah` | `service_only` | subtotal = service price | no stock movement |
| Servis + Sparepart Toko | `entry_mode=service`, `part_source=store_stock`, `pricing_mode=package_auto_split`, `requires_service_product_template=1`, up to 3 product lines | `service_with_store_stock_part` | subtotal = service price + product line totals | issues one stock movement per store-stock line |
| Servis + Pembelian Luar | `entry_mode=service`, `part_source=external_purchase`, one external line `label` + `total_rupiah` | `service_with_external_purchase` | subtotal = service price + external total | no stock movement |

## Payment UI Contract

- The review card opens the payment modal with three modes: full payment, partial payment, and save without payment.
- For create mode, payment UI computes grand total from visible rows before submit.
- Partial payment is invalid when payable amount is zero.
- Transfer submit sets `inline_payment.payment_method=transfer`.
- Cash flow requires `inline_payment.amount_received_rupiah` to be at least the payable amount.
- Skip payment clears method, paid amount, and cash received fields.

## Backend Guard Matrix

| Risk | Guard found |
| --- | --- |
| Empty transaction | `items` is required array with `min:1`. |
| Invalid product row | product id, qty, and unit price are validated server-side. |
| Invalid service row | service name and positive service price are validated, except package auto split composition cases. |
| Store-stock package over 3 products | request validator and composer reject more than 3 product lines. |
| Duplicate product in package | existing feature test proves rejection without financial rows. |
| Template-locked package without active template | existing feature tests prove rejection without note/work item/inventory rows. |
| External purchase package auto split | explicitly rejected until an explicit product contract exists. |
| Partial payment equal/full amount | validator rejects `pay_partial` when amount is greater than or equal to grand total. |
| Cash received less than payable | request validator and payment context resolver both reject it. |
| Duplicate browser submit | idempotency key is required and duplicate same-payload submit replays without duplicate note. |
| Partial write failure | rollback test covers note, item, payment allocation, projection, and audit rollback. |

## Existing Automated Test Coverage

### DB and Domain

- `CreateTransactionWorkspaceLineTypeCharacterizationTest`
  - service-only subtotal and no stock movement.
  - service external subtotal and no stock movement.
  - product subtotal and stock movement.
  - service store-stock package single product.
  - backend direct post multi-product package.
  - template preset multi-product package.
  - active template requirement.
  - external purchase package path rejected.
- `CreateTransactionWorkspaceServiceStoreStockFeatureTest`
  - normal store-stock service payload.
  - package auto split.
  - two different products.
  - duplicate product id rejection.
  - package total equal to sparepart minimum.
  - package below minimum rejection.
  - browser-form string normalization with partial payment.
  - default service price floor.
  - template lock and template product-line matching.
- `CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest`
  - external purchase payload persistence.
  - external purchase package auto split rejection.
- `CreateTransactionWorkspaceRollbackFeatureTest`
  - rollback when inline payment audit fails after write-side work started.

### Payment and Finance

- `CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest`
  - full cash closes note, creates cash detail, allocation, projection, and audit.
  - partial cash keeps note open and records outstanding projection.
  - skip payment creates open debt note without payment rows.
  - full transfer closes note without cash detail.
  - partial transfer keeps note open without cash detail.
- `CreateTransactionWorkspaceFullCashFeatureTest`
- `CreateTransactionWorkspacePartialCashFeatureTest`
- `CreateTransactionWorkspaceFullTransferFeatureTest`
- `CreateTransactionWorkspacePartialTransferFeatureTest`
- `CreateTransactionWorkspaceSkipFeatureTest`
- `CreateTransactionWorkspaceInlinePaymentAmountResolverFeatureTest`
- `CreateTransactionWorkspaceInlinePaymentRecorderFeatureTest`
- `StoreTransactionWorkspacePaymentValidatorTest`

### UI/Blade/JS Contract

- `CreateTransactionWorkspaceTemplateContractFeatureTest`
  - explicit service part-source values.
  - service-store-stock package pricing contract.
  - service catalog contract.
  - package lookup endpoint contract.
- `CreateTransactionWorkspaceDuplicateSubmitFeatureTest`
  - hidden idempotency key rendered.
  - missing key rejected without financial rows.
  - duplicate same payload does not create duplicate note.
  - duplicate different payload rejected.
  - failed attempt after inline payment writes can retry same key without duplicate rows.
- `CashierWorkspaceServiceProductTemplateMinimumContractFeatureTest`
  - JS package selection and invalid package payload guard markers.
- `CashierWorkspaceServiceProductTemplateAutofillContractFeatureTest`
  - product search requests service-product context only for service-store-stock rows.
  - service catalog JS applies template and respects manual overrides.
- `CreateTransactionWorkspaceDefaultCustomerNameFeatureTest`
  - create page does not expose global note count as default customer name.

### Report Impact

- `PackageAutoSplitCreateReportImpactFeatureTest`
  - store-stock package auto split remains valid for report impact.
  - external package path stays blocked.

## GAP

- This audit has not yet run a real browser interaction. Blade and static JS contracts are covered, but actual click/type/select/modal behavior still needs browser/manual proof.
- No runtime bug is proven in create path from the source map alone.
- The owner-facing UI intentionally does not offer external purchase package auto split. Backend rejects that package path by test. If the product requirement changes, this is a new blueprint, not a hidden create bug.
- Create flow still needs a manual scenario checklist executed against a seeded/local DB before claiming "UI fully correct".

## DECISION

- Do not patch runtime create code until a concrete create-path defect is reproduced.
- Treat create transaction as automated-green but browser-unverified.
- Continue with one active create step next: run browser/manual create scenarios against local DB and compare UI, DB, stock, payment, and projection results.

## ACTIVE STEP

Create Transaction Full Surface Audit - Source Map + Test Matrix.

## PROOF

- Targeted create verification:
  - Command: `php artisan test tests/Feature/Note/CreateTransactionWorkspaceLineTypeCharacterizationTest.php tests/Feature/Note/CreateTransactionWorkspaceServiceStoreStockFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceDuplicateSubmitFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceRollbackFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceTemplateContractFeatureTest.php tests/Feature/Note/CashierWorkspaceServiceProductTemplateMinimumContractFeatureTest.php tests/Feature/Note/CashierWorkspaceServiceProductTemplateAutofillContractFeatureTest.php tests/Feature/Reporting/PackageAutoSplitCreateReportImpactFeatureTest.php tests/Unit/Adapters/In/Http/Requests/Note/StoreTransactionWorkspacePaymentValidatorTest.php tests/Unit/Application/Note/Services/CreateTransactionWorkspaceServiceStoreStockPackageProductLinesComposerTest.php`
  - Result: PASS, 47 passed, 478 assertions, duration 9.18s.
- Source files read in this session:
  - `routes/web/note.php`
  - `resources/views/cashier/notes/workspace/create.blade.php`
  - `resources/views/cashier/notes/workspace/partials/rincian-card.blade.php`
  - `resources/views/cashier/notes/workspace/partials/review-payment-card.blade.php`
  - `resources/views/cashier/notes/workspace/partials/templates/product.blade.php`
  - `resources/views/cashier/notes/workspace/partials/templates/service.blade.php`
  - `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php`
  - `resources/views/cashier/notes/workspace/partials/templates/service-external.blade.php`
  - `public/assets/static/js/pages/cashier-note-workspace/rows.js`
  - `public/assets/static/js/pages/cashier-note-workspace/summary.js`
  - `public/assets/static/js/pages/cashier-note-workspace/payment-flow.js`
  - `public/assets/static/js/pages/cashier-note-workspace/package-search.js`
  - `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRules.php`
  - `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceValidator.php`
  - `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceServiceItemValidator.php`
  - `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceProductItemValidator.php`
  - `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspacePaymentValidator.php`
  - `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceGrandTotalItemCalculator.php`
  - `app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php`
  - `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapper.php`
  - `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPayloadMapperValidation.php`
  - `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php`
  - `app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php`
  - `app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentContextResolver.php`
- Test inventory command:
  - `rg -n "public function test_" tests/Feature/Note/CreateTransactionWorkspace*.php tests/Feature/Note/CashierWorkspaceServiceProductTemplate*.php tests/Feature/Reporting/PackageAutoSplitCreateReportImpactFeatureTest.php tests/Unit/Application/Note/Services/CreateTransactionWorkspace*.php tests/Unit/Adapters/In/Http/Requests/Note/StoreTransactionWorkspacePaymentValidatorTest.php`

## NEXT

Execute browser/manual create scenarios:

1. create service only: skip, full cash, partial cash, full transfer, partial transfer.
2. create product only: stock valid, stock over limit, cash/transfer.
3. create service + store-stock package: one product, two/three products, duplicate product attempt, invalid/non-template package attempt.
4. create service + external purchase: valid total, missing label, missing total.
5. delete all lines before submit: UI should show empty state and backend should reject `items` with no financial rows.
6. duplicate submit/reload/back-submit: no duplicate note/payment/stock rows.

## SESSION CONTINUITY LOG

### 2026-06-25 21:31 - Active Step Opened

- Active step: Create Transaction Browser/Manual QA Preparation.
- User rule added: every execution, check, fix, or proof must update docs/error log/workflow so future sessions know the last position.
- Planned first check: inspect local browser/manual test capability and app runtime commands before starting UI proof.
- No runtime patch planned unless a concrete create-path defect is reproduced.

### 2026-06-25 21:33 - Browser Tooling Discovery Check

- Commands executed:
  - `fd -a 'package.json|playwright.config|vite.config|phpunit.xml|Makefile|artisan' .`
  - `rg -n "playwright|browser|dusk|npm run|vite|serve|php artisan serve|APP_URL|DB_CONNECTION|sqlite|cashier.notes.workspace.create" package.json composer.json Makefile phpunit.xml .env .env.testing docs tests`
- Observed result:
  - Found `package.json`, `vite.config.js`, `phpunit.xml`, `Makefile`, and `artisan`.
  - No obvious `playwright.config` or Dusk config found in the initial file discovery.
  - `composer.json` contains dev command pieces for `php artisan serve` and `npm run dev`.
  - `.env` uses `APP_URL=http://127.0.0.1:8000` and MySQL; `.env.testing` uses `APP_URL=http://localhost:8000` and MySQL.
- Current conclusion: browser/manual QA likely needs local Laravel server plus manual browser, unless a hidden browser runner is found in the next focused command check.

### 2026-06-25 21:36 - Runtime Command Check

- Commands executed:
  - `sed -n '1,140p' package.json`
  - `sed -n '1,120p' composer.json`
  - `sed -n '1,220p' Makefile`
  - `fd -a 'dusk|playwright|cypress|selenium|browser' .`
- Observed result:
  - `package.json` only defines `dev` (`vite`) and `build` (`vite build`).
  - `composer.json` defines `dev` with `php artisan serve`, queue listener, pail, and `npm run dev`.
  - No Laravel Dusk, Playwright, Cypress, or Selenium runtime dependency found.
  - Existing browser-related docs found:
    - `docs/04_lifecycle/handoff/0012_service_store_stock_package_autosplit_browser_contract_handoff.md`
    - `docs/99_archive/handoff/v2/edit_refund_sniper/0032_create_transaction_package_pricing_browser_manual_qa_handoff.md`
- Current conclusion: real browser QA cannot be automated from current repo dependencies without adding a browser runner. Next check reads prior manual browser handoffs and then starts/validates local runtime if needed.

### 2026-06-25 21:39 - Prior Browser Handoff Read

- Commands executed:
  - `sed -n '1,260p' docs/04_lifecycle/handoff/0012_service_store_stock_package_autosplit_browser_contract_handoff.md`
  - `sed -n '1,300p' docs/99_archive/handoff/v2/edit_refund_sniper/0032_create_transaction_package_pricing_browser_manual_qa_handoff.md`
- Observed result:
  - Handoff 0012 remains useful as historical proof for the older 1-product service-store-stock browser bug: browser-form string normalization, payment grand-total autosplit, UI guard, and Blade scope were fixed and owner manually confirmed success.
  - Archived handoff 0032 is not current for the owner-facing UI because it expects visible manual split/package-total controls. Current create UI uses package lookup with `pricing_mode=package_auto_split`, `requires_service_product_template=1`, and no visible `package_total_rupiah` input.
- Current conclusion:
  - Do not reuse the archived 0032 checklist verbatim.
  - Browser/manual create QA must use the current template-locked package lookup UI, not the old manual split UI.

### 2026-06-25 21:42 - Static Browser-Path Syntax Check

- Commands executed:
  - `node --check public/assets/static/js/pages/cashier-note-workspace/rows.js`
  - `node --check public/assets/static/js/pages/cashier-note-workspace/summary.js`
  - `node --check public/assets/static/js/pages/cashier-note-workspace/payment-flow.js`
  - `node --check public/assets/static/js/pages/cashier-note-workspace/package-search.js`
  - `php -l resources/views/cashier/notes/workspace/create.blade.php`
  - `php -l resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php`
- Observed result:
  - All four `node --check` commands exited 0.
  - Both Blade PHP lint commands returned `No syntax errors detected`.
- Current conclusion:
  - Create workspace JS/Blade syntax is GREEN for the files needed by current browser/manual QA.
  - This still does not prove real click/type/modal/lookup behavior.

### 2026-06-25 21:44 - Local Server Start Attempt 1

- Command executed:
  - `php artisan serve --host=127.0.0.1 --port=8000`
- Observed result:
  - Failed with: `Failed to listen on 127.0.0.1:8000`.
- Current conclusion:
  - Port 8000 is unavailable in this session.
  - Next attempt uses port 8001.

## PROGRESS

Create path progress: 35%.

Reason: source map, automated coverage matrix, and targeted create test subset are complete, but real browser/manual proof is still absent.
