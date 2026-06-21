(() => {
  const NS = (window.CashierNoteWorkspace = window.CashierNoteWorkspace || {});
  const timers = new WeakMap();
  const requestTokens = new WeakMap();
  const activeChoiceIndexes = new WeakMap();

  const parseDigits = (value) =>
    Number.parseInt(String(value || "").replace(/\D+/g, "") || "0", 10);

  const focusElement = (element, select = true) => {
    if (typeof NS.focusElement === "function") {
      NS.focusElement(element, select);
      return;
    }

    if (!(element instanceof HTMLElement)) return;

    window.requestAnimationFrame(() => {
      element.focus();

      if (select && typeof element.select === "function") {
        element.select();
      }
    });
  };

	  const productScope = (element) =>
	    element?.closest?.("[data-product-line]") ||
	    element?.closest?.("[data-line-item]") ||
	    element;

	  const isPrimaryServiceProductScope = (row, scope) =>
	    (row?.dataset?.itemType || "") === "service_store_stock" &&
	    (!(scope instanceof HTMLElement) || (scope.dataset.productLineIndex || "0") === "0");

  NS.syncFloorPriceGuard = (row) => {
    const scopes = row.querySelectorAll("[data-product-line]");
    const targets = scopes.length ? Array.from(scopes) : [row];

    targets.forEach((scope) => {
      const raw = scope.querySelector('[name$="[unit_price_rupiah]"]');
      const display = scope.querySelector("[data-price-input]");
      const warning = scope.querySelector("[data-min-price-warning]") || row.querySelector("[data-min-price-warning]");
      const text = scope.querySelector("[data-min-price-text]") || row.querySelector("[data-min-price-text]");

      const floor = parseDigits(scope.dataset.minimumUnitPriceRupiah || row.dataset.minimumUnitPriceRupiah || "0");
      const current = parseDigits(raw?.value || display?.value || "0");
      const invalid = floor > 0 && current > 0 && current < floor;

      if (text) {
        text.textContent =
          floor > 0 ? `Harga minimum: ${floor.toLocaleString("id-ID")}` : "Harga produk mengikuti katalog.";
      }

      if (display) {
        display.classList.toggle("is-invalid", invalid);
      }

      if (warning) {
        warning.classList.toggle("d-none", !invalid);
      }
    });
  };

  const resultButtons = (scope) =>
    Array.from(scope.querySelectorAll("[data-product-choice]"));

  const setActiveChoice = (scope, index) => {
    const buttons = resultButtons(scope);

    if (!buttons.length) {
      activeChoiceIndexes.set(scope, -1);
      return;
    }

    const nextIndex = Math.max(0, Math.min(index, buttons.length - 1));
    activeChoiceIndexes.set(scope, nextIndex);

    buttons.forEach((button, buttonIndex) => {
      button.classList.toggle("active", buttonIndex === nextIndex);
    });
  };

  const clearResults = (scope) => {
    const results = scope.querySelector("[data-product-results]");
    if (!results) return;

    results.innerHTML = "";
    results.classList.add("d-none");
    activeChoiceIndexes.set(scope, -1);
  };

  const renderResults = (row, scope, rows) => {
    const results = scope.querySelector("[data-product-results]");
    if (!results) return;

    results.innerHTML = "";

    if (!rows.length) {
      results.classList.add("d-none");
      activeChoiceIndexes.set(scope, -1);
      return;
    }

    rows.forEach((item) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "list-group-item list-group-item-action";
      button.dataset.productChoice = "1";
      button.textContent = `${item.label} · stok ${item.available_stock}`;
      button.addEventListener("click", () => NS.selectProduct(row, item, scope));
      results.appendChild(button);
    });

    results.classList.remove("d-none");
    setActiveChoice(scope, 0);
  };

  NS.selectProduct = (row, item, explicitScope = null) => {
    const scope = explicitScope || productScope(row.querySelector("[data-product-search]"));
    const search = scope.querySelector("[data-product-search]");
    const hidden = scope.querySelector("[data-product-id]");
    const raw = scope.querySelector('[name$="[unit_price_rupiah]"]');
    const display = scope.querySelector("[data-price-input]");
    const qty = scope.querySelector("[data-qty-input]");
    const priceBasis = scope.querySelector("[data-price-basis]");

    if (!search || !hidden) return;

    search.value = item.label;
    hidden.value = item.id;

    if (priceBasis) {
      priceBasis.value = "current_catalog";
    }

    scope.dataset.minimumUnitPriceRupiah = String(
      item.minimum_unit_price_rupiah || item.default_unit_price_rupiah || 0
    );

    if (raw) {
      raw.value = String(item.default_unit_price_rupiah || 0);
    }

    if (display) {
      display.value = String(item.default_unit_price_rupiah || 0);
    }

	    if (isPrimaryServiceProductScope(row, scope)) {
	      NS.applyServiceProductTemplate?.(row, item.service_product_template || null, scope);
	    } else {
	      scope.querySelectorAll("[data-template-selected-section]").forEach((section) => {
	        section.classList.remove("d-none");
	      });
	    }
    NS.updateStockText?.(row, item.available_stock, scope);
    window.AdminMoneyInput?.bindBySelector?.(row);
    NS.syncFloorPriceGuard?.(row);
    clearResults(scope);
    NS.syncQtyGuard?.(row);
    NS.syncServiceDefaults?.(row);
    NS.updateSummary?.();

    if ((row?.dataset?.itemType || "") === "service_store_stock") {
      focusElement(row.querySelector("[data-package-total-input]") || qty);
      return;
    }

    focusElement(qty);
  };

  NS.bindProductSearch = (row) => {
    row.querySelectorAll("[data-product-search]").forEach((input) => {
      const scope = productScope(input);
      const hidden = scope.querySelector("[data-product-id]");

      if (!(input instanceof HTMLInputElement)) return;
      if (input.dataset.searchBound === "1") return;
      input.dataset.searchBound = "1";

      const fetchResults = async () => {
        const query = input.value.trim();
        const endpoint = NS.config?.productLookupEndpoint;

        if (!hidden) {
          clearResults(scope);
          return;
        }

        hidden.value = "";
        scope.querySelector("[data-price-basis]")?.setAttribute("value", "current_catalog");

        if (query.length < 2 || !endpoint) {
          clearResults(scope);
          NS.updateSummary?.();
          return;
        }

        const token = Symbol("product-search");
        requestTokens.set(input, token);

        try {
          const params = new URLSearchParams({ q: query });

	          if (isPrimaryServiceProductScope(row, scope)) {
	            params.set("context", "service_product");
	          }

          const separator = endpoint.includes("?") ? "&" : "?";
          const url = `${endpoint}${separator}${params.toString()}`;
          const response = await fetch(url, { headers: { Accept: "application/json" } });

          if (!response.ok) {
            throw new Error(`Product lookup failed with status ${response.status}`);
          }

          const payload = await response.json();

          if (requestTokens.get(input) !== token) {
            return;
          }

          renderResults(row, scope, payload?.data?.rows || []);
        } catch (_error) {
          if (requestTokens.get(input) !== token) {
            return;
          }

          clearResults(scope);
        }
      };

      input.addEventListener("input", () => {
        if (hidden) {
          hidden.value = "";
        }

	        if (isPrimaryServiceProductScope(row, scope)) {
	          NS.clearServiceProductTemplate?.(row);
	        }

        const raw = scope.querySelector('[name$="[unit_price_rupiah]"]');
        if (raw) {
          raw.value = "";
        }

        clearTimeout(timers.get(input));

        timers.set(
          input,
          setTimeout(() => {
            void fetchResults();
          }, 250)
        );
      });

      input.addEventListener("focus", () => {
        if (input.value.trim().length >= 2) {
          void fetchResults();
        }
      });

      if (scope.dataset.searchOutsideBound !== "1") {
        scope.dataset.searchOutsideBound = "1";

        document.addEventListener("click", (event) => {
          if (!scope.contains(event.target)) {
            clearResults(scope);
          }
        });
      }
    });
  };
})();
