(() => {
  const c = window.expenseTableConfig;
  if (!c) return;

  const defaults = {
    q: "",
    page: 1,
    sort_by: "expense_date",
    sort_dir: "desc",
    category_id: "",
    date_from: "",
    date_to: ""
  };

  const allowedSortBy = new Set(["expense_date", "category_name_snapshot", "description", "amount_rupiah", "payment_method"]);
  const allowedSortDir = new Set(["asc", "desc"]);

  const $ = (id) => document.getElementById(id);
  const body = $("expense-table-body");
  const pager = $("expense-table-pagination");
  const summary = $("expense-table-summary");
  const searchForm = $("expense-search-form");
  const searchInput = $("expense-search-input");
  const filterForm = $("expense-filter-form");
  const drawer = $("expense-filter-drawer");
  const backdrop = $("expense-filter-backdrop");
  const enhancedWrap = $("expense-date-enhanced-wrap");
  const enhancedInput = $("expense-date-range");
  const fallbackWrap = $("expense-date-fallback-wrap");
  const fallbackFromInput = $("expense-date-fallback-from");
  const fallbackToInput = $("expense-date-fallback-to");
  const hiddenFromInput = $("filter-date-from");
  const hiddenToInput = $("filter-date-to");

  const deleteModalElement = $("expense-delete-modal");
  const deleteModalSubtitle = $("expense-delete-modal-subtitle");
  const deleteForm = $("expense-delete-form");
  const deleteModal = deleteModalElement && window.bootstrap && window.bootstrap.Modal
    ? new window.bootstrap.Modal(deleteModalElement)
    : null;

  let searchDebounceTimer = null;
  let requestCounter = 0;
  let activeController = null;

  const esc = (v) => String(v ?? "").replace(/[&<>\"']/g, (m) => ({

    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
  }[m]));

  const tanggalId = (value) => {
    if (value === null || value === undefined || value === "") {
      return "-";
    }

    const text = String(value);

    if (/^\d{2}\/\d{2}\/\d{4}/.test(text)) {
      return text;
    }

    const match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!match) {
      return text;
    }

    return `${match[3]}/${match[2]}/${match[1]}`;
  };


  const rupiah = (v) => "Rp " + Number(v || 0).toLocaleString("id-ID");
  const trimValue = (v) => String(v ?? "").trim();
  const deleteUrl = (id) => String(c.deleteUrlTemplate || "").replace("__EXPENSE_ID__", encodeURIComponent(String(id)));

  const intOrDefault = (v, fallback) => {
    const n = Number.parseInt(String(v ?? ""), 10);
    return Number.isNaN(n) || n < 1 ? fallback : n;
  };

  const stateFromUrl = () => {
    const p = new URLSearchParams(window.location.search);
    const sortBy = trimValue(p.get("sort_by"));
    const sortDir = trimValue(p.get("sort_dir"));

    const query = trimValue(p.get("q"));
    const hasExplicitSort = allowedSortBy.has(sortBy);

    return {
      q: query,
      page: intOrDefault(p.get("page"), 1),
      sort_by: query.length >= 2 && !hasExplicitSort ? "relevance" : (hasExplicitSort ? sortBy : defaults.sort_by),
      sort_dir: allowedSortDir.has(sortDir) ? sortDir : defaults.sort_dir,
      category_id: trimValue(p.get("category_id")),
      date_from: trimValue(p.get("date_from")),
      date_to: trimValue(p.get("date_to"))
    };
  };

  const s = stateFromUrl();

  const syncHiddenDatesFromState = () => {
    if (hiddenFromInput) hiddenFromInput.value = s.date_from;
    if (hiddenToInput) hiddenToInput.value = s.date_to;
  };

  const syncFallbackDatesFromState = () => {
    if (fallbackFromInput) fallbackFromInput.value = s.date_from;
    if (fallbackToInput) fallbackToInput.value = s.date_to;
  };

  const syncStateFromFallbackDates = () => {
    s.date_from = trimValue(fallbackFromInput?.value);
    s.date_to = trimValue(fallbackToInput?.value);
    syncHiddenDatesFromState();
  };

  const updateDateUiMode = () => {
    const enhancedReady = Boolean(enhancedInput && enhancedInput._flatpickr);

    if (enhancedWrap) enhancedWrap.classList.toggle("d-none", !enhancedReady);
    if (fallbackWrap) fallbackWrap.classList.toggle("d-none", enhancedReady);
  };

  const refreshDateUi = () => {
    if (filterForm) window.AdminDateInput?.refreshWithin(filterForm);
    updateDateUiMode();
  };

  const syncInputsFromState = () => {
    if (searchInput) searchInput.value = s.q;

    if (filterForm) {
      if (filterForm.elements["category_id"]) {
        filterForm.elements["category_id"].value = s.category_id;
      }

      syncHiddenDatesFromState();
      syncFallbackDatesFromState();
      refreshDateUi();
    }
  };

  const paramsObject = () => {
    const obj = {
      page: String(s.page),
      per_page: "10",
      sort_dir: s.sort_dir
    };

    if (s.sort_by !== "relevance") obj.sort_by = s.sort_by;

    ["q", "category_id", "date_from", "date_to"].forEach((k) => {
      if (s[k]) obj[k] = s[k];
    });

    return obj;
  };

  const paramsString = () => new URLSearchParams(paramsObject()).toString();

  const updateUrl = (replace = false) => {
    const url = new URL(window.location.href);
    url.search = paramsString();

    if (replace) {
      window.history.replaceState(null, "", url);
      return;
    }

    window.history.pushState(null, "", url);
  };

  const drawOpen = (open) => {
    if (drawer) drawer.classList.toggle("d-none", !open);
    if (backdrop) backdrop.classList.toggle("d-none", !open);
  };

  const configureDeleteModal = (expense) => {
    if (!deleteForm || !deleteModalSubtitle) {
      return;
    }

    deleteForm.action = deleteUrl(expense.id);
    deleteModalSubtitle.textContent = `${expense.category_name} • ${tanggalId(expense.expense_date)} • ${rupiah(expense.amount_rupiah)}`;
  };

  const rowHtml = (r, i, meta) => `
    <tr>
      <td>${(meta.page - 1) * meta.per_page + i + 1}</td>
      <td class="text-nowrap">${esc(tanggalId(r.expense_date))}</td>
      <td>${esc(r.category_name)}<br><small class="text-muted">${esc(r.category_code)}</small></td>
      <td>${esc(r.description)}</td>
      <td class="text-nowrap fw-bold">${rupiah(r.amount_rupiah)}</td>
      <td>${esc(r.payment_method)}</td>
      <td>
        <button
          type="button"
          class="btn btn-sm btn-light-danger"
          data-expense-delete-open="1"
          data-expense-id="${esc(r.id)}"
          data-expense-category-name="${esc(r.category_name)}"
          data-expense-date="${esc(r.expense_date)}"
          data-expense-amount="${esc(r.amount_rupiah)}"
        >
          Hapus
        </button>
      </td>
    </tr>
  `;

  const renderRows = (rows, meta) => {
    if (!rows.length) {
      body.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada pengeluaran yang cocok.</td></tr>`;
      return;
    }

    body.innerHTML = rows.map((r, i) => rowHtml(r, i, meta)).join("");
  };

  const renderPager = (meta) => {
    if (meta.last_page <= 1) {
      pager.innerHTML = "";
      return;
    }

    const start = Math.max(1, meta.page - 2);
    const end = Math.min(meta.last_page, meta.page + 2);

    let html = `<nav><ul class="pagination pagination-primary mb-0">`;
    html += `<li class="page-item ${meta.page === 1 ? "disabled" : ""}"><a class="page-link" href="#" data-page="${meta.page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;

    for (let p = start; p <= end; p++) {
      html += `<li class="page-item ${p === meta.page ? "active" : ""}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
    }

    html += `<li class="page-item ${meta.page === meta.last_page ? "disabled" : ""}"><a class="page-link" href="#" data-page="${meta.page + 1}"><i class="bi bi-chevron-right"></i></a></li></ul></nav>`;
    pager.innerHTML = html;
  };

  const renderSummary = (meta) => {
    const total = Number(meta.total || 0), page = Number(meta.page || 1), perPage = Number(meta.per_page || 10);
    const from = total ? ((page - 1) * perPage) + 1 : 0;
    summary.textContent = `Menampilkan ${from} sampai ${Math.min(page * perPage, total)} dari ${total} pengeluaran`;
  };

  const renderSortIndicators = () => {
    document.querySelectorAll("[data-sort-indicator]").forEach((node) => {
      const key = node.dataset.sortIndicator;

      if (key === s.sort_by) {
        node.textContent = s.sort_dir === "asc" ? "↑" : "↓";
        node.classList.remove("text-muted");
      } else {
        node.textContent = "↕";
        node.classList.add("text-muted");
      }
    });
  };

  const load = async (replaceUrl = false) => {
    activeController?.abort();
    const controller = new AbortController();
    activeController = controller;
    const currentRequest = ++requestCounter;
    body.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Memuat data...</td></tr>`;

    try {
      const res = await fetch(`${c.endpoint}?${paramsString()}`, {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        signal: controller.signal
      });
      const json = await res.json();
      if (currentRequest !== requestCounter) return;
      if (!res.ok || !json.success) throw new Error("expense-table-response");

      renderRows(json.data.rows || [], json.data.meta || {});
      renderSummary(json.data.meta || {});
      renderPager(json.data.meta || {});
      renderSortIndicators();
      syncInputsFromState();
      updateUrl(replaceUrl);
    } catch (error) {
      if (error.name === "AbortError" || currentRequest !== requestCounter) return;
      body.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat data.</td></tr>`;
      pager.innerHTML = "";
      summary.textContent = "Data pengeluaran gagal dimuat.";
    }
  };

  searchForm?.addEventListener("submit", (e) => {
    e.preventDefault();

    const value = trimValue(searchInput?.value);

    if (value.length === 0) {
      s.q = "";
      if (s.sort_by === "relevance") { s.sort_by = defaults.sort_by; s.sort_dir = defaults.sort_dir; }
      s.page = 1;
      load();
      return;
    }

    if (value.length >= 2) {
      s.q = value;
      s.sort_by = "relevance";
      s.page = 1;
      load();
    }
  });

  searchInput?.addEventListener("input", () => {
    const value = trimValue(searchInput?.value);

    clearTimeout(searchDebounceTimer);

    if (value.length < 2) {
      s.q = "";
      if (s.sort_by === "relevance") { s.sort_by = defaults.sort_by; s.sort_dir = defaults.sort_dir; }
      s.page = 1;
      searchDebounceTimer = setTimeout(() => load(), 160);
      return;
    }

    searchDebounceTimer = setTimeout(() => {
      s.q = value;
      s.sort_by = "relevance";
      s.page = 1;
      load();
    }, 220);
  });

  [fallbackFromInput, fallbackToInput].forEach((input) => {
    input?.addEventListener("input", syncStateFromFallbackDates);
    input?.addEventListener("change", syncStateFromFallbackDates);
  });

  $("open-expense-filter")?.addEventListener("click", () => {
    drawOpen(true);
    refreshDateUi();
  });

  $("close-expense-filter")?.addEventListener("click", () => drawOpen(false));
  backdrop?.addEventListener("click", () => drawOpen(false));

  filterForm?.addEventListener("submit", (e) => {
    e.preventDefault();

    if (fallbackWrap && !fallbackWrap.classList.contains("d-none")) {
      syncStateFromFallbackDates();
    }

    const f = new FormData(filterForm);

    ["category_id", "date_from", "date_to"].forEach((k) => {
      s[k] = trimValue(f.get(k));
    });

    s.page = 1;
    drawOpen(false);
    load();
  });

  $("reset-expense-filter")?.addEventListener("click", () => {
    filterForm?.reset();

    ["category_id", "date_from", "date_to"].forEach((k) => {
      s[k] = "";
    });

    syncInputsFromState();
    s.page = 1;
    drawOpen(false);
    load();
  });

  document.querySelector("#expense-table thead")?.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-sort-by]");
    if (!btn) return;

    const key = btn.dataset.sortBy;
    s.sort_dir = s.sort_by === key && s.sort_dir === "asc" ? "desc" : "asc";
    s.sort_by = key;
    s.page = 1;
    load();
  });

  body?.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-expense-delete-open='1']");
    if (!btn) return;

    const expense = {
      id: btn.dataset.expenseId || "",
      category_name: btn.dataset.expenseCategoryName || "",
      expense_date: btn.dataset.expenseDate || "",
      amount_rupiah: btn.dataset.expenseAmount || "0",
    };

    configureDeleteModal(expense);

    if (deleteModal) {
      deleteModal.show();
      return;
    }

    if (deleteForm) {
      deleteForm.action = deleteUrl(expense.id);
    }
  });

  pager?.addEventListener("click", (e) => {
    const link = e.target.closest("[data-page]");
    if (!link || link.parentElement.classList.contains("disabled")) return;

    e.preventDefault();
    s.page = Number(link.dataset.page || 1);
    load();
  });

  window.addEventListener("popstate", () => {
    Object.assign(s, stateFromUrl());
    syncInputsFromState();
    renderSortIndicators();
    load(true);
  });

  syncInputsFromState();
  renderSortIndicators();
  load(true);
})();
