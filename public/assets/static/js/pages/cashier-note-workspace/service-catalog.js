(() => {
  const NS = (window.CashierNoteWorkspace = window.CashierNoteWorkspace || {});
  const timers = new WeakMap();
  const requestTokens = new WeakMap();
  const activeChoiceIndexes = new WeakMap();

  const digits = (value) =>
    Number.parseInt(String(value || "").replace(/\D+/g, "") || "0", 10);
  const format = (value) => Number(value || 0).toLocaleString("id-ID");
  const normalize = (value) =>
    String(value || "")
      .trim()
      .toLowerCase()
      .replace(/[^\p{L}\p{N}]+/gu, " ")
      .replace(/\s+/g, " ")
      .trim();

  const serviceNameInput = (row) => row.querySelector("[data-service-name]");
  const serviceResults = (row) => row.querySelector("[data-service-results]");
  const serviceRaw = (row) => row.querySelector("[data-service-price-raw]");
  const serviceDisplay = (row) => row.querySelector("[data-service-price-display]");
  const defaultFeeInput = (row) => row.querySelector("[data-service-default-fee-rupiah]");
  const catalogIdInput = (row) => row.querySelector("[data-service-catalog-id]");
  const packageRaw = (row) => row.querySelector('input[name$="[package_total_rupiah]"]');
  const packageDisplay = (row) => row.querySelector("[data-package-total-input]");

  const setMoney = (raw, display, amount) => {
    if (raw) raw.value = amount > 0 ? String(amount) : "";
    if (display) display.value = amount > 0 ? format(amount) : "";
  };

  const setDefaultFee = (row, amount, forceDisplay = false) => {
    defaultFeeInput(row)?.setAttribute("value", amount > 0 ? String(amount) : "");
    if (defaultFeeInput(row)) defaultFeeInput(row).value = amount > 0 ? String(amount) : "";

    const raw = serviceRaw(row);
    const display = serviceDisplay(row);
    const displayEmpty = !display || digits(display.value) <= 0;

    if (forceDisplay || displayEmpty || row.dataset.servicePriceManual !== "1") {
      setMoney(raw, display, amount);
    }

    window.AdminMoneyInput?.bindBySelector?.(row);
  };

  const rowProductTotal = (row) =>
    typeof NS.rowProductTotal === "function" ? NS.rowProductTotal(row) : 0;

  const setPackageTotal = (row, amount) => {
    setMoney(packageRaw(row), packageDisplay(row), amount);
    row.dataset.servicePackageAutofilled = "1";
  };

  const currentPackageTotal = (row) =>
    digits(packageRaw(row)?.value || packageDisplay(row)?.value || "");

  const shouldAutofillServiceIdentity = (row) => {
    const input = serviceNameInput(row);
    const currentName = String(input?.value || "").trim();

    return (
      currentName === "" ||
      row.dataset.serviceTemplateAutofilled === "1" ||
      row.dataset.serviceNameManual !== "1"
    );
  };

  const syncPackageTotal = (row, force = false) => {
    if ((row.dataset.itemType || "") !== "service_store_stock") return;

    const fee = digits(defaultFeeInput(row)?.value || serviceRaw(row)?.value || "");
    const productTotal = rowProductTotal(row);
    if (fee <= 0 || productTotal <= 0) return;

    const current = currentPackageTotal(row);
    const shouldSync = force || row.dataset.servicePackageAutofilled === "1" || current <= 0;
    if (!shouldSync) return;

    setPackageTotal(row, fee + productTotal);
  };

  const clearResults = (row) => {
    const results = serviceResults(row);
    if (!results) return;
    results.innerHTML = "";
    results.classList.add("d-none");
    activeChoiceIndexes.set(row, -1);
  };

  const resultButtons = (row) =>
    Array.from(serviceResults(row)?.querySelectorAll("[data-service-choice]") || []);

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

  const selectService = (row, item, forceDisplay = true) => {
    const input = serviceNameInput(row);
    if (input) input.value = item.label || "";
    if (catalogIdInput(row)) catalogIdInput(row).value = item.id || "";
    row.dataset.serviceNameManual = "1";
    row.dataset.serviceTemplateAutofilled = "0";

    setDefaultFee(row, digits(item.default_price_rupiah), forceDisplay);
    syncPackageTotal(row, true);
    clearResults(row);
    NS.updateSummary?.();
  };

  const renderResults = (row, items) => {
    const results = serviceResults(row);
    if (!results) return;

    results.innerHTML = "";
    items.forEach((item) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "list-group-item list-group-item-action";
      button.dataset.serviceChoice = "1";
      button.textContent = `${item.label} · ${format(item.default_price_rupiah)}`;
      button.addEventListener("click", () => selectService(row, item, true));
      results.appendChild(button);
    });

    results.classList.toggle("d-none", items.length === 0);
    setActiveChoice(row, 0);
  };

  const fetchServices = async (row, query) => {
    const endpoint = NS.config?.serviceLookupEndpoint;
    const input = serviceNameInput(row);
    if (!endpoint || !input) return [];

    const token = Symbol("service-search");
    requestTokens.set(input, token);

    try {
      const response = await fetch(`${endpoint}?q=${encodeURIComponent(query)}`, {
        headers: { Accept: "application/json" },
      });
      const payload = await response.json();

      if (requestTokens.get(input) !== token) return [];
      return payload?.data?.rows || [];
    } catch (_error) {
      if (requestTokens.get(input) === token) clearResults(row);
      return [];
    }
  };

  const exactMatch = async (row) => {
    const name = serviceNameInput(row)?.value || "";
    const rows = await fetchServices(row, name);
    return rows.find((item) => normalize(item.normalized_name || item.label) === normalize(name));
  };

  const feeForCreate = (row) => {
    const stored = digits(defaultFeeInput(row)?.value || "");
    if (stored > 0) return stored;

    if ((row.dataset.itemType || "") === "service_store_stock") {
      return Math.max(currentPackageTotal(row) - rowProductTotal(row), 0);
    }

    return digits(serviceRaw(row)?.value || serviceDisplay(row)?.value || "");
  };

  const ensureCatalog = async (row) => {
    const name = serviceNameInput(row)?.value?.trim() || "";
    if (name.length < 2) return;

    const price = feeForCreate(row);
    if (price <= 0) {
      const matched = await exactMatch(row);
      if (matched) selectService(row, matched, false);
      return;
    }

    const endpoint = NS.config?.serviceStoreEndpoint;
    if (!endpoint) return;

    const response = await fetch(endpoint, {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": String(NS.config?.csrfToken || ""),
      },
      credentials: "same-origin",
      body: JSON.stringify({ name, default_price_rupiah: price }),
    });
    const payload = await response.json();
    const rowData = payload?.data?.row;
    if (response.ok && rowData) selectService(row, rowData, row.dataset.servicePriceManual !== "1");
  };

  NS.applyServiceProductTemplate = (row, template) => {
    if (!(row instanceof HTMLElement)) return;
    if ((row.dataset.itemType || "") !== "service_store_stock") return;
    if (!template || typeof template !== "object") {
      delete row.dataset.serviceTemplateDefaultPriceRupiah;
      return;
    }
    const canAutofillServiceIdentity = shouldAutofillServiceIdentity(row);
    const serviceName = String(template.service_name || "").trim();
    const serviceCatalogItemId = String(template.service_catalog_item_id || "").trim();
    const servicePrice = digits(template.default_service_price_rupiah);
    row.dataset.serviceTemplateDefaultPriceRupiah =
      servicePrice > 0 ? String(servicePrice) : "";
    const templatePackageTotal = digits(template.default_package_total_rupiah);

    if (canAutofillServiceIdentity && serviceName !== "") {
      const input = serviceNameInput(row);
      if (input) input.value = serviceName;
      row.dataset.serviceNameManual = "0";
      row.dataset.serviceTemplateAutofilled = "1";
    }

    if (canAutofillServiceIdentity && serviceCatalogItemId !== "" && catalogIdInput(row)) {
      catalogIdInput(row).value = serviceCatalogItemId;
    }

    if (servicePrice > 0 && row.dataset.servicePriceManual !== "1") {
      setDefaultFee(row, servicePrice, true);
    }

    if (templatePackageTotal > 0) {
      const current = currentPackageTotal(row);
      const shouldSyncPackage = row.dataset.servicePackageAutofilled === "1" || current <= 0;

      if (shouldSyncPackage) {
        setPackageTotal(row, templatePackageTotal);
      }
    } else {
      syncPackageTotal(row, false);
    }

    window.AdminMoneyInput?.bindBySelector?.(row);
    NS.updateSummary?.();
  };

  NS.syncServiceDefaults = (row, options = {}) => {
    if (!(row instanceof HTMLElement)) return;

    const existingFee = digits(defaultFeeInput(row)?.value || "");
    const rawFee = digits(serviceRaw(row)?.value || "");
    if (existingFee <= 0 && rawFee > 0) setDefaultFee(row, rawFee, false);

    syncPackageTotal(row, options.force === true);
  };

  NS.bindServiceCatalog = (row) => {
    if (!(row instanceof HTMLElement) || row.dataset.serviceCatalogBound === "1") return;
    row.dataset.serviceCatalogBound = "1";

    const input = serviceNameInput(row);
    if (!(input instanceof HTMLInputElement)) return;

    input.addEventListener("input", () => {
      row.dataset.serviceNameManual = "1";
      row.dataset.serviceTemplateAutofilled = "0";
      catalogIdInput(row)?.setAttribute("value", "");
      if (catalogIdInput(row)) catalogIdInput(row).value = "";
      clearTimeout(timers.get(input));
      timers.set(input, setTimeout(async () => renderResults(row, await fetchServices(row, input.value)), 250));
    });

    input.addEventListener("focus", async () => renderResults(row, await fetchServices(row, input.value)));
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
      }
    });
    input.addEventListener("blur", () => setTimeout(() => void ensureCatalog(row), 150));

    serviceDisplay(row)?.addEventListener("input", () => {
      row.dataset.servicePriceManual = "1";
    });
    serviceDisplay(row)?.addEventListener("blur", () => void ensureCatalog(row));

    packageDisplay(row)?.addEventListener("input", () => {
      row.dataset.servicePackageAutofilled = "0";
    });
    packageDisplay(row)?.addEventListener("blur", () => void ensureCatalog(row));

    row.addEventListener("input", (event) => {
      if (event.target instanceof Element && event.target.matches("[data-qty-input]")) {
        syncPackageTotal(row, false);
      }
    });

    document.addEventListener("click", (event) => {
      if (event.target instanceof Node && !row.contains(event.target)) clearResults(row);
    });

    NS.syncServiceDefaults(row);
  };
})();
