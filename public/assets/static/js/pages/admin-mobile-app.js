(() => {
  const card = document.querySelector('[data-admin-mobile-app]');
  if (!card) return;
  const install = card.querySelector('[data-admin-install]');
  const installStatus = card.querySelector('[data-admin-install-status]');
  const installHelp = card.querySelector('[data-admin-install-help]');
  const toggle = card.querySelector('[data-admin-push-toggle]');
  const state = card.querySelector('[data-admin-push-state]');
  let prompt = null;
  let installationObserved = false;
  let busy = false;
  let revision = 0;

  const installed = () => installationObserved || navigator.standalone === true
    || matchMedia('(display-mode: standalone)').matches
    || matchMedia('(display-mode: fullscreen)').matches;
  const showInstalled = () => {
    installationObserved = true;
    install.disabled = true;
    installStatus.textContent = 'Sudah terpasang';
    installHelp.textContent = 'GlassPos Admin siap digunakan dari layar utama.';
  };
  if (installed()) showInstalled();
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    if (installed()) return;
    prompt = event;
    install.disabled = false;
    installStatus.textContent = 'Siap dipasang';
  });
  install.addEventListener('click', async () => {
    if (!prompt) {
      installHelp.textContent = 'Buka menu browser atau Bagikan, lalu pilih Install app / Tambahkan ke Layar Utama.';
      return;
    }
    const pending = prompt;
    prompt = null;
    install.disabled = true;
    try {
      await pending.prompt();
      await pending.userChoice;
      if (installed()) showInstalled();
      else {
        install.disabled = false;
        installStatus.textContent = 'Belum terpasang';
      }
    } catch (_) {
      install.disabled = false;
      installStatus.textContent = 'Belum terpasang';
    }
  });
  window.addEventListener('appinstalled', showInstalled);
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).catch(() => {});
  }

  const render = (result) => {
    toggle.checked = result.enabled === true;
    const messages = {
      unsupported: 'Browser tidak mendukung. Pada iPhone/iPad, coba pasang app ke Layar Utama terlebih dahulu.',
      permission_denied: 'Izin ditolak. Aktifkan izin notifikasi melalui pengaturan browser.',
      missing_config: 'Konfigurasi belum tersedia',
      missing_vapid_public_key: 'Konfigurasi belum tersedia',
      inactive: 'Nonaktif',
    };
    toggle.disabled = busy || ['unsupported', 'permission_denied', 'missing_config', 'missing_vapid_public_key'].includes(result.reason);
    state.textContent = result.enabled ? 'Aktif' : (messages[result.reason] || 'Nonaktif');
  };
  const refresh = async () => {
    if (busy) return;
    const current = ++revision;
    try {
      const result = await window.AppPushNotifications.getState();
      if (current === revision && !busy) render(result);
    } catch (_) {
      if (current !== revision || busy) return;
      toggle.checked = false;
      toggle.disabled = true;
      state.textContent = 'Status belum dapat diperiksa. Muat ulang untuk mencoba lagi.';
    }
  };
  toggle.addEventListener('change', async () => {
    if (busy) return;
    busy = true;
    revision++;
    const enable = toggle.checked;
    toggle.disabled = true;
    state.textContent = 'Memproses…';
    try {
      const result = await window.AppPushNotifications[enable ? 'enable' : 'disable']();
      busy = false;
      render(enable ? result : { enabled: false, reason: result.deleted ? 'inactive' : result.reason });
    } catch (_) {
      busy = false;
      await refresh();
      state.textContent += ' Perubahan belum berhasil. Periksa koneksi lalu coba lagi.';
    }
  });
  window.addEventListener('pageshow', refresh);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
  refresh();
})();
