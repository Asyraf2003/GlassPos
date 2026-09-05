<div id="payroll-filter-backdrop" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none" style="z-index: 1040;"></div>
<aside id="payroll-filter-drawer" class="position-fixed top-0 end-0 h-100 bg-white shadow p-4 d-none" style="z-index: 1045; width: min(420px, 100vw);">
    <div class="d-flex justify-content-between align-items-center mb-4"><h4 class="mb-0">Filter Gaji</h4><button type="button" id="close-payroll-filter" class="btn-close" aria-label="Tutup"></button></div>
    <form id="payroll-filter-form">
        <div class="mb-3"><label class="form-label">Mode</label><select name="mode" class="form-select"><option value="">Semua</option><option value="daily">Harian</option><option value="weekly">Mingguan</option><option value="monthly">Bulanan</option></select></div>
        <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="all">Semua</option><option value="active">Aktif</option><option value="reversed">Dibatalkan</option></select></div>
        <div class="row g-2"><div class="col-6"><label class="form-label">Dari</label><input name="date_from" type="date" class="form-control"></div><div class="col-6"><label class="form-label">Sampai</label><input name="date_to" type="date" class="form-control"></div></div>
        <div class="d-flex gap-2 mt-4"><button type="submit" class="btn btn-primary flex-fill">Terapkan</button><button type="button" id="reset-payroll-filter" class="btn btn-outline-secondary flex-fill">Reset</button></div>
    </form>
</aside>
