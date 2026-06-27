<div id="admin-note-filter-backdrop" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-25 d-none" style="z-index: 1040;"></div>

<div id="admin-note-filter-drawer" class="position-fixed top-0 end-0 h-100 bg-body border-start shadow d-none" style="width: 360px; z-index: 1050; overflow-y: auto;">
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Filter Daftar Nota Admin</h5>
            <button type="button" id="close-admin-note-filter" class="btn btn-sm btn-light-secondary">Tutup</button>
        </div>

        <form id="admin-note-filter-form">
            <div class="form-group mb-4">
                <label for="admin-note-line-status" class="form-label">Status Rincian</label>
                <select id="admin-note-line-status" class="form-select">
                    <option value="" @selected($filters['line_status'] === '')>Semua Status</option>
                    <option value="open" @selected($filters['line_status'] === 'open')>Belum Selesai</option>
                    <option value="close" @selected($filters['line_status'] === 'close')>Selesai</option>
                    <option value="refund" @selected($filters['line_status'] === 'refund')>Dikembalikan</option>
                </select>
            </div>

            <div class="form-group mb-4">
                <label class="form-label" for="admin-note-date-range">Rentang Tanggal Nota</label>
                <input
                    type="text"
                    id="admin-note-date-range"
                    class="form-control"
                    data-ui-date="range-single"
                    data-ui-date-placeholder="Pilih rentang tanggal nota"
                    data-range-start-name="date_from"
                    data-range-end-name="date_to"
                    autocomplete="off"
                >
                <input type="hidden" id="admin-note-date-from" name="date_from" value="{{ $filters['date_from'] }}">
                <input type="hidden" id="admin-note-date-to" name="date_to" value="{{ $filters['date_to'] }}">
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                <button type="button" id="reset-admin-note-filter" class="btn btn-light-secondary">Reset</button>
            </div>
        </form>
    </div>
</div>
