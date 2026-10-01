const fs = require('node:fs');
const vm = require('node:vm');
const surfaces = [
  ['admin-suppliers-table', 'supplierTableConfig', 'supplier'],
  ['admin-products-table', 'productTableConfig', 'product'],
  ['admin-procurement-invoices-table', 'procurementInvoiceTableConfig', 'procurement-invoice'],
  ['admin-expenses-table', 'expenseTableConfig', 'expense'],
  ['admin-services-table', 'AdminServiceTableConfig', 'service'],
  ['admin-service-product-templates-table', 'AdminPackageTableConfig', 'package'],
  ['admin-employees-table', 'employeeTableConfig', 'employee'],
  ['admin-payrolls-table', 'payrollTableConfig', 'payroll'],
  ['admin-employee-debts-table', 'employeeDebtTableConfig', 'employee-debt'],
  ['admin-audit-logs-table', 'auditLogTableConfig', 'audit-log'],
  ['admin-note-index', null, 'admin-note'],
  ['cashier-note-index', null, 'cashier-note'],
  ['admin-expense-categories-table', 'expenseCategoryTableConfig', 'expense-category'],
];
function harness(surface, initial = '') {
  const [script, configKey, prefix] = surface;
  const timers = new Map(), requests = [], nodes = new Map(), windowHandlers = {};
  let timerId = 0;
  const node = (id = '') => ({
    id, value: '', innerHTML: '', textContent: '', dataset: {}, handlers: {}, attributes: {},
    classList: { add() {}, remove() {}, toggle() {}, contains() { return false; } },
    elements: new Proxy({}, { get(target, key) { return target[key] ||= { value: '' }; } }),
    addEventListener(type, fn) { (this.handlers[type] ||= []).push(fn); },
    dispatch(type, event = {}) { for (const fn of this.handlers[type] || []) fn({ target: this, preventDefault() {}, ...event }); },
    setAttribute(key, value) { this.attributes[key] = value; },
    getAttribute(key) { return this.attributes[key]; },
    querySelectorAll() { return []; }, querySelector() { return null; },
    reset() {}, replaceChildren() { this.innerHTML = ''; },
    focus() { document.activeElement = this; },
    setSelectionRange(start, end) { this.selectionStart = start; this.selectionEnd = end; },
  });
  const get = (id) => { if (!nodes.has(id)) nodes.set(id, node(id)); return nodes.get(id); };
  const buckets = ['unfinished', 'completed'].map(value => { const n = node(); n.dataset.historyBucket = value; return n; });
  const document = {
    activeElement: null, getElementById: get,
    querySelectorAll(selector) { return selector === '[data-history-bucket]' ? buckets : []; },
    querySelector() { return null; }, addEventListener(type, fn) { if (type === 'DOMContentLoaded') this.ready = fn; },
  };
  const location = new URL(`http://localhost/admin/test${initial ? '?q=' + initial + '&search=' + initial : ''}`);
  const history = { entries: [], replaceState(a, b, url) { location.href = new URL(url, location).href; }, pushState(a, b, url) { this.entries.push(String(url)); location.href = new URL(url, location).href; } };
  const config = { endpoint: '/table', editUrlTemplate: '/__CATEGORY_ID__/edit', activateUrlTemplate: '/__CATEGORY_ID__/activate', deactivateUrlTemplate: '/__CATEGORY_ID__/deactivate' };
  get(`${prefix}-index-config`).textContent = JSON.stringify(config);
  const window = { location, history, addEventListener(type, fn) { (windowHandlers[type] ||= []).push(fn); } };
  if (configKey) window[configKey] = config;
  const context = { window, document, location, history, URL, URLSearchParams, AbortController, Intl, console,
    setTimeout(fn, delay) { timers.set(++timerId, { fn, delay }); return timerId; },
    clearTimeout(id) { timers.delete(id); },
    addEventListener: window.addEventListener,
    // Intentionally ignore abort: identity must reject responses even if cancellation loses the race.
    fetch(url, options) { return new Promise((resolve, reject) => requests.push({ url: String(url), options, resolve, reject })); },
  };
  window.setTimeout = context.setTimeout; window.clearTimeout = context.clearTimeout;
  const helper = 'public/assets/static/js/shared/live-search.js';
  if (fs.existsSync(helper)) vm.runInNewContext(fs.readFileSync(helper, 'utf8'), context);
  vm.runInNewContext(fs.readFileSync(`public/assets/static/js/pages/${script}.js`, 'utf8'), context);
  document.ready?.();
  const searchPrefix = prefix === "procurement-invoice" ? "procurement" : prefix;
  const input = get(`${searchPrefix}-search-input`), form = get(`${searchPrefix}-search-form`);
  const body = get(prefix === 'cashier-note' ? 'cashier-note-list' : `${prefix}-table-body`);
  const flush = async () => { for (let i = 0; i < 12; i++) await Promise.resolve(); };
  return {
    requests, timers, input, body, document, history, location, nodes,
    change(value) { input.value = value; input.focus(); input.setSelectionRange(value.length, value.length); input.dispatch('input'); },
    tick() { const pending = [...timers.values()]; timers.clear(); pending.forEach(t => t.fn()); },
    submit() { form.dispatch('submit'); },
    navigate(query) { location.search = '?q=' + query + '&search=' + query; for (const fn of windowHandlers.popstate || []) fn(); },
    async respond(index, name = 'DEFAULT') {
      const row = { id: name, nama_pt_pengirim: name, nama_barang: name, name, employee_name: name, customer_name: name, nomor_faktur: name, description: name, event: name, service_name: name, actions: {}, product_lines: [], items: [] };
      const meta = { page: 1, per_page: 10, total: 1, last_page: 1 };
      requests[index].resolve({ ok: true, json: async () => ({ success: true, data: { rows: [row], items: [row], meta, pagination: meta, summary: { label: name } } }) });
      await flush();
    },
    async fail(index) { requests[index].reject(Error('old network failure')); await flush(); },
  };
}
module.exports = { harness, surfaces };
