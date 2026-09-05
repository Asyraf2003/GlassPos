(() => {
  const c = window.employeeTableConfig;
  if (!c) return;

  const $ = (id) => document.getElementById(id);
  const form = $('employee-search-form');
  const q = $('employee-search-input');
  const body = $('employee-table-body');
  const sum = $('employee-table-summary');
  const pag = $('employee-table-pagination');
  const filterForm = $('employee-filter-form');
  const filterDrawer = $('employee-filter-drawer');
  const filterBackdrop = $('employee-filter-backdrop');

  const allowedSortBy = new Set([
    'employee_name',
    'phone',
    'default_salary_amount',
    'salary_basis_type',
    'employment_status',
  ]);
  const allowedSortDir = new Set(['asc', 'desc']);

  let timer = null;
  let req = 0;
  let activeController = null;

  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (m) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  }[m]));

  const trim = (v) => String(v ?? '').trim();

  const intOr = (v, f) => {
    const n = Number.parseInt(String(v ?? ''), 10);
    return Number.isNaN(n) || n < 1 ? f : n;
  };

  const stateFromUrl = () => {
    const p = new URLSearchParams(window.location.search);
    const sortBy = trim(p.get('sort_by'));
    const sortDir = trim(p.get('sort_dir'));
    const query = trim(p.get('q'));
    const hasExplicitSort = allowedSortBy.has(sortBy);

    return {
      q: query,
      page: intOr(p.get('page'), 1),
      sort_by: query.length >= 2 && !hasExplicitSort ? 'relevance' : (hasExplicitSort ? sortBy : 'employee_name'),
      sort_dir: allowedSortDir.has(sortDir) ? sortDir : 'asc',
      employment_status: ['active', 'inactive'].includes(trim(p.get('employment_status'))) ? trim(p.get('employment_status')) : '',
      salary_basis_type: ['daily', 'weekly', 'monthly', 'manual'].includes(trim(p.get('salary_basis_type'))) ? trim(p.get('salary_basis_type')) : '',
    };
  };

  const s = stateFromUrl();

  const params = () => {
    const out = {
      page: String(s.page),
      per_page: '10',
      sort_dir: s.sort_dir,
    };

    if (s.sort_by !== 'relevance') out.sort_by = s.sort_by;

    if (s.q) out.q = s.q;
    if (s.employment_status) out.employment_status = s.employment_status;
    if (s.salary_basis_type) out.salary_basis_type = s.salary_basis_type;

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
    sum.textContent = `Menampilkan ${from} sampai ${Math.min(page * perPage, total)} dari ${total} karyawan`;
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

  const renderSalary = (row) => {
    if (row.default_salary_amount_formatted === null || row.default_salary_amount_formatted === undefined) {
      return '-';
    }

    return `Rp${esc(row.default_salary_amount_formatted)}`;
  };

  const renderRows = (rows, m) => {
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Belum ada data karyawan.</td></tr>';
      return;
    }

    body.innerHTML = rows.map((row, i) => `
      <tr>
        <td>${(m.page - 1) * m.per_page + i + 1}</td>
        <td>${esc(row.employee_name)}</td>
        <td>${esc(row.phone ?? '-')}</td>
        <td>${renderSalary(row)}</td>
        <td>${esc(row.salary_basis_label)}</td>
        <td>${esc(row.employment_status_label)}</td>
        <td class="text-center">
          <button
            type="button"
            class="btn btn-sm btn-light-primary js-open-employee-action"
            data-employee-id="${esc(row.id)}"
            data-employee-name="${esc(row.employee_name)}"
            data-salary-basis-label="${esc(row.salary_basis_label)}"
            data-employment-status-label="${esc(row.employment_status_label)}"
            data-debt-detail-id="${esc(row.debt_detail_id ?? '')}"
          >
            Aksi
          </button>
        </td>
      </tr>
    `).join('');
  };

  const load = async (replace = false) => {
    activeController?.abort();
    const controller = new AbortController();
    activeController = controller;
    const current = ++req;
    body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Memuat data...</td></tr>';

    try {
      const res = await fetch(`${c.endpoint}?${paramsString()}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal,
      });
      const json = await res.json();
      if (current !== req) return;
      if (!res.ok || !json.success) throw new Error('employee-table-response');

      renderRows(json.data.rows || [], json.data.meta || {});
      renderSummary(json.data.meta || {});
      renderPager(json.data.meta || {});
      renderSort();
      updateUrl(replace);
    } catch (error) {
      if (error?.name === 'AbortError' || current !== req) return;
      body.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
      sum.textContent = 'Menampilkan 0 sampai 0 dari 0 karyawan';
      pag.innerHTML = '';
    } finally {
      if (activeController === controller) activeController = null;
    }
  };

  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    const value = trim(q?.value);

    if (value.length < 2) {
      s.q = '';
      if (s.sort_by === 'relevance') { s.sort_by = 'employee_name'; s.sort_dir = 'asc'; }
      s.page = 1;
      load();
      return;
    }

    if (value.length >= 2) {
      s.q = value;
      s.sort_by = 'relevance';
      s.sort_dir = 'asc';
      s.page = 1;
      load();
    }
  });

  q?.addEventListener('input', () => {
    const value = trim(q.value);
    clearTimeout(timer);

    if (value.length < 2) {
      s.q = '';
      if (s.sort_by === 'relevance') { s.sort_by = 'employee_name'; s.sort_dir = 'asc'; }
      s.page = 1;
      timer = setTimeout(() => load(), 160);
      return;
    }

    timer = setTimeout(() => {
      s.q = value;
      s.sort_by = 'relevance';
      s.sort_dir = 'asc';
      s.page = 1;
      load();
    }, 220);
  });

  document.querySelectorAll('[data-sort-by]').forEach((b) => b.addEventListener('click', () => {
    const key = b.dataset.sortBy;
    s.sort_dir = s.sort_by === key && s.sort_dir === 'asc' ? 'desc' : 'asc';
    s.sort_by = key;
    s.page = 1;
    load();
  }));

  const syncControls = () => {
    q.value = s.q;
    if (filterForm?.elements.employment_status) filterForm.elements.employment_status.value = s.employment_status;
    if (filterForm?.elements.salary_basis_type) filterForm.elements.salary_basis_type.value = s.salary_basis_type;
  };
  const drawFilter = (open) => { filterDrawer?.classList.toggle('d-none', !open); filterBackdrop?.classList.toggle('d-none', !open); };
  $('open-employee-filter')?.addEventListener('click', () => drawFilter(true));
  $('close-employee-filter')?.addEventListener('click', () => drawFilter(false));
  filterBackdrop?.addEventListener('click', () => drawFilter(false));
  filterForm?.addEventListener('submit', (e) => { e.preventDefault(); s.employment_status = filterForm.elements.employment_status.value; s.salary_basis_type = filterForm.elements.salary_basis_type.value; s.page = 1; drawFilter(false); load(); });
  $('reset-employee-filter')?.addEventListener('click', () => { s.employment_status = ''; s.salary_basis_type = ''; s.page = 1; syncControls(); drawFilter(false); load(); });

  pag?.addEventListener('click', (e) => {
    const b = e.target.closest('[data-page]');
    if (!b) return;
    s.page = Number(b.dataset.page);
    load();
  });

  window.addEventListener('popstate', () => { Object.assign(s, stateFromUrl()); syncControls(); load(true); });

  syncControls();
  load(true);
})();
