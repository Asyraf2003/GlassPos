<div id="supplier-filter-backdrop" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none" style="z-index: 1040;"></div>
<aside id="supplier-filter-drawer" class="position-fixed top-0 end-0 h-100 bg-white shadow p-4 d-none" style="z-index: 1045; width: min(420px, 100vw);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Filter Pemasok</h4>
        <button type="button" id="close-supplier-filter" class="btn-close" aria-label="Tutup"></button>
    </div>
    <form id="supplier-filter-form">
        <label for="supplier-filter-status" class="form-label">Status Hutang</label>
        <select id="supplier-filter-status" name="status" class="form-select">
            <option value="all">Semua</option><option value="outstanding">Masih berhutang</option><option value="settled">Tidak ada sisa hutang</option>
        </select>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary flex-fill">Terapkan</button>
            <button type="button" id="reset-supplier-filter" class="btn btn-outline-secondary flex-fill">Reset</button>
        </div>
    </form>
</aside>
