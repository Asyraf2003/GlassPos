@extends('layouts.app')

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('back_url', route('cashier.notes.index'))

@section('content')
<section class="section">
  <style>
    .section:has(.cashier-note-detail) {
      --detail-card: var(--cashier-surface);
      --detail-surface-subtle: var(--cashier-surface-subtle);
      --detail-border: var(--cashier-border);
      --detail-border-strong: var(--cashier-border-strong);
      --detail-muted: var(--cashier-muted);
      --detail-text: var(--cashier-text);
      --detail-accent: var(--cashier-accent);
      --detail-accent-soft: var(--cashier-accent-soft);
      --detail-accent-border: var(--cashier-accent-border);
      background: var(--cashier-page-bg);
      padding-block: 1rem 2rem;
    }

    .cashier-note-detail {
      max-width: 720px;
      margin: 0 auto;
    }

    .cashier-note-detail-shell {
      display: grid;
      gap: .85rem;
    }

    .cashier-note-detail-step {
      border: 1px solid var(--detail-border);
      border-radius: .5rem;
      background: var(--detail-card);
      border-top: .45rem solid var(--detail-accent);
      box-shadow: none;
      overflow: visible;
    }

    .cashier-note-detail-header {
      display: flex;
      align-items: flex-start;
      gap: .85rem;
      padding: 1rem 1rem .75rem;
      border-bottom: 1px solid var(--detail-border);
    }

    summary.cashier-note-detail-header {
      cursor: pointer;
      list-style: none;
    }

    summary.cashier-note-detail-header::-webkit-details-marker {
      display: none;
    }

    .cashier-note-detail-number {
      width: 2.25rem;
      height: 2.25rem;
      flex: 0 0 2.25rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      color: var(--detail-accent);
      background: var(--detail-accent-soft);
      border: 1px solid var(--detail-accent-border);
      font-weight: 800;
    }

    .cashier-note-detail-title {
      margin: 0;
      color: var(--detail-text);
      font-size: 1rem;
      font-weight: 800;
      line-height: 1.35;
    }

    .cashier-note-detail-help {
      margin: .18rem 0 0;
      color: var(--detail-muted);
      font-size: .9rem;
      line-height: 1.55;
    }

    .cashier-note-detail-toggle {
      width: 2.25rem;
      height: 2.25rem;
      flex: 0 0 2.25rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-left: auto;
      border-radius: 50%;
      color: var(--detail-accent);
      transition: transform .15s ease;
    }

    .cashier-note-detail-step[open] .cashier-note-detail-toggle {
      transform: rotate(180deg);
    }

    .cashier-note-detail-body {
      padding: 1rem;
    }

    .cashier-note-detail .ui-card-stack {
      display: grid;
      gap: .85rem;
    }

    .cashier-note-detail .cashier-note-header-stack {
      display: grid;
      grid-template-columns: 1fr;
      gap: .85rem;
    }

    .cashier-note-detail .card {
      border: 0;
      border-radius: 0;
      background: transparent;
      color: var(--detail-text);
      box-shadow: none !important;
      overflow: visible;
    }

    .cashier-note-detail .card-header {
      border-bottom: 1px solid var(--detail-border);
      background: transparent;
      padding: 0 0 .85rem;
      margin-bottom: .85rem;
    }

    .cashier-note-detail .card-body {
      padding: 0;
    }

    .cashier-note-detail .card-title {
      color: var(--detail-text);
      font-size: 1rem;
      font-weight: 800;
      line-height: 1.35;
    }

    .cashier-note-detail .badge {
      border-color: var(--detail-border) !important;
      border-radius: 999px;
      background: var(--detail-surface-subtle) !important;
      color: var(--detail-text) !important;
      font-weight: 700;
    }

    .cashier-note-detail .border.rounded,
    .cashier-note-detail .bg-body {
      border-color: var(--detail-border) !important;
      border-radius: .5rem !important;
      background: var(--detail-surface-subtle) !important;
      color: var(--detail-text) !important;
    }

    .cashier-note-detail .ui-key-value {
      border-bottom: 1px solid var(--detail-border) !important;
      padding-block: .85rem !important;
    }

    .cashier-note-detail .ui-key-value small,
    .cashier-note-detail .text-muted {
      color: var(--detail-muted) !important;
    }

    .cashier-note-detail .table-responsive {
      border: 1px solid var(--detail-border);
      border-radius: .5rem;
      -webkit-overflow-scrolling: touch;
    }

    .cashier-note-detail .table {
      margin-bottom: 0;
      --bs-table-striped-bg: var(--detail-surface-subtle);
      --bs-table-bg: var(--detail-card);
      --bs-table-color: var(--detail-text);
      --bs-table-border-color: var(--detail-border);
    }

    .cashier-note-detail .table thead th {
      border-bottom: 1px solid var(--detail-border);
      background: var(--detail-card);
      color: var(--detail-muted);
      font-size: .78rem;
      font-weight: 800;
      text-transform: none;
      white-space: nowrap;
    }

    .cashier-note-detail .table tbody td {
      border-color: var(--detail-border);
      vertical-align: top;
    }

    .cashier-note-detail .form-control,
    .cashier-note-detail .form-select,
    #note-payment-modal .form-control,
    #note-payment-modal .form-select,
    #note-refund-modal .form-control,
    #note-refund-modal .form-select {
      border-color: var(--detail-border);
      background: var(--detail-card);
      color: var(--detail-text);
    }

    .cashier-note-detail .form-control:focus,
    .cashier-note-detail .form-select:focus,
    #note-payment-modal .form-control:focus,
    #note-payment-modal .form-select:focus,
    #note-refund-modal .form-control:focus,
    #note-refund-modal .form-select:focus {
      border-color: var(--detail-accent);
      box-shadow: 0 0 0 .15rem var(--detail-accent-soft);
    }

    .cashier-note-detail .btn {
      min-height: 2.75rem;
      border-radius: .35rem;
      font-weight: 800;
    }

    .cashier-note-detail .d-grid,
    .cashier-note-detail .ui-form-actions {
      width: 100%;
    }

    .cashier-note-detail .d-grid .btn,
    .cashier-note-detail .ui-form-actions .btn,
    .cashier-note-detail .card-body > .btn,
    .cashier-note-detail a.btn,
    .cashier-note-detail button.btn {
      display: flex;
      align-items: center;
      text-align: center;
      width: 100%;
      justify-content: center;
    }

    .cashier-note-detail .btn-primary {
      border-color: var(--detail-accent);
      background: var(--detail-accent);
      color: #fff;
    }

    .cashier-note-detail .btn-primary:hover,
    .cashier-note-detail .btn-primary:focus {
      border-color: var(--detail-accent);
      background: var(--detail-accent);
      color: #fff;
    }

    .cashier-note-detail .btn-outline-secondary,
    #note-payment-modal .btn-outline-secondary,
    #note-refund-modal .btn-outline-secondary {
      border-color: var(--detail-border);
      background: var(--detail-card);
      color: var(--detail-accent);
    }

    .cashier-note-detail .btn-outline-warning {
      border-color: var(--bs-warning);
      background: var(--cashier-warning-soft);
      color: var(--bs-warning);
    }

    #note-payment-modal .modal-content,
    #note-refund-modal .modal-content {
      border: 1px solid var(--detail-border);
      background: var(--detail-card);
      color: var(--detail-text);
    }

    #note-payment-modal .modal-header,
    #note-payment-modal .modal-footer,
    #note-refund-modal .modal-header,
    #note-refund-modal .modal-footer {
      border-color: var(--detail-border);
      background: var(--detail-card);
    }

    #note-payment-modal .border.rounded,
    #note-refund-modal .border.rounded {
      border-color: var(--detail-border) !important;
      background: var(--detail-surface-subtle);
      color: var(--detail-text);
    }

    #note-payment-modal .badge,
    #note-refund-modal .badge {
      border-color: var(--detail-border) !important;
      background: var(--detail-surface-subtle) !important;
      color: var(--detail-text) !important;
    }

    #note-payment-modal .text-muted,
    #note-refund-modal .text-muted {
      color: var(--detail-muted) !important;
    }

    @media (max-width: 575.98px) {
      .cashier-note-detail {
        max-width: none;
      }

      .cashier-note-detail-header,
      .cashier-note-detail-body {
        padding-inline: .9rem;
      }
    }
  </style>

  <div class="cashier-note-detail">
    <div class="cashier-note-detail-shell">
      <details class="cashier-note-detail-step" open>
        <summary class="cashier-note-detail-header">
          <span class="cashier-note-detail-number">1</span>
          <div>
            <h4 class="cashier-note-detail-title">Info Nota</h4>
            <p class="cashier-note-detail-help">Identitas customer, tanggal, status, dan ringkasan pembayaran.</p>
          </div>
          <span class="cashier-note-detail-toggle" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
          </span>
        </summary>

        <div class="cashier-note-detail-body">
          <div class="ui-card-stack">
            @include('cashier.notes.partials.note-overview')
          </div>
        </div>
      </details>

      <details class="cashier-note-detail-step" open>
        <summary class="cashier-note-detail-header">
          <span class="cashier-note-detail-number">2</span>
          <div>
            <h4 class="cashier-note-detail-title">Rincian Nota</h4>
            <p class="cashier-note-detail-help">Daftar item, status, sisa tagihan, dan dampak refund per rincian.</p>
          </div>
          <span class="cashier-note-detail-toggle" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
          </span>
        </summary>

        <div class="cashier-note-detail-body">
          <div class="ui-card-stack">
            @include('cashier.notes.partials.note-rows-table')
            @include('cashier.notes.partials.billing-table')
          </div>
        </div>
      </details>

      <details class="cashier-note-detail-step" open>
        <summary class="cashier-note-detail-header">
          <span class="cashier-note-detail-number">3</span>
          <div>
            <h4 class="cashier-note-detail-title">Review &amp; Pembayaran</h4>
            <p class="cashier-note-detail-help">Lanjut edit, pembayaran, atau refund setelah rincian nota dicek.</p>
          </div>
          <span class="cashier-note-detail-toggle" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
          </span>
        </summary>

        <div class="cashier-note-detail-body">
          <div class="ui-card-stack">
            @include('cashier.notes.partials.payment-actions')
          </div>
        </div>
      </details>

      <details class="cashier-note-detail-step">
        <summary class="cashier-note-detail-header">
          <span class="cashier-note-detail-number">4</span>
          <div>
            <h4 class="cashier-note-detail-title">Riwayat Nota</h4>
            <p class="cashier-note-detail-help">Riwayat perubahan nota dan koreksi yang pernah dicatat.</p>
          </div>
          <span class="cashier-note-detail-toggle" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
          </span>
        </summary>

        <div class="cashier-note-detail-body">
          <div class="ui-card-stack">
            @include('cashier.notes.partials.note-revision-timeline', [
              'revision' => $note['revision_timeline'] ?? ['current' => [], 'baseline' => [], 'timeline' => []],
              'currentRevision' => ($note['revision_timeline']['current'] ?? []),
              'baselineRevision' => ($note['revision_timeline']['baseline'] ?? []),
              'timelineRevisions' => ($note['revision_timeline']['timeline'] ?? []),
            ])
            @include('cashier.notes.partials.correction-history')
          </div>
        </div>
      </details>
    </div>
  </div>

  @include('cashier.notes.partials.payment-modal')
  @include('cashier.notes.partials.refund-modal')
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/static/js/pages/cashier-note-payment.js') }}?v={{ config('app.asset_version') }}"></script>
<script src="{{ asset('assets/static/js/pages/cashier-note-refund.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
