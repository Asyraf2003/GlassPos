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
            background: #f0ebf8;
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
            max-width: 720px;
            margin: 0 auto;
            padding-bottom: 2rem;
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
            box-shadow: none;
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
        .cashier-workspace-stepper .form-select {
            min-height: 2.75rem;
            border: 0;
            border-bottom: 1px solid #dadce0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            padding-inline: 0;
        }

        .cashier-workspace-stepper textarea.form-control {
            min-height: 5.25rem;
            resize: vertical;
        }

        .cashier-workspace-stepper .form-control:focus,
        .cashier-workspace-stepper .form-select:focus {
            border-color: var(--workspace-accent);
            box-shadow: inset 0 -1px 0 var(--workspace-accent);
        }

        .cashier-workspace-stepper .btn {
            min-height: 2.75rem;
            border-radius: .35rem;
        }

        .cashier-workspace-stepper .btn-primary {
            border-color: var(--workspace-accent);
            background: var(--workspace-accent);
            color: #fff;
        }

        .cashier-workspace-stepper .btn-primary:hover,
        .cashier-workspace-stepper .btn-primary:focus {
            border-color: #512da8;
            background: #512da8;
            color: #fff;
        }

        .cashier-workspace-stepper .btn-light-primary,
        .cashier-workspace-stepper [data-add-product-line] {
            border: 1px solid #d1c4e9;
            background: #fff;
            color: var(--workspace-accent);
            font-weight: 700;
        }

        .cashier-workspace-stepper .btn-light-secondary,
        .cashier-workspace-stepper .btn-light-danger {
            border: 1px solid #dadce0;
            background: #fff;
            color: #3c4043;
            font-weight: 700;
        }

        .cashier-workspace-stepper [data-line-item] {
            border-radius: .5rem !important;
            background: #fff;
        }

        .cashier-workspace-stepper .workspace-answer-card {
            border: 1px solid var(--workspace-border) !important;
            border-left: 1px solid var(--workspace-border) !important;
            border-radius: .5rem !important;
            padding: 1rem 1.05rem !important;
            margin-bottom: .85rem !important;
            box-shadow: none !important;
        }

        .cashier-workspace-stepper .workspace-answer-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            padding-bottom: .85rem;
            margin-bottom: .85rem;
            border-bottom: 1px solid #eceff1;
        }

        .cashier-workspace-stepper .workspace-answer-field {
            border: 0;
            border-radius: 0;
            background: transparent;
            padding: .25rem 0 .8rem;
        }

        .cashier-workspace-stepper .workspace-answer-field + .workspace-answer-field {
            margin-top: .75rem;
        }

        .cashier-workspace-stepper .workspace-answer-card .form-label {
            color: #3c4043;
            font-weight: 700;
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
            width: 2.25rem;
            height: 2.25rem;
            flex: 0 0 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: var(--workspace-accent);
            transition: transform .15s ease;
        }

        .cashier-workspace-stepper details[open] .workspace-details-toggle {
            transform: rotate(180deg);
        }

        .cashier-workspace-stepper .workspace-note-card {
            border: 0;
            border-radius: 0;
            background: transparent;
            padding: 0;
        }

        .cashier-workspace-stepper .workspace-add-question-wrap {
            width: 100%;
            min-width: min(100%, 20rem);
        }

        .cashier-workspace-stepper .ui-form-actions,
        .cashier-workspace-stepper .ui-form-actions .btn {
            width: 100%;
        }

        .cashier-workspace-stepper .ui-form-actions .btn {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cashier-workspace-stepper .workspace-add-question-button {
            border: 1px dashed #b39ddb;
            color: var(--workspace-accent);
            background: #fff;
            font-weight: 700;
        }

        .cashier-workspace-stepper .workspace-add-question-button:hover,
        .cashier-workspace-stepper .workspace-add-question-button:focus {
            border-color: var(--workspace-accent);
            color: var(--workspace-accent);
            background: var(--workspace-accent-soft);
        }

        .cashier-workspace-stepper .workspace-add-question-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.35rem;
            height: 1.35rem;
            margin-right: .35rem;
            border-radius: 50%;
            color: #fff;
            background: var(--workspace-accent);
            line-height: 1;
        }

        .cashier-workspace-stepper .workspace-item-type-menu {
            z-index: 30;
            width: min(calc(100vw - 2rem), 22rem);
            border: 1px solid var(--workspace-border);
            border-radius: .5rem;
            background: #fff;
            box-shadow: 0 .35rem .9rem rgba(60, 64, 67, .16);
            overflow: hidden;
        }

        .cashier-workspace-stepper .workspace-item-type-menu-body {
            display: grid;
            gap: 0;
            padding: .25rem 0;
        }

        .cashier-workspace-stepper .workspace-item-type-option {
            width: 100%;
            min-height: 2.75rem;
            border: 0;
            border-bottom: 1px solid #f1f3f4;
            background: #fff;
            color: var(--workspace-text);
            text-align: left;
            padding: .8rem 1rem;
            font-weight: 600;
        }

        .cashier-workspace-stepper .workspace-item-type-option:last-child {
            border-bottom: 0;
        }

        .cashier-workspace-stepper .workspace-item-type-option:hover,
        .cashier-workspace-stepper .workspace-item-type-option:focus {
            color: var(--workspace-accent);
            background: #f8f6ff;
        }

        .cashier-workspace-stepper .workspace-empty-answer {
            border: 1px dashed #c7c4d1;
            border-radius: .5rem;
            background: #fff;
            padding: 1.2rem;
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
                padding-bottom: 2rem;
            }

            .cashier-workspace-stepper .workspace-step-header,
            .cashier-workspace-stepper .workspace-step-body {
                padding-inline: .9rem;
            }

            .cashier-workspace-stepper .workspace-add-question-wrap {
                width: 100%;
            }

            .cashier-workspace-stepper .workspace-add-question-button {
                width: 100%;
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
            'serviceLookupEndpoint' => $serviceLookupEndpoint ?? null,
            'serviceStoreEndpoint' => $serviceStoreEndpoint ?? null,
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
