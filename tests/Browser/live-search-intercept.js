(() => {
  const requests = [], inputs = [], writes = [], errors = [];
  const nativeFetch = window.fetch;
  // Each route/viewport starts without drafts saved by a previous browser scenario.
  localStorage.clear(); sessionStorage.clear();
  const value = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
  Object.defineProperty(HTMLInputElement.prototype, 'value', { ...value, set(next) {
    writes.push({ target: this === window.proofInput, id: this.id, before: value.get.call(this), next });
    value.set.call(this, next);
  } });
  document.addEventListener('input', event => inputs.push({ id: event.target.id, value: event.target.value }), true);
  window.addEventListener('error', event => errors.push(event.message));
  window.addEventListener('unhandledrejection', event => errors.push(String(event.reason)));
  window.fetch = (url, options = {}) => {
    const parsed = new URL(url, location.href);
    if (parsed.pathname.includes('/assets/')) return nativeFetch(url, options);
    if (parsed.pathname.includes('check-number') || parsed.searchParams.has('nomor_faktur')) {
      return Promise.resolve({ ok: true, json: async () => ({ success: true, data: { duplicate: false } }) });
    }
    if (parsed.pathname.includes('draft')) return Promise.resolve({ ok: true, json: async () => ({ success: true, data: { draft: null } }) });
    return new Promise((resolve, reject) => requests.push({ url: parsed.href, query: parsed.searchParams.get('q') || parsed.searchParams.get('search') || '', options, resolve, reject }));
  };
  const respond = (index, label) => {
    const row = { id: label, name: label, label, nama_barang: label, nama_pt_pengirim: label,
      employee_name: label, customer_name: label, nomor_faktur: label, description: label, event: label,
      service_name: label, service: { name: label, price_rupiah: 10000 }, default_price_rupiah: 10000, default_unit_price_rupiah: 10000, available_stock: 10,
      actions: {}, product_lines: [], items: [] };
    const meta = { page: 1, per_page: 10, total: 1, last_page: 1 };
    requests[index].resolve({ ok: true, json: async () => ({ success: true, data: { rows: [row], items: [row], meta, pagination: meta, summary: { label } } }) });
  };
  window.__searchProof = { requests, inputs, writes, errors, respond };
})();
