@extends('layouts.app')

@section('title', 'Dashboard Kasir')
@section('heading', 'Dashboard Kasir')

@section('content')
<section class="section">
    <style>
        .cashier-home {
            max-width: 720px;
            margin: 0 auto;
        }

        .cashier-home-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .cashier-home-card {
            display: flex;
            min-height: 9.5rem;
            padding: 1rem;
            border: 1px solid var(--cashier-border);
            border-radius: 1rem;
            background: var(--cashier-surface);
            color: var(--cashier-text);
            text-decoration: none;
            box-shadow: var(--cashier-shadow);
        }

        .cashier-home-card:focus,
        .cashier-home-card:hover {
            color: var(--cashier-text);
            border-color: var(--cashier-accent-border);
        }

        .cashier-home-card-inner {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 100%;
            gap: 1rem;
        }

        .cashier-home-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .cashier-home-desc {
            margin: .25rem 0 0;
            color: var(--cashier-muted);
            font-size: .9rem;
            line-height: 1.5;
        }

        .cashier-home-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 2.75rem;
            border-radius: .85rem;
            background: var(--cashier-accent-soft);
            color: var(--cashier-accent);
            font-weight: 800;
        }

        .cashier-home-install-card {
            cursor: default;
        }

        .cashier-home-install-button {
            border: 0;
            cursor: pointer;
        }

        .cashier-home-install-button:disabled {
            cursor: not-allowed;
            opacity: .7;
        }

        .cashier-home-status {
            margin: .5rem 0 0;
            color: var(--cashier-muted);
            font-size: .8rem;
            line-height: 1.4;
        }

        @media (max-width: 575.98px) {
            .cashier-home {
                max-width: none;
            }

            .cashier-home-grid {
                gap: .75rem;
            }

            .cashier-home-card {
                min-height: 10rem;
                padding: .85rem;
            }
        }
    </style>

    <div class="cashier-home">

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
                    <div class="cashier-home-card cashier-home-install-card" data-pwa-install-card>
                <span class="cashier-home-card-inner">
                    <span>
                        <span class="cashier-home-title">Download App PWA</span>
                        <span class="cashier-home-desc d-block">Pasang HyperPOS Kasir ke layar utama.</span>
                        <span class="cashier-home-status d-block" data-pwa-install-status>Gunakan Chrome/Edge mobile, lalu tekan tombol install.</span>
                    </span>
                    <button type="button" class="cashier-home-button cashier-home-install-button" data-pwa-install-button>
                        Download App
                    </button>
                </span>
            </div>

        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/static/js/pages/cashier-dashboard/pwa-install.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
