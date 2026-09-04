(() => {
  const NS = (window.CashierNoteWorkspace = window.CashierNoteWorkspace || {});
  const root = document.querySelector(".cashier-note-workspace");
  const partialPanel = document.getElementById("workspace-simple-partial-panel");
  const partialInput = document.getElementById("workspace-simple-partial-amount");

  if (!(root instanceof HTMLElement)) return;

  const digits = (value) =>
    Number.parseInt(String(value || "").replace(/\D+/g, "") || "0", 10);
  const format = (value) => Number(value || 0).toLocaleString("id-ID");

  const setMode = (mode) => {
    if (!["simple", "detail"].includes(mode)) return;

    root.dataset.presentationMode = mode;
    root.querySelectorAll("[data-mode-choice]").forEach((button) => {
      const active = button.dataset.modeChoice === mode;
      button.setAttribute("aria-pressed", active ? "true" : "false");
    });

    if (mode === "detail") {
      partialPanel?.classList.add("d-none");
    }
  };

  const openPartial = () => {
    partialPanel?.classList.remove("d-none");
    window.requestAnimationFrame(() => partialInput?.focus());
  };

  const closePartial = () => {
    partialPanel?.classList.add("d-none");
    if (partialInput instanceof HTMLInputElement) partialInput.value = "";
  };

  root.addEventListener("click", (event) => {
    if (!(event.target instanceof Element)) return;

    const modeButton = event.target.closest("[data-mode-choice]");
    if (modeButton) {
      setMode(modeButton.dataset.modeChoice || "simple");
      return;
    }

    const action = event.target.closest("[data-simple-payment-action]")
      ?.dataset.simplePaymentAction;

    if (action === "partial") {
      openPartial();
      return;
    }

    if (action === "skip") {
      NS.submitSimplePayment?.("skip");
      return;
    }

    if (action === "full") {
      NS.submitSimplePayment?.("full");
      return;
    }

    if (event.target.closest("#workspace-simple-partial-cancel")) {
      closePartial();
      return;
    }

    if (event.target.closest("#workspace-simple-partial-submit")) {
      const amount = digits(partialInput?.value);
      NS.submitSimplePayment?.("partial", amount);
    }
  });

  partialInput?.addEventListener("input", () => {
    const amount = digits(partialInput.value);
    partialInput.value = amount > 0 ? format(amount) : "";
  });

  partialInput?.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
      event.preventDefault();
      const amount = digits(partialInput.value);
      NS.submitSimplePayment?.("partial", amount);
    }

    if (event.key === "Escape") {
      closePartial();
    }
  });

  document
    .getElementById("cashier-note-workspace-form")
    ?.addEventListener("submit", () => {
      root.querySelectorAll("[data-simple-payment-action], #workspace-simple-partial-submit")
        .forEach((button) => {
          button.disabled = true;
        });
    });

  setMode(root.dataset.presentationMode || "simple");
})();
