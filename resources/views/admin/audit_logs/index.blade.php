@extends('layouts.app')
@section('title', 'Audit Log')
@section('heading', 'Audit Log')

@section('content')
    <section class="section"><div class="card">
        <div class="card-header"><div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
            <div><h4 class="card-title mb-1">Investigasi Aktivitas</h4><p class="text-muted mb-0">Alasan perubahan dicatat dari fitur asal.</p></div>
            <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                <form id="audit-log-search-form" class="m-0 d-flex"><input id="audit-log-search-input" type="text" value="{{ $search }}" class="form-control py-2" placeholder="Cari event, entity, actor, source, alasan" autocomplete="off" style="min-height:40px;"></form>
                <button type="button" id="open-audit-log-filter" class="btn btn-primary py-2">Filter</button>
            </div>
        </div></div>
        <div class="card-body">
            @include('admin.audit_logs.partials.filter_drawer')
            <div class="table-responsive"><table class="table table-lg align-middle" id="audit-log-table">
                <thead><tr class="text-nowrap"><th style="width:80px;">ID</th>
                    @foreach ([['created_at', 'Waktu'], ['source', 'Source'], ['event', 'Event'], ['actor', 'Actor'], ['entity', 'Entity']] as [$key, $label])
                        <th><button type="button" class="btn btn-link p-0 text-decoration-none" data-sort-by="{{ $key }}">{{ $label }} <span class="ms-1 text-muted" data-sort-indicator="{{ $key }}">↕</span></button></th>
                    @endforeach
                    <th>Alasan</th><th>Aksi</th></tr></thead>
                <tbody id="audit-log-table-body">@forelse ($logs as $entry) @include('admin.audit_logs.partials.row', ['entry' => $entry]) @empty <tr><td colspan="8" class="text-center text-muted py-4">Belum ada audit log yang cocok.</td></tr> @endforelse</tbody>
            </table></div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                <small id="audit-log-table-summary" class="text-muted">Menampilkan {{ $logs->firstItem() ?? 0 }} sampai {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} audit log</small>
                <div id="audit-log-table-pagination">@include('layouts.partials.pagination', ['paginator' => $logs])</div>
            </div>
        </div>
    </div></section>
@endsection

@push('scripts')
    <script>window.auditLogTableConfig = { endpoint: @json(route('admin.audit-logs.table')) };</script>
    <script src="{{ asset('assets/static/js/pages/admin-audit-logs-table.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
