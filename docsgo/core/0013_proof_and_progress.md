# Proof And Progress

## Purpose

Progress must be tied to evidence.

## Accepted Proof

- `go test` output.
- `make verify` output.
- migration up/down output.
- API test output.
- contract test output.
- architecture/lint script output.
- inspected diff or file contents.
- explicit owner approval.

## Progress Rule

- Plans do not increase progress.
- Created files increase progress only if the active step was file creation.
- Tests passing increase progress only when output is visible.
- A mutation is not complete without unit, adapter, and API proof where relevant.

## Required Proof Statement

Every completion claim must state:

- command or artifact;
- visible result;
- meaning for the active step.

