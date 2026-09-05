<div id="employee-filter-backdrop" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none" style="z-index: 1040;"></div>
<aside id="employee-filter-drawer" class="position-fixed top-0 end-0 h-100 bg-white shadow p-4 d-none" style="z-index: 1045; width: min(420px, 100vw);">
    <div class="d-flex justify-content-between align-items-center mb-4"><h4 class="mb-0">Filter Karyawan</h4><button type="button" id="close-employee-filter" class="btn-close" aria-label="Tutup"></button></div>
    <form id="employee-filter-form">
        <div class="mb-3"><label class="form-label" for="employee-filter-status">Status</label><select class="form-select" id="employee-filter-status" name="employment_status"><option value="">Semua</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></div>
        <div><label class="form-label" for="employee-filter-basis">Basis Gaji</label><select class="form-select" id="employee-filter-basis" name="salary_basis_type"><option value="">Semua</option><option value="daily">Harian</option><option value="weekly">Mingguan</option><option value="monthly">Bulanan</option><option value="manual">Manual</option></select></div>
        <div class="d-flex gap-2 mt-4"><button type="submit" class="btn btn-primary flex-fill">Terapkan</button><button type="button" id="reset-employee-filter" class="btn btn-outline-secondary flex-fill">Reset</button></div>
    </form>
</aside>
