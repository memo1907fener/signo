/* Signo Service Worker – hält Bilder, Videos und Programmdateien offline bereit. */
const MEDIA = 'signo-media-v1';
const SHELL = 'signo-shell-v1';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));

self.addEventListener('message', (e) => {
    if (e.data && e.data.type === 'precache' && Array.isArray(e.data.urls)) {
        const keep = e.data.urls.map((u) => new URL(u, location.origin).pathname);
        e.waitUntil(caches.open(MEDIA).then((c) => c.keys().then((keys) => Promise.all(keys
            .filter((k) => keep.indexOf(new URL(k.url).pathname) === -1)
            .map((k) => c.delete(k)))).then(() => c)).then((c) => Promise.all(e.data.urls.map((u) =>
            c.match(new URL(u, location.origin).pathname).then((hit) => hit || fetch(u).then((r) => r.ok ? c.put(new URL(u, location.origin).pathname, r) : null).catch(() => null))
        ))));
    }
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== location.origin) return;
    if (url.pathname.startsWith('/api/')) return;           // Schnittstelle nie cachen
    // Videos fragen in Teilstücken (Range) an: aus dem Cache bedienen, sonst direkt laden
    if (req.headers.get('range')) {
        if (!url.pathname.startsWith('/storage/')) return;
        e.respondWith(caches.open(MEDIA).then((c) => c.match(url.pathname).then((hit) => {
            if (!hit) return fetch(req);
            return hit.blob().then((blob) => {
                const m = /bytes=(\d*)-(\d*)/.exec(req.headers.get('range')) || [];
                const start = m[1] ? parseInt(m[1], 10) : 0;
                const end = m[2] ? parseInt(m[2], 10) : blob.size - 1;
                const part = blob.slice(start, end + 1);
                return new Response(part, {
                    status: 206,
                    headers: {
                        'Content-Type': hit.headers.get('Content-Type') || 'video/mp4',
                        'Content-Range': 'bytes ' + start + '-' + end + '/' + blob.size,
                        'Content-Length': String(part.size),
                        'Accept-Ranges': 'bytes',
                    },
                });
            });
        })));
        return;
    }

    // Medien & Build-Dateien: zuerst Cache
    if (url.pathname.startsWith('/storage/') || url.pathname.startsWith('/build/')) {
        const name = url.pathname.startsWith('/build/') ? SHELL : MEDIA;
        e.respondWith(caches.open(name).then((c) => c.match(url.pathname).then((hit) => hit || fetch(req).then((r) => {
            if (r.ok && r.status === 200) c.put(url.pathname, r.clone());
            return r;
        }))));
        return;
    }

    // Player-Seite: zuerst Netz, sonst Cache (Start ohne Internet)
    if (url.pathname === '/tv') {
        e.respondWith(fetch(req).then((r) => {
            if (r.ok) { const copy = r.clone(); caches.open(SHELL).then((c) => c.put('/tv', copy)); }
            return r;
        }).catch(() => caches.open(SHELL).then((c) => c.match('/tv'))));
    }
});
