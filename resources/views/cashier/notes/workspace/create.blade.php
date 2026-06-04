@extends('layouts.app')
@include('layouts.partials.date-picker-assets')
@include('cashier.notes.workspace.partials.dropdown-layer-fix')

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('back_url', $cancelAction ?? route('cashier.notes.index'))

@section('content')
<section class="section">
    <style>
        .section:has(.cashier-workspace-stepper) {
            background: #f6f3ff;
            padding-block: 1rem 2rem;
        }

        .cashier-workspace-stepper {
            --workspace-card: #ffffff;
            --workspace-border: #dadce0;
            --workspace-muted: #5f6368;
            --workspace-text: #202124;
            --workspace-accent: #673ab7;
            --workspace-accent-soft: #ede7f6;
            --workspace-radius: .5rem;
            max-width: 760px;
            margin: 0 auto;
            padding-bottom: 5.5rem;
        }

        .cashier-workspace-stepper .ui-page-intro {
            margin-bottom: 1rem;
        }

        .cashier-workspace-stepper .workspace-step-list {
            display: grid;
            gap: .85rem;
        }

        .cashier-workspace-stepper .workspace-step-card {
            border: 1px solid var(--workspace-border);
            border-radius: var(--workspace-radius);
            background: var(--workspace-card);
            overflow: visible;
            border-top: .45rem solid var(--workspace-accent);
        }

        .cashier-workspace-stepper .workspace-step-header {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            padding: 1rem 1rem .75rem;
            border-bottom: 1px solid rgba(15, 23, 42, .07);
        }

        .cashier-workspace-stepper .workspace-step-number {
            width: 2.25rem;
            height: 2.25rem;
            flex: 0 0 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: var(--workspace-accent);
            background: var(--workspace-accent-soft);
            border: 1px solid #d1c4e9;
            font-weight: 800;
        }

        .cashier-workspace-stepper .workspace-step-title {
            margin: 0;
            color: var(--workspace-text);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .cashier-workspace-stepper .workspace-step-help {
            margin: .18rem 0 0;
            color: var(--workspace-muted);
            font-size: .9rem;
            line-height: 1.55;
        }

        .cashier-workspace-stepper .workspace-step-body {
            padding: 1rem;
        }

        .cashier-workspace-stepper .form-control,
        .cashier-workspace-stepper .form-select,
        .cashier-workspace-stepper .btn {
            min-height: 2.75rem;
            border-radius: .35rem;
        }

        .cashier-workspace-stepper .workspace-total-action {
            position: sticky;
            bottom: .75rem;
            z-index: 30;
            margin-top: 1rem;
            border: 1px solid #d1c4e9;
            border-radius: .5rem;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 .5rem 1.2rem rgba(60, 64, 67, .16);
            backdrop-filter: blur(10px);
        }

        .cashier-workspace-stepper .workspace-total-action .btn {
            white-space: nowrap;
        }

        .cashier-workspace-stepper [data-line-item] {
            border-radius: .5rem !important;
            background: #fff;
        }

        .cashier-workspace-stepper details.workspace-step-card {
            padding: 0;
        }

        .cashier-workspace-stepper .workspace-details-summary {
            cursor: pointer;
            list-style: none;
        }

        .cashier-workspace-stepper .workspace-details-summary::-webkit-details-marker {
            display: none;
        }

        .cashier-workspace-stepper .workspace-details-toggle {
            color: var(--workspace-accent);
            font-size: .86rem;
            font-weight: 800;
        }

        .cashier-workspace-stepper .workspace-note-card {
            border: 1px solid var(--workspace-border);
            border-radius: .5rem;
            background: #fff;
            padding: 1rem;
        }

        #workspace-payment-modal .modal-content {
            border-radius: .75rem;
            border-top: .45rem solid var(--workspace-accent, #673ab7);
        }

        #workspace-payment-modal .workspace-gform-panel {
            border: 1px solid #dadce0;
            border-radius: .5rem;
            background: #fff;
            padding: 1rem;
        }

        #workspace-payment-line-summary {
            display: grid;
            gap: .65rem;
            min-height: auto !important;
            max-height: min(52vh, 34rem);
            overflow-y: auto;
            border: 0 !important;
        }

        @media (max-width: 575.98px) {
            .cashier-workspace-stepper {
                max-width: none;
                padding-bottom: 6rem;
            }

            .cashier-workspace-stepper .workspace-step-header,
            .cashier-workspace-stepper .workspace-step-body {
                padding-inline: .9rem;
            }

            .cashier-workspace-stepper .workspace-total-action {
                bottom: .5rem;
            }
        }
    </style>

    @if ($errors->has('workspace'))
        <div class="alert alert-danger">{{ $errors->first('workspace') }}</div>
    @endif

    <div class="cashier-workspace-stepper">
        <div class="ui-page-intro">
            <div class="small text-muted text-uppercase fw-semibold">Workspace Nota Kasir</div>
            <h4 class="ui-page-intro-title">
                {{ ($workspaceMode ?? 'create') === 'edit' ? 'Edit Nota dari Workspace' : 'Buat Nota dari Workspace' }}
            </h4>
            <p class="ui-page-intro-subtitle">
                Ikuti alur info nota, rincian, lalu review pembayaran sebelum nota diproses.
            </p>
        </div>

        <form action="{{ $formAction ?? route('notes.workspace.store') }}" method="POST" novalidate id="cashier-note-workspace-form">
            @csrf
            @if (($workspaceMode ?? 'create') === 'edit')
                @method('PATCH')
            @endif

            <div class="workspace-step-list">
                @include('cashier.notes.workspace.partials.info-card')
                @include('cashier.notes.workspace.partials.rincian-card')
                @include('cashier.notes.workspace.partials.note-description-card')
                @include('cashier.notes.workspace.partials.review-payment-card')
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
    </div>
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
