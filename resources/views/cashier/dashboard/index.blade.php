@extends('layouts.app')

@section('title', 'Dashboard Kasir')
@section('heading', 'Dashboard Kasir')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/static/css/cashier-dashboard.css') }}?v={{ config('app.asset_version') }}">
@endpush

@section('content')
<section class="section">
    <div
        class="cashier-home"
        data-cashier-dashboard-device="{{ ($isHandset ?? false) ? 'handset' : 'desktop' }}"
    >
        <div class="cashier-home-grid">
            <a href="{{ route('cashier.notes.workspace.create') }}" class="cashier-home-card">
                <span class="cashier-home-card-inner">
                    <span>
                        <span class="cashier-home-title">Buat Nota</span>
                        <span class="cashier-home-desc d-block">Mulai transaksi baru.</span>
                    </span>
                    <span class="cashier-home-button">Buka</span>
                </span>
            </a>

            <a href="{{ route('cashier.notes.index') }}" class="cashier-home-card">
                <span class="cashier-home-card-inner">
                    <span>
                        <span class="cashier-home-title">Riwayat</span>
                        <span class="cashier-home-desc d-block">Cari dan lanjutkan nota.</span>
                    </span>
                    <span class="cashier-home-button">Buka</span>
                </span>
            </a>

            <a href="{{ route('cashier.products.search') }}" class="cashier-home-card">
                <span class="cashier-home-card-inner">
                    <span>
                        <span class="cashier-home-title">Cari Barang</span>
                        <span class="cashier-home-desc d-block">Cek harga dan stok.</span>
                    </span>
                    <span class="cashier-home-button">Buka</span>
                </span>
            </a>

            <a href="{{ route('cashier.account.preferences') }}" class="cashier-home-card">
                <span class="cashier-home-card-inner">
                    <span>
                        <span class="cashier-home-title">Preferensi Akun</span>
                        <span class="cashier-home-desc d-block">Lihat akun dan keluar.</span>
                    </span>
                    <span class="cashier-home-button">Buka</span>
                </span>
            </a>

            @if ($isHandset ?? false)
                <div class="cashier-home-card cashier-home-install-card" data-pwa-install-card>
                    <span class="cashier-home-card-inner">
                        <span>
                            <span class="cashier-home-title">Download App PWA</span>
                            <span class="cashier-home-status d-block" data-pwa-install-status>Menunggu dukungan install dari browser.</span>
                        </span>
                        <button type="button" class="cashier-home-button cashier-home-install-button" data-pwa-install-button disabled>
                            Download App
                        </button>
                    </span>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection

@if ($isHandset ?? false)
    @push('scripts')
    <script src="{{ asset('assets/static/js/pages/cashier-dashboard/pwa-install.js') }}?v={{ config('app.asset_version') }}"></script>
    @endpush
@endif
