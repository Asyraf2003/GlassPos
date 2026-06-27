(() => {
  const modal = document.getElementById("note-payment-modal");
  const form = document.getElementById("note-payment-form");
  if (!modal || !form) return;

  const byId = (id) => document.getElementById(id);
  const digits = (value) =>
    Number.parseInt(String(value || "").replace(/\D+/g, "") || "0", 10);
  const format = (value) => Number(value || 0).toLocaleString("id-ID");
  const moneyInput = (id) => byId(id);
  const rowSources = () => Array.from(document.querySelectorAll("[data-payment-row-source]"));
  const allowPartial = () => modal.dataset.allowPartial === "1";

  const state = {
    mode: modal.dataset.defaultMode === "partial" ? "partial" : "full",
    cashStep: false,
  };

  const escapeHtml = (value) =>
    String(value || "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  const setText = (id, value) => {
    const el = byId(id);
    if (el) el.textContent = format(value);
  };

  const setValue = (id, value) => {
    const el = byId(id);
    if (el) el.value = String(value ?? "");
  };

  const selectedRows = () => {
    const rows = rowSources();

    if (state.mode === "full") {
      return rows;
    }

    const serviceRows = rows.filter((row) => row.dataset.isServiceComponent === "1");
    return serviceRows.length > 0 ? serviceRows : rows;
  };

  const selectedTotal = () =>
    selectedRows().reduce((sum, row) => sum + digits(row.dataset.outstandingRupiah), 0);

  const typedPartialAmount = () => {
    const input = moneyInput("detail_payment_amount_paid_display");
    return digits(input?.value || byId("detail_payment_amount_paid")?.value || "");
  };

  const payableAmount = () => {
    const total = selectedTotal();

    if (state.mode === "full") {
      return total;
    }

    const typed = typedPartialAmount();
    return typed > 0 ? Math.min(typed, total) : total;
  };

  const syncHiddenRows = () => {
    const container = byId("payment-selected-row-ids");
    if (!container) return;

    container.innerHTML = selectedRows()
      .map((row) => `<input type="hidden" name="selected_row_ids[]" value="${escapeHtml(row.dataset.rowId)}">`)
      .join("");
  };

  const syncPartialInput = () => {
    const input = moneyInput("detail_payment_amount_paid_display");
    if (!input || state.mode !== "partial") {
      setValue("detail_payment_amount_paid", "");
      return;
    }

    const total = selectedTotal();
    const typed = typedPartialAmount();
    const amount = typed > 0 ? Math.min(typed, total) : total;

    if (document.activeElement !== input) {
      input.value = amount > 0 ? format(amount) : "";
    }

    setValue("detail_payment_amount_paid", amount > 0 ? amount : "");
  };

  const renderRows = () => {
    const target = byId("detail-payment-line-summary");
    if (!target) return;

    const rows = selectedRows();
    if (rows.length === 0) {
      target.innerHTML = '<div class="p-3 text-muted small">Belum ada sisa tagihan.</div>';
      return;
    }

    target.innerHTML = rows
      .map((row) => `
        <div class="p-3 border rounded">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <div class="fw-semibold">${escapeHtml(row.dataset.label || "Tagihan")}</div>
              <div class="small text-muted">${escapeHtml(row.dataset.typeLabel || "-")}</div>
            </div>
            <strong>${format(digits(row.dataset.outstandingRupiah))}</strong>
          </div>
        </div>
      `)
      .join("");
  };

  const syncDialog = () => {
    const dialog = byId("note-payment-modal-dialog");
    if (!dialog) return;

    dialog.classList.toggle("modal-xl", !state.cashStep);
    dialog.style.maxWidth = state.cashStep ? "560px" : "";
    dialog.style.width = state.cashStep ? "calc(100% - 2rem)" : "";
  };

  const syncHeader = () => {
    const title = byId("detail-payment-title");
    const subtitle = byId("detail-payment-subtitle");

    if (state.cashStep) {
      if (title) title.textContent = "Pembayaran Cash";
      if (subtitle) {
        subtitle.textContent = "Masukkan uang pelanggan, cek kembalian, lalu simpan cash.";
      }
      return;
    }

    if (title) title.textContent = "Proses Nota";
    if (subtitle) {
      subtitle.textContent = "Pilih aksi pembayaran, cek nominal, lalu bayar transfer atau cash.";
    }
  };

  const syncModeText = () => {
    const label = state.mode === "partial" ? "Bayar Sebagian" : "Lunasi";

    const modeText = byId("detail-payment-mode-text");
    if (modeText) modeText.textContent = label;

    const cashText = byId("workspace-cash-mode-text");
    if (cashText) cashText.textContent = label;
  };

  const syncViews = () => {
    byId("detail-payment-standard-view")?.classList.toggle("d-none", state.cashStep);
    byId("detail-payment-cash-view")?.classList.toggle("d-none", !state.cashStep);
    byId("detail-payment-footer-main")?.classList.toggle("d-none", state.cashStep);
    byId("detail-payment-footer-cash")?.classList.toggle("d-none", !state.cashStep);

    const partialPanel = byId("detail-payment-partial-panel");
    if (partialPanel) partialPanel.classList.toggle("d-none", state.mode !== "partial");
  };

  const refresh = () => {
    if (!allowPartial() && state.mode === "partial") {
      state.mode = "full";
    }

    syncHiddenRows();
    syncPartialInput();
    renderRows();
    syncDialog();
    syncHeader();
    syncModeText();
    syncViews();

    const selected = selectedTotal();
    const payable = payableAmount();
    const received = digits(moneyInput("inline_payment_amount_received_display")?.value || "");

    if (state.cashStep) {
      setValue("detail_payment_amount_received", received > 0 ? received : "");
    }

    if (!state.cashStep) {
      setValue("detail_payment_amount_received", "");
    }

    setText("detail-payment-selected-total", selected);
    setText("detail-payment-payable-text", payable);
    setText("detail-payment-remaining-text", Math.max(selected - payable, 0));
    setText("workspace-cash-payable-text", payable);
    setText("workspace-cash-change-text", Math.max(received - payable, 0));

    const hasRows = selectedRows().length > 0;
    const transfer = byId("detail-payment-submit-transfer");
    const openCash = byId("detail-payment-open-cash");
    const submitCash = byId("detail-payment-submit-cash");

    if (transfer) transfer.disabled = !hasRows || payable <= 0;
    if (openCash) openCash.disabled = !hasRows || payable <= 0;
    if (submitCash) submitCash.disabled = !hasRows || payable <= 0 || received < payable;
  };

  const applyMode = (mode) => {
    state.mode = mode === "partial" && allowPartial() ? "partial" : "full";
    state.cashStep = false;
    setValue("detail_payment_method", "");
    setValue("detail_payment_amount_received", "");
    refresh();
  };

  const openCashStep = () => {
    state.cashStep = true;
    setValue("detail_payment_method", "cash");
    refresh();

    window.requestAnimationFrame(() => {
      const input = moneyInput("inline_payment_amount_received_display");
      input?.focus();
      input?.select?.();
    });
  };

  const closeCashStep = () => {
    state.cashStep = false;
    setValue("detail_payment_method", "");
    setValue("detail_payment_amount_received", "");
    const input = moneyInput("inline_payment_amount_received_display");
    if (input) input.value = "";
    refresh();
  };

  document.addEventListener("click", (event) => {
    const trigger = event.target.closest(".js-open-payment-intent");
    if (trigger) {
      applyMode(trigger.dataset.paymentIntent === "pay" ? "partial" : "full");
      return;
    }

    if (event.target.closest("#detail-payment-open-cash")) {
      event.preventDefault();
      openCashStep();
      return;
    }

    if (event.target.closest("#detail-payment-back-cash")) {
      event.preventDefault();
      closeCashStep();
      return;
    }

    if (event.target.closest("#detail-payment-submit-transfer")) {
      setValue("detail_payment_method", "tf");
      refresh();
      return;
    }

    if (event.target.closest("#detail-payment-submit-cash")) {
      setValue("detail_payment_method", "cash");
      setValue(
        "detail_payment_amount_received",
        digits(moneyInput("inline_payment_amount_received_display")?.value || "")
      );
      refresh();
    }
  });

  document.addEventListener("input", (event) => {
    if (event.target.id === "detail_payment_amount_paid_display") {
      const value = Math.min(digits(event.target.value || ""), selectedTotal());
      event.target.value = value > 0 ? format(value) : "";
      setValue("detail_payment_amount_paid", value > 0 ? value : "");
      refresh();
      return;
    }

    if (event.target.id === "inline_payment_amount_received_display") {
      const value = digits(event.target.value || "");
      event.target.value = value > 0 ? format(value) : "";
      setValue("detail_payment_amount_received", value > 0 ? value : "");
      refresh();
    }
  });

  const lockPaymentSubmit = () => {
    form.dataset.submitted = "1";

    [
      "detail-payment-submit-transfer",
      "detail-payment-open-cash",
      "detail-payment-submit-cash",
      "detail-payment-back-cash",
    ].forEach((id) => {
      const button = byId(id);
      if (button) button.disabled = true;
    });
  };

  form.addEventListener("submit", (event) => {
    if (form.dataset.submitted === "1") {
      event.preventDefault();
      return;
    }

    syncHiddenRows();

    if (state.cashStep) {
      setValue("detail_payment_method", "cash");
      setValue(
        "detail_payment_amount_received",
        digits(moneyInput("inline_payment_amount_received_display")?.value || "")
      );
      lockPaymentSubmit();
      return;
    }

    setValue("detail_payment_method", "tf");
    lockPaymentSubmit();
  });

  modal.addEventListener("shown.bs.modal", () => {
    refresh();

    if (state.mode === "partial") {
      const input = moneyInput("detail_payment_amount_paid_display");
      input?.focus();
      input?.select?.();
    }
  });

  refresh();
})();
