# 0016 - Cashier Device-Aware Create Presentation

## Status

- Date: 2026-09-05
- Status: Owner-approved implementation
- Scope: cashier create workspace, cashier note detail presentation, cashier PWA affordance
- Supersedes only the device/default-presentation clauses of UI blueprint `0014`

## Locked Matrix

| Surface | Desktop | Handset |
| --- | --- | --- |
| Fresh create default | Detail | Simple |
| Fresh create mode switch | Detail <-> Simple | Simple <-> Detail |
| Edit/revision | Detail only, no switch | Detail only, no switch |
| Note detail | wide detailed layout | compact stacked layout |
| PWA install affordance | not rendered | rendered only for browser capability gating |

## Device Classification

Presentation policy uses the shared HTTP `HandsetRequestDetector`.

- `Sec-CH-UA-Mobile: ?1` means handset.
- `Sec-CH-UA-Mobile: ?0` means desktop.
- Mobile UA fallback exists for browsers without the client hint.
- Viewport width does not change the server device class.
- Desktop browser resized to a phone width remains desktop policy.
- Full browser device spoofing can intentionally impersonate a handset and is not a physical-device proof.

Viewport breakpoints remain valid only for fitting controls into available space. They no longer decide the create default or PWA availability.

## Shared Transaction Contract

Desktop/handset and Simple/Detail are presentation axes only.

All four combinations keep:

- one HTML form;
- one idempotency key;
- the same authoritative product/service/template IDs;
- the same `inline_payment[...]` payload contract;
- the same request validation;
- the same application/domain/payment/inventory/audit paths.

No mobile or desktop payment use case, endpoint, persistence writer, or business rule may be introduced for this presentation split.

## Payment Presentation

- Desktop Detail is the advanced payment surface by default.
- Handset Simple uses the existing fast `skip`, `partial`, and `full` presets by default.
- Desktop may switch to Simple and receives those same presets.
- Handset may switch to Detail; redundant summary/help/date presentation may be hidden while canonical hidden/default values remain present.
- Cash tender/change calculation remains available when required and must not be replaced by guessed financial values.

## Edit And Detail Boundary

Simple mode exists only on fresh create.

Edit/revision and note detail never render the Simple/Detail switch. Device-specific layout may still reduce presentation noise on handset without changing mutation capability or financial meaning.

## PWA Boundary

The cashier dashboard must not render its install card or install script for desktop requests. On handset, the card starts disabled and browser `beforeinstallprompt` capability enables installation. Installed/unsupported states remain fail-closed.

## Test-First Fence

Implementation is guarded by tests that assert:

1. desktop create starts Detail and still exposes the create-only switch;
2. handset create starts Simple with the same canonical form fields;
3. one idempotency field and one payment-decision field exist;
4. desktop dashboard has no PWA install affordance;
5. handset dashboard has the capability-gated PWA affordance;
6. cashier note detail emits desktop vs compact handset layout markers;
7. existing edit tests continue to require Detail and no toggle;
8. no alternative payment path is added.

Physical-phone/PWA interaction remains a manual acceptance proof after automated verification.
