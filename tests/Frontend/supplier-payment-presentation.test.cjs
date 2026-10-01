const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function mobileHarness() {
  const handlers = {}, modalHandlers = {}, nodes = {};
  const node = (selector) => nodes[selector] ??= { textContent: '', value: '' };
  const form = { dataset: {}, querySelector: node };
  const modal = { querySelector: node, addEventListener: (type, fn) => { modalHandlers[type] = fn; } };
  const root = { dataset: {}, querySelectorAll: () => [], addEventListener: (type, fn) => { handlers[type] = fn; } };
  const context = {
    document: { querySelector: () => root, getElementById: (id) => id.endsWith('-form') ? form : modal },
    window: { bootstrap: { Modal: { getOrCreateInstance: () => ({ show() {} }) } } },
  };
  vm.runInNewContext(fs.readFileSync('public/assets/static/js/pages/admin-mobile-supplier-hub.js', 'utf8'), context);
  return { form, nodes, modalHandlers, click(dataset, legacy) {
    const row = { dataset, hasAttribute: () => legacy };
    handlers.click({ preventDefault() {}, target: { closest: (selector) => selector.includes('data-mobile-pay-invoice') ? row : null } });
  } };
}

test('payment modal uses bank and current supplier while keeping invoice ID internal', () => {
  const h = mobileHarness();
  h.click({ invoiceId: 'invoice-internal', supplierName: 'PT Bank', bankLabel: 'BCA | 00123', outstandingLabel: 'Rp 10.000' }, false);
  assert.equal(h.form.dataset.scopeType, 'supplier_invoice');
  assert.equal(h.form.dataset.scopeId, 'invoice-internal');
  assert.equal(h.nodes['[data-mobile-payment-bank]'].textContent, 'BCA | 00123');
  assert.equal(h.nodes['[data-mobile-payment-supplier]'].textContent, 'PT Bank');
  assert.equal(h.nodes['[data-mobile-payment-amount-label]'].textContent, 'Sisa yang akan dilunasi');
});

test('legacy payment switches to attachment-only scope and back without stale identity', () => {
  const h = mobileHarness();
  h.form.dataset.uploadIdempotencyKey = 'old-key';
  h.click({ paymentId: 'paid-existing', supplierName: 'PT Legacy', outstandingLabel: 'Rp 10.000' }, true);
  assert.equal(h.form.dataset.scopeType, 'supplier_payment');
  assert.equal(h.form.dataset.scopeId, 'paid-existing');
  assert.equal(h.form.dataset.uploadIdempotencyKey, undefined);
  assert.equal(h.nodes['[data-direct-upload-submit]'].textContent, 'Simpan Bukti');
  assert.equal(h.nodes['[data-mobile-payment-amount-label]'].textContent, 'Jumlah yang sudah dibayar');
  h.click({ invoiceId: 'new-invoice', bankLabel: 'Data bank belum diisi' }, false);
  assert.equal(h.form.dataset.scopeType, 'supplier_invoice');
  assert.equal(h.form.dataset.scopeId, 'new-invoice');
  assert.equal(h.nodes['[data-mobile-payment-bank]'].textContent, 'Data bank belum diisi');
});

test('active upload cannot be dismissed or redirected to another payment', () => {
  const h = mobileHarness();
  h.form.dataset.uploading = '1';
  h.form.dataset.scopeId = 'original';
  h.click({ paymentId: 'other' }, true);
  assert.equal(h.form.dataset.scopeId, 'original');
  let prevented = false;
  h.modalHandlers['hide.bs.modal']({ preventDefault() { prevented = true; } });
  assert.equal(prevented, true);
});

function tableHarness(file, configName, rows) {
  const nodes = {};
  const get = (id) => nodes[id] ??= {
    value: '', textContent: '', innerHTML: '', dataset: {}, handlers: {}, attributes: {},
    elements: { bank_name: { value: '' }, bank_account_number: { value: '' } },
    classList: { add() {}, remove() {}, toggle() {} },
    addEventListener(type, fn) { this.handlers[type] = fn; },
    setAttribute(key, value) { this.attributes[key] = value; },
    getAttribute(key) { return this.attributes[key]; },
    querySelector() { return { value: '' }; }, focus() {}, select() {},
  };
  const context = {
    URL, URLSearchParams, AbortController, Intl, setTimeout, clearTimeout,
    document: { getElementById: get, querySelectorAll: () => [], querySelector: () => null },
    window: {
      [configName]: { endpoint: '/table', detailBaseUrl: '/invoice/__ID__', updateUrlTemplate: '/suppliers/__ID__' },
      location: { search: '', href: 'https://example.test/table' }, history: { replaceState() {}, pushState() {} },
      addEventListener() {}, requestAnimationFrame(fn) { fn(); },
      bootstrap: { Modal: class { show() {} hide() {} } },
    },
    fetch: async () => ({ ok: true, json: async () => ({ success: true, data: { rows, meta: { page: 1, per_page: 10, total: rows.length, last_page: 1 } } }) }),
  };
  vm.runInNewContext(fs.readFileSync('public/assets/static/js/shared/live-search.js', 'utf8'), context);
  vm.runInNewContext(fs.readFileSync(file, 'utf8'), context);
  return { get };
}

test('supplier table renders nulls honestly and preserves account strings when editing', async () => {
  const h = tableHarness('public/assets/static/js/pages/admin-suppliers-table.js', 'supplierTableConfig', [
    { id: 'empty', nama_pt_pengirim: 'PT Empty', bank_name: null, bank_account_number: null },
    { id: 'bank', nama_pt_pengirim: 'PT Bank', bank_name: 'BCA', bank_account_number: '00123' },
  ]);
  await new Promise(setImmediate);
  const body = h.get('supplier-table-body');
  assert.equal((body.innerHTML.match(/Belum diisi/g) || []).length, 2);
  assert.match(body.innerHTML, /00123/);
  assert.doesNotMatch(body.innerHTML, /null|undefined/);
  body.handlers.click({ target: { closest: () => ({ dataset: { supplierId: 'bank', supplierName: 'PT Bank' } }) } });
  assert.equal(h.get('supplier-edit-form').elements.bank_account_number.value, '00123');
});

test('desktop payment modal shows current bank, no invoice ID and no empty separator', async () => {
  const h = tableHarness('public/assets/static/js/pages/admin-procurement-invoices-table.js', 'procurementInvoiceTableConfig', []);
  await new Promise(setImmediate);
  for (const [bank, account, expected] of [['BCA', '00012', 'BCA | 00012'], [null, null, 'Data bank belum diisi'], [null, '0007', '0007']]) {
    const row = { supplier_invoice_id: 'internal-id', nomor_faktur: 'INV-PRIVATE', supplier_nama_pt_pengirim_current: 'PT Current',
      bank_name: bank, bank_account_number: account, outstanding_rupiah: 10000, payment_action_mode: 'modal' };
    h.get('procurement-invoice-table-body').handlers.click({ target: { closest: () => ({ getAttribute: () => JSON.stringify(row) }) } });
    h.get('procurement-action-payment-link').handlers.click({ preventDefault() {} });
    assert.equal(h.get('procurement-payment-modal-subtitle').textContent, 'PT Current');
    assert.equal(h.get('procurement-payment-bank').textContent, expected);
    assert.equal(h.get('procurement-payment-outstanding').textContent, 'Rp 10.000');
    assert.equal(h.get('procurement-payment-form').dataset.scopeId, 'internal-id');
  }
});

test('supplier invoice list renders latest received quantity from current projection', async () => {
  const h = tableHarness('public/assets/static/js/pages/admin-procurement-invoices-table.js', 'procurementInvoiceTableConfig', [
    { supplier_invoice_id: 'invoice-revised', nomor_faktur: 'INV-CURRENT', total_received_qty: 80 },
  ]);
  await new Promise(setImmediate);
  assert.match(h.get('procurement-invoice-table-body').innerHTML, /<td>80<\/td>/);
});
