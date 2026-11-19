<?php
require_once __DIR__ . '/../common/auth_session.php';
if (!defined('SERVEASY_ASSET_VER')) {
    define('SERVEASY_ASSET_VER', '20261119v1');
}
$bootstrapPath = file_exists(__DIR__ . '/../../backend/api/bootstrap.php')
    ? __DIR__ . '/../../backend/api/bootstrap.php'
    : (file_exists(__DIR__ . '/../../../backend/api/bootstrap.php') ? __DIR__ . '/../../../backend/api/bootstrap.php' : null);
if ($bootstrapPath && file_exists($bootstrapPath)) {
    require_once $bootstrapPath;
}

$initialServices = [];
$initialCategories = [];

if (class_exists('App\Repositories\ServiceRepository')) {
    try {
        $serviceRepo = new \App\Repositories\ServiceRepository();
        $initialServices = $serviceRepo->search(['limit' => 100, 'sort' => 'default']);
        $initialCategories = $serviceRepo->getCategories();
    } catch (\Exception $e) {
        error_log("Failed to prefetch services on services page: " . $e->getMessage());
    }
} else {
    // Multi-Domain Cloud API Fallback
    $bridgePath = file_exists(__DIR__ . '/../common/ApiBridge.php') ? __DIR__ . '/../common/ApiBridge.php' : __DIR__ . '/../../common/ApiBridge.php';
    if (file_exists($bridgePath)) {
        require_once $bridgePath;
        $srvRes = \Frontend\ApiBridge::callApi('services.php?limit=100&sort=default');
        if (!empty($srvRes['data']) && is_array($srvRes['data'])) {
            $initialServices = $srvRes['data'];
        }
        $catRes = \Frontend\ApiBridge::callApi('categories.php');
        if (!empty($catRes['data']) && is_array($catRes['data'])) {
            $initialCategories = $catRes['data'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ServEasy - Browse Services</title>

  <!-- Favicon -->
  <link rel="shortcut icon" href="./assets/favicon.svg" type="image/svg+xml">
  <link rel="manifest" href="../manifest.json">
  <meta name="theme-color" content="#4361ee">

  <!-- Smart Catalog Cache & Service Worker Registration -->
  <script src="./assets/js/serveasy-catalog-cache.js?v=<?= SERVEASY_ASSET_VER ?>"></script>
  <script src="./assets/js/sw-register.js?v=<?= SERVEASY_ASSET_VER ?>"></script>

  <!-- Font & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

  <!-- Core Stylesheet imports -->
  <link rel="stylesheet" href="./assets/css/main.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/global.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/home.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/notify.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/wishlist.css?v=<?= SERVEASY_ASSET_VER ?>">
</head>

<body id="top">

  <!-- Floating glowing background circles for 3D depth -->
  <div class="glow-sphere sphere-1"></div>
  <div class="glow-sphere sphere-2"></div>
  <div class="glow-sphere sphere-3"></div>

  <!-- NAVIGATION HEADER -->
  <?php include __DIR__ . '/layout/header.php'; ?>

  <!-- SIDEBAR PANELS (Cart, Wishlist, Profile) -->
  <?php include __DIR__ . '/constants/aside.php'; ?>

  <main>
    <article class="dashboard-container">

      <!-- HERO BANNER -->
      <section class="hero-glass-box" style="padding: 40px 30px; margin-bottom: 40px;">
        <h2 class="hero-title" style="font-size: 2.5rem; margin-bottom: 10px;">Explore & Filter Local Services</h2>
        <p class="hero-subtitle" style="margin-bottom: 0;">
          Filter by category, price range, search terms, and sort by budget constraints. Book instantly with our transparent booking wizard.
        </p>
      </section>


      <!-- 2. ADVANCED FILTER BOX -->
      <section class="advanced-filter-box">
        <div class="filter-top-row">
          <div class="search-container" style="max-width: 100%; margin: 0 0 25px 0;">
            <span class="search-icon-glass"><ion-icon name="search-outline"></ion-icon></span>
            <input type="text" id="adv-search" placeholder="Type name, tag, or description to search (e.g. Wash, AC, PG, Towing...)" class="search-bar-glass">
          </div>

          <!-- CATEGORIES PILLS (Matching Home Page) -->
          <div class="categories-pills" id="services-pills">
            <div class="pill active" data-filter="all">All Services</div>
            <div class="pill" data-filter="1" data-slug="automotive">Automotive</div>
            <div class="pill" data-filter="2" data-slug="home">Home & Living</div>
            <div class="pill" data-filter="3" data-slug="care">Personal Staff</div>
            <div class="pill" data-filter="4" data-slug="transport">Transport & Rental</div>
            <div class="pill" data-filter="5" data-slug="stay">Rooms & Lodging</div>
            <div class="pill" data-filter="6" data-slug="food">Food & Dining</div>
            <div class="pill" data-filter="7" data-slug="emergency">Emergency SOS</div>
          </div>
        </div>

        <div class="filter-controls-grid">
          <!-- Category Filter -->
          <div class="filter-control-group">
            <label class="filter-label">Filter Category</label>
            <select id="filter-category" class="glass-select">
              <option value="all">All Categories</option>
              <?php if (!empty($initialCategories)): ?>
                <?php foreach ($initialCategories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat['id']) ?>" data-slug="<?= htmlspecialchars($cat['slug']) ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="1">Automotive & Vehicles</option>
                <option value="2">Home & Living Services</option>
                <option value="3">Personal Staff & Maids</option>
                <option value="4">Transport & Fleet Rental</option>
                <option value="5">Rooms & Lodging Stays</option>
                <option value="6">Food & Culinary Services</option>
                <option value="7">Emergency SOS & Roadside</option>
              <?php endif; ?>
            </select>
          </div>

          <!-- Price Slider -->
          <div class="filter-control-group">
            <div class="label-row">
              <label class="filter-label">Max Budget</label>
              <span class="slider-value" id="price-slider-val">$500+ (All)</span>
            </div>
            <input type="range" id="filter-price-range" min="0" max="500" value="500" class="glass-range-slider">
          </div>

          <!-- Sort Selector -->
          <div class="filter-control-group">
            <label class="filter-label">Sort Services</label>
            <select id="filter-sort" class="glass-select">
              <option value="default">Default Catalog</option>
              <option value="newest">Newest First</option>
              <option value="price-asc">Price: Low to High</option>
              <option value="price-desc">Price: High to Low</option>
              <option value="title-asc">A to Z (Ascending)</option>
              <option value="title-desc">Z to A (Descending)</option>
            </select>
          </div>
        </div>
      </section>

      <!-- SERVICES CARDS SECTION -->
      <section class="services-list-container">
        <div class="services-header">
          <h3 class="services-section-title" id="services-section-heading">All Services</h3>
          <span class="results-count" id="services-count">Loading services...</span>
        </div>

        <div class="services-grid" id="services-grid">
          <!-- Glassmorphic Skeleton Loading Placeholders (Zero-FOUC & Lazy-Loading) -->
          <?php for ($sk = 0; $sk < 9; $sk++): ?>
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
          </div>
          <?php endfor; ?>
        </div>

        <!-- Glassmorphic Loader Spinner -->
        <div class="loader-glass" id="services-loader" style="display: none;">
          <div class="spinner-glass"></div>
          <span class="loading-text">Loading more services...</span>
        </div>
      </section>

    </article>
  </main>

  <!-- GLASSMORPHIC INTERACTIVE WIZARD MODAL (SHARED COMPONENT) -->
  <?php include __DIR__ . '/constants/booking-modal.php'; ?>

  <!-- FOOTER -->
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- BACK TO TOP BUTTON -->
  <a href="#top" class="back-to-top" aria-label="Back to Top" data-back-top-btn>
    <ion-icon name="arrow-up-outline"></ion-icon>
  </a>

  <!-- JS imports -->
  <script src="./assets/js/script.js"></script>
  <script src="./assets/js/notify.js"></script>
  <script src="./assets/js/wishlist.js"></script>
  <script src="./assets/js/services-cards.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <!-- Services Page Script using Unified ServEasyCards Engine -->
  <script>
    // Elements
    const searchInput = document.getElementById('adv-search');
    const categorySelect = document.getElementById('filter-category');
    const priceRange = document.getElementById('filter-price-range');
    const priceVal = document.getElementById('price-slider-val');
    const sortSelect = document.getElementById('filter-sort');
    const servicesGrid = document.getElementById('services-grid');
    const countLabel = document.getElementById('services-count');
    const loader = document.getElementById('services-loader');
    const pills = document.querySelectorAll('#services-pills .pill');

    // State
    let allServices = <?php echo json_encode($initialServices ?: []); ?>;
    let loading = false;
    let currentServiceSlug = null;
    let currentServiceId = null;

    // Unified Grid Rendering using shared ServEasyCards engine
    function renderServices() {
      const headingEl = document.getElementById('services-section-heading');
      let catVal = 'all';
      if (categorySelect && categorySelect.selectedIndex >= 0) {
        const selectedOpt = categorySelect.options[categorySelect.selectedIndex];
        const selectedText = selectedOpt ? selectedOpt.textContent.trim() : '';
        if (categorySelect.value === 'all' || !selectedText) {
          if (headingEl) headingEl.textContent = 'All Services';
          catVal = 'all';
        } else {
          if (headingEl) headingEl.textContent = selectedText;
          catVal = selectedOpt.dataset.slug || categorySelect.value;
        }
      }

      ServEasyCards.renderGrid(allServices, servicesGrid, countLabel, {
        category: catVal,
        query: searchInput ? searchInput.value : '',
        serviceSlug: currentServiceSlug,
        serviceId: currentServiceId,
        maxPrice: priceRange ? parseInt(priceRange.value, 10) : 500,
        sort: sortSelect ? sortSelect.value : 'default'
      });
    }

    // Category Pills synchronizer supporting ID, slug, and category aliases
    function syncPills(selectedVal) {
      const valStr = String(selectedVal || 'all').toLowerCase();
      pills.forEach(p => {
        const pFilter = (p.getAttribute('data-filter') || '').toLowerCase();
        const pSlug = (p.getAttribute('data-slug') || '').toLowerCase();
        
        let match = (valStr === 'all' && pFilter === 'all')
          || (pFilter === valStr)
          || (pSlug === valStr)
          || ((valStr === 'vehicle-services' || valStr === 'automotive' || valStr === '1') && (pSlug === 'automotive' || pFilter === '1'))
          || ((valStr === 'home-services' || valStr === 'home' || valStr === '2') && (pSlug === 'home' || pFilter === '2'))
          || ((valStr === 'personal-staff-&-maids' || valStr === 'care' || valStr === '3') && (pSlug === 'care' || pFilter === '3'))
          || ((valStr === 'transport-&-rental' || valStr === 'transport' || valStr === '4') && (pSlug === 'transport' || pFilter === '4'))
          || ((valStr === 'rooms-&-lodging' || valStr === 'stay' || valStr === '5') && (pSlug === 'stay' || pFilter === '5'))
          || ((valStr === 'food-&-dining' || valStr === 'food' || valStr === '6') && (pSlug === 'food' || pFilter === '6'))
          || ((valStr === 'emergency-sos' || valStr === 'emergency' || valStr === '7') && (pSlug === 'emergency' || pFilter === '7'));

        if (match) {
          p.classList.add('active');
        } else {
          p.classList.remove('active');
        }
      });
    }

    // Pill click handlers
    pills.forEach(pill => {
      pill.addEventListener('click', () => {
        currentServiceSlug = null;
        currentServiceId = null;
        const filterVal = pill.getAttribute('data-filter') || pill.getAttribute('data-slug') || 'all';
        syncPills(filterVal);
        if (categorySelect) {
          for (let i = 0; i < categorySelect.options.length; i++) {
            const opt = categorySelect.options[i];
            const optVal = String(opt.value);
            const optSlug = (opt.dataset.slug || '').toLowerCase();
            if (optVal === filterVal || optSlug === filterVal 
                || (filterVal === 'automotive' && (optSlug === 'vehicle-services' || optVal === '1'))
                || (filterVal === '1' && (optSlug === 'vehicle-services' || optVal === '1'))
                || (filterVal === 'home' && (optSlug === 'home-services' || optVal === '2'))
                || (filterVal === 'care' && (optSlug === 'personal-staff-&-maids' || optVal === '3'))
                || (filterVal === 'transport' && (optSlug === 'transport-&-rental' || optVal === '4'))
                || (filterVal === 'stay' && (optSlug === 'rooms-&-lodging' || optVal === '5'))
                || (filterVal === 'food' && (optSlug === 'food-&-dining' || optVal === '6'))
                || (filterVal === 'emergency' && (optSlug === 'emergency-sos' || optVal === '7'))) {
              categorySelect.selectedIndex = i;
              break;
            } else if (filterVal === 'all') {
              categorySelect.value = 'all';
            }
          }
        }
        renderServices();
      });
    });

    // Category Dropdown Population Helper
    function populateCategoryDropdown(categories) {
      if (!categorySelect || !Array.isArray(categories)) return;
      const currentVal = categorySelect.value;
      categorySelect.innerHTML = '<option value="all">All Categories</option>';
      categories.forEach(cat => {
        const opt = document.createElement('option');
        opt.value = cat.id;
        opt.textContent = cat.name;
        opt.dataset.slug = cat.slug;
        categorySelect.appendChild(opt);
      });
      if (currentVal && currentVal !== 'all') {
        categorySelect.value = currentVal;
      }
    }

    // Dynamic Category Loading with Smart Catalog Cache
    async function loadCategories() {
      // 1. Check local catalog cache first (0 network roundtrips)
      if (window.ServEasyCache) {
        const cached = window.ServEasyCache.getCachedCategories();
        if (cached && Array.isArray(cached) && cached.length > 0) {
          populateCategoryDropdown(cached);
          return;
        }
      }

      try {
        const apiBase = window.SERVEASY_API_BASE || (window.location.pathname.match(/^(.*?)Frontend\//i) ? window.location.pathname.match(/^(.*?)Frontend\//i)[1] + 'backend/api/' : '/backend/api/');
        const apiUrl = window.serveasyApiUrl ? window.serveasyApiUrl('categories.php') : (apiBase + 'categories.php');
        const res = await fetch(apiUrl);
        const data = await res.json();
        if (data.success && data.data && categorySelect) {
          if (window.ServEasyCache) {
            window.ServEasyCache.setCachedCategories(data.data);
          }
          populateCategoryDropdown(data.data);
        }
      } catch (err) {
        console.error("Error loading categories", err);
      }
    }

    // Dynamic Services Retrieval from Database API with Smart Catalog Cache
    async function fetchServices() {
      // 1. Check local catalog cache first (0 network roundtrips)
      if (window.ServEasyCache) {
        const cached = window.ServEasyCache.getCachedServices();
        if (cached && Array.isArray(cached) && cached.length > 0) {
          allServices = cached;
          renderServices();
          return;
        }
      }

      loading = true;
      if (servicesGrid && (!allServices || allServices.length === 0)) {
        if (window.ServEasyCards && typeof window.ServEasyCards.renderSkeletonCards === 'function') {
          window.ServEasyCards.renderSkeletonCards(servicesGrid, 9);
        }
      }
      if (loader && (!allServices || allServices.length === 0)) loader.style.display = 'flex';
      try {
        const apiBase = window.SERVEASY_API_BASE || (window.location.pathname.match(/^(.*?)Frontend\//i) ? window.location.pathname.match(/^(.*?)Frontend\//i)[1] + 'backend/api/' : '/backend/api/');
        const apiUrl = window.serveasyApiUrl ? window.serveasyApiUrl('services.php?limit=100&sort=default') : (apiBase + 'services.php?limit=100&sort=default');
        const res = await fetch(apiUrl);
        const data = await res.json();
        if (data.success && data.data) {
          allServices = data.data;
          if (window.ServEasyCache) {
            window.ServEasyCache.setCachedServices(allServices);
          }
          renderServices();
        } else if (countLabel && (!allServices || allServices.length === 0)) {
          countLabel.textContent = "No services currently available.";
        }
      } catch (err) {
        console.error("Error fetching services", err);
        if (countLabel && (!allServices || allServices.length === 0)) {
          countLabel.textContent = "Error loading services.";
        }
      } finally {
        loading = false;
        if (loader) loader.style.display = 'none';
      }
    }
        if (loader) loader.style.display = 'none';
      }
    }

    // Bind event listeners
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        currentServiceSlug = null;
        currentServiceId = null;
        renderServices();
      });
    }
    if (categorySelect) {
      categorySelect.addEventListener('change', () => {
        currentServiceSlug = null;
        currentServiceId = null;
        syncPills(categorySelect.value);
        renderServices();
      });
    }
    if (priceRange) {
      priceRange.addEventListener('input', () => {
        currentServiceSlug = null;
        currentServiceId = null;
        const val = parseInt(priceRange.value, 10);
        if (priceVal) priceVal.textContent = val >= 500 ? '$500+ (All)' : '$' + val;
        renderServices();
      });
    }
    if (sortSelect) {
      sortSelect.addEventListener('change', () => {
        currentServiceSlug = null;
        currentServiceId = null;
        renderServices();
      });
    }

    // Initialize Booking Modal with session login status
    ServEasyCards.initModal(<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>);

    // Bootstrapping: Load categories and execute initial fetch
    (async function init() {
      const urlParams = new URLSearchParams(window.location.search);
      const urlQuery = urlParams.get('query') || urlParams.get('search') || urlParams.get('q');
      const urlCat = urlParams.get('category') || urlParams.get('cat') || urlParams.get('category_id');
      const urlServiceSlug = urlParams.get('service') || urlParams.get('slug') || urlParams.get('service_slug');
      const urlServiceId = urlParams.get('service_id') || urlParams.get('id') || sessionStorage.getItem('serveasy_auto_open_service_id');
      const urlPrice = urlParams.get('price') || urlParams.get('max_price') || urlParams.get('budget');
      const urlSort = urlParams.get('sort');
      const shouldAutoBook = (urlParams.get('book') === '1') || !!sessionStorage.getItem('serveasy_auto_open_service_id');

      try {
        sessionStorage.removeItem('serveasy_auto_open_service_id');
        sessionStorage.removeItem('serveasy_auto_open_service_name');
      } catch (e) {}

      if (urlQuery && searchInput) {
        searchInput.value = urlQuery;
      }

      function applyCategoryFilter(catVal) {
        if (!catVal || !categorySelect) return false;
        const target = String(catVal).toLowerCase();
        for (let i = 0; i < categorySelect.options.length; i++) {
          const opt = categorySelect.options[i];
          const optVal = String(opt.value);
          const optSlug = (opt.dataset.slug || '').toLowerCase();
          const optText = opt.textContent.toLowerCase();
          if (optSlug === target || optVal === target 
              || (target === 'automotive' && (optSlug === 'vehicle-services' || optVal === '1'))
              || (target === 'vehicle-services' && (optSlug === 'vehicle-services' || optVal === '1'))
              || (target === 'home' && (optSlug === 'home-services' || optVal === '2'))
              || (target === 'home-services' && (optSlug === 'home-services' || optVal === '2'))
              || (target === 'care' && (optSlug === 'personal-staff-&-maids' || optVal === '3'))
              || (target === 'personal-staff-&-maids' && (optSlug === 'personal-staff-&-maids' || optVal === '3'))
              || (target === 'transport' && (optSlug === 'transport-&-rental' || optVal === '4'))
              || (target === 'transport-&-rental' && (optSlug === 'transport-&-rental' || optVal === '4'))
              || (target === 'stay' && (optSlug === 'rooms-&-lodging' || optVal === '5'))
              || (target === 'rooms-&-lodging' && (optSlug === 'rooms-&-lodging' || optVal === '5'))
              || (target === 'food' && (optSlug === 'food-&-dining' || optVal === '6'))
              || (target === 'food-&-dining' && (optSlug === 'food-&-dining' || optVal === '6'))
              || (target === 'emergency' && (optSlug === 'emergency-sos' || optVal === '7'))
              || (target === 'emergency-sos' && (optSlug === 'emergency-sos' || optVal === '7'))
              || optText.includes(target)) {
            categorySelect.selectedIndex = i;
            syncPills(opt.dataset.slug || opt.value);
            return true;
          }
        }
        return false;
      }

      if (urlCat) {
        applyCategoryFilter(urlCat);
      }

      if (urlPrice && priceRange) {
        const p = parseInt(urlPrice, 10);
        if (!isNaN(p) && p > 0) {
          priceRange.value = p;
          if (priceVal) priceVal.textContent = p >= 500 ? '$500+ (All)' : '$' + p;
        }
      }

      if (urlSort && sortSelect) {
        sortSelect.value = urlSort;
      }

      if (urlServiceSlug) {
        currentServiceSlug = urlServiceSlug.toLowerCase();
      } else if (urlServiceId) {
        currentServiceId = urlServiceId;
      }

      // Initial render immediately with all URL filters applied!
      if (allServices && allServices.length > 0) {
        if (window.ServEasyCache) {
          window.ServEasyCache.setCachedServices(allServices);
        }
        renderServices();
      }

      // Refresh categories from API in background if needed
      await loadCategories();
      if (urlCat) {
        applyCategoryFilter(urlCat);
        renderServices();
      }

      // Refresh services from API in background
      await fetchServices();

      // Check if service was requested by slug or id
      if ((urlServiceSlug || urlServiceId) && allServices && allServices.length > 0) {
        let targetService = null;
        if (urlServiceSlug) {
          const targetSlug = urlServiceSlug.toLowerCase();
          targetService = allServices.find(s => (s.slug && s.slug.toLowerCase() === targetSlug) || (s.subcategory_slug && s.subcategory_slug.toLowerCase() === targetSlug));
        }
        if (!targetService && urlServiceId) {
          targetService = allServices.find(s => String(s.id) === String(urlServiceId));
        }

        if (targetService) {
          currentServiceSlug = targetService.slug || urlServiceSlug;

          // If dedicated booking page exists for this service, redirect using slug
          const slug = targetService.slug || '';
          const sName = (targetService.name || '').toLowerCase();
          const subSlug = (targetService.subcategory_slug || '').toLowerCase();
          const isBike = slug === 'bike-wash' || subSlug.includes('bike') || sName.includes('bike');
          const isScooter = subSlug.includes('scooter') || sName.includes('scooter');
          const isSuv = subSlug.includes('suv') || sName.includes('suv');
          const isLuxury = subSlug.includes('luxury') || sName.includes('luxury');
          const vType = isBike ? 'Bike' : isScooter ? 'Scooter' : isSuv ? 'SUV' : isLuxury ? 'Luxury' : 'Car';
          const subcatParam = (isBike || isScooter) ? 'bike-wash' : 'car-wash';

          if (slug === 'vehicle-car-and-bike-wash' || slug === 'vehicle-car-&-bike-wash' || slug === 'bike-wash' || subSlug === 'bike-wash' || subSlug === 'car-wash') {
            window.location.href = `./services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=${subcatParam}&vehicle=${vType}`;
            return;
          } else if (slug === 'personal-staff-and-maids' || slug === 'personal-staff-&-maids') {
            window.location.href = `./services/personal-staff-&-maids.php?service=personal-staff-and-maids`;
            return;
          }

          // Sync category select & pills by category slug or id
          if (categorySelect && (targetService.category_slug || targetService.category_id)) {
            const catVal = targetService.category_id;
            const catSlug = targetService.category_slug;
            for (let i = 0; i < categorySelect.options.length; i++) {
              const opt = categorySelect.options[i];
              if (opt.dataset.slug === catSlug || opt.value == catVal) {
                categorySelect.selectedIndex = i;
                syncPills(opt.dataset.slug || opt.value);
                break;
              }
            }
          }

          renderServices();

          // Auto-trigger booking modal if book=1
          if (shouldAutoBook) {
            handleServiceBookClick(targetService.id, targetService.slug, targetService.name);
          }
        }
      }
    })();
  </script>
</body>

</html>
