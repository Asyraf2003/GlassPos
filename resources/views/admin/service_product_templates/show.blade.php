@extends('layouts.app')

@section('title', 'Detail Template Jasa + Produk')
@section('heading', 'Detail Template Jasa + Produk')
@section('back_url', route('admin.service-product-templates.index'))

@section('content')
    <section class="section">
        <div class="ui-page-intro">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h4 class="ui-page-intro-title mb-1">{{ $template['service_name'] }}</h4>
                    <p class="text-muted mb-0">{{ $template['product_name'] }} · {{ $template['product_code'] }}</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.service-product-templates.edit', ['templateId' => $template['id']]) }}" class="btn btn-primary">
                        Edit Paket
                    </a>

                    <a href="{{ route('admin.products.show', ['productId' => $template['product_id']]) }}" class="btn btn-light-primary">
                        Lihat Produk
                    </a>

                    <a href="{{ route('admin.services.edit', ['serviceId' => $template['service_catalog_item_id']]) }}" class="btn btn-light-secondary">
                        Edit Jasa
                    </a>

                    @if ($template['is_active'])
                        <form action="{{ route('admin.service-product-templates.deactivate', ['templateId' => $template['id']]) }}" method="post" class="m-0">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-warning">
                                Nonaktifkan
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.service-product-templates.reactivate', ['templateId' => $template['id']]) }}" method="post" class="m-0">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-success">
                                Aktifkan
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ringkasan Paket</h5>
                    </div>

                    <div class="card-body">
                        <div class="ui-key-value mb-3">
                            <small>Status</small>
                            <div>
                                <span class="badge {{ $template['is_active'] ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $template['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>

                        <div class="ui-key-value mb-3">
                            <small>Nama Paket/Jasa</small>
                            <div>{{ $template['service_name'] }}</div>
                        </div>

                        <div class="ui-key-value mb-3">
                            <small>Total Paket</small>
                            <div class="fw-semibold">{{ number_format($template['package_total'], 0, ',', '.') }}</div>
                        </div>

                        <div class="ui-key-value mb-3">
                            <small>Minimum Total</small>
                            <div>{{ number_format($template['minimum_total'], 0, ',', '.') }}</div>
                        </div>

                        <div class="ui-key-value mb-3">
                            <small>Selisih Paket</small>
                            <div>{{ number_format($template['package_margin'], 0, ',', '.') }}</div>
                        </div>

                        <div class="ui-key-value mb-3">
                            <small>Harga Modal Produk AVG</small>
                            <div>
                                {{ $template['average_cost'] !== null ? number_format($template['average_cost'], 0, ',', '.') : 'Belum tersedia' }}
                            </div>
                        </div>

                        <div class="ui-key-value mb-3">
                            <small>Margin Produk dari Harga Jual</small>
                            <div>
                                {{ $template['product_gross_margin'] !== null ? number_format($template['product_gross_margin'], 0, ',', '.') : 'Belum tersedia' }}
                            </div>
                        </div>

                        <div class="ui-key-value mb-3">
                            <small>Dibuat</small>
                            <div>{{ \App\Support\ViewDateFormatter::display($template['created_at'] ?? null, true) }}</div>
                        </div>

                        <div class="ui-key-value">
                            <small>Update Terakhir</small>
                            <div>{{ \App\Support\ViewDateFormatter::display($template['updated_at'] ?? null, true) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="ui-card-stack">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Pecahan 80/20 Selisih Paket</h5>
                        </div>

                        <div class="card-body">
                            @if ($template['package_margin'] <= 0)
                                <p class="text-muted mb-0">
                                    Total paket sama dengan minimum produk + jasa. Belum ada selisih untuk dipecah.
                                </p>
                            @else
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="ui-key-value">
                                            <small>Selisih Di Atas Produk + Jasa</small>
                                            <div>{{ number_format($template['package_margin'], 0, ',', '.') }}</div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="ui-key-value">
                                            <small>Keuntungan Paket 80%</small>
                                            <div class="fw-semibold">{{ number_format($template['package_profit'], 0, ',', '.') }}</div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="ui-key-value">
                                            <small>Tambahan Jasa 20%</small>
                                            <div>{{ number_format($template['package_service_extra'], 0, ',', '.') }}</div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="ui-key-value">
                                            <small>Jasa Efektif</small>
                                            <div>{{ number_format($template['effective_service_price'], 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Produk Terhubung</h5>
                        </div>

                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Nama Produk</small>
                                        <div>{{ $template['product_name'] }}</div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Kode Produk</small>
                                        <div>{{ $template['product_code'] }}</div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Merek</small>
                                        <div>{{ $template['product_brand'] }}</div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Ukuran</small>
                                        <div>{{ $template['product_size'] }}</div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Harga Jual Produk</small>
                                        <div>{{ number_format($template['product_price'], 0, ',', '.') }}</div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Harga Modal AVG</small>
                                        <div>{{ $template['average_cost'] !== null ? number_format($template['average_cost'], 0, ',', '.') : 'Belum tersedia' }}</div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Margin Produk</small>
                                        <div>{{ $template['product_gross_margin'] !== null ? number_format($template['product_gross_margin'], 0, ',', '.') : 'Belum tersedia' }}</div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Nilai Persediaan Produk</small>
                                        <div>{{ $template['inventory_value'] !== null ? number_format($template['inventory_value'], 0, ',', '.') : 'Belum tersedia' }}</div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <a href="{{ route('admin.products.show', ['productId' => $template['product_id']]) }}" class="btn btn-sm btn-outline-primary">
                                        Buka Detail Produk
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Jasa Terhubung</h5>
                        </div>

                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Nama Jasa</small>
                                        <div>{{ $template['service_name'] }}</div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Status Jasa</small>
                                        <div>
                                            <span class="badge {{ $template['service_is_active'] ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $template['service_is_active'] ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Harga Jasa Saat Paket Dibuat/Diupdate</small>
                                        <div>{{ number_format($template['template_service_price'], 0, ',', '.') }}</div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="ui-key-value">
                                        <small>Harga Jasa Master Saat Ini</small>
                                        <div>{{ number_format($template['current_service_price'], 0, ',', '.') }}</div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <a href="{{ route('admin.services.edit', ['serviceId' => $template['service_catalog_item_id']]) }}" class="btn btn-sm btn-outline-primary">
                                        Edit Jasa
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light-info border mb-0">
                        Harga modal produk dan riwayat pemakaian paket akan masuk slice berikutnya setelah relasi detail produk dipasang.
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
