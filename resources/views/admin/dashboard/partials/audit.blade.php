<details class="dashboard-panel dashboard-audit">
    <summary>Riwayat Uang dan Stok <span class="dashboard-caption">Refund, reversal &amp; basis perhitungan</span></summary>
    <div class="dashboard-audit-body">
        @if ((bool) ($dashboard['ledger_activity']['is_cash_fully_refunded_period'] ?? false))
            <p class="dashboard-notice" role="status">Refund keluar pada periode ini sama dengan atau melebihi uang masuk. Arus kas bersih dapat nol atau negatif meskipun ada aktivitas pembayaran.</p>
        @endif
        <div class="dashboard-grid">
            <section aria-labelledby="dashboard-reconcile-title">
                <h2 id="dashboard-reconcile-title">Detail Rekonsiliasi</h2>
                <dl class="dashboard-facts dashboard-facts-flush">
                    <div class="dashboard-fact">
                        <dt>Refund atas Nota Periode Ini<span class="dashboard-caption">Customer refund untuk nota terpilih, termasuk refund lintas bulan</span></dt>
                        <dd>Rp {{ number_format($dashboard['position']['monthly_refunded_rupiah'] ?? 0, 0, ',', '.') }}</dd>
                    </div>
                    <div class="dashboard-fact">
                        <dt>Barang Keluar Sebelum Barang Balik</dt>
                        <dd>{{ number_format($dashboard['ledger_activity']['stock_out_qty_before_reversal'] ?? 0, 0, ',', '.') }} Unit</dd>
                    </div>
                    <div class="dashboard-fact">
                        <dt>Barang Balik / Reversal</dt>
                        <dd>{{ number_format($dashboard['ledger_activity']['stock_reversal_qty'] ?? 0, 0, ',', '.') }} Unit</dd>
                    </div>
                    <div class="dashboard-fact">
                        <dt>Barang Keluar Bersih</dt>
                        <dd>{{ number_format($dashboard['ledger_activity']['net_stock_out_qty'] ?? 0, 0, ',', '.') }} Unit</dd>
                    </div>
                </dl>
                <p class="dashboard-caption">Nilai nota, uang bersih diterima, dan sisa tagihan mengikuti tanggal nota. Buku kas mengikuti tanggal pembayaran/refund, termasuk pengembalian surplus. Mutasi stok mengikuti tanggal mutasi.</p>
                <p class="dashboard-caption">Sisa kas operasional mengurangi refund, modal produk, biaya, gaji, dan kasbon. Dataset tren harian memakai pembelian luar sebelum pengurangan retur komponen; ringkasan kas operasional sudah memperhitungkan pengurangannya. Total keduanya dapat berbeda.</p>
            </section>
            <section aria-labelledby="dashboard-analytics-detail-title">
                <h2 id="dashboard-analytics-detail-title">Total Dataset Analitik</h2>
                <dl class="dashboard-facts dashboard-facts-flush" data-dashboard-analytics-target="operational-summary">
                    <div class="dashboard-fact"><dt>Laba Operasional (dataset harian)</dt><dd>—</dd></div>
                    <div class="dashboard-fact"><dt>Biaya Operasional</dt><dd>—</dd></div>
                    <div class="dashboard-fact"><dt>Refund</dt><dd>—</dd></div>
                    <div class="dashboard-fact"><dt>Potensi Kembalian</dt><dd>—</dd></div>
                </dl>
                @include('admin.dashboard.partials.cash-change-denominations')
            </section>
        </div>
    </div>
</details>
