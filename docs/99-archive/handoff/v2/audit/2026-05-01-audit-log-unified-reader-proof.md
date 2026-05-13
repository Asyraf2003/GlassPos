
Audit Log Unified Reader Proof Handoff

Date: 2026-05-01
Branch: main
Final HEAD: 8ef36503 Prove unified audit log source page
Previous implementation HEAD: 8ee7de9c Read audit events on admin audit log page
ADR baseline: ADR-0017 and ADR-0008

Final Goal

Make the admin Audit Log page a reliable read and investigation center for sensitive mutation reasons across both legacy and v2 audit storage.

The goal is not to build archive, export, import, or purge.

Current Scope

Completed scope:

Read legacy audit_logs on admin Audit Log page.
Read v2 audit_events on admin Audit Log page.
Show reason from both sources.
Show source, actor, entity, event, time, and context.
Preserve ADR-0017 restriction against export, import, purge, and post-mutation reason input from Audit Log page.
Locked Decisions

ADR-0017 is active and accepted.

Locked behavior:

Audit log remains append-only.
Active audit log must not be deleted during the initial measurement period.
Export, import, and purge are backlog until one-month load proof exists.
Reason must be entered in the source feature during mutation.
Audit Log page must not become a place to add reasons after mutation.
Audit Log page is only for reading, filtering, and investigation.
Import JSON must not write directly into active audit tables.
Purge must never be a free manual delete button.

ADR-0008 remains active:

Sensitive mutations must be audit-first.
Required audit data includes actor, action, entity, timestamp, reason when required, before or after state, and context metadata.
Sensitive mutation without required audit is invalid.
Completed Commits
a12c20a3 Add audit log retention evaluation ADR
8ee7de9c Read audit events on admin audit log page
8ef36503 Prove unified audit log source page

Final local and remote HEAD after push:

8ef36503

Files Changed

Implementation files:

app/Adapters/Out/Audit/AuditJsonPayload.php
app/Adapters/Out/Audit/AuditLegacyEntityResolver.php
app/Adapters/Out/Audit/AuditLogAdminEntrySorter.php
app/Adapters/Out/Audit/AuditLogAdminListQuery.php
app/Adapters/Out/Audit/AuditLogAdminQueryFilters.php
app/Adapters/Out/Audit/AuditLogAdminRowMapper.php
app/Adapters/Out/Audit/AuditReasonResolver.php
app/Adapters/Out/Audit/DatabaseAuditLogReaderAdapter.php
app/Ports/Out/AuditLogReaderPort.php
resources/views/admin/audit_logs/index.blade.php
resources/views/admin/audit_logs/partials/row.blade.php

Test files:

tests/Feature/AuditLog/AdminAuditEventPageFeatureTest.php
tests/Feature/AuditLog/AdminAuditLogUnifiedSourcePageFeatureTest.php
Behavior Now Proven

Admin Audit Log page can read:

legacy audit_logs
v2 audit_events

Admin Audit Log page can show:

source
event
actor
entity
bounded context
reason
context JSON
created or occurred time

Reason source behavior:

legacy audit_logs reason is resolved from context keys:
reason
alasan
void_reason
correction_reason
note
notes
v2 audit_events reason is resolved from:
audit_events.reason

Entity behavior:

legacy audit_logs entity id is resolved from known context keys such as note_id, supplier_invoice_id, product_id, employee_id, debt_id, payroll_id, and related ids.
v2 audit_events entity is resolved from aggregate_type and aggregate_id.

Search behavior proven:

legacy event and context search still works.
v2 event reason search works.
v2 entity id search works.
Explicitly Not Built

These were intentionally not built because ADR-0017 forbids them as active scope before one-month load proof:

audit log export
audit log import
audit log purge
archive lifecycle
delete audit button
add reason from Audit Log page
edit audit log record from Audit Log page
Verification Proof

Before implementation commit:

Audit-related verification passed:

32 tests passed
262 assertions

After final proof test:

Audit page tests passed:

8 tests passed
39 assertions

Audit regression subset passed:

19 tests passed
150 assertions

Final proof step total:

27 tests passed
189 assertions

Other proof:

git diff --check clean
local HEAD and origin main matched at 8ef36503
final working tree clean after push
Commands Already Proven

Final push proof showed:

main pushed from 8ee7de9c to 8ef36503
local HEAD was 8ef36503
origin main was 8ef36503
final status was clean
Remaining Work Outside This Scope

Next safe scope is audit completeness matrix.

That means mapping all sensitive mutations and proving whether each one records:

reason
actor
entity reference
before state or after state or enough context
audit source table
test proof

Potential sources to inspect:

audit_logs
audit_events
audit_event_snapshots
note_mutation_events
note_revisions
product_versions
supplier_invoice_versions
employee_versions
customer_refunds
employee debt reversal and adjustment tables
payroll reversal tables
supplier payment reversal tables
supplier receipt reversal tables
operational expense mutation flows

Do not implement archive, export, import, or purge during the matrix step.

Known Next Prompt

Lanjutkan project Hyperpos dari repo lokal:

/home/asyraf/Code/laravel/bengkel2/app

State terakhir:

Branch main
HEAD and origin main: 8ef36503 Prove unified audit log source page
Working tree should be clean
Audit Log unified reader scope completed and pushed
ADR-0017 active
Export, import, and purge audit log are backlog until one-month load proof
Next safe scope: audit completeness matrix

Read first:

docs/adr/0017-audit-log-retention-and-archive-evaluation.md
docs/adr/0008-audit-first-sensitive-mutations.md
latest commits 8ef36503 and 8ee7de9c

Goal:

Create a complete audit completeness matrix for all sensitive mutation flows. Prove which flows already record reason, actor, entity, before or after state, and source table. Do not implement before the matrix is complete.
