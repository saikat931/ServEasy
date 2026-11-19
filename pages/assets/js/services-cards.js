/**
 * ServEasy - Unified Services Cards Engine & Booking Controller
 * Shared between Home Page (Frontend/Web/index.php) and Services Page (Frontend/Web/pages/services.php)
 */

(function (window) {
  'use strict';

  function getApiBase() {
    if (window.SERVEASY_API_BASE) return window.SERVEASY_API_BASE;
    const match = window.location.pathname.match(/^(.*?)Frontend\//i);
    return match ? (match[1] + 'backend/api/') : '/backend/api/';
  }

  function getPagesBase() {
    return window.location.pathname.includes('/pages/') ? './' : './pages/';
  }

  // Subcategory mappings to Icons and Badges
  const categoryIcons = {
    1: 'car-sport-outline',     // Bike Wash
    2: 'car-sport-outline',     // Car Wash
    3: 'construct-outline',     // Vehicle Repair
    4: 'build-outline',         // Vehicle Servicing
    5: 'settings-outline',      // Vehicle Parts
    6: 'construct-outline',     // Garage Service
    7: 'home-outline',          // Home Vehicle Service
    8: 'home-outline',          // Room Cleaning
    9: 'water-outline',         // Bathroom Cleaning
    10: 'restaurant-outline',   // Kitchen Cleaning
    11: 'sparkles-outline',     // Deep Cleaning
    12: 'color-wand-outline',   // Decoration Service
    13: 'brush-outline',        // Painting Service
    14: 'shield-outline',       // Watchman
    15: 'shield-half-outline',  // Security Guard
    16: 'people-outline',       // Maid
    17: 'restaurant-outline',   // Cook
    18: 'egg-outline',          // Baby Care
    19: 'heart-outline',        // Elder Care
    20: 'home-outline',         // Caretaker
    21: 'medkit-outline',       // Nurse
    22: 'compass-outline',      // Guide
    23: 'flash-outline',        // Electrician
    24: 'water-outline',        // Plumber
    25: 'medkit-outline',       // Ambulance
    26: 'alert-circle-outline', // Roadside Assistance
    27: 'key-outline',          // Locksmith
    28: 'hardware-chip-outline',// AC Repair
    29: 'snow-outline',         // Refrigerator Repair
    30: 'shirt-outline',        // Washing Machine Repair
    31: 'tv-outline',           // TV Repair
    32: 'laptop-outline',       // Laptop Repair
    33: 'smartphone-outline',   // Mobile Repair
    34: 'shirt-outline',        // Cloth Washing
    35: 'cut-outline',          // Dry Cleaning
    36: 'shirt-outline',        // Ironing
    37: 'bicycle-outline',      // Bike Rental
    38: 'car-outline',          // Car Rental
    39: 'car-sport-outline',    // Auto Rental
    40: 'bus-outline',          // Van Rental
    41: 'bicycle-outline',      // Goods Carrier Bike
    42: 'truck-outline',        // Pickup Van
    43: 'truck-outline',        // Truck
    44: 'truck-outline',        // Mini Truck
    45: 'mail-outline',         // Courier Vehicle
    46: 'compass-outline',      // Tour Guide
    47: 'briefcase-outline',    // Tourist Package
    48: 'map-outline',          // Place Visit Service
    49: 'bed-outline',          // One Night Room
    50: 'bed-outline',          // Guest Room
    51: 'people-outline',       // Shared Room
    52: 'locate-outline',       // Rest Room
    53: 'home-outline',         // Visitor Stay
    54: 'restaurant-outline',   // Restaurant Food Ordering
    55: 'pizza-outline',        // Home Food Delivery
    56: 'bus-outline',          // Bus Ticket Booking
    57: 'ticket-outline'        // Bus Seat Reservation
  };

  const categoryBadges = {
    1: 'Bike Wash',
    2: 'Car Wash',
    3: 'Mechanical Repair',
    4: 'Vehicle Servicing',
    5: 'Parts Replacement',
    6: 'Garage Service',
    7: 'Home Service',
    8: 'Room Cleaning',
    9: 'Bathroom Wash',
    10: 'Kitchen Wash',
    11: 'Deep Cleaning',
    12: 'Room Decor',
    13: 'Painting',
    14: 'Watchman Guard',
    15: 'Security Officer',
    16: 'Verified Maid',
    17: 'Home Chef',
    18: 'Baby Sitter',
    19: 'Elderly Care',
    20: 'Caretaker Support',
    21: 'Personal Nurse',
    22: 'Travel Guide',
    23: 'Electrician Fix',
    24: 'Plumbing Works',
    25: 'Ambulance SOS',
    26: 'Roadside Assist',
    27: 'Locksmith key',
    28: 'AC Service',
    29: 'Fridge Repair',
    30: 'Washer Repair',
    31: 'TV diagnostics',
    32: 'Laptop Fix',
    33: 'Mobile Service',
    34: 'Laundry Washing',
    35: 'Dry Cleaning',
    36: 'Steam Ironing',
    37: 'Scooter Rental',
    38: 'Self-Drive Car',
    39: 'Auto Rental',
    40: 'Utility Van Rent',
    41: 'Cargo Bike Express',
    42: 'Logistics Pickup',
    43: 'Cargo Truck',
    44: 'Mini Truck Shifter',
    45: 'Courier Delivery',
    46: 'Sightseeing Tour',
    47: 'Tourist Package',
    48: 'Heritage Guide',
    49: 'Transit Lodge',
    50: 'Guest Room Night',
    51: 'Co-Living Shared PG',
    52: 'Hygiene Locator',
    53: 'Visitor Stay',
    54: 'Dine & Order',
    55: 'Express Delivery',
    56: 'Luxury Sleeper Bus',
    57: 'Bus Seat Reservation'
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

  // Determine Category CSS Class
  function getCategoryClass(catId, catSlug) {
    const id = parseInt(catId, 10);
    if (id === 1 || catSlug === 'vehicle-services' || catSlug === 'automotive') return 'cat-automotive';
    if (id === 2 || catSlug === 'home-services' || catSlug === 'home') return 'cat-home';
    if (id === 3 || catSlug === 'personal-staff-&-maids' || catSlug === 'care') return 'cat-care';
    if (id === 4 || catSlug === 'transport-&-rental' || catSlug === 'transport') return 'cat-transport';
    if (id === 5 || catSlug === 'rooms-&-lodging' || catSlug === 'stay') return 'cat-stay';
    if (id === 6 || catSlug === 'food-&-dining' || catSlug === 'food') return 'cat-food';
    if (id === 7 || catSlug === 'emergency-sos' || catSlug === 'emergency') return 'cat-emergency';
    return 'cat-home';
  }

  // Determine Subcategory / Service Icon
  function getServiceIcon(service) {
    if (service.subcategory_icon && service.subcategory_icon.endsWith('-outline')) {
      return service.subcategory_icon;
    }
    const iconMap = {
      'car-wash': 'car-sport-outline',
      'bike-wash': 'bicycle-outline',
      'vehicle-repair': 'construct-outline',
      'vehicle-servicing': 'build-outline',
      'room-cleaning': 'home-outline',
      'house-maid': 'people-outline',
      'deep-cleaning': 'sparkles-outline',
      'ac-service': 'hardware-chip-outline',
      'laundry-washing': 'shirt-outline',
      'car-rental': 'car-outline',
      'scooter-rental': 'bicycle-outline',
      'goods-cargo-carrier': 'truck-outline',
      'bus-ticket-booking': 'bus-outline',
      'short-period-rooms': 'bed-outline',
      'paying-guest-pg-stays': 'business-outline',
      'shared-accommodation': 'people-outline',
      'sightseeing-tours': 'compass-outline',
      'restroom-locker-search': 'locate-outline',
      'restaurant-orders-dining': 'fast-food-outline',
      'emergency-roadside-towing': 'car-sport-outline',
      'roadside-towing': 'car-sport-outline',
      'medical-sos': 'medkit-outline'
    };
    if (service.subcategory_slug && iconMap[service.subcategory_slug]) {
      return iconMap[service.subcategory_slug];
    }
    if (service.subcategory_id && categoryIcons[service.subcategory_id]) {
      return categoryIcons[service.subcategory_id];
    }
    const catClass = getCategoryClass(service.category_id, service.category_slug);
    const catIconMap = {
      'cat-automotive': 'car-sport-outline',
      'cat-home': 'home-outline',
      'cat-care': 'people-outline',
      'cat-transport': 'bus-outline',
      'cat-stay': 'bed-outline',
      'cat-food': 'restaurant-outline',
      'cat-emergency': 'alert-circle-outline'
    };
    return catIconMap[catClass] || 'grid-outline';
  }

  // Single logic to render a Service Card element matching individual services and packages
  function renderServiceCard(service) {
    if (window.ServEasyWishlist && typeof window.ServEasyWishlist.cacheServices === 'function') {
      window.ServEasyWishlist.cacheServices([service]);
    }
    const slug = service.slug || '';
    const catClass = getCategoryClass(service.category_id, service.category_slug);
    const icon = getServiceIcon(service);

    // Card Title & Details directly from individual service package or service
    const title = service.name || 'Untitled Service';
    const details = service.description || '';

    // Badge directly from database (subcategory name or category name)
    const badge = service.subcategory_name || categoryBadges[service.subcategory_id] || service.category_name || 'Service';

    // Price strictly from database base_price / package price
    const price = parseFloat(service.base_price);
    const priceFormatted = isNaN(price) ? '0.00' : (price % 1 === 0 ? price.toFixed(0) : price.toFixed(2));
    const priceStr = `$${priceFormatted}`;

    // Wizard routes
    let cardHref = null;
    const sSubSlug = (service.subcategory_slug || '').toLowerCase();
    const sNameLower = (service.name || '').toLowerCase();
    const isBikeWash = slug === 'bike-wash' || sSubSlug.includes('bike') || sNameLower.includes('bike') || sNameLower.includes('motorcycle');
    const isScooterWash = sSubSlug.includes('scooter') || sNameLower.includes('scooter') || sNameLower.includes('scooty') || sNameLower.includes('moped');
    const isSuvWash = sSubSlug.includes('suv') || sNameLower.includes('suv') || sNameLower.includes('muv');
    const isLuxuryWash = sSubSlug.includes('luxury') || sNameLower.includes('luxury') || sNameLower.includes('sports car');
    const isCarWash = slug === 'vehicle-car-and-bike-wash' || slug === 'vehicle-car-&-bike-wash' || sSubSlug.includes('car') || sNameLower.includes('car wash') || sNameLower.includes('wash');

    if (isBikeWash) {
      cardHref = `${getPagesBase()}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=bike-wash&vehicle=Bike`;
      if (service.package_id) cardHref += `&package_id=${service.package_id}`;
    } else if (isScooterWash) {
      cardHref = `${getPagesBase()}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=bike-wash&vehicle=Scooter`;
      if (service.package_id) cardHref += `&package_id=${service.package_id}`;
    } else if (isSuvWash) {
      cardHref = `${getPagesBase()}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=car-wash&vehicle=SUV`;
      if (service.package_id) cardHref += `&package_id=${service.package_id}`;
    } else if (isLuxuryWash) {
      cardHref = `${getPagesBase()}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=car-wash&vehicle=Luxury`;
      if (service.package_id) cardHref += `&package_id=${service.package_id}`;
    } else if (isCarWash) {
      cardHref = `${getPagesBase()}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=car-wash&vehicle=Car`;
      if (service.package_id) cardHref += `&package_id=${service.package_id}`;
    } else if (slug === 'personal-staff-and-maids' || slug === 'personal-staff-&-maids' || sSubSlug === 'house-maid') {
      cardHref = `${getPagesBase()}services/personal-staff-&-maids.php?service=personal-staff-and-maids`;
      if (service.package_id) {
        cardHref += `&package_id=${service.package_id}`;
      }
    }

    const btnText = (catClass === 'cat-emergency') ? 'SOS Alert' : 'Book Now';

    const card = document.createElement('div');
    card.className = `service-card ${catClass} cat-${service.category_id || 1}`;
    card.setAttribute('data-tags', `${title} ${details} ${badge} ${catClass} ${service.parent_service_name || ''} ${service.category_name || ''} ${service.subcategory_name || ''}`.toLowerCase());

    const targetServiceId = service.service_id || service.id;
    const targetPackageId = service.package_id || null;

    let buttonHtml = '';
    if (cardHref) {
      buttonHtml = `
        <a href="${cardHref}" class="btn-book-glass" style="text-decoration: none;">
          <span>${btnText}</span> <ion-icon name="arrow-forward-outline"></ion-icon>
        </a>
      `;
    } else {
      buttonHtml = `
        <button class="btn-book-glass" onclick="handleServiceBookClick(${targetServiceId}, '${escapeQuote(slug)}', '${escapeQuote(title)}', ${targetPackageId || 'null'})">
          <span>${btnText}</span> <ion-icon name="arrow-forward-outline"></ion-icon>
        </button>
      `;
    }

    const isFav = window.ServEasyWishlist ? window.ServEasyWishlist.has(service.id) : false;
    const favIcon = isFav ? 'heart' : 'heart-outline';
    const favClass = isFav ? 'btn-card-wishlist active' : 'btn-card-wishlist';
    const favTitle = isFav ? 'Remove from Wishlist' : 'Save to Wishlist';

    card.innerHTML = `
      <div class="card-top">
        <div class="icon-box-glass"><ion-icon name="${icon}"></ion-icon></div>
        <div class="card-top-meta">
          <span class="card-badge">${escapeHTML(badge)}</span>
          <button type="button" class="${favClass}" data-service-id="${service.id}" onclick="window.ServEasyWishlist && ServEasyWishlist.toggle(${service.id}, event, '${escapeQuote(title)}')" title="${favTitle}" aria-label="Wishlist">
            <ion-icon name="${favIcon}"></ion-icon>
          </button>
        </div>
      </div>
      <h4 class="card-title-glass">${escapeHTML(title)}</h4>
      <p class="card-desc-glass">${escapeHTML(details)}</p>
      <div class="card-footer-glass">
        <div class="card-price-container">
          <span class="card-price-label">Starts from</span>
          <span class="card-price-value">${priceStr}</span>
        </div>
        ${buttonHtml}
      </div>
    `;
    return card;
  }

  // Render animated glassmorphism skeleton card placeholders during lazy loading
  function renderSkeletonCards(container, count = 6) {
    if (!container) return;
    let html = '';
    for (let i = 0; i < count; i++) {
      html += `
        <div class="service-card skeleton-card">
          <div class="skeleton-header">
            <div class="skeleton-icon skeleton-pulse"></div>
            <div class="skeleton-pill skeleton-pulse"></div>
            <div class="skeleton-heart skeleton-pulse"></div>
          </div>
          <div class="skeleton-body">
            <div class="skeleton-title skeleton-pulse"></div>
            <div class="skeleton-line skeleton-pulse" style="width: 92%;"></div>
            <div class="skeleton-line skeleton-pulse" style="width: 68%;"></div>
          </div>
          <div class="skeleton-footer">
            <div class="skeleton-price skeleton-pulse"></div>
            <div class="skeleton-btn skeleton-pulse"></div>
          </div>
        </div>`;
    }
    container.innerHTML = html;
  }

  // Service click router with optional packageId support
  function handleServiceBookClick(serviceId, serviceSlug, serviceName, packageId = null) {
    const sSlug = (serviceSlug || '').toLowerCase();
    const sName = (serviceName || '').toLowerCase();
    const isBikeWash = sSlug === 'bike-wash' || sSlug.includes('bike') || sName.includes('bike') || sName.includes('motorcycle');
    const isScooterWash = sSlug.includes('scooter') || sName.includes('scooter') || sName.includes('scooty') || sName.includes('moped');
    const isSuvWash = sSlug.includes('suv') || sName.includes('suv') || sName.includes('muv');
    const isLuxuryWash = sSlug.includes('luxury') || sName.includes('luxury') || sName.includes('sports car');
    const isCarWash = sSlug === 'vehicle-car-and-bike-wash' || sSlug === 'vehicle-car-&-bike-wash' || sSlug.includes('car') || sName.includes('car wash') || sName.includes('wash');

    if (isBikeWash) {
      let url = `${getPagesBase()}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=bike-wash&vehicle=Bike`;
      if (packageId) url += `&package_id=${packageId}`;
      window.location.href = url;
    } else if (isScooterWash) {
      let url = `${getPagesBase()}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=bike-wash&vehicle=Scooter`;
      if (packageId) url += `&package_id=${packageId}`;
      window.location.href = url;
    } else if (isSuvWash) {
      let url = `${getPagesBase()}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=car-wash&vehicle=SUV`;
      if (packageId) url += `&package_id=${packageId}`;
      window.location.href = url;
    } else if (isLuxuryWash) {
      let url = `${getPagesBase()}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=car-wash&vehicle=Luxury`;
      if (packageId) url += `&package_id=${packageId}`;
      window.location.href = url;
    } else if (isCarWash) {
      let url = `${getPagesBase()}services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=car-wash&vehicle=Car`;
      if (packageId) url += `&package_id=${packageId}`;
      window.location.href = url;
    } else if (sSlug === 'personal-staff-and-maids' || sSlug === 'personal-staff-&-maids' || sName.includes('maid')) {
      let url = `${getPagesBase()}services/personal-staff-&-maids.php?service=personal-staff-and-maids`;
      if (packageId) url += `&package_id=${packageId}`;
      window.location.href = url;
    } else {
      openBookingModalDynamic(serviceId, serviceName, packageId);
    }
  }

  // Format services into individual service cards
  function flattenToIndividualServices(servicesList) {
    if (!Array.isArray(servicesList)) return [];
    return servicesList.map(service => {
      const srvId = parseInt(service.id, 10);
      return {
        ...service,
        id: srvId,
        service_id: srvId,
        package_id: null,
        is_package: false,
        name: service.name,
        base_price: parseFloat(service.base_price || 0)
      };
    });
  }

  // Unified Grid Renderer & Filter Engine
  function renderServicesGrid(servicesList, gridElement, countElement, filters = {}) {
    if (!gridElement) return [];
    gridElement.innerHTML = '';

    // Expand all services and their packages into individual service cards
    const allIndividualServices = flattenToIndividualServices(servicesList);

    if (window.ServEasyWishlist && typeof window.ServEasyWishlist.cacheServices === 'function' && Array.isArray(allIndividualServices)) {
      window.ServEasyWishlist.cacheServices(allIndividualServices);
    }

    const activeCat = filters.category || 'all';
    const activeQuery = (filters.query || '').toLowerCase().trim();
    const activeServiceSlug = filters.serviceSlug ? String(filters.serviceSlug).toLowerCase() : null;
    const activeServiceId = filters.serviceId ? String(filters.serviceId) : null;
    const maxPrice = filters.maxPrice !== undefined ? parseFloat(filters.maxPrice) : null;
    const sort = filters.sort || 'default';

    // 1. Filter
    let filtered = allIndividualServices.filter(service => {
      // Slug match
      if (activeServiceSlug) {
        const sSlug = (service.slug || '').toLowerCase();
        const sSubSlug = (service.subcategory_slug || '').toLowerCase();
        const sName = (service.name || '').toLowerCase();
        if (sSlug !== activeServiceSlug && sSubSlug !== activeServiceSlug && !sName.includes(activeServiceSlug)) {
          return false;
        }
      }

      // Specific Service ID filter (fallback)
      if (activeServiceId) {
        const targetId = String(activeServiceId);
        const matchId = String(service.id) === targetId 
          || String(service.service_id) === targetId 
          || String(service.package_id) === targetId;
        if (!matchId) return false;
      }

      // Category Match
      let matchesCat = (activeCat === 'all');
      if (!matchesCat) {
        const catVal = String(activeCat).toLowerCase();
        const sCatId = String(service.category_id);
        const sCatSlug = (service.category_slug || '').toLowerCase();
        const sCatName = (service.category_name || '').toLowerCase();
        const sCatClass = getCategoryClass(service.category_id, service.category_slug);

        if (sCatId === catVal || sCatClass === 'cat-' + catVal || sCatSlug === catVal || sCatSlug.includes(catVal) || sCatName.includes(catVal)) {
          matchesCat = true;
        } else if ((catVal === 'automotive' || catVal === 'vehicle-services' || catVal === '1') && (sCatId === '1' || sCatSlug === 'vehicle-services')) {
          matchesCat = true;
        } else if ((catVal === 'home' || catVal === 'home-services' || catVal === '2') && (sCatId === '2' || sCatSlug === 'home-services')) {
          matchesCat = true;
        } else if ((catVal === 'care' || catVal === 'personal-staff-&-maids' || catVal === '3') && (sCatId === '3' || sCatSlug === 'personal-staff-&-maids')) {
          matchesCat = true;
        } else if ((catVal === 'transport' || catVal === 'transport-&-rental' || catVal === '4') && (sCatId === '4' || sCatSlug === 'transport-&-rental')) {
          matchesCat = true;
        } else if ((catVal === 'stay' || catVal === 'rooms-&-lodging' || catVal === '5') && (sCatId === '5' || sCatSlug === 'rooms-&-lodging')) {
          matchesCat = true;
        } else if ((catVal === 'food' || catVal === 'food-&-dining' || catVal === '6') && (sCatId === '6' || sCatSlug === 'food-&-dining')) {
          matchesCat = true;
        } else if ((catVal === 'emergency' || catVal === 'emergency-sos' || catVal === '7') && (sCatId === '7' || sCatSlug === 'emergency-sos')) {
          matchesCat = true;
        }
      }
      if (!matchesCat) return false;

      // Price Match
      if (maxPrice !== null && maxPrice < 500) {
        const p = parseFloat(service.base_price);
        if (!isNaN(p) && p > maxPrice) return false;
      }

      // Search Query Match
      if (activeQuery) {
        const badge = service.subcategory_name || categoryBadges[service.subcategory_id] || '';
        const catClass = getCategoryClass(service.category_id, service.category_slug);
        const parentName = service.parent_service_name || '';
        const searchable = `${service.name || ''} ${service.description || ''} ${badge} ${catClass} ${parentName} ${service.category_name || ''} ${service.subcategory_name || ''}`.toLowerCase();
        if (!searchable.includes(activeQuery)) return false;
      }

      return true;
    });

    // 2. Sort
    if (sort === 'price-asc') {
      filtered.sort((a, b) => parseFloat(a.base_price || 0) - parseFloat(b.base_price || 0));
    } else if (sort === 'price-desc') {
      filtered.sort((a, b) => parseFloat(b.base_price || 0) - parseFloat(a.base_price || 0));
    } else if (sort === 'title-asc') {
      filtered.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
    } else if (sort === 'title-desc') {
      filtered.sort((a, b) => (b.name || '').localeCompare(a.name || ''));
    } else if (sort === 'newest') {
      filtered.sort((a, b) => (b.id || 0) - (a.id || 0));
    }

    // 3. Render Cards
    filtered.forEach(service => {
      const card = renderServiceCard(service);
      gridElement.appendChild(card);
    });

    // 4. Update Count
    if (countElement) {
      if (filtered.length === 0) {
        countElement.textContent = activeQuery ? 'Found 0 matching services' : 'No services in this category';
      } else {
        countElement.textContent = (activeServiceSlug || activeServiceId) && filtered.length === 1
          ? `Showing selected service: ${filtered[0].name}`
          : (activeQuery
            ? `Found ${filtered.length} matching service${filtered.length !== 1 ? 's' : ''}`
            : `Showing ${filtered.length} service${filtered.length !== 1 ? 's' : ''}`);
      }
    }

    return filtered;
  }

  // Booking Modal Management
  let currentServiceId = null;
  let userIsLoggedIn = false;

  function initBookingModal(isLoggedIn) {
    userIsLoggedIn = Boolean(isLoggedIn);

    // Default Date to Tomorrow
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dateInput = document.getElementById('booking-date');
    if (dateInput) {
      dateInput.min = today.toISOString().split('T')[0];
      dateInput.value = tomorrow.toISOString().split('T')[0];
    }

    // Default Time
    const timeInput = document.getElementById('booking-time');
    if (timeInput) {
      timeInput.value = "10:00";
    }

    // Close on overlay backdrop click
    const overlay = document.getElementById('booking-modal-overlay');
    if (overlay) {
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
          closeBookingModal();
        }
      });
    }
  }

  async function openBookingModalDynamic(serviceId, serviceName, packageId = null) {
    if (!userIsLoggedIn) {
      if (window.Notify && window.Notify.warning) {
        window.Notify.warning("Please login to book a service.");
      } else {
        alert("Please login to book a service.");
      }

      const currentUrl = new URL(window.location.href);
      currentUrl.searchParams.set('book', '1');
      currentUrl.searchParams.set('service_id', serviceId);
      if (packageId) currentUrl.searchParams.set('package_id', packageId);
      if (serviceName) currentUrl.searchParams.set('service_name', serviceName);

      const isSubpages = window.location.pathname.includes('/pages/services/');
      const isInPages = window.location.pathname.includes('/pages/');
      const authPath = isSubpages ? '../../auth/auth.php' : (isInPages ? '../auth/auth.php' : './auth/auth.php');
      const redirectUrl = currentUrl.toString();

      try {
        sessionStorage.setItem('serveasy_auth_redirect', redirectUrl);
        sessionStorage.setItem('serveasy_auto_open_service_id', serviceId);
        if (packageId) sessionStorage.setItem('serveasy_auto_open_package_id', packageId);
        if (serviceName) sessionStorage.setItem('serveasy_auto_open_service_name', serviceName);
      } catch (e) {}

      window.location.href = authPath + '?redirect=' + encodeURIComponent(redirectUrl);
      return;
    }

    currentServiceId = serviceId;
    const overlay = document.getElementById('booking-modal-overlay');
    const formState = document.getElementById('modal-form-state');
    const successState = document.getElementById('modal-success-state');
    const select = document.getElementById('booking-sub-type');

    if (document.getElementById('wizard-service-name')) {
      document.getElementById('wizard-service-name').textContent = serviceName ? 'Book ' + serviceName : 'Loading Options...';
    }
    if (document.getElementById('booking-service-title')) {
      document.getElementById('booking-service-title').value = serviceName;
    }
    // Check in-memory or sessionStorage cache first for instant 0ms modal render
    window.ServEasyServiceCache = window.ServEasyServiceCache || {};
    let cachedService = window.ServEasyServiceCache[serviceId];
    if (!cachedService) {
      try {
        const stored = sessionStorage.getItem('serveasy_srv_' + serviceId);
        if (stored) cachedService = JSON.parse(stored);
      } catch (e) {}
    }

    const applyServiceData = (service) => {
      if (document.getElementById('wizard-service-name')) {
        document.getElementById('wizard-service-name').textContent = 'Book ' + (serviceName || service.name);
      }
      if (select) {
        select.innerHTML = '';
        if (service.packages && service.packages.length > 0) {
          service.packages.forEach(pkg => {
            const opt = document.createElement('option');
            opt.value = pkg.id;
            opt.textContent = `${pkg.name} ($${parseFloat(pkg.price).toFixed(0)})`;
            if (packageId && String(pkg.id) === String(packageId)) {
              opt.selected = true;
            }
            select.appendChild(opt);
          });
          if (packageId) {
            select.value = packageId;
          }
        } else {
          const opt = document.createElement('option');
          opt.value = '';
          opt.textContent = `Standard package ($${parseFloat(service.base_price).toFixed(0)})`;
          select.appendChild(opt);
        }
      }
    };

    if (cachedService) {
      applyServiceData(cachedService);
    } else if (select) {
      select.innerHTML = '<option>Loading...</option>';
    }

    if (formState) formState.style.display = 'block';
    if (successState) successState.style.display = 'none';
    if (overlay) overlay.classList.add('active');

    try {
      const res = await fetch(`${getApiBase()}services.php?id=${serviceId}`);
      const data = await res.json();

      if (data.success && data.data) {
        const service = data.data;
        window.ServEasyServiceCache[serviceId] = service;
        try {
          sessionStorage.setItem('serveasy_srv_' + serviceId, JSON.stringify(service));
        } catch (e) {}
        applyServiceData(service);
      } else {
        if (!cachedService) {
          if (select) select.innerHTML = '<option value="">Base Service Option</option>';
          if (document.getElementById('wizard-service-name')) {
            document.getElementById('wizard-service-name').textContent = 'Book ' + (serviceName || 'Service');
          }
        }
      }
    } catch (err) {
      console.error("Failed to load booking details", err);
      if (!cachedService) {
        if (select) select.innerHTML = '<option value="">Base Service Option</option>';
        if (document.getElementById('wizard-service-name')) {
          document.getElementById('wizard-service-name').textContent = 'Book ' + (serviceName || 'Service');
        }
      }
    }
  }

  function closeBookingModal() {
    const overlay = document.getElementById('booking-modal-overlay');
    if (overlay) {
      overlay.classList.remove('active');
    }
  }

  async function handleBookingSubmit(event) {
    event.preventDefault();

    const serviceName = document.getElementById('booking-service-title') ? document.getElementById('booking-service-title').value : '';
    const packageId = document.getElementById('booking-sub-type') ? document.getElementById('booking-sub-type').value : null;
    const dateVal = document.getElementById('booking-date') ? document.getElementById('booking-date').value : '';
    const timeVal = document.getElementById('booking-time') ? document.getElementById('booking-time').value : '';
    const addressVal = document.getElementById('booking-address') ? document.getElementById('booking-address').value : '';
    const notesVal = document.getElementById('booking-notes') ? document.getElementById('booking-notes').value : '';

    const select = document.getElementById('booking-sub-type');
    const optionText = select && select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : 'Standard Package';

    const submitBtn = event.target.querySelector('button[type="submit"]');
    const origText = submitBtn ? submitBtn.textContent : 'Confirm Booking';
    if (submitBtn) {
      submitBtn.textContent = "Processing...";
      submitBtn.disabled = true;
    }

    try {
      const response = await fetch(getApiBase() + 'bookings.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          service_id: currentServiceId,
          package_id: packageId || null,
          schedule_date: dateVal,
          schedule_time: timeVal,
          address: addressVal,
          notes: notesVal
        })
      });

      const result = await response.json();
      if (submitBtn) {
        submitBtn.textContent = origText;
        submitBtn.disabled = false;
      }

      if (result.success && result.data) {
        const booking = result.data;

        // Populate success ticket
        if (document.getElementById('res-ref')) document.getElementById('res-ref').textContent = booking.booking_number;
        if (document.getElementById('res-service')) document.getElementById('res-service').textContent = serviceName;
        if (document.getElementById('res-type')) document.getElementById('res-type').textContent = optionText;
        if (document.getElementById('res-schedule')) document.getElementById('res-schedule').textContent = dateVal + ' - ' + timeVal;

        // Swap wizard states
        const formState = document.getElementById('modal-form-state');
        const successState = document.getElementById('modal-success-state');
        if (formState) formState.style.display = 'none';
        if (successState) successState.style.display = 'block';
      } else {
        const msg = "Booking failed: " + (result.message || "Unknown error");
        if (window.Notify && window.Notify.error) window.Notify.error(msg);
        else alert(msg);
      }
    } catch (err) {
      console.error("Booking API Error:", err);
      if (submitBtn) {
        submitBtn.textContent = origText;
        submitBtn.disabled = false;
      }
      const msg = "Server error when processing booking.";
      if (window.Notify && window.Notify.error) window.Notify.error(msg);
      else alert(msg);
    }
  }

  // Unified Category Cards Filter Engine
  function filterCategoryCards(filterCategory = 'all', searchQuery = '') {
    window.activeCategoryFilter = filterCategory;
    window.activeCategorySearch = searchQuery;

    const catCards = document.querySelectorAll('.category-item');
    const subCards = document.querySelectorAll('.subcategory-item');
    const counterEl = document.getElementById('categories-count');
    const btnSub = document.getElementById('btn-view-subcategories');
    const isSubActive = btnSub && btnSub.classList.contains('active');

    const currentFilter = String(filterCategory || 'all').toLowerCase().trim();
    const query = String(searchQuery || '').toLowerCase().trim();

    // 1. Filter Category Domains
    let visibleCatCount = 0;
    if (catCards && catCards.length > 0) {
      catCards.forEach(card => {
        const domain = (card.getAttribute('data-domain') || '').toLowerCase();
        const catId = card.getAttribute('data-cat-id') || '';
        const searchData = (card.getAttribute('data-search') || '').toLowerCase();

        let matchFilter = (currentFilter === 'all');
        if (!matchFilter) {
          if (domain === currentFilter || 
              domain.includes(currentFilter) || 
              catId === currentFilter || 
              currentFilter.includes(domain)) {
            matchFilter = true;
          }
        }

        let matchSearch = true;
        if (query) {
          matchSearch = searchData.includes(query);
        }

        if (matchFilter && matchSearch) {
          card.style.display = '';
          visibleCatCount++;
        } else {
          card.style.display = 'none';
        }
      });
    }

    // 2. Filter Subcategories
    let visibleSubCount = 0;
    if (subCards && subCards.length > 0) {
      subCards.forEach(card => {
        const domain = (card.getAttribute('data-domain') || '').toLowerCase();
        const catId = card.getAttribute('data-cat-id') || '';
        const searchData = (card.getAttribute('data-search') || '').toLowerCase();

        let matchFilter = (currentFilter === 'all');
        if (!matchFilter) {
          if (domain === currentFilter || 
              domain.includes(currentFilter) || 
              catId === currentFilter || 
              currentFilter.includes(domain)) {
            matchFilter = true;
          }
        }

        let matchSearch = true;
        if (query) {
          matchSearch = searchData.includes(query);
        }

        if (matchFilter && matchSearch) {
          card.style.display = '';
          visibleSubCount++;
        } else {
          card.style.display = 'none';
        }
      });
    }

    if (counterEl) {
      if (isSubActive) {
        counterEl.textContent = `Showing ${visibleSubCount} subcategor${visibleSubCount !== 1 ? 'ies' : 'y'}`;
      } else {
        counterEl.textContent = `Showing ${visibleCatCount} domain${visibleCatCount !== 1 ? 's' : ''}`;
      }
    }

    return isSubActive ? visibleSubCount : visibleCatCount;
  }

  // Initialize standalone Category page search & pills if present
  function initCategoryFilterControls(searchInputId = 'category-search', pillSelector = '.cat-pill') {
    const searchInput = document.getElementById(searchInputId);
    const pills = document.querySelectorAll(pillSelector);

    let activeFilter = 'all';
    let activeQuery = '';

    function apply() {
      filterCategoryCards(activeFilter, activeQuery);
    }

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        activeQuery = this.value;
        apply();
      });
    }

    if (pills && pills.length > 0) {
      pills.forEach(pill => {
        pill.addEventListener('click', function () {
          pills.forEach(p => p.classList.remove('active'));
          this.classList.add('active');
          activeFilter = this.getAttribute('data-filter') || 'all';
          apply();
        });
      });
    }
  }

  // Expose globally
  window.categoryIcons = categoryIcons;
  window.categoryBadges = categoryBadges;
  window.escapeHTML = escapeHTML;
  window.escapeQuote = escapeQuote;
  window.getCategoryClass = getCategoryClass;
  window.getServiceIcon = getServiceIcon;
  window.renderServiceCard = renderServiceCard;
  window.renderSkeletonCards = renderSkeletonCards;
  window.handleServiceBookClick = handleServiceBookClick;
  window.renderServicesGrid = renderServicesGrid;
  window.filterCategoryCards = filterCategoryCards;
  window.initCategoryFilterControls = initCategoryFilterControls;
  // ─── Razorpay Marketplace Payment Web Controller ──────────────────────────
  window.initiateRazorpayPayment = async function (bookingId, amount, customerName, customerEmail, customerPhone, onSuccess, onError) {
    try {
      const res = await fetch(getApiBase() + 'create-payment-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: bookingId, amount: amount })
      });
      const orderData = await res.json();
      if (!orderData.success) {
        if (onError) onError(orderData.message || 'Failed to create payment order');
        return;
      }

      const options = {
        key: orderData.data.key_id,
        amount: orderData.data.amount,
        currency: orderData.data.currency,
        name: 'ServEasy Services',
        description: 'Booking #' + (orderData.data.booking_number || bookingId),
        order_id: orderData.data.order_id,
        handler: async function (response) {
          const vRes = await fetch(getApiBase() + 'verify-payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              booking_id: bookingId,
              razorpay_order_id: response.razorpay_order_id,
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_signature: response.razorpay_signature || 'mock_sig_dev'
            })
          });
          const vData = await vRes.json();
          if (vData.success) {
            if (onSuccess) onSuccess(vData);
          } else {
            if (onError) onError(vData.message || 'Verification failed');
          }
        },
        prefill: {
          name: customerName || 'Valued Customer',
          email: customerEmail || 'customer@serveasy.local',
          contact: customerPhone || '9876543210'
        },
        theme: {
          color: '#0084FF'
        }
      };

      if (window.Razorpay) {
        const rzp = new window.Razorpay(options);
        rzp.open();
      } else {
        const vRes = await fetch(getApiBase() + 'verify-payment.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            booking_id: bookingId,
            razorpay_order_id: orderData.data.order_id,
            razorpay_payment_id: 'pay_mock_' + Date.now(),
            razorpay_signature: 'mock_sig_dev'
          })
        });
        const vData = await vRes.json();
        if (onSuccess) onSuccess(vData);
      }
    } catch (err) {
      if (onError) onError(err.message || 'Payment processing error');
    }
  };

  window.initBookingModal = initBookingModal;
  window.openBookingModalDynamic = openBookingModalDynamic;
  window.closeBookingModal = closeBookingModal;
  window.handleBookingSubmit = handleBookingSubmit;

  window.ServEasyCards = {
    categoryIcons,
    categoryBadges,
    escapeHTML,
    escapeQuote,
    getCategoryClass,
    getServiceIcon,
    renderCard: renderServiceCard,
    renderSkeletonCards: renderSkeletonCards,
    handleBookClick: handleServiceBookClick,
    renderGrid: renderServicesGrid,
    filterCategories: filterCategoryCards,
    initCategoryControls: initCategoryFilterControls,
    initModal: initBookingModal,
    openModal: openBookingModalDynamic,
    closeModal: closeBookingModal,
    handleSubmit: handleBookingSubmit
  };

})(window);
