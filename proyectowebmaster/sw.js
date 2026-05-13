// BB4: Service Worker — cache-first for static assets, network-first for PHP pages
const CACHE_NAME = 'pshop-v1';
const STATIC_ASSETS = [
  '/proyectowebmaster/assets/css/bootstrap.min.css',
  '/proyectowebmaster/assets/css/font-awesome.min.css',
  '/proyectowebmaster/assets/css/main.css',
  '/proyectowebmaster/assets/js/jquery-1.11.1.min.js',
  '/proyectowebmaster/assets/js/bootstrap.min.js',
  '/proyectowebmaster/assets/js/toast.js',
  '/proyectowebmaster/offline.php'
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE_NAME).then(c => c.addAll(STATIC_ASSETS)).catch(()=>{}));
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys => Promise.all(
    keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
  )));
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);
  // Cache-first for static assets
  if (e.request.method === 'GET' && /\.(css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|ico)$/.test(url.pathname)) {
    e.respondWith(caches.match(e.request).then(r => r || fetch(e.request).then(res => {
      if (res.ok) caches.open(CACHE_NAME).then(c => c.put(e.request, res.clone()));
      return res;
    }).catch(() => new Response('', {status: 503}))));
    return;
  }
  // Network-first for PHP pages (fall back to offline page)
  if (e.request.method === 'GET' && url.pathname.endsWith('.php')) {
    e.respondWith(fetch(e.request).catch(() => caches.match('/proyectowebmaster/offline.php')));
    return;
  }
});
