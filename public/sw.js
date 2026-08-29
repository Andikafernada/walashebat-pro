/*
 * WALIKELAS PWA SERVICE WORKER v7 (Instant Fresh Navigation Edition)
 *
 * Fitur:
 * - Precache aset statis inti (manifest, icons, favicon, offline fallback page).
 * - Cache dinamis untuk CSS, JS, fonts, images.
 * - Offline Fallback saat koneksi internet sekolah terputus di dalam kelas.
 * - Background Sync Listener untuk antrean presensi offline.
 */
const CACHE_VERSION = 'v7';
const CACHE_NAME = `walikelas-${CACHE_VERSION}`;
const RUNTIME_CACHE = `walikelas-runtime-${CACHE_VERSION}`;

// Aset statis yang di-precache saat install
const PRECACHE_ASSETS = [
  '/manifest.webmanifest',
  '/icon-192.png',
  '/icon-512.png',
  '/favicon.ico',
  '/favicon-32.png',
  '/offline.html',
];

// Pola URL statis yang di-cache saat diminta
const RUNTIME_CACHE_PATTERNS = [
  /\.css(\?.*)?$/,
  /\.js(\?.*)?$/,
  /build\/assets\//,
  /\/fonts\//,
  /\/images\//,
  /\.(woff2?|ttf|eot|svg|png|jpg|jpeg|gif|webp|ico)(\?.*)?$/,
];

// Abaikan dan selalu lewat jaringan
const NETWORK_ONLY_PATTERNS = [
  /api\//,
  /\/api-/,
  /_debugbar/,
  /fonts\.googleapis\.com/,
  /fonts\.gstatic\.com/,
  /localhost:5173/,
  /vitejs/,
];

// ── Install ─────────────────────────────────────────────────────────────
self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        return Promise.allSettled(
          PRECACHE_ASSETS.map((url) =>
            fetch(url)
              .then((response) => {
                if (response.ok && !response.redirected) {
                  return cache.put(url, response);
                }
              })
              .catch((err) => {
                console.warn(`[SW] Precache skipped: ${url}`, err);
              })
          )
        );
      })
      .then(() => self.skipWaiting())
  );
});

// ── Activate ───────────────────────────────────────────────────────────
self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(
        cacheNames
          .filter((name) => name.startsWith('walikelas-') && name !== CACHE_NAME && name !== RUNTIME_CACHE)
          .map((name) => {
            console.log(`[SW] Hapus cache lama: ${name}`);
            return caches.delete(name);
          })
      )
    ).then(() => self.clients.claim())
  );
});

// ── Fetch (Offline-First Resilient Strategy) ────────────────────────────
self.addEventListener('fetch', (e) => {
  const { request } = e;
  const url = new URL(request.url);

  // Hanya proses GET
  if (request.method !== 'GET') return;

  // Lewati resource eksternal atau endpoint debugging/API
  if (NETWORK_ONLY_PATTERNS.some((p) => p.test(url.href))) return;

  // 1. NAVIGASI HALAMAN (HTML): Selalu Network-First
  if (request.mode === 'navigate') {
    e.respondWith(
      fetch(request)
        .catch(() => {
          return caches.match('/offline.html') || new Response(
            `<!DOCTYPE html>
            <html lang="id">
            <head><meta charset="utf-8"><title>Mode Offline - WaliKelas Pro</title><meta name="viewport" content="width=device-width, initial-scale=1"></head>
            <body style="font-family:sans-serif;text-align:center;padding:50px 20px;background:#f8fafc;color:#0f172a">
              <h2 style="color:#059669">📡 Anda Sedang Offline</h2>
              <p>Koneksi internet di kelas sedang terputus. Data presensi lokal tersimpan aman dan akan otomatis disinkronkan saat tersambung kembali ke internet.</p>
              <button onclick="window.location.reload()" style="padding:10px 20px;border-radius:12px;background:#059669;color:#fff;border:none;font-weight:bold;cursor:pointer;margin-top:15px">Coba Muat Ulang</button>
            </body>
            </html>`,
            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
          );
        })
    );
    return;
  }

  // 2. ASET STATIS (CSS, JS, IMAGES, FONTS): Cache-First dengan Network Fallback
  if (RUNTIME_CACHE_PATTERNS.some((p) => p.test(url.pathname))) {
    e.respondWith(
      caches.match(request).then((cachedResponse) => {
        if (cachedResponse) return cachedResponse;

        return fetch(request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(RUNTIME_CACHE).then((cache) => cache.put(request, responseToCache));
          }
          return networkResponse;
        });
      })
    );
  }
});

// ── Background Sync ───────────────────────────────────────────────────
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-offline-attendances') {
    console.log('[SW] Melakukan Background Sync presensi offline...');
    event.waitUntil(
      self.clients.matchAll().then((clients) => {
        clients.forEach((client) => {
          client.postMessage({ type: 'SYNC_OFFLINE_DATA' });
        });
      })
    );
  }
});
