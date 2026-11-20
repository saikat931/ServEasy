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
if (class_exists('App\Repositories\ServiceRepository')) {
    try {
        $serviceRepo = new \App\Repositories\ServiceRepository();
        $initialServices = $serviceRepo->search(['limit' => 100, 'sort' => 'default']);
    } catch (\Exception $e) {
        error_log("Failed to prefetch services on wishlist page: " . $e->getMessage());
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
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ServEasy - My Saved Wishlist</title>

  <!-- Favicon -->
    <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
  <link rel="alternate icon" type="image/x-icon" href="./assets/favicon.ico">
  <link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon-32x32.png">
  <link rel="apple-touch-icon" sizes="180x180" href="./assets/apple-touch-icon.png">
  <link rel="manifest" href="../manifest.json">
  <meta name="theme-color" content="#4361ee">

  <!-- Smart Catalog Cache & Service Worker Registration -->
  <script src="./assets/js/serveasy-catalog-cache.js?v=<?= SERVEASY_ASSET_VER ?>"></script>
  <script src="./assets/js/sw-register.js?v=<?= SERVEASY_ASSET_VER ?>"></script>

  <!-- Font & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

  <!-- Core Stylesheet imports (Matching index.php ingredients) -->
  <link rel="stylesheet" href="./assets/css/main.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/global.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/home.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/notify.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/category.css?v=<?= SERVEASY_ASSET_VER ?>">
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

      <!-- HERO SECTION -->
      <section class="hero-glass-box">
        <div class="wishlist-hero-badge">
          <ion-icon name="heart"></ion-icon>
          <span>Personal Service Wishlist</span>
        </div>
        <h2 class="hero-title">Your Saved Favorites & Wishlist</h2>
        <p class="hero-subtitle">
          Keep track of your favorite local services, compare verified pricing, and book instantly whenever you need professional assistance.
        </p>

        <!-- GLOBAL SEARCH BAR -->
        <div class="search-container">
          <span class="search-icon-glass"><ion-icon name="search-outline"></ion-icon></span>
          <input type="text" id="wishlist-service-search" placeholder="Search your saved services (e.g. Car Wash, House Maid, AC Service...)" class="search-bar-glass">
        </div>

        <!-- CATEGORIES PILLS FILTERS (Matching Index Page) -->
        <div class="categories-pills" id="wishlist-pills">
          <div class="pill active" data-filter="all">All Saved</div>
          <div class="pill" data-filter="1" data-slug="automotive">Automotive</div>
          <div class="pill" data-filter="2" data-slug="home">Home & Living</div>
          <div class="pill" data-filter="3" data-slug="care">Personal Staff</div>
          <div class="pill" data-filter="4" data-slug="transport">Transport & Rental</div>
          <div class="pill" data-filter="5" data-slug="stay">Rooms & Lodging</div>
          <div class="pill" data-filter="6" data-slug="food">Food & Dining</div>
          <div class="pill" data-filter="7" data-slug="emergency">Emergency SOS</div>
        </div>
      </section>

      <!-- WISHLIST STATS & ACTION STRIP -->
      <section class="wishlist-stats-strip" id="wishlist-stats-bar">
        <div class="wishlist-stats-group">
          <!-- Stat 1: Total Saved Items -->
          <div class="wishlist-stat-item">
            <div class="wishlist-stat-icon pink">
              <ion-icon name="heart"></ion-icon>
            </div>
            <div class="wishlist-stat-data">
              <span class="wishlist-stat-val" id="stat-total-count">0</span>
              <span class="wishlist-stat-label">Services Saved</span>
            </div>
          </div>

          <!-- Stat 2: Active Categories -->
          <div class="wishlist-stat-item">
            <div class="wishlist-stat-icon cyan">
              <ion-icon name="grid-outline"></ion-icon>
            </div>
            <div class="wishlist-stat-data">
              <span class="wishlist-stat-val" id="stat-total-categories">0</span>
              <span class="wishlist-stat-label">Domains Covered</span>
            </div>
          </div>

          <!-- Stat 3: Est. Starting Budget -->
          <div class="wishlist-stat-item">
            <div class="wishlist-stat-icon gold">
              <ion-icon name="wallet-outline"></ion-icon>
            </div>
            <div class="wishlist-stat-data">
              <span class="wishlist-stat-val" id="stat-min-budget">$0.00</span>
              <span class="wishlist-stat-label">Min. Starting Cost</span>
            </div>
          </div>
        </div>

        <div class="wishlist-actions-group">
          <button type="button" class="btn-wishlist-action btn-wishlist-clear" onclick="ServEasyWishlist.clear()" title="Clear all saved services">
            <ion-icon name="trash-outline"></ion-icon>
            <span>Clear Wishlist</span>
          </button>
          <a href="./services.php" class="btn-wishlist-action btn-wishlist-browse">
            <span>Browse More</span>
            <ion-icon name="arrow-forward-outline"></ion-icon>
          </a>
        </div>
      </section>

      <!-- SERVICES CARDS SECTION -->
      <section class="services-list-container">
        <div class="services-header">
          <h3 class="services-section-title">Saved Services</h3>
          <div style="display: flex; align-items: center; gap: 15px;">
            <span class="results-count" id="services-count">Loading wishlist...</span>
          </div>
        </div>

        <!-- Empty Wishlist Glassmorphic State -->
        <div class="wishlist-empty-box" id="wishlist-empty-box" style="display: none;">
          <div class="wishlist-empty-icon-wrap">
            <ion-icon name="heart-outline"></ion-icon>
          </div>
          <h3 class="wishlist-empty-title">Your Wishlist is Empty</h3>
          <p class="wishlist-empty-desc">
            You haven't saved any services to your favorites yet. Explore our verified domains and click the heart icon on any service card to pin it here!
          </p>
          <div class="wishlist-empty-cta-group">
            <a href="./services.php" class="btn-book-glass" style="text-decoration: none; padding: 12px 28px;">
              <span>Explore All Services</span> <ion-icon name="arrow-forward-outline"></ion-icon>
            </a>
            <a href="./service-category.php" class="btn-book-glass" style="text-decoration: none; padding: 12px 24px; background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.2);">
              <span>Browse Categories</span> <ion-icon name="grid-outline"></ion-icon>
            </a>
          </div>
        </div>

        <!-- Saved Services Grid -->
        <div class="services-grid" id="services-grid">
          <!-- Glassmorphic Skeleton Loading Placeholders (Zero-FOUC & Lazy-Loading) -->
          <?php for ($sk = 0; $sk < 3; $sk++): ?>
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
          <span class="loading-text">Updating your saved services...</span>
        </div>
      </section>

      <!-- RECOMMENDED SERVICES / POPULAR PICKS SECTION -->
      <section class="wishlist-recommended-section" id="wishlist-recommended-section">
        <div class="wishlist-rec-header">
          <h4 class="wishlist-rec-title">
            <ion-icon name="sparkles"></ion-icon>
            <span>Recommended Services for You</span>
          </h4>
          <a href="./services.php" class="btn-book-glass" style="text-decoration: none; padding: 6px 14px; font-size: 0.82rem;">
            <span>See All</span> <ion-icon name="arrow-forward-outline"></ion-icon>
          </a>
        </div>

        <div class="services-grid" id="recommended-grid">
          <!-- Populated with popular services not yet in wishlist -->
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

  <!-- JS imports (Matching index.php ingredients) -->
  <script src="./assets/js/script.js"></script>
  <script src="./assets/js/notify.js"></script>
  <script src="./assets/js/wishlist.js"></script>
  <script src="./assets/js/services-cards.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <!-- Wishlist Page Script using Unified ServEasyCards Engine -->
  <script>
    // Elements
    const pills = document.querySelectorAll('#wishlist-pills .pill');
    const servicesGrid = document.getElementById('services-grid');
    const recommendedGrid = document.getElementById('recommended-grid');
    const servicesCount = document.getElementById('services-count');
    const searchInput = document.getElementById('wishlist-service-search');
    const loader = document.getElementById('services-loader');
    const emptyBox = document.getElementById('wishlist-empty-box');
    const statsBar = document.getElementById('wishlist-stats-bar');

    // Stat Elements
    const statTotalCount = document.getElementById('stat-total-count');
    const statTotalCategories = document.getElementById('stat-total-categories');
    const statMinBudget = document.getElementById('stat-min-budget');

    // State
    let allServices = <?php echo json_encode($initialServices ?: []); ?>;
    let activeFilter = 'all';
    let activeSearch = '';

    // Reset filters helper
    window.resetWishlistFilters = function() {
      activeFilter = 'all';
      activeSearch = '';
      if (searchInput) searchInput.value = '';
      pills.forEach(p => {
        if (p.getAttribute('data-filter') === 'all') p.classList.add('active');
        else p.classList.remove('active');
      });
      let url = new URL(window.location.href);
      url.searchParams.delete('filter');
      url.searchParams.delete('search');
      history.replaceState(null, '', url.toString());
      renderWishlist();
    };

    // Render Wishlist Grid
    function renderWishlist() {
      const wishlistIds = ServEasyWishlist.getAll();
      if (window.ServEasyWishlist && typeof window.ServEasyWishlist.cacheServices === 'function' && Array.isArray(allServices) && allServices.length > 0) {
        window.ServEasyWishlist.cacheServices(allServices);
      }
      const cachedItems = (window.ServEasyWishlist && typeof window.ServEasyWishlist.getItemsData === 'function') 
        ? window.ServEasyWishlist.getItemsData() 
        : {};

      const savedServices = wishlistIds.map(id => {
        let found = allServices.find(s => Number(s.id) === id);
        if (!found && cachedItems[id]) {
          found = cachedItems[id];
        }
        return found;
      }).filter(Boolean);

      // Update Quick Stats Strip
      updateStats(savedServices);

      // Check if empty
      if (savedServices.length === 0) {
        if (servicesGrid) servicesGrid.innerHTML = '';
        if (emptyBox) emptyBox.style.display = 'block';
        if (servicesCount) servicesCount.textContent = '0 items in wishlist';
      } else {
        if (emptyBox) emptyBox.style.display = 'none';

        // Filter by category pill and search query
        const filtered = savedServices.filter(s => {
          // Category filter
          if (activeFilter !== 'all') {
            const matchesCat = (String(s.category_id) === String(activeFilter)) || 
                               (s.category_slug && s.category_slug === activeFilter) ||
                               (activeFilter === '1' && (s.category_slug === 'vehicle-services' || (s.category_name && s.category_name.toLowerCase().includes('auto')) || (s.category_name && s.category_name.toLowerCase().includes('vehicle')))) ||
                               (activeFilter === '2' && (s.category_slug === 'home-services' || (s.category_name && s.category_name.toLowerCase().includes('home')) || (s.category_name && s.category_name.toLowerCase().includes('living')))) ||
                               (activeFilter === '3' && (s.category_slug === 'personal-staff-&-maids' || (s.category_name && s.category_name.toLowerCase().includes('personal')) || (s.category_name && s.category_name.toLowerCase().includes('maid')))) ||
                               (activeFilter === '4' && (s.category_slug === 'transport-&-rental' || (s.category_name && s.category_name.toLowerCase().includes('transport')))) ||
                               (activeFilter === '5' && (s.category_slug === 'rooms-&-lodging' || (s.category_name && s.category_name.toLowerCase().includes('room')) || (s.category_name && s.category_name.toLowerCase().includes('stay')))) ||
                               (activeFilter === '6' && (s.category_slug === 'food-&-dining' || (s.category_name && s.category_name.toLowerCase().includes('food')))) ||
                               (activeFilter === '7' && (s.category_slug === 'emergency-sos' || (s.category_name && s.category_name.toLowerCase().includes('emergency'))));
            if (!matchesCat) return false;
          }

          // Search query filter
          if (activeSearch.trim()) {
            const q = activeSearch.toLowerCase().trim();
            const haystack = `${s.name || ''} ${s.description || ''} ${s.category_name || ''} ${s.subcategory_name || ''}`.toLowerCase();
            if (!haystack.includes(q)) return false;
          }

          return true;
        });

        if (filtered.length === 0) {
          if (servicesGrid) {
            servicesGrid.innerHTML = `
              <div style="grid-column: 1 / -1; text-align: center; padding: 50px 20px; color: #94a3b8; background: rgba(255, 255, 255, 0.02); border: 1px dashed rgba(255, 255, 255, 0.1); border-radius: 16px;">
                <ion-icon name="filter-outline" style="font-size: 2.2rem; color: #38bdf8; margin-bottom: 10px;"></ion-icon>
                <h4 style="color: #f1f5f9; font-size: 1.1rem; margin-bottom: 6px;">No saved services in this view</h4>
                <p style="font-size: 0.88rem; color: #94a3b8; margin-bottom: 14px;">None of your saved services match the active filter or search criteria.</p>
                <button type="button" onclick="resetWishlistFilters()" class="btn-book-glass" style="display: inline-flex; padding: 8px 18px; font-size: 0.85rem;">
                  <span>Show All Saved Services</span>
                </button>
              </div>
            `;
          }
          if (servicesCount) {
            servicesCount.textContent = `Showing 0 of ${savedServices.length} saved services`;
          }
        } else {
          // Use ServEasyCards to render the grid
          ServEasyCards.renderGrid(filtered, servicesGrid, servicesCount, {
            category: 'all',
            query: ''
          });

          if (servicesCount) {
            servicesCount.textContent = `Showing ${filtered.length} of ${savedServices.length} saved service${savedServices.length === 1 ? '' : 's'}`;
          }
        }
      }

      // Render Recommendations (top 3 services not currently in wishlist)
      renderRecommendations(wishlistIds);

      // Sync aside drawer as well
      ServEasyWishlist.renderAsideDrawer(allServices);
    }

    // Update Quick Stats Metric Strip
    function updateStats(savedList) {
      const count = savedList.length;
      if (statTotalCount) statTotalCount.textContent = count;

      // Unique Categories
      const uniqueCats = new Set(savedList.map(s => s.category_id).filter(Boolean));
      if (statTotalCategories) statTotalCategories.textContent = uniqueCats.size;

      // Min Budget
      if (count === 0) {
        if (statMinBudget) statMinBudget.textContent = '$0.00';
      } else {
        const prices = savedList.map(s => parseFloat(s.base_price) || 0).filter(p => p > 0);
        const minP = prices.length ? Math.min(...prices) : 0;
        if (statMinBudget) statMinBudget.textContent = `$${minP.toFixed(2)}`;
      }
    }

    // Render Recommendations
    function renderRecommendations(wishlistIds) {
      if (!recommendedGrid) return;
      const unSaved = allServices.filter(s => !wishlistIds.includes(Number(s.id)));
      const recs = unSaved.slice(0, 3);

      recommendedGrid.innerHTML = '';
      if (recs.length === 0) {
        document.getElementById('wishlist-recommended-section').style.display = 'none';
        return;
      }

      document.getElementById('wishlist-recommended-section').style.display = 'block';
      recs.forEach(s => {
        const card = ServEasyCards.renderCard(s);
        recommendedGrid.appendChild(card);
      });
    }

    // Category Pills Filter Action
    pills.forEach(pill => {
      pill.addEventListener('click', () => {
        pills.forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        activeFilter = pill.getAttribute('data-filter') || pill.getAttribute('data-slug') || 'all';

        let url = new URL(window.location.href);
        url.searchParams.set('filter', activeFilter);
        history.replaceState(null, '', url.toString());

        renderWishlist();
      });
    });

    // Real-time Search Input Action
    if (searchInput) {
      let searchTimeout;
      searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          activeSearch = searchInput.value;

          let url = new URL(window.location.href);
          if (activeSearch.trim()) {
            url.searchParams.set('search', activeSearch.trim());
          } else {
            url.searchParams.delete('search');
          }
          history.replaceState(null, '', url.toString());

          renderWishlist();
        }, 120);
      });
    }

    // Dynamic Services Retrieval from Database API
    async function loadWishlistServices() {
      if (loader) loader.style.display = 'flex';
      try {
        const apiBase = window.SERVEASY_API_BASE || (window.location.pathname.match(/^(.*?)Frontend\//i) ? window.location.pathname.match(/^(.*?)Frontend\//i)[1] + 'backend/api/' : '/backend/api/');
        const res = await fetch(apiBase + 'services.php?limit=100&sort=default');
        const data = await res.json();
        if (data.success && data.data && data.data.length > 0) {
          allServices = data.data;
          if (window.ServEasyWishlist && typeof window.ServEasyWishlist.cacheServices === 'function') {
            window.ServEasyWishlist.cacheServices(allServices);
          }
          renderWishlist();
        }
      } catch (err) {
        console.error("Error loading services for wishlist:", err);
      } finally {
        if (loader) loader.style.display = 'none';
      }
    }

    // Listen to global wishlist changes (add/remove from any card or aside)
    window.addEventListener('wishlist:updated', function() {
      renderWishlist();
    });

    // Initialize Booking Modal with session login status
    ServEasyCards.initModal(<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>);

    // Restore filter & search state from URL
    (function restoreState() {
      const urlParams = new URLSearchParams(window.location.search);
      const urlFilter = urlParams.get('filter') || 'all';
      const urlSearch = urlParams.get('search') || '';

      if (urlFilter && urlFilter !== 'all') {
        activeFilter = urlFilter;
        pills.forEach(p => {
          const pf = p.getAttribute('data-filter') || p.getAttribute('data-slug') || '';
          if (pf.toLowerCase() === urlFilter.toLowerCase()) {
            p.classList.add('active');
          } else {
            p.classList.remove('active');
          }
        });
      }
      if (urlSearch && searchInput) {
        searchInput.value = urlSearch;
        activeSearch = urlSearch;
      }
    })();

    // Initial load
    if (allServices && allServices.length > 0) {
      renderWishlist();
    } else {
      loadWishlistServices();
    }
  </script>

</body>

</html>
