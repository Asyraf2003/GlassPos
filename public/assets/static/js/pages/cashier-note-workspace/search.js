(() => {
  const NS = (window.CashierNoteWorkspace = window.CashierNoteWorkspace || {});
  const timers = new WeakMap();
  const requestTokens = new WeakMap();
  const activeChoiceIndexes = new WeakMap();
  const queryCache = new Map();

  const parseDigits = (value) =>
    Number.parseInt(String(value || "").replace(/\D+/g, "") || "0", 10);
  const format = (value) => Number(value || 0).toLocaleString("id-ID");
  const productScope = (element) =>
    element?.closest?.("[data-product-line]") ||
    element?.closest?.("[data-line-item]") ||
    element;
  const isPrimaryServiceProductScope = (row, scope) =>
    (row?.dataset?.itemType || "") === "service_store_stock" &&
    (!(scope instanceof HTMLElement) || (scope.dataset.productLineIndex || "0") === "0");

  const focus = (element, select = true) => NS.focusElement?.(element, select);
  const resultButtons = (scope) =>
    Array.from(scope.querySelectorAll("[data-product-choice]"));

  const setActiveChoice = (scope, index) => {
    const buttons = resultButtons(scope);
    if (!buttons.length) {
      activeChoiceIndexes.set(scope, -1);
      return;
    }

    const next = Math.max(0, Math.min(index, buttons.length - 1));
    activeChoiceIndexes.set(scope, next);
    buttons.forEach((button, buttonIndex) => {
      button.classList.toggle("active", buttonIndex === next);
      if (buttonIndex === next) button.scrollIntoView({ block: "nearest" });
    });
  };

  const clearResults = (scope) => {
    const results = scope.querySelector("[data-product-results]");
    if (!results) return;
    results.replaceChildren();
    results.classList.add("d-none");
    activeChoiceIndexes.set(scope, -1);
  };

  const invalidateLookup = (input) => {
    if (!(input instanceof HTMLInputElement)) return;
    window.clearTimeout(timers.get(input));
    requestTokens.set(input, Symbol("product-search-invalidated"));
  };

  const productName = (item) => String(item?.name || item?.label || "Produk");
  const productMeta = (item) =>
    [item?.brand, item?.size, item?.code].filter((value) => value !== null && value !== undefined && String(value) !== "").join(" · ");

  const setProductSelectedState = (scope, item) => {
    const stage = scope.querySelector("[data-product-search-stage]");
    const selected = scope.querySelector("[data-product-selected]");
    const name = scope.querySelector("[data-selected-product-name]");
    const meta = scope.querySelector("[data-selected-product-meta]");
    const priceStock = scope.querySelector("[data-selected-product-price-stock]");

    stage?.classList.add("d-none");
    selected?.classList.remove("d-none");
    if (name) name.textContent = productName(item);
    if (meta) meta.textContent = productMeta(item);
    if (priceStock) {
      priceStock.textContent = `Rp${format(item?.default_unit_price_rupiah)} · stok ${item?.available_stock ?? "-"}`;
    }
  };

  const clearProductSelectedState = (row, scope, focusSearch = true) => {
    const search = scope.querySelector("[data-product-search]");
    const hidden = scope.querySelector("[data-product-id]");
    const raw = scope.querySelector('input[name$="[unit_price_rupiah]"]');

    invalidateLookup(search);
    if (hidden) hidden.value = "";
    if (raw) raw.value = "";
    if (search) {
      search.value = "";
      delete search.dataset.selectedLabel;
    }

    scope.dataset.minimumUnitPriceRupiah = "0";
    scope.dataset.availableStock = "0";
    const qty = scope.querySelector("[data-qty-input]");
    if (qty) qty.value = "1";
    const stockText = scope.querySelector("[data-stock-text]");
    if (stockText) stockText.textContent = "Stok tersedia: -";
    scope.querySelector("[data-stock-error]")?.classList.add("d-none");
    scope.querySelector("[data-min-price-warning]")?.classList.add("d-none");
    scope.querySelector("[data-selected-product-name]")?.replaceChildren();
    scope.querySelector("[data-selected-product-meta]")?.replaceChildren();
    scope.querySelector("[data-selected-product-price-stock]")?.replaceChildren();
    scope.querySelector("[data-product-search-stage]")?.classList.remove("d-none");
    scope.querySelector("[data-product-selected]")?.classList.add("d-none");
    clearResults(scope);
    NS.clearServiceProductTemplate?.(row);
    NS.updateSummary?.();
    if (focusSearch) focus(search);
  };

  const appendResultText = (button, className, value) => {
    const line = document.createElement("span");
    line.className = className;
    line.textContent = value;
    button.appendChild(line);
  };

  const renderResults = (row, scope, rows) => {
    const results = scope.querySelector("[data-product-results]");
    if (!results) return;
    results.replaceChildren();

    rows.forEach((item) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "workspace-search-result";
      button.dataset.productChoice = "1";
      appendResultText(button, "workspace-result-primary", productName(item));
      appendResultText(button, "workspace-result-secondary", productMeta(item));
      appendResultText(
        button,
        "workspace-result-additional",
        `Rp${format(item.default_unit_price_rupiah)} · stok ${item.available_stock}`
      );
      button.addEventListener("click", () => NS.selectProduct(row, item, scope));
      results.appendChild(button);
    });

    results.classList.toggle("d-none", rows.length === 0);
    setActiveChoice(scope, 0);
  };

  NS.syncFloorPriceGuard = (row) => {
    row.querySelectorAll("[data-product-line]").forEach((scope) => {
      const raw = scope.querySelector('input[name$="[unit_price_rupiah]"]');
      const warning = scope.querySelector("[data-min-price-warning]");
      const text = scope.querySelector("[data-min-price-text]");
      const floor = parseDigits(scope.dataset.minimumUnitPriceRupiah);
      const current = parseDigits(raw?.value);
      const invalid = floor > 0 && current > 0 && current < floor;

      if (text) {
        text.textContent = floor > 0 ? `Harga minimum: ${format(floor)}` : "Harga produk mengikuti katalog.";
      }
      warning?.classList.toggle("d-none", !invalid);
    });
  };

  NS.selectProduct = (row, item, explicitScope = null) => {
    const scope = explicitScope || productScope(row.querySelector("[data-product-search]"));
    const search = scope.querySelector("[data-product-search]");
    const hidden = scope.querySelector("[data-product-id]");
    const raw = scope.querySelector('input[name$="[unit_price_rupiah]"]');
    const qty = scope.querySelector("[data-qty-input]");
    const priceBasis = scope.querySelector("[data-price-basis]");
    if (!search || !hidden) return;

    invalidateLookup(search);
    hidden.value = item.id;
    search.value = "";
    search.dataset.selectedLabel = item.label || productName(item);
    if (priceBasis) priceBasis.value = "current_catalog";
    if (raw) raw.value = String(item.default_unit_price_rupiah || 0);

    scope.dataset.minimumUnitPriceRupiah = String(
      item.minimum_unit_price_rupiah || item.default_unit_price_rupiah || 0
    );
    scope.dataset.availableStock = String(item.available_stock || 0);
    setProductSelectedState(scope, item);

    if (isPrimaryServiceProductScope(row, scope)) {
      NS.applyServiceProductTemplate?.(row, item.service_product_template || null, scope);
    }

    NS.updateStockText?.(row, item.available_stock, scope);
    NS.syncFloorPriceGuard?.(row);
    NS.syncQtyGuard?.(row);
    clearResults(scope);
    NS.updateSummary?.();
    focus(qty);
  };

  NS.restoreSelectedProduct = (row, scope, line = {}, fallbackLabel = "") => {
    const id = String(line?.product_id || "").trim();
    if (!id) return;

    const search = scope.querySelector("[data-product-search]");
    const fallbackPrice = parseDigits(line?.unit_price_rupiah);
    const item = {
      id,
      label: line?.selected_label || line?.product_label || fallbackLabel,
      name: line?.product_name || line?.selected_label || line?.product_label || fallbackLabel,
      brand: line?.brand || "",
      size: line?.size ?? "",
      code: line?.code || line?.kode_barang || "",
      available_stock: line?.available_stock ?? "-",
      default_unit_price_rupiah: fallbackPrice,
    };

    if (search) {
      search.value = "";
      search.dataset.selectedLabel = item.label || item.name;
    }
    setProductSelectedState(scope, item);
  };

  const cachedRows = async (endpoint, params) => {
    const separator = endpoint.includes("?") ? "&" : "?";
    const url = `${endpoint}${separator}${params.toString()}`;
    if (queryCache.has(url)) return queryCache.get(url);

    const response = await fetch(url, { headers: { Accept: "application/json" } });
    if (!response.ok) throw new Error(`Product lookup failed with status ${response.status}`);
    const payload = await response.json();

    const rows = payload?.data?.rows || [];
    queryCache.set(url, rows);
    if (queryCache.size > 20) queryCache.delete(queryCache.keys().next().value);
    return rows;
  };

  NS.bindProductSearch = (row) => {
    if ((row?.dataset?.itemType || "") === "service_store_stock" && row.querySelector("[data-package-search]")) return;

    row.querySelectorAll("[data-product-search]").forEach((input) => {
      const scope = productScope(input);
      const hidden = scope.querySelector("[data-product-id]");
      if (!(input instanceof HTMLInputElement) || input.dataset.searchBound === "1") return;
      input.dataset.searchBound = "1";

      const fetchResults = async () => {
        const query = input.value.trim();
        const endpoint = NS.config?.productLookupEndpoint;
        const token = Symbol("product-search");
        requestTokens.set(input, token);
        if (!hidden || query.length < 2 || !endpoint) {
          clearResults(scope);
          return;
        }

        const params = new URLSearchParams({ q: query });
        if (isPrimaryServiceProductScope(row, scope)) {
          params.set("context", "service_product");
        }

        try {
          const rows = await cachedRows(endpoint, params);
          if (requestTokens.get(input) === token) renderResults(row, scope, rows);
        } catch (_error) {
          if (requestTokens.get(input) === token) clearResults(scope);
        }
      };

      input.addEventListener("input", () => {
        const selected = scope.querySelector("[data-product-selected]");
        if (hidden?.value && selected && !selected.classList.contains("d-none")) {
          input.value = "";
          return;
        }

        requestTokens.set(input, Symbol("product-search-input"));
        const raw = scope.querySelector('input[name$="[unit_price_rupiah]"]');
        if (raw) raw.value = "";
        window.clearTimeout(timers.get(input));
        timers.set(input, window.setTimeout(() => void fetchResults(), 250));
        NS.updateSummary?.();
      });

      input.addEventListener("focus", () => {
        if (input.value.trim().length >= 2) void fetchResults();
      });

      input.addEventListener("keydown", (event) => {
        const buttons = resultButtons(scope);
        const current = activeChoiceIndexes.get(scope) ?? 0;
        if (event.key === "Escape") {
          clearResults(scope);
        } else if (buttons.length && event.key === "ArrowDown") {
          event.preventDefault();
          setActiveChoice(scope, current + 1);
        } else if (buttons.length && event.key === "ArrowUp") {
          event.preventDefault();
          setActiveChoice(scope, current - 1);
        } else if (buttons.length && event.key === "Enter") {
          event.preventDefault();
          buttons[current]?.click();
        }
      });

      scope.querySelector("[data-product-change]")?.addEventListener("click", () => {
        clearProductSelectedState(row, scope);
      });

      document.addEventListener("click", (event) => {
        if (event.target instanceof Node && !scope.contains(event.target)) clearResults(scope);
      });
    });
  };
})();
