(() => {
  const NS = (window.CashierNoteWorkspace = window.CashierNoteWorkspace || {});
  const configEl = document.getElementById("cashier-note-workspace-config");
  if (!configEl) return;

  const parseConfig = () => {
    try {
      return JSON.parse(configEl.textContent || "{}");
    } catch (_error) {
      return {};
    }
  };

  const start = async () => {
    if (NS.workspaceConfigReady instanceof Promise) {
      await NS.workspaceConfigReady;
    }

    NS.config = parseConfig();

    const form = document.getElementById("cashier-note-workspace-form");
    const customerName = document.getElementById("note_customer_name");
    const customerPhone = document.getElementById("note_customer_phone");
    const transactionDate = document.getElementById("note_transaction_date");
    const operationalNote = document.getElementById("note_operational_note");
    const paymentModal = document.getElementById("workspace-payment-modal");

    const hydrateNoteFields = () => {
      const note =
        typeof NS.config.oldNote === "object" && NS.config.oldNote !== null
          ? NS.config.oldNote
          : {};

      if (customerName && typeof note.customer_name === "string") {
        customerName.value = note.customer_name;
      }

      if (customerPhone && typeof note.customer_phone === "string") {
        customerPhone.value = note.customer_phone;
      }

      if (
        transactionDate &&
        typeof note.transaction_date === "string" &&
        note.transaction_date !== ""
      ) {
        transactionDate.value = note.transaction_date;
      }

      if (operationalNote && typeof note.operational_note === "string") {
        operationalNote.value = note.operational_note;
      }

      if (customerName && !customerName.value.trim()) {
        customerName.value = NS.config.defaultCustomerName || "Pelanggan no 1";
      }
    };

    const hydratePaymentFields = () => {
      const payment =
        typeof NS.config.oldInlinePayment === "object" &&
        NS.config.oldInlinePayment !== null
          ? NS.config.oldInlinePayment
          : {};

      const setValue = (id, value) => {
        const el = document.getElementById(id);
        if (!el || value === undefined || value === null) return;
        el.value = String(value);
      };

      setValue("inline_payment_decision_hidden", payment.decision || "skip");
      setValue("inline_payment_method_hidden", payment.payment_method || "");
      setValue(
        "inline_payment_paid_at_hidden",
        payment.paid_at || transactionDate?.value || ""
      );
      setValue(
        "inline_payment_paid_at_display",
        payment.paid_at || transactionDate?.value || ""
      );
      setValue(
        "inline_payment_amount_paid_rupiah",
        payment.amount_paid_rupiah || ""
      );
      setValue(
        "inline_payment_amount_received_rupiah",
        payment.amount_received_rupiah || ""
      );
    };

    document.addEventListener("click", (event) => {
      const removeButton = event.target.closest("[data-remove-line]");
      if (removeButton) {
        NS.removeRow?.(removeButton.closest("[data-line-item]"));
      }

      const typeButton = event.target.closest("[data-add-item-type]");
      if (typeButton) {
        NS.addRow?.(typeButton.dataset.addItemType || "service");
      }
    });

    form?.addEventListener("input", (event) => {
      if (
        event.target.closest("[data-line-item]") ||
        event.target.id === "note_transaction_date"
      ) {
        NS.updateSummary?.();
      }
    });

    form?.addEventListener("change", (event) => {
      if (
        event.target.closest("[data-line-item]") ||
        event.target.id === "note_transaction_date"
      ) {
        NS.updateSummary?.();
      }
    });

    hydrateNoteFields();
    hydratePaymentFields();

    (NS.config.oldItems || []).forEach((item) => NS.addRow?.(NS.detectType(item), item));

    window.AdminMoneyInput?.bindBySelector?.(document);

    if (paymentModal) {
      paymentModal.addEventListener("shown.bs.modal", () => {
        window.AdminMoneyInput?.bindBySelector?.(paymentModal);
        NS.updateSummary?.();
        NS.handlePaymentModalShown?.();
      });
    }

    NS.updateSummary?.();
  };

  void start();
})();
