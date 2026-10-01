const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function harness(initial = '') {
  const timers = new Map(), requests = [];
  let timerId = 0;
  const node = () => ({
    value: '', hidden: false, disabled: false, textContent: '', attributes: {}, handlers: {},
    classes: new Set(),
    classList: { toggle(name, on) { if (on) this.owner.classes.add(name); else this.owner.classes.delete(name); } },
    addEventListener(type, fn) { this.handlers[type] = fn; },
    setAttribute(name, value) { this.attributes[name] = value; },
    setCustomValidity(value) { this.validityMessage = value; },
  });
  const input = node(), feedback = node(), button = node(), form = node();
  [input, feedback, button, form].forEach((n) => { n.classList.owner = n; });
  input.value = initial;
  form.querySelector = (selector) => selector.includes('nomor_faktur') ? input : feedback;
  form.querySelectorAll = () => [button];
  const context = {
    window: {}, AbortController, URLSearchParams,
    setTimeout(fn, delay) { timers.set(++timerId, { fn, delay }); return timerId; },
    clearTimeout(id) { timers.delete(id); },
    // Deliberately ignore abort to prove request identity is independently safe.
    fetch(url, options) {
      return new Promise((resolve, reject) => { requests.push({ url, options, resolve, reject }); });
    },
  };
  vm.runInNewContext(fs.readFileSync('public/assets/static/js/shared/supplier-invoice-number-validation.js', 'utf8'), context);
  const guard = context.window.bindSupplierInvoiceNumberValidation({ form, endpoint: '/check-number' });
  const flush = async () => { for (let n = 0; n < 6; n++) await Promise.resolve(); };
  return {
    input, feedback, button, form, guard, requests, timers,
    change(value) { input.value = value; input.handlers.input(); },
    tick() { const pending = [...timers.values()]; timers.clear(); pending.forEach(({ fn }) => fn()); },
    async respond(index, duplicate) {
      requests[index].resolve({ ok: true, json: async () => ({ success: true, data: { duplicate } }) }); await flush();
    },
    async fail(index) { requests[index].reject(new Error('network')); await flush(); },
  };
}

test('debounces typing, blocks pending/duplicate submit, renders red feedback and recovers with unique input', async () => {
  const h = harness();
  h.change('I'); h.change('IN'); h.change('INV-TAKEN');
  assert.equal(h.requests.length, 0);
  assert.equal(h.timers.size, 1);
  assert.equal([...h.timers.values()][0].delay, 250);
  assert.equal(h.button.disabled, true);
  assert.equal(h.guard.canSubmit(), false);
  h.tick(); await h.respond(0, true);
  assert.equal(h.feedback.textContent, 'Nomor faktur ini sudah pernah digunakan');
  assert.equal(h.feedback.hidden, false);
  assert.ok(h.feedback.classes.has('text-danger'));
  assert.ok(h.button.classes.has('disabled'));
  assert.equal(h.button.attributes['aria-disabled'], 'true');
  assert.equal(h.guard.canSubmit(), false);
  h.change('INV-UNIQUE'); h.tick(); await h.respond(1, false);
  assert.equal(h.feedback.hidden, true);
  assert.equal(h.input.validityMessage, '');
  assert.equal(h.button.disabled, false);
  assert.equal(h.guard.canSubmit(), true);
});

test('input invalidates old response immediately, before new debounce sends request', async () => {
  const h = harness();
  h.change('OLD'); h.tick(); h.change('NEW');
  assert.equal(h.requests[0].options.signal.aborted, true);
  await h.respond(0, true);
  assert.equal(h.feedback.hidden, true);
  assert.equal(h.input.attributes['aria-busy'], 'true');
  h.tick(); await h.respond(1, false);
  assert.equal(h.guard.canSubmit(), true);
});

test('out-of-order old duplicate cannot overwrite latest unique result', async () => {
  const h = harness();
  h.change('OLD'); h.tick(); h.change('NEW'); h.tick();
  await h.respond(1, false); await h.respond(0, true);
  assert.equal(h.feedback.hidden, true);
  assert.equal(h.button.disabled, false);
});

test('out-of-order old unique cannot clear latest duplicate result', async () => {
  const h = harness();
  h.change('OLD'); h.tick(); h.change('NEW'); h.tick();
  await h.respond(1, true); await h.respond(0, false);
  assert.equal(h.feedback.hidden, false);
  assert.equal(h.button.disabled, true);
});

test('revisiting the same input still rejects obsolete request generation', async () => {
  const h = harness();
  h.change('SAME'); h.tick(); h.change('OTHER'); h.change('SAME'); h.tick();
  await h.respond(1, false); await h.respond(0, true);
  assert.equal(h.guard.canSubmit(), true);
});

test('clearing or resetting input invalidates old requests without leaving a disabled button', async () => {
  const h = harness();
  h.change('OLD'); h.tick(); h.change(''); await h.respond(0, true);
  assert.equal(h.feedback.hidden, true);
  assert.equal(h.guard.canSubmit(), true);
  h.change('OLD'); h.tick(); h.form.handlers.reset(); h.input.value = ''; h.tick();
  await h.respond(1, true);
  assert.equal(h.guard.canSubmit(), true);
  assert.equal(h.feedback.hidden, true);
});

test('restored draft checks canonical number on server without client normalization', async () => {
  const h = harness(' INV-ÄBC ');
  h.guard.refresh(); h.tick();
  assert.equal(new URL(h.requests[0].url, 'https://example.test').searchParams.get('nomor_faktur'), ' INV-ÄBC ');
  await h.respond(0, true);
  assert.equal(h.guard.canSubmit(), false);
});

test('blur flushes debounce; request failure falls back to backend and retries on next blur', async () => {
  const h = harness();
  h.change('INV'); h.input.handlers.blur();
  assert.equal(h.timers.size, 0);
  assert.equal(h.requests.length, 1);
  await h.fail(0);
  assert.equal(h.guard.canSubmit(), true);
  assert.equal(h.input.validityMessage, '');
  h.input.handlers.blur(); await h.respond(1, true);
  assert.equal(h.guard.canSubmit(), false);
});

test('silent programmatic input change starts validation before submit', async () => {
  const h = harness();
  h.change('UNIQUE'); h.tick(); await h.respond(0, false);
  h.input.value = 'CHANGED';
  assert.equal(h.guard.canSubmit(), false);
  h.tick(); await h.respond(1, true);
  assert.equal(h.guard.canSubmit(), false);
});

test('obsolete network failure cannot clear latest duplicate state', async () => {
  const h = harness();
  h.change('OLD'); h.tick(); h.change('NEW'); h.tick();
  await h.respond(1, true); await h.fail(0);
  assert.equal(h.guard.canSubmit(), false);
  assert.ok(h.feedback.classes.has('text-danger'));
});
