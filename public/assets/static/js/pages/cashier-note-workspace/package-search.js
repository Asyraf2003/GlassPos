(() => {
  const NS = (window.CashierNoteWorkspace = window.CashierNoteWorkspace || {});
  const timers = new WeakMap();
  const requestTokens = new WeakMap();
  const activeChoiceIndexes = new WeakMap();

  const digits = (value) =>
    Number.parseInt(String(value || "").replace(/\D+/g, "") || "0", 10);
  const format = (value) => Number(value || 0).toLocaleString("id-ID");

  const productLineScopes = (row) =>
    Array.from(row.querySelectorAll("[data-product-line]"));

  const packageSearchInput = (row) => row.querySelector("[data-package-search]");
  const packageResults = (row) => row.querySelector("[data-package-results]");
  const packageSelectedSection = (row) => row.querySelector("[data-package-selected-section]");

  const setValue = (root, selector, value) => {
    const el = root.querySelector(selector);
    if (el && value !== undefined && value !== null) el.value = String(value);
  };

  const setText = (row, selector, value) => {
    const el = row.querySelector(selector);
    if (el) el.textContent = String(value || "-");
  };

  const invalidateLookup = (row) => {
    const input = packageSearchInput(row);
    if (!(input instanceof HTMLInputElement)) return;
    clearTimeout(timers.get(input));
    requestTokens.set(input, Symbol("package-search-invalidated"));
  };

  const packageTotal = (item) => {
    const configured = digits(item?.service_product_template?.default_package_total_rupiah);
    if (configured > 0) return configured;

    const service = digits(item?.service?.price_rupiah);
    const products = (item?.product_lines || []).reduce(
      (sum, line) => sum + digits(line?.qty) * digits(line?.unit_price_rupiah),
      0
    );
    return service + products;
  };

  const renderPackageProducts = (row, productLines) => {
    const list = row.querySelector("[data-package-product-list]");
    if (!list) return;
    list.replaceChildren();

    productLines.forEach((line) => {
      const item = document.createElement("div");
      item.className = "workspace-package-product";
      const name = document.createElement("span");
      name.textContent = `${line?.product_name || line?.label || "Sparepart"} × ${line?.qty || 1}`;
      const detail = document.createElement("span");
      detail.textContent = `Rp${format(digits(line?.qty) * digits(line?.unit_price_rupiah))} · stok ${line?.available_stock ?? "-"}`;
      item.append(name, detail);
      list.appendChild(item);
    });
  };

  const showPackageSelected = (row, item, productLines) => {
    const serviceName = String(item?.service?.name || item?.service_product_template?.service_name || "Paket servis").trim();
    row.querySelector("[data-package-search-stage]")?.classList.add("d-none");
    packageSelectedSection(row)?.classList.remove("d-none");
    setText(row, "[data-package-title]", serviceName);
    setText(
      row,
      "[data-package-description]",
      `${productLines.length} sparepart · total Rp${format(packageTotal(item))}`
    );
    setText(row, "[data-package-stock-text]", item?.stock_label || "Stok mengikuti validasi server");
    renderPackageProducts(row, productLines);
  };

  const clearResults = (row) => {
    const results = packageResults(row);
    if (!results) return;

    results.innerHTML = "";
    results.classList.add("d-none");
    activeChoiceIndexes.set(row, -1);
  };

  const resultButtons = (row) =>
    Array.from(packageResults(row)?.querySelectorAll("[data-package-choice]") || []);

  const setActiveChoice = (row, index) => {
    const buttons = resultButtons(row);

    if (!buttons.length) {
      activeChoiceIndexes.set(row, -1);
      return;
    }

    const nextIndex = Math.max(0, Math.min(index, buttons.length - 1));
    activeChoiceIndexes.set(row, nextIndex);

    buttons.forEach((button, buttonIndex) => {
      button.classList.toggle("active", buttonIndex === nextIndex);
    });
  };

  const ensureProductLineCount = (row, count) => {
    const targetCount = Math.max(1, Math.min(Number(count || 1), 3));

    while (productLineScopes(row).length < targetCount) {
      NS.addProductLine?.(row, {}, false);
    }

    while (productLineScopes(row).length > targetCount) {
      const scopes = productLineScopes(row);
      scopes[scopes.length - 1]?.remove();
    }

    NS.reindexProductLines?.(row);
  };

  const applyProductLine = (row, scope, line) => {
    if (!(scope instanceof HTMLElement)) return;

    const label = String(line?.label || line?.product_name || "").trim();

    setValue(scope, "[data-product-search]", label);
    setValue(scope, "[data-product-id]", line?.product_id || "");
    setValue(scope, "[data-price-basis]", "current_catalog");
    setValue(scope, "[data-qty-input]", line?.qty || "1");
    setValue(scope, 'input[name$="[unit_price_rupiah]"]', line?.unit_price_rupiah || "");

    scope.dataset.minimumUnitPriceRupiah = String(
      line?.minimum_unit_price_rupiah || line?.unit_price_rupiah || 0
    );

    NS.updateStockText?.(row, line?.available_stock || 0, scope);
  };

  NS.applyPackageTemplate = (row, item) => {
    if (!(row instanceof HTMLElement)) return;
    if ((row.dataset.itemType || "") !== "service_store_stock") return;

    invalidateLookup(row);
    const productLines = Array.isArray(item?.product_lines)
      ? item.product_lines.slice(0, 3)
      : [];

    row.dataset.serviceProductTemplateApplied = productLines.length > 0 ? "1" : "0";
    row.dataset.serviceTemplateAutofilled = productLines.length > 0 ? "1" : "0";
    row.dataset.selectedPackageId = String(item?.id || "");
    row.dataset.selectedPackageLabel = String(item?.label || item?.service?.name || "");
    setValue(row, "[data-requires-service-product-template]", "1");

    const input = packageSearchInput(row);
    if (input) input.value = "";

    const service = item?.service || {};
    const serviceTemplate = item?.service_product_template || {};
    const serviceName = String(service?.name || serviceTemplate?.service_name || "").trim();
    const servicePrice = digits(
      service?.price_rupiah || serviceTemplate?.default_service_price_rupiah || 0
    );

    setValue(row, "[data-service-name]", serviceName);
    setValue(row, "[data-service-catalog-id]", service?.catalog_item_id || serviceTemplate?.service_catalog_item_id || "");
    setValue(row, "[data-service-default-fee-rupiah]", servicePrice > 0 ? servicePrice : "");
    setValue(row, "[data-service-price-raw]", servicePrice > 0 ? servicePrice : "0");
    setValue(row, "[data-service-price-display]", servicePrice > 0 ? format(servicePrice) : "");

    ensureProductLineCount(row, Math.max(productLines.length, 1));

    productLineScopes(row).forEach((scope, index) => {
      applyProductLine(row, scope, productLines[index] || {});
    });

    if (productLines.length > 0) showPackageSelected(row, item, productLines);

    row.querySelector("[data-package-error]")?.classList.add("d-none");

    window.AdminMoneyInput?.bindBySelector?.(row);
    NS.syncFloorPriceGuard?.(row);
    NS.syncQtyGuard?.(row);
    NS.syncServiceDefaults?.(row);
    NS.updateSummary?.();
  };

  const renderResults = (row, rows) => {
    const results = packageResults(row);
    if (!results) return;

    results.innerHTML = "";

    if (!rows.length) {
      const empty = document.createElement("div");
      empty.className = "list-group-item small text-muted";
      empty.textContent = "Paket tidak ditemukan. Buat template paket dulu di admin.";
      results.appendChild(empty);
      results.classList.remove("d-none");
      activeChoiceIndexes.set(row, -1);
      return;
    }

    rows.forEach((item) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "workspace-search-result";
      button.dataset.packageChoice = "1";
      const primary = document.createElement("span");
      primary.className = "workspace-result-primary";
      primary.textContent = item?.service?.name || item?.service_product_template?.service_name || "Paket servis";
      const secondary = document.createElement("span");
      secondary.className = "workspace-result-secondary";
      secondary.textContent = (item?.product_lines || [])
        .map((line) => `${line.product_name || line.label} × ${line.qty || 1}`)
        .join(" + ");
      const additional = document.createElement("span");
      additional.className = "workspace-result-additional";
      additional.textContent = `Rp${format(packageTotal(item))} · ${item.stock_label || "stok dicek server"}`;
      button.append(primary, secondary, additional);
      button.addEventListener("click", () => {
        NS.applyPackageTemplate(row, item);
        clearResults(row);
        NS.focusElement?.(row.querySelector("[data-package-change]"), false);
      });
      results.appendChild(button);
    });

    results.classList.remove("d-none");
    setActiveChoice(row, 0);
  };

  const fetchPackages = async (row, input) => {
    const query = input.value.trim();
    const endpoint = NS.config?.packageLookupEndpoint;
    const token = Symbol("package-search");
    requestTokens.set(input, token);

    if (query.length < 2 || !endpoint) {
      clearResults(row);
      return;
    }

    try {
      const params = new URLSearchParams({ q: query });
      const separator = endpoint.includes("?") ? "&" : "?";
      const response = await fetch(`${endpoint}${separator}${params.toString()}`, {
        headers: { Accept: "application/json" },
      });

      if (!response.ok) {
        throw new Error(`Package lookup failed with status ${response.status}`);
      }

      const payload = await response.json();

      if (requestTokens.get(input) !== token) return;

      renderResults(row, payload?.data?.rows || []);
    } catch (_error) {
      if (requestTokens.get(input) === token) clearResults(row);
    }
  };

  const clearPackageState = (row) => {
    invalidateLookup(row);
    row.dataset.serviceProductTemplateApplied = "0";
    row.dataset.serviceTemplateAutofilled = "0";
    row.dataset.selectedPackageId = "";
    row.dataset.selectedPackageLabel = "";
    delete row.dataset.serviceTemplateDefaultPriceRupiah;
    setValue(row, "[data-requires-service-product-template]", "1");

    setValue(row, "[data-service-name]", "");
    setValue(row, "[data-service-catalog-id]", "");
    setValue(row, "[data-service-default-fee-rupiah]", "");
    setValue(row, "[data-service-price-raw]", "0");
    setValue(row, "[data-service-price-display]", "");

    ensureProductLineCount(row, 1);
    productLineScopes(row).forEach((scope) => {
      setValue(scope, "[data-product-search]", "");
      setValue(scope, "[data-product-id]", "");
      setValue(scope, "[data-price-basis]", "current_catalog");
      setValue(scope, "[data-qty-input]", "1");
      setValue(scope, 'input[name$="[unit_price_rupiah]"]', "");
      scope.dataset.minimumUnitPriceRupiah = "0";
      scope.dataset.availableStock = "0";
      const stockText = scope.querySelector("[data-stock-text]");
      if (stockText) stockText.textContent = "Stok tersedia: -";
      scope.querySelector("[data-stock-error]")?.classList.add("d-none");
      scope.querySelector("[data-min-price-warning]")?.classList.add("d-none");
    });

    packageSelectedSection(row)?.classList.add("d-none");
    row.querySelector("[data-package-search-stage]")?.classList.remove("d-none");
    row.querySelector("[data-package-product-list]")?.replaceChildren();
    setText(row, "[data-package-title]", "");
    setText(row, "[data-package-description]", "");
    setText(row, "[data-package-stock-text]", "");
    clearResults(row);
    NS.syncFloorPriceGuard?.(row);
    NS.syncQtyGuard?.(row);
    NS.updateSummary?.();
  };

  NS.restorePackageSelection = (row, item) => {
    if (!(row instanceof HTMLElement)) return;
    const productLines = Array.isArray(item?.product_lines) ? item.product_lines.slice(0, 3) : [];
    if (!productLines.some((line) => String(line?.product_id || "").trim() !== "")) return;

    row.dataset.selectedPackageLabel = String(item?.selected_label || item?.service?.name || "Paket servis");
    showPackageSelected(
      row,
      {
        ...item,
        stock_label: item?.stock_label || "Snapshot paket tersimpan",
      },
      productLines
    );
  };

  NS.bindPackageSearch = (row) => {
    if (!(row instanceof HTMLElement)) return;
    if ((row.dataset.itemType || "") !== "service_store_stock") return;
    if (row.dataset.packageSearchBound === "1") return;

    const input = packageSearchInput(row);
    if (!(input instanceof HTMLInputElement)) return;

    row.dataset.packageSearchBound = "1";

    input.addEventListener("input", () => {
      requestTokens.set(input, Symbol("package-search-input"));
      clearPackageState(row);
      clearTimeout(timers.get(input));
      timers.set(input, setTimeout(() => void fetchPackages(row, input), 250));
    });

    input.addEventListener("focus", () => {
      if (input.value.trim().length >= 2) {
        void fetchPackages(row, input);
      }
    });

    input.addEventListener("keydown", (event) => {
      const buttons = resultButtons(row);
      if (!buttons.length) return;

      const current = activeChoiceIndexes.get(row) ?? 0;

      if (event.key === "ArrowDown") {
        event.preventDefault();
        setActiveChoice(row, current + 1);
      } else if (event.key === "ArrowUp") {
        event.preventDefault();
        setActiveChoice(row, current - 1);
      } else if (event.key === "Enter") {
        event.preventDefault();
        buttons[activeChoiceIndexes.get(row) ?? 0]?.click();
      } else if (event.key === "Escape") {
        clearResults(row);
      }
    });

    row.querySelector("[data-package-change]")?.addEventListener("click", () => {
      clearPackageState(row);
      input.value = "";
      NS.focusElement?.(input);
    });

    document.addEventListener("click", (event) => {
      if (event.target instanceof Node && !row.contains(event.target)) clearResults(row);
    });
  };
})();
