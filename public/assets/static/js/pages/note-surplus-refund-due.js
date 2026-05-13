(() => {
  const forms = Array.from(document.querySelectorAll("[data-refund-due-form]"));
  if (forms.length === 0) return;

  const digits = (value) =>
    Number.parseInt(String(value || "").replace(/\D+/g, "") || "0", 10);

  const clampAmount = (input, maxRupiah) => {
    if (!input) return 0;

    const typed = digits(input.value);
    const bounded = maxRupiah > 0 && typed > maxRupiah ? maxRupiah : typed;

    if (typed !== bounded) {
      input.value = bounded > 0 ? String(bounded) : "";
    }

    return bounded;
  };

  forms.forEach((form) => {
    const amountInput = form.querySelector("[data-refund-due-amount]");
    const submitButton = form.querySelector("[data-refund-due-submit]");
    const maxRupiah = digits(form.dataset.refundDueMaxRupiah || amountInput?.max || "");

    amountInput?.addEventListener("input", () => {
      clampAmount(amountInput, maxRupiah);
    });

    form.addEventListener("submit", (event) => {
      if (form.dataset.submitted === "1") {
        event.preventDefault();
        return;
      }

      clampAmount(amountInput, maxRupiah);

      if (!form.checkValidity()) {
        return;
      }

      form.dataset.submitted = "1";

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = submitButton.dataset.loadingText || submitButton.textContent;
      }
    });
  });
})();
