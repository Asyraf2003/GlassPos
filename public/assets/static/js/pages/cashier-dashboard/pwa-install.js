(() => {
  const button = document.querySelector('[data-pwa-install-button]');
  const status = document.querySelector('[data-pwa-install-status]');

  if (!button || !status) {
    return;
  }

  let deferredPrompt = null;

  const setStatus = (message) => {
    status.textContent = message;
  };

  const isInstalledDisplayMode = () => {
    const standalone = window.matchMedia('(display-mode: standalone)').matches;
    const fullscreen = window.matchMedia('(display-mode: fullscreen)').matches;
    return standalone || fullscreen || window.navigator.standalone === true;
  };

  const registerServiceWorker = () => {
    if (!('serviceWorker' in navigator)) {
      return;
    }

    navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).catch(() => {});
  };

  if (isInstalledDisplayMode()) {
    button.disabled = true;
    button.textContent = 'Sudah Terpasang';
    setStatus('HyperPOS Kasir sudah berjalan sebagai app PWA.');
    registerServiceWorker();
    return;
  }

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    button.disabled = false;
    setStatus('App siap dipasang ke layar utama perangkat ini.');
  });

  button.addEventListener('click', async () => {
    if (!deferredPrompt) {
      setStatus('Jika prompt belum muncul, buka menu browser lalu pilih Install app atau Add to Home screen.');
      return;
    }

    button.disabled = true;
    deferredPrompt.prompt();

    const choice = await deferredPrompt.userChoice;
    deferredPrompt = null;

    if (choice.outcome === 'accepted') {
      button.textContent = 'Terpasang';
      setStatus('HyperPOS Kasir berhasil dipasang.');
      return;
    }

    button.disabled = false;
    setStatus('Install dibatalkan. Tombol bisa dicoba lagi saat browser menampilkan prompt.');
  });

  window.addEventListener('appinstalled', () => {
    button.disabled = true;
    button.textContent = 'Terpasang';
    setStatus('HyperPOS Kasir sudah terpasang di perangkat.');
  });

  registerServiceWorker();
})();
