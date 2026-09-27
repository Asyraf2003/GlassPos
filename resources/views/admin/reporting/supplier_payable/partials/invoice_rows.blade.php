<div class="card">
    <div class="card-header"><h5 class="mb-0">Rincian Faktur Pemasok</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr>
                    <th>Nomor Faktur</th><th>Supplier</th><th>Tanggal Pengiriman</th><th>Jatuh Tempo</th>
                    <th class="text-end">Total Faktur</th><th class="text-end">Total Dibayar</th>
                    <th class="text-end">Outstanding</th><th>Status Jatuh Tempo</th>
                </tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['nomor_faktur'] }}</td>
                            <td>{{ $row['supplier_name'] }}</td>
                            <td>{{ \App\Support\ViewDateFormatter::display($row['shipment_date']) }}</td>
                            <td>{{ \App\Support\ViewDateFormatter::display($row['due_date']) }}</td>
                            <td class="text-end text-nowrap">Rp {{ number_format($row['grand_total_rupiah'], 0, ',', '.') }}</td>
                            <td class="text-end text-nowrap">Rp {{ number_format($row['total_paid_rupiah'], 0, ',', '.') }}</td>
                            <td class="text-end text-nowrap">Rp {{ number_format($row['outstanding_rupiah'], 0, ',', '.') }}</td>
                            <td>{{ $row['due_status_label'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">Tidak ada faktur pemasok pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </div>
</div>
