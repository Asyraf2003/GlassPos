@extends('layouts.app')
@section('title', 'Detail Produk')
@section('heading', 'Detail Produk')
@section('back_url', route('admin.products.index'))

@section('content')
    <section class="section">
        <div class="ui-page-intro">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h4 class="ui-page-intro-title">{{ $page['heading'] }} {{ $page['subtitle'] }}</h4>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $page['actions']['edit_identity_url'] }}" class="btn btn-primary">
                        Edit Identitas
                    </a>
                    <a href="{{ $page['actions']['stock_adjustment_url'] }}" class="btn btn-light-primary">
                        Ubah Stok
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <div class="ui-card-stack">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Identitas Saat Ini</h5>
                        </div>
                        <div class="card-body">
                            <div class="ui-key-value mb-3">
                                <small>Kode Barang</small>
                                <div>{{ $page['current_identity']['kode_barang'] }}</div>
                            </div>

                            <div class="ui-key-value mb-3">
                                <small>Nama Barang</small>
                                <div>{{ $page['current_identity']['nama_barang'] }}</div>
                            </div>

                            <div class="ui-key-value mb-3">
                                <small>Merek</small>
                                <div>{{ $page['current_identity']['merek'] }}</div>
                            </div>

                            <div class="ui-key-value mb-3">
                                <small>Ukuran</small>
                                <div>{{ $page['current_identity']['ukuran'] }}</div>
                            </div>

                            <div class="ui-key-value mb-3">
                                <small>Harga Jual</small>
                                <div>{{ $page['current_identity']['harga_jual_label'] }}</div>
                            </div>

                            <div class="ui-key-value mb-3">
                                <small>Mulai Perlu Restok (Reorder Point)</small>
                                <div>{{ $page['current_identity']['reorder_point_qty'] }}</div>
                            </div>

                            <div class="ui-key-value">
                                <small>Batas Stok Kritis</small>
                                <div>{{ $page['current_identity']['critical_threshold_qty'] }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Paket Service Terhubung</h5>
                        </div>

                        <div class="card-body">
                            @if (count($page['linked_service_packages'] ?? []) === 0)
                                <p class="text-muted mb-0">Produk ini belum terhubung ke paket service.</p>
                            @else
                                <div class="vstack gap-3">
                                    @foreach ($page['linked_service_packages'] as $package)
                                        <div class="border rounded p-3">
                                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                                <div>
                                                    <div class="fw-semibold">{{ $package['service_name'] }}</div>
                                                    <small class="text-muted">
                                                        Total {{ number_format($package['package_total'], 0, ',', '.') }}
                                                        · Min {{ number_format($package['minimum_total'], 0, ',', '.') }}
                                                        @if ($package['package_margin'] > 0)
                                                            · Selisih {{ number_format($package['package_margin'], 0, ',', '.') }}
                                                        @endif
                                                    </small>

                                                    @php
                                                        $packageServiceExtra = intdiv((int) $package['package_margin'], 5);
                                                        $packageProfit = (int) $package['package_margin'] - $packageServiceExtra;
                                                    @endphp

                                                    <div class="small text-muted mt-1">
                                                        Modal AVG:
                                                        {{ $package['average_cost'] !== null ? number_format($package['average_cost'], 0, ',', '.') : 'Belum tersedia' }}
                                                        · Margin produk:
                                                        {{ $package['product_gross_margin'] !== null ? number_format($package['product_gross_margin'], 0, ',', '.') : 'Belum tersedia' }}
                                                    </div>

                                                    @if ($package['package_margin'] > 0)
                                                        <div class="small text-muted mt-1">
                                                            80% keuntungan {{ number_format($packageProfit, 0, ',', '.') }}
                                                            · 20% jasa {{ number_format($packageServiceExtra, 0, ',', '.') }}
                                                        </div>
                                                    @endif

                                                    <div class="mt-2">
                                                        <span class="badge {{ $package['is_active'] ? 'bg-success' : 'bg-secondary' }}">
                                                            {{ $package['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="align-self-md-center">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-primary"
                                                        data-package-action="open"
                                                        data-package-name="{{ $package['service_name'] }}"
                                                        data-package-product="{{ $package['product_name'] }}"
                                                        data-package-status="{{ $package['is_active'] ? 'active' : 'inactive' }}"
                                                        data-detail-url="{{ route('admin.service-product-templates.show', ['templateId' => $package['id']]) }}"
                                                        data-edit-url="{{ route('admin.service-product-templates.edit', ['templateId' => $package['id']]) }}"
                                                        data-product-url="{{ route('admin.products.show', ['productId' => $package['product_id']]) }}"
                                                        data-service-url="{{ route('admin.services.edit', ['serviceId' => $package['service_catalog_item_id']]) }}"
                                                        data-deactivate-url="{{ route('admin.service-product-templates.deactivate', ['templateId' => $package['id']]) }}"
                                                        data-reactivate-url="{{ route('admin.service-product-templates.reactivate', ['templateId' => $package['id']]) }}"
                                                    >
                                                        Aksi
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($page['initial_identity'] !== null || $page['initial_identity_meta']['note'] !== null)
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <h5 class="card-title mb-0">{{ $page['initial_identity_meta']['title'] }}</h5>
                                    <span class="badge bg-light-{{ $page['initial_identity_meta']['badge_tone'] }} text-{{ $page['initial_identity_meta']['badge_tone'] }}">
                                        {{ $page['initial_identity_meta']['badge_label'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                @if ($page['initial_identity_meta']['note'] !== null)
                                    <div class="alert alert-light-{{ $page['initial_identity_meta']['badge_tone'] }} mb-4">
                                        {{ $page['initial_identity_meta']['note'] }}
                                    </div>
                                @endif

                                @if ($page['initial_identity_meta']['show_values'] && $page['initial_identity'] !== null)
                                    <div class="ui-key-value mb-3">
                                        <small>Kode Barang</small>
                                        <div>{{ $page['initial_identity']['kode_barang'] }}</div>
                                    </div>

                                    <div class="ui-key-value mb-3">
                                        <small>Nama Barang</small>
                                        <div>{{ $page['initial_identity']['nama_barang'] }}</div>
                                    </div>

                                    <div class="ui-key-value mb-3">
                                        <small>Merek</small>
                                        <div>{{ $page['initial_identity']['merek'] }}</div>
                                    </div>

                                    <div class="ui-key-value mb-3">
                                        <small>Ukuran</small>
                                        <div>{{ $page['initial_identity']['ukuran'] }}</div>
                                    </div>

                                    <div class="ui-key-value mb-3">
                                        <small>Harga Jual</small>
                                        <div>{{ $page['initial_identity']['harga_jual_label'] }}</div>
                                    </div>

                                    <div class="ui-key-value mb-3">
                                        <small>Mulai Perlu Restok (Reorder Point)</small>
                                        <div>{{ $page['initial_identity']['reorder_point_qty'] }}</div>
                                    </div>

                                    <div class="ui-key-value mb-3">
                                        <small>Batas Stok Kritis</small>
                                        <div>{{ $page['initial_identity']['critical_threshold_qty'] }}</div>
                                    </div>

                                    <div class="ui-key-value">
                                        <small>Tercatat Pada</small>
                                        <div>{{ $page['initial_identity']['changed_at'] }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Riwayat Versi Produk</h5>
                    </div>
                    <div class="card-body">
                        @if (count($page['timeline']) === 0)
                            <p class="text-muted mb-0">Belum ada riwayat versi produk.</p>
                        @else
                            <div class="timeline">
                                @foreach ($page['timeline'] as $entry)
                                    <div class="timeline-item pb-4">
                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                                            <div>
                                                <h6 class="mb-1">
                                                    {{ $entry['revision_label'] }} · {{ $entry['event_name'] }}
                                                </h6>
                                                <small class="text-muted">
                                                    {{ $entry['changed_at'] }}
                                                    @if ($entry['actor_label'])
                                                        · {{ $entry['actor_label'] }}
                                                    @endif
                                                </small>
                                            </div>

                                            @if ($entry['reason_label'])
                                                <span class="badge bg-light-info text-info align-self-start">
                                                    {{ $entry['reason_label'] }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="border rounded p-3 bg-light-subtle">
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <div class="ui-key-value">
                                                        <small>Kode Barang</small>
                                                        <div>{{ $entry['snapshot']['kode_barang'] }}</div>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="ui-key-value">
                                                        <small>Nama Barang</small>
                                                        <div>{{ $entry['snapshot']['nama_barang'] }}</div>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="ui-key-value">
                                                        <small>Merek</small>
                                                        <div>{{ $entry['snapshot']['merek'] }}</div>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="ui-key-value">
                                                        <small>Ukuran</small>
                                                        <div>{{ $entry['snapshot']['ukuran'] }}</div>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="ui-key-value">
                                                        <small>Harga Jual</small>
                                                        <div>{{ $entry['snapshot']['harga_jual_label'] }}</div>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="ui-key-value">
                                                        <small>Mulai Perlu Restok (Reorder Point)</small>
                                                        <div>{{ $entry['snapshot']['reorder_point_qty'] }}</div>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="ui-key-value">
                                                        <small>Batas Stok Kritis</small>
                                                        <div>{{ $entry['snapshot']['critical_threshold_qty'] }}</div>
                                                    </div>
                                                </div>

                                                @if ($entry['snapshot']['deleted_at'] !== null)
                                                    <div class="col-12 col-md-6">
                                                        <div class="ui-key-value">
                                                            <small>Dihapus Pada</small>
                                                            <div>{{ \App\Support\ViewDateFormatter::display($entry['snapshot']['deleted_at'] ?? null, true) }}</div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="package-service-action-modal"
            tabindex="-1"
            aria-labelledby="package-service-action-modal-title"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <div class="w-100">
                            <h3 class="modal-title fw-bold mb-1" id="package-service-action-modal-title">Aksi Paket Service</h3>
                            <p class="mb-0 text-muted fs-6" id="package-service-action-modal-subtitle">
                                Pilih tindakan untuk paket service.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <a href="#" id="package-service-action-detail-link" class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100">
                                    <div class="fw-bold fs-5 mb-1">Detail</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="#" id="package-service-action-edit-link" class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100">
                                    <div class="fw-bold fs-5 mb-1">Edit Paket</div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <form id="package-service-action-status-form" method="post" class="m-0">
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        id="package-service-action-status-button"
                                        class="btn btn-outline-warning w-100 text-start py-3 px-4"
                                    >
                                        <div class="fw-bold fs-5 mb-1" id="package-service-action-status-title">Nonaktifkan</div>
                                    </button>
                                </form>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="#" id="package-service-action-service-link" class="btn btn-outline-primary w-100 text-start py-3 px-4 h-100">
                                    <div class="fw-bold fs-5 mb-1">Edit Jasa</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/static/js/pages/admin-package-service-actions.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
