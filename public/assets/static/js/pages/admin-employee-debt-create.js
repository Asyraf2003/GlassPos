(() => {
  const config = window.employeeDebtCreateConfig;
  const employees = Array.isArray(config?.employees) ? config.employees : [];

  const queryInput = document.getElementById('employee_picker_query');
  const hiddenInput = document.getElementById('employee_id');
  const resultBox = document.getElementById('employee-picker-results');
  const summary = document.getElementById('employee-picker-summary');
  const selectedCard = document.getElementById('employee-picker-selected');
  const removeButton = document.getElementById('employee-picker-remove');
  const debtAmountDisplay = document.getElementById('debt_amount_display');

  if (!queryInput || !hiddenInput || !resultBox || !summary) return;

  const state = {
    matches: [],
    activeIndex: -1,
    selected: null,
  };

  const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  }[char]));

  const norm = (value) => String(value ?? '').trim().toLowerCase();

  const employeeMeta = (employee) => {
    const parts = [];

    if (employee.salary_basis_label) {
      parts.push(employee.salary_basis_label);
    }

    if (employee.default_salary_amount_formatted) {
      parts.push(`Rp${employee.default_salary_amount_formatted}`);
    }

    if (employee.employment_status_label) {
      parts.push(employee.employment_status_label);
    }

    return parts.join(' • ');
  };

  const closeResults = () => {
    resultBox.classList.add('d-none');
    resultBox.innerHTML = '';
    state.activeIndex = -1;
    state.matches = [];
  };

  const focusDebtAmount = () => {
    if (!debtAmountDisplay) return;

    window.requestAnimationFrame(() => {
      debtAmountDisplay.focus();

      if (typeof debtAmountDisplay.select === 'function') {
        debtAmountDisplay.select();
      }
    });
  };

  const renderSummary = (employee) => {
    selectedCard?.classList.toggle('d-none', !employee);
    queryInput.classList.toggle('d-none', Boolean(employee));
    if (!employee) {
      summary.textContent = '';
      return;
    }

    const meta = employeeMeta(employee);
    summary.textContent = meta === '' ? employee.employee_name : `${employee.employee_name} • ${meta}`;
  };

  const selectEmployee = (employee, moveNext = true) => {
    state.selected = employee;
    hiddenInput.value = employee.id;
    queryInput.value = '';
    renderSummary(employee);
    closeResults();

    if (moveNext) {
      focusDebtAmount();
    }
  };

  const renderResults = () => {
    if (!state.matches.length) {
      resultBox.innerHTML = '<div class="list-group-item text-muted">Karyawan tidak ditemukan.</div>';
      resultBox.classList.remove('d-none');
      return;
    }

    resultBox.innerHTML = state.matches.map((employee, index) => {
      const active = index === state.activeIndex;
      const meta = employeeMeta(employee);

      return `
        <button
          type="button"
          class="list-group-item list-group-item-action ${active ? 'active' : ''}"
          data-index="${index}"
        >
          <div class="fw-semibold">${esc(employee.employee_name)}</div>
          <div class="small ${active ? 'text-white-50' : 'text-muted'}">${esc(meta || '-')}</div>
        </button>
      `;
    }).join('');

    resultBox.classList.remove('d-none');
  };

  const findMatches = (keyword) => {
    const q = norm(keyword);

    if (q.length < 2) {
      state.matches = [];
      state.activeIndex = -1;
      closeResults();
      return;
    }

    state.matches = employees
      .filter((employee) => {
        const haystacks = [
          employee.employee_name,
          employee.phone,
          employee.salary_basis_label,
          employee.employment_status_label,
        ];

        return haystacks.some((item) => norm(item).includes(q));
      })
      .slice(0, 8);

    state.activeIndex = state.matches.length ? 0 : -1;
    renderResults();
  };

  removeButton?.addEventListener('click', () => {
    state.selected = null;
    hiddenInput.value = '';
    queryInput.value = '';
    renderSummary(null);
    closeResults();
    queryInput.focus();
  });

  const hydrateFromOldValue = () => {
    const oldId = hiddenInput.value.trim();
    if (oldId === '') return;

    const employee = employees.find((item) => item.id === oldId);
    if (!employee) return;

    selectEmployee(employee, false);
  };

  queryInput.addEventListener('input', () => {
    findMatches(queryInput.value);
  });

  queryInput.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowDown') {
      if (!state.matches.length) return;
      event.preventDefault();
      state.activeIndex = Math.min(state.activeIndex + 1, state.matches.length - 1);
      renderResults();
      return;
    }

    if (event.key === 'ArrowUp') {
      if (!state.matches.length) return;
      event.preventDefault();
      state.activeIndex = Math.max(state.activeIndex - 1, 0);
      renderResults();
      return;
    }

    if (event.key === 'Escape') {
      closeResults();
      return;
    }

    if (event.key !== 'Enter') return;

    event.preventDefault();

    if (state.matches.length && state.activeIndex >= 0) {
      selectEmployee(state.matches[state.activeIndex], true);
      return;
    }

    const exact = employees.find((employee) => norm(employee.employee_name) === norm(queryInput.value));
    if (exact) {
      selectEmployee(exact, true);
    }
  });

  resultBox.addEventListener('mousedown', (event) => {
    const button = event.target.closest('[data-index]');
    if (!button) return;

    event.preventDefault();
    const index = Number.parseInt(button.dataset.index || '-1', 10);

    if (Number.isNaN(index) || !state.matches[index]) return;

    selectEmployee(state.matches[index], true);
  });

  queryInput.addEventListener('blur', () => {
    window.setTimeout(() => closeResults(), 120);
  });

  hydrateFromOldValue();

  window.requestAnimationFrame(() => {
    if (state.selected) { focusDebtAmount(); return; }
    queryInput.focus();

    if (typeof queryInput.select === 'function') {
      queryInput.select();
    }
  });
})();
