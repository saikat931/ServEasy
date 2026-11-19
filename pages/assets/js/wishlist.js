/**
 * ServEasy Centralized Wishlist Management Engine
 * Handles client-side storage, synchronization across cards, badges, and the aside drawer.
 */
(function() {
  'use strict';

  const STORAGE_KEY = 'serveasy_wishlist';
  const DATA_KEY = 'serveasy_wishlist_data';

  // Seed default starter service metadata matching live database
  const DEFAULT_SERVICES = {
    1: { id: 1, name: 'Deep Cleaning', base_price: '75.00', category_name: 'Home & Living Services', slug: 'complete-interior-deep-clean-polish', icon: 'sparkles-outline' },
    5: { id: 5, name: 'House Maid', base_price: '40.00', category_name: 'Personal Staff & Maids', slug: 'personal-staff-and-maids', icon: 'people-outline' },
    8: { id: 8, name: 'Laundry Washing', base_price: '20.00', category_name: 'Home & Living Services', slug: 'laundry-and-dry-clean', icon: 'water-outline' }
  };

  // Safe string escaping
  function escapeHTML(str) {
    if (!str) return '';
    return String(str).replace(/[&<>'"]/g, 
      tag => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
      }[tag] || tag)
    );
  }

  function escapeQuote(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'");
  }

  // Read stored IDs
  function getRawList() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored === null) {
        // Default starter favorites
        const defaults = [1, 5, 8];
        localStorage.setItem(STORAGE_KEY, JSON.stringify(defaults));
        saveItemsData(DEFAULT_SERVICES);
        return defaults;
      }
      const parsed = JSON.parse(stored);
      return Array.isArray(parsed) ? parsed.map(Number) : [];
    } catch (e) {
      console.warn('ServEasyWishlist: Error reading localStorage', e);
      return [];
    }
  }

  function saveList(list) {
    try {
      const clean = Array.from(new Set(list.map(Number).filter(id => !isNaN(id) && id > 0)));
      localStorage.setItem(STORAGE_KEY, JSON.stringify(clean));
      return clean;
    } catch (e) {
      console.warn('ServEasyWishlist: Error writing localStorage', e);
      return list;
    }
  }

  // Read cached items data
  function getItemsData() {
    try {
      const stored = localStorage.getItem(DATA_KEY);
      return stored ? JSON.parse(stored) : { ...DEFAULT_SERVICES };
    } catch (e) {
      return { ...DEFAULT_SERVICES };
    }
  }

  function saveItemsData(dataObj) {
    try {
      localStorage.setItem(DATA_KEY, JSON.stringify(dataObj));
    } catch (e) {}
  }

  // Global In-Memory Lookup
  window.__serveasy_services_cache = window.__serveasy_services_cache || [];

  const ServEasyWishlist = {
    getAll: function() {
      return getRawList();
    },

    has: function(serviceId) {
      const id = Number(serviceId);
      return getRawList().includes(id);
    },

    getItemsData: function() {
      return getItemsData();
    },

    // Cache service objects into memory and localStorage
    cacheServices: function(servicesList) {
      if (!Array.isArray(servicesList) || servicesList.length === 0) return;
      window.__serveasy_services_cache = servicesList;
      const currentData = getItemsData();
      let changed = false;

      servicesList.forEach(s => {
        const id = Number(s.id);
        if (id) {
          currentData[id] = {
            id: id,
            name: s.name,
            base_price: s.base_price,
            category_name: s.category_name,
            subcategory_name: s.subcategory_name,
            slug: s.slug,
            icon: s.subcategory_icon || 'sparkles-outline'
          };
          changed = true;
        }
      });

      if (changed) {
        saveItemsData(currentData);
      }
    },

    add: function(serviceOrId, serviceTitle) {
      let id = 0;
      let serviceData = null;

      if (typeof serviceOrId === 'object' && serviceOrId !== null) {
        id = Number(serviceOrId.id);
        serviceData = serviceOrId;
      } else {
        id = Number(serviceOrId);
      }

      if (isNaN(id) || id <= 0) return false;

      const list = getRawList();
      if (!list.includes(id)) {
        list.push(id);
        saveList(list);
      }

      // Store metadata if provided
      if (serviceData) {
        const currentData = getItemsData();
        currentData[id] = {
          id: id,
          name: serviceData.name || serviceTitle,
          base_price: serviceData.base_price || '20.00',
          category_name: serviceData.category_name || 'Service',
          subcategory_name: serviceData.subcategory_name || '',
          slug: serviceData.slug || '',
          icon: serviceData.subcategory_icon || 'sparkles-outline'
        };
        saveItemsData(currentData);
      }

      this.syncUI(id, true);
      this.updateBadges();
      this.renderAsideDrawer();

      if (window.Notify && typeof window.Notify.success === 'function') {
        const title = serviceTitle || (serviceData && serviceData.name) ? `"${serviceTitle || serviceData.name}"` : 'Service';
        window.Notify.success(`${title} added to your Favorites!`);
      }

      window.dispatchEvent(new CustomEvent('wishlist:updated', { detail: { action: 'add', id: id, list: list } }));
      return true;
    },

    remove: function(serviceId, serviceTitle) {
      const id = Number(serviceId);
      let list = getRawList();
      const initialLen = list.length;
      list = list.filter(item => item !== id);
      if (list.length !== initialLen) {
        saveList(list);
      }

      this.syncUI(id, false);
      this.updateBadges();
      this.renderAsideDrawer();

      if (window.Notify && typeof window.Notify.info === 'function') {
        const title = serviceTitle ? `"${serviceTitle}"` : 'Service';
        window.Notify.info(`${title} removed from Favorites.`);
      }

      window.dispatchEvent(new CustomEvent('wishlist:updated', { detail: { action: 'remove', id: id, list: list } }));
      return true;
    },

    toggle: function(serviceOrId, event, serviceTitle) {
      if (event) {
        event.stopPropagation();
        event.preventDefault();
      }
      const id = typeof serviceOrId === 'object' && serviceOrId !== null ? Number(serviceOrId.id) : Number(serviceOrId);
      if (this.has(id)) {
        return this.remove(id, serviceTitle);
      } else {
        return this.add(serviceOrId, serviceTitle);
      }
    },

    clear: async function() {
      if (window.Notify && typeof window.Notify.confirm === 'function') {
        const ok = await window.Notify.confirm('Are you sure you want to clear all saved services from your wishlist?', {
          title: 'Clear Wishlist',
          confirmText: 'Clear All',
          cancelText: 'Keep Items',
          type: 'danger'
        });
        if (!ok) return false;
      }

      saveList([]);
      this.syncAllButtons();
      this.updateBadges();
      this.renderAsideDrawer();

      if (window.Notify && typeof window.Notify.info === 'function') {
        window.Notify.info('Your wishlist has been cleared.');
      }

      window.dispatchEvent(new CustomEvent('wishlist:updated', { detail: { action: 'clear', list: [] } }));
      return true;
    },

    syncUI: function(serviceId, isSaved) {
      const buttons = document.querySelectorAll(`.btn-card-wishlist[data-service-id="${serviceId}"]`);
      buttons.forEach(btn => {
        if (isSaved) {
          btn.classList.add('active');
          btn.setAttribute('title', 'Remove from Wishlist');
          btn.innerHTML = '<ion-icon name="heart"></ion-icon>';
        } else {
          btn.classList.remove('active');
          btn.setAttribute('title', 'Save to Wishlist');
          btn.innerHTML = '<ion-icon name="heart-outline"></ion-icon>';
        }
      });
    },

    syncAllButtons: function() {
      const list = this.getAll();
      const buttons = document.querySelectorAll('.btn-card-wishlist[data-service-id]');
      buttons.forEach(btn => {
        const id = Number(btn.getAttribute('data-service-id'));
        const isSaved = list.includes(id);
        if (isSaved) {
          btn.classList.add('active');
          btn.setAttribute('title', 'Remove from Wishlist');
          btn.innerHTML = '<ion-icon name="heart"></ion-icon>';
        } else {
          btn.classList.remove('active');
          btn.setAttribute('title', 'Save to Wishlist');
          btn.innerHTML = '<ion-icon name="heart-outline"></ion-icon>';
        }
      });
    },

    updateBadges: function() {
      const list = this.getAll();
      const count = list.length;
      const countStr = count < 10 ? '0' + count : String(count);

      // Header Wishlist Action Button Badge
      const headerBadges = document.querySelectorAll('.header-action-btn[data-panel-btn="whishlist"] .btn-badge');
      headerBadges.forEach(b => {
        b.textContent = countStr;
        b.setAttribute('value', count);
      });

      // Update wishlist count in aside drawer header if present
      const drawerCount = document.getElementById('aside-wishlist-count-badge');
      if (drawerCount) drawerCount.textContent = countStr;
    },

    // Book service action from aside drawer
    bookService: function(serviceId, serviceSlug, serviceName) {
      const hasPagesBase = document.querySelector('base') && (document.querySelector('base').getAttribute('href') || '').includes('pages');
      const isPages = window.location.pathname.includes('/pages/');
      const pagesBase = (isPages || hasPagesBase) ? './' : './pages/';
      if (serviceSlug === 'vehicle-car-and-bike-wash' || serviceSlug === 'vehicle-car-&-bike-wash') {
        window.location.href = `${pagesBase}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&select_vehicle=1`;
      } else if (serviceSlug === 'personal-staff-and-maids' || serviceSlug === 'personal-staff-&-maids') {
        window.location.href = `${pagesBase}services/personal-staff-&-maids.php?service=personal-staff-and-maids`;
      } else if (typeof window.openBookingModalDynamic === 'function') {
        // Close aside drawer
        const sidePanel = document.querySelector('[data-side-panel="whishlist"]');
        if (sidePanel) sidePanel.classList.remove('active');
        const backdrop = document.getElementById('sidePanelBackdrop');
        if (backdrop) backdrop.classList.remove('active');

        window.openBookingModalDynamic(serviceId, serviceName);
      } else {
        window.location.href = `${pagesBase}services.php?service_id=${serviceId}&book=1`;
      }
    },

    // Populate the aside drawer with saved services
    renderAsideDrawer: function(servicesLookup = []) {
      const container = document.getElementById('aside-wishlist-items-container');
      if (!container) return;

      const list = this.getAll();
      this.updateBadges();

      if (!list || list.length === 0) {
        container.innerHTML = `
          <div style="padding: 40px 16px; text-align: center; color: #94a3b8; margin: auto 0;">
            <div style="width: 58px; height: 58px; border-radius: 50%; background: rgba(255, 0, 127, 0.1); border: 1px solid rgba(255, 0, 127, 0.3); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; color: #ff6090; font-size: 1.8rem; box-shadow: 0 0 16px rgba(255, 0, 127, 0.2);">
              <ion-icon name="heart-outline"></ion-icon>
            </div>
            <h4 style="font-family: var(--font-primary, 'Poppins', sans-serif) !important; font-size: 1.05rem !important; color: #ffffff; margin-bottom: 5px; font-weight: 600;">Your Wishlist is Empty</h4>
            <p style="font-family: var(--font-primary, 'Poppins', sans-serif) !important; font-size: 0.8rem !important; color: #94a3b8; margin-bottom: 18px; line-height: 1.45;">Click the heart icon on any service card to save it here.</p>
            <a href="${window.location.pathname.includes('/pages/') ? './' : './pages/'}services.php" class="panel-btn-primary" style="display: inline-flex; justify-content: center; text-decoration: none; font-size: 0.82rem; padding: 9px 18px; border-radius: 10px;">
              <span>Explore Services</span>
            </a>
          </div>
        `;
        return;
      }

      // Gather item objects
      const cachedData = getItemsData();
      const lookupPool = [
        ...(Array.isArray(servicesLookup) ? servicesLookup : []),
        ...(Array.isArray(window.__serveasy_services_cache) ? window.__serveasy_services_cache : [])
      ];

      function getShortCat(cat) {
        if (!cat) return 'Service';
        if (cat.toLowerCase().includes('emergency')) return 'Emergency SOS';
        if (cat.toLowerCase().includes('personal') || cat.toLowerCase().includes('maid')) return 'Personal Staff';
        if (cat.toLowerCase().includes('vehicle') || cat.toLowerCase().includes('automotive')) return 'Automotive';
        if (cat.toLowerCase().includes('home') || cat.toLowerCase().includes('living')) return 'Home & Living';
        if (cat.toLowerCase().includes('transport') || cat.toLowerCase().includes('rental')) return 'Transport';
        if (cat.toLowerCase().includes('room') || cat.toLowerCase().includes('lodging') || cat.toLowerCase().includes('pg')) return 'Rooms';
        if (cat.toLowerCase().includes('food') || cat.toLowerCase().includes('dining')) return 'Dining';
        return cat.length > 14 ? cat.substring(0, 13) + '…' : cat;
      }

      let subtotal = 0;
      let itemsHtml = `
        <div style="display: flex; flex-direction: column; height: 100%; justify-content: space-between; overflow-x: hidden; width: 100%; box-sizing: border-box;">
          <ul class="aside-wishlist-list">
      `;

      list.forEach(id => {
        // Find matching service data
        let s = lookupPool.find(item => Number(item.id) === id);
        if (!s && cachedData[id]) {
          s = cachedData[id];
        }
        if (!s && DEFAULT_SERVICES[id]) {
          s = DEFAULT_SERVICES[id];
        }

        const name = s ? s.name : `Service #${id}`;
        const priceNum = s ? (parseFloat(s.base_price) || 20) : 20;
        subtotal += priceNum;
        const priceFormatted = '$' + (priceNum % 1 === 0 ? priceNum.toFixed(0) : priceNum.toFixed(2));
        const rawCat = s ? (s.category_name || s.subcategory_name || 'Service') : 'Service';
        const category = getShortCat(rawCat);
        const icon = (s && s.subcategory_icon) ? s.subcategory_icon : (s && s.icon ? s.icon : 'sparkles-outline');
        const slug = s ? (s.slug || '') : '';

        itemsHtml += `
          <li style="list-style: none; margin: 0; padding: 0; width: 100%; box-sizing: border-box;">
            <div class="aside-wishlist-card">
              
              <div class="aside-wishlist-icon">
                <ion-icon name="${escapeHTML(icon)}"></ion-icon>
              </div>

              <div class="aside-wishlist-meta">
                <div class="aside-wishlist-title" title="${escapeHTML(name)}">
                  ${escapeHTML(name)}
                </div>
                <div class="aside-wishlist-bottom-row">
                  <span class="aside-wishlist-price">${priceFormatted}</span>
                  <span class="aside-wishlist-sep">•</span>
                  <span class="aside-wishlist-cat" title="${escapeHTML(rawCat)}">${escapeHTML(category)}</span>
                </div>
              </div>

              <div class="aside-wishlist-actions">
                <button type="button" class="btn-aside-book" onclick="ServEasyWishlist.bookService(${id}, '${escapeQuote(slug)}', '${escapeQuote(name)}')" title="Book this service">
                  <span>Book</span>
                </button>
                <button type="button" class="btn-aside-remove" onclick="ServEasyWishlist.remove(${id}, '${escapeQuote(name)}')" title="Remove from wishlist" aria-label="Remove">
                  <ion-icon name="trash-outline"></ion-icon>
                </button>
              </div>

            </div>
          </li>
        `;
      });

      const totalFormatted = '$' + (subtotal % 1 === 0 ? subtotal.toFixed(0) : subtotal.toFixed(2));

      itemsHtml += `
          </ul>

          <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255, 255, 255, 0.08); flex-shrink: 0; width: 100%; box-sizing: border-box;">
            <div class="aside-wishlist-subtotal">
              <span class="aside-subtotal-lbl">Est. Starting Total:</span>
              <span class="aside-subtotal-val">${totalFormatted}</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px; width: 100%;">
              <a href="${window.location.pathname.includes('/pages/') ? './' : './pages/'}wishlist.php" class="aside-wishlist-footer-btn">
                <ion-icon name="heart" style="color: #ff6090; font-size: 1rem;"></ion-icon>
                <span>View Full Wishlist Page</span>
              </a>
              <button type="button" class="aside-wishlist-clear-btn" onclick="ServEasyWishlist.clear()">
                Clear All Saved Items
              </button>
            </div>
          </div>
        </div>
      `;

      container.innerHTML = itemsHtml;
    },

    // Background fetch of all active services to ensure metadata is always present
    fetchAndCacheServices: async function() {
      try {
        const apiBase = window.SERVEASY_API_BASE || (window.location.pathname.match(/^(.*?)Frontend\//i) ? window.location.pathname.match(/^(.*?)Frontend\//i)[1] + 'backend/api/' : '/backend/api/');
        const res = await fetch(apiBase + 'services.php?limit=100&sort=default');
        const data = await res.json();
        if (data && data.success && Array.isArray(data.data)) {
          this.cacheServices(data.data);
          this.renderAsideDrawer();
        }
      } catch (e) {
        // Fallback uses stored metadata
      }
    }
  };

  window.ServEasyWishlist = ServEasyWishlist;

  // Intercept click on wishlist sidebar open buttons
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-panel-btn="whishlist"]');
    if (btn) {
      ServEasyWishlist.renderAsideDrawer();
    }
  });

  function bootWishlist() {
    ServEasyWishlist.updateBadges();
    ServEasyWishlist.syncAllButtons();
    ServEasyWishlist.renderAsideDrawer();
    ServEasyWishlist.fetchAndCacheServices();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootWishlist);
  } else {
    bootWishlist();
  }

  // Cross-tab synchronization
  window.addEventListener('storage', function(e) {
    if (e.key === STORAGE_KEY || e.key === DATA_KEY) {
      ServEasyWishlist.updateBadges();
      ServEasyWishlist.syncAllButtons();
      ServEasyWishlist.renderAsideDrawer();
    }
  });

  // Re-render sidebar on global event
  window.addEventListener('wishlist:updated', function() {
    ServEasyWishlist.renderAsideDrawer();
  });

})();
