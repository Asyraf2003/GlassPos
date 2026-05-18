# HyperPOS Database Migration Contract

Status: Active migration contract reference  
Scope: MySQL schema hardening toward PostgreSQL-aligned structure  
Runtime DB now: MySQL  
Future target: PostgreSQL-ready schema discipline, not immediate PostgreSQL cutover

## Goal

The goal of this migration folder is to keep the current MySQL schema structured so that a future PostgreSQL transition is significantly easier.

This does not mean PostgreSQL is active now.

This means new and revised migrations must avoid MySQL-only domain assumptions and must keep finance, stock, audit, projection, and reporting tables portable, explicit, and traceable.

## Before

The migration history currently contains a mixed MySQL-era structure:

- domain tables mostly use string primary keys
- employee/payroll/debt tables use UUID primary keys
- framework/system tables may still use numeric IDs
- money is mostly stored as integer or big integer rupiah
- some fields still rely on MySQL unsigned semantics
- some migrations use MySQL-oriented helpers such as after/change
- JSON storage is mixed between native json columns and text payload columns
- source tables and projection tables exist, but projection materialization policy is not fully uniform
- some legacy seeders still direct-write source/projection/audit tables

## After Target

The target structure is:

- domain identity is explicit and stable
- domain IDs use string or UUID based on table contract
- framework/system numeric IDs stay internal only
- money is stored as integer rupiah, never float or decimal
- status columns use string values, never MySQL enum
- business dates, action timestamps, created_at, and updated_at are not mixed
- JSON is allowed only for snapshot, metadata, draft, or compatibility payload
- JSON must not be the only source of truth for money, status, source ID, actor ID, business date, inventory quantity, payment/refund amount, or report-critical facts
- financial history must not cascade-delete casually
- projection/read-model tables are not canonical financial truth
- indexes must follow real read paths, not guesswork
- PostgreSQL-incompatible assumptions must be documented before new schema work

## ID Policy

Allowed:

- string primary keys for domain tables
- UUID primary keys for domains already modeled as UUID
- numeric IDs for Laravel/framework/system-owned tables only

Forbidden:

- exposing auto-increment IDs as domain/public identity
- adding new mixed identity styles without table-level reason
- relying on numeric framework IDs for finance domain references unless explicitly internal

## Money Policy

Allowed:

- integer
- bigInteger
- explicit signed integer when negative value is valid domain behavior

Forbidden:

- float
- decimal as official rupiah truth
- formatted strings such as 15.000
- relying only on MySQL unsigned as a financial invariant

All rupiah values must reconcile exactly. A one-rupiah mismatch is a defect.

## Date and Time Policy

Do not mix date meanings.

Business/report dates:

- transaction_date
- paid_at
- refunded_at
- tanggal_pengiriman
- tanggal_terima
- tanggal_mutasi
- effective_date where needed

Action/audit timestamps:

- occurred_at
- closed_at
- reopened_at
- uploaded_at
- performed_at where needed

System timestamps:

- created_at
- updated_at

Rules:

- created_at is not transaction_date
- transaction_date is not audit occurred_at
- paid_at/refunded_at are financial event dates
- projection timestamps should use projected_at or rebuilt_at when semantics are selected

## Source Table vs Projection Table

Source-of-truth tables must be seeded and migrated before projections.

Source examples:

- notes
- work_items
- customer_payments
- customer_refunds
- payment_allocations
- payment_component_allocations
- refund_component_allocations
- supplier_invoices
- supplier_invoice_lines
- supplier_receipts
- supplier_payments
- inventory_movements
- audit_events
- note_mutation_events
- version tables

Projection/read-model examples:

- product_inventory
- product_inventory_costing
- note_history_projection
- supplier_invoice_list_projection
- supplier_list_projection

Projection tables must be rebuildable from source or explicitly documented as current-state tables.

## PostgreSQL Readiness Watchlist

Every migration review must check:

- unsignedInteger
- unsignedBigInteger
- after()
- change()
- mediumText
- longText used as JSON payload
- dateTime vs timestamp semantics
- MySQL enum
- cascadeOnDelete on financial history
- raw JSON financial truth
- money stored outside integer/bigInteger
- source/projection ambiguity

## Change Rule

Do not mass-edit old migrations without a clear baseline decision.

Preferred order:

1. document current migration contract
2. audit migration risk by group
3. patch one table group at a time
4. add new compatibility migrations when preserving deployed history matters
5. edit old migrations only when the branch is intentionally treated as rebuildable schema history
6. update seeders only after DB contract is clear

## Current Next Target

First technical target after this README:

- classify unsigned fields into:
  - harmless counter
  - non-negative financial/counter invariant needing validation
  - PostgreSQL check constraint candidate
  - migration rewrite candidate

Do not start seeder rewrite before this classification is done.
