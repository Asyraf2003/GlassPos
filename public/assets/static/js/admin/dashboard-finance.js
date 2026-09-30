(() => {
    const elements = [...document.querySelectorAll('[data-finance-chart]')];
    if (!elements.length) return;

    const rupiah = value => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;
    const compact = value => new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(value);
    const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);
    const instances = new Map();
    const empty = (element, message) => {
        instances.get(element)?.destroy();
        instances.delete(element);
        element.replaceChildren();
        const text = document.createElement('p');
        text.className = 'dashboard-empty';
        text.setAttribute('role', 'status');
        text.textContent = message;
        element.append(text);
    };
    const donutTooltip = ({ series, seriesIndex, w }) => {
        const label = escapeHtml(w.globals.labels[seriesIndex] ?? 'Nilai');
        const accent = w.globals.colors[seriesIndex] ?? '#111827';
        return `<div class="dashboard-donut-tooltip" style="--dashboard-donut-tooltip-accent:${accent}"><span class="dashboard-donut-tooltip-label">${label}</span><strong>${rupiah(Number(series[seriesIndex] || 0))}</strong></div>`;
    };
    const render = async () => {
        const styles = getComputedStyle(document.documentElement);
        const token = name => styles.getPropertyValue(`--dashboard-${name}`).trim();
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const palette = [token('accent'), token('success'), token('warning'), token('danger'), token('info')];

        for (const element of elements) {
            let rows;
            try { rows = JSON.parse(element.dataset.values || '[]'); } catch (_) { rows = []; }
            const donut = element.dataset.financeChart === 'donut';
            const values = rows.map(row => Number(row.amount_rupiah || 0));

            if (!rows.length || !values.some(value => value > 0)) {
                empty(element, donut ? 'Belum ada biaya atau gaji untuk divisualkan.' : 'Belum ada biaya operasional untuk divisualkan.');
                continue;
            }
            if (typeof ApexCharts === 'undefined') {
                empty(element, 'Grafik tidak tersedia. Nilai tetap dapat dibaca pada rincian di bawah.');
                continue;
            }

            instances.get(element)?.destroy();
            element.replaceChildren();
            const chart = new ApexCharts(element, {
                chart: { type: donut ? 'donut' : 'bar', height: 268, fontFamily: 'inherit', foreColor: token('muted'), background: 'transparent', toolbar: { show: false }, animations: { enabled: false }, parentHeightOffset: 0 },
                series: donut ? values : [{ name: 'Biaya', data: values }],
                labels: rows.map(row => row.name),
                colors: donut ? [token('warning'), token('accent')] : palette,
                plotOptions: {
                    bar: { horizontal: true, distributed: true, borderRadius: 5, barHeight: '56%' },
                    pie: { donut: { size: '64%', labels: { show: true, total: { show: true, label: 'Total', formatter: chart => rupiah(chart.globals.seriesTotals.reduce((sum, value) => sum + value, 0)) } } } },
                },
                dataLabels: { enabled: false },
                stroke: { width: donut ? 3 : 0, colors: [token('surface')] },
                xaxis: { categories: rows.map(row => row.name), labels: { formatter: compact } },
                grid: { borderColor: token('border'), strokeDashArray: 3, padding: { left: 8, right: 12 } },
                legend: { show: donut, position: 'bottom', labels: { colors: token('text') } },
                tooltip: donut ? { custom: donutTooltip } : { theme: dark ? 'dark' : 'light', y: { formatter: rupiah } },
            });
            instances.set(element, chart);
            try { await chart.render(); } catch (_) { empty(element, 'Grafik gagal dirender. Nilai tetap tersedia pada rincian.'); }
        }
    };

    new MutationObserver(render).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
    render();
})();
