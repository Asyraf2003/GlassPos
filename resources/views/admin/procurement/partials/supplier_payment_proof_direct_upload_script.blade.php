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
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

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

        const postJson = async (url, body) => {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(body)
            });
            const payload = await response.json().catch(() => null);

            if (!response.ok || !payload?.success) {
                throw new Error(payload?.message || 'Bukti pembayaran gagal diproses.');
            }

            return payload.data;
        };

        const declarations = (files) => files.map((file) => ({
            original_filename: file.name,
            mime_type: declaredMime(file),
            file_size_bytes: file.size
        }));

        const validateFiles = (files) => {
            if (files.length < 1 || files.length > 3) {
                return 'Pilih 1 sampai 3 file bukti pembayaran.';
            }

            if (files.some((file) => file.size < 1 || file.size > 10485760)) {
                return 'Ukuran tiap bukti pembayaran harus lebih dari 0 dan maksimal 10 MB.';
            }

            if (files.some((file) => !allowed.has(declaredMime(file)))) {
                return 'Format bukti harus JPG, PNG, WEBP, HEIC, HEIF, atau PDF.';
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
            const error = validateFiles(files);

            if (error) {
                throw new Error(error);
            }

            const scopeId = String(form.dataset.scopeId || '').trim();

            if (!scopeId) {
                throw new Error('Scope bukti pembayaran tidak tersedia.');
            }

            setState(form, 'Menyiapkan upload aman...');
            const prepared = await postJson(form.dataset.prepareUrl, {
                scope_type: form.dataset.scopeType,
                scope_id: scopeId,
                idempotency_key: idempotencyKey(form),
                files: declarations(files)
            });

            if (prepared?.proof_status) {
                return;
            }

            if (!prepared?.upload_intent_id || prepared.files?.length !== files.length) {
                throw new Error('Otorisasi upload tidak lengkap. Silakan coba lagi.');
            }

            for (let index = 0; index < files.length; index += 1) {
                setState(form, `Mengunggah file ${index + 1} dari ${files.length} langsung ke penyimpanan privat...`);
                const response = await fetch(prepared.files[index].upload_url, {
                    method: 'PUT',
                    headers: prepared.files[index].headers || {},
                    body: files[index]
                });

                if (!response.ok) {
                    throw new Error('Upload langsung gagal. Silakan periksa koneksi dan coba lagi.');
                }
            }

            setState(form, 'Memverifikasi dan menyimpan bukti pembayaran...');
            const finalizeUrl = form.dataset.finalizeUrl.replace(
                '__INTENT__',
                encodeURIComponent(prepared.upload_intent_id)
            );
            await postJson(finalizeUrl, {});
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
                    setState(form, error instanceof Error ? error.message : 'Bukti pembayaran gagal diproses.', true);
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
