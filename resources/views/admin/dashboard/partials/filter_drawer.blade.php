<div
    id="{{ $dashboardFilterDrawer['backdrop_id'] }}"
    class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-25 d-none"
    style="z-index: 1040;"
></div>

<div
    id="{{ $dashboardFilterDrawer['drawer_id'] }}"
    class="position-fixed top-0 end-0 h-100 bg-body border-start shadow d-none"
    style="width: 420px; max-width: 100%; z-index: 1050; overflow-y: auto;"
>
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h5 class="mb-1 fw-bold">Filter Bulan Dashboard</h5>
            </div>

            <button
                type="button"
                id="{{ $dashboardFilterDrawer['close_button_id'] }}"
                class="btn btn-sm btn-light-secondary"
            >
                Tutup
            </button>
        </div>

        <form
            method="get"
            action="{{ route('admin.dashboard') }}"
            id="{{ $dashboardFilterDrawer['form_id'] }}"
            data-report-period-filter="1"
            data-filter-open-button-id="{{ $dashboardFilterDrawer['open_button_id'] }}"
            data-filter-close-button-id="{{ $dashboardFilterDrawer['close_button_id'] }}"
            data-filter-drawer-id="{{ $dashboardFilterDrawer['drawer_id'] }}"
            data-filter-backdrop-id="{{ $dashboardFilterDrawer['backdrop_id'] }}"
            class="d-grid gap-4"
        >
            <section class="d-grid gap-3">
                <div>
                    <div class="section-title mb-1">Bulan Dashboard {{ $dashboard['period']['date_from'] ?? '-' }} s.d {{ $dashboard['period']['date_to'] ?? '-' }}</div>
                </div>

                <div class="form-group">
                    <label for="{{ $dashboardFilterDrawer['form_id'] }}-month" class="form-label fw-bold">Pilih Bulan</label>
                    <input
                        type="month"
                        id="{{ $dashboardFilterDrawer['form_id'] }}-month"
                        name="month"
                        class="form-control"
                        value="{{ $dashboardFilterDrawer['active_month'] }}"
                    >
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary fw-bold">
                        Terapkan Bulan
                    </button>

                    <a href="{{ route('admin.dashboard') }}" class="btn btn-light-secondary fw-bold">
                        Kembali ke Bulan Ini
                    </a>
                </div>
            </section>

            <section class="d-grid gap-3">
                <div>
                    <div class="section-title mb-1">Cetak / Unduh Laporan</div>
                </div>

                <div class="d-grid gap-3">
                    @foreach ($dashboardReportExportShortcuts as $shortcut)
                        <div class="report-export-shortcut-card">
                            <div class="inventory-title mb-2">{{ $shortcut['label'] }}</div>
                            <p class="inventory-meta mb-3">
                                Bulan dashboard: {{ $dashboardFilterDrawer['active_month'] }}
                            </p>

                            <div class="report-export-shortcut-actions">
                                <a href="{{ route($shortcut['pdf'], $dashboardExportQuery) }}" class="btn btn-sm btn-outline-danger fw-bold">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>
                                    PDF
                                </a>

                                <a href="{{ route($shortcut['excel'], $dashboardExportQuery) }}" class="btn btn-sm btn-outline-success fw-bold">
                                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                                    Excel
                                </a>

                                <a href="{{ route($shortcut['index'], $dashboardExportQuery) }}" class="btn btn-sm btn-outline-primary fw-bold">
                                    Buka
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </form>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-report-period-filter.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
