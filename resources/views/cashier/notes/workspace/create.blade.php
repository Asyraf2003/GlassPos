@extends('layouts.app')
@include('layouts.partials.date-picker-assets')
@include('cashier.notes.workspace.partials.dropdown-layer-fix')

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('back_url', $cancelAction ?? route('cashier.notes.index'))

@section('content')
<section class="section">
    @if ($errors->has('workspace'))
        <div class="alert alert-danger">{{ $errors->first('workspace') }}</div>
    @endif

    <div class="ui-page-intro">
        <div class="small text-muted text-uppercase fw-semibold">Workspace Nota Kasir</div>
        <h4 class="ui-page-intro-title">
            {{ ($workspaceMode ?? 'create') === 'edit' ? 'Edit Nota dari Workspace' : 'Buat Nota dari Workspace' }}
        </h4>
        <p class="ui-page-intro-subtitle">
            Header nota tetap di kanan dan rincian tetap di kiri supaya create dan edit memakai pola kerja yang sama.
        </p>
    </div>

    <form action="{{ $formAction ?? route('notes.workspace.store') }}" method="POST" novalidate id="cashier-note-workspace-form">
        @csrf
        @if (($workspaceMode ?? 'create') === 'edit')
            @method('PATCH')
        @endif

        <div class="row g-4">
            @include('cashier.notes.workspace.partials.rincian-card')
            @include('cashier.notes.workspace.partials.info-card')
        </div>

        @include('cashier.notes.workspace.partials.payment-modal')
        @include('cashier.notes.workspace.partials.refund-modal')
    </form>

    <script id="cashier-note-workspace-config" type="application/json">{!! json_encode([
        'oldItems' => is_array($oldItems) ? array_values($oldItems) : [],
        'oldNote' => is_array($oldNote ?? null) ? $oldNote : [],
        'oldInlinePayment' => is_array($oldInlinePayment ?? null) ? $oldInlinePayment : [],
        'defaultCustomerName' => $defaultCustomerName ?? null,
        'productLookupEndpoint' => $productLookupEndpoint ?? null,
        'workspaceMode' => $workspaceMode ?? 'create',
        'noteId' => $noteId ?? null,
        'draftLoadEndpoint' => $draftLoadEndpoint ?? route('cashier.notes.workspace.draft.show'),
        'draftSaveEndpoint' => $draftSaveEndpoint ?? route('cashier.notes.workspace.draft.save'),
        'csrfToken' => csrf_token(),
        'hasOldInput' => $hasOldInput ?? !empty(session()->getOldInput()),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/rows.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/search.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/summary.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/payment-flow.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/draft.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/boot.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
