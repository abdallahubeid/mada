import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;

if (key) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
        },
        withCredentials: true,
    });
}

/**
 * Subscribe the Tenant Owner UI to private dual-channel notifications.
 *
 * @param {{
 *   tenantId: number|string,
 *   userId: number|string,
 *   onNotification: (payload: Record<string, unknown>) => void,
 * }} options
 */
window.veyraListenTenantNotifications = function veyraListenTenantNotifications(options) {
    if (! window.Echo || ! options?.tenantId || ! options?.userId) {
        return null;
    }

    const channelName = `tenant.${options.tenantId}.notifications.${options.userId}`;

    return window.Echo.private(channelName).listen('.TenantNotificationCreated', (payload) => {
        const data = payload || {};

        // Alpine shell callback (badge + drawer inject + toast).
        options.onNotification?.(data);

        // DOM fallback for any non-Alpine listeners.
        window.dispatchEvent(new CustomEvent('veyra:tenant-notification', { detail: data }));
    });
};

/**
 * Live tenant contact inbox updates (portal form → Owner dashboard).
 *
 * @param {{
 *   tenantId: number|string,
 *   userId: number|string,
 *   onMessage: (payload: Record<string, unknown>) => void,
 * }} options
 */
window.veyraListenTenantContactMessages = function veyraListenTenantContactMessages(options) {
    if (! window.Echo || ! options?.tenantId || ! options?.userId) {
        return null;
    }

    const channelName = `tenant.${options.tenantId}.notifications.${options.userId}`;

    return window.Echo.private(channelName).listen('.NewContactMessageReceived', (payload) => {
        const data = payload || {};
        options.onMessage?.(data);
        window.dispatchEvent(new CustomEvent('veyra:tenant-contact-message', { detail: data }));
    });
};

window.veyraPlayNotificationSound = function veyraPlayNotificationSound() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (! AudioCtx) {
            return;
        }

        const ctx = new AudioCtx();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.value = 880;
        gain.gain.value = 0.04;

        oscillator.connect(gain);
        gain.connect(ctx.destination);
        oscillator.start();

        setTimeout(() => {
            oscillator.stop();
            ctx.close();
        }, 160);
    } catch (error) {
        // Ignore autoplay / audio context restrictions.
    }
};
