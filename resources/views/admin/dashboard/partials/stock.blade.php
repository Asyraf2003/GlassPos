<section class="dashboard-panel" aria-labelledby="dashboard-stock-title">
    <header class="dashboard-panel-head"><div><h2 id="dashboard-stock-title">Status Stok Saat Ini</h2><p>Posisi sekarang · tidak mengikuti filter bulan</p></div></header>
    <dl class="dashboard-stock-counts">
        <div class="dashboard-stock-count is-critical"><dt><span class="dashboard-dot" aria-hidden="true"></span>Stok Kritis</dt><dd>{{ number_format($dashboard['stats']['stock_critical_product_rows'] ?? 0, 0, ',', '.') }} <small>produk</small></dd></div>
        <div class="dashboard-stock-count is-low"><dt><span class="dashboard-dot" aria-hidden="true"></span>Mulai Restok</dt><dd>{{ number_format($dashboard['stats']['stock_low_product_rows'] ?? 0, 0, ',', '.') }} <small>produk</small></dd></div>
        <div class="dashboard-stock-count is-safe"><dt><span class="dashboard-dot" aria-hidden="true"></span>Stok Aman</dt><dd>{{ number_format($dashboard['stats']['stock_safe_product_rows'] ?? 0, 0, ',', '.') }} <small>produk</small></dd></div>
        <div class="dashboard-stock-count is-neutral"><dt><span class="dashboard-dot" aria-hidden="true"></span>Belum Diatur</dt><dd>{{ number_format($dashboard['stats']['stock_unconfigured_product_rows'] ?? 0, 0, ',', '.') }} <small>produk</small></dd></div>
    </dl><dl class="dashboard-facts dashboard-panel-foot">
            <div class="dashboard-fact">
                <dt>Total Stok Tersedia</dt>
                <dd>{{ number_format($dashboard['stats']['total_qty_on_hand'] ?? 0, 0, ',', '.') }} Unit</dd>
            </div>
            <div class="dashboard-fact">
                <dt>Nilai Modal Stok</dt>
                <dd>Rp {{ number_format($dashboard['stats']['total_inventory_value_rupiah'] ?? 0, 0, ',', '.') }}</dd>
            </div>
    </dl>
</section>
