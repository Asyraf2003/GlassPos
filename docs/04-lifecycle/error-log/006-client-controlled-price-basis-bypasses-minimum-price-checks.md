# 006 - Client-controlled price basis bypasses minimum price checks

## Status

Fixed with proof.

Final technical fix is implemented and verified with targeted unit/feature coverage plus Note/Payment blast-radius coverage.

Docs closure and commit/push proof for this updated error log remain pending until owner commits/pushes this documentation update.

## Severity

High.

## Source

Audit report #006: Client-controlled price basis bypasses minimum price checks.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 005-note-revision-silently-drops-overpaid-allocations.md
- 004-refunded-work-items-survive-revisions-and-inflate-stock.md

### Jenis Keterkaitan

Direct workflow relationship with #005.

Indirect workflow relationship with #004.

### Alasan

Laporan #006 dan #005 sama-sama berada pada PATCH note workspace revision flow.

- #005 membahas payment replay saat downward note revision.
- #006 membahas client-controlled price_basis pada revision product line yang bisa melewati minimum selling price policy.

Laporan #006 juga berkaitan tidak langsung dengan #004 karena jika bypass berhasil, store-stock line tetap dapat dibuat dan inventory tetap bisa issued, sehingga inventory/financial integrity ikut terdampak.

Namun #006 bukan bug identik dengan #004/#005 karena root cause utamanya adalah trust boundary violation: server mempercayai marker pricing dari client.

## Update Log

### Update 1

Initial audit log entry untuk laporan #006.

Alasan update:

- Laporan menunjukkan client-controlled hidden field price_basis dapat dipakai untuk bypass minimum price check.
- Patch sudah diterapkan pada WorkItemFactory::makeSto().
- Patch menghapus bypass condition berbasis price_basis.
- Verification masih gap karena hanya php -l, git status, dan commit yang dilaporkan.

### Update 4

Final server-side price basis authority fix verified.

Alasan update:

- Proof lama hanya mencakup syntax/status/commit dan sudah stale.
- Targeted unit boundary membuktikan forged underpriced `revision_snapshot` line sekarang ditolak.
- Targeted feature coverage membuktikan underpriced current/revision input ditolak dan mutation rollback menjaga revision, work item, inventory movement, dan stock.
- Historical revision snapshot behavior tetap dijaga saat server bisa membuktikan line berasal dari trusted current revision/work item snapshot.
- Runtime bug tambahan ditemukan: builder path sudah memberi trusted marker, tetapi apply/persist path membangun ulang work items dari original payload sehingga marker hilang.
- Final fix menambahkan server-side trusted revision snapshot marker di builder/handler/apply persistence path, bukan mempercayai `price_basis` dari client.
- Note/Payment blast-radius dilaporkan pass: 163 tests, 969 assertions.

## Ringkasan Indonesia

Bug terjadi pada alur revisi note yang menerima product line dari client.

Field:

price_basis

dikirim dari HTTP revision workspace input, dinormalisasi, divalidasi, lalu diteruskan sampai ke WorkItemFactory.

Sebelum patch, WorkItemFactory::makeSto() melewati MinSellingPricePolicy::assertAllowed() jika payload memiliki:

price_basis = revision_snapshot

Masalahnya, price_basis berasal dari client. Walaupun niat awalnya adalah menjaga harga historis saat revision, marker itu tidak dibuktikan server-side sebagai snapshot existing line yang immutable.

Akibatnya authenticated cashier/admin dapat membuat PATCH request dengan:

- product_id valid
- qty valid
- unit_price_rupiah sangat rendah, misalnya 1
- price_basis = revision_snapshot

Lalu server membuat store-stock line di bawah harga minimum dan tetap mengeluarkan inventory.

## Dampak

Dampak utama:

- cashier/admin bisa mencatat penjualan store-stock di bawah harga minimum
- MinSellingPricePolicy bisa dilewati
- revenue record menjadi underpriced
- inventory tetap issued/stock_out
- fraud control berbasis floor price tidak efektif
- laporan penjualan dan margin bisa rusak

Ini financial-integrity dan inventory-integrity issue.

Severity High tepat karena minimum selling price adalah business invariant untuk POS/back-office. Tidak Critical karena butuh authenticated cashier/admin, akses note, CSRF/session valid, dan produk dengan stok.

## Jalur Risiko

Authenticated cashier/admin melakukan crafted revision request.

Workflow risiko:

1. User login sebagai cashier/admin.
2. User membuka note yang bisa direvisi.
3. User mengirim PATCH revision workspace request.
4. Request menyertakan product line dengan price_basis=revision_snapshot.
5. UpdateTransactionWorkspaceRules menerima value tersebut.
6. StoreTransactionWorkspaceProductLineNormalizer mempertahankan field tersebut.
7. CreateTransactionWorkspaceStoreStockLineMapper menyalin price_basis ke payload domain.
8. WorkItemFactory::makeSto() memakai price_basis untuk skip MinSellingPricePolicy.
9. StoreStockLine dibuat dengan harga di bawah floor.
10. Inventory issue tetap berjalan.

## Root Cause

Root cause utama:

Client-controlled marker dipakai sebagai dasar otorisasi/invariant bypass.

Field `price_basis` dari HTTP revision workspace input sempat dapat mencapai materialisasi domain dan mempengaruhi apakah minimum selling price policy dijalankan.

Root cause final setelah debugging:

1. `price_basis=revision_snapshot` dari client tidak boleh dipercaya.
2. Patch kasar yang selalu memakai current catalog floor price tidak cukup karena merusak behavior legitimate historical revision snapshot.
3. Server perlu membedakan forged client marker dari historical snapshot yang benar-benar berasal dari server-owned current revision/work item.
4. Builder path sempat menandai trusted snapshot dengan benar, tetapi apply/persist path membangun ulang work items dari original payload sehingga trust marker hilang sebelum persistence.

Yang benar:

- Client boleh mengirim data form, tetapi tidak boleh menjadi authority untuk bypass financial invariant.
- Historical under-current-catalog price hanya boleh dipertahankan jika server membuktikan line cocok dengan current revision/work item snapshot yang dipercaya.
- Trust marker harus tetap hidup sampai persistence path, termasuk saat replacement revision diterapkan.

Yang tidak boleh:

- hidden form field dari client langsung menentukan bypass financial policy
- `price_basis=revision_snapshot` dari request diperlakukan sebagai bukti bahwa harga historis valid
- trusted marker hilang saat work items dibangun ulang sebelum persistence

## Patch Summary

Final patch scope:

Production files changed:

- `app/Application/Note/Services/WorkItemFactory.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceStoreStockLineMapper.php`
- `app/Application/Note/Services/RevisionWorkspace/RevisionSnapshotStoreStockLineTrustMarker.php`
- `app/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilder.php`
- `app/Application/Note/UseCases/CreateNoteRevisionHandler.php`
- `app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php`

Test files changed:

- `tests/Unit/Application/Note/Services/WorkItemFactoryTest.php`
- `tests/Unit/Application/Note/Services/RevisionWorkspace/RevisionSnapshotStoreStockLineTrustMarkerTest.php`
- `tests/Unit/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilderTest.php`
- `tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php`

Final fix:

- `WorkItemFactory` no longer trusts raw client `price_basis` as authority to bypass price floor checks.
- forged underpriced `revision_snapshot` store-stock line is rejected.
- underpriced `current_catalog` store-stock line is rejected.
- historical under-current-catalog `revision_snapshot` is allowed only when server-side trusted revision snapshot marker proves it comes from current revision/work item data.
- revision workspace mapping/builder path derives trust from server-owned data instead of client input.
- `ApplyNoteRevisionAsActiveReplacement` marks trusted items again before persistence because revision apply path rebuilds work items from original payload.
- rejected mutation rolls back without creating extra revision, work item, store-stock line, inventory movement, or stock mutation.

Important runtime fix:

The builder path alone was not sufficient. Runtime persistence rebuilt work items again inside `ApplyNoteRevisionAsActiveReplacement`, so the trusted marker had to be restored there before final materialization.

## Scope In

- `WorkItemFactory::makeSto()`
- store-stock line materialization
- minimum selling price invariant
- revision flow price floor enforcement
- server-authoritative revision snapshot trust marker
- revision workspace store-stock line mapping
- revision payload note builder trust derivation
- revision handler/apply path preservation of trusted historical snapshot behavior
- rollback behavior for rejected underpriced mutation

## Scope Out

- UI hidden field cleanup
- request validation cleanup for accepting/rejecting the raw `price_basis` field
- Blade/UI changes
- full browser/manual QA
- full global test suite
- explicit product pricing migration/backfill
- unrelated inventory issue logic outside rejected revision rollback proof
- changing ADR/domain terms
- allowing client-controlled financial bypass

## Proof Dari Patch Session

Targeted proof reported from the final fix session:

Syntax PASS:

- `php -l app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php`
- `php -l app/Application/Note/Services/RevisionWorkspace/RevisionSnapshotStoreStockLineTrustMarker.php`
- `php -l app/Application/Note/Services/WorkItemFactory.php`
- `php -l app/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilder.php`
- `php -l app/Application/Note/UseCases/CreateNoteRevisionHandler.php`

Framework/cache proof:

- `php artisan optimize:clear`: PASS

Targeted unit proof:

- `php artisan test tests/Unit/Application/Note/Services/WorkItemFactoryTest.php`
  - PASS: 4 tests, 6 assertions
- `php artisan test tests/Unit/Application/Note/Services/RevisionWorkspace/RevisionSnapshotStoreStockLineTrustMarkerTest.php`
  - PASS: 2 tests, 2 assertions
- `php artisan test tests/Unit/Application/Note/UseCases/CreateNoteRevisionPayloadNoteBuilderTest.php`
  - PASS: 2 tests, 4 assertions

Targeted feature proof:

- `php artisan test tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php --filter=reuses_only_net_payment_after_refund`
  - PASS: 1 test, 9 assertions
- `php artisan test tests/Feature/Note/CashierProductReplacementBackdatedPriceFinanceFeatureTest.php --filter=price_floor_rejection`
  - PASS: 1 test, 14 assertions

Blast-radius proof:

- `php artisan test tests/Feature/Note tests/Feature/Payment`
  - PASS: 163 tests, 969 assertions

Behavior proven:

- underpriced `current_catalog` store-stock line is rejected.
- underpriced forged `revision_snapshot` store-stock line is rejected.
- server-trusted historical `revision_snapshot` store-stock line below current catalog still succeeds.
- rejected mutation rolls back:
  - no new revision
  - no extra work item/store-stock line
  - no extra inventory movement
  - stock remains unchanged
- existing historical snapshot flow still preserves net payment after refund.

Temporary diagnostics that must not remain:

- `TEMP_DEBUG_RUNTIME_ROOT_WORK_ITEMS`
- `TEMP_DEBUG_BUILDER_TRUST_MARKER`
- `TEMP_DEBUG_FACTORY_TRUST_MARKER`
- `TEMP_DEBUG_HANDLER_ROOT_WORK_ITEMS`
- `TEMP_DEBUG_STORE_STOCK_MAPPER_TRUST`

## Verification Gap

Verified locally for targeted #006 behavior and Note/Payment blast-radius.

Remaining gaps before full project/global closure:

- full global suite not reported for this item
- browser/manual QA not reported
- UI/request validation cleanup for raw `price_basis` remains out of scope
- docs update commit/push proof must be added after owner commits/pushes this documentation update
- final #001 global verification remains pending until selected residual logs are closed

## Recommended Follow-up

Immediate follow-up:

1. Run hygiene/status checks.
2. Commit and push this docs update manually by owner.
3. Record the docs commit hash after commit/push proof exists.

Recommended next residual target after #006 docs closure:

- `#009` via `docs/workflow/security-adr-0019-access-boundary.md`, unless owner chooses otherwise.

Do not proceed to #009 before #006 docs closure is committed/pushed if the workflow requires clean residual-log bookkeeping.

## Kesimpulan

Laporan #006 valid sebagai High severity financial-integrity issue.

Bug sebelumnya mempercayai `price_basis` dari client untuk melewati minimum price policy. Itu trust-boundary bug: hidden field dianggap seperti otoritas finansial, padahal browser user bisa mengubahnya semudah manusia mengubah pikiran saat lapar.

Final fix tidak lagi mempercayai client `price_basis`.

Historical revision snapshot behavior tetap dipertahankan, tetapi hanya saat server bisa membuktikan line berasal dari trusted current revision/work item snapshot. Runtime apply/persist path juga ikut menandai trusted items sebelum persistence agar marker tidak hilang saat work items dibangun ulang.

Status teknis: fixed and locally verified with targeted unit/feature tests plus Note/Payment blast-radius.

Status dokumentasi: updated in working tree by this patch, commit/push proof pending owner action.

## Related Workspace Security Finding From Error Log 007

### Related Error Log

- 007-admin-note-edit-page-exposes-stored-xss.md

### Update

Update 2.

### Reason

Laporan audit lanjutan menemukan issue terpisah dengan severity High pada shared note workspace surface.

Ini bukan root cause yang sama dengan #006.

- #006 is about server-side price floor bypass through client-controlled price_basis.
- #007 is about stored XSS caused by raw JSON embedding of cashier-controlled note fields into the admin edit workspace.

Kedua temuan melibatkan input note workspace/revision yang melewati trust boundary, sehingga perubahan workspace berikutnya harus memverifikasi server-side financial invariants dan safe client-side rendering sekaligus.

## Related Closed-Note Authorization Finding From Error Log 009

### Related Error Log

- 009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md

### Update

Update 3.

### Reason

Laporan audit lanjutan menemukan issue terpisah pada route cashier note workspace update.

Ini bukan root cause yang sama dengan #006.

- #006 is about price floor bypass through client-controlled price_basis.
- #009 is about cashier authorization bypass for closed-note workspace PATCH.

Keduanya melibatkan note workspace update, sehingga perubahan berikutnya harus memverifikasi server-side invariants dan route-level mutation guards.
