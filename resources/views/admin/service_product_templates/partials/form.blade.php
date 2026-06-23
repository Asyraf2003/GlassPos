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
        'help' => 'Harga jasa otomatis mengikuti data master jasa. Total paket otomatis dari produk + jasa.',
    ])

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.service-product-templates.index') }}" class="btn btn-light-secondary">
            Batal
        </a>
    </div>
</form>
