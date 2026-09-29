<style>
    .report-detail { border-collapse: collapse; width: 100%; font-size: 7px; table-layout: fixed; }
    .report-detail th, .report-detail td { border: 1px solid #d1d5db; padding: 4px; overflow-wrap: break-word; }
    .report-detail th { background: #f3f4f6; text-align: left; }
    .report-detail thead { display: table-header-group; }
    .report-detail tr { page-break-inside: avoid; }
</style>
@foreach ($detailTables as $table)
    <h2>{{ $table['title'] }}</h2>
    <table class="report-detail">
        <thead><tr>
            @foreach ($table['columns'] as $label)
                <th>{{ $label }}</th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse ($table['rows'] as $row)
                <tr>
                    @foreach ($table['columns'] as $key => $label)
                        <td>{{ $row[$key] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($table['columns']) }}">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
@endforeach
