const ORIGINPAY_CACHE = 'originpay-static-v1';

const STATIC_ASSETS = [
    '/site.webmanifest',
    '/frontend/images/originpay/android-chrome-192x192.png',
    '/frontend/images/originpay/android-chrome-512x512.png',
    '/frontend/images/originpay/apple-touch-icon.png',
    '/general/css/bootstrap.min.css',
    '/general/css/fontawesome.min.css',
    '/general/css/simple-notify.min.css',
    '/general/css/originpay-notify.css',
    '/general/js/jquery.min.js',
    '/general/js/bootstrap.bundle.min.js',
    '/general/js/simple-notify.min.js',
    '/general/js/helpers.js',
];

const STATIC_FILE_PATTERN = /\.(?:css|js|png|jpg|jpeg|svg|webp|gif|ico|woff2?|ttf|eot)$/i;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(ORIGINPAY_CACHE)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .catch(() => undefined)
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => key !== ORIGINPAY_CACHE)
                .map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => new Response(
                '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>OriginPay offline</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0b0e14;color:#f8fafc;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;padding:24px}main{max-width:360px;text-align:center}h1{font-size:1.3rem;margin:0 0 8px}p{color:#94a3b8;line-height:1.5}</style></head><body><main><h1>Sem conexão</h1><p>Conecte-se à internet para acessar o painel OriginPay com segurança.</p></main></body></html>',
                { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
            ))
        );
        return;
    }

    if (url.origin === self.location.origin && STATIC_FILE_PATTERN.test(url.pathname)) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const networkResponse = fetch(request).then((response) => {
                    if (response && response.ok) {
                        const clone = response.clone();
                        caches.open(ORIGINPAY_CACHE).then((cache) => cache.put(request, clone));
                    }

                    return response;
                }).catch(() => cachedResponse);

                return cachedResponse || networkResponse;
            })
        );
    }
});

self.addEventListener('message', (event) => {
    const data = event.data || {};

    if (data.type !== 'ORIGINPAY_SHOW_NOTIFICATION') {
        return;
    }

    const payload = data.payload || {};
    const title = payload.title || 'OriginPay';
    const targetUrl = payload.action_link || '/user/notifications';

    event.waitUntil(
        self.registration.showNotification(title, {
            body: payload.message || 'Nova notificação recebida.',
            icon: payload.icon || '/frontend/images/originpay/android-chrome-192x192.png',
            badge: payload.badge || '/frontend/images/originpay/android-chrome-192x192.png',
            tag: payload.tag || 'originpay-notification',
            renotify: true,
            data: {
                url: targetUrl,
                timestamp: payload.timestamp || new Date().toISOString(),
            },
        })
    );
});

self.addEventListener('push', (event) => {
    let payload = {};

    if (event.data) {
        try {
            payload = event.data.json();
        } catch (error) {
            payload = { message: event.data.text() };
        }
    }

    const title = payload.title || 'OriginPay';

    event.waitUntil(
        self.registration.showNotification(title, {
            body: payload.message || 'Nova notificação recebida.',
            icon: payload.icon || '/frontend/images/originpay/android-chrome-192x192.png',
            badge: payload.badge || '/frontend/images/originpay/android-chrome-192x192.png',
            tag: payload.tag || 'originpay-notification',
            renotify: true,
            data: {
                url: payload.action_link || '/user/notifications',
                timestamp: payload.timestamp || new Date().toISOString(),
            },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = new URL(event.notification.data?.url || '/user/notifications', self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }

            return self.clients.openWindow(targetUrl);
        })
    );
});
