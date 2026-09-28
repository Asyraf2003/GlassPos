const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('public/assets/static/js/pages/admin-mobile-app.js', 'utf8');

const tick = () => new Promise((resolve) => setImmediate(resolve));

function harness({ supported = true } = {}) {
  const listeners = {};
  const windowListeners = {};
  const nodes = {
    install: { disabled: true, addEventListener: (name, handler) => { listeners[`install:${name}`] = handler; } },
    installStatus: { textContent: 'Belum terpasang' },
    installHelp: { textContent: '' },
    toggle: { checked: false, disabled: true, addEventListener: (name, handler) => { listeners[`toggle:${name}`] = handler; } },
    state: { textContent: '' },
  };
  const card = {
    querySelector: (selector) => ({
      '[data-admin-install]': nodes.install,
      '[data-admin-install-status]': nodes.installStatus,
      '[data-admin-install-help]': nodes.installHelp,
      '[data-admin-push-toggle]': nodes.toggle,
      '[data-admin-push-state]': nodes.state,
    })[selector],
  };
  const context = {
    navigator: {
      standalone: false,
      serviceWorker: { register: async () => ({}) },
    },
    matchMedia: () => ({ matches: false }),
    document: {
      hidden: false,
      querySelector: (selector) => selector === '[data-admin-mobile-app]' ? card : null,
      addEventListener: (name, handler) => { listeners[`document:${name}`] = handler; },
    },
    window: {
      addEventListener: (name, handler) => { windowListeners[name] = handler; },
      AppPushNotifications: {
        isSupported: () => supported,
        getState: async () => { throw new Error('status endpoint unavailable'); },
        enable: async () => ({ enabled: true }),
        disable: async () => ({ deleted: true }),
      },
    },
  };
  vm.createContext(context);
  vm.runInContext(source, context);
  return { nodes, listeners, windowListeners };
}

test('transient status failure keeps a supported notification toggle retryable', async () => {
  const h = harness({ supported: true });
  await tick();
  assert.equal(h.nodes.toggle.checked, false);
  assert.equal(h.nodes.toggle.disabled, false);
  assert.match(h.nodes.state.textContent, /tetap bisa mencoba mengaktifkan notifikasi/);
});

test('status failure stays disabled when push is genuinely unsupported', async () => {
  const h = harness({ supported: false });
  await tick();
  assert.equal(h.nodes.toggle.checked, false);
  assert.equal(h.nodes.toggle.disabled, true);
  assert.match(h.nodes.state.textContent, /belum dapat digunakan/);
});
