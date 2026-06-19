@extends('layouts.app')

@section('title', 'Template Jasa + Produk')
@section('heading', 'Template Jasa + Produk')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Produk memakai harga jual katalog. Harga jasa mengikuti master jasa. Total paket wajib minimal produk + jasa</h4>
                    </div>

                    <a href="{{ route('admin.service-product-templates.create') }}" class="btn btn-primary">
                        Tambah Paket
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @error('product_id')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <div class="table-responsive">
                    <table class="table table-lg">
                        <thead>
                            <tr>
                                <th>Paket</th>
                                <th>Produk</th>
                                <th>Jasa</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($templates as $template)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $template['service_name'] }}</div>
                                        <small class="text-muted">Template Jasa + Produk</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $template['nama_barang'] }}</div>
                                        <small class="text-muted">
                                            {{ $template['kode_barang'] ?: '-' }} · harga jual {{ number_format($template['harga_jual'], 0, ',', '.') }}
                                        </small>
                                    </td>
                                    <td>{{ number_format($template['default_service_price_rupiah'], 0, ',', '.') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ number_format($template['package_total'], 0, ',', '.') }}</div>
                                        <small class="text-muted">
                                            Min {{ number_format($template['minimum_total'], 0, ',', '.') }}
                                            @if ($template['package_margin'] > 0)
                                                · Selisih {{ number_format($template['package_margin'], 0, ',', '.') }}
                                            @endif
                                        </small>
                                        @if ($template['package_margin'] > 0)
                                            <div class="small text-muted mt-1">
                                                80% keuntungan {{ number_format($template['package_profit'], 0, ',', '.') }}
                                                · 20% jasa {{ number_format($template['package_service_extra'], 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $template['is_active'] ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $template['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-package-action="open"
                                            data-package-name="{{ $template['service_name'] }}"
                                            data-package-product="{{ $template['nama_barang'] }}"
                                            data-package-status="{{ $template['is_active'] ? 'active' : 'inactive' }}"
                                            data-detail-url="{{ route('admin.service-product-templates.show', ['templateId' => $template['id']]) }}"
                                            data-edit-url="{{ route('admin.service-product-templates.edit', ['templateId' => $template['id']]) }}"
                                            data-product-url="{{ route('admin.products.show', ['productId' => $template['product_id']]) }}"
                                            data-service-url="{{ route('admin.services.edit', ['serviceId' => $template['service_catalog_item_id']]) }}"
                                            data-deactivate-url="{{ route('admin.service-product-templates.deactivate', ['templateId' => $template['id']]) }}"
                                            data-reactivate-url="{{ route('admin.service-product-templates.reactivate', ['templateId' => $template['id']]) }}"
                                        >
                                            Aksi
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada paket service.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
                            <h3 class="modal-title fw-bold mb-1" id="package-service-action-modal-title">Aksi Template Jasa + Produk</h3>
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
