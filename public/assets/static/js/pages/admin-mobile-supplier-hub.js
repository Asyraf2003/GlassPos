(() => {
  const root = document.querySelector('[data-mobile-supplier-hub]');
  if (!root) return;

  const sections = Object.fromEntries(
    Array.from(root.querySelectorAll('[data-mobile-hub-section]'))
      .map((section) => [section.dataset.mobileHubSection, section])
  );

  const showSection = (name) => {
    Object.entries(sections).forEach(([key, section]) => {
      section.classList.toggle('d-none', key !== name);
    });

    root.querySelectorAll('[data-mobile-hub-action]').forEach((button) => {
      button.setAttribute('aria-pressed', button.dataset.mobileHubAction === name ? 'true' : 'false');
    });
  };

  root.addEventListener('click', (event) => {
    const action = event.target.closest('[data-mobile-hub-action]');
    if (action) {
      showSection(action.dataset.mobileHubAction || '');
      return;
    }

    const invoice = event.target.closest('[data-mobile-pay-invoice]');
    if (!invoice || !window.bootstrap?.Modal) return;

    const modal = document.getElementById('mobile-supplier-payment-modal');
    const form = document.getElementById('mobile-supplier-payment-form');
    if (!modal || !form) return;

    form.dataset.scopeId = invoice.dataset.invoiceId || '';
    delete form.dataset.uploadIdempotencyKey;

    const input = form.querySelector('input[type="file"]');
    if (input) input.value = '';

    modal.querySelector('[data-mobile-payment-supplier]').textContent = invoice.dataset.supplierName || '-';
    modal.querySelector('[data-mobile-payment-invoice]').textContent = invoice.dataset.invoiceNo || '-';
    modal.querySelector('[data-mobile-payment-outstanding]').textContent = invoice.dataset.outstandingLabel || '-';

    window.bootstrap.Modal.getOrCreateInstance(modal).show();
  });

  const modal = document.getElementById('mobile-supplier-payment-modal');
  modal?.addEventListener('hidden.bs.modal', () => {
    const form = document.getElementById('mobile-supplier-payment-form');
    if (!form) return;
    form.dataset.scopeId = '';
    delete form.dataset.uploadIdempotencyKey;
    const input = form.querySelector('input[type="file"]');
    if (input) input.value = '';
  });

  const initial = root.dataset.initialTab || '';
  if (sections[initial]) showSection(initial);
})();
