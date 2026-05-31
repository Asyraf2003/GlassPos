(() => {
  const NS = (window.CashierNoteWorkspace = window.CashierNoteWorkspace || {});

  const replaceIndex = (html, index) => html.replaceAll("__INDEX__", String(index));
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

  NS.firstFieldForRow = (row) => {
    const type = row?.dataset?.itemType || "";

    if (type === "product") {
      return (
        row.querySelector("[data-product-search]") ||
        row.querySelector("[data-qty-input]") ||
        row.querySelector("[data-price-input]")
      );
    }

    return (
      row.querySelector('input[name$="[service][name]"]') ||
      row.querySelector("[data-package-total-input]") ||
      row.querySelector("[data-product-search]") ||
      row.querySelector('input[name$="[external_purchase_lines][0][label]"]') ||
      row.querySelector("textarea")
    );
  };

  const rowFieldSequence = (row) => {
    const type = row?.dataset?.itemType || "";
    const moneyInputs = Array.from(row.querySelectorAll("[data-money-display]"));

    if (type === "product") {
      return [
        row.querySelector("[data-product-search]"),
        row.querySelector("[data-qty-input]"),
        row.querySelector("[data-price-input]"),
      ].filter(Boolean);
    }

    if (type === "service_store_stock") {
      return [
        row.querySelector('input[name$="[service][name]"]'),
        row.querySelector("[data-package-total-input]"),
        row.querySelector("[data-product-search]"),
        row.querySelector("[data-qty-input]"),
        row.querySelector("[data-price-input]"),
      ].filter(Boolean);
    }

    if (type === "service_external") {
      return [
        row.querySelector('input[name$="[service][name]"]'),
        moneyInputs[0],
        row.querySelector('input[name$="[external_purchase_lines][0][label]"]'),
        row.querySelector('input[name$="[external_purchase_lines][0][qty]"]'),
        moneyInputs[1],
        row.querySelector('textarea[name$="[service][notes]"]'),
      ].filter(Boolean);
    }

    return [
      row.querySelector('input[name$="[service][name]"]'),
      moneyInputs[0],
      row.querySelector('textarea[name$="[service][notes]"]'),
    ].filter(Boolean);
  };

  NS.bindRowKeyboard = (row) => {
    if (!(row instanceof HTMLElement)) return;
    if (row.dataset.keyboardBound === "1") return;

    row.dataset.keyboardBound = "1";

    rowFieldSequence(row).forEach((field, index, fields) => {
      if (!(field instanceof HTMLElement)) return;

      field.addEventListener("keydown", (event) => {
        if (event.key !== "Enter") return;
        if (event.ctrlKey || event.altKey || event.metaKey) return;

        event.preventDefault();

        if (event.shiftKey) {
          const prev = fields[index - 1];
          if (prev) {
            focusElement(prev);
          }
          return;
        }

        const next = fields[index + 1];
        if (next) {
          focusElement(next);
          return;
        }

        const addButton = document.getElementById("workspace-add-button");
        if (addButton) {
          focusElement(addButton, false);
        }
      });
    });
  };

  NS.bindQtyControls = (row) => {
    if (!(row instanceof HTMLElement)) return;
    if (row.dataset.qtyControlsBound === "1") return;

    row.dataset.qtyControlsBound = "1";

    row.querySelectorAll("[data-qty-increment], [data-qty-decrement]").forEach((button) => {
      if (!(button instanceof HTMLElement)) return;

      button.addEventListener("click", () => {
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
    NS.bindQtyControls?.(row);
    NS.bindRowKeyboard?.(row);
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
    const set = (selector, value) => {
      const el = row.querySelector(selector);
      if (el && value !== undefined && value !== null) el.value = String(value);
    };

    set('input[name$="[description]"]', item?.description || "");
    set('textarea[name$="[description]"]', item?.description || "");
    set("[data-pay-now]", item?.pay_now || "0");
    set('input[name$="[service][name]"]', item?.service?.name || "");
    set('input[name$="[service][notes]"]', item?.service?.notes || "");
    set('textarea[name$="[service][notes]"]', item?.service?.notes || "");
    set("[data-product-search]", item?.selected_label || "");
    set("[data-pricing-mode]", item?.pricing_mode || "package_auto_split");
    set('input[name$="[package_total_rupiah]"]', item?.package_total_rupiah || "");
    set("[data-product-id]", item?.product_lines?.[0]?.product_id || "");
    set("[data-price-basis]", item?.product_lines?.[0]?.price_basis || "current_catalog");
    set('input[name$="[external_purchase_lines][0][label]"]', item?.external_purchase_lines?.[0]?.label || "");
    set('input[name$="[external_purchase_lines][0][qty]"]', item?.external_purchase_lines?.[0]?.qty || "1");
    set('input[name$="[product_lines][0][qty]"]', item?.product_lines?.[0]?.qty || "1");
    set('input[name$="[service][price_rupiah]"]', item?.service?.price_rupiah ?? "0");
    set('input[name$="[product_lines][0][unit_price_rupiah]"]', item?.product_lines?.[0]?.unit_price_rupiah || "");
    set('input[name$="[external_purchase_lines][0][unit_cost_rupiah]"]', item?.external_purchase_lines?.[0]?.unit_cost_rupiah || "");

    if (type === "product" || type === "service_store_stock") {
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

  NS.updateStockText = (row, stock) => {
    const text = row.querySelector("[data-stock-text]");
    if (text) text.textContent = `Stok tersedia: ${stock}`;
    row.dataset.availableStock = String(stock || 0);
  };
})();
