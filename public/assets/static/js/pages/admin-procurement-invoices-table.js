(() => {
  const c = window.procurementInvoiceTableConfig;
  if (!c) return;

  const defaults = {
    q: "",
    payment_status: "active",
    page: 1,
    sort_by: "shipment_date",
    sort_dir: "desc",
    shipment_date_from: "",
    shipment_date_to: ""
  };

  const allowedSortBy = new Set([
    "shipment_date",
    "due_date",
    "nama_pt_pengirim",
    "grand_total_rupiah",
    "total_paid_rupiah",
    "outstanding_rupiah",
    "receipt_count",
    "total_received_qty"
  ]);
  const allowedSortDir = new Set(["asc", "desc"]);
  const allowedPaymentStatus = new Set(["active", "all", "outstanding", "paid", "voided"]);

  const $ = (id) => document.getElementById(id);
  const body = $("procurement-invoice-table-body");
  const pager = $("procurement-invoice-table-pagination");
  const summary = $("procurement-invoice-table-summary");
  const activeFilters = $("procurement-active-filters");
  const activeFilterChips = $("procurement-active-filter-chips");
  const resetAllFilters = $("procurement-reset-all-filters");
  const searchForm = $("procurement-search-form");
  const searchInput = $("procurement-search-input");
  const filterForm = $("procurement-filter-form");
  const resetFilter = $("reset-procurement-filter");
  const drawer = $("procurement-filter-drawer");
  const backdrop = $("procurement-filter-backdrop");

  const actionModalElement = $("procurement-action-modal");
  const actionModalSubtitle = $("procurement-action-modal-subtitle");
  const actionDetailLink = $("procurement-action-detail-link");
  const actionPaymentLink = $("procurement-action-payment-link");
  const actionPaymentTitle = $("procurement-action-payment-title");
  const actionEditLink = $("procurement-action-edit-link");
  const actionEditTitle = $("procurement-action-edit-title");
  const actionVoidButton = $("procurement-action-void-button");
  const actionVoidTitle = $("procurement-action-void-title");

  const paymentModalElement = $("procurement-payment-modal");
  const paymentModalSubtitle = $("procurement-payment-modal-subtitle");
  const paymentForm = $("procurement-payment-form");
  const paymentInvoiceIdInput = $("procurement-payment-invoice-id");
  const paymentDateInput = $("procurement-payment-date");
  const paymentAmountRaw = $("procurement-payment-amount");
  const paymentAmountDisplay = $("procurement-payment-amount-display");
  const paymentAmountHelp = $("procurement-payment-amount-help");

  const voidModalElement = $("procurement-void-modal");
  const voidModalSubtitle = $("procurement-void-modal-subtitle");
  const voidForm = $("procurement-void-form");
  const voidInvoiceIdInput = $("procurement-void-invoice-id");
  const voidReasonInput = $("procurement-void-reason");
  const voidSubmitButton = $("procurement-void-submit");

  const actionModal = actionModalElement && window.bootstrap && window.bootstrap.Modal
    ? new window.bootstrap.Modal(actionModalElement)
    : null;

  const paymentModal = paymentModalElement && window.bootstrap && window.bootstrap.Modal
    ? new window.bootstrap.Modal(paymentModalElement)
    : null;

  const voidModal = voidModalElement && window.bootstrap && window.bootstrap.Modal
    ? new window.bootstrap.Modal(voidModalElement)
    : null;

  let searchDebounceTimer = null;
  let requestCounter = 0;
  let pendingPaymentAction = null;
  let pendingVoidAction = null;

  const esc = (v) => String(v ?? "").replace(/[&<>"']/g, (m) => ({

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


  const trimValue = (v) => String(v ?? "").trim();
  const rupiah = (v) => "Rp " + Number(v || 0).toLocaleString("id-ID");
  const detailUrl = (id) => c.detailBaseUrl.replace("__ID__", encodeURIComponent(id));
  const paymentProofStoreUrl = (id) => c.paymentProofStoreBaseUrl.replace("__ID__", encodeURIComponent(id));
  const paymentSectionUrl = (id) => `${detailUrl(id)}#payment-form-section`;

  const intOrDefault = (v, fallback) => {
    const n = Number.parseInt(String(v ?? ""), 10);
    return Number.isNaN(n) || n < 1 ? fallback : n;
  };

  const todayYmd = () => new Date().toISOString().slice(0, 10);

  const setActionLinkDisabledState = (node, disabled) => {
    if (!node) return;
    node.classList.toggle("disabled", disabled);
    node.setAttribute("aria-disabled", disabled ? "true" : "false");
    node.tabIndex = disabled ? -1 : 0;
  };

  const setActionButtonDisabledState = (node, disabled) => {
    if (!node) return;
    node.disabled = disabled;
    node.classList.toggle("disabled", disabled);
    node.setAttribute("aria-disabled", disabled ? "true" : "false");
  };

  const supplierCellHtml = (row) => {
    const currentName = trimValue(row.supplier_nama_pt_pengirim_current);
    const snapshotName = trimValue(row.supplier_nama_pt_pengirim_snapshot);

    const primary = currentName || snapshotName || "-";
    const showSnapshot = snapshotName && currentName && snapshotName !== currentName;

    return `
      <div class="fw-semibold">${esc(primary)}</div>
      ${showSnapshot ? `<div class="small text-muted">saat nota dibuat: ${esc(snapshotName)}</div>` : ""}
    `;
  };

  const invoiceCellHtml = (row) => {
    const nomorFaktur = trimValue(row.nomor_faktur);
    const isVoided = row.policy_state === "voided";

    return `
      <div class="fw-semibold">${esc(nomorFaktur || "-")}</div>
      ${isVoided ? '<div class="mt-1"><span class="badge bg-secondary">Dibatalkan</span></div>' : ""}
    `;
  };

  const activeFilterEntries = () => {
    const entries = [];

    if (trimValue(s.q) !== "") {
      entries.push({ label: "Keyword", value: s.q });
    }

    if (s.payment_status === "outstanding") {
      entries.push({ label: "Status", value: "Masih Punya Tagihan" });
    }

    if (s.payment_status === "paid") {
      entries.push({ label: "Status", value: "Sudah Lunas" });
    }

    if (s.payment_status === "voided") {
      entries.push({ label: "Status", value: "Sudah Dibatalkan" });
    }

    if (s.payment_status === "all") {
      entries.push({ label: "Status", value: "Semua" });
    }

    if (trimValue(s.shipment_date_from) !== "" || trimValue(s.shipment_date_to) !== "") {
      const from = tanggalId(trimValue(s.shipment_date_from)) || "...";
      const to = tanggalId(trimValue(s.shipment_date_to)) || "...";
      entries.push({ label: "Tanggal Kirim", value: `${from} s.d. ${to}` });
    }

    return entries;
  };

  const renderActiveFilters = () => {
    if (!activeFilters || !activeFilterChips) return;

    const entries = activeFilterEntries();

    if (!entries.length) {
      activeFilters.classList.add("d-none");
      activeFilterChips.innerHTML = "";
      return;
    }

    activeFilters.classList.remove("d-none");
    activeFilterChips.innerHTML = entries.map((entry) => `
      <span class="badge bg-light-primary text-primary border px-3 py-2">
        ${esc(entry.label)}: ${esc(entry.value)}
      </span>
    `).join("");
  };

  const stateFromUrl = () => {
    const p = new URLSearchParams(window.location.search);
    const sortBy = trimValue(p.get("sort_by"));
    const sortDir = trimValue(p.get("sort_dir"));
    const paymentStatus = trimValue(p.get("payment_status"));

    return {
      q: trimValue(p.get("q")),
      payment_status: allowedPaymentStatus.has(paymentStatus) ? paymentStatus : defaults.payment_status,
      page: intOrDefault(p.get("page"), 1),
      sort_by: allowedSortBy.has(sortBy) ? sortBy : defaults.sort_by,
      sort_dir: allowedSortDir.has(sortDir) ? sortDir : defaults.sort_dir,
      shipment_date_from: trimValue(p.get("shipment_date_from")),
      shipment_date_to: trimValue(p.get("shipment_date_to"))
    };
  };

  const s = stateFromUrl();

  const syncInputsFromState = () => {
    if (searchInput) {
      searchInput.value = s.q;
    }

    if (filterForm?.elements["payment_status"]) {
      filterForm.elements["payment_status"].value = s.payment_status;
    }

    if (filterForm?.elements["shipment_date_from"]) {
      filterForm.elements["shipment_date_from"].value = s.shipment_date_from;
    }

    if (filterForm?.elements["shipment_date_to"]) {
      filterForm.elements["shipment_date_to"].value = s.shipment_date_to;
    }

    window.AdminDateInput?.refreshWithin(filterForm);
  };

  const paramsObject = () => {
    const obj = {
      page: String(s.page),
      per_page: "10",
      sort_by: s.sort_by,
      sort_dir: s.sort_dir,
      payment_status: s.payment_status
    };

    ["q", "shipment_date_from", "shipment_date_to"].forEach((k) => {
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

  const openPaymentModal = (row, preserveOldInput = false) => {
    if (!paymentForm || !paymentModal) {
      window.location.assign(paymentSectionUrl(row.supplier_invoice_id));
      return;
    }

    const supplierName = trimValue(row.supplier_nama_pt_pengirim_current)
      || trimValue(row.supplier_nama_pt_pengirim_snapshot)
      || "-";
    const nomorFaktur = trimValue(row.nomor_faktur) || "-";

    paymentForm.action = paymentProofStoreUrl(row.supplier_invoice_id);

    if (paymentInvoiceIdInput) {
      paymentInvoiceIdInput.value = row.supplier_invoice_id;
    }

    if (paymentModalSubtitle) {
      paymentModalSubtitle.textContent = `${nomorFaktur} • ${supplierName} • Sisa tagihan ${rupiah(row.outstanding_rupiah || 0)}`;
    }

    if (!preserveOldInput) {
      const proofInput = paymentForm.querySelector("input[type='file']");
      if (proofInput) {
        proofInput.value = "";
      }
    }

    paymentModal.show();
  };

  const syncVoidSubmitState = () => {
    const hasReason = trimValue(voidReasonInput?.value) !== "";
    setActionButtonDisabledState(voidSubmitButton, !hasReason);
  };

  const openVoidModal = (row, preserveOldInput = false) => {
    if (!voidForm || !voidModal) {
      window.location.assign(detailUrl(row.supplier_invoice_id));
      return;
    }

    const supplierName = trimValue(row.supplier_nama_pt_pengirim_current)
      || trimValue(row.supplier_nama_pt_pengirim_snapshot)
      || "-";
    const nomorFaktur = trimValue(row.nomor_faktur) || "-";
    const actionUrl = trimValue(row.void_action_url);

    if (actionUrl === "") {
      return;
    }

    voidForm.action = actionUrl;

    if (voidInvoiceIdInput) {
      voidInvoiceIdInput.value = row.supplier_invoice_id;
    }

    if (voidModalSubtitle) {
      voidModalSubtitle.textContent = `${nomorFaktur} • ${supplierName}`;
    }

    if (!preserveOldInput) {
      if (voidReasonInput) {
        voidReasonInput.value = "";
      }

    }

    syncVoidSubmitState();
    voidModal.show();
  };

  const configureActionModal = (row) => {
    if (
      !actionModalSubtitle ||
      !actionDetailLink ||
      !actionPaymentLink ||
      !actionPaymentTitle ||
      !actionEditLink ||
      !actionEditTitle ||
      !actionVoidButton ||
      !actionVoidTitle
    ) {
      return;
    }

    const supplierName = trimValue(row.supplier_nama_pt_pengirim_current)
      || trimValue(row.supplier_nama_pt_pengirim_snapshot)
      || "-";
    const nomorFaktur = trimValue(row.nomor_faktur) || "-";

    actionModalSubtitle.textContent = `${nomorFaktur} • ${supplierName}`;
    actionDetailLink.href = detailUrl(row.supplier_invoice_id);

    const paymentActionMode = trimValue(row.payment_action_mode) || "link";
    const paymentActionLabel = trimValue(row.payment_action_label) || "Bayar";
    const paymentActionUrl = trimValue(row.payment_action_url) || paymentSectionUrl(row.supplier_invoice_id);
    const paymentActionEnabled = row.payment_action_enabled !== false;

    pendingPaymentAction = paymentActionEnabled
      ? {
          mode: paymentActionMode,
          row,
          url: paymentActionUrl
        }
      : null;

    actionPaymentLink.href = paymentActionEnabled ? paymentActionUrl : "#";
    actionPaymentLink.dataset.actionMode = paymentActionMode;
    actionPaymentTitle.textContent = paymentActionLabel;
    setActionLinkDisabledState(actionPaymentLink, !paymentActionEnabled);

    const editActionLabel = trimValue(row.edit_action_label) || "Edit Nota";
    const editActionUrl = trimValue(row.edit_action_url);

    actionEditTitle.textContent = editActionLabel;
    actionEditLink.href = editActionUrl || "#";
    setActionLinkDisabledState(actionEditLink, editActionUrl === "");

    const voidEnabled = Boolean(row.void_action_enabled);
    actionVoidTitle.textContent = trimValue(row.void_action_label) || "Hapus Nota";
    pendingVoidAction = voidEnabled ? row : null;
    setActionButtonDisabledState(actionVoidButton, !voidEnabled);
  };

  const rowHtml = (row, index, meta) => {
    const isVoided = row.policy_state === "voided";
    const rowClass = isVoided ? "table-secondary text-muted" : "";

    return `
    <tr class="${rowClass}">
      <td>${(meta.page - 1) * meta.per_page + index + 1}</td>
      <td>${invoiceCellHtml(row)}</td>
      <td>${supplierCellHtml(row)}</td>
      <td>${esc(tanggalId(row.shipment_date))}</td>
      <td>${esc(tanggalId(row.due_date))}</td>
      <td>${rupiah(row.grand_total_rupiah)}</td>
      <td>${rupiah(row.total_paid_rupiah)}</td>
      <td>${rupiah(row.outstanding_rupiah)}</td>
      <td>${esc(row.receipt_count)}</td>
      <td>${esc(row.total_received_qty)}</td>
      <td class="text-center">
        <button
          type="button"
          class="btn btn-sm ${isVoided ? "btn-outline-secondary" : "btn-outline-primary"}"
          data-procurement-action="open"
          data-row='${JSON.stringify(row).replace(/'/g, "&apos;")}'
        >
          Aksi
        </button>
      </td>
    </tr>
  `;
  };

  const renderRows = (rows, meta) => {
    if (!rows.length) {
      body.innerHTML = `<tr><td colspan="11" class="text-center text-muted py-4">Tidak ada nota supplier yang cocok.</td></tr>`;
      return;
    }

    body.innerHTML = rows.map((row, index) => rowHtml(row, index, meta)).join("");
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
    summary.textContent = `Total: ${meta.total} nota supplier`;
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
    const currentRequest = ++requestCounter;

    body.innerHTML = `<tr><td colspan="11" class="text-center text-muted py-4">Memuat data...</td></tr>`;

    try {
      const res = await fetch(`${c.endpoint}?${paramsString()}`, {
        headers: { Accept: "application/json" }
      });

      const json = await res.json();

      if (currentRequest !== requestCounter) {
        return;
      }

      if (!res.ok || !json.success) {
        body.innerHTML = `<tr><td colspan="11" class="text-center text-danger py-4">Gagal memuat data.</td></tr>`;
        return;
      }

      const rows = json.data.rows || [];
      const meta = json.data.meta || {};

      renderRows(rows, meta);
      renderSummary(meta);
      renderPager(meta);
      renderSortIndicators();
      syncInputsFromState();
      renderActiveFilters();
      updateUrl(replaceUrl);

      if (c.oldPaymentInvoiceId) {
        const failedPaymentRow = rows.find((row) => row.supplier_invoice_id === c.oldPaymentInvoiceId);
        if (failedPaymentRow) {
          openPaymentModal(failedPaymentRow, true);
          c.oldPaymentInvoiceId = "";
        }
      }

      if (c.oldVoidInvoiceId) {
        const failedVoidRow = rows.find((row) => row.supplier_invoice_id === c.oldVoidInvoiceId);
        if (failedVoidRow) {
          openVoidModal(failedVoidRow, true);
          c.oldVoidInvoiceId = "";
        }
      }
    } catch (_error) {
      if (currentRequest !== requestCounter) {
        return;
      }

      body.innerHTML = `<tr><td colspan="11" class="text-center text-danger py-4">Gagal memuat data.</td></tr>`;
    }
  };

  $("open-procurement-filter")?.addEventListener("click", () => drawOpen(true));
  $("close-procurement-filter")?.addEventListener("click", () => drawOpen(false));
  backdrop?.addEventListener("click", () => drawOpen(false));

  actionPaymentLink?.addEventListener("click", (event) => {
    if (actionPaymentLink.getAttribute("aria-disabled") === "true") {
      event.preventDefault();
      return;
    }

    if (!pendingPaymentAction) {
      event.preventDefault();
      return;
    }

    if (pendingPaymentAction.mode === "modal") {
      event.preventDefault();
      actionModal?.hide();
      openPaymentModal(pendingPaymentAction.row);
    }
  });

  actionEditLink?.addEventListener("click", (event) => {
    if (actionEditLink.getAttribute("aria-disabled") === "true") {
      event.preventDefault();
    }
  });

  actionVoidButton?.addEventListener("click", () => {
    if (!pendingVoidAction) {
      return;
    }

    actionModal?.hide();
    openVoidModal(pendingVoidAction);
  });

  voidReasonInput?.addEventListener("input", syncVoidSubmitState);

  voidForm?.addEventListener("submit", (event) => {
    const hasReason = trimValue(voidReasonInput?.value) !== "";

    if (!hasReason) {
      event.preventDefault();
      syncVoidSubmitState();
    }
  });

  searchForm?.addEventListener("submit", (e) => {
    e.preventDefault();
    const value = trimValue(searchInput?.value);

    if (value.length === 0) {
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

  searchInput?.addEventListener("input", () => {
    const value = trimValue(searchInput.value);
    clearTimeout(searchDebounceTimer);

    if (value.length === 0) {
      s.q = "";
      s.page = 1;
      searchDebounceTimer = setTimeout(() => load(), 250);
      return;
    }

    if (value.length < 2) {
      return;
    }

    searchDebounceTimer = setTimeout(() => {
      s.q = value;
      s.page = 1;
      load();
    }, 300);
  });

  filterForm?.addEventListener("submit", (e) => {
    e.preventDefault();

    const f = new FormData(filterForm);
    const paymentStatus = trimValue(f.get("payment_status"));

    s.payment_status = allowedPaymentStatus.has(paymentStatus) ? paymentStatus : "all";
    s.shipment_date_from = trimValue(f.get("shipment_date_from"));
    s.shipment_date_to = trimValue(f.get("shipment_date_to"));
    s.page = 1;

    drawOpen(false);
    load();
  });

  resetFilter?.addEventListener("click", () => {
    s.payment_status = "all";
    s.shipment_date_from = "";
    s.shipment_date_to = "";
    s.page = 1;
    syncInputsFromState();
    drawOpen(false);
    load();
  });

  resetAllFilters?.addEventListener("click", () => {
    s.q = "";
    s.payment_status = "all";
    s.shipment_date_from = "";
    s.shipment_date_to = "";
    s.page = 1;
    syncInputsFromState();
    load();
  });

  document.querySelector("#procurement-invoice-table thead")?.addEventListener("click", (e) => {
    const button = e.target.closest("[data-sort-by]");
    if (!button) return;

    const key = button.dataset.sortBy;
    if (!allowedSortBy.has(key)) {
      return;
    }

    if (s.sort_by === key) {
      s.sort_dir = s.sort_dir === "asc" ? "desc" : "asc";
    } else {
      s.sort_by = key;
      s.sort_dir = key === "shipment_date" ? "desc" : "asc";
    }

    s.page = 1;
    load();
  });

  pager?.addEventListener("click", (e) => {
    const link = e.target.closest("[data-page]");
    if (!link) return;

    e.preventDefault();

    const nextPage = intOrDefault(link.dataset.page, s.page);
    if (nextPage === s.page) {
      return;
    }

    s.page = nextPage;
    load();
  });

  body?.addEventListener("click", (e) => {
    const button = e.target.closest("[data-procurement-action='open']");
    if (!button) return;

    const raw = button.getAttribute("data-row");
    if (!raw) return;

    const row = JSON.parse(raw.replace(/&apos;/g, "'"));
    configureActionModal(row);

    if (actionModal) {
      actionModal.show();
      return;
    }

    window.location.assign(detailUrl(row.supplier_invoice_id));
  });

  window.addEventListener("popstate", () => {
    Object.assign(s, stateFromUrl());
    load(true);
  });

  if (window.AdminMoneyInput && paymentAmountDisplay && paymentAmountRaw) {
    window.AdminMoneyInput.bindMoneyPair(paymentAmountDisplay, paymentAmountRaw);
  }

  syncInputsFromState();
  renderSortIndicators();
  renderActiveFilters();
  syncVoidSubmitState();
  load(true);
})();
