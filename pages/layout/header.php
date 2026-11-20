<?php
$authSessionPath = file_exists(__DIR__ . '/../../common/auth_session.php')
    ? __DIR__ . '/../../common/auth_session.php'
    : (file_exists(__DIR__ . '/../common/auth_session.php') ? __DIR__ . '/../common/auth_session.php' : null);
if ($authSessionPath) {
    require_once $authSessionPath;
} elseif (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
}
$systemLogPath = __DIR__ . '/../../../../Backend/logs/systemlog.php';
$errorLogPath = __DIR__ . '/../../../../Backend/logs/errorlog.php';
if (file_exists($systemLogPath)) {
    require_once $systemLogPath;
    if (class_exists('SystemLogger')) SystemLogger::startTimer();
}
if (file_exists($errorLogPath)) {
    require_once $errorLogPath;
    if (class_exists('ErrorLogger')) ErrorLogger::register();
}

// Determine web root and pages root for consistent navigation
$nav_script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (($nav_pos = stripos($nav_script_path, '/frontend-web')) !== false) {
    $nav_web_root = substr($nav_script_path, 0, $nav_pos + strlen('/frontend-web'));
} elseif (($nav_pos = strpos($nav_script_path, '/Frontend/Web')) !== false) {
    $nav_web_root = substr($nav_script_path, 0, $nav_pos + strlen('/Frontend/Web'));
} else {
    $nav_pages_pos = strpos($nav_script_path, '/pages');
    if ($nav_pages_pos !== false) {
        $nav_web_root = substr($nav_script_path, 0, $nav_pages_pos);
    } else {
        $nav_dir = dirname($nav_script_path);
        $nav_web_root = ($nav_dir === '/' || $nav_dir === '\\') ? '' : rtrim($nav_dir, '/\\');
    }
}
$nav_pages_root = $nav_web_root . '/pages';

// if(!isset($_SESSION['user_id'])){
//   header("Location: ../../../../login.php");
//   exit;
// }
if (!defined('SERVEASY_ASSET_VER')) {
    define('SERVEASY_ASSET_VER', '20261119v1');
}
?>
  
  <!-- Solution A: Customer Web Independent Config & API Bridge (Cache-Busted for Hostinger) -->
  <script src="<?= $nav_web_root ?>/config.js?v=<?= SERVEASY_ASSET_VER ?>"></script>
  <script src="<?= $nav_web_root ?>/pages/assets/js/serveasy-api-bridge.js?v=<?= SERVEASY_ASSET_VER ?>"></script>
  <!-- Smart Catalog Cache & Service Worker Registration -->
  <script src="<?= $nav_web_root ?>/pages/assets/js/serveasy-catalog-cache.js?v=<?= SERVEASY_ASSET_VER ?>"></script>
  <script src="<?= $nav_web_root ?>/pages/assets/js/sw-register.js?v=<?= SERVEASY_ASSET_VER ?>"></script>
  <link rel="manifest" href="<?= $nav_web_root ?>/manifest.json">
  <meta name="theme-color" content="#4361ee">
  <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">

  <!-- Schema.org JSON-LD Structured Data (Phase 6 SEO) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "@id": "https://silver-penguin-156353.hostingersite.com/#organization",
        "name": "ServEasy",
        "url": "https://silver-penguin-156353.hostingersite.com/",
        "logo": "https://silver-penguin-156353.hostingersite.com/pages/assets/images/logo.png",
        "description": "On-demand doorstep service marketplace for vehicle care, home cleaning, skilled personal staff, and emergency roadside help.",
        "telephone": "+919647414029",
        "email": "support@serveasy.in",
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "Salt Lake, Sector V",
          "addressLocality": "Kolkata",
          "addressRegion": "West Bengal",
          "postalCode": "700091",
          "addressCountry": "IN"
        }
      },
      {
        "@type": "WebSite",
        "@id": "https://silver-penguin-156353.hostingersite.com/#website",
        "url": "https://silver-penguin-156353.hostingersite.com/",
        "name": "ServEasy",
        "publisher": {
          "@id": "https://silver-penguin-156353.hostingersite.com/#organization"
        },
        "potentialAction": {
          "@type": "SearchAction",
          "target": "https://silver-penguin-156353.hostingersite.com/pages/services.php?query={search_term_string}",
          "query-input": "required name=search_term_string"
        }
      }
    ]
  }
  </script>

  <!-- Instant Theme Bootstrapper (Zero Flash) -->
  <script>
    (function() {
      try {
        var t = localStorage.getItem('serveasy_theme') || 'default';
        document.documentElement.setAttribute('data-theme', t);
        if (document.body) {
          document.body.classList.remove('theme-default', 'theme-black', 'theme-white');
          document.body.classList.add('theme-' + t);
        }
      } catch (e) {}
    })();
  </script>

  <link rel="stylesheet" href="./assets/css/theme.css?v=<?= SERVEASY_ASSET_VER ?>">
  <!-- Razorpay Official Checkout Library -->
  <script src="https://checkout.razorpay.com/v1/checkout.js" async></script>

  <!-- Header & Dashboard Container Width Synchronization -->
  <style>
    .user-header-btn {
      position: relative !important;
    }
    .user-active-dot {
      position: absolute;
      top: 6px;
      right: 6px;
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: #94a3b8;
      border: 2px solid rgba(15, 23, 42, 0.85);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      pointer-events: none;
    }
    .user-active-dot.online {
      background: #10b981;
      box-shadow: 0 0 10px rgba(16, 185, 129, 0.85);
    }

    .header .container,
    .nav-wrapper .container,
    .top-bar .container {
      width: 100% !important;
      max-width: 1300px !important;
      margin-left: auto !important;
      margin-right: auto !important;
      margin-inline: auto !important;
      padding-left: 20px !important;
      padding-right: 20px !important;
      padding-inline: 20px !important;
      box-sizing: border-box !important;
    }

    /* Page-level Glassmorphic Skeleton Loader (Immediate Preloader) */
    @keyframes skeletonShimmer {
      0% { background-position: -200% 0; }
      100% { background-position: 200% 0; }
    }
    .skeleton-pulse {
      background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0.03) 0%,
        rgba(0, 242, 254, 0.12) 35%,
        rgba(168, 85, 247, 0.1) 50%,
        rgba(0, 242, 254, 0.12) 65%,
        rgba(255, 255, 255, 0.03) 100%
      ) !important;
      background-size: 250% 100% !important;
      animation: skeletonShimmer 1.8s infinite linear !important;
      border-radius: 8px;
    }
    body.theme-white .skeleton-pulse {
      background: linear-gradient(
        90deg,
        rgba(0, 0, 0, 0.04) 0%,
        rgba(0, 150, 255, 0.1) 35%,
        rgba(120, 50, 220, 0.08) 50%,
        rgba(0, 150, 255, 0.1) 65%,
        rgba(0, 0, 0, 0.04) 100%
      ) !important;
      background-size: 250% 100% !important;
    }
    #serveasy-page-skeleton {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      z-index: 999999;
      background: radial-gradient(circle at 50% 20%, rgba(14, 18, 36, 0.99) 0%, rgba(7, 9, 18, 1) 100%);
      overflow-y: auto;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 16px 20px 40px;
      box-sizing: border-box;
      opacity: 1;
      visibility: visible;
      transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.4s ease;
      pointer-events: all;
    }
    body.theme-white #serveasy-page-skeleton { background: #f8fafc !important; }
    body.theme-black #serveasy-page-skeleton { background: #000000 !important; }
    #serveasy-page-skeleton.loaded {
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;
    }
    .skeleton-page-wrap {
      width: 100%;
      max-width: 1300px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 24px;
    }
    .skeleton-nav-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      height: 56px;
      padding: 0 10px;
      box-sizing: border-box;
    }
    .skeleton-logo { width: 140px; height: 34px; border-radius: 8px; }
    .skeleton-nav-links { display: flex; gap: 20px; align-items: center; }
    .skeleton-nav-item { width: 70px; height: 16px; border-radius: 4px; }
    .skeleton-nav-icons { display: flex; gap: 12px; align-items: center; }
    .skeleton-circle-icon { width: 38px; height: 38px; border-radius: 50%; }
    .skeleton-hero-card {
      width: 100%;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.06);
      padding: 36px 32px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
      text-align: center;
    }
    .skeleton-badge-pill { width: 130px; height: 24px; border-radius: 20px; }
    .skeleton-hero-headline { width: 55%; height: 36px; border-radius: 10px; max-width: 600px; }
    .skeleton-hero-sub-1 { width: 75%; height: 16px; border-radius: 6px; max-width: 700px; }
    .skeleton-hero-sub-2 { width: 50%; height: 14px; border-radius: 6px; max-width: 500px; }
    .skeleton-search-box { width: 100%; max-width: 720px; height: 52px; border-radius: 30px; margin-top: 8px; }
    .skeleton-pills-row { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; width: 100%; margin-top: 6px; }
    .skeleton-pill-tab { width: 92px; height: 32px; border-radius: 20px; }
    .skeleton-cards-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      width: 100%;
      margin-top: 4px;
    }
    .skeleton-glass-card {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 20px;
      padding: 24px 22px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .skeleton-card-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .skeleton-icon-box { width: 44px; height: 44px; border-radius: 12px; }
    .skeleton-tag { width: 80px; height: 22px; border-radius: 20px; }
    .skeleton-title-bar { width: 65%; height: 22px; border-radius: 6px; }
    .skeleton-line-bar { width: 90%; height: 13px; border-radius: 4px; }
    .skeleton-card-bottom {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 14px;
      padding-top: 14px;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    .skeleton-price-box { width: 70px; height: 22px; border-radius: 6px; }
    .skeleton-btn-box { width: 95px; height: 36px; border-radius: 10px; }
    @media (max-width: 992px) {
      .skeleton-cards-grid { grid-template-columns: repeat(2, 1fr); }
      .skeleton-nav-links { display: none; }
    }
    @media (max-width: 600px) {
      .skeleton-cards-grid { grid-template-columns: 1fr; }
      .skeleton-hero-headline { width: 85%; }
      .skeleton-hero-sub-1 { width: 95%; }
    }
  </style>

  <!-- Full-Page Glassmorphic Skeleton (Displays while whole HTML, CSS, fonts, and JS are loading) -->
  <div id="serveasy-page-skeleton" aria-hidden="true">
    <div class="skeleton-page-wrap">
      
      <div class="skeleton-nav-bar">
        <div class="skeleton-logo skeleton-pulse"></div>
        <div class="skeleton-nav-links">
          <div class="skeleton-nav-item skeleton-pulse"></div>
          <div class="skeleton-nav-item skeleton-pulse"></div>
          <div class="skeleton-nav-item skeleton-pulse"></div>
          <div class="skeleton-nav-item skeleton-pulse"></div>
        </div>
        <div class="skeleton-nav-icons">
          <div class="skeleton-circle-icon skeleton-pulse"></div>
          <div class="skeleton-circle-icon skeleton-pulse"></div>
          <div class="skeleton-circle-icon skeleton-pulse"></div>
        </div>
      </div>

      <div class="skeleton-hero-card">
        <div class="skeleton-badge-pill skeleton-pulse"></div>
        <div class="skeleton-hero-headline skeleton-pulse"></div>
        <div class="skeleton-hero-sub-1 skeleton-pulse"></div>
        <div class="skeleton-hero-sub-2 skeleton-pulse"></div>
        <div class="skeleton-search-box skeleton-pulse"></div>
        <div class="skeleton-pills-row">
          <div class="skeleton-pill-tab skeleton-pulse"></div>
          <div class="skeleton-pill-tab skeleton-pulse"></div>
          <div class="skeleton-pill-tab skeleton-pulse"></div>
          <div class="skeleton-pill-tab skeleton-pulse"></div>
          <div class="skeleton-pill-tab skeleton-pulse"></div>
        </div>
      </div>

      <div class="skeleton-cards-grid">
        <div class="skeleton-glass-card">
          <div class="skeleton-card-top">
            <div class="skeleton-icon-box skeleton-pulse"></div>
            <div class="skeleton-tag skeleton-pulse"></div>
          </div>
          <div class="skeleton-title-bar skeleton-pulse"></div>
          <div class="skeleton-line-bar skeleton-pulse"></div>
          <div class="skeleton-line-bar skeleton-pulse" style="width: 60%;"></div>
          <div class="skeleton-card-bottom">
            <div class="skeleton-price-box skeleton-pulse"></div>
            <div class="skeleton-btn-box skeleton-pulse"></div>
          </div>
        </div>
        <div class="skeleton-glass-card">
          <div class="skeleton-card-top">
            <div class="skeleton-icon-box skeleton-pulse"></div>
            <div class="skeleton-tag skeleton-pulse"></div>
          </div>
          <div class="skeleton-title-bar skeleton-pulse"></div>
          <div class="skeleton-line-bar skeleton-pulse"></div>
          <div class="skeleton-line-bar skeleton-pulse" style="width: 75%;"></div>
          <div class="skeleton-card-bottom">
            <div class="skeleton-price-box skeleton-pulse"></div>
            <div class="skeleton-btn-box skeleton-pulse"></div>
          </div>
        </div>
        <div class="skeleton-glass-card">
          <div class="skeleton-card-top">
            <div class="skeleton-icon-box skeleton-pulse"></div>
            <div class="skeleton-tag skeleton-pulse"></div>
          </div>
          <div class="skeleton-title-bar skeleton-pulse"></div>
          <div class="skeleton-line-bar skeleton-pulse"></div>
          <div class="skeleton-line-bar skeleton-pulse" style="width: 50%;"></div>
          <div class="skeleton-card-bottom">
            <div class="skeleton-price-box skeleton-pulse"></div>
            <div class="skeleton-btn-box skeleton-pulse"></div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Auto-Dismiss Skeleton when HTML, CSS, fonts, and scripts are fully ready -->
  <script>
    (function() {
      function dismissPageSkeleton() {
        var sk = document.getElementById('serveasy-page-skeleton');
        if (sk && !sk.classList.contains('loaded')) {
          sk.classList.add('loaded');
          setTimeout(function() {
            if (sk && sk.parentNode) {
              sk.parentNode.removeChild(sk);
            }
          }, 450);
        }
      }

      if (document.readyState === 'complete') {
        dismissPageSkeleton();
      } else {
        window.addEventListener('load', dismissPageSkeleton);
        // Fail-safe timeout in case any external resource hangs
        setTimeout(dismissPageSkeleton, 2500);
      }
    })();
  </script>

  <header class="header" data-header>

    <div class="top-bar">
      <div class="container">
        <p>Verified Doorstep Services &bull; Transparent Pricing &bull; On-Demand Booking</p>
      </div>
    </div>

    <div class="nav-wrapper">
      <div class="container">

        <h1 class="h1">
          <a href="<?= $nav_web_root ?>/index.php" class="logo">
          <?php
            $logo_path = __DIR__ . '/../assets/images/logo.png';
            if (file_exists($logo_path)) {
                $assetVer = defined('SERVEASY_ASSET_VER') ? SERVEASY_ASSET_VER : '20261119v1';
                echo '<img src="' . $nav_web_root . '/pages/assets/images/logo.png?v=' . $assetVer . '" alt="ServEasy Logo" style="max-height: 40px; width: auto; display: block;">';
            } else {
                echo 'Serv<span class="span">Easy</span>';
            }
            ?>
          </a>
        </h1>

        <button class="nav-open-btn" aria-label="Open Menu" data-nav-open-btn>
          <ion-icon name="menu-outline"></ion-icon>
        </button>

        <nav class="navbar" data-navbar>

          <button class="nav-close-btn" aria-label="Close Menu" data-nav-close-btn>
            <ion-icon name="close-outline"></ion-icon>
          </button>

          <ul class="navbar-list">

            <li>
              <a href="<?= $nav_pages_root ?>/services.php" class="navbar-link">Services</a>
            </li>

            <li>
              <a href="<?= $nav_pages_root ?>/service-category.php" class="navbar-link">Category</a>
            </li>

            <li>
              <a href="<?= $nav_pages_root ?>/wishlist.php" class="navbar-link">Favourites</a>
            </li>

            <li>
              <a href="<?= $nav_pages_root ?>/blog.php" class="navbar-link">Blog</a>
            </li>

            <li>
              <a href="<?= $nav_pages_root ?>/contact.php" class="navbar-link">Contact Us</a>
            </li>

          </ul>

        </nav>

        <div class="header-action">

          <!-- Theme Switcher (placed before search button) -->
          <div class="theme-switcher-wrapper">
            <button type="button" class="header-action-btn theme-toggle-btn" id="theme-toggle-btn" aria-label="Toggle Theme" title="Switch Theme (Cosmic / Black / White)">
              <ion-icon name="color-palette-outline" id="theme-toggle-icon"></ion-icon>
            </button>

            <div class="theme-dropdown-menu" id="theme-dropdown-menu">
              <div class="theme-dropdown-header">Color Themes</div>
              <button type="button" class="theme-opt-btn" data-theme="default">
                <span class="theme-swatch theme-swatch-cosmic"></span>
                <span class="theme-label">Cosmic (Default)</span>
                <ion-icon name="checkmark-outline" class="theme-check"></ion-icon>
              </button>
              <button type="button" class="theme-opt-btn" data-theme="black">
                <span class="theme-swatch theme-swatch-black"></span>
                <span class="theme-label">Pitch Black</span>
                <ion-icon name="checkmark-outline" class="theme-check"></ion-icon>
              </button>
              <button type="button" class="theme-opt-btn" data-theme="white">
                <span class="theme-swatch theme-swatch-white"></span>
                <span class="theme-label">Clean White</span>
                <ion-icon name="checkmark-outline" class="theme-check"></ion-icon>
              </button>
            </div>
          </div>

          <div class="search-wrapper" data-search-wrapper>

            <button class="header-action-btn" aria-label="Toggle search" data-search-btn>
              <ion-icon name="search-outline" class="search-icon"></ion-icon>
              <ion-icon name="close-outline" class="close-icon"></ion-icon>
            </button>

            <div class="input-wrapper">
              <input type="search" name="search" placeholder="Search here" class="search-input">

              <button class="search-submit" aria-label="Submit search">
                <ion-icon name="search-outline"></ion-icon>
              </button>
            </div>

          </div>

          <button class="header-action-btn" aria-label="Open whishlist" data-panel-btn="whishlist">
            <ion-icon name="heart-outline"></ion-icon>

            <data class="btn-badge" id="header-wishlist-badge" value="0">00</data>
          </button>

          <button class="header-action-btn" aria-label="Open shopping cart" data-panel-btn="cart">
            <ion-icon name="basket-outline"></ion-icon>

            <data class="btn-badge" id="header-cart-badge" value="0">00</data>
          </button>

          <button class="header-action-btn user-header-btn" aria-label="Open user profile" data-panel-btn="user">
            <ion-icon name="person-outline"></ion-icon>
            <span class="user-active-dot <?= !empty($_SESSION['user_id']) ? 'online' : '' ?>" id="header-user-dot" title="<?= !empty($_SESSION['user_id']) ? 'Active Session' : 'Guest' ?>"></span>
          </button>

        </div>

      </div>
    </div>

  </header>

  <!-- Theme Switcher Interactive Controller -->
  <script>
    (function initServEasyTheme() {
      function applyTheme(theme) {
        if (!['default', 'black', 'white'].includes(theme)) theme = 'default';
        try { localStorage.setItem('serveasy_theme', theme); } catch (e) {}

        document.documentElement.setAttribute('data-theme', theme);
        if (document.body) {
          document.body.classList.remove('theme-default', 'theme-black', 'theme-white');
          document.body.classList.add('theme-' + theme);
        }

        const icon = document.getElementById('theme-toggle-icon');
        if (icon) {
          if (theme === 'black') icon.setAttribute('name', 'moon-outline');
          else if (theme === 'white') icon.setAttribute('name', 'sunny-outline');
          else icon.setAttribute('name', 'color-palette-outline');
        }

        document.querySelectorAll('.theme-opt-btn').forEach(function(btn) {
          if (btn.getAttribute('data-theme') === theme) {
            btn.classList.add('active');
          } else {
            btn.classList.remove('active');
          }
        });
      }

      window.setServEasyTheme = applyTheme;

      function setup() {
        const toggleBtn = document.getElementById('theme-toggle-btn');
        const dropdown = document.getElementById('theme-dropdown-menu');
        const current = (function() {
          try { return localStorage.getItem('serveasy_theme') || 'default'; } catch(e) { return 'default'; }
        })();
        applyTheme(current);

        if (toggleBtn && dropdown) {
          toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('active');
          });

          document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== toggleBtn) {
              dropdown.classList.remove('active');
            }
          });

          document.querySelectorAll('.theme-opt-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
              e.stopPropagation();
              const selected = this.getAttribute('data-theme');
              applyTheme(selected);
              dropdown.classList.remove('active');
            });
          });
        }

        // Keep wishlist and cart badges in sync across all pages
        try {
          const stored = localStorage.getItem('serveasy_wishlist');
          let list = [];
          try { if (stored) list = JSON.parse(stored); } catch (e) {}
          const count = Array.isArray(list) ? list.length : 0;
          const countStr = count < 10 ? '0' + count : String(count);
          const badges = document.querySelectorAll('.header-action-btn[data-panel-btn="whishlist"] .btn-badge');
          badges.forEach(function(b) { b.textContent = countStr; b.setAttribute('value', count); });
          const drawerCount = document.getElementById('aside-wishlist-count-badge');
          if (drawerCount) drawerCount.textContent = countStr;

          const cartStored = localStorage.getItem('serveasy_cart');
          let cartList = [];
          try { if (cartStored) cartList = JSON.parse(cartStored); } catch (e) {}
          const cartCount = Array.isArray(cartList) ? cartList.length : 0;
          const cartCountStr = cartCount < 10 ? '0' + cartCount : String(cartCount);
          const cartBadges = document.querySelectorAll('.header-action-btn[data-panel-btn="cart"] .btn-badge');
          cartBadges.forEach(function(b) { b.textContent = cartCountStr; b.setAttribute('value', cartCount); });
          const asideCartBadge = document.getElementById('aside-cart-count-badge');
          if (asideCartBadge) asideCartBadge.textContent = cartCountStr;
        } catch (e) {}
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
      } else {
        setup();
      }
    })();
  </script>