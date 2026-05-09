# Error Log Remediation Sequence

- Status: Planning sequence.
- Scope: urutan perbaikan dan verifikasi seluruh docs/error_log/.
- Non-goal: dokumen ini bukan patch source, bukan commit, dan bukan klaim semua issue selesai.
## Prinsip Sequence

- Error log adalah satu rangkaian.
- Aturan eksekusi:
- Satu active slice saja.
- Tidak boleh pindah slice sebelum proof slice aktif lengkap.
- Jangan urut berdasarkan nomor file.
- Urut berdasarkan dependency, source boundary, dan domain impact.
- Issue fixed tetapi proof lemah masuk verification slice.
- Jika source bertentangan dengan docs, source dan test proof menang.
- Jika ADR terbaru bertentangan dengan ADR lama, ADR terbaru menang kecuali dokumen yang lebih spesifik memberi aturan lebih tepat.
- Seeder berada di luar workflow utama dan hanya menjadi future scope.
## Legend

- Trust status:
- trusted: proof dokumen/source/test cukup untuk scope yang diklaim
- weak: patch atau klaim ada, tetapi proof kurang atau gap besar
- contradicted: ada konflik antar dokumen/source/test
- unknown: belum cukup dibaca
- Affected layer:
- domain
- application
- infrastructure
- HTTP/controller
- Blade
- JS
- security
- docs
## Global Repair Order

### Slice 0 - Baseline Intake dan Source Reality

- Tujuan:
- inventaris 29 error log
- source priority dipakai
- trust status awal dibuat
- dependency graph dibuat
- tidak ada patch
- Alasan urutan:
- tanpa baseline, setiap patch berikutnya mudah menjadi ritual memindahkan bug dari satu rak ke rak lain, kegiatan favorit software yang tidak diawasi.
- Stop gate sebelum pindah:
- seluruh docs/error_log/ terdaftar
- issue weak/contradicted ditandai
- conflict #001/#003 dan #021/#022 dicatat
- active slice pertama dipilih
- no source changes
### Slice 1 - Current vs Historical Operational Row Foundation

- Issues:
- docs/error_log/004-refunded-work-items-survive-revisions-and-inflate-stock.md
- docs/error_log/012-canceled-note-rows-re-enter-payment-flows.md
- Alasan urutan:
- current vs historical row boundary menentukan payment selection, refund selection, inventory reversal, workspace projection, dan reporting.
- Jika row boundary salah, settlement/refund tests bisa hijau pada data yang salah.
- Boleh digabung:
- analisis current operational rows
- payment/refund row visibility
- historical anchor preservation
- Harus dipisah:
- reporting rewrite
- schema projection baru
- DB uniqueness inventory reversal
- seeder
- Stop gate:
- current rows dan historical rows punya source boundary
- canceled/refunded/superseded rows tidak masuk payable/refundable path tanpa policy
- inventory reversal idempotency proof tidak rusak
#### Issue Card 004

- error_log path: docs/error_log/004-refunded-work-items-survive-revisions-and-inflate-stock.md
- status dokumen saat ini: Fixed with proof
- status kepercayaan: trusted untuk Note/Payment/current operational flow; weak untuk reporting direct work_items
- root cause sementara: refunded historical work_items tetap attached sebagai active row dan duplicate inventory reversal bisa inflate stock
- affected layer: domain, application, infrastructure, HTTP/controller, security, docs
- dependency upstream: current/historical row model, revision lifecycle
- dependency downstream: #012, #013, #014, #017, reporting audit
- RED proof yang dibutuhkan: re-run atau pertahankan test stale refunded historical row tidak muncul sebagai current operational row dan duplicate reversal tidak terjadi
- minimal patch boundary: current projection/workspace row source dan reversal idempotency only
- focused test target: RevisionAfterRefundPreservesHistoricalWorkItemsFeatureTest, ReverseIssuedInventoryOperationFeatureTest
- wider regression target: tests/Feature/Note, tests/Feature/Payment, inventory focused tests
- closure proof: targeted + Note/Payment proof, residual reporting gap dicatat
- handoff note template: "004 current/historical row boundary: source=<paths>, red=<command>, green=<command>, wider=<command>, residual_reporting=<yes/no>"
#### Issue Card 012

- error_log path: docs/error_log/012-canceled-note-rows-re-enter-payment-flows.md
- status dokumen saat ini: Patched, with verification gap and residual audit note
- status kepercayaan: weak
- root cause sementara: canceled rows masuk note->workItems() tetapi payment/status consumers masih menganggap semua rows active
- affected layer: domain, application, payment, status correction, security, docs
- dependency upstream: #004 current/historical row boundary
- dependency downstream: #013, #014, #021 refund eligibility, payment selection
- RED proof yang dibutuhkan: canceled selected row rejected, canceled cannot transition to done, full-note/no-selected flow does not allocate canceled row
- minimal patch boundary: payable component resolver, status transition service, billing projection if needed
- focused test target: ResolveNotePayableComponentsTest, WorkItemStatusTransitionServiceTest, selected-row HTTP payment test
- wider regression target: tests/Feature/Note, tests/Feature/Payment
- closure proof: focused unit/feature tests pass and residual fromNote() audit closed or explicitly deferred
- handoff note template: "012 canceled rows: selected=<proof>, full_note=<proof>, status_transition=<proof>, billing_projection=<proof>, residual=<gap>"
### Slice 2 - Settlement and Payment Basis Foundation

- Issues:
- docs/error_log/001-refunds-counted-as-paid-in-note-totals.md
- docs/error_log/003-refunded-revised-notes-are-misclassified-as-underpaid.md
- docs/error_log/005-note-revision-silently-drops-overpaid-allocations.md
- docs/error_log/008-legacy-paid-notes-can-be-paid-again.md
- docs/error_log/017-workspace-edit-payments-ignore-existing-note-payments.md
- Alasan urutan:
- payment/refund arithmetic adalah foundation untuk paid status, outstanding, editability, refund eligibility, and concurrency.
- #001 dan #003 punya conflict settlement semantics, jadi harus diverifikasi bersama.
- #008 dan #017 memastikan legacy/component/existing payment basis tidak membuat double payment.
- #005 memastikan downward revision tidak menyembunyikan overpaid allocation.
- Boleh digabung:
- verification matrix settlement basis
- legacy/component allocation compatibility
- inline payment outstanding
- carry-forward current refund semantics
- Harus dipisah:
- explicit customer credit/overpaid product feature
- reporting rewrite
- seeder
- true parallel concurrency stress, yang masuk Slice 3
- Stop gate:
- active refund normal note dan revised historical refund sama-sama pass
- legacy/component mixed allocation tidak overpay
- inline pay_full memakai outstanding, bukan total penuh
- downward overpaid revision reject+rollback atau explicit domain model tersedia
#### Issue Card 001

- error_log path: docs/error_log/001-refunds-counted-as-paid-in-note-totals.md
- status dokumen saat ini: Patched
- status kepercayaan: contradicted
- root cause sementara: active refund ikut dihitung allocated lalu dikurangi lagi sehingga refund netral; later #003 mengubah settlement basis
- affected layer: application, infrastructure, domain, payment, docs
- dependency upstream: #003 current-refund semantics
- dependency downstream: #003, #008, #017, #026
- RED proof yang dibutuhkan: active refund normal note total 50.000, payment 50.000, refund 10.000 menghasilkan net paid 40.000 dan outstanding 10.000
- minimal patch boundary: paid status/refund reader semantics, bukan revert reader generik tanpa consumer proof
- focused test target: NotePaidStatusPolicyTest, payment allocation reader tests
- wider regression target: tests/Feature/Note, tests/Feature/Payment
- closure proof: active refund and revised historical refund both pass
- handoff note template: "001 conflict with 003: active_refund=<proof>, revised_historical=<proof>, selected_patch_boundary=<path>"
#### Issue Card 003

- error_log path: docs/error_log/003-refunded-revised-notes-are-misclassified-as-underpaid.md
- status dokumen saat ini: Fixed with proof
- status kepercayaan: trusted
- root cause sementara: historical refund after revision was subtracted again from carry-forward settlement
- affected layer: domain, application, infrastructure, payment, docs
- dependency upstream: #001 conflict
- dependency downstream: #005, #008, #017
- RED proof yang dibutuhkan: carry-forward settlement 200.000 with historical refund ledger 100.000 remains paid for revised total 200.000
- minimal patch boundary: current-refund settlement boundary in paid status policy/refund reader
- focused test target: NotePaidStatusPolicyTest
- wider regression target: Note + Payment feature suites
- closure proof: RED before patch, targeted 4 passed, relevant blast radius, Note+Payment proof
- handoff note template: "003 current refund boundary: source=<paths>, red=<output>, green=<output>, regression_001=<output>"
#### Issue Card 005

- error_log path: docs/error_log/005-note-revision-silently-drops-overpaid-allocations.md
- status dokumen saat ini: Fixed and verified
- status kepercayaan: trusted
- root cause sementara: revision payment replay silently capped old allocation and hid overpaid excess
- affected layer: domain, application, payment, docs
- dependency upstream: #003 carry-forward settlement, #004 current rows
- dependency downstream: explicit overpaid/customer credit future model
- RED proof yang dibutuhkan: downward revision old paid amount greater than revised payable rejects instead of silently capping
- minimal patch boundary: NoteReplacementPaymentAllocationReconciler::rebuild() and tests; no customer-credit feature yet
- focused test target: NoteReplacementOverpaidAllocationReplayFeatureTest
- wider regression target: product/service-store-stock replacement finance tests, Note + Payment
- closure proof: reject+rollback behavior proven, original allocations remain, Note+Payment pass
- handoff note template: "005 overpaid replay: behavior=reject_rollback, tests=<commands>, future_overpaid_model=<deferred>"
#### Issue Card 008

- error_log path: docs/error_log/008-legacy-paid-notes-can-be-paid-again.md
- status dokumen saat ini: Patched and locally verified for backend payment allocation/projection scope
- status kepercayaan: trusted untuk backend scope
- root cause sementara: selected-row payment ignored legacy allocation and later mixed legacy/component totals
- affected layer: application, infrastructure, payment, UI data, docs
- dependency upstream: #001/#003 settlement basis
- dependency downstream: #017, #026, reporting/migration
- RED proof yang dibutuhkan: legacy paid note rejected; mixed legacy 40.000 + component 10.000 on total 100.000 only allows 50.000
- minimal patch boundary: compatibility allocated total reader and row settlement projectors
- focused test target: RecordNotePaymentHttpFeatureTest, NoteOperationalRowSettlementProjectorTest
- wider regression target: tests/Feature/Payment, tests/Feature/Note
- closure proof: targeted mixed allocation pass, focused pass, Note+Payment pass; migration double-count risk documented
- handoff note template: "008 compatibility allocation: legacy=<proof>, mixed=<proof>, migration_risk=<noted>"
#### Issue Card 017

- error_log path: docs/error_log/017-workspace-edit-payments-ignore-existing-note-payments.md
- status dokumen saat ini: Fixed and verified
- status kepercayaan: trusted
- root cause sementara: inline payment pay_full and policy ignored existing allocated total
- affected layer: application, payment, workspace, UI data, docs
- dependency upstream: #008 compatibility allocation
- dependency downstream: #026 concurrency, selected-row projection
- RED proof yang dibutuhkan: existing legacy 40.000 on total 100.000 makes pay_full record 60.000, not 100.000
- minimal patch boundary: inline payment amount resolver and recorder use PaymentAllocationReaderPort
- focused test target: CreateTransactionWorkspaceInlinePaymentRecorderFeatureTest
- wider regression target: selected-row payment tests, Note + Payment
- closure proof: targeted 1 passed, focused selected-row + 017 pass, Note+Payment pass
- handoff note template: "017 inline payment: outstanding=<proof>, selected_row_regression=<proof>, wider=<proof>"
### Slice 3 - Payment and Revision Concurrency Serialization

- Issues:
- docs/error_log/010-revision-reallocation-can-lose-concurrent-payments.md
- docs/error_log/026-concurrent-note-payments-can-over-allocate-balances.md
- Alasan urutan:
- concurrency harus mengunci invariant settlement yang sudah benar dari Slice 2.
- Lock atas math yang salah hanya membuat bug berjalan antre, bukan hilang. Dunia tidak butuh race condition yang lebih sopan.
- Boleh digabung:
- same-note row lock protocol
- transaction boundary verification
- source anchor verification
- Harus dipisah:
- idempotency token feature
- database-specific stress tests jika environment belum siap
- overpaid/customer credit model
- Stop gate:
- payment path and revision path share lock protocol
- lock read terjadi sebelum allocated-total read/write/capture/delete/rebuild
- targeted/focused tests pass
- true concurrency stress gap dicatat jika belum dilakukan
#### Issue Card 010

- error_log path: docs/error_log/010-revision-reallocation-can-lose-concurrent-payments.md
- status dokumen saat ini: Fixed and locally verified for minimum revision/payment same-note serialization control
- status kepercayaan: trusted untuk source-level serialization; weak untuk true parallel stress
- root cause sementara: revision capture/delete/rebuild could delete concurrent payment allocation
- affected layer: application, infrastructure, payment, revision, security, docs
- dependency upstream: #005, #008, #026 lock support
- dependency downstream: #011 CreateNoteRevisionHandler merge safety
- RED proof yang dibutuhkan: source/test proof that payment inserted between capture and delete is protected by same note lock
- minimal patch boundary: CreateNoteRevisionHandler uses getByIdForUpdate() inside transaction
- focused test target: update/revision/payment focused tests
- wider regression target: Note + Payment
- closure proof: lock anchors + focused pass + Note/Payment pass; true parallel gap documented
- handoff note template: "010 revision/payment lock: root_lock=<path>, payment_lock=<path>, stress_gap=<yes/no>"
#### Issue Card 026

- error_log path: docs/error_log/026-concurrent-note-payments-can-over-allocate-balances.md
- status dokumen saat ini: Fixed and locally verified for minimum note-level payment serialization control
- status kepercayaan: trusted untuk minimum lock; weak untuk true parallel stress
- root cause sementara: concurrent payment requests read stale allocated total then both committed
- affected layer: application, infrastructure, payment, concurrency, docs
- dependency upstream: #008/#017 allocated total correctness
- dependency downstream: #010 revision/payment serialization
- RED proof yang dibutuhkan: stale concurrent payment over-allocation characterized or source lock proof accepted
- minimal patch boundary: NoteReaderPort::getByIdForUpdate(), adapter lockForUpdate(), operation lock before allocated-total read
- focused test target: RecordAndAllocateNotePaymentFeatureTest, AutoClosePaidNoteOnFullPaymentFeatureTest, RecordNotePaymentHttpFeatureTest
- wider regression target: Note + Payment
- closure proof: source anchors, focused pass, Note+Payment pass, concurrency stress gap visible
- handoff note template: "026 payment lock: getByIdForUpdate=<proof>, transaction_boundary=<proof>, stress=<done/deferred>"
### Slice 4 - Access, Capability, Date Window, and Disclosure Boundary

- Issues:
- docs/error_log/009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md
- docs/error_log/011-cashier-revision-path-mutates-settled-note-state.md
- docs/error_log/016-unauthenticated-admin-capability-toggle-endpoints.md
- docs/error_log/019-cashiers-can-list-historical-closed-notes-by-date.md
- docs/error_log/020-admin-note-actions-bypass-transaction-capability.md
- docs/error_log/027-admin-invoice-creation-bypasses-transaction-entry-gate.md
- docs/error_log/029-cashier-create-page-leaks-total-note-count.md
- Alasan urutan:
- setelah settlement/status foundation cukup, access boundary bisa memakai status yang benar.
- route/capability/date-window harus kuat sebelum refund/procurement/output closure final.
- Boleh digabung:
- ADR-0019 route matrix verification
- transaction capability route-list proof
- cashier date-window proof
- global count disclosure proof
- Harus dipisah:
- UI-only edit button from #015
- storage/attachment MIME from #028
- seeder credential #002
- Stop gate:
- direct unauthorized mutation rejected
- admin read route tetap boleh tanpa transaction capability
- admin mutation route butuh transaction capability
- cashier out-of-window returns 403
- capability toggle audited and not client-performer controlled
- cashier global count removed or scoped
#### Issue Card 009

- error_log path: docs/error_log/009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md
- status dokumen saat ini: Fixed with proof
- status kepercayaan: trusted
- root cause sementara: cashier workspace PATCH route classified as view-only access
- affected layer: HTTP/controller, application, security, docs
- dependency upstream: ADR-0019 access boundary
- dependency downstream: #011, #018, #020
- RED proof yang dibutuhkan: closed note PATCH expected 403 but previously got 302
- minimal patch boundary: EnsureCashierNoteAccess route classification
- focused test target: CashierClosedNoteWorkspaceReplacementSubmitFeatureTest
- wider regression target: cashier workspace/revision focused tests
- closure proof: RED/GREEN/focused proof and no mutation assertion
- handoff note template: "009 cashier workspace guard: route=<name>, red=<output>, green=<output>, no_mutation=<proof>"
#### Issue Card 011

- error_log path: docs/error_log/011-cashier-revision-path-mutates-settled-note-state.md
- status dokumen saat ini: Fixed with proof plus route-scoped admin compatibility follow-up
- status kepercayaan: trusted
- root cause sementara: cashier revision missed editability guard; later admin correction required route-scoped guard
- affected layer: application, HTTP/controller, security, domain, docs
- dependency upstream: #009, #010 lock
- dependency downstream: #018, admin correction/revision workflow
- RED proof yang dibutuhkan: cashier cannot mutate open-but-settled note; admin official revision remains allowed where policy allows
- minimal patch boundary: CreateNoteRevisionHandler / workflow guard flag without losing getByIdForUpdate()
- focused test target: CashierNoteRevisionSubmitFeatureTest, EditableWorkspaceNoteGuardFeatureTest
- wider regression target: route-scoped admin/cashier revision tests and full verification when available
- closure proof: cashier guard proof, admin compatibility proof, #010 lock preserved
- handoff note template: "011 editability: cashier_guard=<proof>, admin_route_scope=<proof>, lock_preserved=<proof>"
#### Issue Card 016

- error_log path: docs/error_log/016-unauthenticated-admin-capability-toggle-endpoints.md
- status dokumen saat ini: Patched, with verification gap
- status kepercayaan: weak
- root cause sementara: capability toggle endpoint lacked auth/admin middleware and trusted performed_by_actor_id from client
- affected layer: HTTP/controller, application, security, audit, docs
- dependency upstream: ADR-0019 capability policy
- dependency downstream: #020, #027
- RED proof yang dibutuhkan: guest/kasir cannot toggle; admin can; spoofed performer ignored; audit uses authenticated actor
- minimal patch boundary: route middleware, request validation, controller performer derivation, audit test
- focused test target: identity access enable/disable feature tests
- wider regression target: route-list identity-access, access/security tests
- closure proof: unauth rejected, non-admin rejected, admin allowed, audit actor server-derived
- handoff note template: "016 capability toggle: guest=<proof>, kasir=<proof>, admin=<proof>, audit=<proof>"
#### Issue Card 019

- error_log path: docs/error_log/019-cashiers-can-list-historical-closed-notes-by-date.md
- status dokumen saat ini: Patched, with verification gap
- status kepercayaan: weak
- root cause sementara: cashier table trusted client date and used openOnly=false, leaking historical closed notes
- affected layer: HTTP/controller, infrastructure query, security, disclosure, docs
- dependency upstream: ADR-0019 cashier date-window
- dependency downstream: #022, #029
- RED proof yang dibutuhkan: arbitrary historical date does not return old closed note; today/yesterday still works
- minimal patch boundary: CashierNoteHistoryCriteria server date anchor and query scope
- focused test target: CashierNoteHistoryTableClosurePolicyFeatureTest
- wider regression target: cashier note table/history tests and route-list
- closure proof: server-side date window proof, direct table endpoint proof, no historical row leak
- handoff note template: "019 cashier table: historical_date=<proof>, current_window=<proof>, route_middleware=<proof>"
#### Issue Card 020

- error_log path: docs/error_log/020-admin-note-actions-bypass-transaction-capability.md
- status dokumen saat ini: Patched
- status kepercayaan: weak
- root cause sementara: admin note mutation routes lacked transaction-entry gate
- affected layer: routes, HTTP/controller, security, audit, docs
- dependency upstream: #016 capability state integrity
- dependency downstream: #027 procurement gate
- RED proof yang dibutuhkan: admin without transaction capability denied on refund/payment/rows/workspace update; admin read still allowed
- minimal patch boundary: route middleware group for admin mutation routes
- focused test target: admin note mutation capability feature tests
- wider regression target: route-list admin/notes, admin read/mutation tests
- closure proof: four mutation routes gated, read-only routes unaffected
- handoff note template: "020 admin note gate: routes=<proof>, inactive_admin_denied=<proof>, read_allowed=<proof>"
#### Issue Card 027

- error_log path: docs/error_log/027-admin-invoice-creation-bypasses-transaction-entry-gate.md
- status dokumen saat ini: Patched, with verification gap
- status kepercayaan: weak
- root cause sementara: supplier invoice creation route lacked transaction.entry
- affected layer: routes, HTTP/controller, procurement, security, inventory, docs
- dependency upstream: #016/#020 capability boundary
- dependency downstream: #028 proof attachment access and procurement hardening
- RED proof yang dibutuhkan: inactive admin cannot create supplier invoice; active admin can; read-only procurement unaffected
- minimal patch boundary: routes/web/admin_procurement.php route middleware
- focused test target: supplier invoice creation authorization feature test
- wider regression target: procurement feature tests and route-list
- closure proof: denied mutation creates no invoice/payment/receipt/stock movement
- handoff note template: "027 supplier invoice gate: inactive_denied=<proof>, active_allowed=<proof>, no_mutation=<proof>"
#### Issue Card 029

- error_log path: docs/error_log/029-cashier-create-page-leaks-total-note-count.md
- status dokumen saat ini: Fixed with proof
- status kepercayaan: trusted untuk create-page disclosure; weak untuk full global verify
- root cause sementara: cashier create default customer name used global countAll()+1
- affected layer: application, Blade, security, disclosure, docs
- dependency upstream: #019 cashier visibility boundary, ADR-0019, ADR-0020
- dependency downstream: final global verification
- RED proof yang dibutuhkan: page previously rendered Pelanggan no 2
- minimal patch boundary: CreateTransactionWorkspacePageDataBuilder default label to neutral text; no seeder
- focused test target: CreateTransactionWorkspaceDefaultCustomerNameFeatureTest
- wider regression target: create workspace feature tests
- closure proof: page shows Pelanggan baru, no global count leak, focused pass, global blocker documented
- handoff note template: "029 count disclosure: red=<proof>, green=<proof>, focused=<proof>, global_blocker=<proof>"
### Slice 5 - Refund Lifecycle, Parent Note Eligibility, Terminal State, and UI Entry

- Issues:
- docs/error_log/013-forged-row-refund-can-auto-finalize-unpaid-notes.md
- docs/error_log/014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md
- docs/error_log/021-refunds-can-be-recorded-on-open-notes.md
- docs/error_log/022-cashier-refund-route-bypasses-note-access-guard.md
- docs/error_log/018-refunded-notes-bypass-cashier-closed-note-guards.md
- docs/error_log/015-refunded-notes-expose-edit-workspace.md
- Alasan urutan:
- refund flow perlu settlement, current rows, and access boundary yang sudah stabil.
- #021 dan #022 punya conflict, jadi refund slice harus berhenti sampai source/test membuktikan policy final.
- Boleh digabung:
- selected-row refund eligibility
- parent note eligibility
- route access guard
- refunded terminal guard
- UI edit visibility verification
- Harus dipisah:
- product overpaid/customer credit feature
- public output XSS
- seeder
- unrelated admin procurement gate
- Stop gate:
- selected row unpaid/open rejected or policy explicitly allows with proof
- parent note open/closed policy resolved against source
- refund route passes cashier note access/date-window
- zero-allocation finalization cannot mark note refunded
- refunded terminal note cannot be mutated via cashier path
- UI edit button aligns but backend remains boundary
#### Issue Card 013

- error_log path: docs/error_log/013-forged-row-refund-can-auto-finalize-unpaid-notes.md
- status dokumen saat ini: Patched for auto-finalization, with verification gap and residual validation risk
- status kepercayaan: weak
- root cause sementara: zero-allocation selected-row refund could cancel rows and finalize unpaid note as refunded
- affected layer: application, payment/refund, domain, audit, docs
- dependency upstream: #012 row state
- dependency downstream: #014, #021, #018
- RED proof yang dibutuhkan: unpaid all-row selected refund does not create allocation and must not finalize note as refunded
- minimal patch boundary: finalizer gate behind recorded allocation plus resolver validation if required
- focused test target: selected rows refund transaction/controller feature tests
- wider regression target: refund lifecycle tests, Note + Payment
- closure proof: zero-allocation no finalization, legitimate allocation refund still finalizes correctly
- handoff note template: "013 refund finalizer: zero_alloc=<proof>, legitimate_refund=<proof>, audit=<proof>"
#### Issue Card 014

- error_log path: docs/error_log/014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md
- status dokumen saat ini: Patched, with verification gap
- status kepercayaan: weak
- root cause sementara: selected-row refund resolver accepted open/unpaid rows and cancelled them
- affected layer: application, refund, domain, security, docs
- dependency upstream: #013
- dependency downstream: #021, #022
- RED proof yang dibutuhkan: fully unpaid open row and partially paid operationally open row rejected, no cancellation, no note total change
- minimal patch boundary: SelectedNoteRowsRefundPlanResolver operationally close precondition
- focused test target: CashierRefundRejectsOpenLineFeatureTest, RecordSelectedRowsClosedNoteRefundHttpFeatureTest
- wider regression target: refund controller/lifecycle tests
- closure proof: invalid row rejected, valid current paid row still refundable
- handoff note template: "014 row eligibility: unpaid=<proof>, partial_open=<proof>, valid_close=<proof>"
#### Issue Card 021

- error_log path: docs/error_log/021-refunds-can-be-recorded-on-open-notes.md
- status dokumen saat ini: Patched, with verification gap
- status kepercayaan: contradicted
- root cause sementara: selected rows could be close while parent note remained open; whole-note close invariant unclear
- affected layer: HTTP/controller, application, refund, security, docs
- dependency upstream: #014 selected-row eligibility
- dependency downstream: #022 route guard and final refund policy
- RED proof yang dibutuhkan: source/test must decide whether open parent note refund is rejected or explicitly allowed
- minimal patch boundary: RecordClosedNoteRefundController whole-note eligibility only after conflict resolution
- focused test target: RecordSelectedRowsClosedNoteRefundHttpFeatureTest, RecordClosedNoteRefundControllerFeatureTest
- wider regression target: refund lifecycle tests
- closure proof: #021/#022 conflict resolved with source/test proof; docs updated with final policy
- handoff note template: "021 parent note refund conflict: source=<proof>, docs_conflict=<paths>, decision=<policy>, tests=<commands>"
#### Issue Card 022

- error_log path: docs/error_log/022-cashier-refund-route-bypasses-note-access-guard.md
- status dokumen saat ini: Fixed and locally verified for cashier refund route note-access enforcement
- status kepercayaan: trusted for route access/date-window; separate from #021 parent close policy
- root cause sementara: cashier refund route was outside EnsureCashierNoteAccess
- affected layer: routes, middleware, HTTP/controller, security, docs
- dependency upstream: #019 cashier date-window, #021 policy conflict
- dependency downstream: refund lifecycle closure
- RED proof yang dibutuhkan: historical note outside cashier access window expected 403 but previously got 302
- minimal patch boundary: move cashier refund route inside note access guard; classify as view access to allow intended closed-note refund
- focused test target: RecordClosedNoteRefundControllerFeatureTest
- wider regression target: refund lifecycle focused tests
- closure proof: historical denied, today/yesterday valid refund behavior preserved, route-list proof
- handoff note template: "022 refund route guard: historical=<proof>, valid_refund=<proof>, route_context=<proof>"
#### Issue Card 018

- error_log path: docs/error_log/018-refunded-notes-bypass-cashier-closed-note-guards.md
- status dokumen saat ini: Fixed with proof
- status kepercayaan: trusted
- root cause sementara: refunded state not treated terminal by cashier mutation guard and addability policy
- affected layer: application, security, domain, HTTP/controller, docs
- dependency upstream: #013/#014 refund lifecycle
- dependency downstream: #015 UI edit visibility
- RED proof yang dibutuhkan: cashier POST rows to refunded note expected 403; AddWorkItemHandler rejects refunded note
- minimal patch boundary: CashierNoteAccessGuard, NoteAddabilityPolicy, error classifier
- focused test target: CashierProtectedNoteRoutesAccessGuardFeatureTest, AddWorkItemToPaidNoteFeatureTest
- wider regression target: refunded note detail/editability/refund lifecycle focused tests
- closure proof: targeted 8 pass, focused 22 pass, no mutation proof
- handoff note template: "018 refunded terminal: route_guard=<proof>, addability=<proof>, focused=<proof>"
#### Issue Card 015

- error_log path: docs/error_log/015-refunded-notes-expose-edit-workspace.md
- status dokumen saat ini: Patched, with server-side authorization verification gap
- status kepercayaan: weak
- root cause sementara: shared note detail rendered Edit button without can_edit_workspace
- affected layer: Blade, HTTP/controller, security, docs
- dependency upstream: #018 server-side terminal guard, #011 editability
- dependency downstream: UI closure
- RED proof yang dibutuhkan: refunded note detail does not render Edit link; direct GET/PATCH still rejected server-side
- minimal patch boundary: Blade conditional plus server-side guard verification; no UI-only closure
- focused test target: refunded note detail view feature test and direct route tests
- wider regression target: cashier/admin note detail/workspace tests
- closure proof: UI link absent, editable open note still shows edit, direct mutation rejected
- handoff note template: "015 UI edit exposure: blade=<proof>, direct_get=<proof>, direct_patch=<proof>"
### Slice 6 - Server-Side Price Basis Authority

- Issue:
- docs/error_log/006-client-controlled-price-basis-bypasses-minimum-price-checks.md
- Alasan urutan:
- price basis is a financial invariant in workspace revision, but it is isolated enough to run after settlement/access foundations.
- Native JS and hidden fields must be reviewed, but final authority is server-side.
- Boleh digabung:
- server-side trusted revision snapshot marker
- mapper/builder/apply path consistency
- UI/JS hidden-field review
- Harus dipisah:
- stored XSS workspace JSON from #007
- broad product pricing redesign
- overpaid/customer credit feature
- Stop gate:
- forged price_basis=revision_snapshot rejected
- trusted historical snapshot still allowed when server proves it
- rejected mutation rolls back revision, inventory, and payment side effects
- native JS/Blade hidden field is not authority
#### Issue Card 006

- error_log path: docs/error_log/006-client-controlled-price-basis-bypasses-minimum-price-checks.md
- status dokumen saat ini: Fixed with proof
- status kepercayaan: trusted for technical source/test scope; weak for docs commit/global/browser closure
- root cause sementara: client-controlled price_basis bypassed minimum selling price policy
- affected layer: HTTP/controller, application, domain, Blade, JS, security, docs
- dependency upstream: #005 revision payment replay, #004 row/current projection
- dependency downstream: #007 workspace output review, #009 workspace mutation guard
- RED proof yang dibutuhkan: forged underpriced revision_snapshot rejected; trusted server snapshot allowed
- minimal patch boundary: server-side trust marker through mapper, builder, handler, apply persistence, and WorkItemFactory
- focused test target: WorkItemFactoryTest, RevisionSnapshotStoreStockLineTrustMarkerTest, CreateNoteRevisionPayloadNoteBuilderTest, product replacement finance tests
- wider regression target: Note + Payment
- closure proof: targeted unit/feature + Note/Payment pass, no temp debug marker, docs closure proof
- handoff note template: "006 price basis: forged=<proof>, trusted_snapshot=<proof>, rollback=<proof>, js_authority=<not_client>"
### Slice 7 - Output Context, Blade, Native JS, and Unsafe URL

- Issues:
- docs/error_log/007-admin-note-edit-page-exposes-stored-xss.md
- docs/error_log/024-reflected-xss-in-expense-create-json-config.md
- docs/error_log/025-reflected-javascript-url-in-product-return-link.md
- Alasan urutan:
- after finance/access boundaries, public surface output must be verified across Blade and JS contexts.
- #007 and #024 share JSON-in-script hazard; #025 is URL context hazard.
- Boleh digabung:
- XSS payload matrix
- Blade JSON script sink audit
- safe return URL policy
- native JS config parsing review
- Harus dipisah:
- proof attachment MIME from #028
- global count disclosure already in #029
- server-side price basis #006
- Stop gate:
- no raw </script> breakout in rendered responses
- all JSON script config sinks use safe encoding
- unsafe javascript: return URL not rendered active
- native JS does not turn escaped payload into HTML/script
#### Issue Card 007

- error_log path: docs/error_log/007-admin-note-edit-page-exposes-stored-xss.md
- status dokumen saat ini: Patched, with verification gap
- status kepercayaan: weak
- root cause sementara: workspace config JSON rendered into script context without HTML-safe JSON encoding; multiple stored data sources reach same sink
- affected layer: Blade, JS, HTTP/controller, security, docs
- dependency upstream: #006 workspace input trust boundary
- dependency downstream: #024, #025, ADR-0020 output audit
- RED proof yang dibutuhkan: stored note/customer/service/product label payload </script><script> cannot appear literal or execute
- minimal patch boundary: workspace JSON config encoding in view/builder; no raw user-controlled JSON
- focused test target: admin/cashier workspace rendering feature tests
- wider regression target: Blade raw JSON grep, workspace/admin/cashier view tests
- closure proof: both stored field and product label data sources safe; no unsafe JSON sink remains
- handoff note template: "007 workspace XSS: sinks=<paths>, payloads=<proof>, negative_search=<proof>"
#### Issue Card 024

- error_log path: docs/error_log/024-reflected-xss-in-expense-create-json-config.md
- status dokumen saat ini: Patched, with verification gap
- status kepercayaan: weak
- root cause sementara: query category_id reflected into expense create JSON config script with unsafe raw encoding
- affected layer: HTTP/controller, Blade, JS, security, docs
- dependency upstream: #007 output encoding pattern
- dependency downstream: ADR-0020 JS config standard
- RED proof yang dibutuhkan: category_id=</script><script>... does not break script block
- minimal patch boundary: resources/views/admin/expenses/create.blade.php safe JSON encoding
- focused test target: CreateExpensePageFeatureTest
- wider regression target: expense feature tests plus raw JSON grep
- closure proof: rendered response safe, config parseable, unsafe literal absent
- handoff note template: "024 expense JSON: payload=<proof>, response_negative=<proof>, parseable=<proof>"
#### Issue Card 025

- error_log path: docs/error_log/025-reflected-javascript-url-in-product-return-link.md
- status dokumen saat ini: Patched, with verification gap
- status kepercayaan: weak
- root cause sementara: query return_to rendered as href; HTML escaping did not block javascript:
- affected layer: HTTP/controller, Blade, security, docs
- dependency upstream: ADR-0020 return URL policy
- dependency downstream: output URL audit
- RED proof yang dibutuhkan: return_to=javascript:alert(1) not rendered as active href; valid internal return still works
- minimal patch boundary: controller return URL resolver / allowlist; view remains escaped
- focused test target: admin product create page feature test
- wider regression target: product/procurement route return link tests
- closure proof: unsafe schemes rejected/fallback, allowed route accepted
- handoff note template: "025 return URL: unsafe=<proof>, allowed=<proof>, fallback=<proof>"
### Slice 8 - Storage, Public Helper, and Attachment Proof Security

- Issues:
- docs/error_log/023-public-helper-can-expose-private-storage.md
- docs/error_log/028-di-fix-exposes-unsafe-proof-attachment-content-type.md
- Alasan urutan:
- public/private storage and proof attachment security must be verified after access/procurement route gates are stable.
- #023 is public helper exposure; #028 is authenticated controller response MIME/content-disposition.
- Boleh digabung:
- storage/public helper audit
- supplier payment proof upload/serve/download focused suite
- attachment response header verification
- Harus dipisah:
- route transaction capability #027
- output JSON XSS #007/#024
- deployment-only symlink cleanup unless environment available
- Stop gate:
- public/a.php absent from repo and deployment check documented
- no private storage public symlink exposure
- proof attachment route uses auth/policy
- MIME server-detected, client MIME ignored
- risky files download with nosniff
- filename safe
#### Issue Card 023

- error_log path: docs/error_log/023-public-helper-can-expose-private-storage.md
- status dokumen saat ini: Patched
- status kepercayaan: weak for deployment; source deletion likely sufficient for repo endpoint
- root cause sementara: public a.php helper outside Laravel middleware could create symlink exposing private storage
- affected layer: infrastructure, public webroot, storage, security, docs
- dependency upstream: ADR-0020 storage boundary
- dependency downstream: #028 private proof serving
- RED proof yang dibutuhkan: helper endpoint absent; no helper-like public file; public/storage not pointing to private sensitive disk
- minimal patch boundary: remove public helper; no replacement public shortcut
- focused test target: repo/file absence check, route/public path audit
- wider regression target: storage/public helper grep
- closure proof: source deletion + deployment/runtime checklist completed or explicitly deferred
- handoff note template: "023 public helper: repo_absent=<proof>, public_symlink=<proof>, deployment=<done/deferred>"
#### Issue Card 028

- error_log path: docs/error_log/028-di-fix-exposes-unsafe-proof-attachment-content-type.md
- status dokumen saat ini: Fixed with proof
- status kepercayaan: trusted for attachment MIME/content-disposition scope
- root cause sementara: stored/client MIME and inline response let proof attachment be served as HTML same-origin
- affected layer: HTTP/controller, infrastructure storage, security, docs
- dependency upstream: #023 storage boundary, #027 procurement access gate
- dependency downstream: final global verification
- RED proof yang dibutuhkan: client text/html MIME replaced by server-detected safe MIME; risky content forced attachment
- minimal patch boundary: serve controller response factory behavior and storage adapter safe MIME detection
- focused test target: ServeSupplierPaymentProofAttachmentFeatureTest, SupplierPaymentProofFileStorageAdapterFeatureTest
- wider regression target: procurement proof matrix focused suite
- closure proof: RED/GREEN serve, RED/GREEN storage, nosniff, makeDisposition, focused 17 pass
- handoff note template: "028 proof attachment: serve=<proof>, storage=<proof>, headers=<proof>, focused=<proof>"
### Slice 9 - Seeder Credential Safety Future Scope

- Issue:
- docs/error_log/002-seeder-introduces-predictable-admin-credentials.md
- Alasan urutan:
- user explicitly forbids making seeder now.
- seeder remains outside active remediation workflow.
- Still tracked because it is identity/security risk and may affect future test environment/bootstrap.
- Boleh digabung:
- future seeder safety blueprint
- deployment credential rotation checklist
- local/dev vs production seeder policy
- Harus dipisah:
- all active error-log remediation slices
- bugfix implementation session
- source production changes in this session
- Stop gate:
- do not modify seeder
- document as future scope only
- do not claim seeder fixed globally
#### Issue Card 002

- error_log path: docs/error_log/002-seeder-introduces-predictable-admin-credentials.md
- status dokumen saat ini: Patched
- status kepercayaan: weak
- root cause sementara: seeded predictable admin credential and reseed overwrite risk; residual deployment rotation and kasir/default credential questions
- affected layer: infrastructure, seeder, auth/security, docs
- dependency upstream: none for active workflow
- dependency downstream: future bootstrap/security baseline
- RED proof yang dibutuhkan: future test proving reseed does not reset privileged credentials and production-like seeding cannot create known privileged password
- minimal patch boundary: future seeder policy only, not active session
- focused test target: future seeder regression/static tests
- wider regression target: future auth/bootstrap safety tests
- closure proof: future owner-approved seeder scope, credential rotation/deployment proof, no known default privileged credentials
- handoff note template: "002 seeder future: out_of_scope=true, rotation_needed=<yes/no>, future_tests=<list>"
### Slice 10 - Final Global Verification and Documentation Closure

- Issues:
- all docs/error_log/*.md
- Alasan urutan:
- final closure only after all active slices are resolved or explicitly deferred.
- No global claim while any weak/contradicted issue remains unresolved.
- Stop gate:
- every issue has final trust status
- #001/#003 conflict resolved
- #021/#022 conflict resolved
- all targeted/focused proofs recorded
- wider domain suites pass
- route-list proof for access issues
- Blade/JS negative search for output issues
- storage/attachment proof for proof issues
- audit/log/redaction proof for sensitive mutation
- final docs aligned
- no full verification blocker
## Issue Yang Boleh Digabung

- Boleh digabung untuk analysis/verification slice, bukan berarti harus satu commit:
- #001 + #003: active refund vs historical refund settlement conflict
- #005 + #008 + #017: payment/revision/legacy-existing allocation integrity
- #010 + #026: note-level lock protocol
- #013 + #014 + #021 + #022: refund route, row eligibility, parent note eligibility, date-window access
- #018 + #015: refunded terminal server guard plus UI visibility
- #016 + #020 + #027: transaction capability enforcement
- #007 + #024 + #025: output context and unsafe URL
- #023 + #028: storage boundary and attachment serving
- #019 + #029: cashier disclosure boundary
## Issue Yang Harus Dipisah

- Harus dipisah kecuali owner membuka scope eksplisit:
- #002 seeder dari workflow utama
- #006 price basis dari #007 XSS meskipun sama-sama workspace
- #015 UI visibility dari #018 server guard
- #021 parent note refund policy dari #022 route access guard sampai conflict selesai
- #023 public helper deletion dari #028 MIME/content-disposition behavior
- #028 attachment hardening dari #027 transaction capability gate
- explicit overpaid/customer credit feature dari #005 reject+rollback fix
- reporting rewrite dari #004 current operational flow
## Stop Gate Sebelum Pindah Slice

- Sebelum pindah slice, wajib ada:
- source inspected
- root cause final or conflict note
- RED proof or justified exception
- targeted GREEN proof
- focused blast-radius proof for sensitive slice
- UI Blade review if applicable
- native JS review if applicable
- security/auth review if applicable
- audit/log/redaction review if applicable
- docs update plan
- residual gaps listed
- handoff note completed
- Jika salah satu tidak ada, slice belum boleh ditutup.
## Format Handoff Per Slice

- Gunakan format ini saat berhenti atau pindah sesi:
- Handoff Slice
- Active slice:
- Issue paths:
- Trust status:
- Root cause final:
- Source files inspected:
- Production files changed:
- Test files changed:
- UI Blade impact:
- Native JS impact:
- Security impact:
- Audit/log/redaction impact:
- RED proof:
- GREEN proof:
- Focused blast-radius proof:
- Wider regression proof:
- Conflicts:
- Residual gaps:
- Stop conditions triggered:
- Safe next step:
- Do not touch:
- Suggested opening prompt for next session:
## Final Note

- Sequence ini sengaja menahan closure untuk issue yang dokumennya tampak selesai tetapi proof-nya lemah. Itu bukan pesimisme, itu cuma pengalaman pahit yang akhirnya punya format markdown.
