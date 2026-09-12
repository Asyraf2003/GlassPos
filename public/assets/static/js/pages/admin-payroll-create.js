(() => {
  const c = window.payrollCreateConfig;
  if (!c || !Array.isArray(c.employees)) return;

  const $ = (id) => document.getElementById(id);

  const form = $('payroll-create-form');
  const employeeId = $('employee_id');
  const employeeSearch = $('employee_search');
  const results = $('payroll-employee-search-results');
  const selected = $('payroll-selected-employee');
  const selectedCard = $('payroll-selected-card');
  const removeEmployee = $('payroll-employee-remove');
  const amount = $('amount_display');
  const date = $('disbursement_date_string');
  const mode = $('mode_value');
  const notes = $('notes');

  if (!form || !employeeId || !employeeSearch || !results || !selected || !amount || !date || !mode || !notes) {
    return;
  }

  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (m) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  }[m]));

  const norm = (v) => String(v ?? '').trim().toLowerCase();

  const formatMoney = (value) => {
    const num = Number.parseInt(String(value ?? ''), 10);
    if (Number.isNaN(num) || num < 1) return '-';
    return `Rp${num.toLocaleString('id-ID')}`;
  };

  const basisLabel = (value) => {
    switch (String(value ?? '')) {
      case 'daily':
        return 'Harian';
      case 'weekly':
        return 'Mingguan';
      case 'monthly':
        return 'Bulanan';
      case 'manual':
        return 'Manual';
      default:
        return String(value ?? '-') || '-';
    }
  };

  const focusField = (field) => {
    if (!field) return;

    window.requestAnimationFrame(() => {
      field.focus();

      if (typeof field.showPicker === 'function' && field.type === 'date') {
        try {
          field.showPicker();
        } catch (_) {
        }
      }

      if (typeof field.select === 'function' && field.tagName !== 'SELECT') {
        field.select();
      }
    });
  };

  const employees = c.employees.map((employee) => ({
    id: String(employee.id ?? ''),
    employee_name: String(employee.employee_name ?? ''),
    phone: employee.phone ?? null,
    salary_basis_type: employee.salary_basis_type ?? null,
    default_salary_amount: employee.default_salary_amount ?? null,
  }));

  let filtered = [];
  let activeIndex = 0;
  let timer = null;

  const hideResults = () => {
    filtered = [];
    activeIndex = 0;
    results.innerHTML = '';
    results.classList.add('d-none');
  };

  const renderSelected = (employee) => {
    selectedCard?.classList.toggle('d-none', !employee);
    employeeSearch.classList.toggle('d-none', Boolean(employee));
    if (!employee) {
      selected.innerHTML = 'Belum ada karyawan dipilih.';
      selected.classList.add('text-muted');
      return;
    }

    selected.classList.remove('text-muted');
    selected.innerHTML = `
      <div class="fw-semibold mb-1">${esc(employee.employee_name)}</div>
      <div class="small text-muted mb-1">Telepon: ${esc(employee.phone ?? '-')}</div>
      <div class="small text-muted mb-1">Basis Gaji: ${esc(basisLabel(employee.salary_basis_type))}</div>
      <div class="small text-muted">Default Gaji: ${esc(formatMoney(employee.default_salary_amount))}</div>
    `;
  };

  const renderResults = () => {
    if (!filtered.length) {
      results.innerHTML = '<div class="list-group-item text-muted">Karyawan tidak ditemukan.</div>';
      results.classList.remove('d-none');
      return;
    }

    results.innerHTML = filtered.map((employee, index) => `
      <button
        type="button"
        class="list-group-item list-group-item-action ${index === activeIndex ? 'active' : ''}"
        data-index="${index}"
      >
        <div class="fw-semibold">${esc(employee.employee_name)}</div>
        <div class="small ${index === activeIndex ? 'text-white' : 'text-muted'}">
          ${esc(employee.phone ?? '-')} · ${esc(basisLabel(employee.salary_basis_type))} · ${esc(formatMoney(employee.default_salary_amount))}
        </div>
      </button>
    `).join('');

    results.classList.remove('d-none');
  };

  const selectEmployee = (employee) => {
    window.clearTimeout(timer);
    employeeId.value = employee.id;
    employeeSearch.value = '';
    renderSelected(employee);
    hideResults();
    focusField(amount);
  };

  const runSearch = () => {
    if (employeeId.value) return;
    const query = norm(employeeSearch.value);

    if (query.length < 2) {
      filtered = [];
      activeIndex = 0;
      hideResults();
      return;
    }

    filtered = employees.filter((employee) => {
      const haystack = [
        employee.employee_name,
        employee.phone,
        employee.salary_basis_type,
      ].map(norm).join(' ');
      return haystack.includes(query);
    }).slice(0, 8);

    activeIndex = 0;
    renderResults();
  };

  const queueSearch = () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(runSearch, 250);
  };

  const moveFocusOnEnter = (current, next) => {
    current.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter') return;
      if (e.altKey || e.metaKey || e.shiftKey || e.ctrlKey) return;
      e.preventDefault();
      focusField(next);
    });
  };

  employeeSearch.addEventListener('input', queueSearch);
  removeEmployee?.addEventListener('click', () => {
    window.clearTimeout(timer);
    employeeId.value = '';
    employeeSearch.value = '';
    renderSelected(null);
    hideResults();
    focusField(employeeSearch);
  });

  employeeSearch.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown' && filtered.length) {
      e.preventDefault();
      activeIndex = Math.min(activeIndex + 1, filtered.length - 1);
      renderResults();
      return;
    }

    if (e.key === 'ArrowUp' && filtered.length) {
      e.preventDefault();
      activeIndex = Math.max(activeIndex - 1, 0);
      renderResults();
      return;
    }

    if (e.key === 'Enter') {
      if (!filtered.length) {
        e.preventDefault();
        return;
      }

      e.preventDefault();
      selectEmployee(filtered[activeIndex]);
    }

    if (e.key === 'Escape') {
      window.clearTimeout(timer);
      hideResults();
    }
  });

  results.addEventListener('click', (e) => {
    const button = e.target.closest('[data-index]');
    if (!button) return;

    const index = Number.parseInt(button.dataset.index ?? '', 10);
    if (Number.isNaN(index) || !filtered[index]) return;

    selectEmployee(filtered[index]);
  });

  moveFocusOnEnter(amount, date);
  moveFocusOnEnter(date, mode);
  moveFocusOnEnter(mode, notes);

  notes.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    if (e.altKey || e.metaKey || e.shiftKey || e.ctrlKey) return;
    e.preventDefault();

    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
      return;
    }

    form.submit();
  });

  const oldEmployee = employees.find((employee) => employee.id === employeeId.value);
  if (oldEmployee) {
    employeeSearch.value = '';
    renderSelected(oldEmployee);
    focusField(amount);
    return;
  }

  renderSelected(null);
  focusField(employeeSearch);
})();
