(() => {
  const c = window.payrollTableConfig;
  if (!c) return;

  const $ = (id) => document.getElementById(id);
  const form = $("payroll-search-form");
  const q = $("payroll-search-input");
  const body = $("payroll-table-body");
  const sum = $("payroll-table-summary");
  const pag = $("payroll-table-pagination");
  const filterForm = $("payroll-filter-form");
  const filterDrawer = $("payroll-filter-drawer");
  const filterBackdrop = $("payroll-filter-backdrop");

  const actionModalEl = $("payroll-action-modal");
  const actionModalSubtitle = $("payroll-action-modal-subtitle");
  const actionDetailEmployeeLink = $("payroll-action-detail-employee-link");
  const actionDetailPayrollLink = $("payroll-action-detail-payroll-link");
  const actionReversalButton = $("payroll-action-reversal-button");
  const actionReversalNote = $("payroll-action-reversal-note");

  const reversalModalEl = $("payroll-reversal-modal");
  const reversalForm = $("payroll-reversal-form");
  const reversalReason = $("payroll-reversal-reason");
  const reversalSubtitle = $("payroll-reversal-modal-subtitle");

  const actionModal = actionModalEl && window.bootstrap?.Modal
    ? new window.bootstrap.Modal(actionModalEl)
    : null;

  const reversalModal = reversalModalEl && window.bootstrap?.Modal
    ? new window.bootstrap.Modal(reversalModalEl)
    : null;

  const sortKeys = new Set(["disbursement_date", "employee_name", "amount", "mode", "status"]);
  const sortDirs = new Set(["asc", "desc"]);

  let timer = null;
  let req = 0;
  let activeController = null;

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


  const trim = (v) => String(v ?? "").trim();

  const intOr = (v, f) => {
    const n = Number.parseInt(String(v ?? ""), 10);
    return Number.isNaN(n) || n < 1 ? f : n;
  };

  const employeeDetailUrl = (id) => c.employeeDetailBaseUrl.replace("__ID__", encodeURIComponent(id));
  const employeePayrollDetailUrl = (id) => c.employeePayrollDetailBaseUrl.replace("__ID__", encodeURIComponent(id));
  const reverseStoreUrl = (id) => c.reverseStoreBaseUrl.replace("__ID__", encodeURIComponent(id));

  const stateFromUrl = () => {
    const p = new URLSearchParams(window.location.search);
    const s = trim(p.get("sort_by"));
    const d = trim(p.get("sort_dir"));

    const query = trim(p.get("q"));
    const explicitSort = sortKeys.has(s);
    return {
      q: query,
      page: intOr(p.get("page"), 1),
      sort_by: query.length >= 2 && !explicitSort ? "relevance" : (explicitSort ? s : "disbursement_date"),
      sort_dir: query.length >= 2 && !explicitSort ? "asc" : (sortDirs.has(d) ? d : "desc"),
      mode: ["daily", "weekly", "monthly"].includes(trim(p.get("mode"))) ? trim(p.get("mode")) : "",
      status: ["active", "reversed"].includes(trim(p.get("status"))) ? trim(p.get("status")) : "all",
      date_from: trim(p.get("date_from")),
      date_to: trim(p.get("date_to")),
    };
  };

  const s = stateFromUrl();

  const params = () => {
    const o = {
      page: String(s.page),
      per_page: "10",
      sort_dir: s.sort_dir,
    };

    if (s.sort_by !== "relevance") o.sort_by = s.sort_by;

    if (s.q) o.q = s.q;
    ["mode", "status", "date_from", "date_to"].forEach((key) => {
      if (s[key] && !(key === "status" && s[key] === "all")) o[key] = s[key];
    });

    return o;
  };

  const paramsString = () => new URLSearchParams(params()).toString();

  const updateUrl = (replace = false) => {
    const url = new URL(window.location.href);
    url.search = paramsString();

    if (replace) {
      window.history.replaceState(null, "", url);
      return;
    }

    window.history.pushState(null, "", url);
  };

  const renderSummary = (m) => {
    const total = Number(m.total || 0), page = Number(m.page || 1), perPage = Number(m.per_page || 10);
    const from = total ? ((page - 1) * perPage) + 1 : 0;
    sum.textContent = `Menampilkan ${from} sampai ${Math.min(page * perPage, total)} dari ${total} pencairan gaji`;
  };

  const renderSort = () => {
    document.querySelectorAll("[data-sort-indicator]").forEach((n) => {
      const active = n.dataset.sortIndicator === s.sort_by;
      n.textContent = active ? (s.sort_dir === "asc" ? "↑" : "↓") : "↕";
      n.classList.toggle("text-muted", !active);
    });
  };

  const renderPager = (m) => {
    if (m.last_page <= 1) {
      pag.innerHTML = "";
      return;
    }

    const start = Math.max(1, m.page - 2);
    const end = Math.min(m.last_page, m.page + 2);

    let html = '<nav><ul class="pagination pagination-primary mb-0">';
    html += `<li class="page-item ${m.page === 1 ? "disabled" : ""}"><a class="page-link" href="#" data-page="${m.page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;

    for (let p = start; p <= end; p += 1) {
      html += `<li class="page-item ${p === m.page ? "active" : ""}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
    }

    html += `<li class="page-item ${m.page === m.last_page ? "disabled" : ""}"><a class="page-link" href="#" data-page="${m.page + 1}"><i class="bi bi-chevron-right"></i></a></li></ul></nav>`;
    pag.innerHTML = html;
  };

  const configureActionModal = (row) => {
    if (!actionModalSubtitle || !actionDetailEmployeeLink || !actionDetailPayrollLink || !actionReversalButton) {
      return;
    }

    actionModalSubtitle.textContent = `${row.employeeName} • ${row.disbursementDate} • Rp${row.amountFormatted}`;
    actionDetailEmployeeLink.href = employeeDetailUrl(row.employeeId);
    actionDetailPayrollLink.href = employeePayrollDetailUrl(row.employeeId);

    if (row.isReversed) {
      actionReversalButton.disabled = true;
      actionReversalButton.dataset.payrollId = "";
      actionReversalButton.dataset.employeeName = "";
      actionReversalButton.dataset.disbursementDate = "";
      actionReversalButton.dataset.amountFormatted = "";
      if (actionReversalNote) {
        actionReversalNote.textContent = "Riwayat ini sudah dibatalkan.";
      }
      return;
    }

    actionReversalButton.disabled = false;
    actionReversalButton.dataset.payrollId = row.payrollId;
    actionReversalButton.dataset.employeeName = row.employeeName;
    actionReversalButton.dataset.disbursementDate = row.disbursementDate;
    actionReversalButton.dataset.amountFormatted = row.amountFormatted;
    if (actionReversalNote) {
      actionReversalNote.textContent = "Buka form pembatalan dengan alasan yang jelas.";
    }
  };

  const openReversalModal = (payload) => {
    if (!reversalModal || !reversalForm || !reversalSubtitle) return;

    reversalForm.action = reverseStoreUrl(payload.payrollId);

    if (reversalReason) {
      reversalReason.value = "";
    }

    reversalSubtitle.textContent = `${payload.employeeName} • ${payload.disbursementDate} • Rp${payload.amountFormatted}`;
    reversalModal.show();
  };

  const renderRows = (rows, m) => {
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada pencairan gaji yang cocok.</td></tr>';
      return;
    }

    body.innerHTML = rows.map((r, i) => {
      const notesHtml = r.is_reversed && r.reversal_reason
        ? `${esc(r.notes ?? "-")}<div class="small text-danger mt-1">Dibatalkan: ${esc(r.reversal_reason)}</div>`
        : esc(r.notes ?? "-");

      const statusHtml = r.is_reversed
        ? `<div class="small text-danger mt-1">Dibatalkan${r.reversal_created_at ? ` • ${esc(r.reversal_created_at)}` : ""}</div>`
        : '<div class="small text-success mt-1">Aktif</div>';

      return `
      <tr>
        <td>${(m.page - 1) * m.per_page + i + 1}</td>
        <td>${esc(tanggalId(r.disbursement_date))}</td>
        <td>${esc(r.employee_name)}</td>
        <td>Rp${esc(r.amount_formatted)}</td>
        <td>${esc(r.mode_label)}</td>
        <td>${statusHtml}</td>
        <td>${notesHtml}</td>
        <td class="text-center">
          <button
            type="button"
            class="btn btn-sm btn-outline-primary"
            data-payroll-action="open"
            data-payroll-id="${esc(r.id)}"
            data-employee-id="${esc(r.employee_id)}"
            data-employee-name="${esc(r.employee_name)}"
            data-disbursement-date="${esc(r.disbursement_date)}"
            data-amount-formatted="${esc(r.amount_formatted)}"
            data-is-reversed="${r.is_reversed ? "1" : "0"}"
          >
            Aksi
          </button>
        </td>
      </tr>`;
    }).join("");
  };

  const load = async (replace = false) => {
    activeController?.abort();
    const controller = new AbortController();
    activeController = controller;
    const current = ++req;
    body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Sedang memuat data...</td></tr>';
    try {
      const res = await fetch(`${c.endpoint}?${paramsString()}`, { headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }, signal: controller.signal });
      const json = await res.json();
      if (current !== req) return;
      if (!res.ok || !json.success) throw new Error("payroll-table-response");
      renderRows(json.data.rows || [], json.data.meta || {}); renderSummary(json.data.meta || {}); renderPager(json.data.meta || {}); renderSort(); updateUrl(replace);
    } catch (error) {
      if (error?.name === "AbortError" || current !== req) return;
      body.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
      sum.textContent = "Menampilkan 0 sampai 0 dari 0 pencairan gaji"; pag.innerHTML = "";
    } finally { if (activeController === controller) activeController = null; }
  };

  form?.addEventListener("submit", (e) => {
    e.preventDefault();
    const value = trim(q.value);

    if (value.length < 2) {
      s.q = "";
      if (s.sort_by === "relevance") { s.sort_by = "disbursement_date"; s.sort_dir = "desc"; }
      s.page = 1;
      load();
      return;
    }

    if (value.length >= 2) {
      s.q = value;
      s.sort_by = "relevance"; s.sort_dir = "asc";
      s.page = 1;
      load();
    }
  });

  q?.addEventListener("input", () => {
    const value = trim(q.value);
    clearTimeout(timer);

    if (value.length < 2) {
      s.q = "";
      if (s.sort_by === "relevance") { s.sort_by = "disbursement_date"; s.sort_dir = "desc"; }
      s.page = 1;
      timer = setTimeout(() => load(), 160);
      return;
    }

    timer = setTimeout(() => {
      s.q = value;
      s.sort_by = "relevance"; s.sort_dir = "asc";
      s.page = 1;
      load();
    }, 220);
  });

  document.querySelectorAll("[data-sort-by]").forEach((b) => b.addEventListener("click", () => {
    const key = b.dataset.sortBy;
    s.sort_dir = s.sort_by === key && s.sort_dir === "asc" ? "desc" : "asc";
    s.sort_by = key;
    s.page = 1;
    load();
  }));

  pag?.addEventListener("click", (e) => {
    const b = e.target.closest("[data-page]");
    if (!b || b.parentElement.classList.contains("disabled")) return;
    e.preventDefault();
    s.page = Number(b.dataset.page);
    load();
  });

  document.addEventListener("click", (e) => {
    const actionButton = e.target.closest('[data-payroll-action="open"]');
    if (actionButton && actionModal) {
      configureActionModal({
        payrollId: trim(actionButton.dataset.payrollId),
        employeeId: trim(actionButton.dataset.employeeId),
        employeeName: trim(actionButton.dataset.employeeName) || "Karyawan",
        disbursementDate: trim(actionButton.dataset.disbursementDate) || "-",
        amountFormatted: trim(actionButton.dataset.amountFormatted) || "0",
        isReversed: trim(actionButton.dataset.isReversed) === "1",
      });

      actionModal.show();
      return;
    }

    const reversalButton = e.target.closest("#payroll-action-reversal-button");
    if (!reversalButton) return;

    const payrollId = trim(reversalButton.dataset.payrollId);
    if (payrollId === "") return;

    const payload = {
      payrollId,
      employeeName: trim(reversalButton.dataset.employeeName) || "Karyawan",
      disbursementDate: trim(reversalButton.dataset.disbursementDate) || "-",
      amountFormatted: trim(reversalButton.dataset.amountFormatted) || "0",
    };

    actionModal?.hide();
    window.setTimeout(() => openReversalModal(payload), 180);
  });

  reversalModalEl?.addEventListener("hidden.bs.modal", () => {
    if (reversalForm) {
      reversalForm.action = "#";
    }

    if (reversalReason) {
      reversalReason.value = "";
    }

    if (reversalSubtitle) {
      reversalSubtitle.textContent = "Isi alasan pembatalan pencairan gaji.";
    }
  });

  const syncControls = () => {
    q.value = s.q;
    ["mode", "status", "date_from", "date_to"].forEach((key) => { if (filterForm?.elements[key]) filterForm.elements[key].value = s[key]; });
  };
  const drawFilter = (open) => { filterDrawer?.classList.toggle("d-none", !open); filterBackdrop?.classList.toggle("d-none", !open); };
  $("open-payroll-filter")?.addEventListener("click", () => drawFilter(true));
  $("close-payroll-filter")?.addEventListener("click", () => drawFilter(false));
  filterBackdrop?.addEventListener("click", () => drawFilter(false));
  filterForm?.addEventListener("submit", (e) => { e.preventDefault(); ["mode", "status", "date_from", "date_to"].forEach((key) => { s[key] = trim(filterForm.elements[key].value); }); s.page = 1; drawFilter(false); load(); });
  $("reset-payroll-filter")?.addEventListener("click", () => { s.mode = ""; s.status = "all"; s.date_from = ""; s.date_to = ""; s.page = 1; syncControls(); drawFilter(false); load(); });
  window.addEventListener("popstate", () => { Object.assign(s, stateFromUrl()); syncControls(); load(true); });

  syncControls();
  load(true);
})();