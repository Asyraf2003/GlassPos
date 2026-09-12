@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Form belum valid.</div>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="post">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div data-package-picker data-products='@json($productOptions)' data-services='@json($serviceOptions)'>
        <input type="hidden" name="product_id" value="{{ old('product_id', $template['product_id'] ?? '') }}" data-package-product-id>
        <input type="hidden" name="product_lines[1][product_id]" value="{{ old('product_lines.1.product_id', $template['product_lines'][1]['product_id'] ?? '') }}" data-package-product-id>
        <input type="hidden" name="product_lines[2][product_id]" value="{{ old('product_lines.2.product_id', $template['product_lines'][2]['product_id'] ?? '') }}" data-package-product-id>
        <input type="hidden" name="service_catalog_item_id" value="{{ old('service_catalog_item_id', $template['service_catalog_item_id'] ?? '') }}" data-package-service-id>
        <div class="mb-3">
            <label class="form-label" for="package-product-search">Produk</label>
            <div class="admin-lookup-search" data-product-search-stage>
                <input type="search" id="package-product-search" class="form-control" placeholder="Cari produk..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="package-product-results" data-package-product-search>
                <div id="package-product-results" class="admin-lookup-results list-group d-none" role="listbox" data-package-product-results></div>
            </div>
            <div data-package-products-selected></div>
            <small class="text-muted" data-package-count aria-live="polite">0 dari maksimal 3 produk</small>
            <a class="d-block small" href="{{ route('admin.products.create') }}">Buat produk baru</a>
        </div>
        <div class="mb-3">
            <label class="form-label" for="package-service-search">Jasa</label>
            <div class="admin-lookup-search" data-service-search-stage>
                <input type="search" id="package-service-search" class="form-control" placeholder="Cari jasa..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="package-service-results" data-package-service-search>
                <div id="package-service-results" class="admin-lookup-results list-group d-none" role="listbox" data-package-service-results></div>
            </div>
            <div data-package-service-selected></div>
            <a class="small" href="{{ route('admin.services.create') }}">Buat jasa baru</a>
        </div>
        <div class="mb-4">
            <div class="fw-semibold">TOTAL PAKET</div>
            <output class="fs-4" data-package-total aria-live="polite">Rp0</output>
            <small class="d-block text-muted">Pratinjau harga master saat ini. Total dihitung kembali saat disimpan.</small>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.service-product-templates.index') }}" class="btn btn-light-secondary">
            Batal
        </a>
    </div>
</form>
