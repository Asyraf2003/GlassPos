@extends('layouts.app')

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('back_url', $backUrl)

@section('content')
<section class="section">
  <style>
    .section:has(.note-detail-mobile-stack) {
      background: #f0ebf8;
      padding-block: 1rem 2rem;
    }

    .note-detail-mobile-stack {
      --note-detail-card: #ffffff;
      --note-detail-border: #dadce0;
      --note-detail-muted: #5f6368;
      --note-detail-text: #202124;
      --note-detail-accent: #673ab7;
      --note-detail-accent-soft: #ede7f6;
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
      overflow: hidden;
    }

    .note-detail-mobile-summary {
      display: flex;
      align-items: flex-start;
      gap: .85rem;
      padding: 1rem 1rem .75rem;
      cursor: pointer;
      list-style: none;
      border-bottom: 1px solid #eceff1;
    }

    .note-detail-mobile-summary::-webkit-details-marker {
      display: none;
    }

    .note-detail-mobile-number {
      width: 2.25rem;
      height: 2.25rem;
      flex: 0 0 2.25rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      color: var(--note-detail-accent);
      background: var(--note-detail-accent-soft);
      border: 1px solid #d1c4e9;
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
      width: 2.25rem;
      height: 2.25rem;
      flex: 0 0 2.25rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-left: auto;
      color: var(--note-detail-accent);
      transition: transform .15s ease;
    }

    .note-detail-mobile-step[open] .note-detail-mobile-toggle {
      transform: rotate(180deg);
    }

    .note-detail-mobile-body {
      padding: 1rem;
    }

    .note-detail-mobile-stack .card {
      border: 1px solid var(--note-detail-border);
      border-radius: .5rem;
      box-shadow: none !important;
    }

    .note-detail-mobile-stack .card-header {
      border-bottom: 1px solid #eceff1;
      background: #fff;
      padding: 1rem;
    }

    .note-detail-mobile-stack .card-body {
      padding: 1rem;
    }

    .note-detail-mobile-stack .card-title {
      color: var(--note-detail-text);
      font-size: 1rem;
      font-weight: 800;
    }

    .note-detail-mobile-stack .ui-card-stack {
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
      border-color: #dadce0 !important;
      border-radius: 999px;
      background: #fff !important;
      color: #3c4043 !important;
      font-weight: 700;
    }

    .note-detail-mobile-stack .table-responsive {
      border: 1px solid #dadce0;
      border-radius: .5rem;
      -webkit-overflow-scrolling: touch;
    }

    .note-detail-mobile-stack .table {
      --bs-table-striped-bg: #fff;
      --bs-table-bg: #fff;
      margin-bottom: 0;
    }

    .note-detail-mobile-stack .table thead th,
    .note-detail-mobile-stack .table tbody td {
      border-color: #eceff1;
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
          <div>
            <h4 class="note-detail-mobile-title">Header</h4>
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
          <div>
            <h4 class="note-detail-mobile-title">List Line</h4>
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
          <div>
            <h4 class="note-detail-mobile-title">Status Aksi</h4>
            <p class="note-detail-mobile-help">Edit, pembayaran, dan refund setelah line dicek.</p>
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
          <div>
            <h4 class="note-detail-mobile-title">Versioning &amp; Revisi</h4>
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
