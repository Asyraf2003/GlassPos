@extends('layouts.app')

@section('title', 'Dashboard Kasir')
@section('heading', 'Dashboard Kasir')

@section('content')
<section class="section">
    <style>
        .cashier-dashboard {
            --cashier-radius-xl: 1.6rem;
            --cashier-radius-lg: 1.25rem;
            --cashier-radius-md: 1rem;
            --cashier-radius-sm: .85rem;
            --cashier-shadow: 0 1rem 2.2rem rgba(15, 23, 42, .08);
            --cashier-shadow-soft: 0 .5rem 1.2rem rgba(15, 23, 42, .05);
            --cashier-shadow-hover: 0 1.25rem 2.5rem rgba(15, 23, 42, .12);
            --cashier-primary-soft: rgba(var(--bs-primary-rgb), .10);
            --cashier-primary-soft-2: rgba(var(--bs-primary-rgb), .16);
            --cashier-primary-soft-3: rgba(var(--bs-primary-rgb), .22);
            --cashier-primary-border: rgba(var(--bs-primary-rgb), .24);
            --cashier-muted-bg: var(--bs-tertiary-bg, var(--bs-secondary-bg));
            --cashier-card-bg: var(--bs-body-bg);
            --cashier-card-bg-soft: color-mix(in srgb, var(--bs-body-bg) 88%, var(--bs-primary) 12%);
            --cashier-text: var(--bs-body-color);
            --cashier-text-strong: color-mix(in srgb, var(--bs-body-color) 90%, #000 10%);
            --cashier-text-muted: var(--bs-secondary-color);
            --cashier-border: color-mix(in srgb, var(--bs-border-color) 82%, var(--bs-primary) 18%);
            --cashier-result-hover: rgba(var(--bs-primary-rgb), .07);
            --cashier-warning-soft: rgba(var(--bs-warning-rgb), .13);
            --cashier-danger-soft: rgba(var(--bs-danger-rgb), .13);
            --cashier-success-soft: rgba(var(--bs-success-rgb), .13);
        }

        .cashier-dashboard,
        .cashier-dashboard input,
        .cashier-dashboard button,
        .cashier-dashboard select,
        .cashier-dashboard textarea {
            font-family: "Nunito", "Inter", "Segoe UI", sans-serif;
        }

        .cashier-hero-card,
        .cashier-profile-card,
        .cashier-search-card,
        .cashier-action-card,
        .cashier-empty-state,
        .cashier-result-item,
        .cashier-mini-stat {
            position: relative;
            border: 1px solid var(--cashier-border);
            background: var(--cashier-card-bg);
            background-clip: padding-box; 
            transform: translateZ(0);
        }

        .cashier-hero-card,
        .cashier-search-card,
        .cashier-profile-card {
            overflow: hidden;
        }

        .cashier-hero-card::before,
        .cashier-search-card::before,
        .cashier-profile-card::before {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(2px);
            opacity: .9;
        }

        .cashier-hero-card::before {
            width: 220px;
            height: 220px;
            top: -80px;
            right: -70px;
            background: radial-gradient(circle, rgba(var(--bs-primary-rgb), .18) 0%, rgba(var(--bs-primary-rgb), 0) 70%);
        }

        .cashier-search-card::before {
            width: 180px;
            height: 180px;
            top: -60px;
            right: -50px;
            background: radial-gradient(circle, rgba(var(--bs-info-rgb, 13, 202, 240), .14) 0%, rgba(var(--bs-info-rgb, 13, 202, 240), 0) 72%);
        }

        .cashier-profile-card::before {
            width: 160px;
            height: 160px;
            top: -55px;
            right: -40px;
            background: radial-gradient(circle, rgba(255, 255, 255, .18) 0%, rgba(255, 255, 255, 0) 72%);
        }

        .cashier-hero-card {
            border-radius: var(--cashier-radius-xl);
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), .12), transparent 30%),
                linear-gradient(135deg, var(--cashier-card-bg) 0%, var(--cashier-muted-bg) 100%);
            box-shadow: var(--cashier-shadow);
        }

        .cashier-hero-card::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(135deg, transparent 0%, rgba(var(--bs-primary-rgb), .04) 100%);
        }

        .cashier-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .65rem 1rem;
            border-radius: 999px;
            background: var(--cashier-primary-soft);
            color: var(--bs-primary);
            font-size: .9rem;
            font-weight: 800;
            border: 1px solid var(--cashier-primary-border);
            letter-spacing: .01em;
        }

        .cashier-action-card,
        .cashier-search-card,
        .cashier-profile-card {
            border-radius: var(--cashier-radius-xl);
            box-shadow: var(--cashier-shadow-soft);
        }

        .cashier-action-card {
            height: 100%;
            padding: 1.35rem;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, background-color .2s ease;
            background:
                linear-gradient(180deg, rgba(var(--bs-primary-rgb), .015), rgba(var(--bs-primary-rgb), .04)),
                var(--cashier-card-bg);
        }

        .cashier-action-card:hover,
        .cashier-result-item:hover {
            border-color: var(--cashier-primary-border);
            box-shadow: var(--cashier-shadow-hover);
        }

        .cashier-action-card:hover {
            transform: translateY(-3px);
        }

        .cashier-action-icon {
            width: 58px;
            height: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            border: 1px solid transparent;
        }

        .cashier-action-icon.primary {
            background: linear-gradient(135deg, var(--cashier-primary-soft), var(--cashier-primary-soft-2));
            color: var(--bs-primary);
            border-color: var(--cashier-primary-border);
        }

        .cashier-action-icon.secondary {
            background: rgba(var(--bs-secondary-color-rgb, 108, 117, 125), .10);
            color: var(--cashier-text-strong);
            border-color: var(--cashier-border);
        }

        .cashier-main-btn {
            min-height: 52px;
            border-radius: 999px;
            font-weight: 800;
            letter-spacing: .01em;
            background-clip: padding-box;
            transform: translateZ(0);
        }

        .cashier-info-box {
            border: 1px dashed var(--cashier-primary-border);
            border-radius: var(--cashier-radius-xl);
            background:
                radial-gradient(circle at right top, rgba(var(--bs-primary-rgb), .10), transparent 30%),
                var(--cashier-primary-soft);
        }

        .cashier-profile-top {
            position: relative;
            overflow: hidden;
            background: linear-gradient(
                135deg,
                rgba(var(--bs-primary-rgb), .96) 0%,
                rgba(var(--bs-primary-rgb), .78) 100%
            );
            padding: 1.35rem;
        }

        .cashier-profile-top::after {
            content: "";
            position: absolute;
            width: 170px;
            height: 170px;
            right: -55px;
            top: -70px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(255, 255, 255, .16) 0%, rgba(255, 255, 255, 0) 70%);
        }

        .cashier-avatar-wrap {
            width: 74px;
            height: 74px;
            flex-shrink: 0;
            overflow: hidden;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, .58);
            background: rgba(255, 255, 255, .16);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .12);
        }

        .cashier-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cashier-user-name {
            margin: 0;
            color: #fff;
            font-size: 1.12rem;
            font-weight: 800;
            line-height: 1.3;
        }

        .cashier-user-email {
            color: rgba(255, 255, 255, .86);
            font-size: .94rem;
            word-break: break-word;
        }

        .cashier-mini-stat {
            height: 100%;
            border-radius: var(--cashier-radius-lg);
            padding: 1rem 1.05rem;
            background:
                linear-gradient(180deg, rgba(var(--bs-primary-rgb), .025), rgba(var(--bs-primary-rgb), .06)),
                var(--cashier-card-bg);
        }

        .cashier-mini-stat-label {
            margin-bottom: .28rem;
            color: var(--cashier-text-muted);
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .01em;
        }

        .cashier-mini-stat-value {
            margin: 0;
            color: var(--cashier-text-strong);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.4;
        }

        .cashier-section-title {
            color: var(--cashier-text-strong);
            line-height: 1.3;
        }

        .cashier-section-desc {
            color: var(--cashier-text-muted);
            font-size: .95rem;
            line-height: 1.65;
        }

        .cashier-tip-icon {
            color: var(--bs-primary);
            font-size: 1.3rem;
            line-height: 1;
        }

        .cashier-search-card {
            padding: 1.35rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), .08), transparent 26%),
                linear-gradient(180deg, rgba(var(--bs-primary-rgb), .02), rgba(var(--bs-primary-rgb), .045)),
                var(--cashier-card-bg);
        }

        .cashier-search-input {
            min-height: 54px;
            border-radius: 999px;
            padding-inline: 1rem 1.15rem;
            font-size: .97rem;
            color: var(--cashier-text-strong);
            border-color: var(--cashier-border);
            background: color-mix(in srgb, var(--cashier-card-bg) 92%, white 8%);
            background-clip: padding-box;
            transform: translateZ(0);
        }

        .cashier-search-input::placeholder {
            color: color-mix(in srgb, var(--cashier-text-muted) 82%, transparent 18%);
        }

        .cashier-search-input:focus {
            border-color: rgba(var(--bs-primary-rgb), .38);
            box-shadow: 0 0 0 .24rem rgba(var(--bs-primary-rgb), .13);
        }

        .cashier-search-actions {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            min-height: 100%;
            text-align: right;
        }

        .cashier-results-wrap {
            display: grid;
            gap: .95rem;
        }

        .cashier-result-item {
            border-radius: var(--cashier-radius-lg);
            padding: 1.05rem 1.1rem;
            transition: background-color .2s ease, border-color .2s ease, box-shadow .2s ease, transform .2s ease;
            background:
                linear-gradient(180deg, rgba(var(--bs-primary-rgb), .018), rgba(var(--bs-primary-rgb), .04)),
                var(--cashier-card-bg);
        }

        .cashier-result-item:hover {
            background: var(--cashier-result-hover);
            transform: translateY(-2px);
        }

        .cashier-result-title {
            margin: 0;
            color: var(--cashier-text-strong);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .cashier-result-meta {
            color: var(--cashier-text-muted);
            font-size: .88rem;
            margin-top: .2rem;
            line-height: 1.5;
        }

        .cashier-result-price {
            font-size: 1.02rem;
            font-weight: 800;
            color: var(--cashier-text-strong);
            line-height: 1.4;
        }

        .cashier-result-stock {
            display: inline-flex;
            align-items: center;
            gap: .38rem;
            padding: .42rem .78rem;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 800;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .cashier-result-stock.is-good {
            background: var(--cashier-success-soft);
            color: var(--bs-success);
            border-color: rgba(var(--bs-success-rgb), .22);
        }

        .cashier-result-stock.is-low {
            background: var(--cashier-warning-soft);
            color: var(--bs-warning-text-emphasis, var(--bs-warning));
            border-color: rgba(var(--bs-warning-rgb), .25);
        }

        .cashier-result-stock.is-empty {
            background: var(--cashier-danger-soft);
            color: var(--bs-danger);
            border-color: rgba(var(--bs-danger-rgb), .22);
        }

        .cashier-empty-state {
            border-radius: var(--cashier-radius-lg);
            padding: 1.05rem 1.1rem;
            background:
                radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), .06), transparent 28%),
                linear-gradient(180deg, rgba(var(--bs-primary-rgb), .02), rgba(var(--bs-primary-rgb), .04)),
                var(--cashier-card-bg);
        }

        .cashier-search-status {
            color: var(--cashier-text-muted);
            font-size: .89rem;
            font-weight: 700;
        }

        .cashier-search-spinner {
            width: 1rem;
            height: 1rem;
        }

        .cashier-dashboard .fw-bold,
        .cashier-dashboard .fw-semibold {
            letter-spacing: .01em;
        }

        @media (max-width: 991.98px) {
            .cashier-search-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 767.98px) {
            .cashier-hero-card,
            .cashier-search-card,
            .cashier-profile-card,
            .cashier-info-box {
                border-radius: 1.2rem;
            }

            .cashier-search-actions {
                flex-direction: column;
                align-items: stretch;
            }

            

            .cashier-user-name {
                font-size: 1.02rem;
            }

            .cashier-section-desc {
                font-size: .92rem;
            }
        }
    </style>

    <div
        class="cashier-dashboard"
        data-cashier-dashboard
        data-product-lookup-endpoint="{{ $productLookupEndpoint }}"
    >
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="cashier-hero-card mb-4">
                    <div class="card-body p-4 p-lg-5">

                        <div class="mb-4">
                            <h3 class="cashier-section-title fw-bold mb-2">
                                Pusat kerja cepat kasir
                            </h3>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="cashier-action-card">
                                    <div class="cashier-action-icon primary">
                                        <i class="bi bi-receipt-cutoff"></i>
                                    </div>

                                    <h5 class="fw-bold mb-2">Buat Nota Baru</h5>
                                    <p class="cashier-section-desc small mb-4">
                                        Gunakan menu ini untuk memulai transaksi baru dari workspace kasir.
                                        Ini tetap menjadi aksi utama dashboard.
                                    </p>

                                    <a href="{{ route('cashier.notes.workspace.create') }}" class="btn btn-primary cashier-main-btn w-100">
                                        <i class="bi bi-plus-circle me-2"></i>
                                        Mulai Buat Nota
                                    </a>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="cashier-action-card">
                                    <div class="cashier-action-icon secondary">
                                        <i class="bi bi-clock-history"></i>
                                    </div>

                                    <h5 class="fw-bold mb-2">Riwayat Nota Aktif</h5>
                                    <p class="cashier-section-desc small mb-4">
                                        Buka kembali nota open pada window kasir hari ini dan kemarin
                                        untuk melanjutkan transaksi yang belum selesai.
                                    </p>

                                    <a href="{{ route('cashier.notes.index') }}" class="btn btn-outline-primary cashier-main-btn w-100">
                                        <i class="bi bi-journal-text me-2"></i>
                                        Buka Riwayat Nota
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cashier-search-card mb-4">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="cashier-section-title fw-bold mb-1">Cari Produk Cepat</h5>
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-lg-8">
                            <label for="cashier-product-search" class="form-label fw-semibold">
                                Kata kunci produk
                            </label>
                            <input
                                type="search"
                                id="cashier-product-search"
                                class="form-control cashier-search-input"
                                placeholder="Contoh: oli, kampas, federal, 10W"
                                autocomplete="off"
                                data-product-search-input
                            >
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="cashier-search-status" data-search-status>
                            <span>Siap digunakan</span>
                        </div>
                    </div>

                    <div class="cashier-results-wrap" data-product-search-results>
                        <div class="cashier-empty-state">
                            <div class="fw-semibold mb-1">Belum ada pencarian</div>
                            <div class="cashier-section-desc small mb-0">
                                Masukkan kata kunci untuk melihat harga jual dan stok produk yang tersedia.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="cashier-profile-card">
                    <div class="cashier-profile-top">
                        <div class="d-flex align-items-center gap-3">
                            <div class="cashier-avatar-wrap">
                                <img src="{{ asset('assets/compiled/jpg/1.jpg') }}" alt="Foto Pengguna">
                            </div>

                            <div class="min-w-0">
                                <div class="mb-1">
                                    <h5 class="cashier-user-name">
                                        {{ $appShell['actor_label'] ?? 'Pengguna' }}
                                    </h5>
                                </div>

                                <div class="cashier-user-email">
                                    {{ $appShell['user_email'] ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="cashier-mini-stat">
                                    <div class="cashier-mini-stat-label">Role</div>
                                    <p class="cashier-mini-stat-value">Kasir</p>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="cashier-mini-stat">
                                    <div class="cashier-mini-stat-label">Status</div>
                                    <p class="cashier-mini-stat-value">Online</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="fw-semibold mb-1">Akun sedang digunakan</div>
                            <div class="cashier-section-desc small">
                                Pastikan keluar dari akun setelah selesai menggunakan dashboard kasir,
                                terutama bila perangkat dipakai bergantian.
                            </div>
                        </div>

                        <form action="{{ route('logout') }}" method="post" class="d-grid">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger cashier-main-btn">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Keluar Akun
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="{{ asset('assets/static/js/pages/cashier-dashboard.js') }}?v={{ config('app.asset_version') }}"></script>
@endsection
