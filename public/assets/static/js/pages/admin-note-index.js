document.addEventListener('DOMContentLoaded', () => {
    const configNode = document.getElementById('admin-note-index-config');
    const searchForm = document.getElementById('admin-note-search-form');
    const searchInput = document.getElementById('admin-note-search-input');
    const dateRangeInput = document.getElementById('admin-note-date-range');
    const dateFromInput = document.getElementById('admin-note-date-from');
    const dateToInput = document.getElementById('admin-note-date-to');
    const lineStatusInput = document.getElementById('admin-note-line-status');
    const tableBody = document.getElementById('admin-note-table-body');
    const summaryNode = document.getElementById('admin-note-table-summary');
    const paginationNode = document.getElementById('admin-note-table-pagination');
    const filterForm = document.getElementById('admin-note-filter-form');
    const filterDrawer = document.getElementById('admin-note-filter-drawer');
    const filterBackdrop = document.getElementById('admin-note-filter-backdrop');
    const openFilterButton = document.getElementById('open-admin-note-filter');
    const closeFilterButton = document.getElementById('close-admin-note-filter');
    const resetFilterButton = document.getElementById('reset-admin-note-filter');
    const sortButtons = Array.from(document.querySelectorAll('[data-sort-by]'));

    if (
        !configNode
        || !searchForm
        || !searchInput
        || !dateRangeInput
        || !dateFromInput
        || !dateToInput
        || !lineStatusInput
        || !tableBody
        || !summaryNode
        || !paginationNode
        || !filterForm
        || !filterDrawer
        || !filterBackdrop
        || !openFilterButton
        || !closeFilterButton
        || !resetFilterButton
    ) {
        return;
    }

    let config = {};

    try {
        config = JSON.parse(configNode.textContent || '{}');
    } catch (_error) {
        config = {};
    }

    const endpoint = typeof config.endpoint === 'string' ? config.endpoint : '';
    const filters = typeof config.filters === 'object' && config.filters !== null
        ? config.filters
        : {};

    const normalize = (value) => String(value ?? '').trim();
    const allowedSortBy = new Set(['customer_name', 'created_at', 'total_rupiah', 'net_paid_rupiah', 'outstanding_rupiah']);
    const intOrDefault = (value, fallback) => {
        const parsed = Number.parseInt(String(value ?? ''), 10);
        return Number.isNaN(parsed) || parsed < 1 ? fallback : parsed;
    };

    const refreshDatePicker = () => {
        window.AdminDateInput?.refreshWithin(filterForm);
    };

    const stateFromUrl = () => {
        const params = new URLSearchParams(window.location.search);

        return {
            date_from: normalize(params.get('date_from') || filters.date_from),
            date_to: normalize(params.get('date_to') || filters.date_to),
            search: normalize(params.get('search') || filters.search),
            line_status: normalize(params.get('line_status') || filters.line_status),
            sort_by: normalize(params.get('sort_by') || filters.sort_by) === 'relevance'
                ? 'relevance'
                : (allowedSortBy.has(normalize(params.get('sort_by') || filters.sort_by)) ? normalize(params.get('sort_by') || filters.sort_by) : 'created_at'),
            sort_dir: normalize(params.get('sort_dir') || filters.sort_dir) === 'asc' ? 'asc' : 'desc',
            page: intOrDefault(params.get('page'), 1),
            per_page: intOrDefault(params.get('per_page'), 10),
        };
    };

    const state = stateFromUrl();

    let searchDebounceTimer = null;
    let requestCounter = 0;
    let activeController = null;

    const fillControlsFromState = () => {
        searchInput.value = state.search;
        dateFromInput.value = state.date_from;
        dateToInput.value = state.date_to;
        lineStatusInput.value = state.line_status;
        refreshDatePicker();
        document.querySelectorAll('[data-sort-indicator]').forEach((node) => {
            const active = node.dataset.sortIndicator === state.sort_by;
            node.textContent = active ? (state.sort_dir === 'asc' ? '↑' : '↓') : '↕';
            node.classList.toggle('text-muted', !active);
        });
    };

    const syncFilterState = () => {
        state.date_from = normalize(dateFromInput.value);
        state.date_to = normalize(dateToInput.value);
        state.line_status = normalize(lineStatusInput.value);
        state.page = 1;
    };

    const syncSearchState = () => {
        const value = normalize(searchInput.value);
        state.search = value.length >= 2 ? value : '';
        if (state.search !== '') {
            state.sort_by = 'relevance';
            state.sort_dir = 'asc';
        } else if (state.sort_by === 'relevance') {
            state.sort_by = 'created_at';
            state.sort_dir = 'desc';
        }
        state.page = 1;
    };

    const paramsObject = () => {
        const obj = {
            page: String(state.page),
            per_page: String(state.per_page),
            sort_dir: state.sort_dir,
        };

        if (state.sort_by !== 'relevance') obj.sort_by = state.sort_by;

        ['date_from', 'date_to', 'search', 'line_status'].forEach((key) => {
            const value = normalize(state[key]);
            if (value !== '') {
                obj[key] = value;
            }
        });

        return obj;
    };

    const paramsString = () => new URLSearchParams(paramsObject()).toString();

    const updateUrlState = (replace = false) => {
        const url = new URL(window.location.href);
        url.search = paramsString();

        if (replace) {
            window.history.replaceState(null, '', url);
            return;
        }

        window.history.pushState(null, '', url);
    };

    const drawOpen = (open) => {
        filterDrawer.classList.toggle('d-none', !open);
        filterBackdrop.classList.toggle('d-none', !open);
    };

    const renderLoading = () => {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">Sedang memuat daftar nota...</td>
            </tr>
        `;
        summaryNode.textContent = 'Memuat ringkasan daftar nota...';
        paginationNode.innerHTML = '<span class="text-muted small">Memuat pagination...</span>';
    };

    const renderError = () => {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-danger py-4">Daftar nota gagal dimuat.</td>
            </tr>
        `;
        summaryNode.textContent = 'Gagal memuat ringkasan daftar nota.';
        paginationNode.innerHTML = '<span class="text-muted small">Pagination belum tersedia.</span>';
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const renderAction = (item) => {
        if (typeof item.action_url === 'string' && item.action_url !== '') {
            return `<a href="${escapeHtml(item.action_url)}" class="btn btn-sm btn-outline-primary">Buka</a>`;
        }

        return '-';
    };

    const renderPager = (pagination) => {
        const page = Number.parseInt(String(pagination?.page ?? 1), 10) || 1;
        const lastPage = Number.parseInt(String(pagination?.last_page ?? 1), 10) || 1;

        if (lastPage <= 1) {
            paginationNode.innerHTML = '';
            return;
        }

        const start = Math.max(1, page - 2);
        const end = Math.min(lastPage, page + 2);

        let html = '<nav><ul class="pagination pagination-primary mb-0">';
        html += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;

        for (let p = start; p <= end; p += 1) {
            html += `<li class="page-item ${p === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
        }

        html += `<li class="page-item ${page === lastPage ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        html += '</ul></nav>';

        paginationNode.innerHTML = html;
    };

    const renderItems = (items, summaryLabel, pagination) => {
        if (!Array.isArray(items) || items.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        ${escapeHtml(summaryLabel || 'Belum ada data nota.')}
                    </td>
                </tr>
            `;
        } else {
            const page = Number.parseInt(String(pagination?.page ?? 1), 10) || 1;
            const perPage = Number.parseInt(String(pagination?.per_page ?? 10), 10) || 10;

            tableBody.innerHTML = items.map((item, index) => `
                <tr>
                    <td>${((page - 1) * perPage) + index + 1}</td>
                    <td class="fw-semibold">${escapeHtml(item.customer_name ?? 'Pelanggan')}</td>
                    <td>${escapeHtml(item.created_at_text ?? '-')}</td>
                    <td>${escapeHtml(item.line_summary_label ?? '-')}</td>
                    <td class="text-end">${escapeHtml(item.grand_total_text ?? '-')}</td>
                    <td class="text-end">${escapeHtml(item.total_paid_text ?? '-')}</td>
                    <td class="text-end">${escapeHtml(item.outstanding_text ?? '-')}</td>
                    <td class="text-center">${renderAction(item)}</td>
                </tr>
            `).join('');
        }

        const total = Number(pagination?.total || 0);
        const page = Number(pagination?.page || 1);
        const perPage = Number(pagination?.per_page || 10);
        const from = total === 0 ? 0 : ((page - 1) * perPage) + 1;
        const to = Math.min(page * perPage, total);
        summaryNode.textContent = `Menampilkan ${from} sampai ${to} dari ${total} nota`;
        renderPager(pagination);
    };

    const loadTable = async (replaceUrl = false) => {
        if (endpoint === '') {
            renderError();
            return;
        }

        activeController?.abort();
        const controller = new AbortController();
        activeController = controller;
        const currentRequest = ++requestCounter;
        renderLoading();

        const url = new URL(endpoint, window.location.origin);
        const params = paramsObject();

        Object.keys(params).forEach((key) => {
            url.searchParams.set(key, params[key]);
        });

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: controller.signal,
            });

            const payload = await response.json();

            if (currentRequest !== requestCounter) {
                return;
            }

            if (!response.ok || payload?.success !== true) {
                renderError();
                return;
            }

            const data = payload?.data ?? {};
            const items = Array.isArray(data.items) ? data.items : [];
            const summaryLabel = typeof data?.summary?.label === 'string'
                ? data.summary.label
                : 'Daftar nota siap.';
            const pagination = typeof data.pagination === 'object' && data.pagination !== null
                ? data.pagination
                : { page: 1, per_page: 10, total: 0, last_page: 1 };

            renderItems(items, summaryLabel, pagination);
            fillControlsFromState();
            updateUrlState(replaceUrl);
        } catch (error) {
            if (error?.name === 'AbortError' || currentRequest !== requestCounter) {
                return;
            }

            renderError();
        } finally {
            if (activeController === controller) activeController = null;
        }
    };

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        syncSearchState();
        loadTable();
    });

    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);

        const value = normalize(searchInput.value);

        if (value.length < 2) {
            syncSearchState();
            searchDebounceTimer = window.setTimeout(() => loadTable(), 160);
            return;
        }

        searchDebounceTimer = window.setTimeout(() => {
            syncSearchState();
            loadTable();
        }, 220);
    });

    openFilterButton.addEventListener('click', () => {
        fillControlsFromState();
        drawOpen(true);
    });

    closeFilterButton.addEventListener('click', () => drawOpen(false));
    filterBackdrop.addEventListener('click', () => drawOpen(false));

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        syncFilterState();
        drawOpen(false);
        loadTable();
    });

    resetFilterButton.addEventListener('click', () => {
        filterForm.reset();
        dateFromInput.value = normalize(filters.date_from);
        dateToInput.value = normalize(filters.date_to);
        lineStatusInput.value = '';
        refreshDatePicker();
        syncFilterState();
        drawOpen(false);
        loadTable();
    });

    sortButtons.forEach((button) => button.addEventListener('click', (event) => {
        const sortBy = normalize(button.dataset.sortBy);
        if (!allowedSortBy.has(sortBy)) return;
        event.preventDefault();
        state.sort_dir = state.sort_by === sortBy && state.sort_dir === 'asc' ? 'desc' : 'asc';
        state.sort_by = sortBy;
        state.page = 1;
        fillControlsFromState();
        loadTable();
    }));

    paginationNode.addEventListener('click', (event) => {
        const link = event.target.closest('[data-page]');
        if (!link || link.parentElement.classList.contains('disabled')) {
            return;
        }

        event.preventDefault();
        state.page = Number(link.dataset.page || 1);
        loadTable();
    });

    window.addEventListener('popstate', () => {
        Object.assign(state, stateFromUrl());
        fillControlsFromState();
        loadTable(true);
    });

    fillControlsFromState();
    loadTable(true);
});
