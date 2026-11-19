/**
 * ServEasy Progressive Web App (PWA) Service Worker
 * Version: 2.1.0
 * 
 * Strategy:
 * - Visited Pages: Stale-While-Revalidate / Cache-First for opened pages (instant load on revisit)
 * - Static Assets (CSS, JS, Fonts, Images): Cache-First with runtime caching
 * - Dynamic / API / Auth / Bookings / SSE: Network-Only (never served stale)
 */

const CACHE_VERSION = 'serveasy-v2.1';
const STATIC_CACHE = `serveasy-static-${CACHE_VERSION}`;
const RUNTIME_CACHE = `serveasy-runtime-${CACHE_VERSION}`;

// Pre-cache core shell resources on install
const PRECACHE_ASSETS = [
  './favicon.svg',
  './pages/assets/css/main.css',
  './pages/assets/css/global.css',
  './pages/assets/css/home.css',
  './pages/assets/css/notify.css',
  './pages/assets/css/theme.css',
  './pages/assets/css/category.css',
  './pages/assets/css/wishlist.css'
];

// URLs/Patterns to NEVER cache (strictly Network-Only)
const NETWORK_ONLY_PATTERNS = [
  /\/api\//i,
  /bookings\.php/i,
  /create-payment-order/i,
  /verify-payment/i,
  /web-portal-api\.php/i,
  /provider-dashboard-api\.php/i,
  /provider-location\.php/i,
  /stream\.php/i,
  /sse/i,
  /\/auth\//i,
  /logout\.php/i,
  /session-sync\.php/i
];

// Helper: Check if request is network-only
function isNetworkOnly(url) {
  return NETWORK_ONLY_PATTERNS.some(pattern => pattern.test(url.href));
}

// Helper: Check if request is for static assets
function isStaticAsset(url) {
  return /\.(css|js|woff2?|ttf|eot|svg|png|jpe?g|webp|ico)(\?.*)?$/i.test(url.pathname);
}

// Helper: Check if request is an HTML / navigation request
function isPageNavigation(request) {
  return request.mode === 'navigate' || (request.headers.get('accept') && request.headers.get('accept').includes('text/html'));
}

// 1. Install Event: Pre-cache core UI shell
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then(cache => {
      // Non-blocking best-effort pre-caching
      return Promise.allSettled(
        PRECACHE_ASSETS.map(url => cache.add(url).catch(err => {
          console.warn('[ServEasy SW] Failed to pre-cache:', url, err);
        }))
      );
    }).then(() => self.skipWaiting())
  );
});

// 2. Activate Event: Clean up stale/old caches
self.addEventListener('activate', event => {
  const currentCaches = [STATIC_CACHE, RUNTIME_CACHE];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(name => {
          if (!currentCaches.includes(name)) {
            console.log('[ServEasy SW] Deleting obsolete cache:', name);
            return caches.delete(name);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// 3. Fetch Event: Intelligent on-demand runtime caching
self.addEventListener('fetch', event => {
  const req = event.request;

  // Only intercept GET requests
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);

  // Strictly skip API, SSE, Auth, and Bookings
  if (isNetworkOnly(url)) {
    return;
  }

  // Strategy A: Static Assets (CSS, JS, Fonts, Images) -> Cache-First with Network Fallback
  if (isStaticAsset(url)) {
    event.respondWith(
      caches.match(req).then(cachedRes => {
        if (cachedRes) {
          // Return cached asset immediately
          return cachedRes;
        }

        // Fetch from network and save into STATIC_CACHE on-demand
        return fetch(req).then(networkRes => {
          if (!networkRes || networkRes.status !== 200 || networkRes.type !== 'basic') {
            return networkRes;
          }

          const resClone = networkRes.clone();
          caches.open(STATIC_CACHE).then(cache => cache.put(req, resClone));
          return networkRes;
        }).catch(() => {
          // Offline fallback if needed
          return cachedRes;
        });
      })
    );
    return;
  }

  // Strategy B: Page Navigation / Opened Pages -> Stale-While-Revalidate On-Demand
  // Only the pages the user actually opens are loaded and saved in cache!
  if (isPageNavigation(req)) {
    event.respondWith(
      caches.match(req).then(cachedPage => {
        // Fetch fresh copy from network in background or primary
        const fetchPromise = fetch(req).then(networkRes => {
          if (networkRes && networkRes.status === 200) {
            const resClone = networkRes.clone();
            caches.open(RUNTIME_CACHE).then(cache => cache.put(req, resClone));
          }
          return networkRes;
        }).catch(err => {
          // If offline and page was previously opened, cachedPage will be served
          if (cachedPage) {
            return cachedPage;
          }
          throw err;
        });

        // If previously opened and cached, return instantly for 0ms load time!
        return cachedPage || fetchPromise;
      })
    );
  }
});
