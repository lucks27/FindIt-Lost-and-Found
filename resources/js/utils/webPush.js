const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY || '';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const normalized = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const raw = atob(normalized);
    const output = new Uint8Array(raw.length);

    for (let i = 0; i < raw.length; i++) {
        output[i] = raw.charCodeAt(i);
    }

    return output;
}

export async function initWebPushNotifications() {
    if (!('Notification' in window) || !('serviceWorker' in navigator)) {
        return false;
    }

    if (Notification.permission === 'denied') {
        return false;
    }

    const permission = Notification.permission === 'granted'
        ? 'granted'
        : await Notification.requestPermission();

    if (permission !== 'granted') {
        return false;
    }

    try {
        const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });

        if (!VAPID_PUBLIC_KEY) {
            new Notification('FindIt notifications are ready', {
                body: 'Browser notifications are enabled. Add your VAPID keys to receive server-triggered desktop alerts.',
                icon: '/favicon.ico',
            });
            return true;
        }

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
        });

        await fetch('/api/web-push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ subscription: subscription.toJSON() }),
        });

        new Notification('FindIt notifications are ready', {
            body: 'You will now receive pop-up alerts for important updates.',
            icon: '/favicon.ico',
        });

        return true;
    } catch (error) {
        console.error('Web push setup failed:', error);
        return false;
    }
}

export function showWebNotification(title, body, options = {}) {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return null;
    }

    return new Notification(title, {
        body,
        icon: '/favicon.ico',
        ...options,
    });
}
