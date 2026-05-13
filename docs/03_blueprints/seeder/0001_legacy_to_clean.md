# Legacy to Clean Seeder Blueprint

## Status

Planning blueprint.

This document does not change runtime behavior.

This document does not mark any seeder, error log, or make seed level as fixed.

## Source Of Truth

- docs/01_standards/00-index.md
- docs/01_standards/01-decision-policy.md
- docs/01_standards/core/10-scope-and-facts.md
- docs/01_standards/core/11-blueprint-first.md
- docs/01_standards/core/12-step-by-step-execution.md
- docs/01_standards/core/13-proof-and-progress.md
- docs/02_architecture/adr/0023-seeder-credential-and-environment-safety.md
- docs/03_blueprints/security/adr-0023-seeder-safety.md
- docs/99_archive/handoff/v2/seedernew/2026-04-26-seedernew-finance-blueprint-adr.md
- docs/99_archive/handoff/v2/seedernew/2026-04-26-seedernew-scenario-matrix.md
- docs/99_archive/handoff/v2/seedernew/2026-04-26-seedernew-audit-command-proof.md
- docs/99_archive/handoff/v2/seedernew/2026-04-26-seedernew-make2-idempotency-proof.md
- current local command output at execution time

## Problem Statement

The current seeder system has grown into a mixed legacy surface.

It contains credential seeders, baseline seeders, domain seeders, scenario seeders, load seeders, finance seeders, support helpers, and old compatibility paths.

The risk is not only messy file naming.

The real risks are:

1. legacy seeders being mistaken as final clean behavior
2. production-like credential exposure through predictable privileged accounts
3. non-idempotent reruns corrupting or duplicating finance-sensitive data
4. scenario data not being traceable to a scenario matrix
5. seeders being treated as proof of business correctness
6. make levels becoming unclear for future engineers
7. load or stress seeders leaking into baseline workflows
8. direct database writes bypassing intended usecase/domain paths
9. future cleanup deleting or rewriting legacy seeders without replacement proof

## Decision

All current files under `database/seeders/**/*.php` are classified as legacy compatibility seeders until each file is explicitly mapped to a clean seeder contract.

Legacy compatibility does not mean useless.

Legacy compatibility means:

- allowed to remain temporarily
- not treated as final architecture
- must not be expanded casually
- must be guarded when security-sensitive
- must be replaced or wrapped by clean deterministic seed flows
- must be documented before deletion or rename

Clean seeder work must be built in phases.

No mass rename, mass deletion, or mass header edit is allowed before inventory classification and mapping are proven.

## Current Local Inventory Snapshot

Local command output on 2026-05-11 showed:

- branch: main
- HEAD: d24398de
- origin/main aligned with local HEAD
- total PHP seeder files found under `database/seeders`: 41
- Makefile routes:
  - `make 1` -> `Database\\Seeders\\SeedLevel1Seeder`
  - `make 2` -> `Database\\Seeders\\SeedLevel2Seeder`
  - `make 3` -> `Database\\Seeders\\SeedLevel3Seeder`

## Legacy Boundary

Until classification is complete, these paths are considered legacy compatibility surface:

- database/seeders/DatabaseLoadSeeder.php
- database/seeders/DatabaseSeeder.php
- database/seeders/EmployeeFinanceBaselineSeeder.php
- database/seeders/EmployeeFinanceSeeder.php
- database/seeders/Expense/ExpenseBaselineSeeder.php
- database/seeders/ExpenseSeeder.php
- database/seeders/FinancialCorrectionSeeder.php
- database/seeders/Load/CustomerCorrectionLoadSeeder.php
- database/seeders/Load/CustomerPaymentLoadSeeder.php
- database/seeders/Load/CustomerRefundLoadSeeder.php
- database/seeders/Load/CustomerTransactionLoadSeeder.php
- database/seeders/Load/ExpenseLoadSeeder.php
- database/seeders/Load/ProcurementLoadSeeder.php
- database/seeders/Load/ProductLoadSeeder.php
- database/seeders/ProductInventoryThresholdBackfillSeeder.php
- database/seeders/Product/ProductScenarioActiveBasicSeeder.php
- database/seeders/Product/ProductScenarioEditedSeeder.php
- database/seeders/Product/ProductScenarioLegacyIncompleteSeeder.php
- database/seeders/Product/ProductScenarioRecreatedSeeder.php
- database/seeders/Product/ProductScenarioSoftDeletedSeeder.php
- database/seeders/Product/ProductSeedCatalog.php
- database/seeders/Product/ProductSeedThresholds.php
- database/seeders/ProductSeeder.php
- database/seeders/SeedLevel1Seeder.php
- database/seeders/SeedLevel2Seeder.php
- database/seeders/SeedLevel3Seeder.php
- database/seeders/SupplierInvoiceAnnualDenseSeeder.php
- database/seeders/SupplierInvoiceBaselineSeeder.php
- database/seeders/SupplierInvoiceScenarioSeeder.php
- database/seeders/SupplierInvoiceSeeder.php
- database/seeders/SupplierInvoiceVoidedScenarioSeeder.php
- database/seeders/SupplierPaymentProofSeeder.php
- database/seeders/SupplierSeeder.php
- database/seeders/Support/SeedDensity.php
- database/seeders/Support/SeedWindow.php
- database/seeders/Transaction/CustomerCorrectionBaselineSeeder.php
- database/seeders/Transaction/CustomerPaymentBaselineSeeder.php
- database/seeders/Transaction/CustomerRefundBaselineSeeder.php
- database/seeders/Transaction/CustomerTransactionBaselineSeeder.php
- database/seeders/UserSeeder.php
- database/seeders/WorkshopStressTestSeeder.php

## Clean Seeder Contract

A clean seeder must satisfy these rules:

1. deterministic output
2. rerunnable without unintended count growth
3. stable scenario identifiers
4. traceable scenario matrix entry
5. no random finance data unless explicitly isolated as stress-only
6. no predictable privileged credentials outside local/testing
7. production-like environments fail closed for unsafe credential paths
8. scenario/load seeders are not part of production-safe baseline
9. finance-sensitive data reconciles through audit command or invariant test
10. seeders do not replace domain correctness tests
11. direct DB insert is allowed only when intentionally documented and risk-classified
12. final clean path must have proof before legacy path is deprecated or deleted

## Proposed Clean Seed Levels

### Level 1: Identity Access Local/Test Seed

Purpose:

- minimal local/testing login and access data

Allowed:

- local/testing users
- local/testing admin/cashier credentials
- actor access setup
- local/testing capability setup

Forbidden:

- production-like predictable credential creation
- finance scenario data
- product/supplier/transaction data

### Level 2: Deterministic Domain Baseline Seed

Purpose:

- normal 1 month deterministic dataset for manual review and business analysis

Allowed:

- products
- suppliers
- inventory baseline
- supplier invoice baseline/scenarios
- customer transaction baseline
- payment/refund/correction baseline
- expense baseline
- employee finance baseline if deterministic and audited

Forbidden:

- annual stress data
- uncontrolled random data
- production-like credential fallback
- load-only data

### Level 3: Deterministic Load/Extreme Seed

Purpose:

- 1 year deterministic stress dataset

Allowed:

- annual dense supplier/customer/load scenarios
- high volume report data
- controlled deterministic stress data

Forbidden:

- random behavior that breaks rerun audit
- production-like credential path
- data not covered by audit snapshot

### Production Bootstrap Path

Purpose:

- explicit operator-controlled production-like bootstrap

Allowed only when:

- operator intent is explicit
- credential source is explicit
- password/secret is not hardcoded
- missing credential input fails closed
- behavior is documented in runbook

## Legacy Marker Policy

Initial marker is document-level, not file-level.

Reason:

- avoids noisy mass diff across 41 files
- avoids touching runtime files before classification
- keeps first step auditable
- prevents false progress

File-level legacy markers may be added later only after the manifest classifies each seeder.

Allowed future file-level marker format:

    /**
     * @deprecated Legacy compatibility seeder.
     *
     * Do not extend this path for new clean scenarios.
     * See docs/03_blueprints/seeder/2026-05-11-legacy-to-clean-seeder-blueprint.md.
     */

File-level marker must not be added to generated/support helpers blindly if the helper remains part of clean flow.

## Migration Workflow

### Step 1: Inventory

Produce full file list from local repo.

Proof:

- `find database/seeders -type f -name '*.php' | sort`

### Step 2: Classification Manifest

Create a manifest that maps each seeder to:

- path
- current role
- legacy status
- clean target
- risk level
- make level usage
- credential risk
- idempotency status
- direct DB write risk
- scenario matrix coverage
- replacement plan

Recommended path:

- docs/03_blueprints/seeder/2026-05-11-legacy-seeder-manifest.md

### Step 3: Clean Contract Lock

Lock allowed clean seed levels and entrypoints.

No runtime patch before this is reviewed.

### Step 4: First Safe Marker

Add document-level or file-level markers only after classification manifest exists.

Do not mass-edit all files before manifest review.

### Step 5: Build Clean Path Incrementally

Start with the smallest safe slice.

Recommended order:

1. identity/access safety
2. product/supplier deterministic baseline
3. supplier invoice baseline/scenario
4. customer transaction baseline
5. payment/refund/correction scenario
6. expense baseline
7. employee finance baseline
8. load/annual seeders

### Step 6: Audit And Invariant Proof

Each clean slice must include targeted proof.

Possible proof:

- syntax check
- targeted seeder run
- rerun idempotency check
- audit command output
- invariant test
- relevant feature/unit tests
- final diff review

### Step 7: Deprecate Legacy Path

A legacy seeder may be marked for deletion only when:

- clean replacement exists
- make level wiring no longer depends on it
- tests pass
- audit output passes
- docs mention replacement
- owner accepts the diff

## Classification Categories

Allowed categories:

- ENTRYPOINT
- IDENTITY_ACCESS
- CREDENTIAL
- PRODUCT_BASELINE
- PRODUCT_SCENARIO
- SUPPLIER_BASELINE
- SUPPLIER_INVOICE_BASELINE
- SUPPLIER_INVOICE_SCENARIO
- CUSTOMER_TRANSACTION_BASELINE
- CUSTOMER_PAYMENT_BASELINE
- CUSTOMER_REFUND_BASELINE
- CUSTOMER_CORRECTION_BASELINE
- EXPENSE_BASELINE
- EMPLOYEE_FINANCE_BASELINE
- FINANCIAL_CORRECTION
- LOAD_TEST
- STRESS_TEST
- SUPPORT_HELPER
- BACKFILL
- LEGACY_ORPHAN
- UNKNOWN

## Risk Levels

### LOW

- support helper
- deterministic non-finance fixture
- no credentials
- no direct finance mutation

### MEDIUM

- domain baseline
- scenario seed
- direct inserts with known scope
- idempotency needs proof

### HIGH

- credentials
- privileged users
- finance-sensitive mutation
- payments/refunds/corrections
- supplier payment/proof
- inventory movement/costing
- random/stress data
- destructive purge/rebuild logic

## Stop Conditions

Stop immediately if:

- classification cannot determine make level usage
- a seeder creates privileged users without environment boundary
- a seeder uses predictable credentials outside local/testing
- a clean path would silently remove local developer workflow
- a direct DB write bypasses finance domain logic without documented reason
- make 1/2/3 behavior changes without proof
- scenario data is claimed clean without scenario matrix mapping
- idempotency is claimed without rerun proof
- error_log status is updated before proof
- legacy deletion is attempted before replacement proof

## Immediate Next Step

Create classification manifest from local source.

Do not edit runtime seeders yet.

