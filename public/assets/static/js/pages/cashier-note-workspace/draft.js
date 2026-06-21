(() => {
  const NS = (window.CashierNoteWorkspace = window.CashierNoteWorkspace || {});
  const configEl = document.getElementById("cashier-note-workspace-config");
  if (!configEl) return;

  let isSubmitting = false;

  const parseConfig = () => {
    try {
      return JSON.parse(configEl.textContent || "{}");
    } catch (_error) {
      return {};
    }
  };

  const writeConfig = (config) => {
    configEl.textContent = JSON.stringify(config);
    NS.config = config;
  };

  const hasBlockingServerOldInput = (config) => config?.hasOldInput === true;

  const mergeProductLine = (draftLine, baseLine) => ({
    product_id: draftLine?.product_id || baseLine?.product_id || "",
    qty: draftLine?.qty || baseLine?.qty || "1",
    unit_price_rupiah:
      draftLine?.unit_price_rupiah || baseLine?.unit_price_rupiah || "",
    price_basis: draftLine?.price_basis || baseLine?.price_basis || "current_catalog",
    selected_label: draftLine?.selected_label || baseLine?.selected_label || "",
  });

	  const mergeExternalLine = (draftLine, baseLine) => ({
	    label: draftLine?.label || baseLine?.label || "",
	    qty: draftLine?.qty || baseLine?.qty || "1",
	    unit_cost_rupiah:
	      draftLine?.unit_cost_rupiah || baseLine?.unit_cost_rupiah || "",
	    total_rupiah:
	      draftLine?.total_rupiah || baseLine?.total_rupiah || "",
	  });

  const mergeProductLines = (draftLines, baseLines) => {
    const draftList = Array.isArray(draftLines) ? draftLines : [];
    const baseList = Array.isArray(baseLines) ? baseLines : [];
    const maxLength = Math.max(draftList.length, baseList.length, 1);
    const lines = [];

    for (let index = 0; index < maxLength; index += 1) {
      lines.push(mergeProductLine(draftList[index], baseList[index]));
    }

    return lines;
  };

  const mergeService = (draftService, baseService) => ({
    name: draftService?.name || baseService?.name || "",
    price_rupiah: draftService?.price_rupiah || baseService?.price_rupiah || "",
    notes: draftService?.notes || baseService?.notes || "",
  });

  const mergeItemWithPreload = (draftItem, preloadItem) => ({
    ...preloadItem,
    ...draftItem,
    entry_mode: draftItem?.entry_mode || preloadItem?.entry_mode || "",
    description: draftItem?.description || preloadItem?.description || "",
    part_source: draftItem?.part_source || preloadItem?.part_source || "",
    selected_label: draftItem?.selected_label || preloadItem?.selected_label || "",
    service: mergeService(draftItem?.service, preloadItem?.service),
    product_lines: mergeProductLines(draftItem?.product_lines, preloadItem?.product_lines),
    external_purchase_lines: [
      mergeExternalLine(
        Array.isArray(draftItem?.external_purchase_lines)
          ? draftItem.external_purchase_lines[0]
          : null,
        Array.isArray(preloadItem?.external_purchase_lines)
          ? preloadItem.external_purchase_lines[0]
          : null,
      ),
    ],
  });

  const mergeDraftItemsWithPreload = (draftItems, preloadItems) => {
    const draftList = Array.isArray(draftItems) ? draftItems : [];
    const preloadList = Array.isArray(preloadItems) ? preloadItems : [];
    const maxLength = Math.max(draftList.length, preloadList.length);
    const merged = [];

    for (let index = 0; index < maxLength; index += 1) {
      const draftItem = draftList[index];
      const preloadItem = preloadList[index];

      if (draftItem && preloadItem) {
        merged.push(mergeItemWithPreload(draftItem, preloadItem));
        continue;
      }

      if (draftItem) {
        merged.push(draftItem);
        continue;
      }

      if (preloadItem) {
        merged.push(preloadItem);
      }
    }

    return merged;
  };

  const restoreFromServer = async () => {
    const config = parseConfig();
    writeConfig(config);

    if (hasBlockingServerOldInput(config)) {
      return config;
    }

    const endpoint = String(config.draftLoadEndpoint || "").trim();
    if (!endpoint) {
      return config;
    }

    const url = new URL(endpoint, window.location.origin);
    url.searchParams.set("workspace_mode", String(config.workspaceMode || "create"));
    if (config.noteId) {
      url.searchParams.set("note_id", String(config.noteId));
    }

    try {
      const response = await fetch(url.toString(), {
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
      });

      const payload = await response.json();
      if (!response.ok || payload?.success !== true) {
        return config;
      }

      const draft = payload?.data?.draft;
      const draftPayload = draft?.payload;

      if (!draftPayload || typeof draftPayload !== "object") {
        return config;
      }

      const merged = {
        ...config,
        oldNote:
          typeof draftPayload.note === "object" && draftPayload.note !== null
            ? { ...(config.oldNote || {}), ...draftPayload.note }
            : config.oldNote || {},
        oldItems: mergeDraftItemsWithPreload(
          draftPayload.items,
          config.oldItems,
        ),
        oldInlinePayment:
          typeof draftPayload.inline_payment === "object" &&
          draftPayload.inline_payment !== null
            ? { ...(config.oldInlinePayment || {}), ...draftPayload.inline_payment }
            : config.oldInlinePayment || {},
        draftMeta: {
          restored_at: new Date().toISOString(),
          updated_at: draft.updated_at || null,
          workspace_key: payload?.data?.workspace_key || null,
        },
      };

      writeConfig(merged);
      return merged;
    } catch (_error) {
      return config;
    }
  };

  const numberText = (value) => String(value ?? "").replace(/\D+/g, "");
  const valueOf = (selector, root = document) => root.querySelector(selector)?.value || "";

  const productLineScopes = (row) => {
    const scopes = Array.from(row.querySelectorAll("[data-product-line]"));
    return scopes.length ? scopes : [row];
  };

	  const productLinesFromRow = (row) =>
	    productLineScopes(row).map((scope) => ({
	      product_id: valueOf("[data-product-id]", scope),
	      qty: numberText(valueOf("[data-qty-input]", scope)) || "1",
	      unit_price_rupiah: numberText(valueOf('input[name$="[unit_price_rupiah]"]', scope)),
	      price_basis: valueOf("[data-price-basis]", scope) || "current_catalog",
	      selected_label: valueOf("[data-product-search]", scope),
	    }));

	  const firstProductLineFromRow = (row) => {
	    const firstLine = productLinesFromRow(row)[0];
	    return firstLine ? [firstLine] : [];
	  };

  const normalizeItem = (row) => {
    const itemType = row.dataset.itemType || "service";
    const base = {
      description: valueOf('textarea[name$="[description]"]', row),
      pay_now: valueOf("[data-pay-now]", row) || "0",
    };

    if (itemType === "product") {
      return {
        ...base,
	        entry_mode: "product",
	        part_source: "store_stock",
	        selected_label: valueOf("[data-product-search]", row),
	        product_lines: firstProductLineFromRow(row),
	      };
	    }

    if (itemType === "service_store_stock") {
      return {
        ...base,
        entry_mode: "service",
        part_source: "store_stock",
        service: {
          name: valueOf('input[name$="[service][name]"]', row),
          notes: valueOf('textarea[name$="[service][notes]"]', row),
          price_rupiah: numberText(valueOf('input[name$="[service][price_rupiah]"]', row)),
	        },
	        selected_label: valueOf("[data-product-search]", row),
	        product_lines: productLinesFromRow(row),
	      };
	    }

    if (itemType === "service_external") {
      return {
        ...base,
        entry_mode: "service",
        part_source: "external_purchase",
        service: {
          name: valueOf('input[name$="[service][name]"]', row),
          notes: valueOf('textarea[name$="[service][notes]"]', row),
          price_rupiah: numberText(valueOf('input[name$="[service][price_rupiah]"]', row)),
        },
	        external_purchase_lines: [
	          {
	            label: valueOf('input[name$="[external_purchase_lines][0][label]"]', row),
	            total_rupiah: numberText(
	              valueOf('input[name$="[external_purchase_lines][0][total_rupiah]"]', row),
	            ),
	          },
	        ],
	      };
    }

    return {
      ...base,
      entry_mode: "service",
      part_source: "none",
      service: {
        name: valueOf('input[name$="[service][name]"]', row),
        notes: valueOf('textarea[name$="[service][notes]"]', row),
        price_rupiah: numberText(valueOf('input[name$="[service][price_rupiah]"]', row)),
      },
    };
  };

  const serializeDraft = () => {
    const config = parseConfig();

    return {
      workspace_mode: config.workspaceMode || "create",
      note_id: config.noteId || null,
      note: {
        customer_name: valueOf("#note_customer_name"),
        customer_phone: valueOf("#note_customer_phone"),
        transaction_date: valueOf("#note_transaction_date"),
        operational_note: valueOf("#note_operational_note"),
      },
      items: Array.from(document.querySelectorAll("[data-line-item]")).map((row) =>
        normalizeItem(row),
      ),
      inline_payment: {
        decision: valueOf("#inline_payment_decision_hidden") || "skip",
        payment_method: valueOf("#inline_payment_method_hidden"),
        paid_at: valueOf("#inline_payment_paid_at_hidden"),
        amount_paid_rupiah: numberText(valueOf("#inline_payment_amount_paid_rupiah")),
        amount_received_rupiah: numberText(
          valueOf("#inline_payment_amount_received_rupiah"),
        ),
      },
    };
  };

  const saveDraftToServer = async (keepalive = false) => {
    if (isSubmitting) {
      return;
    }

    const config = parseConfig();
    const endpoint = String(config.draftSaveEndpoint || "").trim();
    if (!endpoint) {
      return;
    }

    try {
      await fetch(endpoint, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": String(config.csrfToken || ""),
        },
        credentials: "same-origin",
        keepalive,
        body: JSON.stringify(serializeDraft()),
      });
    } catch (_error) {
      // noop
    }
  };

  const initAutosave = async () => {
    if (NS.workspaceConfigReady instanceof Promise) {
      await NS.workspaceConfigReady;
    }

    const form = document.getElementById("cashier-note-workspace-form");
    if (!form) return;

    let timer = null;
    let hasInteracted = false;

    const queueSave = () => {
      if (isSubmitting) {
        return;
      }

      hasInteracted = true;
      window.clearTimeout(timer);
      timer = window.setTimeout(() => {
        void saveDraftToServer(false);
      }, 1200);
    };

    form.addEventListener("submit", () => {
      isSubmitting = true;
      window.clearTimeout(timer);
    });

    form.addEventListener("input", queueSave);
    form.addEventListener("change", queueSave);

    document.addEventListener("click", (event) => {
      if (
        event.target.closest("[data-add-item-type]") ||
        event.target.closest("[data-remove-line]") ||
        event.target.closest("[data-add-product-line]") ||
        event.target.closest("[data-remove-product-line]") ||
        event.target.closest("[data-payment-choice]") ||
        event.target.closest("#workspace-payment-open-cash") ||
        event.target.closest("#workspace-payment-back-cash")
      ) {
        window.setTimeout(queueSave, 0);
      }
    });

    window.addEventListener("beforeunload", () => {
      if (!hasInteracted || isSubmitting) {
        return;
      }

      void saveDraftToServer(true);
    });
  };

  NS.workspaceConfigReady = restoreFromServer();

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      void initAutosave();
    });
  } else {
    void initAutosave();
  }
})();
