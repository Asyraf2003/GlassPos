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

    const invoice = event.target.closest('[data-mobile-pay-invoice], [data-mobile-attach-payment]');
    if (!invoice || !window.bootstrap?.Modal) return;

    const modal = document.getElementById('mobile-supplier-payment-modal');
    const form = document.getElementById('mobile-supplier-payment-form');
    if (!modal || !form || form.dataset.uploading === '1') return;

    event.preventDefault();
    const existingPayment = invoice.hasAttribute('data-mobile-attach-payment');
    form.dataset.scopeType = existingPayment ? 'supplier_payment' : 'supplier_invoice';
    form.dataset.scopeId = existingPayment ? invoice.dataset.paymentId : invoice.dataset.invoiceId;
    modal.querySelector('.modal-title').textContent = existingPayment ? 'Tambah Bukti Pembayaran' : 'Bayar Supplier';
    modal.querySelector('[data-mobile-payment-amount-label]').textContent = existingPayment ? 'Jumlah yang sudah dibayar' : 'Sisa yang akan dilunasi';
    form.querySelector('[data-direct-upload-submit]').textContent = existingPayment ? 'Simpan Bukti' : 'Kirim Bukti & Tandai Lunas';
    delete form.dataset.uploadIdempotencyKey;

    const input = form.querySelector('input[type="file"]');
    if (input) input.value = '';

    modal.querySelector('[data-mobile-payment-supplier]').textContent = invoice.dataset.supplierName || '-';
    modal.querySelector('[data-mobile-payment-bank]').textContent = invoice.dataset.bankLabel || '';
    modal.querySelector('[data-mobile-payment-outstanding]').textContent = invoice.dataset.outstandingLabel || '-';

    window.bootstrap.Modal.getOrCreateInstance(modal).show();
  });

  const modal = document.getElementById('mobile-supplier-payment-modal');
  modal?.addEventListener('hide.bs.modal', (event) => {
    if (document.getElementById('mobile-supplier-payment-form')?.dataset.uploading === '1') event.preventDefault();
  });
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
