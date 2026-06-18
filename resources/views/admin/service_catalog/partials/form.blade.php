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
        <label for="name" class="form-label">Nama Jasa</label>
        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $service['name'] ?? '') }}"
            class="form-control @error('name') is-invalid @enderror"
            placeholder="Contoh: Ganti Oli"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group mb-4">
        <label for="default_price_rupiah" class="form-label">Default Harga Jasa</label>
        <input
            type="number"
            min="1"
            id="default_price_rupiah"
            name="default_price_rupiah"
            value="{{ old('default_price_rupiah', $service['default_price_rupiah'] ?? '') }}"
            class="form-control @error('default_price_rupiah') is-invalid @enderror"
            placeholder="Contoh: 75000"
            required
        >
        @error('default_price_rupiah')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.services.index') }}" class="btn btn-light-secondary">
            Batal
        </a>
    </div>
</form>
