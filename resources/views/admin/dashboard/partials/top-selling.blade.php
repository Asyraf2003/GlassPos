<section class="dashboard-panel" aria-labelledby="dashboard-selling-title">
    <header class="dashboard-panel-head"><div><h2 id="dashboard-selling-title">Barang Paling Laku</h2><p>Maksimal 5 produk · kuantitas dan nilai setelah reversal</p></div></header>
    <div class="table-responsive">
        <table class="table dashboard-table">
            <thead><tr><th scope="col">Produk</th><th scope="col" class="text-end">Terjual bersih</th><th scope="col" class="text-end">Nilai produk</th></tr></thead>
            <tbody>
            @forelse (($dashboard['top_selling_rows'] ?? []) as $row)
                <tr>
                    <td><a class="dashboard-product" href="{{ route('admin.products.show', ['productId' => $row['product_id']]) }}">{{ $row['nama_barang'] }}</a></td>
                    <td class="text-end">{{ number_format($row['sold_qty'], 0, ',', '.') }} Unit</td>
                    <td class="text-end">Rp {{ number_format($row['gross_revenue_rupiah'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="dashboard-empty">Belum ada produk terjual bersih pada periode ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="dashboard-panel-foot dashboard-caption">Mengikuti tanggal nota. Nilai produk bukan uang diterima atau laba.</p>
</section>
