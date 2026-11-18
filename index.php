<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$bootstrapPath = file_exists(__DIR__ . '/../backend/api/bootstrap.php')
    ? __DIR__ . '/../backend/api/bootstrap.php'
    : (file_exists(__DIR__ . '/../../backend/api/bootstrap.php') ? __DIR__ . '/../../backend/api/bootstrap.php' : null);
if ($bootstrapPath && file_exists($bootstrapPath)) {
    require_once $bootstrapPath;
}

$initialServices = [];
if (class_exists('App\Repositories\ServiceRepository')) {
    try {
        $serviceRepo = new \App\Repositories\ServiceRepository();
        $initialServices = $serviceRepo->search(['limit' => 100, 'sort' => 'default']);
    } catch (\Throwable $e) {
        // Multi-Domain Cloud API Fallback
        $bridgePath = file_exists(__DIR__ . '/common/ApiBridge.php') ? __DIR__ . '/common/ApiBridge.php' : __DIR__ . '/../common/ApiBridge.php';
        if (file_exists($bridgePath)) {
            require_once $bridgePath;
            $srvRes = \Frontend\ApiBridge::callApi('services.php?limit=100&sort=default');
            if (!empty($srvRes['data']) && is_array($srvRes['data'])) {
                $initialServices = $srvRes['data'];
            }
        }
    }
} else {
    // Multi-Domain Cloud API Fallback
    $bridgePath = file_exists(__DIR__ . '/common/ApiBridge.php') ? __DIR__ . '/common/ApiBridge.php' : __DIR__ . '/../common/ApiBridge.php';
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
  <title>ServEasy - Premium Glassmorphic Service Hub</title>
  <base href="pages/">

  <!-- Solution A: Customer Web Independent Config & API Bridge -->
  <script src="../config.js?v=20261118v2" onerror="this.onerror=null;this.src='./config.js?v=20261118v2';"></script>
  <script src="./assets/js/serveasy-api-bridge.js?v=20261118v2"></script>

  <!-- Favicon -->
  <link rel="shortcut icon" href="./assets/favicon.svg" type="image/svg+xml">

  <!-- Font & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

  <!-- Core Stylesheet imports (for general structure compatibility) -->
  <link rel="stylesheet" href="./assets/css/main.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="./assets/css/global.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="./assets/css/home.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="./assets/css/notify.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="./assets/css/category.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="./assets/css/wishlist.css?v=<?= time(); ?>">


</head>

<body id="top">

  <!-- Floating glowing background circles for 3D depth -->
  <div class="glow-sphere sphere-1"></div>
  <div class="glow-sphere sphere-2"></div>
  <div class="glow-sphere sphere-3"></div>

  <!-- NAVIGATION HEADER -->
  <?php include __DIR__ . '/pages/layout/header.php'; ?>

  <!-- SIDEBAR PANELS (Cart, Wishlist, Profile) -->
  <?php include __DIR__ . '/pages/constants/aside.php'; ?>

  <main>
    <article class="dashboard-container">

      <!-- HERO SECTION -->
      <section class="hero-glass-box">
        <h2 class="hero-title">Discover & Book Premium Local Services</h2>
        <p class="hero-subtitle">
          From mechanical care and private room booking to professional personal assistance, emergency help, and logistics. Book trusted services instantly with transparent booking.
        </p>

        <!-- GLOBAL SEARCH BAR -->
        <div class="search-container">
          <span class="search-icon-glass"><ion-icon name="search-outline"></ion-icon></span>
          <input type="text" id="global-service-search" placeholder="Type a service to search (e.g. AC Repair, Bike Rental, Female Maid, Towing...)" class="search-bar-glass">
        </div>

        <!-- CATEGORIES PILLS FILTERS (Matching Services Page) -->
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
      </section>

      <!-- CATEGORIES SHOWCASE SECTION (SHARED COMPONENT) -->
      <?php 
        $categorySectionTitle = "Explore Service Categories";
        $isHomeContext = true;
        include __DIR__ . '/pages/constants/category-cards.php'; 
      ?>

      <!-- SERVICES CARDS SECTION -->
      <section class="services-list-container">
        <div class="services-header">
          <h3 class="services-section-title">Available Bookings</h3>
          <div style="display: flex; align-items: center; gap: 15px;">
            <span class="results-count" id="services-count">Loading services...</span>
            <a href="./services.php" class="btn-book-glass" style="text-decoration: none; padding: 6px 14px; font-size: 0.85rem;">
              <span>View All</span> <ion-icon name="arrow-forward-outline"></ion-icon>
            </a>
          </div>
        </div>

        <div class="services-grid" id="services-grid">
          <!-- Glassmorphic Skeleton Loading Placeholders (Zero-FOUC & Lazy-Loading) -->
          <?php for ($sk = 0; $sk < 6; $sk++): ?>
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
          <span class="loading-text">Loading services...</span>
        </div>
      </section>

    </article>
  </main>

  <!-- GLASSMORPHIC INTERACTIVE WIZARD MODAL (SHARED COMPONENT) -->
  <?php include __DIR__ . '/pages/constants/booking-modal.php'; ?>

  <!-- FOOTER -->
  <?php include __DIR__ . '/pages/layout/footer.php'; ?>

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

  <!-- Home Page Script using Unified ServEasyCards Engine -->
  <script>
    // Elements
    const pills = document.querySelectorAll('.categories-pills .pill');
    const servicesGrid = document.getElementById('services-grid');
    const servicesCount = document.getElementById('services-count');
    const searchInput = document.getElementById('global-service-search');
    const loader = document.getElementById('services-loader');

    // State (Prefetched from Database & synced with API)
    let allServices = <?php echo json_encode($initialServices ?: []); ?>;
    let activeFilter = 'all';
    let activeSearch = '';

    // Unified Grid Rendering using shared ServEasyCards engine
    function renderGrid() {
      ServEasyCards.renderGrid(allServices, servicesGrid, servicesCount, {
        category: activeFilter,
        query: activeSearch
      });
      ServEasyCards.filterCategories(activeFilter, activeSearch);
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

        renderGrid();
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

          renderGrid();
        }, 120);
      });
    }

    // Dynamic Services Fetching from Database API
    async function loadHomeServices() {
      if (servicesGrid && (!allServices || allServices.length === 0)) {
        ServEasyCards.renderSkeletonCards(servicesGrid, 6);
      }
      if (loader) loader.style.display = 'flex';
      try {
        const res = await (window.serveasyFetch ? window.serveasyFetch('services.php?limit=100&sort=default') : fetch((window.SERVEASY_API_BASE || '/ServEasy/backend/api/') + 'services.php?limit=100&sort=default'));
        const data = await res.json();
        if (data.success && data.data && data.data.length > 0) {
          allServices = data.data;
          renderGrid();
          restoreUrlState();
          checkAutoOpenService();
        } else if (!allServices || allServices.length === 0) {
          if (servicesCount) servicesCount.textContent = "No services currently available.";
        }
      } catch (err) {
        console.error("Error loading home services:", err);
        if (!allServices || allServices.length === 0) {
          if (servicesCount) servicesCount.textContent = "Error loading services.";
        }
      } finally {
        if (loader) loader.style.display = 'none';
      }
    }

    // Restore URL or localStorage filters
    function restoreUrlState() {
      const urlParams = new URLSearchParams(window.location.search);
      const urlSearch = urlParams.get('search');
      const urlFilter = urlParams.get('filter') || urlParams.get('category');

      if (urlSearch) {
        if (searchInput) searchInput.value = urlSearch;
        activeSearch = urlSearch;
      }
      if (urlFilter) {
        activeFilter = urlFilter;
        pills.forEach(p => {
          if (p.getAttribute('data-filter') === urlFilter || p.getAttribute('data-slug') === urlFilter) {
            p.classList.add('active');
          } else {
            p.classList.remove('active');
          }
        });
      }
      renderGrid();
    }

    // Auto-trigger service booking after login redirect or deep link
    function checkAutoOpenService() {
      const urlParams = new URLSearchParams(window.location.search);
      const shouldBook = urlParams.get('book') === '1';
      const autoServiceId = urlParams.get('service_id') || sessionStorage.getItem('serveasy_auto_open_service_id');
      const autoServiceName = urlParams.get('service_name') || sessionStorage.getItem('serveasy_auto_open_service_name') || '';
      const autoServiceSlug = urlParams.get('service') || urlParams.get('slug');

      try {
        sessionStorage.removeItem('serveasy_auto_open_service_id');
        sessionStorage.removeItem('serveasy_auto_open_service_name');
      } catch (e) {}

      if (shouldBook || autoServiceId || autoServiceSlug) {
        if (allServices && allServices.length > 0) {
          let target = null;
          if (autoServiceId) {
            target = allServices.find(s => String(s.id) === String(autoServiceId));
          }
          if (!target && autoServiceSlug) {
            const slg = autoServiceSlug.toLowerCase();
            target = allServices.find(s => (s.slug && s.slug.toLowerCase() === slg) || (s.subcategory_slug && s.subcategory_slug.toLowerCase() === slg));
          }

          if (target) {
            handleServiceBookClick(target.id, target.slug, target.name);
          } else if (autoServiceId && typeof openBookingModalDynamic === 'function') {
            openBookingModalDynamic(autoServiceId, autoServiceName);
          }
        }
      }
    }

    // Initialize Booking Modal with session login status
    ServEasyCards.initModal(<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>);

    // Initial Render
    if (allServices && allServices.length > 0) {
      renderGrid();
      restoreUrlState();
      checkAutoOpenService();
    } else {
      loadHomeServices();
    }
  </script>

</body>

</html>
