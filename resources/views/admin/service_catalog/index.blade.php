@extends('layouts.app')

@section('title', 'Master Jasa')
@section('heading', 'Master Jasa')

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="card-title mb-1">Master jasa bengkel</h4>
                        <p class="text-muted mb-0">Dipakai untuk lookup kasir dan template jasa + produk.</p>
                    </div>

                    <a href="{{ route('admin.services.create') }}" class="btn btn-primary">
                        Tambah Jasa
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

                <div class="table-responsive">
                    <table class="table table-lg">
                        <thead>
                            <tr>
                                <th>Nama Jasa</th>
                                <th>Nama Normal</th>
                                <th>Default Harga</th>
                                <th>Status</th>
                                <th style="width: 220px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($services as $service)
                                <tr>
                                    <td class="fw-semibold">{{ $service['name'] }}</td>
                                    <td><small class="text-muted">{{ $service['normalized_name'] }}</small></td>
                                    <td>{{ number_format($service['default_price_rupiah'], 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge {{ $service['is_active'] ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $service['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ route('admin.services.edit', ['serviceId' => $service['id']]) }}" class="btn btn-sm btn-outline-primary">
                                                Edit
                                            </a>

                                            @if ($service['is_active'])
                                                <form action="{{ route('admin.services.deactivate', ['serviceId' => $service['id']]) }}" method="post" class="m-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                                        Nonaktifkan
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.services.activate', ['serviceId' => $service['id']]) }}" method="post" class="m-0">
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
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada master jasa.
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
