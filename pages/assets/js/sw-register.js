/**
 * ServEasy Service Worker Auto-Registration
 * Automatically discovers sw.js relative to portal root and registers it.
 */
(function () {
  'use strict';

  if ('serviceWorker' in navigator && (window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
    window.addEventListener('load', function () {
      let swPath = '/sw.js';
      const m = window.location.pathname.match(/^(.*?\/(?:Frontend\/Web|frontend-web)\/)/i);
      if (m) {
        swPath = m[1] + 'sw.js';
      } else if (window.location.pathname.includes('/pages/')) {
        swPath = '../sw.js';
      } else {
        swPath = './sw.js';
      }

      navigator.serviceWorker.register(swPath)
        .then(function (registration) {
          // Check for background worker updates
          registration.addEventListener('updatefound', function () {
            const installingWorker = registration.installing;
            if (installingWorker) {
              installingWorker.addEventListener('statechange', function () {
                if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                  console.info('[ServEasy SW] New version installed in background.');
                }
              });
            }
          });
        })
        .catch(function (err) {
          // Graceful fallback: app works normally even if SW is blocked (e.g. Incognito mode)
          console.debug('[ServEasy SW] Registration notice:', err.message);
        });
    });
  }
})();
