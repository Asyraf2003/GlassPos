(() => {
  const selector = 'input[data-ui-date="month"]';
  const defaultPlaceholder = 'Pilih bulan';

  const getLocale = () => window.flatpickr?.l10ns?.id ?? 'id';
  const currentTheme = () => document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';

  const resolvePlaceholder = (input) => {
    const fromData = String(input?.dataset?.uiDatePlaceholder || '').trim();
    if (fromData !== '') return fromData;

    const fromAttr = String(input?.getAttribute?.('placeholder') || '').trim();
    return fromAttr !== '' ? fromAttr : defaultPlaceholder;
  };

  const applyPlaceholder = (fp, placeholder) => {
    if (!fp || !placeholder) return;
    if (fp.altInput) fp.altInput.placeholder = placeholder;
    if (fp.input) fp.input.placeholder = placeholder;
  };

  const applyTheme = (input) => {
    const calendar = input?._flatpickr?.calendarContainer;
    if (!calendar) return;

    calendar.classList.remove('flatpickr-monthSelect-theme-light', 'flatpickr-monthSelect-theme-dark');
    calendar.classList.add(`flatpickr-monthSelect-theme-${currentTheme()}`);
  };

  const bind = (input) => {
    if (!input || input.dataset.uiDateBound === '1' || input._flatpickr) return;
    if (!window.flatpickr || !window.monthSelectPlugin) return;

    const placeholder = resolvePlaceholder(input);
    const instance = window.flatpickr(input, {
      locale: getLocale(),
      altInput: true,
      disableMobile: true,
      allowInput: false,
      static: window.matchMedia?.('(pointer: coarse)').matches ?? false,
      plugins: [window.monthSelectPlugin({
        shorthand: false,
        dateFormat: 'Y-m',
        altFormat: 'F Y',
        theme: currentTheme(),
      })],
      onReady: (_selectedDates, _dateStr, fp) => {
        applyPlaceholder(fp, placeholder);
        applyTheme(fp.input);
      },
      onValueUpdate: (_selectedDates, _dateStr, fp) => {
        applyPlaceholder(fp, placeholder);
      },
    });

    applyPlaceholder(instance, placeholder);
    input.dataset.uiDateBound = '1';
    applyTheme(input);
  };

  const bindBySelector = (root = document) => {
    const scope = root === document ? document : root;
    scope.querySelectorAll?.(selector)?.forEach(bind);
  };

  const refreshWithin = (root = document) => {
    const scope = root === document ? document : root;
    scope.querySelectorAll?.(selector)?.forEach((input) => {
      if (!input?._flatpickr) return;

      const value = String(input.value || '').trim();
      input._flatpickr.setDate(value === '' ? null : value, false, 'Y-m');
      applyPlaceholder(input._flatpickr, resolvePlaceholder(input));
      applyTheme(input);
    });
  };

  window.AdminMonthInput = { bindBySelector, refreshWithin };

  const initialize = () => bindBySelector(document);
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
  } else {
    initialize();
  }

  new MutationObserver(() => {
    document.querySelectorAll(selector).forEach(applyTheme);
  }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
})();
