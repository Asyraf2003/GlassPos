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

    @include('admin.shared.partials.searchable-create-select', [
        'id' => 'product_id',
        'name' => 'product_id',
        'label' => 'Produk 1',
        'options' => $productOptions,
        'selected' => old('product_id', $template['product_id'] ?? ''),
        'placeholder' => 'Cari / pilih produk',
        'emptyMessage' => 'Produk tidak ditemukan.',
        'createUrl' => route('admin.products.create'),
        'createLabel' => 'Buat produk baru',
    ])

    <div class="row">
        <div class="col-md-8">
            @include('admin.shared.partials.searchable-create-select', [
                'id' => 'product_line_1_product_id',
                'name' => 'product_lines[1][product_id]',
                'label' => 'Produk 2 (opsional)',
                'options' => $productOptions,
                'selected' => old('product_lines.1.product_id', $template['product_lines'][1]['product_id'] ?? ''),
                'placeholder' => 'Cari / pilih produk opsional',
                'emptyMessage' => 'Produk tidak ditemukan.',
                'createUrl' => route('admin.products.create'),
                'createLabel' => 'Buat produk baru',
            ])
        </div>
        <div class="col-md-4">
            <div class="form-group mb-4">
                <label for="product_line_1_qty" class="form-label">Qty Produk 2</label>
                <input
                    type="number"
                    id="product_line_1_qty"
                    name="product_lines[1][qty]"
                    value="{{ old('product_lines.1.qty', $template['product_lines'][1]['qty'] ?? 1) }}"
                    class="form-control @error('product_lines.1.qty') is-invalid @enderror"
                    min="1"
                >
                @error('product_lines.1.qty')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            @include('admin.shared.partials.searchable-create-select', [
                'id' => 'product_line_2_product_id',
                'name' => 'product_lines[2][product_id]',
                'label' => 'Produk 3 (opsional)',
                'options' => $productOptions,
                'selected' => old('product_lines.2.product_id', $template['product_lines'][2]['product_id'] ?? ''),
                'placeholder' => 'Cari / pilih produk opsional',
                'emptyMessage' => 'Produk tidak ditemukan.',
                'createUrl' => route('admin.products.create'),
                'createLabel' => 'Buat produk baru',
            ])
        </div>
        <div class="col-md-4">
            <div class="form-group mb-4">
                <label for="product_line_2_qty" class="form-label">Qty Produk 3</label>
                <input
                    type="number"
                    id="product_line_2_qty"
                    name="product_lines[2][qty]"
                    value="{{ old('product_lines.2.qty', $template['product_lines'][2]['qty'] ?? 1) }}"
                    class="form-control @error('product_lines.2.qty') is-invalid @enderror"
                    min="1"
                >
                @error('product_lines.2.qty')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    @error('product_lines')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    @include('admin.shared.partials.searchable-create-select', [
        'id' => 'service_catalog_item_id',
        'name' => 'service_catalog_item_id',
        'label' => 'Jasa',
        'options' => $serviceOptions,
        'selected' => old('service_catalog_item_id', $template['service_catalog_item_id'] ?? ''),
        'placeholder' => 'Cari / pilih jasa',
        'emptyMessage' => 'Jasa tidak ditemukan.',
        'createUrl' => route('admin.services.create'),
        'createLabel' => 'Buat jasa baru',
        'help' => 'Harga jasa otomatis mengikuti data master jasa yang dipilih.',
    ])

    <div class="form-group mb-4" data-money-input-group>
        <label for="default_service_price_rupiah_display" class="form-label">Harga Jasa Template</label>

        <input
            type="hidden"
            id="default_service_price_rupiah"
            name="default_service_price_rupiah"
            value="{{ old('default_service_price_rupiah', $template['default_service_price_rupiah'] ?? '') }}"
            data-money-raw
        >

        <input
            type="text"
            inputmode="numeric"
            id="default_service_price_rupiah_display"
            value="{{ old('default_service_price_rupiah', $template['default_service_price_rupiah'] ?? '') }}"
            class="form-control @error('default_service_price_rupiah') is-invalid @enderror"
            placeholder="Contoh: 75.000"
            data-money-display
            required
        >

        <small class="text-muted">
            Harga jasa template dipakai sebagai batas bawah jasa saat paket otomatis dipecah.
        </small>

        @error('default_service_price_rupiah')
            <div class="invalid-feedback d-block">{{ $message }}</div>
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
            Total tidak boleh lebih kecil dari harga jual produk + harga jasa. Selisih di atas minimum dibaca sebagai 80% keuntungan paket dan 20% tambahan jasa.
        </small>

        @error('default_package_total_rupiah')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group mb-4">
        <label for="sort_order" class="form-label">Urutan</label>
        <input
            type="number"
            id="sort_order"
            name="sort_order"
            value="{{ old('sort_order', $template['sort_order'] ?? 0) }}"
            class="form-control @error('sort_order') is-invalid @enderror"
            min="0"
            required
        >
        @error('sort_order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.service-product-templates.index') }}" class="btn btn-light-secondary">
            Batal
        </a>
    </div>
</form>
