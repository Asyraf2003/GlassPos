@extends('layouts.app')

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('back_url', $backUrl)

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/static/css/cashier-note-payment-timeline.css') }}?v={{ config('app.asset_version') }}">
<link rel="stylesheet" href="{{ asset('assets/static/css/note-detail-desktop-polish.css') }}?v={{ config('app.asset_version') }}">
@endpush

@section('content')
<section class="section note-detail-shell">
  @if (($noteDetailLayout ?? 'desktop') === 'desktop')
    <div class="note-detail-desktop">
      <section class="note-detail-desktop-info note-detail-surface">
        <div class="note-detail-section-heading">
          <h4>Info Nota</h4>
        </div>
        @include('shared.notes.partials.header-summary')
      </section>

      <div class="note-detail-desktop-main">
        <section class="note-detail-desktop-lines note-detail-surface">
          <div class="note-detail-section-heading">
            <h4>Rincian Nota</h4>
          </div>
          @include('shared.notes.partials.line-workspace')
        </section>

        <aside class="note-detail-desktop-payment note-detail-surface">
          <div class="note-detail-section-heading">
            <h4>Pembayaran</h4>
          </div>
          @include('shared.notes.partials.payment-summary-actions')
        </aside>
      </div>

      <section class="note-detail-desktop-history">
        <div class="note-detail-history-title">
          <h4>Riwayat Nota</h4>
        </div>
        @include('shared.notes.partials.history-panel')
      </section>
    </div>
  @else
    <div class="note-detail-mobile-stack note-detail-handset">
      <div class="note-detail-mobile-stack-list">
        <details class="note-detail-mobile-step" open>
          <summary class="note-detail-mobile-summary">
            <span class="note-detail-mobile-number">1</span>
            <div class="note-detail-mobile-heading flex-grow-1">
              <h4 class="note-detail-mobile-title">Info Nota</h4>
              <p class="note-detail-mobile-help">Identitas pelanggan, tanggal, dan status nota.</p>
            </div>
            <span class="note-detail-mobile-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
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
              <p class="note-detail-mobile-help">Daftar rincian nota dan status setiap rincian.</p>
            </div>
            <span class="note-detail-mobile-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
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
              <p class="note-detail-mobile-help">Status dan aksi pembayaran nota.</p>
            </div>
            <span class="note-detail-mobile-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
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
              <p class="note-detail-mobile-help">Perubahan, pembayaran, dan pengembalian.</p>
            </div>
            <span class="note-detail-mobile-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
          </summary>
          <div class="note-detail-mobile-body">
            @include('shared.notes.partials.history-panel')
          </div>
        </details>
      </div>
    </div>
  @endif

  @include('cashier.notes.partials.payment-modal')
  @include('cashier.notes.partials.refund-modal')
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/static/js/pages/cashier-note-payment.js') }}?v={{ config('app.asset_version') }}"></script>
<script src="{{ asset('assets/static/js/pages/cashier-note-refund.js') }}?v={{ config('app.asset_version') }}"></script>
<script src="{{ asset('assets/static/js/pages/note-line-actions.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush