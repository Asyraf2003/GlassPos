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
        data-presentation-mode="{{ ($workspaceMode ?? 'create') === 'edit' ? 'detail' : 'simple' }}"
        data-workspace-mode="{{ $workspaceMode ?? 'create' }}"
    >
        <header class="workspace-toolbar">
            <div>
                <div class="workspace-kicker">Kasir · Nota Aktif</div>
                <p class="workspace-toolbar-copy mb-0">
                    Pilih transaksi, isi rincian, lalu simpan atau bayar langsung.
                </p>
            </div>

            <div class="workspace-mode-switch" aria-label="Mode tampilan">
                <button type="button" class="workspace-mode-choice" data-mode-choice="simple" aria-pressed="false">
                    Simple
                </button>
                <button type="button" class="workspace-mode-choice" data-mode-choice="detail" aria-pressed="false">
                    Detail
                </button>
            </div>
        </header>

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
            'presentationMode' => ($workspaceMode ?? 'create') === 'edit' ? 'detail' : 'simple',
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
