(() => {
  const readConfig = () => {
    const node = document.getElementById('push-notification-config');

    if (!node) {
      return null;
    }

    const text = node.textContent ? node.textContent.trim() : '';

    if (text !== '') {
      try {
        return JSON.parse(text);
      } catch (error) {
        return null;
      }
    }

    return {
      serviceWorkerUrl: node.dataset.serviceWorkerUrl || '',
      serviceWorkerScope: node.dataset.serviceWorkerScope || '/',
      subscribeUrl: node.dataset.subscribeUrl || '',
      unsubscribeUrl: node.dataset.unsubscribeUrl || '',
      vapidPublicKey: node.dataset.vapidPublicKey || '',
      defaultIcon: node.dataset.defaultIcon || '',
      defaultUrl: node.dataset.defaultUrl || '',
    };
  };

  const legacyReloadFlag = 'hyperpos.legacy-service-worker-cleanup-reloaded';

  const registrationScriptUrls = (registration) => [
    registration.active?.scriptURL || '',
    registration.waiting?.scriptURL || '',
    registration.installing?.scriptURL || '',
  ].filter((scriptURL) => scriptURL !== '');

  const isLegacyServiceWorkerScript = (scriptURL) => {
    try {
      return new URL(scriptURL).pathname.endsWith('/sw.js');
    } catch (_error) {
      return false;
    }
  };

  const clearCacheStorage = async () => {
    if (!('caches' in window)) {
      return 0;
    }

    const cacheNames = await caches.keys();

    await Promise.all(cacheNames.map((name) => caches.delete(name)));

    return cacheNames.length;
  };

  const cleanupLegacyServiceWorkers = async () => {
    if (!('serviceWorker' in navigator)) {
      return;
    }

    const registrations = await navigator.serviceWorker.getRegistrations();
    const legacyRegistrations = registrations.filter((registration) => (
      registrationScriptUrls(registration).some(isLegacyServiceWorkerScript)
    ));

    if (legacyRegistrations.length === 0) {
      try {
        window.sessionStorage.removeItem(legacyReloadFlag);
      } catch (_error) {
        // ignore unavailable sessionStorage
      }

      return;
    }

    await Promise.all(legacyRegistrations.map((registration) => registration.unregister()));
    await clearCacheStorage();

    let shouldReload = true;

    try {
      shouldReload = window.sessionStorage.getItem(legacyReloadFlag) !== '1';
      window.sessionStorage.setItem(legacyReloadFlag, '1');
    } catch (_error) {
      // reload once when sessionStorage is unavailable too
    }

    if (shouldReload) {
      window.location.reload();
    }
  };

  const base64UrlToUint8Array = (base64Url) => {
    const padding = '='.repeat((4 - (base64Url.length % 4)) % 4);
    const base64 = (base64Url + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    const output = new Uint8Array(raw.length);

    for (let index = 0; index < raw.length; index += 1) {
      output[index] = raw.charCodeAt(index);
    }

    return output;
  };

  const csrfToken = () => {
    const node = document.querySelector('meta[name="csrf-token"]');

    return node ? node.getAttribute('content') : '';
  };

  const postJson = async (url, method, payload) => {
    const response = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify(payload),
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error(`Push notification request failed: ${response.status}`);
    }

    return response.json();
  };

  const ensureSupported = () => {
    return 'serviceWorker' in navigator
      && 'PushManager' in window
      && 'Notification' in window;
  };

  const registerServiceWorker = async (config) => {
    return navigator.serviceWorker.register(config.serviceWorkerUrl, {
      scope: config.serviceWorkerScope || '/',
    });
  };

  const currentSubscription = async (registration) => {
    return registration.pushManager.getSubscription();
  };

  const enable = async () => {
    const config = readConfig();

    if (!config || !ensureSupported()) {
      return { enabled: false, reason: 'unsupported' };
    }

    if (!config.vapidPublicKey) {
      return { enabled: false, reason: 'missing_vapid_public_key' };
    }

    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
      return { enabled: false, reason: 'permission_denied' };
    }

    const registration = await registerServiceWorker(config);
    const existing = await currentSubscription(registration);

    const subscription = existing || await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: base64UrlToUint8Array(config.vapidPublicKey),
    });

    await postJson(config.subscribeUrl, 'POST', {
      endpoint: subscription.endpoint,
      keys: subscription.toJSON().keys,
      contentEncoding: 'aes128gcm',
    });

    return { enabled: true };
  };

  const disable = async () => {
    const config = readConfig();

    if (!config || !ensureSupported()) {
      return { deleted: false, reason: 'unsupported' };
    }

    const registration = await registerServiceWorker(config);
    const subscription = await currentSubscription(registration);

    if (!subscription) {
      return { deleted: true };
    }

    await postJson(config.unsubscribeUrl, 'DELETE', {
      endpoint: subscription.endpoint,
    });

    await subscription.unsubscribe();

    return { deleted: true };
  };

  window.AppPushNotifications = {
    enable,
    disable,
    isSupported: ensureSupported,
  };

  cleanupLegacyServiceWorkers().catch(() => {
    // Legacy service worker cleanup is best-effort.
  });
})();
