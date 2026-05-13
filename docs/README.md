# Hyperpos Documentation Index

## Purpose

This directory contains rules, decisions, blueprints, handoffs, workflows, audits, and operational notes for the Hyperpos Laravel kasir/bengkel project.

The goal of this index is to prevent humans or AI assistants from reading every document just to understand where to start.

## Fast Start

Run:

    make docs-help

Then read:

    docs/01-standards/ai-usage-guide.md
    docs/01-standards/00-index.md
    The active blueprint for the current scope
    The latest relevant handoff
    The latest local command output from the operator

## Source Of Truth Priority

Use this order when documents conflict:

1. Local command output from the operator
2. docs/01-standards
3. docs/adr
4. Active blueprint
5. Latest relevant handoff
6. Older handoff or archive note
7. General memory or model knowledge

If local output conflicts with GitHub, local output wins.

## Directory Map

### docs/01-standards

Mandatory operating rules for AI work in this repo.

Use for: zero assumption rule, blueprint-first rule, one active step rule, proof and progress rule,
response structure, handoff policy, architecture boundary, public contract protection,
redaction and error handling, final domain map, stack rules.

Do not use for: daily bug notes, feature status ledger, commit hash snapshots,
temporary UI issues, local stash notes.

### docs/adr

Permanent decision records.

Use for: accepted architecture decisions, accepted domain decisions, lifecycle decisions,
reporting boundary decisions, data representation decisions.

Do not use for: daily handoff, temporary implementation notes, unverified ideas,
task checklist, local command output dump.

ADR files should be stable. If a decision is replaced, mark the old ADR as superseded instead of deleting it.

### docs/blueprint

Design contracts for active or recent scopes.

Use for: scope goal, problem statement, architecture direction, phases,
required tests, definition of done, known gaps, next active step.

Blueprints may be active, closed, superseded, or archived.

### docs/workflow

Roadmap and process flow documents.

Important: Some tests and old handoffs reference docs/workflow/workflow_v1.md.
Do not rename or move this folder before backlink audit and test update.

### docs/dod

Definition of done documents.

### docs/handoff

Session recovery notes.

Use for: what was done, what was proven, what was not done, changed files,
latest branch and HEAD, next safe step, opening prompt for the next session.

Do not treat an old handoff as permanent truth unless its decision was promoted into ADR or active blueprint.

### docs/03-blueprints/v2 or docs/99-archive/handoff/v2

V2 continuation lane.

Use for: app running while being improved, feature continuation ledger,
live local gaps, UI continuation handoffs, V2 session recovery.

This folder is not automatically more authoritative than ADR or AI_RULES.

### docs/99-archive/handoff/ui

Error and audit notes. Use for: investigation notes, error-specific documentation, bug trail.

## Status Tags

Use these tags in docs when possible:

- ACTIVE: Current source for an ongoing scope.
- ACCEPTED: Permanent accepted decision.
- CLOSED: Finished scope or session.
- SUPERSEDED: Replaced by another document.
- HISTORICAL: Kept for traceability, not current instruction.
- STALE: May contain outdated paths or decisions. Must be reviewed before use.
- DRAFT: Not accepted yet.

## Known Overlaps To Clean Later

### ADR 0014 and ADR 0015

ADR 0014 is now a superseded pointer to ADR 0015.

Canonical decision record:

    docs/02-architecture/adr/0015-note-operational-status-open-close-editable-partial-payment.md

Historical superseded pointer:

    docs/02-architecture/adr/0014-note-operational-status-open-close-editable-partial-payment.md

### Handoff template

Canonical template: docs/01-standards/04-handoff-template.md
Legacy template: docs/99-archive/handoff/handoff_template.md

Recommended cleanup: Convert the legacy template into a pointer or archive it
after confirming no active workflow depends on it.

### docs/03-blueprints/v2/feature-continuation

This path contains a feature control ledger:

    docs/03-blueprints/v2/feature-continuation/00-blueprint.md

Recommended cleanup: Keep it, but document that it is a V2 continuation control ledger, not a permanent ADR.

### Stale setting_control references

Some old handoffs reference:

    docs/setting_control/first_in.md
    docs/setting_control/ai_contract.md

These paths are historical unless proven active.

## Naming Rules

ADR preferred:       docs/02-architecture/adr/0016-short-decision-name.md
Blueprint preferred: docs/03-blueprints/v2/scope/YYYY-MM-DD-scope-name.md
Handoff preferred:   docs/99-archive/handoff/v2/scope/YYYY-MM-DD-short-session-name.md
Audit preferred:     docs/99-archive/handoff/ui/YYYY-MM-DD-short-error-name.md

## Promotion Rule

If a handoff contains a decision that should become permanent:

1. Create or update an ADR.
2. Reference the handoff as evidence.
3. Mark the handoff as historical or closed.
4. Do not leave the permanent decision only inside handoff.

## Cleanup Rule

Before moving or renaming docs:

1. Run grep backlink audit.
2. Check route, test, Makefile, and docs references.
3. Update references.
4. Run targeted tests if code references docs.
5. Commit small.

Never move many docs just because the tree looks ugly.
