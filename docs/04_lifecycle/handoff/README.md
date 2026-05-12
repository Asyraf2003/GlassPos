# Handoff Notes

Status: Historical session recovery lane

Purpose:

`docs/handoff` stores session recovery notes, implementation summaries, proof trails, and next-step context from previous work sessions.

This folder is not the primary source of truth for permanent decisions.

Use handoff files to recover context, then verify current state with local command output before continuing work.

## Source of truth priority

Use this priority when a handoff conflicts with other docs or the repo:

1. Current local command output
2. docs/01_standards
3. docs/adr
4. Active blueprint for the current scope
5. Latest relevant handoff
6. Older handoff notes

## What belongs here

Use this folder for:

- session summaries
- recovery notes
- latest proof and command output references
- changed file summaries
- next safe step notes
- historical implementation context

Do not use this folder as the only place for:

- permanent domain decisions
- long-lived architecture decisions
- public contract changes
- source-of-truth workflow rules
- final domain maps

Permanent decisions must be promoted into `docs/adr`.

Active implementation contracts should be promoted into `docs/blueprint`.

Mandatory AI/session rules belong in `docs/01_standards`.

## Folder map

### docs/handoff

Older root-level handoffs and step-based historical notes.

These files may contain useful recovery context, but some names and references may be old.

### docs/handoff/ui

UI-specific historical handoffs.

Use these for UI recovery context only. Do not treat UI handoff notes as final domain decisions.

### docs/handoff/v2

V2 application continuation handoffs.

Use these when continuing work on the running application while improving it gradually.

### docs/handoff/v2/cashier

Cashier-specific V2 recovery notes.

### docs/handoff/v2/note-finance

Note finance and refund/current projection recovery notes.

### docs/handoff/v2/report

Reporting V2 recovery notes and reporting blueprint handoff context.

### docs/handoff/v2/seeder-audit

Seeder audit recovery and proof notes.

### docs/handoff/v2/seedernew

Seeder finance, scenario matrix, and proof notes.

Some files here may contain ADR-like analysis, but they are not permanent ADR files unless promoted into `docs/adr`.

### docs/handoff/v2/ui

V2 UI recovery notes.

Some notes may supersede older notes. Prefer the newest relevant handoff and verify against current repo state.

## Legacy template

Legacy path:

- docs/handoff/handoff_template.md

Canonical template:

- docs/01_standards/04_HANDOFF_TEMPLATE.md

Use the canonical template for new handoffs.

The legacy template is kept only for old links and historical references.

## Rules

- Do not delete handoff files during docs cleanup.
- Do not move or rename handoff files without backlink audit.
- Do not treat old handoffs as permanent truth by default.
- Do not mass-edit historical handoffs just to modernize wording.
- If a handoff contains a permanent decision, promote the decision into ADR.
- If a handoff contains an active implementation contract, promote it into `docs/blueprint`.
- Always verify old handoff claims against current repo files and local command output.

## Recommended session start

For work that depends on handoff history, read:

1. docs/README.md
2. docs/DOCS_HELP.md
3. docs/01_standards/AI_USAGE_GUIDE.md
4. docs/01_standards/00_INDEX.md
5. docs/handoff/README.md
6. The latest relevant handoff for the current scope
7. The latest local command output from the operator
