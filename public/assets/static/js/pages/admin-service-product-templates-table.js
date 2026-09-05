(() => {
  "use strict";
  const c = window.AdminPackageTableConfig || {};
  const $ = (id) => document.getElementById(id);
  const body = $("package-table-body"), sum = $("package-table-summary"), pag = $("package-table-pagination");
  const input = $("package-search-input"), form = $("package-search-form"), filters = $("package-filter-form");
  const drawer = $("package-filter-drawer"), backdrop = $("package-filter-backdrop");
  if (!c.endpoint || !body || !sum || !pag || !input) return;

  const sorts = new Set(["product_name", "service_name", "default_service_price_rupiah", "package_total", "is_active"]);
  const trim = (v) => String(v || "").trim();
  const esc = (v) => String(v ?? "").replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
  const money = (v) => new Intl.NumberFormat("id-ID").format(Number(v || 0));
  const fromUrl = () => {
    const p = new URLSearchParams(location.search), sort = trim(p.get("sort_by")), status = trim(p.get("status"));
    return { q: trim(p.get("q")), status: ["active", "inactive"].includes(status) ? status : "all",
      sort_by: sorts.has(sort) ? sort : "", sort_dir: p.get("sort_dir") === "desc" ? "desc" : "asc",
      page: Math.max(1, Number(p.get("page") || 1)) };
  };
  let s = fromUrl(), timer = null, counter = 0, activeController = null;

  const params = () => {
    const p = new URLSearchParams({ status: s.status, page: String(s.page), per_page: "10", sort_dir: s.sort_dir });
    if (s.q.length >= 2) p.set("q", s.q);
    if (s.sort_by) p.set("sort_by", s.sort_by);
    return p;
  };
  const url = (replace) => {
    const p = params();
    if (s.status === "all") p.delete("status");
    if (s.page === 1) p.delete("page");
    if (!s.sort_by) p.delete("sort_dir");
    const q = p.toString();
    history[replace ? "replaceState" : "pushState"](null, "", `${location.pathname}${q ? `?${q}` : ""}`);
  };
  const controls = () => {
    input.value = s.q;
    if (filters?.elements.status) filters.elements.status.value = s.status;
  };
  const open = (value) => {
    drawer?.classList.toggle("d-none", !value);
    backdrop?.classList.toggle("d-none", !value);
  };
  const renderSummary = (m) => {
    const total = Number(m.total || 0), page = Number(m.page || 1), perPage = Number(m.per_page || 10);
    const start = total ? ((page - 1) * perPage) + 1 : 0, end = Math.min(page * perPage, total);
    sum.textContent = `Menampilkan ${start} sampai ${end} dari ${total} paket service`;
  };
  const renderPager = (m) => {
    const page = Number(m.page || 1), last = Number(m.last_page || 1);
    pag.innerHTML = last <= 1 ? "" : `<nav aria-label="Pagination paket service"><ul class="pagination pagination-sm mb-0">
      <li class="page-item ${page <= 1 ? "disabled" : ""}"><button type="button" class="page-link" data-page="${page - 1}">Sebelumnya</button></li>
      <li class="page-item disabled"><span class="page-link">${page} / ${last}</span></li>
      <li class="page-item ${page >= last ? "disabled" : ""}"><button type="button" class="page-link" data-page="${page + 1}">Berikutnya</button></li>
    </ul></nav>`;
  };
  const renderSort = () => document.querySelectorAll("[data-sort-indicator]").forEach((n) => {
    n.textContent = n.dataset.sortIndicator === s.sort_by ? (s.sort_dir === "asc" ? "↑" : "↓") : "↕";
  });
  const renderRows = (rows, m) => {
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada paket service yang cocok.</td></tr>';
      return;
    }
    body.innerHTML = rows.map((r, i) => {
      const active = Boolean(r.is_active), margin = Number(r.package_margin || 0);
      const marginText = margin > 0 ? ` · Selisih Rp${money(margin)}` : "";
      const splitText = margin > 0 ? `<div class="small text-muted mt-1">80% keuntungan Rp${money(r.package_profit)} · 20% jasa Rp${money(r.package_service_extra)}</div>` : "";
      return `<tr>
        <td>${((Number(m.page) - 1) * Number(m.per_page)) + i + 1}</td>
        <td><div class="fw-semibold">${esc(r.service_name)}</div><small class="text-muted">Service</small></td>
        <td><div class="fw-semibold">${esc(r.nama_barang)}</div><small class="text-muted">${esc(r.kode_barang || "-")} · harga jual Rp${money(r.harga_jual)}</small></td>
        <td>Rp${money(r.default_service_price_rupiah)}</td>
        <td><div class="fw-semibold">Rp${money(r.package_total)}</div><small class="text-muted">Min Rp${money(r.minimum_total)}${marginText}</small>${splitText}</td>
        <td><span class="badge ${active ? "bg-success" : "bg-secondary"}">${active ? "Aktif" : "Nonaktif"}</span></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-primary" data-package-action="open"
          data-package-name="${esc(r.service_name)}" data-package-product="${esc(r.nama_barang)}" data-package-status="${active ? "active" : "inactive"}"
          data-detail-url="${esc(r.actions.detail_url)}" data-edit-url="${esc(r.actions.edit_url)}" data-product-url="${esc(r.actions.product_url)}"
          data-service-url="${esc(r.actions.service_url)}" data-deactivate-url="${esc(r.actions.deactivate_url)}" data-reactivate-url="${esc(r.actions.reactivate_url)}">Aksi</button></td>
      </tr>`;
    }).join("");
  };

  const load = async (replace = false) => {
    activeController?.abort();
    const controller = new AbortController(); activeController = controller;
    const request = ++counter;
    body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Sedang memuat data...</td></tr>';
    try {
      const response = await fetch(`${c.endpoint}?${params()}`, { headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }, signal: controller.signal });
      const payload = await response.json();
      if (request !== counter) return;
      if (!response.ok || payload?.success !== true) throw new Error("package-table-response");
      const data = payload.data || {}, meta = data.meta || {};
      renderRows(Array.isArray(data.rows) ? data.rows : [], meta); renderSummary(meta); renderPager(meta); renderSort(); url(replace);
    } catch (error) {
      if (error?.name === "AbortError" || request !== counter) return;
      body.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
      sum.textContent = "Menampilkan 0 sampai 0 dari 0 paket service"; pag.innerHTML = "";
    } finally { if (activeController === controller) activeController = null; }
  };
  const search = () => {
    clearTimeout(timer); const value = trim(input.value); s.q = value.length >= 2 ? value : ""; s.page = 1;
    timer = setTimeout(() => load(), value.length >= 2 ? 220 : 160);
  };
  form?.addEventListener("submit", (e) => { e.preventDefault(); search(); });
  input.addEventListener("input", search);
  $("open-package-filter")?.addEventListener("click", () => open(true));
  $("close-package-filter")?.addEventListener("click", () => open(false));
  backdrop?.addEventListener("click", () => open(false));
  filters?.addEventListener("submit", (e) => { e.preventDefault(); s.status = filters.elements.status.value; s.page = 1; open(false); load(); });
  $("reset-package-filter")?.addEventListener("click", () => { s.status = "all"; s.page = 1; controls(); open(false); load(); });
  document.querySelectorAll("[data-sort-by]").forEach((b) => b.addEventListener("click", () => {
    const key = b.dataset.sortBy; if (!sorts.has(key)) return;
    s.sort_dir = s.sort_by === key && s.sort_dir === "asc" ? "desc" : "asc"; s.sort_by = key; s.page = 1; load();
  }));
  pag.addEventListener("click", (e) => { const b = e.target.closest("[data-page]"); if (!b || b.closest(".disabled")) return; s.page = Number(b.dataset.page); load(); });
  addEventListener("popstate", () => { s = fromUrl(); controls(); load(true); });
  controls(); load(true);
})();
