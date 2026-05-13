# AI Usage Guide

## Purpose

This guide defines where AI-related context belongs.

The project uses several layers of context. Mixing them causes duplicated rules,
stale decisions, and AI sessions that waste time reading old handoffs.

## Layer 1: ChatGPT Memory Or Personalization

Use for stable personal working preferences across many projects.

Put here:
- Preferred language is Bahasa Indonesia.
- Prefer blueprint-first work.
- Prefer evidence-driven answers.
- Prefer zero hidden assumptions.
- Prefer FACT, GAP, DECISION, PROOF, NEXT.
- Prefer copy-paste terminal commands.
- Prefer one active step per response.
- Do not claim progress without proof.

Do not put here:
- repository paths, branch names, commit hashes, active stash names
- test output, temporary bugs, current file changed list, sprint state

Memory is a working style layer, not a project database.

## Layer 2: Project Custom Instructions

Use for project-level defaults that should apply in most repo sessions.

Put here:
- This is a Laravel kasir/bengkel project.
- Local command output from the operator is the highest source of truth.
- Read docs/01-standards before technical implementation.
- Do not change locked domain terms without conflict and evidence.
- Handoff is session recovery, not permanent decision.
- Progress only increases with proof.

Do not put here:
- every ADR, every handoff, all blueprint content, every file path in the repo, stale session notes

Project instructions should point to the correct docs, not duplicate the docs.

## Layer 3: docs/01-standards

Use for mandatory rules that apply to all AI work in this repo.

Put here:
- decision hierarchy, zero assumption rule, blueprint-first rule
- one active step rule, proof and progress rule, response structure
- session capacity rule, output rules, hexagonal architecture rules
- public contract protection, error and log redaction rules
- final domain map, stack rules, official handoff template

Do not put here:
- daily work summary, feature todo list, active bug dump
- temporary UI notes, latest branch or commit, one-day local state

docs/01-standards is the canonical standards root for AI_RULES, not the diary.

## Layer 4: docs/02-architecture/adr

Use for permanent accepted decisions.

Put here:
- architecture decisions, domain decisions, lifecycle decisions
- reporting source of truth decisions, money representation decisions
- auditability decisions, rejected alternatives for long-lived decisions

Do not put here:
- temporary plan, local command output, incomplete idea, session handoff, daily next step

If a decision changes, create a new ADR or mark the old ADR as superseded.

## Layer 5: docs/03-blueprints

Use for active or recent scope design.

Put here:
- final goal of the scope, problem statement, scope in, scope out
- design options, selected direction, phase plan, implementation sequence
- required tests, definition of done, known gaps, next active step

Do not put here:
- permanent global rules that belong in docs/01-standards
- final accepted decisions that should be ADR
- daily command logs unless needed as evidence

Blueprint is the design contract for a scope.

## Layer 6: Handoff Docs

Use for session recovery.

Current handoff locations include:
- docs/99-archive/handoff
- docs/99-archive/handoff/v2
- docs/99-archive/handoff/v2/feature-continuation
- docs/99-archive/handoff/v2/ui

Put here:
- branch and HEAD at the time, dirty state, stash warnings
- files changed, tests run, proof output summary
- what was closed, what was not closed, known risks
- next safe step, opening prompt for next session

Do not put here:
- permanent decisions without later promotion to ADR
- global AI rules, broad architecture constitution

Handoff may be detailed and messy. That is acceptable because it is a historical recovery note.

## Layer 7: Session Opening Prompt

Use to start a new AI session. A good session prompt should include only pointers and fresh proof:

    Kita lanjut repo Laravel kasir/bengkel.

    Wajib baca:
    1. docs/01-standards/00-index.md
    2. docs/01-standards/01-decision-policy.md
    3. docs/01-standards/ai-usage-guide.md
    4. path blueprint aktif
    5. path handoff terakhir

    Rules:
    - local command output saya adalah source of truth tertinggi
    - jangan klaim progress tanpa proof
    - bedakan FACT, GAP, DECISION, PROOF, NEXT
    - satu active step per respons
    - jangan implementasi sebelum snapshot dan blueprint minimum

    Scope aktif:
    - path blueprint
    - path handoff

    Output lokal terbaru:
    - paste command output

Do not paste the entire docs tree unless the task is docs audit.

## What Goes Where

| Information                          | Correct Place                                    |
|--------------------------------------|--------------------------------------------------|
| Personal response preference         | ChatGPT memory or personalization                |
| Project-level operating default      | Project custom instructions                      |
| Mandatory AI rule                    | docs/01-standards                                    |
| Permanent decision                   | docs/02-architecture/adr                                         |
| Active scope design                  | docs/03-blueprints                                  |
| Daily work recovery                  | handoff                                          |
| Feature status ledger                | docs/03-blueprints/v2/feature-continuation or dedicated doc    |
| Test output                          | handoff or proof note                            |
| Commit hash                          | handoff                                          |
| Bug found in live local              | handoff or docs/99-archive/handoff/ui                             |
| Final source of truth domain map     | docs/01-standards and ADR when needed                |

## Promotion From Handoff To ADR

Use this when a session discovers a decision that must become permanent.

Steps:
1. Identify the decision in the handoff.
2. Confirm it is not just temporary implementation detail.
3. Create or update ADR.
4. Put context, decision, consequences, rejected alternatives, and invariants in ADR.
5. Link the handoff as evidence.
6. Mark the handoff as historical or closed.

## Closing A Session

Every large session should end with a handoff containing:
- final goal, current scope, completed steps, pending steps
- locked decisions, files changed, proof and verification
- blockers or gaps, safest next step, opening prompt for a new session

If context risk is 80 percent or higher, stop large implementation and write the handoff first.

## Cleanup Safety

Before renaming or moving docs:
1. Run grep backlink audit.
2. Check app, routes, tests, database, Makefile, and docs.
3. Update references in the same small patch.
4. Run relevant tests when code references docs.
5. Avoid big-bang folder reshuffle.
