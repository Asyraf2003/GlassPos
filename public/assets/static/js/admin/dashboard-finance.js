(() => {
    const elements = [...document.querySelectorAll('[data-finance-chart]')];
    if (!elements.length || typeof ApexCharts === 'undefined') return;
    const rupiah = value => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;
    const compact = value => new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(value);
    const instances = new Map();
    const render = async () => {
        const styles = getComputedStyle(document.documentElement);
        const token = name => styles.getPropertyValue(`--dashboard-${name}`).trim();
        for (const element of elements) {
            instances.get(element)?.destroy();
            instances.delete(element);
            element.replaceChildren();
            try {
                const rows = JSON.parse(element.dataset.values);
                const donut = element.dataset.financeChart === 'donut';
                const values = rows.map(row => row.amount_rupiah);
                if (!values.some(value => value > 0)) { element.hidden = true; continue; }
                element.hidden = false;
                const chart = new ApexCharts(element, {
                    chart: { type: donut ? 'donut' : 'bar', height: 260, fontFamily: 'inherit', foreColor: token('muted'), background: 'transparent', toolbar: { show: false }, animations: { enabled: false } },
                    series: donut ? values : [{ name: 'Biaya', data: values }],
                    labels: rows.map(row => row.name),
                    colors: [token('accent'), token('warning')],
                    plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '50%' }, pie: { donut: { size: '68%' } } },
                    dataLabels: { enabled: false },
                    stroke: { width: donut ? 2 : 0, colors: [token('surface')] },
                    xaxis: { categories: rows.map(row => row.name), labels: { formatter: compact } },
                    grid: { borderColor: token('border'), strokeDashArray: 3 },
                    legend: { show: donut, position: 'bottom' },
                    tooltip: { theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light', y: { formatter: rupiah } },
                });
                instances.set(element, chart);
                await chart.render();
            } catch (_) {
                instances.get(element)?.destroy();
                instances.delete(element);
                element.hidden = true;
            }
        }
    };
    new MutationObserver(render).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
    render();
})();
