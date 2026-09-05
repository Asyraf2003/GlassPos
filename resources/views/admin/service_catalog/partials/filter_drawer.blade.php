<div id="service-filter-backdrop" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none" style="z-index: 1040;"></div>
<aside id="service-filter-drawer" class="position-fixed top-0 end-0 h-100 bg-white shadow p-4 d-none" style="z-index: 1045; width: min(420px, 100vw);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Filter Jasa</h4>
        <button type="button" id="close-service-filter" class="btn-close" aria-label="Tutup"></button>
    </div>
    <form id="service-filter-form">
        <label for="service-filter-status" class="form-label">Status</label>
        <select id="service-filter-status" name="status" class="form-select">
            <option value="all">Semua status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </select>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary flex-fill">Terapkan</button>
            <button type="button" id="reset-service-filter" class="btn btn-outline-secondary flex-fill">Reset</button>
        </div>
    </form>
</aside>
