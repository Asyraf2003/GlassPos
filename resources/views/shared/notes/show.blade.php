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
    <div class="note-detail-desktop note-detail-desktop-grid">
      <details class="note-detail-desktop-panel note-detail-desktop-panel--info" data-note-desktop-panel="info" open>
        <summary class="note-detail-desktop-summary">
          <span>Info Nota</span>
          <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </summary>
        <div class="note-detail-desktop-body">
          @include('shared.notes.partials.header-summary')
        </div>
      </details>

      <details class="note-detail-desktop-panel note-detail-desktop-panel--lines" data-note-desktop-panel="lines" open>
        <summary class="note-detail-desktop-summary">
          <span>Rincian Nota</span>
          <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </summary>
        <div class="note-detail-desktop-body">
          @include('shared.notes.partials.line-workspace')
        </div>
      </details>

      <details class="note-detail-desktop-panel note-detail-desktop-panel--payment" data-note-desktop-panel="payment" open>
        <summary class="note-detail-desktop-summary">
          <span>Pembayaran</span>
          <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </summary>
        <div class="note-detail-desktop-body">
          @include('shared.notes.partials.payment-summary-actions')
        </div>
      </details>

      <details class="note-detail-desktop-panel note-detail-desktop-panel--history-main" data-note-desktop-panel="history-main" open>
        <summary class="note-detail-desktop-summary">
          <span>Riwayat Nota</span>
          <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </summary>
        <div class="note-detail-desktop-body note-detail-desktop-history-body">
          @include('shared.notes.partials.history-operational')
        </div>
      </details>

      <details class="note-detail-desktop-panel note-detail-desktop-panel--history-finance" data-note-desktop-panel="history-finance" open>
        <summary class="note-detail-desktop-summary">
          <span>Riwayat Finansial</span>
          <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </summary>
        <div class="note-detail-desktop-body note-detail-desktop-history-body">
          @include('shared.notes.partials.history-financial')
        </div>
      </details>
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