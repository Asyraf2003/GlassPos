@extends('layouts.app')
@section('title', 'Detail Audit Log')
@section('heading', 'Detail Audit Log')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h4 class="card-title mb-1">{{ $entry['event'] }}</h4>
                    <p class="text-muted mb-0">{{ $entry['source'] }} · {{ $entry['id'] }}</p>
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-light-secondary">Kembali</a>
            </div>
            <div class="card-body">
                <dl class="row mb-4">
                    <dt class="col-sm-3">Waktu</dt><dd class="col-sm-9">{{ \App\Support\ViewDateFormatter::display($entry['created_at'], true) }}</dd>
                    <dt class="col-sm-3">Actor</dt><dd class="col-sm-9">{{ $entry['actor_id'] ?? '-' }} @if($entry['actor_role'])({{ $entry['actor_role'] }})@endif</dd>
                    <dt class="col-sm-3">Entity</dt><dd class="col-sm-9">{{ $entry['entity_type'] ?? '-' }} · {{ $entry['entity_id'] ?? '-' }}</dd>
                    <dt class="col-sm-3">Bounded Context</dt><dd class="col-sm-9">{{ $entry['bounded_context'] ?? '-' }}</dd>
                    <dt class="col-sm-3">Alasan</dt><dd class="col-sm-9">{{ $entry['reason'] }}</dd>
                </dl>
                <h5>Context</h5>
                <pre class="bg-light p-3 rounded mb-0" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $entry['context_json'] }}</pre>
            </div>
        </div>
    </section>
@endsection
