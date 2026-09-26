@extends('layouts.app')

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('back_url', $backUrl)

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/static/css/cashier-note-payment-timeline.css') }}?v={{ config('app.asset_version') }}">
<link rel="stylesheet" href="{{ asset('assets/static/css/note-detail-desktop-polish.css') }}?v={{ config('app.asset_version') }}">
<link rel="stylesheet" href="{{ asset('assets/static/css/note-detail-canonical.css') }}?v={{ config('app.asset_version') }}">
@endpush

@section('content')
<section class="section note-detail-shell">
  @if (($noteDetailLayout ?? 'desktop') === 'desktop')
    <div class="note-detail-desktop note-detail-desktop-columns">
      <div class="note-detail-desktop-stack note-detail-desktop-stack--main" data-note-desktop-stack="main">
        <details class="note-detail-desktop-panel" data-note-desktop-panel="info">
          <summary class="note-detail-desktop-summary">
            <span class="note-detail-desktop-title-group">
              <h4>Info Nota</h4>
              @include('shared.notes.partials.status-badge', [
                'label' => $note['operational_status'] ?? '-',
              ])
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
          </summary>
          <div class="note-detail-desktop-body">
            @include('shared.notes.partials.header-summary')
          </div>
        </details>

        <details class="note-detail-desktop-panel" data-note-desktop-panel="lines" open>
          <summary class="note-detail-desktop-summary">
            <span class="note-detail-desktop-title-group">
              <h4>Rincian Nota</h4>
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
          </summary>
          <div class="note-detail-desktop-body">
            @include('shared.notes.partials.line-workspace')
          </div>
        </details>

        <details class="note-detail-desktop-panel" data-note-desktop-panel="history-main">
          <summary class="note-detail-desktop-summary">
            <span class="note-detail-desktop-title-group">
              <h4>Riwayat Nota</h4>
              @include('shared.notes.partials.status-badge', [
                'label' => $note['operational_status'] ?? '-',
              ])
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
          </summary>
          <div class="note-detail-desktop-body note-detail-desktop-history-body">
            @include('shared.notes.partials.history-operational')
          </div>
        </details>
      </div>

      <aside class="note-detail-desktop-stack note-detail-desktop-stack--finance" data-note-desktop-stack="finance">
        <details class="note-detail-desktop-panel" data-note-desktop-panel="payment" open>
          <summary class="note-detail-desktop-summary">
            <span class="note-detail-desktop-title-group">
              <h4>Pembayaran</h4>
              @include('shared.notes.partials.status-badge', [
                'label' => $note['payment_status_label'] ?? '-',
              ])
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
          </summary>
          <div class="note-detail-desktop-body">
            @include('shared.notes.partials.payment-summary-actions')
          </div>
        </details>

        <details class="note-detail-desktop-panel" data-note-desktop-panel="history-finance">
          <summary class="note-detail-desktop-summary">
            <span class="note-detail-desktop-title-group">
              <h4>Riwayat Finansial</h4>
            </span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
          </summary>
          <div class="note-detail-desktop-body note-detail-desktop-history-body">
            @include('shared.notes.partials.history-financial')
          </div>
        </details>

        @if (isset($lifecycle))
          <details class="note-detail-desktop-panel" data-note-desktop-panel="lifecycle">
            <summary class="note-detail-desktop-summary">
              <span class="note-detail-desktop-title-group">
                <h4>{{ ($lifecycle['mode'] ?? null) === 'restore' ? 'Pulihkan' : 'Batalkan Transaksi' }}</h4>
              </span>
              <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </summary>
            <div class="note-detail-desktop-body">
              @include('shared.notes.partials.lifecycle-actions')
            </div>
          </details>
        @endif
      </aside>
    </div>
  @else
    <div class="note-detail-mobile-stack note-detail-handset">
      <div class="note-detail-mobile-stack-list">
        <details class="note-detail-mobile-step">
          <summary class="note-detail-mobile-summary">
            <span class="note-detail-mobile-number">1</span>
            <div class="note-detail-mobile-heading flex-grow-1">
              <span class="note-detail-mobile-title-group">
                <h4 class="note-detail-mobile-title">Info Nota</h4>
                @include('shared.notes.partials.status-badge', [
                  'label' => $note['operational_status'] ?? '-',
                ])
              </span>
            </div>
            <span class="note-detail-mobile-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
          </summary>
          <div class="note-detail-mobile-body">@include('shared.notes.partials.header-summary')</div>
        </details>

        <details class="note-detail-mobile-step" open>
          <summary class="note-detail-mobile-summary">
            <span class="note-detail-mobile-number">2</span>
            <div class="note-detail-mobile-heading flex-grow-1">
              <span class="note-detail-mobile-title-group">
                <h4 class="note-detail-mobile-title">Rincian Nota</h4>
              </span>
            </div>
            <span class="note-detail-mobile-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
          </summary>
          <div class="note-detail-mobile-body">@include('shared.notes.partials.line-workspace')</div>
        </details>

        <details class="note-detail-mobile-step" open>
          <summary class="note-detail-mobile-summary">
            <span class="note-detail-mobile-number">3</span>
            <div class="note-detail-mobile-heading flex-grow-1">
              <span class="note-detail-mobile-title-group">
                <h4 class="note-detail-mobile-title">Pembayaran</h4>
                @include('shared.notes.partials.status-badge', [
                  'label' => $note['payment_status_label'] ?? '-',
                ])
              </span>
            </div>
            <span class="note-detail-mobile-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
          </summary>
          <div class="note-detail-mobile-body">@include('shared.notes.partials.payment-summary-actions')</div>
        </details>

        <details class="note-detail-mobile-step">
          <summary class="note-detail-mobile-summary">
            <span class="note-detail-mobile-number">4</span>
            <div class="note-detail-mobile-heading flex-grow-1">
              <span class="note-detail-mobile-title-group">
                <h4 class="note-detail-mobile-title">Riwayat Nota</h4>
                @include('shared.notes.partials.status-badge', [
                  'label' => $note['operational_status'] ?? '-',
                ])
              </span>
            </div>
            <span class="note-detail-mobile-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
          </summary>
          <div class="note-detail-mobile-body">@include('shared.notes.partials.history-operational')</div>
        </details>

        <details class="note-detail-mobile-step">
          <summary class="note-detail-mobile-summary">
            <span class="note-detail-mobile-number">5</span>
            <div class="note-detail-mobile-heading flex-grow-1">
              <span class="note-detail-mobile-title-group">
                <h4 class="note-detail-mobile-title">Riwayat Finansial</h4>
              </span>
            </div>
            <span class="note-detail-mobile-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
          </summary>
          <div class="note-detail-mobile-body">@include('shared.notes.partials.history-financial')</div>
        </details>

        @if (isset($lifecycle))
          <details class="note-detail-mobile-step">
            <summary class="note-detail-mobile-summary">
              <span class="note-detail-mobile-number">6</span>
              <div class="note-detail-mobile-heading flex-grow-1">
                <span class="note-detail-mobile-title-group">
                  <h4 class="note-detail-mobile-title">{{ ($lifecycle['mode'] ?? null) === 'restore' ? 'Pulihkan' : 'Batalkan Transaksi' }}</h4>
                </span>
              </div>
              <span class="note-detail-mobile-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
            </summary>
            <div class="note-detail-mobile-body">@include('shared.notes.partials.lifecycle-actions')</div>
          </details>
        @endif
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
