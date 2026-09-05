(() => {
  const c = window.supplierTableConfig;
  if (!c) return;

  const defaults = {
    q: "",
    page: 1,
    sort_by: "nama_pt_pengirim",
    sort_dir: "asc",
    status: "all"
  };

  const allowedSortBy = new Set([
    "nama_pt_pengirim",
    "invoice_count",
    "outstanding_rupiah",
    "invoice_unpaid_count",
    "last_shipment_date"
  ]);
  const allowedSortDir = new Set(["asc", "desc"]);

  const $ = (id) => document.getElementById(id);
  const body = $("supplier-table-body");
  const pager = $("supplier-table-pagination");
  const summary = $("supplier-table-summary");
  const searchForm = $("supplier-search-form");
  const searchInput = $("supplier-search-input");
  const filterForm = $("supplier-filter-form");
  const filterDrawer = $("supplier-filter-drawer");
  const filterBackdrop = $("supplier-filter-backdrop");

  const editModalElement = $("supplier-edit-modal");
  const editModalSubtitle = $("supplier-edit-modal-subtitle");
  const editForm = $("supplier-edit-form");
  const editSupplierIdInput = $("supplier-edit-supplier-id");
  const editNamaPtInput = $("supplier-edit-nama-pt-pengirim");
  const editFormAlert = $("supplier-edit-form-alert");
  const editNamaPtError = $("supplier-edit-nama-pt-error");

  const editModal = editModalElement && window.bootstrap && window.bootstrap.Modal
    ? new window.bootstrap.Modal(editModalElement)
    : null;

  let searchDebounceTimer = null;
  let requestCounter = 0;
  let activeController = null;
  let lastLoadedRows = [];

  const esc = (v) => String(v ?? "").replace(/[&<>\"']/g, (m) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
  }[m]));

  const rupiah = (v) => "Rp " + Number(v || 0).toLocaleString("id-ID");
  const angka = (v) => Number(v || 0).toLocaleString("id-ID");
  const trimValue = (v) => String(v ?? "").trim();

  const tanggalId = (v) => {
    if (!v) {
      return "-";
    }

    const date = new Date(`${v}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
      return esc(v);
    }

    return new Intl.DateTimeFormat("id-ID", {
      day: "2-digit",
      month: "short",
      year: "numeric"
    }).format(date);
  };

  const intOrDefault = (v, fallback) => {
    const n = Number.parseInt(String(v ?? ""), 10);
    return Number.isNaN(n) || n < 1 ? fallback : n;
  };

  const editPageUrl = (supplierId) =>
    `${String(c.editBaseUrl || "").replace(/\/$/, "")}/${encodeURIComponent(supplierId)}/edit`;

  const updateUrl = (supplierId) =>
    String(c.updateUrlTemplate || "").replace("__ID__", encodeURIComponent(supplierId));

  const setFormError = (message) => {
    if (!editFormAlert || !editNamaPtInput || !editNamaPtError) {
      return;
    }

    const normalized = trimValue(message);

    if (normalized === "") {
      editFormAlert.textContent = "";
      editFormAlert.classList.add("d-none");
      editNamaPtInput.classList.remove("is-invalid");
      editNamaPtError.textContent = "";
      editNamaPtError.classList.add("d-none");
      return;
    }

    editFormAlert.textContent = normalized;
    editFormAlert.classList.remove("d-none");
    editNamaPtInput.classList.add("is-invalid");
    editNamaPtError.textContent = normalized;
    editNamaPtError.classList.remove("d-none");
  };

  const focusEditInput = () => {
    if (!editNamaPtInput) {
      return;
    }

    window.requestAnimationFrame(() => {
      editNamaPtInput.focus();
      editNamaPtInput.select();
    });
  };

  const openEditModal = (supplierId, supplierName, preserveOldInput = false) => {
    if (!editModal || !editForm || !editSupplierIdInput || !editNamaPtInput) {
      window.location.assign(editPageUrl(supplierId));
      return;
    }

    editForm.action = updateUrl(supplierId);
    editSupplierIdInput.value = supplierId;

    if (editModalSubtitle) {
      editModalSubtitle.textContent = trimValue(supplierName) === ""
        ? "Ubah nama PT pemasok langsung dari daftar."
        : `Ubah nama PT untuk ${supplierName}.`;
    }

    if (!preserveOldInput) {
      editNamaPtInput.value = supplierName;
      setFormError("");
    }

    editModal.show();
    focusEditInput();
  };

  const stateFromUrl = () => {
    const p = new URLSearchParams(window.location.search);

    const sortBy = trimValue(p.get("sort_by"));
    const sortDir = trimValue(p.get("sort_dir"));

    return {
      q: trimValue(p.get("q")),
      page: intOrDefault(p.get("page"), 1),
      sort_by: allowedSortBy.has(sortBy) ? sortBy : defaults.sort_by,
      sort_dir: allowedSortDir.has(sortDir) ? sortDir : defaults.sort_dir,
      status: ["outstanding", "settled"].includes(trimValue(p.get("status"))) ? trimValue(p.get("status")) : defaults.status
    };
  };

  const s = stateFromUrl();

  const syncInputsFromState = () => {
    searchInput.value = s.q;
    if (filterForm?.elements.status) filterForm.elements.status.value = s.status;
  };

  const paramsObject = () => {
    const obj = {
      page: String(s.page),
      per_page: "10",
      sort_by: s.sort_by,
      sort_dir: s.sort_dir,
      status: s.status
    };

    if (s.q) obj.q = s.q;

    return obj;
  };

  const paramsString = () => new URLSearchParams(paramsObject()).toString();

  const updateUrlState = (replace = false) => {
    const url = new URL(window.location.href);
    url.search = paramsString();

    if (replace) {
      window.history.replaceState(null, "", url);
      return;
    }

    window.history.pushState(null, "", url);
  };

  const rowHtml = (r, i, meta) => `
    <tr>
      <td>${(meta.page - 1) * meta.per_page + i + 1}</td>
      <td>${esc(r.nama_pt_pengirim)}</td>
      <td class="text-end">${angka(r.invoice_count)}</td>
      <td class="text-end">${rupiah(r.outstanding_rupiah)}</td>
      <td class="text-end">${angka(r.invoice_unpaid_count)}</td>
      <td>${tanggalId(r.last_shipment_date)}</td>
      <td>
        <button
          type="button"
          class="btn btn-sm btn-outline-primary"
          data-supplier-action="edit"
          data-supplier-id="${esc(r.id)}"
          data-supplier-name="${esc(r.nama_pt_pengirim)}"
        >
          Edit
        </button>
      </td>
    </tr>
  `;

  const renderRows = (rows, meta) => {
    if (!rows.length) {
      body.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada supplier yang cocok.</td></tr>`;
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
    const total = Number(meta.total || 0);
    const from = total === 0 ? 0 : ((Number(meta.page || 1) - 1) * Number(meta.per_page || 10)) + 1;
    const to = Math.min(Number(meta.page || 1) * Number(meta.per_page || 10), total);
    summary.textContent = `Menampilkan ${from} sampai ${to} dari ${total} pemasok`;
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

  const maybeRestoreFailedEditModal = () => {
    if (!c.hasUpdateErrors || !c.oldSupplierId) {
      return;
    }

    const failedId = String(c.oldSupplierId);
    const failedRow = lastLoadedRows.find((row) => String(row.id) === failedId);
    const supplierName = failedRow
      ? trimValue(failedRow.nama_pt_pengirim)
      : trimValue(c.oldNamaPtPengirim);

    openEditModal(failedId, supplierName, true);

    if (editNamaPtInput && trimValue(c.oldNamaPtPengirim) !== "") {
      editNamaPtInput.value = c.oldNamaPtPengirim;
    }

    setFormError(c.updateErrorMessage);
    c.hasUpdateErrors = false;
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
      if (!res.ok || !json.success) throw new Error("supplier-table-response");

      lastLoadedRows = json.data.rows || [];
      renderRows(lastLoadedRows, json.data.meta || {});
      renderSummary(json.data.meta || {});
      renderPager(json.data.meta || {});
      renderSortIndicators();
      syncInputsFromState();
      updateUrlState(replaceUrl);
      maybeRestoreFailedEditModal();
    } catch (error) {
      if (error?.name === "AbortError" || currentRequest !== requestCounter) return;
      body.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat data.</td></tr>`;
      summary.textContent = "Menampilkan 0 sampai 0 dari 0 pemasok";
      pager.innerHTML = "";
    } finally {
      if (activeController === controller) activeController = null;
    }
  };

  searchForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const value = trimValue(searchInput.value);

    if (value.length < 2) {
      s.q = "";
      s.page = 1;
      load();
      return;
    }

    if (value.length >= 2) {
      s.q = value;
      s.page = 1;
      load();
    }
  });

  searchInput.addEventListener("input", () => {
    const value = trimValue(searchInput.value);

    clearTimeout(searchDebounceTimer);

    if (value.length < 2) {
      s.q = "";
      s.page = 1;
      searchDebounceTimer = setTimeout(() => load(), 160);
      return;
    }

    searchDebounceTimer = setTimeout(() => {
      s.q = value;
      s.page = 1;
      load();
    }, 220);
  });

  const drawFilter = (open) => {
    filterDrawer?.classList.toggle("d-none", !open);
    filterBackdrop?.classList.toggle("d-none", !open);
  };
  $("open-supplier-filter")?.addEventListener("click", () => drawFilter(true));
  $("close-supplier-filter")?.addEventListener("click", () => drawFilter(false));
  filterBackdrop?.addEventListener("click", () => drawFilter(false));
  filterForm?.addEventListener("submit", (event) => {
    event.preventDefault();
    s.status = filterForm.elements.status.value;
    s.page = 1;
    drawFilter(false);
    load();
  });
  $("reset-supplier-filter")?.addEventListener("click", () => {
    s.status = "all";
    s.page = 1;
    syncInputsFromState();
    drawFilter(false);
    load();
  });

  document.querySelectorAll("[data-sort-by]").forEach((button) => {
    button.addEventListener("click", () => {
      const key = button.dataset.sortBy;

      if (!allowedSortBy.has(key)) {
        return;
      }

      if (s.sort_by === key) {
        s.sort_dir = s.sort_dir === "asc" ? "desc" : "asc";
      } else {
        s.sort_by = key;
        s.sort_dir = key === "last_shipment_date" ? "desc" : "asc";
      }

      s.page = 1;
      load();
    });
  });

  pager.addEventListener("click", (e) => {
    const link = e.target.closest("[data-page]");
    if (!link) return;

    e.preventDefault();

    const nextPage = intOrDefault(link.dataset.page, s.page);
    if (nextPage === s.page) return;

    s.page = nextPage;
    load();
  });

  body.addEventListener("click", (e) => {
    const button = e.target.closest("[data-supplier-action='edit']");
    if (!button) return;

    const supplierId = trimValue(button.dataset.supplierId);
    const supplierName = trimValue(button.dataset.supplierName);

    if (supplierId === "") {
      return;
    }

    openEditModal(supplierId, supplierName);
  });

  window.addEventListener("popstate", () => {
    const nextState = stateFromUrl();
    s.q = nextState.q;
    s.page = nextState.page;
    s.sort_by = nextState.sort_by;
    s.sort_dir = nextState.sort_dir;
    s.status = nextState.status;
    syncInputsFromState();
    load(true);
  });

  editModalElement?.addEventListener("hidden.bs.modal", () => {
    if (!c.hasUpdateErrors) {
      setFormError("");
    }
  });

  syncInputsFromState();
  load(true);
})();
