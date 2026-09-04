# 0014 - Cashier Note Workspace Simple/Detail POS Hardening Blueprint

## Metadata

- Date: 2026-09-04
- Area: Cashier create/edit note workspace
- Status: Implemented and locally verified
- Owner decision: Simple/Detail POS workspace closure slice
- Supersedes: the create/edit transaction workspace direction in `0011_cashier_stepper_mobile_ui_redesign.md`
- Related note: `0013_cashier_responsive_simple_complex_modes_note.md`

## Goal

Close the cashier create/edit note presentation as a fast POS workspace without changing the existing transaction, payment, inventory, idempotency, revision, refund, audit, or reporting meaning.

The final flow is:

```text
mobile shell / desktop shell
-> Simple or Detail presentation state
-> one shared form and transaction state
-> existing HTTP request
-> existing application/domain flow
```

## Supersession Boundary

For create/edit note workspace, the final direction is no longer a required stepper ceremony such as:

```text
Info Nota -> Rincian -> Review -> Proses Nota
```

This blueprint supersedes only that create/edit workspace portion of blueprint `0011`.

Blueprint `0011` remains historical evidence and may remain relevant to cashier dashboard, history, detail, or other cashier surfaces that are not explicitly replaced here. Note history and note detail remain detailed audit-oriented representations.

## Locked Presentation Decisions

### Simple

- Fresh create defaults to Simple.
- Simple is a presentation preset, not a business-rule bypass.
- Fresh create starts with no active detail row.
- A row is created only after choosing one of four transaction types, except when restoring draft/old input or rendering edit/revision state.
- Customer name, phone, transaction date, operational note, payment method, paid date, amount received, and advanced review controls remain in the shared form contract but are hidden from the default presentation.
- Simple checkout prioritizes only total, `Simpan Nota`, `Bayar Sebagian`, and `Bayar Penuh`.
- Simple deliberately omits redundant headings, kickers, decorative icons, badges, and explanatory copy.

### Detail

- A single compact `Detail` control enables the advanced presentation on create only.
- Fresh create defaults the control off.
- Edit/revision is a hard Detail presentation: it never renders the toggle and provides no route into Simple.
- Detail preserves customer/date/operational fields, advanced payment method and amount controls, paid date, payment notes where supported, revision context, and refund controls where the existing lifecycle permits them.
- A paid transaction must not receive a cancel shortcut; reversal remains the existing refund path.

### Transaction Types

The workspace exposes these four existing mappings directly:

1. Produk
2. Servis
3. Servis + Sparepart Toko
4. Servis + Pembelian Luar

The selector is text-first. It does not reinterpret `entry_mode`, `part_source`, pricing mode, stock behavior, or financial lines.

## Responsive Contract

### Desktop

At the desktop breakpoint the workspace is a two-pane POS:

- left: transaction type controls, active rows, lookup/selection, quantity and line editing;
- right: compact total and transaction actions.

Keyboard lookup navigation remains first-class.

### Mobile

Below the desktop breakpoint the workspace is one column with touch-sized controls, nearby search results, compact selected states, and a sticky total/action area that respects safe-area spacing.

Viewport width determines layout. Pointer/hover capabilities may adjust tap interaction. Standalone display mode may adjust safe-area treatment. User-agent sniffing is forbidden.

Desktop browser emulation at mobile width proves responsive layout only; it is not proof of a physical phone or installed PWA.

## Lookup And Selection Contract

### Product

- Search input is query only.
- The authoritative product identity is the selected product ID.
- Results expose name as primary text; brand, size, and code as secondary text; price and stock as additional text.
- Selection clears the query, closes results, and renders one compact selected stamp with quantity controls.
- Only the explicit `×` action releases the stamp and authoritative ID. Typing, focus, blur, ordinary click, keyboard navigation, stale responses, and re-rendering must not release it.
- Releasing the stamp clears product price/stock presentation state as well as identity before search becomes active again.
- Plain typed text without an authoritative selected ID cannot pass as a valid product.
- ArrowUp, ArrowDown, Enter, Escape, stale-request protection, and a bounded query cache remain supported.
- The server remains authoritative for active product, price floor, stock, and submit-time constraints.

### Service

Service lookup follows query -> result -> stable selected stamp. A catalog selection retains its authoritative catalog ID until explicit `×`; release clears catalog identity and catalog-derived defaults together. Service catalog data supplies identity/default presentation without becoming the transaction financial source of truth.

### Store-stock package

Simple selects an active service-product package/template and displays its service/product composition as one compact stamp. Explicit `×` clears package/template/service/product identity and every derived decomposition before search is re-enabled. The UI must not construct a bypass payload. Existing template requirement, maximum product lines, package auto split, floor price, and submit-time stock validation remain authoritative.

### Shared selection language

Product, service, and store-stock package use the same interaction language: query -> authoritative result -> compact selected stamp -> explicit `×` release. Display text is never identity, and an invalidated or stale lookup response cannot revive a released selection.

### External purchase

Simple retains separate structured values for service name, service price, external part label, and external purchase amount. It must not collapse those values into an operational note or a generic total.

## Payment Preset Contract

### Simpan Nota

Assembles the existing `skip` decision. It creates no payment/ledger effect beyond the canonical no-payment create flow.

### Bayar Penuh

Assembles the existing `pay_full` decision with cash, exact payable amount as received amount where required, and the existing default paid date. It still submits through request validation, application services, domain policies, finance, inventory, audit/projection, idempotency, and the transaction boundary.

### Bayar Sebagian

Assembles the existing `pay_partial` decision with cash and only asks for the current paid nominal. Zero and full-or-greater boundaries remain rejected by the canonical rule.

### Detail payment

The existing advanced skip/full/partial, cash/transfer, received amount, and paid-date flow remains available through the same form and backend contract.

## Architecture Boundary

- No alternative controller, endpoint, use case, domain model, or persistence path may be introduced for Simple, mobile, or desktop.
- JavaScript owns only presentation state, lookup interaction, selected cards, quantity controls, and payment preset assembly.
- Backend validation remains authoritative.
- One shared form prevents duplicate named mobile/desktop payloads.
- Existing edit/revision/refund/history/detail paths remain in place.

## Static Asset Delivery Decision

- `ASSET_URL` alone controls Laravel static UI asset base URLs.
- `R2_PUBLIC_URL` remains the public object-storage/media URL.
- Local development without explicit `ASSET_URL` uses same-origin assets even when R2 media is configured.
- Production may explicitly set `ASSET_URL` to its CDN base.
- `ASSET_VERSION`, falling back to release `APP_VERSION`, is the cache-busting query contract and must be explicitly advanced per production release.
- Incremental R2 static upload accepts explicit files beneath canonical `public/assets`, preserves `assets/<relative-path>`, overwrites only targeted objects, and leaves unrelated remote objects untouched.

## Scope In

- Simple-mode copy and decorative-noise removal.
- Compact Detail control and create/edit defaults.
- Empty fresh-create state and draft/old/edit restore preservation.
- Desktop/mobile workspace hardening.
- Product/service/package/external selected-state hardening.
- Simple and Detail payment interaction proof.
- Adversarial transaction/payment/inventory/idempotency regression proof.
- Local/CDN asset URL separation and targeted static asset upload.
- Browser interaction and responsive matrix verification.
- Closure documentation and handoff.

## Scope Out

- New transaction/payment/inventory domain semantics.
- New Simple/mobile/desktop endpoints or use cases.
- Cashier history work-queue redesign, which is governed by blueprint `0015`.
- Production deploy, production migration, production data mutation, or static upload to the shared production CDN.
- Claiming physical phone/PWA acceptance from Chromium emulation.

## Verification Contract

Implementation cannot close until there is proof for:

- focused UI, Note, finance, inventory, idempotency, revision, refund, and infrastructure tests;
- at least fifteen adversarial domain scenarios derived from current domain truth;
- real Chromium interaction across all touched controls;
- responsive widths 360, 390, 412, about 768, and desktop at/above 992 including 1440x900;
- no horizontal overflow, duplicate active named inputs, or workspace JavaScript exceptions;
- targeted asset upload overwrite/new/rejection/isolation behavior;
- JavaScript syntax, Pint, architecture/contract audits, `git diff --check`, and `make verify` exit 0.

## Verification Proof

- The prior closure regression remains green: 114 focused Note/infrastructure tests with 1,324 assertions, followed by the full Note suite with 367 tests and 3,051 assertions.
- Chromium interaction proof passed at widths 360, 390x844, 412, 768, 992, and 1440x900 with no horizontal overflow, duplicate active named input, sticky/input overlap, workspace asset failure, or workspace JavaScript exception.
- Browser interaction covered all four row types, remove, Detail on/off, product/service/package search, keyboard navigation, selected stamp, explicit release and reselection, product quantity, partial open/cancel/submit, skip, real full cash, Detail transfer, edit PATCH, and refund surface.
- Product, service, and package release plus rapid/stale lookup behavior were exercised against canonical hidden identity/decomposition state; no released selection was revived by a delayed response.
- All ten assets loaded by the workspace resolved from the local application origin during browser proof.
- Final repository `make verify` passed: PHPStan analyzed 2,017 files with no errors, contract audits passed, and 1,582 tests completed with 10,103 assertions.

## Remaining GAP

- Physical phone and installed standalone PWA behavior require post-implementation manual device verification.
- Production CDN sync and production release configuration are intentionally outside this local closure slice.

## Next Active Step

Use the separate production workflow to set explicit production asset configuration, advance the asset release version, sync the reviewed changed-asset manifest, and perform physical phone/PWA plus production smoke verification.
