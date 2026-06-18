(() => {
  const NS = (window.CashierNoteWorkspace = window.CashierNoteWorkspace || {});
  const digits = (value) =>
    Number.parseInt(String(value || "").replace(/\D+/g, "") || "0", 10);
  const format = (value) => Number(value || 0).toLocaleString("id-ID");
  const byId = (id) => document.getElementById(id);

  const setText = (id, value) => {
    const el = byId(id);
    if (el) el.textContent = format(value);
  };

  const setHtml = (id, value) => {
    const el = byId(id);
    if (el) el.innerHTML = value;
  };

  const toggle = (id, show) => {
    const el = byId(id);
    if (el) el.classList.toggle("d-none", !show);
  };

  const toggleFlex = (id, show) => {
    const el = byId(id);
    if (!el) return;
    el.classList.toggle("d-none", !show);
    el.classList.toggle("d-flex", show);
  };

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

  const escapeHtml = (value) =>
    String(value || "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  const formatActiveMoneyInput = (input) => {
    if (!(input instanceof HTMLInputElement)) {
      return 0;
    }

    const numeric = digits(input.value || "");
    input.value = numeric > 0 ? format(numeric) : "";
    return numeric;
  };

  NS.paymentState = NS.paymentState || { mode: "", cashStep: false };

  const updateHidden = (id, value) => {
    const el = byId(id);
    if (el) el.value = String(value ?? "");
  };

  const hiddenValue = (id) => byId(id)?.value || "";
  const partialAmountInput = () => byId("inline_payment_amount_paid_display");
  const receivedAmountInput = () => byId("inline_payment_amount_received_display");
  const modalEl = () => byId("workspace-payment-modal");
  const dialogEl = () => byId("workspace-payment-modal-dialog");
  const formEl = () => byId("cashier-note-workspace-form");
  const choiceButtons = () =>
    Array.from(document.querySelectorAll("[data-payment-choice]"));

  const hasBootstrapModal = () =>
    typeof bootstrap !== "undefined" && !!bootstrap.Modal;

  const visibleModalCount = () => document.querySelectorAll(".modal.show").length;

  const cleanupResidualModalArtifacts = () => {
    if (visibleModalCount() > 0) {
      return;
    }

    document.querySelectorAll(".modal-backdrop").forEach((el) => el.remove());
    document.body.classList.remove("modal-open");
    document.body.style.removeProperty("overflow");
    document.body.style.removeProperty("padding-right");
  };

  const deferCleanupResidualModalArtifacts = () => {
    window.setTimeout(cleanupResidualModalArtifacts, 0);
  };

  const getPaymentModalInstance = () => {
    const el = modalEl();
    if (!el || !hasBootstrapModal()) return null;

    return bootstrap.Modal.getOrCreateInstance(el);
  };

  const currentRows = () =>
    typeof NS.currentRows === "function" ? NS.currentRows() : [];

  const backendSettlementContext = () => {
    const modal = modalEl();

    if (!modal || modal.dataset.backendPaymentBasis !== "backend_outstanding_settlement") {
      return null;
    }

    return {
      payable: digits(modal.dataset.backendPayableRupiah || ""),
      netPaid: digits(modal.dataset.backendNetPaidRupiah || ""),
      grossTotal: digits(modal.dataset.backendGrossTotalRupiah || ""),
    };
  };

  const effectivePaymentTotal = (total) => {
    const context = backendSettlementContext();

    if (!context) {
      return total;
    }

    return Math.max(total - context.netPaid, 0);
  };

  const grandTotal = () =>
    currentRows().reduce((sum, item) => sum + Number(item?.total || 0), 0);

  const productDefaultAmount = (total) => {
    const productTotal = currentRows().reduce(
      (sum, item) => sum + Number(item?.productTotal || 0),
      0
    );

    return productTotal > 0 && productTotal < total ? productTotal : 0;
  };

  const partialAmount = (total) => {
    const input = partialAmountInput();
    const inputValue = digits(
      input?.value || hiddenValue("inline_payment_amount_paid_rupiah")
    );

    if (inputValue > 0 && input?.dataset.partialDefault !== "product") {
      return Math.min(inputValue, total);
    }

    const defaultAmount = productDefaultAmount(total);
    return defaultAmount > 0 ? defaultAmount : Math.min(inputValue, total);
  };

  const payableAmount = (total) => {
    const effectiveTotal = effectivePaymentTotal(total);

    if (NS.paymentState.mode === "full") {
      return effectiveTotal;
    }

    if (NS.paymentState.mode === "partial") {
      return partialAmount(effectiveTotal);
    }

    return 0;
  };

  const clearWorkspaceValidationError = () => {
    const alert = byId("workspace-client-validation-error");
    if (alert) {
      alert.remove();
    }

    document
      .querySelectorAll("[data-workspace-client-invalid='1']")
      .forEach((element) => {
        element.classList.remove("is-invalid");
        delete element.dataset.workspaceClientInvalid;
      });
  };

  const showWorkspaceValidationError = (message, target = null) => {
    const form = formEl();

    if (!form) {
      window.alert(message);
      return;
    }

    let alert = byId("workspace-client-validation-error");

    if (!alert) {
      alert = document.createElement("div");
      alert.id = "workspace-client-validation-error";
      alert.className = "alert alert-danger";
      alert.setAttribute("role", "alert");
      form.prepend(alert);
    }

    alert.textContent = message;
    alert.scrollIntoView({ behavior: "smooth", block: "center" });

    if (target instanceof HTMLElement) {
      target.classList.add("is-invalid");
      target.dataset.workspaceClientInvalid = "1";
      focusElement(target);
    }
  };

  const serviceStoreStockAutosplitIssue = (row) => {
    const pricingMode = row.querySelector("[data-pricing-mode]")?.value || "";
    const packageTotalInput = row.querySelector('input[name$="[package_total_rupiah]"]');
    const packageTotal = digits(packageTotalInput?.value || "");
    const requiresServiceProductTemplate =
      row.querySelector("[data-requires-service-product-template]")?.value === "1";

    if (pricingMode !== "package_auto_split") {
      return null;
    }

    if (packageTotal <= 0) {
      return {
        message: "Total paket wajib diisi sebelum proses nota.",
        target: row.querySelector("[data-package-total-input]") || packageTotalInput,
      };
    }

    const scopes = Array.from(row.querySelectorAll("[data-product-line]"));
    const productScopes = scopes.length ? scopes : [row];

    if (requiresServiceProductTemplate && productScopes.length !== 1) {
      return {
        message: "Paket servis + produk hanya boleh memakai 1 produk template aktif.",
        target: row.querySelector("[data-product-search]") || row.querySelector("[data-package-total-input]") || packageTotalInput,
      };
    }

    let sparepartTotal = 0;

    for (const scope of productScopes) {
      const productSearch = scope.querySelector("[data-product-search]");
      const productId = scope.querySelector("[data-product-id]")?.value?.trim() || "";
      const qtyInput = scope.querySelector("[data-qty-input]");
      const unitPriceInput = scope.querySelector('input[name$="[unit_price_rupiah]"]');
      const qty = digits(qtyInput?.value || "");
      const unitPrice = digits(unitPriceInput?.value || "");

      if (!productId) {
        return {
          message: "Sparepart toko wajib dipilih dari hasil pencarian, bukan diketik manual.",
          target: productSearch,
        };
      }

      if (qty <= 0) {
        return {
          message: "Qty sparepart toko wajib lebih dari 0.",
          target: qtyInput,
        };
      }

      if (unitPrice <= 0) {
        return {
          message: "Harga sparepart toko belum valid. Pilih ulang produk dari hasil pencarian.",
          target: productSearch,
        };
      }

      sparepartTotal += qty * unitPrice;
    }

    if (packageTotal < sparepartTotal) {
      return {
        message: "Total paket tidak boleh lebih kecil dari total harga sparepart.",
        target: row.querySelector("[data-package-total-input]") || packageTotalInput,
      };
    }

    if (
      requiresServiceProductTemplate &&
      row.dataset.serviceProductTemplateApplied !== "1"
    ) {
      return {
        message: "Paket servis + produk wajib memakai template aktif.",
        target: row.querySelector("[data-product-search]") || row.querySelector("[data-package-total-input]") || packageTotalInput,
      };
    }

    const minimumTemplateServicePrice = digits(
      row.dataset.serviceTemplateDefaultPriceRupiah || ""
    );

    if (
      minimumTemplateServicePrice > 0 &&
      packageTotal < sparepartTotal + minimumTemplateServicePrice
    ) {
      return {
        message: `Total paket tidak boleh membuat harga jasa di bawah default template (${format(minimumTemplateServicePrice)}).`,
        target: row.querySelector("[data-package-total-input]") || packageTotalInput,
      };
    }

    return null;
  };

  const firstWorkspaceValidationIssue = () => {
    for (const row of document.querySelectorAll("[data-line-item]")) {
      if (!(row instanceof HTMLElement)) {
        continue;
      }

      if ((row.dataset.itemType || "") !== "service_store_stock") {
        continue;
      }

      const issue = serviceStoreStockAutosplitIssue(row);
      if (issue) {
        return issue;
      }
    }

    return null;
  };

  const ensureWorkspaceReadyForPayment = () => {
    const issue = firstWorkspaceValidationIssue();

    if (!issue) {
      clearWorkspaceValidationError();
      return true;
    }

    showWorkspaceValidationError(issue.message, issue.target);
    return false;
  };

  const lineSummaryLabel = (item) => {
    const row = item?.row;
    if (!(row instanceof HTMLElement)) {
      return item?.title || "Rincian";
    }

    const type = row.dataset.itemType || "";
    const serviceName =
      row.querySelector('input[name$="[service][name]"]')?.value?.trim() || "";
    const productName =
      row.querySelector("[data-product-search]")?.value?.trim() || "";
    const externalLabel =
      row
        .querySelector('input[name$="[external_purchase_lines][0][label]"]')
        ?.value?.trim() || "";
    const description =
      row.querySelector('textarea[name$="[description]"]')?.value?.trim() || "";

    if (type === "product") {
      return productName || description || item.title || "Produk";
    }

    if (type === "service_store_stock") {
      return [serviceName, productName].filter(Boolean).join(" + ") || item.title || "Rincian";
    }

    if (type === "service_external") {
      return [serviceName, externalLabel].filter(Boolean).join(" + ") || item.title || "Rincian";
    }

    return serviceName || description || item.title || "Servis";
  };

  const renderLineSummary = () => {
    const rows = currentRows();

    if (!rows.length) {
      setHtml(
        "workspace-payment-line-summary",
        '<div class="p-3 text-muted small">Belum ada rincian nota.</div>'
      );
      return;
    }

    const html = rows
      .map(
        (item) => `
          <div class="p-3 border-bottom">
              <div class="d-flex justify-content-between align-items-start gap-3">
                  <div class="pe-2">
                      <div class="fw-semibold">${escapeHtml(lineSummaryLabel(item))}</div>
                      <div class="small text-muted">${escapeHtml(item.title || "Rincian")}</div>
                  </div>
                  <strong>${format(item.total)}</strong>
              </div>
          </div>
        `
      )
      .join("");

    setHtml("workspace-payment-line-summary", html);
  };

  const syncChoiceButtons = () => {
    choiceButtons().forEach((button) => {
      const active = button.dataset.paymentChoice === NS.paymentState.mode;

      button.classList.toggle("btn-primary", active);
      button.classList.toggle("text-white", active);
      button.classList.toggle("btn-outline-secondary", !active);
      button.classList.toggle("shadow-sm", active);
    });
  };

  const clearReceivedAmount = () => {
    updateHidden("inline_payment_amount_received_rupiah", "");
    const input = receivedAmountInput();
    if (input) {
      input.value = "";
    }
  };

  const clearPaymentMethod = () => {
    updateHidden("inline_payment_method_hidden", "");
  };

  const syncPartialAmount = (total) => {
    const amount = partialAmount(total);
    const defaultAmount = productDefaultAmount(total);
    updateHidden("inline_payment_amount_paid_rupiah", amount > 0 ? amount : "");

    const input = partialAmountInput();
    if (input && document.activeElement !== input) {
      input.dataset.partialDefault =
        amount > 0 && amount === defaultAmount ? "product" : "";
      input.value = amount > 0 ? format(amount) : "";
    }
  };

  const syncReceivedAmount = () => {
    const hidden = digits(hiddenValue("inline_payment_amount_received_rupiah"));
    const input = receivedAmountInput();

    if (input && document.activeElement !== input) {
      input.value = hidden > 0 ? format(hidden) : "";
    }
  };

  const applyMode = (mode) => {
    NS.paymentState.mode = mode;
    NS.paymentState.cashStep = false;

    if (mode === "skip") {
      updateHidden("inline_payment_decision_hidden", "skip");
      updateHidden("inline_payment_amount_paid_rupiah", "");
      clearPaymentMethod();
      clearReceivedAmount();
    }

    if (mode === "full") {
      updateHidden("inline_payment_decision_hidden", "pay_full");
      updateHidden("inline_payment_amount_paid_rupiah", "");
      clearPaymentMethod();
      clearReceivedAmount();
    }

    if (mode === "partial") {
      updateHidden("inline_payment_decision_hidden", "pay_partial");
      clearPaymentMethod();
      clearReceivedAmount();
    }

    NS.refreshPaymentUi();
  };

  const openCashStep = () => {
    updateHidden("inline_payment_method_hidden", "cash");
    NS.paymentState.cashStep = true;
    NS.refreshPaymentUi();
    focusElement(receivedAmountInput());
  };

  const closeCashStep = () => {
    NS.paymentState.cashStep = false;
    clearPaymentMethod();
    clearReceivedAmount();
    NS.refreshPaymentUi();

    if (NS.paymentState.mode === "partial") {
      focusElement(partialAmountInput());
      return;
    }

    focusElement(byId("workspace-payment-submit-transfer"), false);
  };

  const hydrateStateFromHidden = () => {
    const decision = hiddenValue("inline_payment_decision_hidden");
    const method = hiddenValue("inline_payment_method_hidden");

    NS.paymentState.mode =
      decision === "pay_partial"
        ? "partial"
        : decision === "pay_full"
          ? "full"
          : decision === "skip"
            ? "skip"
            : "";

    NS.paymentState.cashStep = method === "cash";
  };

  const updateHeaderText = () => {
    const title = byId("workspace-payment-modal-title");
    const subtitle = byId("workspace-payment-modal-subtitle");

    if (!title || !subtitle) return;

    if (NS.paymentState.cashStep) {
      title.textContent = "Pembayaran Cash";
      subtitle.textContent =
        "Masukkan uang pelanggan, cek kembalian, lalu simpan cash.";
      return;
    }

    title.textContent = "Proses Nota";
    subtitle.textContent =
      "Cek ringkasan transaksi, lalu lanjutkan dengan metode pembayaran.";
  };

  const updateModeText = () => {
    const standardModeText = byId("workspace-payment-mode-text");
    const cashModeText = byId("workspace-cash-mode-text");

    const standardLabel =
      NS.paymentState.mode === "full"
        ? "Bayar Penuh"
        : NS.paymentState.mode === "partial"
          ? "Bayar Sebagian"
          : NS.paymentState.mode === "skip"
            ? "Simpan Tanpa Pembayaran"
            : "Belum dipilih";

    if (standardModeText) {
      standardModeText.textContent = standardLabel;
    }

    if (cashModeText) {
      cashModeText.textContent =
        NS.paymentState.mode === "partial" ? "Bayar Sebagian" : "Bayar Penuh";
    }
  };

  const syncDialogWidth = () => {
    const dialog = dialogEl();
    if (!dialog) return;

    dialog.classList.toggle("modal-xl", !NS.paymentState.cashStep);

    if (NS.paymentState.cashStep) {
      dialog.style.maxWidth = "560px";
      dialog.style.width = "calc(100% - 2rem)";
    } else {
      dialog.style.maxWidth = "";
      dialog.style.width = "";
    }
  };

  NS.refreshPaymentUi = (total = grandTotal()) => {
    const noteDate = byId("note_transaction_date")?.value || "";
    updateHidden("inline_payment_paid_at_hidden", noteDate);

    syncDialogWidth();
    renderLineSummary();
    syncChoiceButtons();
    updateHeaderText();
    updateModeText();

    if (NS.paymentState.mode === "partial") {
      syncPartialAmount(total);
    } else {
      updateHidden("inline_payment_amount_paid_rupiah", "");
      const input = partialAmountInput();
      if (input && document.activeElement !== input) {
        input.value = "";
      }
    }

    if (NS.paymentState.cashStep) {
      syncReceivedAmount();
    }

    const payable = payableAmount(total);
    const received = digits(hiddenValue("inline_payment_amount_received_rupiah"));

    setText("workspace-modal-total-text", total);
    setText("workspace-cash-payable-text", payable);
    setText("workspace-cash-change-text", Math.max(received - payable, 0));

    toggle("workspace-payment-standard-view", !NS.paymentState.cashStep);
    toggle("workspace-payment-cash-view", NS.paymentState.cashStep);
    toggle("workspace-payment-panel-partial", NS.paymentState.mode === "partial" && !NS.paymentState.cashStep);

    toggleFlex("workspace-payment-footer-main", !NS.paymentState.cashStep);
    toggleFlex("workspace-payment-footer-cash", NS.paymentState.cashStep);

    const skipButton = byId("workspace-payment-submit-skip");
    const transferButton = byId("workspace-payment-submit-transfer");
    const cashButton = byId("workspace-payment-open-cash");
    const cashSubmitButton = byId("workspace-payment-submit-cash");

    const partialInvalid = NS.paymentState.mode === "partial" && payable <= 0;
    const baseInvalid = total <= 0;
    const transferInvalid =
      baseInvalid ||
      !["full", "partial"].includes(NS.paymentState.mode) ||
      partialInvalid;
    const skipInvalid = baseInvalid || NS.paymentState.mode !== "skip";
    const cashInvalid =
      baseInvalid ||
      !["full", "partial"].includes(NS.paymentState.mode) ||
      partialInvalid;
    const receivedInvalid =
      payable <= 0 || received < payable || hiddenValue("inline_payment_method_hidden") !== "cash";

    if (skipButton) {
      skipButton.classList.toggle("d-none", NS.paymentState.mode !== "skip");
      skipButton.disabled = skipInvalid;
    }

    if (transferButton) {
      transferButton.classList.toggle(
        "d-none",
        !["full", "partial"].includes(NS.paymentState.mode)
      );
      transferButton.disabled = transferInvalid;
    }

    if (cashButton) {
      cashButton.classList.toggle(
        "d-none",
        !["full", "partial"].includes(NS.paymentState.mode)
      );
      cashButton.disabled = cashInvalid;
    }

    if (cashSubmitButton) {
      cashSubmitButton.disabled = receivedInvalid;
    }
  };

  const bindPaymentModalLifecycle = () => {
    const el = modalEl();
    if (!el || el.dataset.paymentLifecycleBound === "1") return;

    el.dataset.paymentLifecycleBound = "1";

    el.addEventListener("shown.bs.modal", () => {
      window.AdminMoneyInput?.bindBySelector?.(el);
      NS.refreshPaymentUi();
    });

    el.addEventListener("hidden.bs.modal", () => {
      deferCleanupResidualModalArtifacts();
    });
  };

  const showPaymentModal = () => {
    cleanupResidualModalArtifacts();

    const instance = getPaymentModalInstance();
    if (!instance) return;

    instance.show();
  };

  const reopenModalIfNeeded = () => {
    const workspaceMode = String(NS.config?.workspaceMode || "create");
    if (workspaceMode === "edit") {
      return;
    }

    const decision = hiddenValue("inline_payment_decision_hidden");
    const method = hiddenValue("inline_payment_method_hidden");

    if (!["pay_full", "pay_partial"].includes(decision)) {
      return;
    }

    hydrateStateFromHidden();

    if (method !== "cash") {
      NS.paymentState.cashStep = false;
    }

    NS.refreshPaymentUi();
    showPaymentModal();
  };

  const openPaymentModal = () => {
    bindPaymentModalLifecycle();

    NS.paymentState.mode = "";
    NS.paymentState.cashStep = false;
    updateHidden("inline_payment_decision_hidden", "skip");
    updateHidden("inline_payment_amount_paid_rupiah", "");
    clearPaymentMethod();
    clearReceivedAmount();

    NS.refreshPaymentUi();
    showPaymentModal();
  };

  document.addEventListener("click", (event) => {
    if (event.target.closest("#workspace-open-payment-dialog")) {
      if (!ensureWorkspaceReadyForPayment()) {
        return;
      }

      openPaymentModal();
      return;
    }

    const choiceButton = event.target.closest("[data-payment-choice]");
    if (choiceButton) {
      applyMode(choiceButton.dataset.paymentChoice || "");
      return;
    }

    if (event.target.closest("#workspace-payment-submit-skip")) {
      applyMode("skip");
      return;
    }

    if (event.target.closest("#workspace-payment-submit-transfer")) {
      updateHidden("inline_payment_method_hidden", "transfer");
      return;
    }

    if (event.target.closest("#workspace-payment-open-cash")) {
      openCashStep();
      return;
    }

    if (event.target.closest("#workspace-payment-back-cash")) {
      closeCashStep();
      return;
    }

    if (event.target.closest("#workspace-payment-submit-cash")) {
      updateHidden("inline_payment_method_hidden", "cash");
    }
  });

  document.addEventListener("input", (event) => {
    if (event.target.closest?.("[data-line-item]")) {
      clearWorkspaceValidationError();
    }

    if (event.target.id === "inline_payment_amount_paid_display") {
      const input = partialAmountInput();
      if (input) input.dataset.partialDefault = "";
      const numeric = formatActiveMoneyInput(input);
      updateHidden("inline_payment_amount_paid_rupiah", numeric);
      NS.refreshPaymentUi();
      return;
    }

    if (event.target.id === "inline_payment_amount_received_display") {
      const numeric = formatActiveMoneyInput(receivedAmountInput());
      updateHidden("inline_payment_amount_received_rupiah", numeric);
      NS.refreshPaymentUi();
      return;
    }

    if (event.target.id === "note_transaction_date") {
      NS.refreshPaymentUi();
    }
  });

  const bindWorkspaceSubmitGuard = () => {
    const form = formEl();

    if (!form || form.dataset.workspaceSubmitGuardBound === "1") {
      return;
    }

    form.dataset.workspaceSubmitGuardBound = "1";

    form.addEventListener("submit", (event) => {
      if (ensureWorkspaceReadyForPayment()) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
    });
  };

  bindPaymentModalLifecycle();
  bindWorkspaceSubmitGuard();
  hydrateStateFromHidden();
  NS.refreshPaymentUi();

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", reopenModalIfNeeded, { once: true });
  } else {
    reopenModalIfNeeded();
  }
})();
