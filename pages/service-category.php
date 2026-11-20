<?php
require_once __DIR__ . '/../common/auth_session.php';
if (!defined('SERVEASY_ASSET_VER')) {
    define('SERVEASY_ASSET_VER', '20261119v1');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Service Categories — ServEasy Glassmorphic Service Hub</title>
  <meta name="description" content="Explore verified doorstep service categories on ServEasy. Transport, Automotive repair, Home sanitization, Personal staff, Rooms & lodging, and 24/7 Emergency SOS.">

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

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

  <!-- Core Stylesheets -->
  <link rel="stylesheet" href="./assets/css/main.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/global.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/home.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/notify.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/category.css?v=<?= SERVEASY_ASSET_VER ?>">
</head>

<body id="top">

  <!-- Floating Glowing Spheres for Depth -->
  <div class="glow-sphere sphere-1"></div>
  <div class="glow-sphere sphere-2"></div>
  <div class="glow-sphere sphere-3"></div>

  <!-- NAVIGATION HEADER -->
  <?php include __DIR__ . '/layout/header.php'; ?>

  <!-- SIDEBAR PANELS (Cart, Wishlist, Profile) -->
  <?php include __DIR__ . '/constants/aside.php'; ?>

  <main>
    <article class="dashboard-container">

      <?php $isSubView = isset($_GET['view']) && $_GET['view'] === 'subcategories'; ?>
      <!-- BREADCRUMBS -->
      <div class="cat-breadcrumb-wrap">
        <ol class="cat-breadcrumb-list">
          <li class="cat-breadcrumb-item">
            <a href="./index.php" class="cat-breadcrumb-link">
              <ion-icon name="home-outline"></ion-icon>
              <span>Home</span>
            </a>
          </li>
          <li class="cat-breadcrumb-separator">/</li>
          <li class="cat-breadcrumb-item">
            <span class="cat-breadcrumb-current" id="cat-breadcrumb-current"><?= $isSubView ? 'All Subcategories' : 'All Categories' ?></span>
          </li>
        </ol>
      </div>

      <!-- HERO BANNER -->
      <section class="cat-hero-box">
        <div class="cat-badge-pill">
          <span class="pulse-cat-dot"></span>
          <span id="cat-hero-badge-text"><?= $isSubView ? 'All Subcategories & Specializations' : 'Explore Service Domains' ?></span>
        </div>

        <h1 class="cat-hero-title" id="cat-hero-main-title"><?= $isSubView ? 'Browse All Verified Subcategories' : 'Browse Verified Service Categories' ?></h1>
        <p class="cat-hero-subtitle" id="cat-hero-subtitle-text">
          <?= $isSubView 
            ? 'Explore all 40 specialized service offerings across automotive, cleaning, domestic assistance, lodging, dining, and emergency SOS.'
            : 'From vehicle mechanical repair and high-pressure washing to certified home sanitization, personal domestic helpers, room lodging, and 24/7 roadside rescue.' ?>
        </p>

        <!-- Live Search Input -->
        <div class="cat-search-container">
          <ion-icon name="search-outline" class="cat-search-icon"></ion-icon>
          <input type="text" id="category-search" placeholder="Search categories or services (e.g. Car Wash, Maid, AC Repair, Towing...)" class="cat-search-bar">
        </div>

        <!-- Quick Filter Pills -->
        <div class="cat-filter-pills" id="category-pills">
          <div class="cat-pill active" data-filter="all">All Domains</div>
          <div class="cat-pill" data-filter="automotive">Automotive</div>
          <div class="cat-pill" data-filter="home">Home & Living</div>
          <div class="cat-pill" data-filter="care">Care & Staff</div>
          <div class="cat-pill" data-filter="transport">Transport & Rental</div>
          <div class="cat-pill" data-filter="stay">Rooms & Lodging</div>
          <div class="cat-pill" data-filter="food">Food & Dining</div>
          <div class="cat-pill" data-filter="emergency">Emergency SOS</div>
        </div>
      </section>

      <!-- CATEGORIES SHOWCASE GRID (SHARED COMPONENT) -->
      <?php include __DIR__ . '/constants/category-cards.php'; ?>

      <!-- TRUST & SERVICE GUARANTEES STRIP -->
      <section class="cat-trust-strip">
        <div class="trust-item">
          <div class="trust-icon-box">
            <ion-icon name="shield-checkmark-outline"></ion-icon>
          </div>
          <div class="trust-info">
            <h4>100% Background Checked</h4>
            <p>Every specialist is police-verified and certified.</p>
          </div>
        </div>

        <div class="trust-item">
          <div class="trust-icon-box">
            <ion-icon name="flash-outline"></ion-icon>
          </div>
          <div class="trust-info">
            <h4>Rapid Doorstep Arrival</h4>
            <p>Average technician dispatch within 45 minutes.</p>
          </div>
        </div>

        <div class="trust-item">
          <div class="trust-icon-box">
            <ion-icon name="card-outline"></ion-icon>
          </div>
          <div class="trust-info">
            <h4>Transparent Pricing</h4>
            <p>No hidden charges, secure cashless escrow.</p>
          </div>
        </div>

        <div class="trust-item">
          <div class="trust-icon-box">
            <ion-icon name="thumbs-up-outline"></ion-icon>
          </div>
          <div class="trust-info">
            <h4>Quality Guarantee</h4>
            <p>Free re-service if you are not 100% satisfied.</p>
          </div>
        </div>
      </section>

    </article>
  </main>

  <!-- FOOTER -->
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- BACK TO TOP -->
  <a href="#top" class="back-to-top" aria-label="Back to Top" data-back-top-btn>
    <ion-icon name="arrow-up-outline"></ion-icon>
  </a>

  <!-- Custom Scripts -->
  <script src="./assets/js/notify.js"></script>
  <script src="./assets/js/script.js"></script>
  <script src="./assets/js/services-cards.js"></script>

  <!-- Ionicons Modules -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <!-- Dynamic Category Filter using unified ServEasyCards engine -->
  <script>
    ServEasyCards.initCategoryControls('category-search', '.cat-pill');
  </script>

</body>

</html>
