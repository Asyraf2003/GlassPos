(() => {
    const source = document.getElementById('admin-dashboard-analytics-payload');
    if (!source) return;

    const chart = document.getElementById('admin-chart-operational-performance');
    const summary = document.querySelector('[data-dashboard-analytics-target="operational-summary"]');
    const denominations = document.querySelector('[data-dashboard-analytics-target="cash-change-rows"]');
    const range = document.querySelector('[data-dashboard-analytics-target="operational-range"]');
    const number = value => new Intl.NumberFormat('id-ID').format(Number(value || 0));
    const rupiah = value => `Rp ${number(value)}`;
    const compact = value => new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(value);
    const date = value => /^\d{4}-\d{2}-\d{2}$/.test(value || '') ? value.split('-').reverse().join('-') : '—';
    const fields = [
        ['total_operational_profit_rupiah', 'Laba Operasional (dataset harian)'],
        ['total_operational_expense_rupiah', 'Biaya Operasional'],
        ['total_refund_rupiah', 'Refund'],
        ['total_potential_change_rupiah', 'Potensi Kembalian'],
    ];
    const labels = {
        operational_profit: 'Laba Operasional (dataset harian)',
        operational_expense: 'Biaya Operasional',
        refund: 'Refund',
        potential_change: 'Potensi Kembalian',
    };
    let payload = null;
    let instance = null;
    let failed = false;
    const colors = () => {
        const styles = getComputedStyle(document.documentElement);
        const token = name => styles.getPropertyValue(`--dashboard-${name}`).trim();
        return { text: token('text'), muted: token('muted'), border: token('border'), series: [token('accent'), token('warning'), token('danger'), token('neutral')] };
    };
    const empty = message => {
        chart.replaceChildren();
        const p = document.createElement('p');
        p.className = 'dashboard-empty';
        p.setAttribute('role', 'status');
        p.textContent = message;
        chart.append(p);
    };
    const renderSummary = () => {
        const totals = payload.charts.operational_performance_bar.summary || {};
        summary.replaceChildren();
        fields.forEach(([key, label]) => {
            const row = document.createElement('div');
            row.className = 'dashboard-fact';
            const term = document.createElement('dt');
            term.textContent = label;
            const value = document.createElement('dd');
            value.textContent = rupiah(totals[key]);
            row.append(term, value);
            summary.append(row);
        });
        denominations.replaceChildren();
        const rows = payload.cash_change_denominations || [];
        rows.forEach(item => {
            const row = document.createElement('tr');
            [rupiah(item.denomination), `${number(item.count)} Lembar/Koin`, rupiah(item.total_rupiah)].forEach((text, index) => {
                const cell = document.createElement('td');
                cell.textContent = text;
                if (index === 2) cell.className = 'text-end';
                row.append(cell);
            });
            denominations.append(row);
        });
        if (!rows.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 3;
            cell.className = 'dashboard-empty';
            cell.textContent = 'Belum ada data kembalian cash pada periode ini.';
            row.append(cell);
            denominations.append(row);
        }
    };
    const renderChart = () => {
        if (instance) { instance.destroy(); instance = null; }
        if (!payload || failed) return;
        const data = payload.charts.operational_performance_bar;
        const series = data.series || [];
        const days = data.labels || [];
        if (!days.length || !series.some(row => row.values?.some(value => Number(value) !== 0))) {
            empty('Belum ada aktivitas operasional pada periode ini.');
            return;
        }
        if (typeof ApexCharts === 'undefined') {
            empty('Grafik tidak tersedia. Total analitik dapat dibaca di detail rekonsiliasi.');
            return;
        }
        const palette = colors();
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        chart.replaceChildren();
        instance = new ApexCharts(chart, {
            chart: { type: 'line', height: 280, fontFamily: 'inherit', foreColor: palette.muted, background: 'transparent', toolbar: { show: false }, zoom: { enabled: false }, animations: { enabled: false }, parentHeightOffset: 0 },
            series: series.map(row => ({ name: labels[row.key] || row.label, data: row.values.map(Number) })),
            colors: palette.series,
            stroke: { curve: 'straight', width: [2, 1.5, 1.5, 1.5], dashArray: [0, 0, 4, 4] },
            markers: { size: days.length === 1 ? 4 : 0, hover: { size: 4 } },
            dataLabels: { enabled: false },
            grid: { borderColor: palette.border, strokeDashArray: 3, padding: { left: 8, right: 16 } },
            xaxis: { categories: days, tickAmount: Math.min(7, days.length), labels: { formatter: value => String(value).slice(-2), style: { colors: palette.muted, fontSize: '12px' } }, axisBorder: { color: palette.border }, axisTicks: { color: palette.border } },
            yaxis: { labels: { formatter: compact, style: { colors: palette.muted, fontSize: '12px' } } },
            legend: { position: 'bottom', fontSize: '12px', fontWeight: 400, labels: { colors: palette.text }, itemMargin: { horizontal: 8, vertical: 4 } },
            tooltip: { theme: dark ? 'dark' : 'light', shared: true, intersect: false, x: { formatter: (_, opts) => date(days[opts.dataPointIndex]) }, y: { formatter: rupiah } },
        });
        instance.render();
    };
    const failure = () => {
        failed = true;
        empty('Analitik gagal dimuat. Muat ulang halaman untuk mencoba lagi.');
        summary.querySelectorAll('dd').forEach(value => { value.textContent = 'Tidak tersedia'; });
        denominations.querySelector('td').textContent = 'Data pecahan kembalian tidak tersedia.';
    };
    const load = async () => {
        try {
            const response = await fetch(source.dataset.url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('analytics unavailable');
            payload = await response.json();
            if (!Array.isArray(payload?.charts?.operational_performance_bar?.series)) throw new Error('invalid analytics');
            range.textContent = `${date(payload.period.date_from)} – ${date(payload.period.date_to)} · Rupiah`;
            renderSummary();
            renderChart();
        } catch (_) { failure(); }
    };
    new MutationObserver(renderChart).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
    load();
})();
