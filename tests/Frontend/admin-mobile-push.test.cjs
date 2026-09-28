const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync('public/assets/static/js/shared/push-notifications.js', 'utf8');
function harness(options = {}) {
  const calls = [];
  let subscription = null;
  let active = options.active ?? false;
  const object = {
    endpoint: 'https://push.example.test/device',
    toJSON: () => ({ keys: { p256dh: 'key', auth: 'auth' } }),
    unsubscribe: async () => { calls.push('unsubscribe'); if (!options.unsubscribeFailure) subscription = null; return !options.unsubscribeFailure; },
  };
  if (options.existing) subscription = object;
  const registration = { pushManager: {
    getSubscription: async () => subscription,
    subscribe: async () => { calls.push('subscribe'); subscription = object; return object; },
  } };
  const config = { serviceWorkerUrl: '/service-worker.js', serviceWorkerScope: '/', subscribeUrl: '/subscribe', unsubscribeUrl: '/unsubscribe', statusUrl: '/status', vapidPublicKey: options.missingKey ? '' : 'AQID' };
  const context = {
    URL, Uint8Array,
    document: { getElementById: () => ({ textContent: '', dataset: config }), querySelector: () => ({ getAttribute: () => 'csrf' }) },
    Notification: { permission: options.permission || 'default', requestPermission: async () => { calls.push('permission'); context.Notification.permission = options.permissionResult || 'granted'; return context.Notification.permission; } },
    navigator: { serviceWorker: { getRegistrations: async () => [], getRegistration: async () => registration, register: async (url) => { calls.push(url); return registration; }, ready: Promise.resolve(registration) } },
    fetch: async (url, request) => {
      calls.push(request.method + ' ' + url);
      if (options.failStore && url === '/subscribe' || options.failDelete && url === '/unsubscribe') return { ok: false, status: 500 };
      if (url === '/subscribe') active = true;
      if (url === '/unsubscribe') active = false;
      return { ok: true, json: async () => ({ data: { active } }) };
    },
  };
  context.window = { PushManager: function () {}, Notification: context.Notification, atob: v => Buffer.from(v, 'base64').toString('binary'), sessionStorage: { removeItem() {} } };
  if (options.unsupported) delete context.window.PushManager;
  vm.createContext(context);vm.runInContext(source, context);
  return { api: context.window.AppPushNotifications, calls, subscription: () => subscription, active: () => active };
}
test('enable requests permission only on action, canonical worker, saves; reopen reads actual subscription; OFF deletes then unsubscribes', async () => {
  const h = harness();
  assert.equal((await h.api.getState()).enabled, false);
  assert(!h.calls.includes('permission'));
  assert.equal((await h.api.enable()).enabled, true);
  assert.deepEqual(h.calls.filter(v => v !== 'POST /status'), ['permission', '/service-worker.js', 'subscribe', 'POST /subscribe']);
  assert.equal((await h.api.getState()).enabled, true);
  assert.equal((await h.api.disable()).deleted, true);
  assert.deepEqual(h.calls.slice(-2), ['DELETE /unsubscribe', 'unsubscribe']);
  assert.equal((await h.api.getState()).enabled, false);
});
test('existing subscription renders ON without creating a subscription or permission prompt', async () => {
  const h = harness({ existing: true, active: true, permission: 'granted' });
  assert.equal((await h.api.getState()).enabled, true);
  await h.api.enable();
  assert(!h.calls.includes('subscribe'));assert(!h.calls.includes('permission'));
});
test('denied, dismissed, unsupported and missing configuration never fake active', async () => {
  for (const options of [{ permission: 'denied' }, { permissionResult: 'denied' }, { permissionResult: 'default' }, { unsupported: true }, { missingKey: true }]) {
    const h = harness(options);assert.equal((await h.api.enable()).enabled, false);assert.equal((await h.api.getState()).enabled, false);
    assert(!h.calls.includes('subscribe'));
    if (options.permission === 'denied') assert(!h.calls.includes('permission'));
  }
});
test('stale/other-account server registration stays OFF and does not auto reactivate', async () => {
  const h = harness({ existing: true, active: false, permission: 'granted' });
  assert.equal((await h.api.getState()).enabled, false);
  assert.deepEqual(h.calls, ['POST /status']);
  await h.api.enable();assert.equal((await h.api.getState()).enabled, true);assert(!h.calls.includes('subscribe'));
});
test('failed store rolls back newly created subscription; failed delete preserves browser subscription', async () => {
  const h = harness({ failStore: true });await assert.rejects(h.api.enable());assert.equal(h.subscription(), null);
  const d = harness({ existing: true, active: true, permission: 'granted', failDelete: true });
  await assert.rejects(d.api.disable());assert(d.subscription());assert.equal((await d.api.getState()).enabled, true);
});
test('browser unsubscribe failure is reported instead of claiming success', async () => {
  const h = harness({ existing: true, active: true, permission: 'granted', unsubscribeFailure: true });
  await assert.rejects(h.api.disable(), /unsubscribe failed/);
});
test('canonical service worker uses system notification without silent override and navigates to payload URL', async () => {
  const listeners = {};let notification;let navigated;
  const url = '/admin/procurement/supplier-invoices?payment_status=outstanding&sort_by=due_date&sort_dir=asc';
  const context = { self: { addEventListener: (name, handler) => listeners[name] = handler, registration: { showNotification: async (title, options) => notification = { title, options } } }, clients: { matchAll: async () => [], openWindow: async value => navigated = value } };
  vm.runInNewContext(fs.readFileSync('public/service-worker.js', 'utf8'), context);
  let pending;
  listeners.push({ data: { json: () => ({ title: 'Supplier', url }) }, waitUntil: p => pending = p });await pending;
  assert.equal(notification.options.silent, undefined);assert.equal(notification.options.data.url, url);
  listeners.notificationclick({ notification: { close() {}, data: { url } }, waitUntil: p => pending = p });await pending;
  assert.equal(navigated, url);
});
