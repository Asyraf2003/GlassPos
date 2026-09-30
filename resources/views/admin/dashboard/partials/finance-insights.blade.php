<div class="dashboard-grid dashboard-finance-insights">
    <section class="dashboard-panel dashboard-panel-accent dashboard-panel-accent-payroll" aria-labelledby="dashboard-payroll-title">
        <header class="dashboard-panel-head"><div><h2 id="dashboard-payroll-title">Gaji Dibayarkan</h2><p>Pencairan dalam periode · posisi akhir periode</p></div><a href="{{ route('admin.reports.payroll.index', $dashboardExportQuery) }}">Buka laporan</a></header>
        <dl class="dashboard-facts">
            <div class="dashboard-fact"><dt>Total gaji dibayarkan</dt><dd>Rp {{ number_format($dashboard['finance_insights']['payroll_total_rupiah'], 0, ',', '.') }}</dd></div>
            <div class="dashboard-fact"><dt>Penerima / pencairan</dt><dd>{{ $dashboard['finance_insights']['employee_count'] }} karyawan / {{ $dashboard['finance_insights']['payroll_count'] }} kali</dd></div>
        </dl>
        @if ($dashboard['finance_insights']['employee_rows'])
            <div class="table-responsive">
                <table class="table dashboard-table dashboard-table-compact">
                    <thead><tr><th>Penerima teratas</th><th class="text-end">Dibayarkan</th></tr></thead>
                    <tbody>
                        @foreach ($dashboard['finance_insights']['employee_rows'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td class="text-end">Rp {{ number_format($row['amount_rupiah'], 0, ',', '.') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="dashboard-empty">Belum ada pencairan gaji pada periode ini.</p>
        @endif
        <p class="dashboard-panel-foot dashboard-caption">Pencairan dikurangi pembatalan sampai akhir periode, bukan gaji yang masih terutang. Saldo kasbon saat ini tersedia pada Kewajiban &amp; Biaya.</p>
    </section>

    <section class="dashboard-panel dashboard-panel-accent dashboard-panel-accent-expense" aria-labelledby="dashboard-expense-title">
        <header class="dashboard-panel-head"><div><h2 id="dashboard-expense-title">Biaya Operasional Terbesar</h2><p>Maksimal 5 kategori · posisi akhir periode</p></div><a href="{{ route('admin.reports.operational_expense.index', $dashboardExportQuery) }}">Semua biaya</a></header>
        <div class="dashboard-chart-shell">
            <div class="dashboard-chart" data-finance-chart="bar" data-values="{{ json_encode($dashboard['finance_insights']['expense_categories']) }}" role="img" aria-label="Grafik batang biaya operasional terbesar">
                <p class="dashboard-empty" role="status">Menyiapkan grafik biaya…</p>
            </div>
        </div>
        @if ($dashboard['finance_insights']['expense_categories'])
            <dl class="dashboard-facts">
                @foreach ($dashboard['finance_insights']['expense_categories'] as $row)
                    <div class="dashboard-fact"><dt>{{ $row['name'] }}</dt><dd>Rp {{ number_format($row['amount_rupiah'], 0, ',', '.') }}</dd></div>
                @endforeach
            </dl>
        @endif
    </section>

    <section class="dashboard-panel dashboard-panel-accent dashboard-panel-accent-composition" aria-labelledby="dashboard-composition-title">
        <header class="dashboard-panel-head"><div><h2 id="dashboard-composition-title">Komposisi Biaya &amp; Gaji</h2><p>Periode terpilih · Rupiah</p></div></header>
        <div class="dashboard-chart-shell dashboard-chart-shell-donut">
            <div class="dashboard-chart" data-finance-chart="donut" data-values="{{ json_encode($dashboard['finance_insights']['composition']) }}" role="img" aria-label="Diagram lingkaran komposisi biaya dan gaji">
                <p class="dashboard-empty" role="status">Menyiapkan komposisi biaya…</p>
            </div>
        </div>
        @if ($dashboard['finance_insights']['payroll_total_rupiah'] > 0 || $dashboard['position']['monthly_operational_expense_rupiah'] > 0)
            <dl class="dashboard-facts">
                @foreach ($dashboard['finance_insights']['composition'] as $row)
                    <div class="dashboard-fact"><dt>{{ $row['name'] }}</dt><dd>Rp {{ number_format($row['amount_rupiah'], 0, ',', '.') }}</dd></div>
                @endforeach
            </dl>
        @endif
        <p class="dashboard-panel-foot dashboard-caption">Hanya biaya operasional dan gaji dibayarkan. Tidak mencakup modal barang, refund, atau pencairan kasbon.</p>
    </section>
</div>
