@extends('layouts.app')

@section('title', 'Paket Service')
@section('heading', 'Paket Service')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Paket fast entry untuk servis + produk</h4>
                        <p class="text-muted mb-0">Produk memakai harga jual katalog. Harga jasa mengikuti master jasa. Total paket wajib minimal produk + jasa.</p>
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
                                <th style="width: 220px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($templates as $template)
                                @php
                                    $minimumTotal = (int) $template['harga_jual'] + (int) $template['default_service_price_rupiah'];
                                    $packageTotal = $template['default_package_total_rupiah'] !== null
                                        ? (int) $template['default_package_total_rupiah']
                                        : $minimumTotal;
                                    $packageMargin = max(0, $packageTotal - $minimumTotal);
                                @endphp

                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $template['service_name'] }}</div>
                                        <small class="text-muted">Paket Service</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $template['nama_barang'] }}</div>
                                        <small class="text-muted">
                                            {{ $template['kode_barang'] ?: '-' }} · harga jual {{ number_format($template['harga_jual'], 0, ',', '.') }}
                                        </small>
                                    </td>
                                    <td>
                                        {{ number_format($template['default_service_price_rupiah'], 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ number_format($packageTotal, 0, ',', '.') }}</div>
                                        <small class="text-muted">
                                            Min {{ number_format($minimumTotal, 0, ',', '.') }}
                                            @if ($packageMargin > 0)
                                                · Selisih {{ number_format($packageMargin, 0, ',', '.') }}
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $template['is_active'] ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $template['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ route('admin.service-product-templates.edit', ['templateId' => $template['id']]) }}" class="btn btn-sm btn-outline-primary">
                                                Edit
                                            </a>

                                            @if ($template['is_active'])
                                                <form action="{{ route('admin.service-product-templates.deactivate', ['templateId' => $template['id']]) }}" method="post" class="m-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                                        Nonaktifkan
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.service-product-templates.reactivate', ['templateId' => $template['id']]) }}" method="post" class="m-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        Aktifkan
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
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
    </section>
@endsection
