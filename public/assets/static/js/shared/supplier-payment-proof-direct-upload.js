(() => {
  const forms = Array.from(document.querySelectorAll('[data-supplier-proof-direct-upload]'));
  if (!forms.length) return;

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
    cameraUnsupported: 'Kamera tidak didukung oleh browser ini. Gunakan pilihan file atau galeri.',
    cameraPermissionDenied: 'Izin kamera ditolak. Izinkan akses kamera atau gunakan pilihan file.',
    cameraNotFound: 'Kamera tidak ditemukan pada perangkat ini.',
    cameraUnavailable: 'Kamera sedang tidak dapat digunakan. Tutup aplikasi lain yang memakai kamera lalu coba lagi.',
    cameraCaptureFailed: 'Foto dari kamera gagal diambil. Silakan coba lagi.',
    unknown: 'Bukti pembayaran gagal diproses. Silakan coba lagi.'
  });
  const trustedBackendMessages = new Set([
    'Upload bukti pembayaran gagal disiapkan.',
    'Upload bukti pembayaran tidak dapat difinalisasi.',
    'Upload bukti pembayaran gagal difinalisasi.'
  ]);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const cameraStates = new WeakMap();

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
    if (allowed.has(browserMime)) return browserMime;

    const extension = String(file.name || '').split('.').pop()?.toLowerCase() || '';
    return byExtension[extension] || '';
  };

  const resetIdempotency = (form) => delete form.dataset.uploadIdempotencyKey;
  const idempotencyKey = (form) => {
    if (form.dataset.uploadIdempotencyKey) return form.dataset.uploadIdempotencyKey;

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
          Accept: 'application/json',
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
    if (Array.isArray(headers)) return headers.length === 0 ? {} : null;
    return headers && typeof headers === 'object' ? headers : null;
  };

  const validateFiles = (files) => {
    if (files.length < 1 || files.length > 3) return 'invalidFileCount';
    if (files.some((file) => file.size < 1 || file.size > 10485760)) return 'invalidFileSize';
    if (files.some((file) => !allowed.has(declaredMime(file)))) return 'invalidFileType';
    return '';
  };

  const setState = (form, message, failed = false) => {
    const status = form.querySelector('[data-direct-upload-status]');
    if (!status) return;

    status.textContent = message;
    status.classList.toggle('text-danger', failed);
    status.classList.toggle('text-muted', !failed);
  };

  const cameraState = (form) => {
    if (!cameraStates.has(form)) {
      cameraStates.set(form, { files: [], stream: null, root: null, video: null, preview: null, status: null });
    }
    return cameraStates.get(form);
  };

  const selectedFiles = (form) => {
    const input = form.querySelector('input[type="file"]');
    return [...Array.from(input?.files || []), ...cameraState(form).files];
  };

  const setCameraStatus = (form, message, failed = false) => {
    const status = cameraState(form).status;
    if (!status) return;

    status.textContent = message;
    status.classList.toggle('text-danger', failed);
    status.classList.toggle('text-muted', !failed);
  };

  const stopCamera = (form) => {
    const state = cameraState(form);
    state.stream?.getTracks?.().forEach((track) => track.stop());
    state.stream = null;
    if (state.video) {
      state.video.pause?.();
      state.video.srcObject = null;
    }
    state.root?.querySelector('[data-camera-live]')?.classList.add('d-none');
  };

  const renderCameraFiles = (form) => {
    const state = cameraState(form);
    if (!state.preview) return;

    state.preview.replaceChildren();
    state.files.forEach((file, index) => {
      const row = document.createElement('div');
      row.className = 'd-flex align-items-center justify-content-between gap-2 border rounded px-2 py-2';

      const label = document.createElement('span');
      label.className = 'small text-truncate';
      label.textContent = file.name;

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn btn-sm btn-outline-danger';
      remove.textContent = 'Hapus';
      remove.dataset.cameraRemove = String(index);

      row.append(label, remove);
      state.preview.append(row);
    });
  };

  const cameraFailureCode = (error) => {
    const name = String(error?.name || '');
    if (name === 'NotAllowedError' || name === 'SecurityError') return 'cameraPermissionDenied';
    if (name === 'NotFoundError' || name === 'DevicesNotFoundError') return 'cameraNotFound';
    if (name === 'NotReadableError' || name === 'TrackStartError' || name === 'AbortError') return 'cameraUnavailable';
    return 'cameraUnavailable';
  };

  const startCamera = async (form) => {
    const state = cameraState(form);
    if (!navigator.mediaDevices?.getUserMedia) {
      setCameraStatus(form, publicMessages.cameraUnsupported, true);
      return;
    }

    if (state.stream) return;

    try {
      state.stream = await navigator.mediaDevices.getUserMedia({
        audio: false,
        video: { facingMode: { ideal: 'environment' } }
      });
      state.video.srcObject = state.stream;
      await state.video.play();
      state.root.querySelector('[data-camera-live]')?.classList.remove('d-none');
      setCameraStatus(form, 'Kamera aktif. Arahkan ke bukti pembayaran lalu ambil foto.');
    } catch (error) {
      stopCamera(form);
      setCameraStatus(form, publicMessages[cameraFailureCode(error)], true);
    }
  };

  const canvasBlob = (canvas) => new Promise((resolve) => {
    canvas.toBlob(resolve, 'image/jpeg', 0.9);
  });

  const captureCamera = async (form) => {
    const state = cameraState(form);
    if (selectedFiles(form).length >= 3) {
      setCameraStatus(form, publicMessages.invalidFileCount, true);
      return;
    }

    const video = state.video;
    if (!state.stream || !video?.videoWidth || !video?.videoHeight) {
      setCameraStatus(form, publicMessages.cameraCaptureFailed, true);
      return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext('2d');
    if (!context) {
      setCameraStatus(form, publicMessages.cameraCaptureFailed, true);
      return;
    }

    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    const blob = await canvasBlob(canvas);
    if (!(blob instanceof Blob) || blob.size < 1) {
      setCameraStatus(form, publicMessages.cameraCaptureFailed, true);
      return;
    }

    const file = new File([blob], `bukti-kamera-${Date.now()}.jpg`, {
      type: 'image/jpeg',
      lastModified: Date.now()
    });
    state.files.push(file);
    resetIdempotency(form);
    renderCameraFiles(form);
    stopCamera(form);
    setCameraStatus(form, 'Foto kamera siap dikirim bersama bukti pembayaran.');
  };

  const resetCameraFiles = (form) => {
    const state = cameraState(form);
    state.files = [];
    resetIdempotency(form);
    renderCameraFiles(form);
  };

  const installCamera = (form) => {
    const input = form.querySelector('input[type="file"]');
    if (!input || form.querySelector('[data-direct-upload-camera]')) return;

    input.required = false;

    const root = document.createElement('div');
    root.className = 'border rounded p-3 mt-3 bg-light-subtle';
    root.dataset.directUploadCamera = '';
    root.innerHTML = `
      <div class="d-flex flex-wrap gap-2 mb-2">
        <button type="button" class="btn btn-outline-primary" data-camera-start>Gunakan Kamera</button>
        <button type="button" class="btn btn-outline-secondary d-none" data-camera-close>Tutup Kamera</button>
      </div>
      <div class="d-none mb-3" data-camera-live>
        <video class="w-100 rounded border bg-dark" style="max-height: 320px; object-fit: contain;" autoplay playsinline muted></video>
        <button type="button" class="btn btn-primary mt-2" data-camera-capture>Ambil Foto</button>
      </div>
      <div class="small text-muted mb-2" data-camera-status aria-live="polite">Kamera bersifat opsional. File atau galeri tetap dapat digunakan.</div>
      <div class="d-flex flex-column gap-2" data-camera-preview></div>
    `;
    input.insertAdjacentElement('afterend', root);

    const state = cameraState(form);
    state.root = root;
    state.video = root.querySelector('video');
    state.preview = root.querySelector('[data-camera-preview]');
    state.status = root.querySelector('[data-camera-status]');

    root.querySelector('[data-camera-start]')?.addEventListener('click', async () => {
      await startCamera(form);
      root.querySelector('[data-camera-close]')?.classList.toggle('d-none', !state.stream);
    });
    root.querySelector('[data-camera-close]')?.addEventListener('click', () => {
      stopCamera(form);
      root.querySelector('[data-camera-close]')?.classList.add('d-none');
      setCameraStatus(form, 'Kamera ditutup. Foto yang sudah diambil tetap dipilih.');
    });
    root.querySelector('[data-camera-capture]')?.addEventListener('click', async () => {
      await captureCamera(form);
      root.querySelector('[data-camera-close]')?.classList.add('d-none');
    });
    root.addEventListener('click', (event) => {
      const button = event.target.closest('[data-camera-remove]');
      if (!button) return;
      const index = Number.parseInt(button.dataset.cameraRemove || '', 10);
      if (Number.isNaN(index) || !state.files[index]) return;
      state.files.splice(index, 1);
      resetIdempotency(form);
      renderCameraFiles(form);
      setCameraStatus(form, 'Foto kamera dihapus dari pilihan.');
    });

    form.closest('.modal')?.addEventListener('hidden.bs.modal', () => {
      stopCamera(form);
      resetCameraFiles(form);
      root.querySelector('[data-camera-close]')?.classList.add('d-none');
      setCameraStatus(form, 'Kamera bersifat opsional. File atau galeri tetap dapat digunakan.');
    });
  };

  const installActionModalHandoffGuard = () => {
    const actionModal = document.getElementById('procurement-action-modal');
    if (!actionModal || !window.bootstrap?.Modal) return;

    const targets = ['procurement-payment-modal', 'procurement-void-modal']
      .map((id) => document.getElementById(id))
      .filter(Boolean);
    if (!targets.length) return;

    let actionHiding = false;
    let pendingTarget = null;

    actionModal.addEventListener('hide.bs.modal', () => {
      actionHiding = true;
    });
    actionModal.addEventListener('hidden.bs.modal', () => {
      actionHiding = false;
      if (!pendingTarget) return;

      const target = pendingTarget;
      pendingTarget = null;
      window.bootstrap.Modal.getOrCreateInstance(target).show();
    });

    targets.forEach((target) => {
      target.addEventListener('show.bs.modal', (event) => {
        if (!actionHiding) return;
        event.preventDefault();
        pendingTarget = target;
      });
    });
  };

  const upload = async (form) => {
    const files = selectedFiles(form);
    const validationFailure = validateFiles(files);
    if (validationFailure) throw failure(validationFailure);

    const scopeId = String(form.dataset.scopeId || '').trim();
    if (!scopeId) throw failure('missingScope');

    setState(form, 'Menyiapkan upload aman...');
    const prepared = await postJson(form.dataset.prepareUrl, {
      scope_type: form.dataset.scopeType,
      scope_id: scopeId,
      idempotency_key: idempotencyKey(form),
      files: declarations(files)
    }, 'prepare');

    if (prepared?.proof_status) return;

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

      if (!response.ok) throw failure('storageUploadRejected');
    }

    setState(form, 'Memverifikasi dan menyimpan bukti pembayaran...');
    const finalizeTemplate = String(form.dataset.finalizeUrl || '');
    if (!finalizeTemplate.includes('__INTENT__')) throw failure('finalizeFailed');

    const finalizeUrl = finalizeTemplate.replace('__INTENT__', encodeURIComponent(prepared.upload_intent_id));
    await postJson(finalizeUrl, {}, 'finalize');
  };

  installActionModalHandoffGuard();

  forms.forEach((form) => {
    const input = form.querySelector('input[type="file"]');
    const button = form.querySelector('[data-direct-upload-submit]');
    const defaultLabel = button?.textContent?.trim() || 'Kirim Bukti';

    installCamera(form);
    input?.addEventListener('change', () => resetIdempotency(form));

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
        stopCamera(form);
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

  window.addEventListener('pagehide', () => forms.forEach((form) => stopCamera(form)));
})();
