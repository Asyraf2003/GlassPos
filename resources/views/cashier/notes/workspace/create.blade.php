@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('back_url', $cancelAction ?? route('cashier.notes.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/static/css/cashier-note-workspace.css') }}?v={{ config('app.asset_version') }}">
@endpush

@section('content')
<section class="section">
    @if ($errors->has('workspace'))
        <div class="alert alert-danger">{{ $errors->first('workspace') }}</div>
    @endif

    <div
        class="cashier-note-workspace"
        data-presentation-mode="{{ $presentationMode ?? (($workspaceMode ?? 'create') === 'edit' ? 'detail' : 'simple') }}"
        data-device-class="{{ $deviceClass ?? 'desktop' }}"
        data-workspace-mode="{{ $workspaceMode ?? 'create' }}"
    >
        @if (($workspaceMode ?? 'create') === 'create')
        <header class="workspace-toolbar" aria-label="Kontrol tampilan workspace">
            <div class="form-check form-switch workspace-detail-control">
                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="workspace-detail-toggle"
                    data-detail-toggle
                    @checked(($presentationMode ?? 'simple') === 'detail')
                >
                <label class="form-check-label" for="workspace-detail-toggle">Detail</label>
            </div>
        </header>
        @endif

        <form action="{{ $formAction ?? route('notes.workspace.store') }}" method="POST" novalidate id="cashier-note-workspace-form">
            @csrf
            @if (($workspaceMode ?? 'create') === 'edit')
                @method('PATCH')
            @endif
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey ?? '') }}">

            <div class="workspace-pos-layout">
                <main class="workspace-entry-pane">
                    <div class="workspace-detail-stack" data-detail-only>
                        @include('cashier.notes.workspace.partials.info-card')
                        @include('cashier.notes.workspace.partials.note-description-card')
                    </div>

                    @include('cashier.notes.workspace.partials.rincian-card')
                </main>

                <aside class="workspace-order-pane">
                    @include('cashier.notes.workspace.partials.review-payment-card')
                </aside>
            </div>

            @include('cashier.notes.workspace.partials.payment-modal')
        </form>

        @include('cashier.notes.workspace.partials.refund-modal')

        <script id="cashier-note-workspace-config" type="application/json">{!! json_encode([
            'oldItems' => is_array($oldItems) ? array_values($oldItems) : [],
            'oldNote' => is_array($oldNote ?? null) ? $oldNote : [],
            'oldInlinePayment' => is_array($oldInlinePayment ?? null) ? $oldInlinePayment : [],
            'defaultCustomerName' => $defaultCustomerName ?? null,
            'productLookupEndpoint' => $productLookupEndpoint ?? null,
            'packageLookupEndpoint' => $packageLookupEndpoint ?? null,
            'serviceLookupEndpoint' => $serviceLookupEndpoint ?? null,
            'serviceStoreEndpoint' => $serviceStoreEndpoint ?? null,
            'workspaceMode' => $workspaceMode ?? 'create',
            'presentationMode' => $presentationMode ?? (($workspaceMode ?? 'create') === 'edit' ? 'detail' : 'simple'),
            'deviceClass' => $deviceClass ?? 'desktop',
            'noteId' => $noteId ?? null,
            'draftLoadEndpoint' => $draftLoadEndpoint ?? route('cashier.notes.workspace.draft.show'),
            'draftSaveEndpoint' => $draftSaveEndpoint ?? route('cashier.notes.workspace.draft.save'),
            'csrfToken' => csrf_token(),
            'hasOldInput' => $hasOldInput ?? !empty(session()->getOldInput()),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-money-input.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/rows.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/search.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/summary.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/service-catalog.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/package-search.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/payment-flow.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/presentation.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/draft.js') }}?v={{ config('app.asset_version') }}"></script>
    <script src="{{ asset('assets/static/js/pages/cashier-note-workspace/boot.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
