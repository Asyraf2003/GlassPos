# One real mapping preflight: A3GN5 -> A3GN520

> Superseded execution status: see [projection gate continuation](20261004_real_adoption_projection_gate.md). Owner authorized migration and apply; schema now exists. New dry-run projection differences require a decision. Original preflight below is historical evidence.

## Status / owner boundary

Target: audit this ONE owner-confirmed same-physical-product mapping and run adoption DRY RUN, then stop for review. No apply, no real relation insert, no stock movement, no seed cleanup, no second candidate.

**NEEDS_OWNER_DECISION. Dry-run NOT executed.** Actual local business database `glasspos_local` lacks `product_identity_merges`; its migration is not recorded. Code exists on main, schema has not been deployed to this database. Running schema migration would permanently change the real local business database, outside this session's explicit no-permanent-mutation authorization. Do not bypass this by creating a table manually or treating an isolated copy as successful dry-run of the actual database.

Next ONE target: owner decision whether to authorize ONLY the existing PR #77 schema migration on glasspos_local, then resume the authorized rollback-only dry-run. Alternatively run rehearsal on a separately provisioned disposable copy, explicitly labeling it as a copy, not actual-database verification. Recommendation: explicit schema-only authorization, no adoption apply.

## Repo / environment

- Main and origin/main verified equal: `e1820a1208244fbc757db5bf72bd992fac5967a3`.
- Main worktree clean before this docs slice; PR #77 CLOSED, no code reopened.
- Worktree `/home/asus/projects/GlassPos-adr0047`; audit branch `docs/adr0047-real-mapping-preflight` based on above SHA. Containing docs commit obtainable from git log.
- Database selected via original `/home/asus/projects/GlassPos/.env`: localhost/127.0.0.1 MySQL, `SELECT DATABASE()` returned `glasspos_local`. Credentials never output or committed.
- Audit used PDO `START TRANSACTION READ ONLY`, SELECT queries, then rollback. No app bootstrap, migration, adoption command, or permanent business data mutation.
- Current worktree's default env must NOT be assumed to select this database for future commands; explicitly verify DB configuration before executing anything.

## Authoritative business identity

Owner explicitly locked in this session that A3GN5 and A3GN520 are duplicate identities of the same physical product, A3GN5 retired/legacy, A3GN520 canonical. That direct owner instruction is the authority. Database product history/deletion prose corroborates it but is NOT used to infer mapping. Name/code similarity is not proof.

| Field | A | B |
|---|---|---|
| ID | c824fe66-71d5-4d86-89b5-03bdb3476b5b | 137b8787-7b3e-425e-b4b0-ab286cd8118a |
| Code | A3GN5 | A3GN520 |
| Name | PISTON GREND | PISTON GREND |
| Brand | HONDA | AHM |
| Size | 50 | 50 |
| State | deleted 2026-09-29 14:25:45 | active, deleted_at NULL |

A versions: R1 created 2026-04-22 07:45:48; R2 updated 2026-05-21 03:20:14; R3 price update 2026-09-12 17:07:05; R4 product_soft_deleted 2026-09-29 14:25:45. R4 reason records client duplicate correction to AHM A3GN520.

B versions: R1 created 2026-04-25 01:05:29; R2 updated 2026-06-07 23:51:05; R3 price update 2026-09-11 20:29:19. No merge-specific B version. Database timestamps above are stored values, not assumed timezone conversions.

Valid existing actor: actor_id `1`, role `admin` (actor_accesses).

## Structured prior transfer

source_type `product_master_merge`, shared source_id `8087a690-0324-429e-bc87-3887d4bd03bb`:

- A movement `70857596-af78-493b-82e0-56c28af5b3e8`: stock_out, qty -10, value -1,236,700.
- B movement `bb506ab1-11a7-4511-9d54-f55567274596`: stock_in, qty +10, value +1,236,700.
- Both created_at 2026-09-29 14:25:45. Quantity and value sums exactly zero.
- A full ledger balance qty 0/value 0; inventory projection qty 0/value 0, avg cost 0. Drained gate passes.

REAL ANOMALY (read-only, not repaired): B full ledger qty 77/value 8,480,600, but current projection qty 30/value 3,710,100, avg cost 123,670. Source decomposition:

| B movement source | Qty | Value | Rows |
|---|---:|---:|---:|
| supplier_receipt_line | 20 | 2,473,400 | 2 |
| product_master_merge | 10 | 1,236,700 | 1 |
| opening_stock_seed | 47 | 4,770,500 | 1 |

The exact discrepancy equals the opening_stock_seed row. This arithmetic does not authorize deleting it or establish which state should be repaired. The adopted identity-only command creates no stock effect, but do not give unconditional SAFE_TO_APPLY certification or claim inventory consistency. Record this for owner review; no cleanup scope opened.

## CURRENT invoice gap

Exactly ONE current invoice references A:

- Invoice `5b6b8e6b-0ea7-4ef4-845d-f76f3e45578d`, number `iss26041043`.
- Active/not voided; current revision 3.
- Current A line `42560753-59d9-4d14-9266-9bbe5f573993`, qty 10/value 1,236,700.
- Grand total 6,785,250; paid 0; outstanding 6,785,250; payment_count 0.
- Receipt_count 1; total received qty across invoice 50.
- Current B collision count 0. No consolidation decision needed for this candidate.
- Historical A lines R1/R2 are is_current=0 and are NOT migration targets. Invoice versions R1/R2/R3 recorded on 2026-04-22; no post-transfer revision.
- Expected dry-run outcome (NOT yet observed): one new R4 with B replacing current A, same economics; R1-R3 unchanged; rollback returns persisted current to R3 A and relation remains absent.

## Other-domain reference scan (read-only)

All actual-schema columns named product_id/product_id_snapshot were counted for A:

- supplier_invoice_lines 3: two historical, one current target.
- inventory_movements 4 and product_versions 4: historical immutable evidence, not migration targets.
- product_inventory and product_inventory_costing one zero-balance row each: no positive current stock/value.
- service_product_template_lines 0; service_product_templates 0; work_item_store_stock_lines 0; inventory_cost_adjustments 0; supplier_receipt_lines product_id_snapshot 0.

No other current operational A reference found in this bounded direct-column scan. Not an exhaustive JSON/indirect-reference scan; no claim about every domain. No other-domain mutation.

## Prepared command — NOT EXECUTED

Stable adoption operation ID: reuse exact historical stock-transfer operation UUID as relation ID. This directly anchors retry identity; no auto-discovery or new random ID per attempt. Reason is explicit current owner-approved business correction.

Execution prerequisites: code at merged main; approved existing schema deployed; environment explicitly verified to target glasspos_local; no other app writer during before/after comparison. Use actual-database connection override without exposing secrets; do not assume the worktree's `.env.testing` is the intended database.

DRY RUN (no --apply):

```bash
php artisan products:adopt-transferred-merge \
  8087a690-0324-429e-bc87-3887d4bd03bb \
  c824fe66-71d5-4d86-89b5-03bdb3476b5b \
  137b8787-7b3e-425e-b4b0-ab286cd8118a \
  --actor=1 \
  --reason='Owner-confirmed duplicate physical product A3GN5 merged into canonical A3GN520; adopt prior stock transfer and correct current supplier invoice identity only.' \
  --prior-transfer=8087a690-0324-429e-bc87-3887d4bd03bb \
  --same-physical-product
```

EXACT FUTURE APPLY COMMAND — PREPARED ONLY, requires successful dry-run and SEPARATE explicit owner apply approval:

```bash
php artisan products:adopt-transferred-merge \
  8087a690-0324-429e-bc87-3887d4bd03bb \
  c824fe66-71d5-4d86-89b5-03bdb3476b5b \
  137b8787-7b3e-425e-b4b0-ab286cd8118a \
  --actor=1 \
  --reason='Owner-confirmed duplicate physical product A3GN5 merged into canonical A3GN520; adopt prior stock transfer and correct current supplier invoice identity only.' \
  --prior-transfer=8087a690-0324-429e-bc87-3887d4bd03bb \
  --same-physical-product --apply
```

## Proof still required / next session

Dry-run success, simulated relation/R4-B state, immutable old versions, unchanged economic rows, and full before/after rollback comparison have NOT been demonstrated on this real database. Do not reuse synthetic PR #77 tests as that evidence. Capture stable ordered row hashes/counts for affected tables and observe transactional simulated state via non-mutating instrumentation before rollback, then compare persisted state afterward.

Potential schema-only command for owner review (NOT executed, environment must explicitly target glasspos_local): `php artisan migrate --path=database/migrations/2026_10_03_000100_create_product_identity_merges_table.php`. Never substitute general migrate/fresh/reset or manual relation insert.

Only docs changed this slice. Read-only evidence generated at `/tmp/adr0047-real-audit.jsonl` and `/tmp/adr0047-real-audit-extra.php`; temporary files may disappear. Durable exact findings above. No tests/browser needed for read-only queries/docs. `git diff --check` is the docs verification.
