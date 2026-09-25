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

## NORMALIZED OWNER DECISION SNAPSHOT

These are working interpretations, not a replacement for the raw statements above.

### 1. Refund and cancellation remain distinct user/domain intents

- Keep both `Refund` and `Batalkan Transaksi`.
- A cancellation attempt must not silently reinterpret genuine realized transaction effects as pure cancellation.
- If the current transaction has effects that belong to the refund lifecycle, the cashier should be directed into the existing refund workflow rather than creating a second cancellation finance engine.
- Pure cancellation is for a transaction whose active business effect is considered invalid/semu/salah and should contribute zero to active business truth while preserving the history that the record existed.

### 2. Cancellation means zero active business effect, not erased database history

Working invariant for pure cancellation:

- active revenue effect = 0
- active receivable effect = 0
- active profit effect = 0
- active inventory effect = 0
- active transaction effect = 0
- transaction/cancellation history remains reconstructable
- cancellation reporting remains available

The exact persistence representation is still open.

### 3. Recorded payment is accountable financial history

Latest owner direction supersedes the earlier draft assumption that a dummy/erroneous recorded payment necessarily requires a separate `payment_reversal` primitive.

Current owner direction:

- if no payment was ever recorded, a pure invalid/dummy note may remain a cancellation case;
- if a payment was already recorded, even when physical money is later claimed to be absent, the record remains accountable and the money consequence must go through the refund/financial lifecycle rather than deleting or silently invalidating the payment row.

This decision must be reconciled carefully with cash-ledger semantics before ADR promotion.

### 4. Wrong price / ordinary correction remains revision/versioning

A wrong price is not automatically cancellation. Use the existing immutable revision/versioning model when the transaction remains a real transaction whose current truth is being corrected.

### 5. Inventory must reuse existing primitives

Cancellation/refund must reuse existing inventory reversal/adjustment machinery rather than creating a second stock engine. Pure cancellation should remove active stock consequence through explicit compensating history, not by deleting movement history.

### 6. Access should reuse the existing actor/date mental model

Do not invent a cancellation-specific role hierarchy before proving the existing access policy is insufficient.

Current source/docs direction:

- cashier performs normal transaction work within the cashier date window (today/yesterday);
- admin has broader read scope;
- transaction-sensitive admin mutation remains subject to transaction capability and domain policy;
- paid/closed/refunded state is not itself a blanket prohibition on official audited lifecycle flows.

### 7. External-purchase data already persisted as transaction data belongs to transaction/refund lifecycle

Do not treat an external-purchase line as scratch data merely because payment is still full debt. Once it is committed as transaction data, cancellation/refund decisions must respect the existing external-purchase transaction semantics.

### 8. Reuse existing atomicity/concurrency/idempotency patterns

Cancellation must follow the existing transaction mental model rather than introducing a parallel mechanism:

- canonical root locking/serialization;
- atomic business effects and durable audit capture where required;
- same idempotency key + same semantic payload => replay;
- same key + changed semantic payload => conflict;
- no duplicate stock/refund/report effects.

### 9. Revision mental model: current accepted revision is authoritative, history remains

The owner's "colokan" mental model is retained with one existing contract correction:

- the latest accepted current revision drives current transaction truth;
- older revisions remain historical and must not affect current projection merely because they still exist;
- however, a stale editor based on an older revision must not silently overwrite a newer accepted revision;
- handoff0027 already locks `base_revision_id` / stale-revision rejection before a newer revision can be accepted.

Payments/refunds/inventory remain immutable ledgers and are interpreted against the current revision; they are not physically moved into the latest revision.

### 10. Simple/Auto and Detail remain presentation modes over the same engine

- Simple/Auto may compress the explanation and choose safe defaults only from proven facts.
- Detail may expose the effect plan and require explicit operator choices where needed.
- Neither mode owns separate finance/inventory logic.

### 11. Draft discard remains distinct from persisted transaction cancellation

- unsaved workspace/draft => discard/delete scratch data;
- successfully committed note root => use official edit/refund/cancellation lifecycle, not physical deletion.

### 12. Soft delete is not selected as the normal note-cancellation mechanism

Do not add Laravel-style soft delete to `notes` merely to implement `Batalkan Transaksi`. A note is a transaction graph with payment/refund/inventory/revision/report/audit consequences; hiding only the root row would not neutralize those consequences.

## SUPERSEDED / CONFLICTING EARLIER DIRECTION

The earlier cancellation draft contained this candidate direction:

- a recorded payment that was not real-world cash should use payment reversal/correction rather than refund.

Latest owner decision supersedes that candidate at the business-policy level:

- once a payment has been recorded, it remains accountable and money handling belongs to the refund/financial lifecycle.

Do not delete the historical discussion. The eventual ADR must explain the supersession and define the resulting cash/report semantics precisely.

## CONTRACT GAPS STILL OPEN

Do not promote this handoff into a final implementation contract until these are resolved:

1. Exact criterion that separates a valid pure `Batalkan Transaksi` from a transaction that must be redirected to Refund.
2. Exact root lifecycle representation for pure cancellation:
   - whether `note_state = cancelled` is canonical;
   - required metadata/event fields;
   - separation from operational open/close state.
3. Exact financial/report interpretation when a payment row exists but physical cash is claimed missing, given the owner's decision to keep it accountable through refund.
4. Exact current-report treatment versus cancellation/history report fields and counts.
5. Exact cancellation impact-plan UI contract for Simple/Auto versus Detail.
6. Whether any existing access rule needs an owner-approved amendment before cancellation uses it; ADR-0019 is still marked draft.
7. Exact effect-graph mapping for all current transaction components before implementation, including package/external-purchase/costing/projection/audit references.
8. Exact cancellation event naming and source identity needed for audit/history.
9. Whether a cancelled root is ever restorable. No normal `restore()` behavior is authorized by this handoff.

## NEXT SAFE STEP

Discuss and resolve the remaining contract gaps with the owner.

After owner decisions are explicit:

1. preserve new raw owner statements in this handoff or its successor;
2. promote permanent decisions to the appropriate ADR;
3. update/supersede the cancellation section of the relevant active blueprint;
4. only then prepare the implementation prompt/campaign.

Do not implement production cancellation code from this handoff alone.
