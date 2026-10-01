const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
function setup() {
  const timers = new Map(); let id = 0;
  const context = { window: {}, AbortController,
    setTimeout(fn) { timers.set(++id, fn); return id; }, clearTimeout(id) { timers.delete(id); },
  };
  vm.runInNewContext(fs.readFileSync('public/assets/static/js/shared/live-search.js', 'utf8'), context);
  return { ...context.window.LiveSearch, tick() { const callbacks = [...timers.values()]; timers.clear(); callbacks.forEach(fn => fn()); } };
}
test('invalidation rejects old responses immediately and aborts even during debounce', () => {
  const h = setup(), gate = h.create(), a = gate.begin();
  gate.invalidate(); let loads = 0; gate.schedule(() => loads++);
  assert.equal(a.signal.aborted, true); assert.equal(a.isCurrent(), false); assert.equal(loads, 0);
  h.tick(); assert.equal(loads, 1);
});
test('stale completion cannot clear the current controller or its generation', () => {
  const h = setup(), gate = h.create(), a = gate.begin(), b = gate.begin();
  a.finish(); assert.equal(b.isCurrent(), true); assert.equal(gate.pending(), true);
  gate.invalidate(); assert.equal(b.signal.aborted, true);
});
test('load from filter/pagination cancels a queued search; ABA query cannot revive older work', () => {
  const h = setup(), gate = h.create(); let called = false;
  const a = gate.begin(); gate.invalidate(); gate.schedule(() => { called = true; });
  const b = gate.begin(); h.tick(); assert.equal(called, false); assert.equal(a.isCurrent(), false); assert.equal(b.isCurrent(), true);
});
test('multiple independent lookup gates do not cancel each other', () => {
  const h = setup(), a = h.create(), b = h.create(), ra = a.begin(), rb = b.begin();
  a.invalidate(); assert.equal(ra.isCurrent(), false); assert.equal(rb.isCurrent(), true);
});
test('latest state changes synchronously; raw whitespace input stays untouched', () => {
  const h = setup(), gate = h.create(), handlers = {}, input = { value: ' jaya ', addEventListener(type, fn) { handlers[type] = fn; } };
  let q = '', loads = 0;
  h.bind({ gate, input, getQuery: () => q, onQuery: value => { q = value; }, load() { loads++; } });
  handlers.input(); assert.equal(q, 'jaya'); assert.equal(input.value, ' jaya '); assert.equal(loads, 0);
  h.tick(); assert.equal(loads, 1);
});
test('async boot hydration skips a form field edited before draft load completes', async () => {
  let resolveDraft;
  const draft = new Promise(resolve => { resolveDraft = resolve; });
  const handlers = {}, customer = { id: 'note_customer_name', value: 'Initial', closest() { return null; } }, phone = { id: 'note_customer_phone', value: '' };
  const config = { oldNote: { customer_name: 'STALE', customer_phone: '123' }, oldItems: [] };
  const configNode = { textContent: JSON.stringify(config) };
  class Input {}
  Object.setPrototypeOf(customer, Input.prototype); Object.setPrototypeOf(phone, Input.prototype);
  const document = { addEventListener(type, fn) { handlers[type] = fn; }, getElementById(id) { return { 'cashier-note-workspace-config': configNode, 'note_customer_name': customer, 'note_customer_phone': phone }[id] || null; } };
  const context = { window: { CashierNoteWorkspace: { workspaceConfigReady: draft } }, document, HTMLInputElement: Input, HTMLTextAreaElement: class {} };
  vm.runInNewContext(fs.readFileSync('public/assets/static/js/pages/cashier-note-workspace/boot.js', 'utf8'), context);
  customer.value = 'JAYA MOTOR'; handlers.input({ target: customer });
  resolveDraft(); for (let i = 0; i < 6; i++) await Promise.resolve();
  assert.equal(customer.value, 'JAYA MOTOR'); assert.equal(phone.value, '123');
});
