(() => {
  const NS = (window.CashierNoteWorkspace = window.CashierNoteWorkspace || {});

  const replaceIndex = (html, index) => html.replaceAll("__INDEX__", String(index));
  const replaceProductIndex = (html, index) =>
    html.replaceAll("__PRODUCT_INDEX__", String(index));
  const titleByType = (type, number) => `Rincian ${number} · ${NS.labelByType(type)}`;
  const digits = (value) => Number.parseInt(String(value || "").replace(/\D+/g, "") || "0", 10);

  const focusElement = (element, select = true) => {
    if (!(element instanceof HTMLElement)) return;

    window.requestAnimationFrame(() => {
      element.focus();

      if (select && typeof element.select === "function") {
        element.select();
      }
    });
  };

  NS.focusElement = focusElement;

  NS.labelByType = (type) =>
    ({
      product: "Produk",
      service: "Servis",
      service_store_stock: "Servis + Sparepart Toko",
      service_external: "Servis + Pembelian Luar",
    })[type] || "Rincian";

  NS.detectType = (item) => {
    if ((item?.entry_mode || "") === "product") return "product";
    if ((item?.part_source || "") === "store_stock") return "service_store_stock";
    if ((item?.part_source || "") === "external_purchase") return "service_external";
    return "service";
  };

  const productLineScopes = (row) => Array.from(row.querySelectorAll("[data-product-line]"));

  const updateProductLineRemoveState = (row) => {
    const scopes = productLineScopes(row);
    scopes.forEach((scope) => {
      const button = scope.querySelector("[data-remove-product-line]");
      if (button) button.classList.toggle("d-none", scopes.length <= 1);
    });
  };

  const reindexProductLines = (row) => {
    productLineScopes(row).forEach((scope, index) => {
      scope.dataset.productLineIndex = String(index);

      scope.querySelectorAll("[name]").forEach((input) => {
        input.name = input.name.replace(/\[product_lines\]\[\d+\]/, `[product_lines][${index}]`);
      });
    });

    updateProductLineRemoveState(row);
  };

  NS.reindexProductLines = reindexProductLines;

  const setValue = (root, selector, value) => {
    const el = root.querySelector(selector);
    if (el && value !== undefined && value !== null) el.value = String(value);
  };

	  const setProductLineValues = (row, scope, line = {}, selectedLabel = "") => {
    if (!(scope instanceof HTMLElement)) return;

    setValue(scope, "[data-product-search]", line?.selected_label || line?.product_label || selectedLabel || "");
    setValue(scope, "[data-product-id]", line?.product_id || "");
    setValue(scope, "[data-price-basis]", line?.price_basis || "current_catalog");
    setValue(scope, "[data-qty-input]", line?.qty || "1");
    setValue(scope, 'input[name$="[unit_price_rupiah]"]', line?.unit_price_rupiah || "");

    if (line?.available_stock !== undefined && line?.available_stock !== null) {
      NS.updateStockText?.(row, line.available_stock, scope);
    }
	  };

	  const externalLineTotal = (line = {}) => {
	    const total = digits(line?.total_rupiah || "");
	    if (total > 0) return total;

	    const qty = digits(line?.qty || "");
	    const unitCost = digits(line?.unit_cost_rupiah || "");
	    return qty > 0 && unitCost > 0 ? qty * unitCost : "";
	  };

		  const appendProductLine = (row, line = {}, focus = false) => {
		    const root = row.querySelector("[data-product-lines]");
		    const template = row.querySelector("[data-product-line-template]");

    if (!root || !(template instanceof HTMLTemplateElement)) {
      return null;
    }

    if (productLineScopes(row).length >= 3) {
      updateProductLineRemoveState(row);
      return null;
    }

    const index = productLineScopes(row).length;
    const wrapper = document.createElement("div");

    wrapper.innerHTML = replaceProductIndex(template.innerHTML, index).trim();
    const scope = wrapper.firstElementChild;

    if (!(scope instanceof HTMLElement)) {
      return null;
    }

		    root.appendChild(scope);
	    setProductLineValues(row, scope, line);
	    if (row?.dataset?.serviceProductTemplateApplied === "1") {
	      scope.querySelectorAll("[data-template-selected-section]").forEach((section) => {
	        section.classList.remove("d-none");
	      });
	    }
		    reindexProductLines(row);
    updateAddProductLineState(row);

    window.AdminMoneyInput?.bindBySelector?.(scope);
    NS.bindProductSearch?.(row);
    NS.syncServiceDefaults?.(row);
    NS.syncFloorPriceGuard?.(row);
    NS.syncQtyGuard?.(row);
    NS.updateSummary?.();

    if (focus) {
      focusElement(scope.querySelector("[data-product-search]"));
    }

    return scope;
	  };

  NS.addProductLine = (row, line = {}, focus = true) => appendProductLine(row, line, focus);

	  NS.bindProductLines = (row) => {
    if (!(row instanceof HTMLElement)) return;
    if (row.dataset.productLinesBound === "1") return;

    row.dataset.productLinesBound = "1";

    row.addEventListener("click", (event) => {
      if (!(event.target instanceof Element)) return;

      const addButton = event.target.closest("[data-add-product-line]");
      if (addButton && row.contains(addButton)) {
	        event.preventDefault();
	        appendProductLine(row, {}, true);
	        return;
      }

      const removeButton = event.target.closest("[data-remove-product-line]");
      if (!removeButton || !row.contains(removeButton)) return;

      event.preventDefault();

      const scope = removeButton.closest("[data-product-line]");
      if (!(scope instanceof HTMLElement)) return;
      if (productLineScopes(row).length <= 1) return;

	      scope.remove();
	      reindexProductLines(row);
      updateAddProductLineState(row);
	      NS.syncFloorPriceGuard?.(row);
      NS.syncQtyGuard?.(row);
      NS.syncServiceDefaults?.(row);
      NS.updateSummary?.();
    });
	  };

  const updateAddProductLineState = (row) => {
    const addButton = row.querySelector("[data-add-product-line]");
    if (!addButton) return;

    const isAtLimit = productLineScopes(row).length >= 3;
    addButton.disabled = isAtLimit;
    addButton.classList.toggle("d-none", isAtLimit);
  };

	  NS.firstFieldForRow = (row) => {
	    const type = row?.dataset?.itemType || "";

	    if (type === "product") {
	      return (
        row.querySelector("[data-product-search]") ||
        row.querySelector("[data-qty-input]") ||
        row.querySelector("[data-price-input]")
	      );
	    }

	    if (type === "service_store_stock") {
	      return (
	        row.querySelector("[data-package-search]") ||
	        row.querySelector("[data-service-price-display]") ||
	        row.querySelector("[data-product-search]")
	      );
	    }
	
		    return (
		      row.querySelector("[data-product-search]") ||
		      row.querySelector('input[name$="[service][name]"]') ||
	      row.querySelector("[data-service-price-display]") ||
	      row.querySelector("[data-product-search]") ||
      row.querySelector('input[name$="[external_purchase_lines][0][label]"]') ||
      row.querySelector("textarea")
    );
  };

  NS.bindQtyControls = (row) => {
    if (!(row instanceof HTMLElement)) return;
    if (row.dataset.qtyControlsBound === "1") return;

    row.dataset.qtyControlsBound = "1";

    row.addEventListener("click", (event) => {
      if (!(event.target instanceof Element)) return;

      const button = event.target.closest("[data-qty-increment], [data-qty-decrement]");
      if (!button || !row.contains(button)) return;

      const input = button
        .closest(".workspace-qty-control")
        ?.querySelector("[data-qty-input]");

      if (!(input instanceof HTMLInputElement)) return;

      const current = Math.max(digits(input.value), 1);
      const next = button.hasAttribute("data-qty-decrement")
        ? Math.max(current - 1, 1)
        : current + 1;

      input.value = String(next);
      input.dispatchEvent(new Event("input", { bubbles: true }));
      NS.updateSummary?.();
    });
  };

  NS.addRow = (type, initial = {}) => {
    const root = document.getElementById("workspace-line-items");
    const template = document.getElementById(`workspace-template-${type}`);
    const emptyState = document.getElementById("workspace-empty-state");

    if (!root || !template || !emptyState) return null;

    const index = Number(root.dataset.nextIndex || "0");
    const wrapper = document.createElement("div");

    wrapper.innerHTML = replaceIndex(template.innerHTML, index);
    const row = wrapper.firstElementChild;

    if (!(row instanceof HTMLElement)) {
      return null;
    }

    row.dataset.rowIndex = String(index);
    root.appendChild(row);
    root.dataset.nextIndex = String(index + 1);
    emptyState.classList.add("d-none");

    NS.applyInitialValues(row, type, initial);
    window.AdminMoneyInput?.bindBySelector?.(row);
    NS.bindProductSearch?.(row);
    NS.bindPackageSearch?.(row);
    NS.bindServiceCatalog?.(row);
    NS.bindQtyControls?.(row);
    NS.bindProductLines?.(row);
    NS.renumberRows();
    NS.updateSummary?.();

    const hasInitialValues =
      !!initial &&
      typeof initial === "object" &&
      Object.keys(initial).length > 0;

    if (!hasInitialValues) {
      focusElement(NS.firstFieldForRow(row));
    }

    return row;
  };

  NS.applyInitialValues = (row, type, item) => {
    const set = (selector, value) => setValue(row, selector, value);

    set('input[name$="[description]"]', item?.description || "");
    set('textarea[name$="[description]"]', item?.description || "");
    set("[data-pay-now]", item?.pay_now || "0");
    set('input[name$="[service][name]"]', item?.service?.name || "");
    set("[data-service-default-fee-rupiah]", item?.service?.price_rupiah || "");
	    set("[data-pricing-mode]", item?.pricing_mode || "package_auto_split");
		    set('input[name$="[external_purchase_lines][0][label]"]', item?.external_purchase_lines?.[0]?.label || "");
		    set('input[name$="[service][price_rupiah]"]', item?.service?.price_rupiah ?? "0");
	    set(
	      'input[name$="[external_purchase_lines][0][total_rupiah]"]',
	      externalLineTotal(item?.external_purchase_lines?.[0] || {}),
	    );

	    if (type === "service_store_stock") {
		      const productLines = (
		        Array.isArray(item?.product_lines) && item.product_lines.length > 0
		          ? item.product_lines.slice(0, 3)
		          : [{}]
		      );

      while (productLineScopes(row).length < productLines.length) {
        appendProductLine(row, {}, false);
      }

      productLineScopes(row).forEach((scope, index) => {
        setProductLineValues(
          row,
          scope,
          productLines[index] || {},
          index === 0 ? item?.selected_label || "" : "",
        );
      });

	      reindexProductLines(row);
      updateAddProductLineState(row);
	      return;
    }

    set("[data-product-search]", item?.selected_label || "");
    set("[data-product-id]", item?.product_lines?.[0]?.product_id || "");
    set("[data-price-basis]", item?.product_lines?.[0]?.price_basis || "current_catalog");
    set('input[name$="[product_lines][0][qty]"]', item?.product_lines?.[0]?.qty || "1");
    set('input[name$="[product_lines][0][unit_price_rupiah]"]', item?.product_lines?.[0]?.unit_price_rupiah || "");

    if (type === "product") {
      NS.updateStockText(row, item?.available_stock || 0);
    }
  };

  NS.renumberRows = () => {
    document.querySelectorAll("[data-line-item]").forEach((row, index) => {
      const title = row.querySelector("[data-line-title]");
      if (title) title.textContent = titleByType(row.dataset.itemType || "", index + 1);
    });
  };

  NS.removeRow = (row) => {
    row.remove();
    const emptyState = document.getElementById("workspace-empty-state");
    if (!document.querySelector("[data-line-item]")) emptyState?.classList.remove("d-none");
    NS.renumberRows();
    NS.updateSummary?.();
  };

  NS.updateStockText = (row, stock, scope = null) => {
    const target = scope instanceof HTMLElement ? scope : row;
    const text = target.querySelector("[data-stock-text]") || row.querySelector("[data-stock-text]");

    if (text) text.textContent = `Stok tersedia: ${stock}`;

    target.dataset.availableStock = String(stock || 0);

    if (!(scope instanceof HTMLElement)) {
      row.dataset.availableStock = String(stock || 0);
    }
  };
})();
