# 0029 - Whole-Note Cancellation Owner Decision Handoff

## Status

- Date: 2026-09-26, Asia/Makassar
- Scope: docs-only contract maturation for whole-note transaction cancellation / `Batalkan Transaksi`
- Status: ACTIVE / OWNER DECISIONS PARTIALLY LOCKED / CONTRACT GAPS REMAIN
- Production code: NOT CHANGED
- Previous lifecycle baseline:
  - `docs/03_blueprints/finance/0018_primitive_lifecycle_contract_map_and_adversarial_torture_tests.md`
  - `docs/04_lifecycle/handoff/0027_primitive_lifecycle_ordered_execution_handoff.md`
  - `docs/04_lifecycle/handoff/0028_blueprint0018_closeout.md`
  - `docs/02_architecture/adr/0042_note_edit_refund_settlement_machine_contract.md`
  - `docs/02_architecture/adr/0044_payment_settlement_intent_cash_tender_and_ui_compression.md`
  - `docs/02_architecture/adr/0045_transaction_revision_version_graph_and_full_layer_snapshot_contract.md`

This file intentionally preserves the owner's raw language separately from AI-normalized interpretation. If the normalized interpretation conflicts with the raw owner statement, stop and resolve the conflict with the owner before promoting the decision into an ADR.

## FACT

- Blueprint0018 classifies whole-note cancel/delete as `MISSING PRIMITIVE`.
- Reset is not a domain action.
- Whole-note cancellation must not be implemented as destructive SQL deletion.
- ADR-0044 already states that cancellation is a lifecycle decision whose financial/inventory consequences must use shared primitives rather than a separate magic financial engine.
- ADR-0045 preserves immutable revision/history and requires current operations to resolve against the current accepted revision.
- Existing Simple/Detail behavior is presentation compression over the same primitive engine.
- Existing concurrency/idempotency work in handoff0027 provides reusable root-lock, replay/conflict, and stale-revision patterns.
- Existing note access direction gives cashier today/yesterday operational access and admin broader read access; transaction-sensitive admin mutation still depends on transaction capability/domain policy. ADR-0019 remains marked draft for owner review and must not silently be promoted by this handoff.

## RAW OWNER DECISION RECORD

The following text is intentionally retained in the owner's own wording and has not been rewritten into canonical contract language:

> 1. bagi saya itu tetep ada refund tetep ada cancel, ketika 2 2 nya di klik cancel maka yg seharusnya refund mesin belakangnya dan si kasir itu diarahkan ke refund sistem, sedangkan cancel yaa ikut sistem cancel, ada gambaran?
>
> 2. cancel itu bener" seperti semuanya semu cuma tercatat aja di log dan laporan cancel, tapi g mempengaruhi apapun, seperti yaa bener" salah aja pengaruh g ada tapi cctv tetep nangkep pernah ada maling masuk tapi g ambil apa" dan keluar lagi gitu analoginya
>
> 3. untuk ini uang masuk tercatat tapi uang fisiknya g ada itu kesalahan kasir, tetep masuknnya ke refund dan kasir mempertangungjawabkan isi soal uang tapi uangnya g ada, gimana klo dumy? tetep refund, karena ada laporan keunangan disana beda dengan simpan nota saja tanpa membayar 1 rp pun
>
> 4. kayaknya di sini seharusnya no 2 menjawabnya
>
> 5. sudah dijawab no 2
>
> 6. ok setuju, soal harga salah ini padahal ada model edit versioning(ini fungsinya jadi admintelat update dan sistem jalan gitu bisa tetep besoknya versioningkan notanya)
>
> 7. g paham, auditor ini ngapain tetep mau lihat? dia siapa? saya g paham, kita bahas lagi
>
> 8. sudah ada mekanismenya sebenrnya, klo di refund itu dia balikkan atau ngga? nah oto nya sih balikkan ada detailnya seharusnya perlu alasan dibalikan g dibalikan kenapa, soal prod juga ada fitur kurangi stok prod nya dan tulis alasan, bisa dikoreksi secara teknis pendapat saya
>
> 9. ini sebenrnya simpel yaa, hak kasir dan admin itu sebenrnya sama, tapi saya pantau belakangan ini kasir terbatas di beberapa hal, padahal bedanya hanya kasir g punya kontrol data yg sudah g aktif kemarin kebelakang, dan nota kemarin dan hari ini yg aktif masih bisa maupun g aktif, beda time aja sih, cek ulang docs apa ada dibahas soal ini untuk koreksi saya
>
> 10. sudah kejawab di no 9
>
> 11. untuk ini klo data sudah ditulis sudah disimpan itu data barang luar masuknya transaksi walaupun full hutang, masuk area refund dia
>
> 12. perhatikan sistem kita bagaimana bekerja, kita ikuti mental modelnya
>
> 13. g paham
>
> 14. jelaskan
>
> 15. sebernya kasusnya g mungkin seperti itu, karena gimana caeritanya admin edit dan kasir edit nota yg sama? tapi oklah ambil hal itu ya, tetep pegangan terkuat versi utama itu editan terbaru misal v1 yg di edit yaa dia yg aktif, sebenrnya mereka itu kayak colokan primitifnya, bisa dicolok kemana saja, tapi yg terkuat yg terbaru, klo ada 2 dan berdekatan maka yg dipilih tetep yg terbaru editannya
>
> 16. hmm okok, jelskan sedikit, setau saya bagian oto itu catat sesuai kasusnnya seperti jelaskan apa yg terjadi klo manual detail yaa kasir isi sendiri
>
> 17. bolee, jelaskan analogi
>
> 18. yups setuju
>
> 19. kayaknya jawaban yg atas bisa pake buat disini
>
> 20. kan itu model colokan, anggap aja seluruh kasusnya kecolok di v2 nya bukan di v1 refundnya karena itu tu edit modelnya uang masuk pun tetep aja edit ya edit, diatas transaksi dia karena versioning itu model colokan, datanya g pernah ilang tapi g mempengaruhi apapun karena seluruh rantai ke solokan 2 bukan 1, 1 masuk ke riwayat dan legacynya tapi g mmepengaruhi laporan(ini pure pendapat bisa dikritik, soalnya berdasrkan kasus dunia nyata yg awal g pernah dianggap ada, editan selalu memperbarui data sebelumnya, kecuali aksus uji ekstrem yg semau jidat v1 v2 aneh" v3 aneh" tetep aja kasusnya ektrem tapi yg editan teteplah dunia nyata setau saya yg lama penuh coretan jadi g dipake tapi kasus lama disalain dan ada pembaruan di v terbarunya gitu)
>
> 21. g paham
>
> 22. hmmm boleh" soft delete itu saya pikir data g ilang bukan ilang permanen gitu bukan bisa dipulihakan eh sebenrnya ada kasusnnya sih jadi ok hutang dari 22 ini kita bahas setelah anda baca keputusan saya, jangan lupa push main, terkadang beda teks raw saya vs pemahaman anda jadi beberapa handoff saya rasa perlu juga ttd mentah keputusan manusia bukan yg sudah diterjmahkan ai full

## RAW OWNER DECISION ADDENDUM — 2026-09-26

The following additional owner statements refine cancellation, UI compression, and restore semantics:

> gap a itu konsepnya kayak maling, klo emangnya maling cuma sentuh barangnya tapi g dibawa dan di dunia nyata dia kasi lagi ke si kasir kasusnya sama saja kan? abstraknya di manusinya, gimana klo modelnya client narik ulur barangnyake tangan kasir? puluhan riwayat data barang keluar masuk? anjay
>
> no 3 anda kesannya saya liat agak ribet tapi primitiv sih jadi saya setuju tapi user g akan ngalami due dan paid, taunya balik yaa dikembalikan uangnya gitu
>
> no 4 bagian refund yg ada prod nya atau bagian paket saya rasa gaya ref nya udah gini deh
>
> no 5 yups, intinya itu kebiasaan toko langsung dieksekusi 1-2 klik, detail? yaa sebenrnya bukan jadi lebih detail tapi layernya turun gitu, jadi 1x klik di oto = klik 4-5 transaksi di backend, detail yaa klik 4-5x gitu normal dia, ini analoginya
>
> jawaban gap
> a g paham, bahasa anda tinggi dan silau, rendahkan sedikit agar saya bisa melihatnya
> b auto g dibuat, buat saja versi detailnya, auto dibuat karena kasir ngeluh ini kok proses lama bisa ngga ini auto a b c aja, nah auto lahir darisana bukan lahir dari build
> c ok
> d ok

## NORMALIZED OWNER DECISION SNAPSHOT

These are working interpretations, not a replacement for the raw statements above.

### 1. Refund and cancellation remain distinct user/domain intents

- Keep both `Refund` and `Batalkan Transaksi`.
- If a recorded payment already exists, cancellation must route into the existing refund/financial lifecycle rather than silently removing or invalidating the payment.
- If no payment exists, `Batalkan Transaksi` may neutralize the current transaction effects through the existing primitives.
- External-purchase transaction data that has already become committed transaction data remains governed by the existing refund/external-purchase lifecycle rather than scratch cancellation semantics.
- Wrong price or ordinary correction remains revision/versioning, not cancellation.

### 2. Cancellation is a business-state abstraction, not a sensor log of every physical hand movement

Owner analogy: a person may touch/take a product and hand it back before the transaction is finally cancelled. The system must not create dozens of stock movements merely because a customer and cashier physically pass an item back and forth.

Contract direction:

- record canonical business effects, not every transient physical handling event;
- if the committed transaction created an inventory issue and the cancellation returns that transaction effect to zero, use the existing compensating/reversal primitive once;
- do not model temporary hand-to-hand possession as repeated sale/return ledger events unless the business itself commits a distinct inventory event;
- cancellation history records the transaction/cancellation reason and canonical compensating effects, not CCTV-like micro-events.

### 3. Cancellation means zero active customer-transaction effect, not erased database history

Working invariant for pure cancellation:

- active revenue effect = 0
- active receivable effect = 0
- active profit effect = 0
- active transaction effect = 0
- transaction-owned inventory effect is neutralized through the proper compensating primitive
- transaction/cancellation history remains reconstructable
- cancellation reporting remains available

If a real independent stock/material loss remains after cancellation, that loss must be represented by the existing stock/cost adjustment primitive rather than by keeping the cancelled sale active.

### 4. Root cancellation lifecycle marker is accepted in principle

Owner accepts a root-level cancelled lifecycle marker whose meaning is only:

> this note root is no longer an active customer business transaction.

The exact schema spelling/metadata still requires source audit, but cancellation must not collapse financial, inventory, operational, and revision truth into one status flag. Each primitive remains authoritative for its own effect.

### 5. Recorded payment is accountable financial history and uses refund semantics

Latest owner direction supersedes the earlier draft assumption that a dummy/erroneous recorded payment necessarily requires a separate `payment_reversal` primitive.

Current owner direction:

- if no payment was ever recorded, a pure invalid/dummy note may remain a cancellation case;
- if a payment was already recorded, even when physical money is later claimed to be absent, the record remains accountable;
- the financial consequence goes through the refund lifecycle;
- backend may retain its existing `refund_due` / `refund_paid` primitives, but normal cashier UI does not need to expose those ledger steps as separate user actions;
- cashier-facing semantics remain simply that the money is returned/refunded;
- `refund_paid` must still mean actual money-out according to the existing financial contract.

### 6. Wrong price / ordinary correction remains revision/versioning

A wrong price is not automatically cancellation. Use the existing immutable revision/versioning model when the transaction remains a real transaction whose current truth is being corrected.

### 7. Inventory/refund/package behavior must reuse existing primitives

Cancellation/refund must reuse existing inventory reversal/adjustment and refund/package machinery rather than creating a second stock/refund engine.

Before designing new product/package refund behavior, audit the current implementation because the owner expects the existing refund flows may already express the needed return/no-return choices.

### 8. Access should reuse the existing actor/date mental model

Do not invent a cancellation-specific role hierarchy before proving the existing access policy is insufficient.

Current source/docs direction:

- cashier performs normal transaction work within the cashier date window (today/yesterday);
- admin has broader read scope;
- transaction-sensitive admin mutation remains subject to transaction capability and domain policy;
- paid/closed/refunded state is not itself a blanket prohibition on official audited lifecycle flows.

### 9. Reuse existing atomicity/concurrency/idempotency patterns

Cancellation must follow the existing transaction mental model rather than introducing a parallel mechanism:

- canonical root locking/serialization;
- atomic business effects and durable audit capture where required;
- same idempotency key + same semantic payload => replay;
- same key + changed semantic payload => conflict;
- no duplicate stock/refund/report effects.

### 10. Revision mental model: current accepted revision is authoritative, history remains

The owner's "colokan" mental model is retained with one existing contract correction:

- the latest accepted current revision drives current transaction truth;
- older revisions remain historical and must not affect current projection merely because they still exist;
- a stale editor based on an older revision must not silently overwrite a newer accepted revision;
- handoff0027 already locks `base_revision_id` / stale-revision rejection before a newer revision can be accepted.

Payments/refunds/inventory remain immutable ledgers and are interpreted against the current revision; they are not physically moved into the latest revision.

### 11. Build Detail primitives first; Auto is a later compression learned from shop habit

Do not invent Auto presets during the initial cancellation build.

Owner direction:

- first build the complete Detail path using explicit primitive actions;
- observe real cashier usage;
- if cashiers repeatedly perform the same 4–5 backend/domain actions and complain that the flow is too slow, then introduce an Auto shortcut for that proven shop habit;
- one Auto click may later compress several already-valid primitive actions;
- Auto is therefore derived from observed operational repetition, not guessed during architecture design.

### 12. Draft discard remains distinct from persisted transaction cancellation

- unsaved workspace/draft => discard/delete scratch data;
- successfully committed note root => use official edit/refund/cancellation lifecycle, not physical deletion.

### 13. Restore is a new accepted revision, not undelete

Owner direction:

- UI may say `Pulihkan`;
- backend semantics are not `cancelled = false` and not Laravel soft-delete restore;
- restoring a cancelled transaction creates a new accepted revision/version derived from the prior transaction truth;
- the cancellation remains historical;
- the new revision becomes current truth through the existing version graph and must create/recreate only the effects required by that new current state.

### 14. Soft delete is not selected as the normal note-cancellation mechanism

Do not add Laravel-style soft delete to `notes` merely to implement `Batalkan Transaksi`. A note is a transaction graph with payment/refund/inventory/revision/report/audit consequences; hiding only the root row would not neutralize those consequences.

## SUPERSEDED / CONFLICTING EARLIER DIRECTION

The earlier cancellation draft contained this candidate direction:

- a recorded payment that was not real-world cash should use payment reversal/correction rather than refund.

Latest owner decision supersedes that candidate at the business-policy level:

- once a payment has been recorded, it remains accountable and money handling belongs to the refund/financial lifecycle.

Do not delete the historical discussion. The eventual ADR must explain the supersession.

The earlier interpretation that Auto should be designed up front as a safe-default cancellation/refund mode is also superseded:

- Detail/primitive-complete behavior comes first;
- Auto is introduced only after repeated real cashier behavior establishes which multi-step sequence should be compressed.

## CONTRACT GAPS STILL OPEN

The remaining work is now primarily source audit and contract mapping rather than broad owner-business ambiguity:

1. Build the exact cancellation/refund eligibility matrix from current source:
   - no recorded payment generally permits cancellation;
   - recorded payment routes to refund;
   - external-purchase committed data follows its existing transaction/refund lifecycle;
   - verify package/service/product edge behavior against current implementation before adding exceptions.
2. Audit the exact root lifecycle representation:
   - confirm how `cancelled` should coexist with current operational open/close state;
   - determine exact field/event metadata without making the status flag authoritative for money or stock.
3. Audit existing product/package refund and stock-return/no-return behavior before changing it.
4. Map every current transaction effect to its canonical primitive for cancellation, including inventory, costing/COGS, projection, reporting, audit/outbox, package and external purchase.
5. Define cancellation report/UI presentation by reusing the closest existing transaction-history/report UX pattern; create a new pattern only where none exists.
6. Confirm existing actor/date access rules are sufficient for cancellation; ADR-0019 remains marked draft and should not be silently promoted.
7. Define exact cancellation event/source naming for audit/history.
8. Define restore-as-next-revision details against ADR-0045: base revision, new revision identity, stock reissue if needed, payment/refund carry-forward rules, and stale protection.

## NEXT SAFE STEP

Use current source to resolve the remaining mapping questions above.

After those source-backed contracts are explicit:

1. preserve any new owner statement verbatim when it changes business semantics;
2. promote permanent decisions to the appropriate ADR;
3. update/supersede the cancellation section of the relevant active blueprint;
4. only then prepare the implementation prompt/campaign.

Do not implement production cancellation code from this handoff alone.
