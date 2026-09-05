(() => {
  "use strict";

  const config = window.AdminServiceTableConfig || {};
  const byId = (id) => document.getElementById(id);
  const body = byId("service-table-body");
  const summary = byId("service-table-summary");
  const pagination = byId("service-table-pagination");
  const searchForm = byId("service-search-form");
  const searchInput = byId("service-search-input");
  const filterForm = byId("service-filter-form");
  const drawer = byId("service-filter-drawer");
  const backdrop = byId("service-filter-backdrop");

  if (!config.endpoint || !body || !summary || !pagination || !searchInput) return;

  const allowedSorts = new Set(["name", "normalized_name", "default_price_rupiah", "is_active"]);
  const trim = (value) => String(value || "").trim();
  const escapeHtml = (value) => String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
  const rupiah = (value) => new Intl.NumberFormat("id-ID").format(Number(value || 0));

  const stateFromUrl = () => {
    const params = new URLSearchParams(window.location.search);
    const sortBy = trim(params.get("sort_by"));
    const status = trim(params.get("status"));

    return {
      q: trim(params.get("q")),
      status: ["active", "inactive"].includes(status) ? status : "all",
      sort_by: allowedSorts.has(sortBy) ? sortBy : "",
      sort_dir: params.get("sort_dir") === "desc" ? "desc" : "asc",
      page: Math.max(1, Number(params.get("page") || 1)),
    };
  };

  let state = stateFromUrl();
  let debounceTimer = null;
  let requestCounter = 0;
  let activeController = null;

  const requestParams = () => {
    const params = new URLSearchParams({
      status: state.status,
      page: String(state.page),
      per_page: "10",
      sort_dir: state.sort_dir,
    });
    if (state.q.length >= 2) params.set("q", state.q);
    if (state.sort_by) params.set("sort_by", state.sort_by);
    return params;
  };

  const syncUrl = (replace = false) => {
    const params = requestParams();
    if (state.status === "all") params.delete("status");
    if (state.page === 1) params.delete("page");
    if (!state.sort_by) params.delete("sort_dir");
    const query = params.toString();
    const url = `${window.location.pathname}${query ? `?${query}` : ""}`;
    window.history[replace ? "replaceState" : "pushState"](null, "", url);
  };

  const fillControls = () => {
    searchInput.value = state.q;
    if (filterForm?.elements.status) filterForm.elements.status.value = state.status;
  };

  const drawOpen = (open) => {
    drawer?.classList.toggle("d-none", !open);
    backdrop?.classList.toggle("d-none", !open);
  };

  const renderSummary = (meta) => {
    const total = Number(meta.total || 0);
    const page = Number(meta.page || 1);
    const perPage = Number(meta.per_page || 10);
    const from = total === 0 ? 0 : ((page - 1) * perPage) + 1;
    const to = Math.min(page * perPage, total);
    summary.textContent = `Menampilkan ${from} sampai ${to} dari ${total} jasa`;
  };

  const renderPagination = (meta) => {
    const page = Number(meta.page || 1);
    const lastPage = Number(meta.last_page || 1);
    if (lastPage <= 1) {
      pagination.innerHTML = "";
      return;
    }
    pagination.innerHTML = `
      <nav aria-label="Pagination jasa"><ul class="pagination pagination-sm mb-0">
        <li class="page-item ${page <= 1 ? "disabled" : ""}"><button class="page-link" type="button" data-page="${page - 1}">Sebelumnya</button></li>
        <li class="page-item disabled"><span class="page-link">${page} / ${lastPage}</span></li>
        <li class="page-item ${page >= lastPage ? "disabled" : ""}"><button class="page-link" type="button" data-page="${page + 1}">Berikutnya</button></li>
      </ul></nav>`;
  };

  const renderSort = () => {
    document.querySelectorAll("[data-sort-indicator]").forEach((node) => {
      node.textContent = node.dataset.sortIndicator === state.sort_by
        ? (state.sort_dir === "asc" ? "↑" : "↓")
        : "↕";
    });
  };

  const renderRows = (rows, meta) => {
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada jasa yang cocok.</td></tr>';
      return;
    }
    body.innerHTML = rows.map((row, index) => {
      const active = Boolean(row.is_active);
      return `<tr>
        <td>${((Number(meta.page) - 1) * Number(meta.per_page)) + index + 1}</td>
        <td class="fw-semibold">${escapeHtml(row.name)}</td>
        <td><small class="text-muted">${escapeHtml(row.normalized_name)}</small></td>
        <td>Rp${rupiah(row.default_price_rupiah)}</td>
        <td><span class="badge ${active ? "bg-success" : "bg-secondary"}">${active ? "Aktif" : "Nonaktif"}</span></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-primary"
          data-service-action="open"
          data-service-name="${escapeHtml(row.name)}"
          data-service-normalized="${escapeHtml(row.normalized_name)}"
          data-service-status="${active ? "active" : "inactive"}"
          data-edit-url="${escapeHtml(row.actions.edit_url)}"
          data-deactivate-url="${escapeHtml(row.actions.deactivate_url)}"
          data-activate-url="${escapeHtml(row.actions.activate_url)}">Aksi</button></td>
      </tr>`;
    }).join("");
  };

  const load = async (replaceUrl = false) => {
    activeController?.abort();
    const controller = new AbortController();
    activeController = controller;
    const currentRequest = ++requestCounter;
    body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Sedang memuat data...</td></tr>';

    try {
      const response = await fetch(`${config.endpoint}?${requestParams()}`, {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        signal: controller.signal,
      });
      const payload = await response.json();
      if (currentRequest !== requestCounter) return;
      if (!response.ok || payload?.success !== true) throw new Error("service-table-response");
      const data = payload.data || {};
      const meta = data.meta || {};
      renderRows(Array.isArray(data.rows) ? data.rows : [], meta);
      renderSummary(meta);
      renderPagination(meta);
      renderSort();
      syncUrl(replaceUrl);
    } catch (error) {
      if (error?.name === "AbortError" || currentRequest !== requestCounter) return;
      body.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
      summary.textContent = "Menampilkan 0 sampai 0 dari 0 jasa";
      pagination.innerHTML = "";
    } finally {
      if (activeController === controller) activeController = null;
    }
  };

  const queueSearch = () => {
    clearTimeout(debounceTimer);
    const value = trim(searchInput.value);
    state.q = value.length >= 2 ? value : "";
    state.page = 1;
    debounceTimer = window.setTimeout(() => load(), value.length >= 2 ? 220 : 160);
  };

  searchForm?.addEventListener("submit", (event) => {
    event.preventDefault();
    queueSearch();
  });
  searchInput.addEventListener("input", queueSearch);
  byId("open-service-filter")?.addEventListener("click", () => drawOpen(true));
  byId("close-service-filter")?.addEventListener("click", () => drawOpen(false));
  backdrop?.addEventListener("click", () => drawOpen(false));
  filterForm?.addEventListener("submit", (event) => {
    event.preventDefault();
    state.status = filterForm.elements.status.value;
    state.page = 1;
    drawOpen(false);
    load();
  });
  byId("reset-service-filter")?.addEventListener("click", () => {
    state.status = "all";
    state.page = 1;
    fillControls();
    drawOpen(false);
    load();
  });
  document.querySelectorAll("[data-sort-by]").forEach((button) => button.addEventListener("click", () => {
    const key = button.dataset.sortBy;
    if (!allowedSorts.has(key)) return;
    state.sort_dir = state.sort_by === key && state.sort_dir === "asc" ? "desc" : "asc";
    state.sort_by = key;
    state.page = 1;
    load();
  }));
  pagination.addEventListener("click", (event) => {
    const button = event.target.closest("[data-page]");
    if (!button || button.closest(".disabled")) return;
    state.page = Number(button.dataset.page || 1);
    load();
  });
  window.addEventListener("popstate", () => {
    state = stateFromUrl();
    fillControls();
    load(true);
  });

  fillControls();
  load(true);
})();
