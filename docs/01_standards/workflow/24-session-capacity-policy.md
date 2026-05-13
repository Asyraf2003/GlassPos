# P1 - Session Capacity Policy

## Purpose

This rule prevents long AI work sessions from continuing after reasoning quality, context clarity, or remaining execution capacity becomes unsafe.

The goal is not to claim exact machine telemetry. The goal is to force an operational stop before the AI starts losing precision, mixing scopes, forgetting proof, or making risky assumptions.

## Required Capacity Footer

At the end of every technical work response, the AI must include this footer:

~~~text
Kapasitas sesi:
- Kemampuan menalar: xx%
- Jendela konteks: xx%
- Kemampuan sisa: xx%
- Status: aman / mulai rawan / ganti halaman baru
~~~

## Capacity Meaning

### Kemampuan menalar

Estimated reasoning reliability for the current page/session.

This is affected by:
- number of files already analyzed
- number of patches already proposed
- number of decisions already made
- amount of unresolved ambiguity
- complexity of current domain
- risk of mixing previous and current scope

### Jendela konteks

Estimated remaining useful context clarity in the current chat/page.

This is affected by:
- long command outputs
- pasted logs
- repeated file contents
- handoff summaries
- multiple implementation phases in the same page
- old decisions that may conflict with newer facts

### Kemampuan sisa

Estimated safe remaining working capacity for the current page.

This combines reasoning reliability and context clarity into one practical safety indicator.

## Threshold Rule

If any indicator is below 80%, the AI must stop large implementation work.

When below 80%, the AI must:
- avoid starting a new major patch
- avoid broad refactors
- avoid multi-file implementation
- prepare a concise handoff
- recommend opening a new chat/page
- continue only with small clarification, audit, or handoff work

## Status Values

### aman

Use when all indicators are 80% or above and the next step is still safe.

### mulai rawan

Use when at least one indicator is near the threshold or the next step has elevated risk.

The AI may continue only with:
- read-only audit
- small docs-only patch
- small single-file patch
- handoff preparation

### ganti halaman baru

Use when any indicator is below 80%.

The AI must stop major implementation and produce a handoff before continuing in a new page.

## New Session Baseline

A new chat/page does not mean perfect 100% capability.

A new page usually resets active chat clutter and improves context clarity, but the AI still carries:
- system instructions
- project rules
- user preferences
- durable memory
- handoff constraints
- repo-specific AI rules

Practical starting estimate for a clean new page with a good handoff:

~~~text
Kapasitas sesi:
- Kemampuan menalar: 92-95%
- Jendela konteks: 95-98%
- Kemampuan sisa: 92-95%
- Status: aman
~~~

Do not claim exact telemetry. Treat these numbers as operational risk estimates, not internal machine metrics.

## Mandatory Behavior

- Do not treat the capacity footer as decorative.
- Do not continue large implementation below 80%.
- Do not claim 100% just because a new chat starts.
- Do not hide uncertainty about capacity.
- Do not use capacity numbers to excuse weak reasoning.
- Always prefer handoff before context becomes unreliable.
- If the user asks whether to continue or open a new page, choose the safer option based on the threshold.

## Handoff Trigger

When status is `ganti halaman baru`, the handoff must include:
- completed work
- pending work
- locked facts
- decisions made
- files changed
- verification proof
- known risks
- safest next step
- exact prompt for the next page

## Example

~~~text
Kapasitas sesi:
- Kemampuan menalar: 79%
- Jendela konteks: 78%
- Kemampuan sisa: 78%
- Status: ganti halaman baru
~~~

Correct action:
- stop new implementation
- write handoff
- continue in a new page

Incorrect action:
- start another refactor
- patch multiple files
- assume missing details
- claim progress without proof
