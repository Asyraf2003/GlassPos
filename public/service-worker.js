const PUBLIC_ASSET_BASE = 'https://media.arbiconbengkel.my.id';

const DEFAULT_NOTIFICATION = {
  title: 'Reminder Jatuh Tempo',
  body: 'Ada nota pelanggan yang perlu dicek.',
  icon: `${PUBLIC_ASSET_BASE}/assets/compiled/svg/favicon.svg`,
  badge: `${PUBLIC_ASSET_BASE}/assets/compiled/svg/favicon.svg`,
  url: '/admin/due-note-reminders',
  tag: 'due-note-reminder',
};

const normalizePayload = (payload) => {
  if (!payload || typeof payload !== 'object') {
    return DEFAULT_NOTIFICATION;
  }

  return {
    title: typeof payload.title === 'string' && payload.title.trim() !== ''
      ? payload.title
      : DEFAULT_NOTIFICATION.title,
    body: typeof payload.body === 'string' && payload.body.trim() !== ''
      ? payload.body
      : DEFAULT_NOTIFICATION.body,
    icon: typeof payload.icon === 'string' && payload.icon.trim() !== ''
      ? payload.icon
      : DEFAULT_NOTIFICATION.icon,
    badge: typeof payload.badge === 'string' && payload.badge.trim() !== ''
      ? payload.badge
      : DEFAULT_NOTIFICATION.badge,
    url: typeof payload.url === 'string' && payload.url.trim() !== ''
      ? payload.url
      : DEFAULT_NOTIFICATION.url,
    tag: typeof payload.tag === 'string' && payload.tag.trim() !== ''
      ? payload.tag
      : DEFAULT_NOTIFICATION.tag,
  };
};

self.addEventListener('push', (event) => {
  const payload = event.data ? event.data.json() : {};
  const notification = normalizePayload(payload);

  event.waitUntil(
    self.registration.showNotification(notification.title, {
      body: notification.body,
      icon: notification.icon,
      badge: notification.badge,
      tag: notification.tag,
      data: {
        url: notification.url,
      },
    }),
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = event.notification.data?.url || DEFAULT_NOTIFICATION.url;

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        for (const client of clientList) {
          if ('focus' in client) {
            client.navigate(url);
            return client.focus();
          }
        }

        if (clients.openWindow) {
          return clients.openWindow(url);
        }

        return undefined;
      }),
  );
});
