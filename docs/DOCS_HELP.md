# Hyperpos Docs Help

Purpose:
This file is the fast entrypoint for humans and AI assistants.
Run make docs-help to see where to start without reading every docs file.

Start here for every AI or human session:
1. docs/README.md
2. docs/01_standards/AI_USAGE_GUIDE.md
3. docs/01_standards/00_INDEX.md
4. The active blueprint for the current scope
5. The latest handoff for the current scope
6. The latest local command output from the operator

Priority of truth:
1. Local command output from the operator
2. AI_RULES
3. ADR
4. Active blueprint
5. Latest relevant handoff
6. Older handoff or archive notes

Doc categories:
- docs/01_standards
  Mandatory repo rules for all AI sessions.
  Contains decision policy, workflow policy, output policy, architecture rules, domain map, stack rules, and handoff template.

- docs/adr
  Permanent accepted decisions.
  Use for long-lived architecture, domain, lifecycle, and reporting decisions.

- docs/blueprint
  Active or recent design contracts for a scope.
  Use before implementation.

- docs/workflow
  Roadmap and process flow.
  Some tests still reference this folder, so do not rename it casually.

- docs/dod
  Definition of done.

- docs/handoff
  Historical session logs and recovery notes.
  Do not treat old handoffs as permanent decisions unless promoted into ADR or active blueprint.

- docs/blueprint/v2 or docs/handoff/v2
  V2 continuation lane.
  This is for app-running-while-improving work, feature continuation, live local gaps, and UI/session recovery notes.

- docs/handoff/ui
  Error and audit notes.

Known cleanup warnings:
- ADR 0014 is now a superseded pointer to ADR 0015. ADR 0015 is the canonical decision record.
- docs/handoff/handoff_template.md is legacy compared with docs/01_standards/04_HANDOFF_TEMPLATE.md.
- docs/blueprint/v2/feature-continuation/00-blueprint.md is more like a control ledger than a normal blueprint.
- Some docs reference stale paths such as docs/setting_control. Treat them as historical unless proven active.
- Do not move docs before grep backlink audit.

For AI sessions:
- Read AI_RULES first.
- Read AI_USAGE_GUIDE to know where each type of info belongs.
- Read only the active blueprint and latest handoff relevant to the current scope.
- Never claim tests pass without pasted output.
- Never claim progress without proof.
- Use FACT, GAP, DECISION, PROOF, NEXT.

For humans:
- Use this command first:
  make docs-help

- Then open:
  docs/README.md
  docs/01_standards/AI_USAGE_GUIDE.md

Session rule:
If context risk is 80 percent or higher, create a handoff before continuing large work.
