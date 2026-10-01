<div class="modal fade" id="supplier-create-modal" tabindex="-1" aria-labelledby="supplier-create-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.suppliers.store') }}">
            @csrf
            <input type="hidden" name="supplier_form" value="create">
            <div class="modal-header">
                <h5 class="modal-title" id="supplier-create-title">Tambah Pemasok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label for="supplier-create-name" class="form-label">Nama PT</label>
                    <input type="text" id="supplier-create-name" name="nama_pt_pengirim" class="form-control" required maxlength="255" value="{{ old('nama_pt_pengirim') }}">
                    @error('nama_pt_pengirim')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                @include('admin.suppliers.partials.bank_fields', ['prefix' => 'supplier-create'])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
