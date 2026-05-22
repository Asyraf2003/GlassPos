# Blade/JS UI Payload Readiness Findings

Scope source: Stage 0 baseline. This file is pending deeper Blade/JS audit.

## MRD-009 - Blade/JS Payload Coupling Remains A Contract And Browser Proof Gap

ID: MRD-009

Status: needs-proof

Severity: P2

Area: Blade/JS/UI payload readiness

Type: readiness-debt

Evidence:

- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- `resources/views/shared/notes/partials/payment-summary-actions.blade.php`
- `resources/views/admin/procurement/supplier_invoices/create.blade.php`
- `resources/views/admin/procurement/supplier_invoices/edit.blade.php`
- `resources/views/admin/procurement/supplier_invoices/payment_proofs.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/product.blade.php`
- `resources/views/cashier/notes/workspace/partials/templates/service-store-stock.blade.php`
- `public/assets/static/js/pages/cashier-note-payment.js`
- `public/assets/static/js/pages/admin-procurement-create.js`
- Stage 0 command evidence: `rg "<script|@json|Js::from|json_encode|innerHTML|insertAdjacentHTML|hidden|type=\"hidden\"|data-|window\\." resources/views -n`
- Stage 0 command evidence: `rg "innerHTML|insertAdjacentHTML|outerHTML|template|dataset|JSON\\.parse|window\\.[A-Za-z0-9_]+Config|fetch\\(" public/assets/static/js resources/views -n`

FACT:

- Finance/procurement UI uses hidden inputs, data attributes, global JS config, template cloning, dynamic HTML, and JavaScript-derived selected row payloads.
- `cashier-note-payment.js` builds hidden `selected_row_ids[]` inputs and computes UI payable/selected totals from DOM dataset values.
- `admin-procurement-create.js` uses window config, hidden product IDs, money raw fields, template cloning, draft storage, and client-side duplicate feedback.
- ADR-0028 says controllers are transport and Blade must not become financial truth.

RISK:

- UI/backend mismatch can become an API contract bug when Go clients or mobile clients reimplement payload composition.
- XSS or unsafe dynamic HTML risk cannot be dismissed without a sink audit.

GAP:

- No full browser QA.
- No full Blade/JS sink audit.
- No backend-vs-UI contract matrix.
- Hidden input trust boundary not fully mapped.
- Dataset-derived totals/IDs not fully classified as authoritative or preview-only.
- Dynamic HTML/template cloning not fully audited.
- Global JS config not fully audited.
- Payment/refund/procurement payload coupling not fully mapped.

Why it matters for smooth Go/PostgreSQL transition:

Go API extraction needs typed contracts independent from Blade behavior. Any business rule hidden in DOM/JS must be moved to backend contract or documented as UI-only preview.

Recommended direction:

Map each UI payload field to backend command fields and classify it as authoritative, preview-only, or derived server-side.

Proof required:

- UI payload contract map for note payment/refund/workspace and procurement create/edit/payment proof.
- Browser tests or HTTP tests proving backend rejects tampered UI payloads.
- XSS sink audit for dynamic HTML paths.

Suggested test/proof:

- Tampered hidden input tests.
- Browser flow tests for payment/refund/procurement.
- Static sink audit for `innerHTML`, `insertAdjacentHTML`, JSON config, and dataset rendering.

Do not fix yet: yes

## Pending Deeper Blade/JS Audit

Status: non-register note

Area: Blade/JS/UI payload readiness

Type: proof-gap summary

Evidence:

- Stage 0 `MRD-009`

FACT:

- Stage 0 identified UI payload coupling and sink-risk categories.

RISK:

- Browser behavior can remain correct by accident while backend/API contracts diverge.

GAP:

- Need tampered payload tests.
- Need browser/sink audit.
- Need route-to-view-to-JS payload map.

Why it matters for smooth Go/PostgreSQL transition:

Future API clients should not depend on undocumented Blade/JS behavior.

Recommended direction:

Run a dedicated Blade/JS readiness batch after database and domain mutation discovery.

Proof required:

- Full UI payload and sink inventory.

Suggested test/proof:

- Browser QA and tamper-resistance tests.

Do not fix yet: yes
