# Supplier invoice canonicalization — authoritative mapping gate

RESOLVED: owner approved minimal persistence. Continue at [implementation checkpoint](20261003_adr0047_canonical_implementation.md); the STOP below is historical.

## Exact target and status

Latest owner target: finish canonical product merge lifecycle ONLY for current supplier invoice references. Given authoritative same-physical-product A -> B, append invoice revision B with canonical snapshot, preserve old A revisions, and do not repeat inventory transfer or change qty/value/cost/payable/payment/receipts. Batch affected current invoices; rerun skips already-B invoices. Keep PR #77 metadata compatibility and stale guard.

STOP CONDITION REACHED: no authoritative canonical relation contract/persistence or original merge implementation was found in the inspected repository/local evidence. Owner explicitly requires STOP before introducing persistence. No implementation/schema/business-data mutation was performed this turn.

This supersedes the previous audit's pending choice of economic-edit strategy: owner has now chosen current canonicalization at merge/recovery, not a permanent inactive-current economic model. The remaining decision is the persistence needed to authorize that canonicalization.

## Repository state

- Worktree `/home/asus/projects/GlassPos-adr0047`.
- Branch `fix/adr0047-supplier-invoice-metadata`.
- Discovery HEAD `ff68d2b85bed1c231067156837cdd52e6a138145`.
- origin/main baseline `9d16c3819532ab768a7a7a63c6d9b862165eb90d`.
- This documentation checkpoint is a subsequent commit; `git log -1` gives its SHA.
- PR https://github.com/Asyraf2003/GlassPos/pull/77 remains open/on hold; no merge.
- Dashboard branch/SHA remain preserved as recorded in [prior audit](20261003_adr0047_owner_model_audit.md).

## Discovery evidence (2026-10-03)

Earlier [audit](20261003_adr0047_owner_model_audit.md) already proves the incomplete current state and actual economic risk; those closed characterizations were not repeated.

Additional read-only local database inspection used PDO `START TRANSACTION READ ONLY` and rollback. Credentials were not printed. Evidence:

1. `information_schema.COLUMNS` scan for table names merge/canonical/lineage and columns canonical/merged/merge_/target_product/source_product returned no rows.
2. A deletion audit event `099d1106-a07d-413e-82bf-e53287f8eb64`: event `product_soft_deleted`, actor `1`, source channel `cli_data_workbench`, correlation_id NULL. `metadata_json` contains only `product` (A snapshot including deletion actor/time) and `revision_no=4`; no structured B or merge operation link.
3. No matching `audit_event_snapshots` for A/B since merge date. No matching `audit_outbox` rows for A/B/operation or matching operation metadata/correlation. No matching legacy `audit_logs` for A/operation or merge-named events.
4. `product_versions` already audited: A R4 has merge reason prose, without structured target; B has no merge-specific version.
5. Paired inventory movements have structured product IDs, signs and shared `source_type=product_master_merge`, `source_id=8087a690-0324-429e-bc87-3887d4bd03bb`. They prove physical transfer A -10/value -1,236,700 and B +10/value +1,236,700. They do NOT, without a governing command/contract, establish authoritative canonical mapping, same-product attestation, lifecycle completion, or replay semantics. No relation was inferred from code/name/reason.
6. Movement schema has a nonunique `(source_type, source_id)` index. Its special unique reversal key applies only to `work_item_store_stock_line_reversal`, not product_master_merge. Therefore no database exact-once guarantee for merge can be claimed from that index. Unknown script might have additional guards; source is missing.
7. Repository production source/migrations/scripts/routes and reachable git history contain no writer/contract for `product_master_merge`, `canonical_product`, or `merged_into`. Extended searches across local project worktrees for `cli_data_workbench` and `product_master_merge` found no production script. File-name search in accessible `/tmp` found only this session's read-only audit tools; some system-owned temporary directories were inaccessible. This is a bounded search result, not a claim that the script never existed.
8. Existing bulk product maintenance and product soft-delete have their own transaction/version/audit behavior but no merge operation or dependent-invoice canonicalization. Do not attribute the paired transfers to those implementations.

Proven state remains: historical invoice A; CURRENT invoice R3 A; recorded physical stock transfer to B. No invoice revision was created at merge time. Exact original operation atomicity/idempotency cannot be established from matching timestamps/source IDs alone.

## Minimal persistence recommendation — NOT implemented/approved

Use one product-specific authoritative duplicate-identity merge operation record in ProductCatalog, tentatively `product_identity_merges`. This is narrower than a universal lineage framework and can represent a merge even if no stock needs transfer.

Minimum durable information:

- unique merge operation ID / retry identity;
- explicit source product ID A and canonical target product ID B, referencing existing products including inactive A;
- valid actor ID, explicit duplicate-same-physical-product reason, recorded timestamp;
- explicit link to the existing physical transfer operation when adopting a prior transfer, so invoice canonicalization cannot replay it;
- an unambiguous distinction between an adopted already-applied transfer and a new merge's inventory work. Exact representation belongs in the approved design; absence of a movement is not proof of transfer completion.

One accepted outgoing mapping per source plus source != target and target/cycle checks would prevent conflicting canonical ownership. Reuse existing invoice revision/version/audit writer for before/after actor/reason and source merge operation; no second inventory/version engine. Do not add another invoice migration tracking table merely for reruns: locked current-line comparison can skip invoices already canonical, with existing version/audit preserving evidence.

Why here: canonical identity belongs to ProductCatalog, not invoice-specific tables or reporting. Existing general-purpose audit JSON is explanatory evidence; no existing canonical-relation event contract exists. Adding such a structured event contract instead is possible, but would still be NEW authoritative persistence semantics and would require equivalent uniqueness/recovery guarantees. Recommendation is the explicit product-specific record, not parsing arbitrary audit JSON or movement signs as canonical policy.

## Migration implications

- A schema migration would create the relation storage only; do not auto-populate canonical identities from deletion prose, product similarity, or stock movement direction.
- Adoption of prior transfers requires explicit confirmed source/target IDs and existing transfer operation linkage, then validates evidence before recording the relation. This approval request does not authorize business-data repair.
- The existing operation ID can be retained as the provenance/retry anchor; no old ledger/version/audit rewrite.
- Once authoritative mapping is accepted, backend discovers CURRENT supplier invoice references and appends B revisions with unchanged economics, never sends identity-only migration through ordinary product replacement stock-out/in logic blindly.
- Rerun after current B must skip; old A snapshots/receipts/movements remain untouched.
- New merge operations must persist relation and supplier-invoice current revisions with the correct atomic boundary and exact-once stock effects. No claim of whole-application merge completion while other domains are out of scope.

## Owner decision required

Approve a minimal ProductCatalog duplicate-identity merge operation record as authoritative A -> B persistence, with explicit prior-transfer linkage and no automatic prose-based backfill? If an existing original script/contract is available, provide its path/commit to verify before adding storage.

The need to stop comes from the owner's explicit IMPORTANT DISCOVERY CONDITION: if no authoritative machine-readable mapping exists, STOP and report minimal persistence/location/migration implications. It is not an additional approval flow invented by the assistant.

## Files changed / proof / unfinished

This turn changes only this handoff and pointers in earlier handoffs. No production code/test/schema change. Read-only discovery completed; new mapping implementation has NOT begun.

Prior characterization + metadata tests: 13 passed / 94 assertions; PHPStan passed at ff68d2b8. No fresh test or make verify run for this documentation-only gate. New required 20-case canonicalization matrix, procurement regression, full verify and current-B/history-A browser proof are still pending implementation. Old baseline browser proof covers metadata compatibility only.

Temporary evidence: `/tmp/adr0047-mapping-discovery.php` and `.jsonl`; do not rely on their survival. Durable findings are above. `git diff --check` is the documentation closeout check.

ONE next target: obtain owner decision on authoritative merge persistence (or original structured merge contract), then resume supplier-invoice-only canonicalization. Do not substitute a generic inactive-economic blocker for the owner's canonicalization target.

Starting commands, from `/home/asus/projects/GlassPos-adr0047`:

```bash
git status --short --branch
git rev-parse HEAD origin/main
git log -3 --oneline
cat docs/04_lifecycle/handoff/20261003_adr0047_canonical_mapping_gate.md
rg -n 'product_master_merge|canonical_product|merged_into|cli_data_workbench' app database routes scripts
```

Read ADR-0047 / Blueprint UI 0019 owner clarification and the supplied owner persistence decision before any implementation. Preserve dashboard, metadata behavior, historical ledgers and separate product/service domains.
