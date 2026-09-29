<div class="dashboard-grid dashboard-finance">
    <section class="dashboard-panel" aria-labelledby="dashboard-cash-title">
        <header class="dashboard-panel-head"><div><h2 id="dashboard-cash-title">Buku Kas Transaksi</h2><p>Berdasarkan tanggal pembayaran dan refund</p></div><a href="{{ route('admin.reports.transaction_cash_ledger.index', $dashboardExportQuery) }}">Buka laporan</a></header>
        <dl class="dashboard-facts">
            <div class="dashboard-fact">
                <dt>Uang Masuk Sebelum Refund</dt>
                <dd>Rp {{ number_format($dashboard['finance']['monthly_cash_in_rupiah'] ?? 0, 0, ',', '.') }}</dd>
            </div>
            <div class="dashboard-fact">
                <dt>Uang Dikembalikan<span class="dashboard-caption">Refund dan pengembalian surplus yang dibayar</span></dt>
                <dd>Rp {{ number_format($dashboard['finance']['monthly_cash_out_rupiah'] ?? 0, 0, ',', '.') }}</dd>
            </div>
            <div class="dashboard-fact">
                <dt>Arus Kas Bersih Transaksi<span class="dashboard-caption">Uang masuk dikurangi uang dikembalikan</span></dt>
                <dd>Rp {{ number_format($dashboard['finance']['monthly_net_cash_flow_rupiah'] ?? 0, 0, ',', '.') }}</dd>
            </div>
            <div class="dashboard-fact">
                <dt>Uang Diterima Hari Ini<span class="dashboard-caption">{{ \App\Support\ViewDateFormatter::display($dashboard['period']['today'] ?? null) }} · sebelum refund</span></dt>
                <dd>Rp {{ number_format($dashboard['stats']['daily_cash_in_rupiah'] ?? 0, 0, ',', '.') }}</dd>
            </div>
        </dl>
    </section>
    <section class="dashboard-panel" aria-labelledby="dashboard-position-title">
        <header class="dashboard-panel-head"><div><h2 id="dashboard-position-title">Kewajiban &amp; Biaya</h2><p>Saldo kewajiban dan biaya operasional</p></div></header>
        <dl class="dashboard-facts">
            <div class="dashboard-fact">
                <dt><a href="{{ route('admin.reports.supplier_payable.index') }}">Sisa Hutang Supplier</a><span class="dashboard-caption">Saldo seluruh faktur aktif saat ini</span></dt>
                <dd>Rp {{ number_format($dashboard['position']['supplier_outstanding_rupiah'] ?? 0, 0, ',', '.') }}</dd>
            </div>
            <div class="dashboard-fact">
                <dt><a href="{{ route('admin.reports.employee_debt.index') }}">Kasbon/Hutang Karyawan</a><span class="dashboard-caption">Sisa seluruh kasbon saat ini</span></dt>
                <dd>Rp {{ number_format($dashboard['position']['employee_debt_remaining_rupiah'] ?? 0, 0, ',', '.') }}</dd>
            </div>
            <div class="dashboard-fact">
                <dt><a href="{{ route('admin.reports.operational_expense.index', $dashboardExportQuery) }}">Biaya Operasional</a><span class="dashboard-caption">Biaya bertanggal dalam periode</span></dt>
                <dd>Rp {{ number_format($dashboard['position']['monthly_operational_expense_rupiah'] ?? 0, 0, ',', '.') }}</dd>
            </div>
        </dl>
        <a class="dashboard-report-link" href="{{ route('admin.reports.operational_profit.index', $dashboardExportQuery) }}">Rincian sisa kas operasional <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </section>
</div>
