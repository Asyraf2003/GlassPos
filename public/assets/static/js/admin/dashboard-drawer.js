(() => {
    const drawer = document.getElementById('admin-dashboard-filter-drawer');
    const open = document.getElementById('admin-dashboard-filter-open-filter');
    if (!drawer || !open) return;
    const focusables = () => Array.from(drawer.querySelectorAll('button, input, a[href]')).filter(node => !node.disabled);
    new MutationObserver(() => {
        const visible = !drawer.classList.contains('d-none');
        open.setAttribute('aria-expanded', String(visible));
        if (visible) focusables()[0]?.focus();
        else open.focus();
    }).observe(drawer, { attributes: true, attributeFilter: ['class'] });
    drawer.addEventListener('keydown', event => {
        if (event.key === 'Escape') document.getElementById('admin-dashboard-filter-close-filter').click();
        if (event.key !== 'Tab') return;
        const items = focusables();
        const first = items[0];
        const last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
})();
