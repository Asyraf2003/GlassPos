# Supplier Payable Current Position

Date: 2026-09-27
Issue: https://github.com/Asyraf2003/GlassPos/issues/30
Owner contract: approved phase-0 inspection and implementation gate.

## Problem and source facts

The report previously defaulted to monthly and required shipment-date bounds through application handlers and the source port. Both source rows and reconciliation filtered invoices before calculating their balances. The dashboard reused that cohort as supplier outstanding. Push notifications linked to the report. The HTML controller supplied paginated rows, but the view did not render them.

## Locked semantics

- Report: all non-void invoices by default, including settled invoices. Explicit daily, weekly, monthly or custom modes filter the shipment-date cohort.
- Current outstanding: sum positive balances across all non-void invoices using all active, non-reversed payments. No shipment-date filter and no dependence on dashboard month.
- Operational outstanding: existing procurement invoice list with payment_status=outstanding, sort_by=due_date, sort_dir=asc and no shipment-date bounds.
- Due date changes urgency only. Reminder selection remains non-void, positive outstanding and due date <= today + 5 days.
- Example: August invoice 10000000 minus active payments 2000000 remains 8000000 in September; August due date means overdue. September cohort may exclude it; current balance must retain it.

## Read contracts and implementation

- Nullable paired shipment bounds represent all periods as null/null. One missing bound is rejected. No sentinel dates.
- Source and reconciliation apply date predicates only for explicit bounds.
- Shared SupplierPayableReportingQueryFactory invoice/payment query supplies report rows and the dedicated CurrentSupplierOutstandingReaderPort adapter.
- Dashboard handler fills current balance outside the existing month cache, so payments and reversals cannot leave different supplier balances in different month cache entries.
- HTML renders eight invoice columns using existing pagination; shared period partial exposes all only when the supplier report opts in.
- PDF/Excel use the same query/dataset. All has a dedicated label and filename; existing explicit-range guards remain.
- Push factory uses existing RouteUrlGeneratorPort with the canonical named operational route.

## Constraints and risks

No finance mutation, customer receivable/lifecycle changes, schema migration, dependencies, global UI redesign or production cron setup.

All-period report/export materializes the complete dataset, matching the existing reporting architecture; large production datasets have not been load-tested. Current dashboard balance uses one aggregate query per request outside the period cache.

Repeated daily command invocation may resend; no persistence dedupe is introduced. Default limits remain 100 invoices / 500 subscriptions. Tests verify truncation without warning and no duplicate subscription delivery within one invocation.

## Workflow and acceptance

Issue and branch from clean main; implement read-side contracts; focused tests; adjacent procurement/reporting/export/push/admin regression; browser proof in isolated local database; PHPStan, line/Blade audits and diff check; self-review and PR. Merge requires actual green proof.

Test coverage includes cross-month debt, overdue, active settlement, void, payment reversal and projection refresh, optional period boundaries, pagination, HTML/PDF/XLSX consistency, dashboard cache/month switching, payload-to-list endpoint, no-session command execution, exit codes, no finance writes, repeated invocation and limits.

Evidence and remaining merge gate: docs/04_lifecycle/handoff/0037_supplier_payable_current_position.md.
