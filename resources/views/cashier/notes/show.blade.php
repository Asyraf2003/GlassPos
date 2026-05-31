@extends('layouts.app')

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('back_url', route('cashier.notes.index'))

@section('content')
<section class="section">
  <div class="ui-page-intro">
    <div class="small text-muted text-uppercase fw-semibold">Workspace Nota Kasir</div>
    <h4 class="ui-page-intro-title">Detail Nota Root + Revision</h4>
    <p class="ui-page-intro-subtitle">Line domain tetap dibaca sebagai isi nota. Pembayaran bergerak lewat billing projection, refund tetap selection-first, dan riwayat perubahan nota sekarang dibaca dari revision chain nyata pada root note yang sama.</p>
  </div>

  <div class="row g-4 align-items-start">
    <div class="col-12 col-xl-8">
      <div class="ui-card-stack">
        @include('cashier.notes.partials.note-overview')
        @include('cashier.notes.partials.note-revision-timeline', [
          'revision' => $note['revision_timeline'] ?? ['current' => [], 'baseline' => [], 'timeline' => []],
          'currentRevision' => ($note['revision_timeline']['current'] ?? []),
          'baselineRevision' => ($note['revision_timeline']['baseline'] ?? []),
          'timelineRevisions' => ($note['revision_timeline']['timeline'] ?? []),
        ])
        @include('cashier.notes.partials.correction-history')
        @include('cashier.notes.partials.note-rows-table')
        @include('cashier.notes.partials.billing-table')
      </div>
    </div>

    <div class="col-12 col-xl-4">
      <div class="ui-card-stack">
        @include('cashier.notes.partials.payment-actions')

        @if ($note['can_show_workspace_panel'] ?? false)
          @include('cashier.notes.partials.add-rows-form')
        @endif
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
