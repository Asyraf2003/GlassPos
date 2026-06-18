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

    <div class="form-group mb-4">
        <label for="product_id" class="form-label">Produk</label>
        <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
            <option value="">Pilih produk</option>
            @foreach ($productOptions as $option)
                <option
                    value="{{ $option['id'] }}"
                    @selected(old('product_id', $template['product_id'] ?? '') === $option['id'])
                >
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
        @error('product_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group mb-4">
        <label for="service_catalog_item_id" class="form-label">Jasa</label>
        <select id="service_catalog_item_id" name="service_catalog_item_id" class="form-select @error('service_catalog_item_id') is-invalid @enderror" required>
            <option value="">Pilih jasa</option>
            @foreach ($serviceOptions as $option)
                <option
                    value="{{ $option['id'] }}"
                    @selected(old('service_catalog_item_id', $template['service_catalog_item_id'] ?? '') === $option['id'])
                >
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Harga jasa otomatis mengikuti data master jasa yang dipilih.</small>
        @error('service_catalog_item_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group mb-4" data-money-input-group>
        <label for="default_package_total_rupiah_display" class="form-label">Total</label>

        <input
            type="hidden"
            id="default_package_total_rupiah"
            name="default_package_total_rupiah"
            value="{{ old('default_package_total_rupiah', $template['default_package_total_rupiah'] ?? '') }}"
            data-money-raw
        >

        <input
            type="text"
            inputmode="numeric"
            id="default_package_total_rupiah_display"
            value="{{ old('default_package_total_rupiah', $template['default_package_total_rupiah'] ?? '') }}"
            class="form-control @error('default_package_total_rupiah') is-invalid @enderror"
            placeholder="Contoh: 300.000"
            data-money-display
            required
        >

        <small class="text-muted">
            Total tidak boleh lebih kecil dari harga jual produk + harga jasa.
        </small>

        @error('default_package_total_rupiah')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.service-product-templates.index') }}" class="btn btn-light-secondary">
            Batal
        </a>
    </div>
</form>
