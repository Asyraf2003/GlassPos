<section class="dashboard-panel" aria-labelledby="dashboard-restock-title">
    <header class="dashboard-panel-head">
        <div><h2 id="dashboard-restock-title">Prioritas Restok</h2><p>Saat ini · maksimal 5 produk, stok kritis lebih dulu</p></div>
        <a href="{{ route('admin.reports.inventory_stock_value.index', $dashboardExportQuery) }}">Lihat stok <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </header>
    <div class="table-responsive">
        <table class="table dashboard-table">
            <thead><tr><th scope="col">Produk</th><th scope="col" class="text-end">Tersedia</th><th scope="col" class="text-end">Batas restok</th><th scope="col" class="text-end">Batas kritis</th><th scope="col">Status</th></tr></thead>
            <tbody>
            @forelse (($dashboard['restock_priority_rows'] ?? []) as $row)
                <tr>
                    <td><a class="dashboard-product" href="{{ route('admin.products.show', ['productId' => $row['product_id']]) }}" aria-label="Lihat Detail {{ $row['nama_barang'] }}">{{ $row['nama_barang'] }}</a></td>
                    <td class="text-end">{{ number_format($row['current_qty_on_hand'], 0, ',', '.') }} Unit</td>
                    <td class="text-end">{{ number_format($row['reorder_point_qty'], 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($row['critical_threshold_qty'], 0, ',', '.') }}</td>
                    <td><span class="dashboard-status {{ $row['status'] === 'critical' ? 'is-critical' : 'is-low' }}">{{ $row['status_label'] }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="dashboard-empty">Tidak ada prioritas restok dari produk dengan batas stok yang sudah diatur.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
