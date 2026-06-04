@extends('layouts.app')

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('back_url', route('cashier.notes.index'))

@section('content')
<section class="section">
  <style>
    .section:has(.cashier-note-detail) {
      background: #f0ebf8;
      padding-block: 1rem 2rem;
    }

    .cashier-note-detail {
      --detail-card: #ffffff;
      --detail-border: #dadce0;
      --detail-muted: #5f6368;
      --detail-text: #202124;
      --detail-accent: #673ab7;
      --detail-accent-soft: #ede7f6;
      --detail-accent-border: #d1c4e9;
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
      border-bottom: 1px solid rgba(15, 23, 42, .07);
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

    .cashier-note-detail-body {
      padding: 1rem;
    }

    .cashier-note-detail .ui-card-stack {
      gap: .85rem;
    }

    .cashier-note-detail .card {
      border: 1px solid var(--detail-border);
      border-radius: .5rem;
      box-shadow: none !important;
      overflow: hidden;
    }

    .cashier-note-detail .card-header {
      border-bottom: 1px solid #eceff1;
      background: #fff;
      padding: 1rem;
    }

    .cashier-note-detail .card-body {
      padding: 1rem;
    }

    .cashier-note-detail .card-title {
      color: var(--detail-text);
      font-size: 1rem;
      font-weight: 800;
      line-height: 1.35;
    }

    .cashier-note-detail .badge {
      border-color: #dadce0 !important;
      border-radius: 999px;
      background: #fff !important;
      color: #3c4043 !important;
      font-weight: 700;
    }

    .cashier-note-detail .border.rounded,
    .cashier-note-detail .bg-light {
      border-color: #dadce0 !important;
      border-radius: .5rem !important;
      background: #fff !important;
    }

    .cashier-note-detail .ui-key-value {
      border-bottom: 1px solid #eceff1 !important;
      padding-block: .85rem !important;
    }

    .cashier-note-detail .ui-key-value small,
    .cashier-note-detail .text-muted {
      color: var(--detail-muted) !important;
    }

    .cashier-note-detail .table-responsive {
      border: 1px solid #dadce0;
      border-radius: .5rem;
      -webkit-overflow-scrolling: touch;
    }

    .cashier-note-detail .table {
      margin-bottom: 0;
      --bs-table-striped-bg: #fff;
      --bs-table-bg: #fff;
    }

    .cashier-note-detail .table thead th {
      border-bottom: 1px solid #dadce0;
      background: #fff;
      color: var(--detail-muted);
      font-size: .78rem;
      font-weight: 800;
      text-transform: none;
      white-space: nowrap;
    }

    .cashier-note-detail .table tbody td {
      border-color: #eceff1;
      vertical-align: top;
    }

    .cashier-note-detail .btn {
      min-height: 2.75rem;
      border-radius: .35rem;
      font-weight: 800;
    }

    .cashier-note-detail .btn-primary {
      border-color: var(--detail-accent);
      background: var(--detail-accent);
      color: #fff;
    }

    .cashier-note-detail .btn-primary:hover,
    .cashier-note-detail .btn-primary:focus {
      border-color: #512da8;
      background: #512da8;
      color: #fff;
    }

    .cashier-note-detail .btn-outline-secondary,
    .cashier-note-detail .btn-light-secondary,
    .cashier-note-detail .btn-light-primary {
      border-color: #dadce0;
      background: #fff;
      color: var(--detail-accent);
    }

    .cashier-note-detail .btn-outline-warning {
      border-color: #fbbc04;
      background: #fff;
      color: #8a5d00;
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
    <div class="ui-page-intro">
      <div class="small text-muted text-uppercase fw-semibold">Workspace Nota Kasir</div>
      <h4 class="ui-page-intro-title">Detail Nota</h4>
      <p class="ui-page-intro-subtitle">Baca info nota, rincian, lalu lanjutkan aksi pembayaran atau refund.</p>
    </div>

    <div class="cashier-note-detail-shell">
      <div class="cashier-note-detail-step">
        <div class="cashier-note-detail-header">
          <span class="cashier-note-detail-number">1</span>
          <div>
            <h5 class="cashier-note-detail-title">Info Nota</h5>
            <p class="cashier-note-detail-help">Identitas customer, tanggal, status, dan ringkasan pembayaran.</p>
          </div>
        </div>

        <div class="cashier-note-detail-body">
          <div class="ui-card-stack">
            @include('cashier.notes.partials.note-overview')
            @include('cashier.notes.partials.note-revision-timeline', [
              'revision' => $note['revision_timeline'] ?? ['current' => [], 'baseline' => [], 'timeline' => []],
              'currentRevision' => ($note['revision_timeline']['current'] ?? []),
              'baselineRevision' => ($note['revision_timeline']['baseline'] ?? []),
              'timelineRevisions' => ($note['revision_timeline']['timeline'] ?? []),
            ])
            @include('cashier.notes.partials.correction-history')
          </div>
        </div>
      </div>

      <div class="cashier-note-detail-step">
        <div class="cashier-note-detail-header">
          <span class="cashier-note-detail-number">2</span>
          <div>
            <h5 class="cashier-note-detail-title">Rincian Nota</h5>
            <p class="cashier-note-detail-help">Daftar item, status, sisa tagihan, dan dampak refund per rincian.</p>
          </div>
        </div>

        <div class="cashier-note-detail-body">
          <div class="ui-card-stack">
            @include('cashier.notes.partials.note-rows-table')
            @include('cashier.notes.partials.billing-table')
          </div>
        </div>
      </div>

      <div class="cashier-note-detail-step">
        <div class="cashier-note-detail-header">
          <span class="cashier-note-detail-number">3</span>
          <div>
            <h5 class="cashier-note-detail-title">Review & Tindakan</h5>
            <p class="cashier-note-detail-help">Lanjut edit atau refund setelah rincian nota dicek.</p>
          </div>
        </div>

        <div class="cashier-note-detail-body">
          <div class="ui-card-stack">
            @include('cashier.notes.partials.payment-actions')

            @if ($note['can_show_workspace_panel'] ?? false)
              @include('cashier.notes.partials.add-rows-form')
            @endif
          </div>
        </div>
      </div>
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
