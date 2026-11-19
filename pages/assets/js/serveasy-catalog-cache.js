/**
 * ServEasy Smart Catalog Cache Engine
 * Provides session-backed caching with TTL for services, categories, and providers.
 * Eliminates redundant HTTP requests when navigating between home, browse, and service pages.
 */
(function (window) {
  'use strict';

  // Default TTL: 15 minutes
  const DEFAULT_TTL_MS = 15 * 60 * 1000;
  const PREFIX = 'se_cache_';

  const ServEasyCache = {
    /**
     * Get item from cache if not expired
     * @param {string} key
     * @returns {any|null}
     */
    get: function (key) {
      try {
        const raw = sessionStorage.getItem(PREFIX + key);
        if (!raw) return null;
        const item = JSON.parse(raw);
        if (!item || !item.time) return null;

        const maxAge = item.ttl || DEFAULT_TTL_MS;
        if (Date.now() - item.time > maxAge) {
          sessionStorage.removeItem(PREFIX + key);
          return null;
        }
        return item.data;
      } catch (e) {
        return null;
      }
    },

    /**
     * Store item in cache with TTL
     * @param {string} key
     * @param {any} data
     * @param {number} [ttlMs]
     */
    set: function (key, data, ttlMs) {
      try {
        if (data === undefined || data === null) return;
        const item = {
          time: Date.now(),
          ttl: ttlMs || DEFAULT_TTL_MS,
          data: data
        };
        sessionStorage.setItem(PREFIX + key, JSON.stringify(item));
      } catch (e) {
        console.warn('[ServEasyCache] Quota exceeded or error saving:', key, e);
      }
    },

    /**
     * Remove item from cache
     * @param {string} key
     */
    remove: function (key) {
      try {
        sessionStorage.removeItem(PREFIX + key);
      } catch (e) {}
    },

    /**
     * Clear all ServEasy catalog caches
     */
    clearAll: function () {
      try {
        const keysToRemove = [];
        for (let i = 0; i < sessionStorage.length; i++) {
          const k = sessionStorage.key(i);
          if (k && k.startsWith(PREFIX)) {
            keysToRemove.push(k);
          }
        }
        keysToRemove.forEach(k => sessionStorage.removeItem(k));
      } catch (e) {}
    },

    // ─── Domain-Specific Convenience Methods ───

    getCachedServices: function () {
      return this.get('all_services');
    },

    setCachedServices: function (services) {
      if (Array.isArray(services) && services.length > 0) {
        this.set('all_services', services);
      }
    },

    getCachedCategories: function () {
      return this.get('all_categories');
    },

    setCachedCategories: function (categories) {
      if (Array.isArray(categories) && categories.length > 0) {
        this.set('all_categories', categories);
      }
    }
  };

  window.ServEasyCache = ServEasyCache;
})(window);
