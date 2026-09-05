(() => {
  const c = window.employeeDebtTableConfig;
  if (!c) return;

  const $ = (id) => document.getElementById(id);
  const q = $('employee-debt-search-input');
  const body = $('employee-debt-table-body');
  const sum = $('employee-debt-table-summary');
  const pag = $('employee-debt-table-pagination');
  const filterForm = $('employee-debt-filter-form');
  const filterDrawer = $('employee-debt-filter-drawer');
  const filterBackdrop = $('employee-debt-filter-backdrop');

  const allowedSortBy = new Set([
    'employee_name',
    'latest_recorded_at',
    'total_debt_records',
    'total_debt_amount',
    'total_remaining_balance',
    'status',
  ]);
  const allowedSortDir = new Set(['asc', 'desc']);

  const trim = (v) => String(v ?? '').trim();
  const intOr = (v, f) => {
    const n = Number.parseInt(String(v ?? ''), 10);
    return Number.isNaN(n) || n < 1 ? f : n;
  };
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (m) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  }[m]));

  const state = () => {
    const p = new URLSearchParams(window.location.search);
    const s = trim(p.get('sort_by'));
    const d = trim(p.get('sort_dir'));

    return {
      q: trim(p.get('q')),
      page: intOr(p.get('page'), 1),
      sort_by: allowedSortBy.has(s) ? s : 'latest_recorded_at',
      sort_dir: allowedSortDir.has(d) ? d : 'desc',
      status: ['active', 'paid'].includes(trim(p.get('status'))) ? trim(p.get('status')) : 'all',
    };
  };

  const s = state();
  let timer = null;
  let req = 0;
  let activeController = null;

  const params = () => {
    const out = {
      page: String(s.page),
      per_page: '10',
      sort_by: s.sort_by,
      sort_dir: s.sort_dir,
    };

    if (s.q) out.q = s.q;
    if (s.status !== 'all') out.status = s.status;

    return out;
  };

  const paramsString = () => new URLSearchParams(params()).toString();

  const updateUrl = (replace = false) => {
    const url = new URL(window.location.href);
    url.search = paramsString();

    if (replace) {
      window.history.replaceState(null, '', url);
      return;
    }

    window.history.pushState(null, '', url);
  };

  const renderSummary = (m) => {
    const total = Number(m.total || 0), page = Number(m.page || 1), perPage = Number(m.per_page || 10);
    const from = total ? ((page - 1) * perPage) + 1 : 0;
    sum.textContent = `Menampilkan ${from} sampai ${Math.min(page * perPage, total)} dari ${total} karyawan dengan hutang`;
  };

  const renderSort = () => {
    document.querySelectorAll('[data-sort-indicator]').forEach((n) => {
      const active = n.dataset.sortIndicator === s.sort_by;
      n.textContent = active ? (s.sort_dir === 'asc' ? '↑' : '↓') : '↕';
      n.classList.toggle('text-muted', !active);
    });
  };

  const renderPager = (m) => {
    if (m.last_page <= 1) {
      pag.innerHTML = '';
      return;
    }

    const start = Math.max(1, m.page - 2);
    const end = Math.min(m.last_page, m.page + 2);

    let html = '<nav><ul class="pagination pagination-primary mb-0">';
    html += `<li class="page-item ${m.page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${m.page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;

    for (let p = start; p <= end; p += 1) {
      html += `<li class="page-item ${p === m.page ? 'active' : ''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
    }

    html += `<li class="page-item ${m.page === m.last_page ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${m.page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
    html += '</ul></nav>';

    pag.innerHTML = html;
  };

  const renderRows = (rows, m) => {
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data hutang karyawan yang cocok.</td></tr>';
      return;
    }

    body.innerHTML = rows.map((r, i) => {
      const debtStatusSummary = String(r.status_label ?? '-');

      return `
        <tr>
          <td>${(m.page - 1) * m.per_page + i + 1}</td>
          <td>${esc(r.employee_name)}</td>
          <td>${esc(r.latest_recorded_at)}</td>
          <td>${esc(r.total_debt_records)}</td>
          <td>Rp${esc(r.total_debt_amount_formatted)}</td>
          <td>Rp${esc(r.total_remaining_balance_formatted)}</td>
          <td>${esc(debtStatusSummary)}</td>
          <td class="text-center">
            <button
              type="button"
              class="btn btn-sm btn-light-primary js-open-employee-debt-action"
              data-employee-id="${esc(r.employee_id)}"
              data-employee-name="${esc(r.employee_name)}"
              data-debt-status-summary="${esc(debtStatusSummary)}"
              data-debt-detail-id="${esc(r.debt_detail_id ?? '')}"
              data-latest-unpaid-debt-id="${esc(r.latest_unpaid_debt_id ?? '')}"
            >
              Aksi
            </button>
          </td>
        </tr>
      `;
    }).join('');
  };

  const load = async (replace = false) => {
    activeController?.abort();
    const controller = new AbortController(); activeController = controller;
    const current = ++req;
    body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Sedang memuat data...</td></tr>';
    try {
      const res = await fetch(`${c.endpoint}?${paramsString()}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
      const json = await res.json();
      if (current !== req) return;
      if (!res.ok || !json.success) throw new Error('employee-debt-table-response');
      renderRows(json.data.rows || [], json.data.meta || {}); renderSummary(json.data.meta || {}); renderPager(json.data.meta || {}); renderSort(); updateUrl(replace);
    } catch (error) {
      if (error?.name === 'AbortError' || current !== req) return;
      body.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
      sum.textContent = 'Menampilkan 0 sampai 0 dari 0 karyawan dengan hutang'; pag.innerHTML = '';
    } finally { if (activeController === controller) activeController = null; }
  };

  q?.addEventListener('input', (e) => {
    clearTimeout(timer);
    const value = trim(e.target.value);
    s.q = value.length >= 2 ? value : '';
    s.page = 1;
    timer = setTimeout(() => {
      load();
    }, value.length >= 2 ? 220 : 160);
  });

  document.querySelectorAll('[data-sort-by]').forEach((b) => b.addEventListener('click', () => {
    const key = b.dataset.sortBy;
    s.sort_dir = s.sort_by === key && s.sort_dir === 'asc' ? 'desc' : 'asc';
    s.sort_by = key;
    s.page = 1;
    load();
  }));

  const syncControls = () => { q.value = s.q; if (filterForm?.elements.status) filterForm.elements.status.value = s.status; };
  const drawFilter = (open) => { filterDrawer?.classList.toggle('d-none', !open); filterBackdrop?.classList.toggle('d-none', !open); };
  $('open-employee-debt-filter')?.addEventListener('click', () => drawFilter(true));
  $('close-employee-debt-filter')?.addEventListener('click', () => drawFilter(false));
  filterBackdrop?.addEventListener('click', () => drawFilter(false));
  filterForm?.addEventListener('submit', (e) => { e.preventDefault(); s.status = filterForm.elements.status.value; s.page = 1; drawFilter(false); load(); });
  $('reset-employee-debt-filter')?.addEventListener('click', () => { s.status = 'all'; s.page = 1; syncControls(); drawFilter(false); load(); });

  pag?.addEventListener('click', (e) => {
    const b = e.target.closest('[data-page]');
    if (!b) return;
    s.page = Number(b.dataset.page);
    load();
  });

  window.addEventListener('popstate', () => { Object.assign(s, state()); syncControls(); load(true); });

  syncControls();
  load(true);
})();
