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
  const serviceSearchInput = (row) => row.querySelector("[data-service-search]");
  const serviceResults = (row) => row.querySelector("[data-service-results]");
  const serviceRaw = (row) => row.querySelector("[data-service-price-raw]");
  const serviceDisplay = (row) => row.querySelector("[data-service-price-display]");
  const defaultFeeInput = (row) => row.querySelector("[data-service-default-fee-rupiah]");
  const catalogIdInput = (row) => row.querySelector("[data-service-catalog-id]");

  const setServiceSelectedState = (row, name, price) => {
    const selected = row.querySelector("[data-service-selected]");
    if (!selected) return;

    const query = serviceSearchInput(row);
    if (query) query.value = "";
    row.querySelector("[data-service-search-stage]")?.classList.add("d-none");
    selected.classList.remove("d-none");
    const nameText = selected.querySelector("[data-selected-service-name]");
    const priceText = selected.querySelector("[data-selected-service-price]");
    if (nameText) nameText.textContent = name || "Servis terpilih";
    if (priceText) priceText.textContent = price > 0 ? `Rp${format(price)}` : "Harga belum diisi";
  };

  const clearServiceSelectedState = (row) => {
    row.querySelector("[data-service-selected]")?.classList.add("d-none");
    row.querySelector("[data-service-search-stage]")?.classList.remove("d-none");
    row.querySelector("[data-selected-service-name]")?.replaceChildren();
    row.querySelector("[data-selected-service-price]")?.replaceChildren();
  };

  const invalidateLookup = (row) => {
    const input = serviceSearchInput(row);
    if (!(input instanceof HTMLInputElement)) return;
    clearTimeout(timers.get(input));
    requestTokens.set(input, Symbol("service-search-invalidated"));
  };

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

  const shouldAutofillServiceIdentity = (row) => {
    const input = serviceNameInput(row);
    const currentName = String(input?.value || "").trim();

    return (
      currentName === "" ||
      row.dataset.serviceTemplateAutofilled === "1" ||
      row.dataset.serviceNameManual !== "1"
    );
  };

  const setTemplateDetailsVisible = (row, visible) => {
    if (!(row instanceof HTMLElement)) return;
    if ((row.dataset.itemType || "") !== "service_store_stock") return;

    row.querySelectorAll("[data-template-selected-section]").forEach((section) => {
      section.classList.toggle("d-none", !visible);
    });

    row.querySelectorAll("[data-template-empty-section]").forEach((section) => {
      section.classList.toggle("d-none", visible);
    });

    const productName = row.querySelector("[data-template-product-name]");
    const productSearch = row.querySelector("[data-product-search]");
    if (productName) {
      productName.textContent = visible && productSearch?.value ? productSearch.value : "-";
    }
  };

  const clearTemplateState = (row) => {
    if (!(row instanceof HTMLElement)) return;
    if ((row.dataset.itemType || "") !== "service_store_stock") return;

    row.dataset.serviceProductTemplateApplied = "0";
    row.dataset.serviceTemplateAutofilled = "0";
    setTemplateDetailsVisible(row, false);
    delete row.dataset.serviceTemplateDefaultPriceRupiah;

    const input = serviceNameInput(row);
    if (input) input.value = "";

    if (catalogIdInput(row)) catalogIdInput(row).value = "";

    setMoney(serviceRaw(row), serviceDisplay(row), 0);
    window.AdminMoneyInput?.bindBySelector?.(row);
    NS.updateSummary?.();
  };

  NS.clearServiceProductTemplate = clearTemplateState;

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
    const name = serviceNameInput(row);
    const query = serviceSearchInput(row);
    invalidateLookup(row);
    if (name) name.value = item.label || "";
    if (query) query.value = "";
    if (catalogIdInput(row)) catalogIdInput(row).value = item.id || "";
    row.dataset.serviceNameManual = "1";
    row.dataset.serviceTemplateAutofilled = "0";

	    const price = digits(item.default_price_rupiah);
	    setDefaultFee(row, price, forceDisplay);
	    setServiceSelectedState(row, item.label || "", price);
	    clearResults(row);
    NS.updateSummary?.();
    NS.focusElement?.(serviceDisplay(row));
  };

  const renderResults = (row, items) => {
    const results = serviceResults(row);
    if (!results) return;

    results.innerHTML = "";
    items.forEach((item) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "workspace-search-result";
      button.dataset.serviceChoice = "1";
      const name = document.createElement("span");
      name.className = "workspace-result-primary";
      name.textContent = item.label || "Servis";
      const price = document.createElement("span");
      price.className = "workspace-result-additional";
      price.textContent = `Rp${format(item.default_price_rupiah)}`;
      button.append(name, price);
      button.addEventListener("click", () => selectService(row, item, true));
      results.appendChild(button);
    });

    results.classList.toggle("d-none", items.length === 0);
    setActiveChoice(row, 0);
  };

  const fetchServices = async (row, query) => {
    const endpoint = NS.config?.serviceLookupEndpoint;
    const input = serviceSearchInput(row);
    if (!endpoint || !input || String(query || "").trim().length < 2) return [];

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

  const exactMatch = async (row, name) => {
    const rows = await fetchServices(row, name);
    return rows.find((item) => normalize(item.normalized_name || item.label) === normalize(name));
  };

  const feeForCreate = (row) => {
    const stored = digits(defaultFeeInput(row)?.value || "");
    if (stored > 0) return stored;

    return digits(serviceRaw(row)?.value || serviceDisplay(row)?.value || "");
  };

  const ensureCatalog = async (row) => {
    if (String(catalogIdInput(row)?.value || "").trim() !== "") return;

    const name = serviceSearchInput(row)?.value?.trim() || "";
    if (name.length < 2) return;

    const price = feeForCreate(row);
    if (price <= 0) {
      const matched = await exactMatch(row, name);
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
      clearTemplateState(row);
      return;
    }

    row.dataset.serviceProductTemplateApplied = "1";
    setTemplateDetailsVisible(row, true);
    const canAutofillServiceIdentity = shouldAutofillServiceIdentity(row);
    const serviceName = String(template.service_name || "").trim();
    const serviceCatalogItemId = String(template.service_catalog_item_id || "").trim();
    const servicePrice = digits(template.default_service_price_rupiah);
    row.dataset.serviceTemplateDefaultPriceRupiah =
      servicePrice > 0 ? String(servicePrice) : "";

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

    window.AdminMoneyInput?.bindBySelector?.(row);
    NS.updateSummary?.();
  };

  NS.syncServiceDefaults = (row) => {
    if (!(row instanceof HTMLElement)) return;

    const existingFee = digits(defaultFeeInput(row)?.value || "");
    const rawFee = digits(serviceRaw(row)?.value || "");
    if (existingFee <= 0 && rawFee > 0) setDefaultFee(row, rawFee, false);

    const name = serviceNameInput(row)?.value?.trim() || "";
    if (name !== "") {
      setServiceSelectedState(row, name, digits(serviceRaw(row)?.value));
    }
  };

  NS.restoreSelectedService = (row) => {
    const name = serviceNameInput(row)?.value?.trim() || "";
    if (name === "") return;
    setServiceSelectedState(row, name, digits(serviceRaw(row)?.value));
  };

  NS.bindServiceCatalog = (row) => {
    if (!(row instanceof HTMLElement) || row.dataset.serviceCatalogBound === "1") return;
    row.dataset.serviceCatalogBound = "1";

    const name = serviceNameInput(row);
    if (!(name instanceof HTMLInputElement)) return;

    if ((row.dataset.itemType || "") === "service_store_stock") {
      name.readOnly = true;
      NS.syncServiceDefaults(row);
      serviceDisplay(row)?.addEventListener("input", () => {
        row.dataset.servicePriceManual = "1";
        NS.updateSummary?.();
      });
      return;
    }

    const input = serviceSearchInput(row);
    if (!(input instanceof HTMLInputElement)) return;

    input.addEventListener("input", () => {
      const selected = row.querySelector("[data-service-selected]");
      if (catalogIdInput(row)?.value && selected && !selected.classList.contains("d-none")) {
        input.value = "";
        return;
      }

      requestTokens.set(input, Symbol("service-search-input"));
      row.dataset.serviceNameManual = "1";
      row.dataset.serviceTemplateAutofilled = "0";
      clearTimeout(timers.get(input));
      timers.set(input, setTimeout(async () => renderResults(row, await fetchServices(row, input.value)), 250));
    });

    input.addEventListener("focus", async () => {
      if (input.value.trim().length >= 2) {
        renderResults(row, await fetchServices(row, input.value));
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
    input.addEventListener("blur", () => setTimeout(() => void ensureCatalog(row), 150));

    serviceDisplay(row)?.addEventListener("input", () => {
      row.dataset.servicePriceManual = "1";
      const name = serviceNameInput(row)?.value?.trim() || "";
      if (name !== "") setServiceSelectedState(row, name, digits(serviceDisplay(row)?.value));
    });
    serviceDisplay(row)?.addEventListener("blur", () => void ensureCatalog(row));

    document.addEventListener("click", (event) => {
      if (event.target instanceof Node && !row.contains(event.target)) clearResults(row);
    });

    row.querySelector("[data-service-change]")?.addEventListener("click", () => {
      invalidateLookup(row);
      clearServiceSelectedState(row);
      input.value = "";
      name.value = "";
      if (catalogIdInput(row)) catalogIdInput(row).value = "";
      if (defaultFeeInput(row)) defaultFeeInput(row).value = "";
      setMoney(serviceRaw(row), serviceDisplay(row), 0);
      row.dataset.serviceNameManual = "0";
      clearResults(row);
      NS.updateSummary?.();
      NS.focusElement?.(input);
    });

    NS.syncServiceDefaults(row);
  };
})();
