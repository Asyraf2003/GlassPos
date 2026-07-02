<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-start">
            <div class="{{ ! empty($exportActions ?? []) ? 'col-12 col-xl-7' : 'col-12 col-xl-9' }}">
                <div class="border rounded p-3 bg-light-subtle mb-0">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex flex-column flex-md-row gap-3">
                            <div>
                                <div class="text-muted small">Mode Aktif</div>
                                <div class="fw-semibold">
                                    @if (($filters['period_mode'] ?? 'monthly') === 'custom')
                                        Custom
                                    @elseif (($filters['period_mode'] ?? 'monthly') === 'weekly')
                                        Mingguan
                                    @elseif (($filters['period_mode'] ?? 'monthly') === 'monthly')
                                        Bulanan
                                    @else
                                        Harian
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="text-muted small">Dasar Tanggal</div>
                                <div class="fw-semibold">{{ $basisDateLabel ?? 'Tanggal referensi laporan' }}</div>
                            </div>

                            <div>
                                <div class="text-muted small">
                                    @if ($useReportPeriodContext ?? false)
                                        {{ \App\Support\ReportPeriodDateLabelFormatter::label($filters['date_from'] ?? null, $filters['date_to'] ?? null) }}
                                    @else
                                        {{ $rangeLabelText ?? 'Rentang Aktif' }}
                                    @endif
                                </div>
                                <div class="fw-semibold">
                                    @if ($useReportPeriodContext ?? false)
                                        {{ \App\Support\ReportPeriodDateLabelFormatter::value($filters['date_from'] ?? null, $filters['date_to'] ?? null) }}
                                    @else
                                        {{ \App\Support\ViewDateFormatter::range($filters['date_from'] ?? null, $filters['date_to'] ?? null) }}
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="{{ ! empty($exportActions ?? []) ? 'col-12 col-xl-5' : 'col-12 col-xl-3' }}">
                @if (! empty($exportActions ?? []))
                    <div class="row g-2">
                        <div class="{{ count($exportActions ?? []) === 1 ? 'col-12 col-sm-4' : 'col-6' }} d-grid">
                            <button
                                type="button"
                                id="{{ $formId }}-open-filter"
                                class="btn btn-primary text-nowrap w-100"
                            >
                                Filter
                            </button>
                        </div>

                        <div class="{{ count($exportActions ?? []) === 1 ? 'col-12 col-sm-4' : 'col-6' }} d-grid">
                            <a href="{{ $resetUrl }}" class="btn btn-outline-secondary text-nowrap w-100">
                                Atur Ulang
                            </a>
                        </div>

                        @foreach (($exportActions ?? []) as $exportAction)
                            <div class="{{ count($exportActions ?? []) === 1 ? 'col-12 col-sm-4' : 'col-6' }} d-grid">
                                <a
                                    href="{{ $exportAction['url'] }}"
                                    class="{{ trim(($exportAction['class'] ?? 'btn btn-outline-secondary text-nowrap') . ' w-100') }}"
                                >
                                    {{ $exportAction['label'] }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
                        <button
                            type="button"
                            id="{{ $formId }}-open-filter"
                            class="btn btn-primary text-nowrap"
                        >
                            Filter
                        </button>

                        <a href="{{ $resetUrl }}" class="btn btn-outline-secondary text-nowrap">
                            Atur Ulang
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div
    id="{{ $formId }}-filter-backdrop"
    class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-25 d-none"
    style="z-index: 1040;"
></div>

<div
    id="{{ $formId }}-filter-drawer"
    class="position-fixed top-0 end-0 h-100 bg-body border-start shadow d-none"
    style="width: 360px; z-index: 1050; overflow-y: auto;"
>
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Filter Laporan</h5>
            <button
                type="button"
                id="{{ $formId }}-close-filter"
                class="btn btn-sm btn-light-secondary"
            >
                Tutup
            </button>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="get"
            action="{{ $action }}"
            id="{{ $formId }}"
            data-report-period-filter="1"
            data-filter-open-button-id="{{ $formId }}-open-filter"
            data-filter-close-button-id="{{ $formId }}-close-filter"
            data-filter-drawer-id="{{ $formId }}-filter-drawer"
            data-filter-backdrop-id="{{ $formId }}-filter-backdrop"
            class="d-grid gap-3"
        >
            <div class="form-group">
                <label for="{{ $formId }}-period-mode" class="form-label">Mode Periode</label>
                <select
                    name="period_mode"
                    id="{{ $formId }}-period-mode"
                    class="form-select"
                    data-report-period-mode-select
                >
                    <option value="daily" {{ ($filters['period_mode'] ?? 'monthly') === 'daily' ? 'selected' : '' }}>Harian</option>
                    <option value="weekly" {{ ($filters['period_mode'] ?? 'monthly') === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                    <option value="monthly" {{ ($filters['period_mode'] ?? 'monthly') === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    @if (($supportsCustomRange ?? false))
                        <option value="custom" {{ ($filters['period_mode'] ?? 'monthly') === 'custom' ? 'selected' : '' }}>Custom</option>
                    @endif
                </select>
            </div>

            <div class="form-group" data-report-reference-group>
                <label for="{{ $formId }}-reference-date" class="form-label">Tanggal Referensi</label>
                <input
                    type="date"
                    name="reference_date"
                    id="{{ $formId }}-reference-date"
                    class="form-control"
                    value="{{ $filters['reference_date'] }}"
                    data-ui-date="single"
                    data-ui-date-placeholder="Pilih tanggal referensi"
                    data-report-reference-input
                    autocomplete="off"
                >
            </div>

            @if (($supportsCustomRange ?? false))
                <div class="form-group">
                    <label for="{{ $formId }}-date-from" class="form-label">Tanggal Mulai Custom</label>
                    <input
                        type="date"
                        name="date_from"
                        id="{{ $formId }}-date-from"
                        class="form-control"
                        value="{{ $filters['date_from'] ?? '' }}"
                        data-ui-date="single"
                        data-ui-date-placeholder="Pilih tanggal mulai"
                        autocomplete="off"
                    >
                </div>

                <div class="form-group">
                    <label for="{{ $formId }}-date-to" class="form-label">Tanggal Akhir Custom</label>
                    <input
                        type="date"
                        name="date_to"
                        id="{{ $formId }}-date-to"
                        class="form-control"
                        value="{{ $filters['date_to'] ?? '' }}"
                        data-ui-date="single"
                        data-ui-date-placeholder="Pilih tanggal akhir"
                        autocomplete="off"
                    >
                </div>

                <div class="small text-muted">
                    Tanggal mulai dan akhir hanya wajib untuk mode custom.
                </div>
            @endif

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                <a href="{{ $resetUrl }}" class="btn btn-light-secondary">Atur Ulang</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/static/js/shared/admin-report-period-filter.js') }}?v={{ config('app.asset_version') }}"></script>
@endpush
