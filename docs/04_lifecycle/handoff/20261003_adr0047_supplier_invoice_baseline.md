# ADR-0047 Supplier Invoice Metadata Slice Handoff

## Latest status — implementation delivered for review

This section supersedes earlier in-progress checkpoints below.

- Implementation committed and pushed: 14f7093ee6d7465df6ea2b73d162251024a5259e.
- Branch: fix/adr0047-supplier-invoice-metadata; baseline origin/main: 9d16c3819532ab768a7a7a63c6d9b862165eb90d.
- Issue: https://github.com/Asyraf2003/GlassPos/issues/76.
- PR: https://github.com/Asyraf2003/GlassPos/pull/77 (open; not merged or deployed).
- Full make verify passed: 1881 tests / 14704 assertions, 86 frontend tests, PHPStan and contract audits.
- Final procurement rerun after review and stronger assertions passed: 317 tests / 2243 assertions, 31.66 seconds. This includes the 12 dedicated legacy metadata tests, query/selected-ID lookup exclusion, immutable previous versions, and complete economic-header comparison.
- Final PHPStan, 86 frontend tests, line/Blade audits and diff whitespace checks passed.
- Real Chromium legacy fixture proof passed on desktop/mobile, including metadata save and detail/history consistency. No real business invoice was modified.
- Dashboard worktree preserved at c170c8a2. No dashboard files in this diff.
- Supplementary hexagonal audit has two unchanged baseline violations in bulk product maintenance; not repaired in this slice. Concurrent two-connection supplier-invoice contention remains untested; sequential stale rejection is covered.
- Raw logs/screenshots were temporary artifacts under /tmp; they may not survive environment rotation. Their outcomes and exact reproduction commands are preserved here; do not treat missing temporary artifacts as missing implementation progress.

ONE next target: review PR #77 and address only concrete review findings within this slice. No automatic merge is authorized by this report. Implementation, tests and browser proof are complete; review/merge/deployment remain pending.

Next-session commands, from /home/asus/projects/GlassPos-adr0047:

    git status --short --branch
    git log -2 --oneline
    git rev-parse HEAD origin/main
    gh pr view 77 --repo Asyraf2003/GlassPos --json state,url,headRefOid,mergeStateStatus,reviews,statusCheckRollup

Do not repeat closed discovery without new contradictory evidence. For a relevant new code change, use the test/browser commands below. This documentation closeout is a separate commit after the implementation SHA above; git log gives its exact SHA.

## Exact target and authorization

Implement invoice-number metadata correction with unchanged historical/soft-deleted product references; preserve historical snapshots and economic rows; render historical references on edit/detail; reject inactive new selections; retain existing revision/audit actor/reason and economic reconciliation. No dashboard changes, product resurrection, canonical FK rewrite, schema, opening_stock_seed cleanup, or new inventory/version engine.

Owner approved hybrid autonomy after the first baseline audit: continue discovery, RED tests, implementation, verification, browser proof, self-review, issue/PR/commit without per-step feedback. Earlier wait-for-feedback note is superseded by that explicit authorization.

## Repository state

- Worktree: /home/asus/projects/GlassPos-adr0047.
- Branch: fix/adr0047-supplier-invoice-metadata.
- Initial implementation HEAD and locked origin/main baseline: 9d16c3819532ab768a7a7a63c6d9b862165eb90d.
- Original worktree /home/asus/projects/GlassPos remains on fix/57-dashboard-finance-port at c170c8a2dfff36de9d16b79364f44efcba3109db. Its dashboard work was neither moved nor edited.
- Local main at initial audit: 03a50970227f5c82443d2235910ed0a513e37343.
- Recheck git status and HEAD before resuming; final commit/PR evidence is recorded below when available.

## Contracts and bounded design

Active: ADR-0047 and UI Blueprint 0019. Constraints: ADR-0045, existing ADR-0037 received cost revaluation, ADR-0040 UI financial safety, canonical standards and AGENTS.md.

Only invoice number is treated as administrative in this slice. Supplier/date changes retain the existing economic revision route. Metadata detection compares the authoritative accepted line identity, product, line number, qty, submitted pretax total, and tax inputs. The application repeats classification under the invoice transaction lock. The request uses the same classifier solely to avoid re-confirming unchanged tax residue.

## Confirmed root cause and flow

1. GET /admin/procurement/supplier-invoices/{id}/edit uses EditSupplierInvoicePageController, or redirects received invoices to ReviseSupplierInvoicePageController. Both use the detail reader and EditSupplierInvoiceLineItemsViewBuilder.
2. The detail query already reads product snapshots directly from current invoice lines. The edit builder instead originally derived selected labels solely from active ProductReaderPort::findAll(), causing blank inactive labels and current-master labels after rename.
3. PUT /admin/procurement/supplier-invoices/{id} uses UpdateSupplierInvoiceRequest -> CreateSupplierInvoicePostValidator (duplicate number/date/line/tax checks) -> UpdateSupplierInvoiceController -> UpdateSupplierInvoiceHandler -> transactional runner -> UpdateSupplierInvoiceOperation.
4. Operation originally always called UpdatedSupplierInvoiceBuilder -> tax allocator -> SupplierInvoiceFactory::makeLines(). Factory looked up every submitted product using active ProductReaderPort::getById(), throwing Product tidak ditemukan for an unchanged soft-deleted reference. It regenerated all line IDs and refreshed snapshots from current master even for invoice-number-only changes.
5. Operation then ran received/payment context, paid-total guard, delta builder, negative-stock guard, writer, inventory effect applier, and invoice list projection sync. Economic deltas still use those same services after this patch. No payable ledger is invented; payable derives from accepted total and existing payment history.
6. DatabaseVersionedSupplierInvoiceWriterAdapter originally superseded/reinserted every line. The before-snapshot helper also included superseded lines and omitted product labels. It now reuses the canonical current reader and version snapshot serializer.
7. expected_revision_no was required by HTTP validation but only selected revision mode in the handler; it was never compared to the stored revision. Handler now locks/reloads current invoice inside its transaction and rejects a stale revision before any write.

Root cause was reproduced before production edits: 4 failing / 3 passing tests. Failures proved inactive metadata rejection, active metadata line/snapshot replacement, missing edit label, and accepted stale overwrite.

## Locked implementation decisions

- No change to global product reader eligibility. New/replaced product references still use active master lookup.
- Existing reference continuity requires the submitted previous_line_id to identify a current line of this invoice and retain its product ID. Snapshot labels come from that line, not request-provided labels or current master.
- Metadata correction retains exact persisted line rows/IDs, receipts, stock movements/projections, costing and payments. Writer changes only invoice number/normalized number and revision counter, while appending existing version/audit primitives.
- An economic revision still replaces lines and uses the existing delta/revaluation/negative-stock/paid-total machinery. Reused product identities keep historical labels.
- Audit before/after uses the same snapshot serializer and only current lines; older version records remain append-only.
- UI indicates Historis (tidak aktif); canonical merge target resolution is deferred because this slice adds no lineage schema.
- Unchanged tax residue is not re-confirmed. Validation-error old input does not become trusted UI baseline; backend classification remains authoritative.

## Changed files

Application:
- app/Application/Procurement/Services/SupplierInvoiceMetadataChange.php (new classifier and number correction).
- app/Application/Procurement/Services/UpdateSupplierInvoiceOperation.php.
- app/Application/Procurement/Services/UpdatedSupplierInvoiceBuilder.php.
- app/Application/Procurement/Services/SupplierInvoiceFactory.php.
- app/Application/Procurement/UseCases/UpdateSupplierInvoiceHandler.php.

Adapters/UI:
- app/Adapters/In/Http/Requests/Procurement/CreateSupplierInvoicePostValidator.php.
- app/Adapters/In/Http/Controllers/Admin/Procurement/Support/EditSupplierInvoiceLineItemsViewBuilder.php.
- app/Adapters/In/Http/Controllers/Admin/Procurement/Support/SupplierInvoiceProductLabelBuilder.php.
- app/Adapters/Out/Procurement/DatabaseVersionedSupplierInvoiceWriterAdapter.php.
- app/Adapters/Out/Procurement/Concerns/LoadsCurrentSupplierInvoiceWriteSnapshot.php.
- app/Adapters/Out/Procurement/Concerns/MapsCurrentSupplierInvoiceWriteSnapshotLines.php (removed obsolete duplicate serializer).
- app/Adapters/Out/Procurement/Concerns/PersistsVersionedSupplierInvoiceWrites.php.
- app/Adapters/Out/Procurement/Concerns/ProcurementInvoiceDetailLinesQuery.php (carry line_no through edit form).
- public/assets/static/js/pages/admin-procurement-edit.js.

Proof:
- tests/Feature/Procurement/SupplierInvoiceLegacyMetadataFeatureTest.php.
- tests/Browser/supplier-invoice-legacy-fixture.php.
- tests/Browser/supplier-invoice-legacy.mjs.
- This handoff.

## Verification evidence

- Initial RED: 4 failed / 3 passed / 16 assertions, /tmp/adr0047-red.log.
- First GREEN: 7 passed / 44 assertions, /tmp/adr0047-green1.log.
- Procurement suite: 312 passed / 2213 assertions, /tmp/adr0047-procurement.log (before 5 additional tests).
- Expanded targeted suite: 12 passed / 69 assertions, /tmp/adr0047-expanded.log.
- Real Chromium authenticated against the local Laravel app and dedicated MySQL browser fixture: legacy edit label and product ID rendered; number correction submitted; detail/history showed corrected number, historical label and reason; mobile edit retained historical label and advanced revision. Screenshots: /tmp/adr0047-legacy-edit.png, /tmp/adr0047-legacy-detail.png, /tmp/adr0047-legacy-mobile.png. Desktop edit screenshot visually inspected.
- DB_DATABASE=glasspos_adr0047_test make verify: exit 0, 1881 tests / 14704 assertions, 470.99 seconds; PHPStan, line/Blade audits and 86 frontend tests passed. /tmp/adr0047-verify.log. Final lint and frontend rerun after review changes also passed.
- git diff --check passed after whitespace cleanup.

## Failed experiments / environment boundaries

- SQLite test attempt failed before assertions because an existing MySQL generated-column migration uses AFTER syntax. No migration was changed; tests use isolated MySQL database glasspos_adr0047_test.
- Temporary .env.testing edits were restored exactly to tracked content; run tests with DB_DATABASE=glasspos_adr0047_test. Never point RefreshDatabase at a business database.
- Browser uses a separate glasspos_adr0047_browser database and untracked .env.adr0047-browser. Do not commit that environment file.
- First browser attempt got 419 because artisan serve child selected .env.testing with array sessions. Explicit APP_ENV=adr0047-browser fixed environment selection; CSRF/auth code was not bypassed.
- First browser save succeeded but automation read body during navigation; readiness wait fixed the harness. Reruns preserve and advance fixture history.

## Work not yet closed at checkpoint

Full verify and self-review closed. Final targeted rerun and commit/PR delivery evidence will be recorded below. No real business invoice was modified; browser proof uses the same legacy-reference class with synthetic fixture identities. Parallel two-connection contention has not been exercised; sequential stale rejection is covered.

## ONE next target

Close verification and delivery for this slice; do not restart discovery.

Execution context /home/asus/projects/GlassPos-adr0047:

    git status --short --branch
    git rev-parse HEAD origin/main
    tail -n 8 /tmp/adr0047-verify.log
    DB_DATABASE=glasspos_adr0047_test php artisan test tests/Feature/Procurement/SupplierInvoiceLegacyMetadataFeatureTest.php
    DB_DATABASE=glasspos_adr0047_test make verify
    git diff --check

Browser rerun (separate terminals, same repo root):

    php tests/Browser/supplier-invoice-legacy-fixture.php
    APP_ENV=adr0047-browser php artisan serve --env=adr0047-browser --host=127.0.0.1 --port=8174
    node tests/Browser/supplier-invoice-legacy.mjs

The fixture command creates only its dedicated browser database and refuses to reset existing fixture history. Local Chromium and Node 24 were available. Tests use copied vendor dependencies with regenerated autoload; the dashboard worktree dependencies were untouched.

## Session context health

Operational estimate: safe. Discovery and root cause are closed; continue from verification/delivery only.

## Delivery checkpoint update

- Issue created: https://github.com/Asyraf2003/GlassPos/issues/76.
- Browser rerun with changing invoice number passed; database readback at revision 4: one original line, one original movement, one receipt, qty 2, inventory value 20000, paid 5000, grand total 20000, three appended versions. Readback artifact /tmp/adr0047-browser-db-proof.json.
- Desktop and mobile screenshots visually inspected; historical label remains readable.
- Extra hexagonal audit reports two existing violations in BulkProductMaintenanceRunner and BulkProductMaintenanceValidator (Application imports DB facade). git diff against baseline for both files is empty. This is outside slice scope and outside make verify; no workaround added.
- Final test helper strengthened to compare all persisted invoice fields except number/normalized number/generated uniqueness marker/revision, and prove old version records unchanged after later corrections.

- Final expanded invoice-row assertion initially failed because active_nomor_faktur_normalized is a generated column derived from the corrected number. Migration 2026_10_01_000002 proves it is metadata; test comparison now excludes this marker alongside invoice number/revision. No production code change was needed.
- Temporary browser server stopped; generated environment file moved to /tmp/adr0047-browser.env after proof, so no credentials/config artifact is included in the commit. Fixture command regenerates it for reruns.

## Owner continuity checkpoint (2026-10-03)

Owner explicitly requested periodic committed/pushed documentation before the rolling token quota expires. Exact quota remaining is not visible to the agent. This checkpoint includes implementation and all proof so far, with no dependence on chat-only context. Full verify passed; final procurement rerun is pending final result collection; then create PR and update delivery status. Issue #76 is open. Do not merge automatically.

Single next target after this checkpoint: collect /tmp/adr0047-final-procurement.log result and create the PR from fix/adr0047-supplier-invoice-metadata to main. Use git log -1 and git status to obtain the exact checkpoint SHA; the SHA cannot be embedded in its own commit.
