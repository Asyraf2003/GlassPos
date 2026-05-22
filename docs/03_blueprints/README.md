# 03_blueprints

Design blueprints, DoD, and workflow for each implementation topic.

## Purpose

This folder stores planning documents for work that is not yet final ADR/runtime proof.

Use this folder when a topic still needs analysis, blueprinting, implementation order, DoD, test matrix, or migration-readiness planning before code changes are made.

## Structure

Each topic subfolder may contain adjacent file types:

| Suffix | Type | Contents |
|---|---|---|
| `NNNN_topic_name.md` | Blueprint | Owner decisions, scope, access model, policy design |
| `NNNN_topic_name_dod.md` | DoD | Kriteria selesai — planning dan implementation |
| `NNNN_topic_name_workflow.md` | Workflow | Test matrix, implementation order, CLI workflow, commands |
| `README.md` | Folder guide | Topic boundary, source of truth, and document map for the subfolder |

## Suitable For

- mapping the active scope before implementation
- storing designs that are still allowed to change
- defining the work order and the proof that is required
- making the DoD explicit so completion is unambiguous
- documenting transition strategy before ADR/runtime proof exists

## Not Suitable For

- permanent decisions that should become ADR
- daily session notes
- final test results that fit better in a handoff
- old history that is already finished
- claims that runtime behavior is fixed without proof

## Subfolders

| Folder | Topic |
|---|---|
| `security/` | ADR-0019 access boundary, ADR-0020 public surface, ADR-0022 payment concurrency, ADR-0023 seeder safety |
| `finance/` | Note finance stabilization, finance residual, note revision refund ledger |
| `reporting/` | Report export, reporting execution workflow |
| `seeder/` | Legacy-to-clean seeder migration |
| `mobile/` | Mobile API v1 |
| `audit/` | Canonical audit runtime, audit outbox planning, audit write-path readiness, and PostgreSQL/API audit transition planning |
| `error_log_remediation/` | Error-log remediation process |
| `feature_continuation/` | Feature continuation scope blueprint |

## Rules

- Put exploratory analysis and proposed direction here before runtime edits.
- Move accepted long-term architectural decisions to ADR when they become stable policy.
- Move completed implementation proof to handoff or lifecycle docs.
- Do not use blueprint docs as proof that code behavior already changed.
