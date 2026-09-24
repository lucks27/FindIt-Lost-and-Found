self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = {
            title: 'FindIt',
            body: 'You have a new notification.',
        };
    }

    const title = payload.title || 'FindIt';
    const options = {
        body: payload.body || 'You have a new notification.',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        data: payload.data || {},
        tag: payload.tag || 'findit-notification',
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientsList) => {
            if (clientsList.length > 0) {
                const client = clientsList[0];
                return client.focus();
            }

            return clients.openWindow(url);
        })
    );
});
