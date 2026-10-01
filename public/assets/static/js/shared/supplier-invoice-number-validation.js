(() => {
  window.bindSupplierInvoiceNumberValidation = ({ form, endpoint }) => {
    const input = form.querySelector('[name="nomor_faktur"]');
    const feedback = form.querySelector('[data-invoice-number-feedback]');
    const buttons = Array.from(form.querySelectorAll('[type="submit"]'));
    let timer, controller, generation = 0, state = 'idle', checkedValue = null;

    const render = (next, message = '') => {
      state = next;
      const blocked = next === 'pending' || next === 'duplicate';
      buttons.forEach((button) => {
        button.disabled = blocked;
        button.classList.toggle('disabled', blocked);
        button.setAttribute('aria-disabled', String(blocked));
      });
      input.classList.toggle('is-invalid', next === 'duplicate');
      input.setAttribute('aria-invalid', String(next === 'duplicate'));
      input.setAttribute('aria-busy', String(next === 'pending'));
      input.setCustomValidity(next === 'duplicate' ? message : '');
      feedback.textContent = message;
      feedback.hidden = message === '';
      feedback.classList.toggle('text-danger', next === 'duplicate');
      feedback.classList.toggle('text-muted', next !== 'duplicate');
    };

    const check = async (value, requestGeneration) => {
      const activeController = new AbortController();
      controller = activeController;
      const isCurrent = () => requestGeneration === generation && input.value === value;
      try {
        const params = new URLSearchParams({ nomor_faktur: value });
        const response = await fetch(`${endpoint}?${params}`, {
          headers: { Accept: 'application/json' }, cache: 'no-store',
          signal: activeController.signal,
        });
        const payload = await response.json();
        if (!isCurrent()) return;
        if (!response.ok || !payload.success || typeof payload.data?.duplicate !== 'boolean') {
          throw new Error('invoice-number-check');
        }
        render(payload.data.duplicate ? 'duplicate' : 'unique',
          payload.data.duplicate ? 'Nomor faktur ini sudah pernah digunakan' : '');
      } catch (_error) {
        if (!isCurrent() || activeController.signal.aborted) return;
        // Saving still goes through canonical request validation and DB uniqueness.
        render('unavailable', 'Nomor faktur akan diperiksa saat disimpan.');
      }
    };

    const refresh = (immediate = false) => {
      // Invalidate on input, before debounce starts, even when abort is ignored.
      generation += 1;
      clearTimeout(timer);
      controller?.abort();
      const value = input.value;
      checkedValue = value;
      if (value.trim() === '') {
        render('idle');
        return;
      }
      render('pending');
      const currentGeneration = generation;
      if (immediate) check(value, currentGeneration);
      else timer = setTimeout(() => check(value, currentGeneration), 250);
    };

    input.addEventListener('input', () => refresh());
    input.addEventListener('change', () => refresh(true));
    input.addEventListener('blur', () => {
      if (state === 'pending' || state === 'unavailable' || checkedValue !== input.value) refresh(true);
    });
    // Native reset applies its values after dispatching the reset event.
    form.addEventListener('reset', () => {
      generation += 1;
      clearTimeout(timer);
      controller?.abort();
      timer = setTimeout(() => refresh(), 0);
    });

    return {
      refresh,
      canSubmit() {
        if (checkedValue !== input.value) refresh();
        return state !== 'pending' && state !== 'duplicate';
      },
    };
  };
})();
