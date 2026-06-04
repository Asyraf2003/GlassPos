@extends('layouts.app')

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('back_url', $backUrl)

@section('content')
<section class="section">
  <style>
    .section:has(.note-detail-mobile-stack) {
      --note-detail-card: var(--cashier-surface);
      --note-detail-surface-subtle: var(--cashier-surface-subtle);
      --note-detail-border: var(--cashier-border);
      --note-detail-muted: var(--cashier-muted);
      --note-detail-text: var(--cashier-text);
      --note-detail-accent: var(--cashier-accent);
      --note-detail-accent-soft: var(--cashier-accent-soft);
      --note-detail-accent-border: var(--cashier-accent-border);
      background: var(--cashier-page-bg);
      padding-block: 1rem 2rem;
    }

    .note-detail-mobile-stack {
      max-width: 720px;
      margin: 0 auto;
    }

    .note-detail-mobile-stack-list {
      display: grid;
      gap: .85rem;
    }

    .note-detail-mobile-step {
      border: 1px solid var(--note-detail-border);
      border-top: .45rem solid var(--note-detail-accent);
      border-radius: .5rem;
      background: var(--note-detail-card);
      overflow: visible;
      box-shadow: none;
    }

    .note-detail-mobile-summary {
      display: grid !important;
      grid-template-columns: 2.25rem minmax(0, 1fr) 2.25rem;
      align-items: flex-start;
      column-gap: .85rem;
      row-gap: 0;
      padding: 1rem 1rem .75rem;
      cursor: pointer;
      list-style: none;
      border-bottom: 1px solid var(--note-detail-border);
    }

    .note-detail-mobile-summary::-webkit-details-marker {
      display: none;
    }

    .note-detail-mobile-heading {
      grid-column: 2;
      grid-row: 1;
      min-width: 0;
    }

    .note-detail-mobile-summary > .flex-grow-1 {
      min-width: 0;
    }

    .note-detail-mobile-number {
      grid-column: 1;
      grid-row: 1;
      width: 2.25rem;
      height: 2.25rem;
      flex: 0 0 2.25rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      color: var(--note-detail-accent);
      background: var(--note-detail-accent-soft);
      border: 1px solid var(--note-detail-accent-border);
      font-weight: 800;
    }

    .note-detail-mobile-title {
      margin: 0;
      color: var(--note-detail-text);
      font-size: 1rem;
      font-weight: 800;
      line-height: 1.35;
    }

    .note-detail-mobile-help {
      margin: .18rem 0 0;
      color: var(--note-detail-muted);
      font-size: .9rem;
      line-height: 1.55;
    }

    .note-detail-mobile-toggle {
      grid-column: 3;
      grid-row: 1;
      justify-self: end;
      width: 2.25rem;
      height: 2.25rem;
      flex: 0 0 2.25rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-left: 0;
      border-radius: 50%;
      color: var(--note-detail-accent);
      transition: transform .15s ease;
    }

    .note-detail-mobile-step[open] .note-detail-mobile-toggle {
      transform: rotate(180deg);
    }

    .note-detail-mobile-body {
      padding: 1rem;
    }

    .note-detail-readonly-grid {
      display: grid;
      gap: 1rem;
    }

    .note-detail-readonly-field {
      display: grid;
      gap: .45rem;
    }

    .note-detail-readonly-label {
      margin: 0;
      color: var(--note-detail-text);
      font-weight: 700;
      line-height: 1.35;
    }

    .note-detail-readonly-control {
      min-height: 2.75rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .75rem;
      width: 100%;
      border: 1px solid var(--note-detail-border);
      border-radius: .25rem;
      background: transparent;
      color: var(--note-detail-text);
      padding: .68rem .75rem;
      line-height: 1.35;
      overflow-wrap: anywhere;
    }

    .note-detail-readonly-control .badge {
      flex: 0 0 auto;
    }

    .note-detail-mobile-stack .card {
      border: 0;
      border-radius: 0;
      background: transparent;
      color: var(--note-detail-text);
      box-shadow: none !important;
    }

    .note-detail-mobile-stack .card-header {
      border-bottom: 1px solid var(--note-detail-border);
      background: transparent;
      padding: 0 0 .85rem;
      margin-bottom: .85rem;
    }

    .note-detail-mobile-stack .card-body {
      padding: 0;
    }

    .note-detail-mobile-stack .card-title {
      color: var(--note-detail-text);
      font-size: 1rem;
      font-weight: 800;
    }

    .note-detail-mobile-stack .ui-card-stack {
      display: grid;
      gap: .85rem;
    }

    .note-detail-mobile-stack .btn,
    .note-detail-mobile-stack a.btn,
    .note-detail-mobile-stack button.btn {
      width: 100%;
      min-height: 2.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: .35rem;
      font-weight: 800;
      text-align: center;
    }

    .note-detail-mobile-stack .badge {
      border-color: var(--note-detail-border) !important;
      border-radius: 999px;
      background: var(--note-detail-surface-subtle) !important;
      color: var(--note-detail-text) !important;
      font-weight: 700;
    }

    .note-detail-mobile-stack .border.rounded,
    .note-detail-mobile-stack .bg-body {
      border-color: var(--note-detail-border) !important;
      background: var(--note-detail-surface-subtle) !important;
      color: var(--note-detail-text) !important;
    }

    .note-detail-mobile-stack .text-muted {
      color: var(--note-detail-muted) !important;
    }

    .note-detail-mobile-stack .table-responsive {
      border: 1px solid var(--note-detail-border);
      border-radius: .5rem;
      -webkit-overflow-scrolling: touch;
    }

    .note-detail-mobile-stack .table {
      --bs-table-striped-bg: var(--note-detail-surface-subtle);
      --bs-table-bg: var(--note-detail-card);
      --bs-table-color: var(--note-detail-text);
      --bs-table-border-color: var(--note-detail-border);
      margin-bottom: 0;
    }

    .note-detail-mobile-stack .table thead th,
    .note-detail-mobile-stack .table tbody td {
      border-color: var(--note-detail-border);
    }

    .note-detail-mobile-stack .form-control,
    .note-detail-mobile-stack .form-select,
    #note-payment-modal .form-control,
    #note-payment-modal .form-select,
    #note-refund-modal .form-control,
    #note-refund-modal .form-select {
      border-color: var(--note-detail-border);
      background: var(--note-detail-card);
      color: var(--note-detail-text);
    }

    #note-payment-modal .modal-content,
    #note-refund-modal .modal-content {
      border: 1px solid var(--note-detail-border);
      background: var(--note-detail-card);
      color: var(--note-detail-text);
    }

    #note-payment-modal .modal-header,
    #note-payment-modal .modal-footer,
    #note-refund-modal .modal-header,
    #note-refund-modal .modal-footer {
      border-color: var(--note-detail-border);
      background: var(--note-detail-card);
    }

    #note-payment-modal .border.rounded,
    #note-refund-modal .border.rounded {
      border-color: var(--note-detail-border) !important;
      background: var(--note-detail-surface-subtle);
      color: var(--note-detail-text);
    }

    #note-payment-modal .badge,
    #note-refund-modal .badge {
      border-color: var(--note-detail-border) !important;
      background: var(--note-detail-surface-subtle) !important;
      color: var(--note-detail-text) !important;
    }

    #note-payment-modal .text-muted,
    #note-refund-modal .text-muted {
      color: var(--note-detail-muted) !important;
    }

    @media (max-width: 575.98px) {
      .note-detail-mobile-stack {
        max-width: none;
      }

      .note-detail-mobile-summary,
      .note-detail-mobile-body {
        padding-inline: .9rem;
      }
    }
  </style>

  <div class="note-detail-mobile-stack">
    <div class="note-detail-mobile-stack-list">
      <details class="note-detail-mobile-step" open>
        <summary class="note-detail-mobile-summary">
          <span class="note-detail-mobile-number">1</span>
          <div class="note-detail-mobile-heading flex-grow-1">
            <h4 class="note-detail-mobile-title">Info Nota</h4>
            <p class="note-detail-mobile-help">Identitas customer, tanggal, dan status nota.</p>
          </div>
          <span class="note-detail-mobile-toggle" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
          </span>
        </summary>
        <div class="note-detail-mobile-body">
          @include('shared.notes.partials.header-summary')
        </div>
      </details>

      <details class="note-detail-mobile-step" open>
        <summary class="note-detail-mobile-summary">
          <span class="note-detail-mobile-number">2</span>
          <div class="note-detail-mobile-heading flex-grow-1">
            <h4 class="note-detail-mobile-title">Rincian Nota</h4>
            <p class="note-detail-mobile-help">Daftar rincian nota dan status setiap line.</p>
          </div>
          <span class="note-detail-mobile-toggle" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
          </span>
        </summary>
        <div class="note-detail-mobile-body">
          @include('shared.notes.partials.line-workspace')
        </div>
      </details>

      <details class="note-detail-mobile-step" open>
        <summary class="note-detail-mobile-summary">
          <span class="note-detail-mobile-number">3</span>
          <div class="note-detail-mobile-heading flex-grow-1">
            <h4 class="note-detail-mobile-title">Review &amp; Pembayaran</h4>
            <p class="note-detail-mobile-help">Edit, pembayaran, dan refund setelah rincian nota dicek.</p>
          </div>
          <span class="note-detail-mobile-toggle" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
          </span>
        </summary>
        <div class="note-detail-mobile-body">
          @include('shared.notes.partials.payment-summary-actions')
        </div>
      </details>

      <details class="note-detail-mobile-step">
        <summary class="note-detail-mobile-summary">
          <span class="note-detail-mobile-number">4</span>
          <div class="note-detail-mobile-heading flex-grow-1">
            <h4 class="note-detail-mobile-title">Riwayat Nota</h4>
            <p class="note-detail-mobile-help">Riwayat perubahan nota terbaru.</p>
          </div>
          <span class="note-detail-mobile-toggle" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
          </span>
        </summary>
        <div class="note-detail-mobile-body">
          @include('shared.notes.partials.versioning-compact', [
            'currentRevision' => ($note['revision_timeline']['current'] ?? []),
            'timelineRevisions' => array_slice(($note['revision_timeline']['timeline'] ?? []), 0, 3),
            'revisionCount' => count($note['revision_timeline']['timeline'] ?? []),
          ])
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
<script src="{{ asset('assets/static/js/pages/note-line-actions.js') }}?v={{ config('app.asset_version') }}"></script>
<script src="{{ asset('assets/static/js/pages/note-surplus-refund-due.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
