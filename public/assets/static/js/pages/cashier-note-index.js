document.addEventListener('DOMContentLoaded', () => {
    const configNode = document.getElementById('cashier-note-index-config');
    const searchForm = document.getElementById('cashier-note-search-form');
    const searchInput = document.getElementById('cashier-note-search-input');
    const list = document.getElementById('cashier-note-list');
    const summary = document.getElementById('cashier-note-table-summary');
    const pagination = document.getElementById('cashier-note-table-pagination');
    const bucketButtons = Array.from(document.querySelectorAll('[data-history-bucket]'));

    if (!configNode || !searchForm || !searchInput || !list || !summary || !pagination || bucketButtons.length !== 2) return;

    let config = {};
    try {
        config = JSON.parse(configNode.textContent || '{}');
    } catch (_error) {
        config = {};
    }

    const endpoint = typeof config.endpoint === 'string' ? config.endpoint : '';
    const filters = typeof config.filters === 'object' && config.filters !== null ? config.filters : {};
    const clean = (value) => String(value ?? '').trim();
    const validBucket = (value) => clean(value) === 'completed' ? 'completed' : 'unfinished';
    const pageNumber = (value) => Math.max(Number.parseInt(String(value || '1'), 10) || 1, 1);
    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const stateFromUrl = () => {
        const params = new URLSearchParams(window.location.search);
        return {
            search: clean(params.get('search') || filters.search),
            bucket: validBucket(params.get('bucket') || filters.bucket),
            page: pageNumber(params.get('page')),
            per_page: 10,
        };
    };

    const state = stateFromUrl();
    let requestCounter = 0;
    let debounceTimer = null;

    const fillControls = () => {
        searchInput.value = state.search;
        bucketButtons.forEach((button) => {
            button.setAttribute('aria-pressed', button.dataset.historyBucket === state.bucket ? 'true' : 'false');
        });
    };

    const requestParams = () => {
        const params = new URLSearchParams({
            bucket: state.bucket,
            page: String(state.page),
            per_page: String(state.per_page),
        });
        if (state.search !== '') params.set('search', state.search);
        return params;
    };

    const updateUrl = (replace = false) => {
        const url = new URL(window.location.href);
        url.search = requestParams().toString();
        window.history[replace ? 'replaceState' : 'pushState'](null, '', url);
    };

    const renderState = (message, error = false) => {
        list.innerHTML = `<div class="cashier-note-list-state${error ? ' is-error' : ''}">${escapeHtml(message)}</div>`;
    };

    const badge = (label, domain = false) => label
        ? `<span class="cashier-note-badge${domain ? ' is-domain' : ''}">${escapeHtml(label)}</span>`
        : '';

    const renderActions = (item) => {
        const detail = typeof item.detail_url === 'string' && item.detail_url !== ''
            ? `<a class="btn btn-sm btn-outline-primary" data-history-detail href="${escapeHtml(item.detail_url)}">Detail</a>`
            : '';
        const edit = item.can_edit === true && typeof item.edit_url === 'string' && item.edit_url !== ''
            ? `<a class="btn btn-sm btn-primary" data-history-edit href="${escapeHtml(item.edit_url)}">Edit</a>`
            : '';
        return detail + edit;
    };

    const renderItems = (items) => {
        if (!Array.isArray(items) || items.length === 0) {
            renderState(state.bucket === 'completed' ? 'Belum ada nota selesai.' : 'Tidak ada nota yang perlu ditangani.');
            return;
        }

        list.innerHTML = items.map((item) => `
            <article class="cashier-note-card" data-history-note-id="${escapeHtml(item.note_id)}">
                <div class="cashier-note-card-primary">
                    <strong>${escapeHtml(item.customer_name || 'Pelanggan baru')}</strong>
                    <span class="cashier-note-card-meta">${escapeHtml(item.transaction_at_text || item.transaction_date || '-')} · ${escapeHtml(item.note_number || '-')}</span>
                    <div class="cashier-note-card-badges">
                        ${badge(item.focus_status_label)}
                        ${badge(item.payment_status_label)}
                        ${badge(item.domain_status_label, true)}
                    </div>
                </div>
                <div class="cashier-note-card-money">
                    <strong>${escapeHtml(item.grand_total_text || '-')}</strong>
                    <span>Sisa ${escapeHtml(item.outstanding_text || '-')}</span>
                </div>
                <div class="cashier-note-card-context">
                    ${escapeHtml(item.line_summary_label || '-')}
                    <span>${escapeHtml(item.work_status_label || '-')}</span>
                </div>
                <div class="cashier-note-card-actions">${renderActions(item)}</div>
            </article>
        `).join('');
    };

    const renderPager = (data) => {
        const current = pageNumber(data?.page);
        const last = pageNumber(data?.last_page);
        if (last <= 1) {
            pagination.replaceChildren();
            return;
        }

        const pages = [];
        for (let page = Math.max(1, current - 2); page <= Math.min(last, current + 2); page += 1) {
            pages.push(`<li class="page-item${page === current ? ' active' : ''}"><button type="button" class="page-link" data-page="${page}">${page}</button></li>`);
        }
        pagination.innerHTML = `<nav aria-label="Halaman riwayat"><ul class="pagination pagination-primary">
            <li class="page-item${current === 1 ? ' disabled' : ''}"><button type="button" class="page-link" data-page="${current - 1}" aria-label="Sebelumnya">‹</button></li>
            ${pages.join('')}
            <li class="page-item${current === last ? ' disabled' : ''}"><button type="button" class="page-link" data-page="${current + 1}" aria-label="Berikutnya">›</button></li>
        </ul></nav>`;
    };

    const load = async (replaceUrl = false) => {
        if (endpoint === '') {
            renderState('Riwayat nota gagal dimuat.', true);
            return;
        }

        const request = ++requestCounter;
        list.setAttribute('aria-busy', 'true');
        renderState('Memuat riwayat nota...');

        try {
            const url = new URL(endpoint, window.location.origin);
            url.search = requestParams().toString();
            const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const payload = await response.json();
            if (request !== requestCounter) return;
            if (!response.ok || payload?.success !== true) throw new Error('history-request-failed');

            const data = payload.data || {};
            renderItems(data.items || []);
            renderPager(data.pagination || {});
            summary.textContent = data?.summary?.label || 'Riwayat nota siap.';
            list.setAttribute('aria-busy', 'false');
            fillControls();
            updateUrl(replaceUrl);
        } catch (_error) {
            if (request !== requestCounter) return;
            renderState('Riwayat nota gagal dimuat.', true);
            summary.textContent = 'Gagal memuat riwayat nota.';
            pagination.replaceChildren();
            list.setAttribute('aria-busy', 'false');
        }
    };

    bucketButtons.forEach((button) => button.addEventListener('click', () => {
        const bucket = validBucket(button.dataset.historyBucket);
        if (bucket === state.bucket) return;
        state.bucket = bucket;
        state.page = 1;
        fillControls();
        void load();
    }));

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        state.search = clean(searchInput.value);
        state.page = 1;
        void load();
    });

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const value = clean(searchInput.value);
        if (value.length === 1) return;
        debounceTimer = window.setTimeout(() => {
            state.search = value;
            state.page = 1;
            void load();
        }, value === '' ? 200 : 300);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        searchInput.value = '';
        state.search = '';
        state.page = 1;
        void load();
    });

    pagination.addEventListener('click', (event) => {
        const button = event.target.closest('[data-page]');
        if (!button || button.closest('.page-item')?.classList.contains('disabled')) return;
        state.page = pageNumber(button.dataset.page);
        void load();
    });

    window.addEventListener('popstate', () => {
        Object.assign(state, stateFromUrl());
        fillControls();
        void load(true);
    });

    fillControls();
    void load(true);
});
