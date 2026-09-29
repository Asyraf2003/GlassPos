@foreach ($detailTables as $table)
    <section class="mb-4">
        <h5>{{ $table['title'] }}</h5>
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm">
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
        </div>
        @if ($table['rows'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="d-flex justify-content-end mt-3">
                @include('layouts.partials.pagination', ['paginator' => $table['rows']])
            </div>
        @endif
    </section>
@endforeach
