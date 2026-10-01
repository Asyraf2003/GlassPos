(function () {
    const root = document.querySelector('[data-cashier-dashboard]');

    if (!root) {
        return;
    }

    const endpoint = root.dataset.productLookupEndpoint || '';
    const input = root.querySelector('[data-product-search-input]');
    const results = root.querySelector('[data-product-search-results]');
    const status = root.querySelector('[data-search-status]');

    if (!endpoint || !input || !results) {
        return;
    }

    const searchGate = window.LiveSearch.create();
    let debounceTimer = null;
    let lastQuery = '';

    function formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID').format(Number(amount || 0));
    }

    function stockBadgeClass(stock) {
        if (stock <= 0) {
            return 'is-empty';
        }

        if (stock <= 5) {
            return 'is-low';
        }

        return 'is-good';
    }

    function stockBadgeLabel(stock) {
        if (stock <= 0) {
            return 'Stok habis';
        }

        if (stock <= 5) {
            return 'Stok tipis';
        }

        return 'Stok tersedia';
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setStatus(message) {
        if (!status) {
            return;
        }

        status.innerHTML = '<span>' + escapeHtml(message) + '</span>';
    }

    function setLoadingStatus() {
        if (!status) {
            return;
        }

        status.innerHTML = ''
            + '<span class="spinner-border spinner-border-sm cashier-search-spinner" aria-hidden="true"></span>'
            + '<span>Mencari produk...</span>';
    }

    function renderInitialState() {
        results.innerHTML = ''
            + '<div class="cashier-empty-state">'
            + '    <div class="fw-semibold mb-1">Belum ada pencarian</div>'
            + '    <div class="cashier-section-desc small mb-0">'
            + '        Ketik minimal 2 karakter untuk melihat harga jual dan stok produk yang tersedia.'
            + '    </div>'
            + '</div>';

        setStatus('Siap digunakan');
    }

    function renderMinimumQueryState() {
        results.innerHTML = ''
            + '<div class="cashier-empty-state">'
            + '    <div class="fw-semibold mb-1">Lanjutkan mengetik</div>'
            + '    <div class="cashier-section-desc small mb-0">'
            + '        Sistem mulai mencari saat kata kunci mencapai minimal 2 karakter.'
            + '    </div>'
            + '</div>';

        setStatus('Menunggu minimal 2 karakter');
    }

    function renderErrorState() {
        results.innerHTML = ''
            + '<div class="cashier-empty-state">'
            + '    <div class="fw-semibold mb-1">Gagal memuat data produk</div>'
            + '    <div class="cashier-section-desc small mb-0">'
            + '        Coba ketik ulang atau reset pencarian. Jika tetap gagal, periksa endpoint lookup.'
            + '    </div>'
            + '</div>';

        setStatus('Terjadi kesalahan');
    }

    function renderEmptyResult(query) {
        results.innerHTML = ''
            + '<div class="cashier-empty-state">'
            + '    <div class="fw-semibold mb-1">Produk tidak ditemukan</div>'
            + '    <div class="cashier-section-desc small mb-0">'
            + '        Tidak ada produk dengan stok tersedia untuk kata kunci "<strong>' + escapeHtml(query) + '</strong>".'
            + '    </div>'
            + '</div>';

        setStatus('0 produk ditemukan');
    }

    function renderRows(rows) {
        const html = rows.map(function (row) {
            const stock = Number(row.available_stock || 0);
            const price = Number(row.default_unit_price_rupiah || 0);

            return ''
                + '<div class="cashier-result-item">'
                + '    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">'
                + '        <div class="flex-grow-1">'
                + '            <h6 class="cashier-result-title mb-1">' + escapeHtml(row.label || '-') + '</h6>'
                + '            <div class="cashier-result-meta">ID Produk: ' + escapeHtml(row.id || '-') + '</div>'
                + '        </div>'
                + '        <div class="text-lg-end">'
                + '            <div class="cashier-result-price mb-2">Rp ' + formatRupiah(price) + '</div>'
                + '            <span class="cashier-result-stock ' + stockBadgeClass(stock) + '">'
                + '                <i class="bi bi-box-seam"></i>'
                + '                ' + escapeHtml(stockBadgeLabel(stock)) + ' · ' + escapeHtml(stock) + ''
                + '            </span>'
                + '        </div>'
                + '    </div>'
                + '</div>';
        }).join('');

        results.innerHTML = html;
        setStatus(rows.length + ' produk ditemukan');
    }

    async function runSearch(query) {
        if (query.length < 2) {
            lastQuery = '';
            searchGate.invalidate();
            renderMinimumQueryState();
            return;
        }

        if (query === lastQuery) {
            return;
        }

        lastQuery = query;

        const request = searchGate.begin();
        setLoadingStatus();

        results.innerHTML = ''
            + '<div class="cashier-empty-state">'
            + '    <div class="fw-semibold mb-1">Mencari produk...</div>'
            + '    <div class="cashier-section-desc small mb-0">'
            + '        Sistem sedang mengambil harga jual dan stok tersedia.'
            + '    </div>'
            + '</div>';

        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('q', query);

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                signal: request.signal
            });

            if (!response.ok) {
                throw new Error('Lookup request failed');
            }

            const payload = await response.json();
            if (!request.isCurrent()) return;
            const rows = payload && payload.data && Array.isArray(payload.data.rows)
                ? payload.data.rows
                : [];

            if (rows.length === 0) {
                renderEmptyResult(query);
                return;
            }

            renderRows(rows);
        } catch (error) {
            if (!request.isCurrent() || (error && error.name === 'AbortError')) {
                return;
            }

            renderErrorState();
        } finally { request.finish(); }
    }

    function scheduleSearch() {
        const query = input.value.trim();

        clearTimeout(debounceTimer);
        searchGate.invalidate();
        lastQuery = "";
        if (query.length < 2) { renderMinimumQueryState(); return; }
        debounceTimer = setTimeout(function () {
            runSearch(query);
        }, 220);
    }

    function resetSearch() {
        clearTimeout(debounceTimer);

        searchGate.invalidate();

        lastQuery = '';
        input.value = '';
        input.focus();
        renderInitialState();
    }

    input.addEventListener('input', function () {
        const query = input.value.trim();

        if (query.length === 0) {
            resetSearch();
            return;
        }

        scheduleSearch();
    });

    renderInitialState();
})();
