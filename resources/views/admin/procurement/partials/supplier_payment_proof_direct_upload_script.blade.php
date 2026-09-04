<script>
    (() => {
        const forms = document.querySelectorAll('[data-supplier-proof-direct-upload]');
        const allowed = new Set([
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/heic',
            'image/heif'
        ]);
        const byExtension = {
            jpg: 'image/jpeg',
            jpeg: 'image/jpeg',
            png: 'image/png',
            webp: 'image/webp',
            heic: 'image/heic',
            heif: 'image/heif',
            pdf: 'application/pdf'
        };
        const publicMessages = Object.freeze({
            appNetworkUnavailable: 'Aplikasi tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
            storageNetworkUnavailable: 'Penyimpanan privat tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
            storageUploadRejected: 'Penyimpanan privat menolak upload. Silakan coba lagi.',
            malformedPreparation: 'Respons persiapan upload tidak valid. Silakan coba lagi.',
            prepareFailed: 'Upload bukti pembayaran gagal disiapkan.',
            finalizeFailed: 'Upload bukti pembayaran gagal difinalisasi.',
            invalidFileCount: 'Pilih 1 sampai 3 file bukti pembayaran.',
            invalidFileSize: 'Ukuran tiap bukti pembayaran harus lebih dari 0 dan maksimal 10 MB.',
            invalidFileType: 'Format bukti harus JPG, PNG, WEBP, HEIC, HEIF, atau PDF.',
            missingScope: 'Scope bukti pembayaran tidak tersedia.',
            unknown: 'Bukti pembayaran gagal diproses. Silakan coba lagi.'
        });
        const trustedBackendMessages = new Set([
            'Upload bukti pembayaran gagal disiapkan.',
            'Upload bukti pembayaran tidak dapat difinalisasi.',
            'Upload bukti pembayaran gagal difinalisasi.'
        ]);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        class DirectUploadFailure {
            constructor(code, backendMessage = '') {
                this.code = code;
                this.backendMessage = trustedBackendMessages.has(backendMessage) ? backendMessage : '';
            }
        }

        const failure = (code, backendMessage = '') => new DirectUploadFailure(code, backendMessage);
        const publicMessageFor = (error) => {
            if (!(error instanceof DirectUploadFailure)) {
                return publicMessages.unknown;
            }

            return error.backendMessage || publicMessages[error.code] || publicMessages.unknown;
        };

        const declaredMime = (file) => {
            const browserMime = String(file.type || '').trim().toLowerCase();

            if (allowed.has(browserMime)) {
                return browserMime;
            }

            const extension = String(file.name || '').split('.').pop()?.toLowerCase() || '';

            return byExtension[extension] || '';
        };

        const idempotencyKey = (form) => {
            if (form.dataset.uploadIdempotencyKey) {
                return form.dataset.uploadIdempotencyKey;
            }

            const generated = window.crypto?.randomUUID?.()
                || `proof-${Date.now()}-${Math.random().toString(16).slice(2)}`;
            form.dataset.uploadIdempotencyKey = generated;

            return generated;
        };

        const postJson = async (url, body, stage) => {
            let response;

            try {
                response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(body)
                });
            } catch {
                throw failure('appNetworkUnavailable');
            }

            const payload = await response.json().catch(() => null);

            if (!payload || typeof payload !== 'object') {
                throw failure(stage === 'prepare' ? 'malformedPreparation' : 'finalizeFailed');
            }

            if (!response.ok || !payload?.success) {
                const message = typeof payload.message === 'string' ? payload.message : '';

                throw failure(stage === 'prepare' ? 'prepareFailed' : 'finalizeFailed', message);
            }

            return payload.data;
        };

        const declarations = (files) => files.map((file) => ({
            original_filename: file.name,
            mime_type: declaredMime(file),
            file_size_bytes: file.size
        }));
        const browserHeaders = (headers) => {
            if (Array.isArray(headers)) {
                return headers.length === 0 ? {} : null;
            }

            return headers && typeof headers === 'object' ? headers : null;
        };

        const validateFiles = (files) => {
            if (files.length < 1 || files.length > 3) {
                return 'invalidFileCount';
            }

            if (files.some((file) => file.size < 1 || file.size > 10485760)) {
                return 'invalidFileSize';
            }

            if (files.some((file) => !allowed.has(declaredMime(file)))) {
                return 'invalidFileType';
            }

            return '';
        };

        const setState = (form, message, failed = false) => {
            const status = form.querySelector('[data-direct-upload-status]');

            if (!status) {
                return;
            }

            status.textContent = message;
            status.classList.toggle('text-danger', failed);
            status.classList.toggle('text-muted', !failed);
        };

        const upload = async (form) => {
            const input = form.querySelector('input[type="file"]');
            const files = Array.from(input?.files || []);
            const validationFailure = validateFiles(files);

            if (validationFailure) {
                throw failure(validationFailure);
            }

            const scopeId = String(form.dataset.scopeId || '').trim();

            if (!scopeId) {
                throw failure('missingScope');
            }

            setState(form, 'Menyiapkan upload aman...');
            const prepared = await postJson(form.dataset.prepareUrl, {
                scope_type: form.dataset.scopeType,
                scope_id: scopeId,
                idempotency_key: idempotencyKey(form),
                files: declarations(files)
            }, 'prepare');

            if (prepared?.proof_status) {
                return;
            }

            const preparedFilesAreValid = Array.isArray(prepared?.files)
                && prepared.files.length === files.length
                && prepared.files.every((file) => typeof file?.upload_url === 'string'
                    && file.upload_url.startsWith('https://')
                    && browserHeaders(file.headers) !== null);

            if (!prepared?.upload_intent_id || !preparedFilesAreValid) {
                throw failure('malformedPreparation');
            }

            for (let index = 0; index < files.length; index += 1) {
                setState(form, `Mengunggah file ${index + 1} dari ${files.length} langsung ke penyimpanan privat...`);
                let response;

                try {
                    response = await fetch(prepared.files[index].upload_url, {
                        method: 'PUT',
                        headers: browserHeaders(prepared.files[index].headers),
                        body: files[index]
                    });
                } catch {
                    throw failure('storageNetworkUnavailable');
                }

                if (!response.ok) {
                    throw failure('storageUploadRejected');
                }
            }

            setState(form, 'Memverifikasi dan menyimpan bukti pembayaran...');
            const finalizeTemplate = String(form.dataset.finalizeUrl || '');

            if (!finalizeTemplate.includes('__INTENT__')) {
                throw failure('finalizeFailed');
            }

            const finalizeUrl = finalizeTemplate.replace(
                '__INTENT__',
                encodeURIComponent(prepared.upload_intent_id)
            );
            await postJson(finalizeUrl, {}, 'finalize');
        };

        forms.forEach((form) => {
            const input = form.querySelector('input[type="file"]');
            const button = form.querySelector('[data-direct-upload-submit]');
            const defaultLabel = button?.textContent?.trim() || 'Kirim Bukti';

            input?.addEventListener('change', () => delete form.dataset.uploadIdempotencyKey);
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (!form.checkValidity() || form.dataset.uploading === '1') {
                    form.reportValidity();
                    return;
                }

                form.dataset.uploading = '1';

                if (button) {
                    button.disabled = true;
                    button.textContent = button.dataset.submittingLabel || 'Mengirim...';
                }

                try {
                    await upload(form);
                    setState(form, 'Bukti pembayaran berhasil disimpan.');
                    window.location.assign(form.dataset.successUrl || window.location.href);
                } catch (error) {
                    setState(form, publicMessageFor(error), true);
                    form.dataset.uploading = '0';

                    if (button) {
                        button.disabled = false;
                        button.textContent = defaultLabel;
                    }
                }
            });
        });
    })();
</script>
