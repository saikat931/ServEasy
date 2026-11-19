<?php
require_once __DIR__ . '/../../common/auth_session.php';

// Determine web root to generate robust, base-tag-immune URLs
$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (($pos = stripos($script_path, '/frontend-web')) !== false) {
    $web_root = substr($script_path, 0, $pos + strlen('/frontend-web'));
} elseif (($pos = strpos($script_path, '/Frontend/Web')) !== false) {
    $web_root = substr($script_path, 0, $pos + strlen('/Frontend/Web'));
} else {
    $pages_pos = strpos($script_path, '/pages');
    if ($pages_pos !== false) {
        $web_root = substr($script_path, 0, $pages_pos);
    } else {
        $dir = dirname($script_path);
        $web_root = ($dir === '/' || $dir === '\\') ? '' : rtrim($dir, '/\\');
    }
}

$link_profile   = $web_root . '/pages/user-profile.php';
$link_orders    = $web_root . '/pages/orders.php';
$link_addresses = $web_root . '/pages/user-profile.php#addresses';
$link_wishlist  = $web_root . '/pages/wishlist.php';
$link_cart      = $web_root . '/pages/cart.php';
$link_services  = $web_root . '/pages/services.php';
$link_contact   = $web_root . '/pages/contact.php';
$link_auth      = $web_root . '/auth/auth.php';
$link_logout    = $web_root . '/logout.php';
$p_assets       = $web_root . '/pages/assets/';

// User Identity Extraction
$display_identity = "Login or Register";
$display_name     = "Guest User";
$display_sub      = "Access your bookings & profile";
$user_initials    = "GU";
$is_logged_in     = false;
$has_name         = false;

if (isset($_SESSION['user_id'])) {
    $is_logged_in = true;
    $user_id = (int)$_SESSION['user_id'];
    $full_name = trim((string)($_SESSION['full_name'] ?? ''));
    $email     = trim((string)($_SESSION['email'] ?? ''));
    $phone     = trim((string)($_SESSION['phone'] ?? ''));

    // Optional refresh from DB if available locally (never throws on standalone Hostinger domain)
    if (empty($full_name) && empty($email) && empty($phone) && file_exists(__DIR__ . '/../../common/db_connection.php')) {
        try {
            require_once __DIR__ . '/../../common/db_connection.php';
            $asidePdo = class_exists('ServEasyWebDB') ? ServEasyWebDB::connect() : null;
            if ($asidePdo) {
                $st = $asidePdo->prepare("SELECT full_name, email_address, phone_number FROM site_user WHERE id = ? LIMIT 1");
                $st->execute([$user_id]);
                if ($aside_user = $st->fetch(PDO::FETCH_ASSOC)) {
                    $full_name = trim((string)($aside_user['full_name'] ?? ''));
                    $email     = trim((string)($aside_user['email_address'] ?? ''));
                    $phone     = trim((string)($aside_user['phone_number'] ?? ''));
                }
            }
        } catch (\Throwable $t) {}
    }

    if (!empty($full_name)) {
        $display_name = $full_name;
        $has_name = true;
        $words = preg_split("/\s+/", $full_name);
        if (count($words) >= 2) {
            $user_initials = strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        } else {
            $user_initials = strtoupper(mb_substr($full_name, 0, 2));
        }
        $display_identity = !empty($phone) ? $phone : (!empty($email) ? $email : "Verified Customer");
        $display_sub      = (!empty($email) && !empty($phone)) ? $email : "Verified Customer";
    } else {
        if (!empty($email)) {
            $display_name     = "My Account";
            $display_identity = $email;
            $user_initials    = strtoupper(substr($email, 0, 2));
            $display_sub      = "Verified Customer";
        } elseif (!empty($phone)) {
            $display_name     = "My Account";
            $display_identity = $phone;
            $user_initials    = strtoupper(substr($phone, -2));
            $display_sub      = "Verified Customer";
        } else {
            $display_name     = "ServEasy Member";
            $display_identity = "Customer";
            $user_initials    = "SE";
            $display_sub      = "Verified Customer";
        }
    }
}
?>

<aside class="aside">

  <style>
    .side-panel-header .panel-header-heading {
      font-size: 1.6rem !important;
      font-weight: 700 !important;
      color: #ffffff !important;
      margin: 0 !important;
      line-height: 1.2 !important;
    }
    .side-panel-header .panel-header-sub {
      font-size: 1.15rem !important;
      color: #94a3b8 !important;
      margin: 2px 0 0 !important;
      line-height: 1.3 !important;
    }
    .side-panel-header .panel-header-icon-box {
      width: 40px !important;
      height: 40px !important;
      border-radius: 11px !important;
      font-size: 2.1rem !important;
    }
    .side-panel-header .aside-count-pill {
      font-family: var(--font-primary, 'Poppins', sans-serif) !important;
      font-size: 0.75rem !important;
      font-weight: 700 !important;
      padding: 2px 8px !important;
      border-radius: 20px !important;
      line-height: 1 !important;
      display: inline-flex !important;
      align-items: center !important;
    }
    .side-panel-header .aside-count-pill.pink {
      background: rgba(255, 0, 127, 0.16) !important;
      border: 1px solid rgba(255, 0, 127, 0.38) !important;
      color: #ff6090 !important;
    }
    .side-panel-header .aside-count-pill.purple {
      background: rgba(168, 85, 247, 0.16) !important;
      border: 1px solid rgba(168, 85, 247, 0.38) !important;
      color: #c084fc !important;
    }

    /* Wishlist Sidebar List Typography & Element Proportions (11px title, adjusted price) */
    .aside-wishlist-card {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      gap: 8px !important;
      padding: 8px 10px !important;
      background: rgba(255, 255, 255, 0.03) !important;
      border: 1px solid rgba(255, 255, 255, 0.07) !important;
      border-radius: 10px !important;
      width: 100% !important;
      box-sizing: border-box !important;
      overflow: hidden !important;
      transition: all 0.22s ease !important;
    }
    .aside-wishlist-title {
      font-family: var(--font-primary, 'Poppins', sans-serif) !important;
      font-size: 11px !important;
      font-weight: 600 !important;
      color: #ffffff !important;
      white-space: nowrap !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
      margin: 0 !important;
      line-height: 1.3 !important;
    }
    .aside-wishlist-bottom-row {
      display: flex !important;
      align-items: center !important;
      gap: 5px !important;
      overflow: hidden !important;
    }
    .aside-wishlist-price {
      font-family: var(--font-primary, 'Poppins', sans-serif) !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      color: var(--neon-cyan, #00f2fe) !important;
      white-space: nowrap !important;
      line-height: 1 !important;
    }
    .aside-wishlist-sep {
      color: rgba(255, 255, 255, 0.25) !important;
      font-size: 8px !important;
      line-height: 1 !important;
    }
    .aside-wishlist-cat {
      font-family: var(--font-primary, 'Poppins', sans-serif) !important;
      font-size: 9.5px !important;
      font-weight: 500 !important;
      color: #94a3b8 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.3px !important;
      white-space: nowrap !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
      max-width: 90px !important;
      line-height: 1 !important;
    }
    .btn-aside-book {
      padding: 4px 8px !important;
      border-radius: 5px !important;
      background: rgba(0, 242, 254, 0.12) !important;
      border: 1px solid rgba(0, 242, 254, 0.32) !important;
      color: #00f2fe !important;
      font-family: var(--font-primary, 'Poppins', sans-serif) !important;
      font-size: 10px !important;
      font-weight: 600 !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      transition: all 0.2s ease !important;
      line-height: 1 !important;
      outline: none !important;
    }
    .btn-aside-remove {
      width: 24px !important;
      height: 24px !important;
      border-radius: 5px !important;
      background: rgba(255, 255, 255, 0.04) !important;
      border: 1px solid rgba(255, 255, 255, 0.08) !important;
      color: #64748b !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-size: 12px !important;
      cursor: pointer !important;
      padding: 0 !important;
      transition: all 0.2s ease !important;
      outline: none !important;
    }
    .aside-wishlist-icon {
      width: 32px !important;
      height: 32px !important;
      border-radius: 8px !important;
      font-size: 1.15rem !important;
    }
    .aside-subtotal-lbl {
      font-size: 11px !important;
    }
    .aside-subtotal-val {
      font-size: 12.5px !important;
    }
    .aside-wishlist-footer-btn {
      font-size: 11.5px !important;
      padding: 8px 12px !important;
    }
    .aside-wishlist-clear-btn {
      font-size: 10.5px !important;
    }
  </style>

  <!-- Side Panel Backdrop Overlay (Closes panels on click) -->
  <div class="side-panel-backdrop" id="sidePanelBackdrop"></div>

  <!-- ══════════════════════════════════════════════════ -->
  <!-- 1. WHISHLIST SIDEBAR PANEL                         -->
  <!-- ══════════════════════════════════════════════════ -->
  <div class="side-panel" data-side-panel="whishlist">

    <div class="side-panel-header">
      <div class="panel-header-title">
        <div class="panel-header-icon-box pink">
          <ion-icon name="heart-outline"></ion-icon>
        </div>
        <div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <h3 class="panel-header-heading">My Wishlist</h3>
            <span class="aside-count-pill pink" id="aside-wishlist-count-badge">00</span>
          </div>
          <p class="panel-header-sub">Saved Services & Favorites</p>
        </div>
      </div>
      <button class="panel-close-btn" aria-label="Close wishlist" data-panel-btn="whishlist">
        <ion-icon name="close-outline"></ion-icon>
      </button>
    </div>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($p_assets); ?>css/wishlist.css">

    <div id="aside-wishlist-items-container" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
      <div style="padding: 40px 20px; text-align: center; color: #94a3b8;">
        <ion-icon name="heart-outline" style="font-size: 2.5rem; opacity: 0.5; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto; color: #ff6090;"></ion-icon>
        <p style="font-size: 0.95rem; margin-bottom: 15px;">No favorite services saved yet.</p>
        <a href="<?php echo htmlspecialchars($link_services); ?>" class="panel-btn-primary" style="display: inline-flex; justify-content: center; text-decoration: none;">
          <span>Browse Services</span>
        </a>
      </div>
    </div>

  </div>

  <!-- ══════════════════════════════════════════════════ -->
  <!-- 2. CART SIDEBAR PANEL                              -->
  <!-- ══════════════════════════════════════════════════ -->
  <div class="side-panel" data-side-panel="cart">

    <div class="side-panel-header">
      <div class="panel-header-title">
        <div class="panel-header-icon-box purple">
          <ion-icon name="bag-handle-outline"></ion-icon>
        </div>
        <div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <h3 class="panel-header-heading">My Cart</h3>
            <span class="aside-count-pill purple" id="aside-cart-count-badge">02</span>
          </div>
          <p class="panel-header-sub">Ready to Book Services</p>
        </div>
      </div>
      <button class="panel-close-btn" aria-label="Close cart" data-panel-btn="cart">
        <ion-icon name="close-outline"></ion-icon>
      </button>
    </div>

    <div style="padding: 40px 20px; text-align: center; color: #94a3b8;">
      <ion-icon name="bag-outline" style="font-size: 2.5rem; opacity: 0.5; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;"></ion-icon>
      <p style="font-size: 0.95rem; margin-bottom: 15px;">Your cart is currently empty.</p>
      <a href="<?php echo htmlspecialchars($link_services); ?>" class="panel-btn-primary" style="display: inline-flex; justify-content: center; text-decoration: none;">
        <span>Explore Services</span>
      </a>
    </div>

  </div>

  <!-- ══════════════════════════════════════════════════ -->
  <!-- 3. USER ACCOUNT SIDEBAR PANEL                      -->
  <!-- ══════════════════════════════════════════════════ -->
  <div class="side-panel" data-side-panel="user">

    <div class="side-panel-header">
      <div class="panel-header-title">
        <div class="panel-header-icon-box cyan">
          <ion-icon name="person-circle-outline"></ion-icon>
        </div>
        <div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <h3 class="panel-header-heading">Account Details</h3>
          </div>
          <p class="panel-header-sub">Manage profile & preferences</p>
        </div>
      </div>
      <button class="panel-close-btn" aria-label="Close user profile" data-panel-btn="user">
        <ion-icon name="close-outline"></ion-icon>
      </button>
    </div>

    <div class="user-panel">

      <!-- User Identity Card -->
      <div class="user-profile-card">
        <div class="user-avatar-wrap">
          <div class="user-avatar-badge">
            <span class="user-monogram" id="aside-user-monogram">
              <?php if ($is_logged_in): ?>
                <?php echo htmlspecialchars($user_initials); ?>
              <?php else: ?>
                <ion-icon name="person"></ion-icon>
              <?php endif; ?>
            </span>
            <span class="user-status-indicator <?php echo $is_logged_in ? 'online' : 'offline'; ?>" id="aside-status-indicator" title="<?php echo $is_logged_in ? 'Active Session' : 'Guest'; ?>"></span>
          </div>
        </div>

        <div class="user-profile-meta">
          <div class="user-profile-header-row">
            <h4 class="user-name" id="aside-user-name"><?php echo htmlspecialchars($display_name); ?></h4>
            <span class="badge-verified" id="aside-verified-badge" title="Verified ServEasy Member" style="<?php echo $is_logged_in ? '' : 'display: none;'; ?>">
              <ion-icon name="shield-checkmark"></ion-icon>
            </span>
          </div>

          <div class="user-identifier">
            <ion-icon name="<?php echo (preg_match('/^[0-9+\s\-]+$/', $display_identity)) ? 'call-outline' : 'mail-outline'; ?>"></ion-icon>
            <span class="user-email" id="aside-user-ident"><?php echo htmlspecialchars($display_identity); ?></span>
          </div>

          <p class="user-subtext" id="aside-user-sub" style="<?php echo !empty($display_sub) ? '' : 'display: none;'; ?>"><?php echo htmlspecialchars($display_sub); ?></p>
        </div>
      </div>

      <!-- Guest Auth Callout -->
      <div class="guest-auth-banner" id="aside-guest-banner" style="<?php echo $is_logged_in ? 'display: none;' : ''; ?>">
        <a href="<?php echo htmlspecialchars($link_auth . (!empty($_SERVER['REQUEST_URI']) ? '?redirect=' . urlencode($_SERVER['REQUEST_URI']) : '')); ?>" class="btn-sidebar-auth">
          <ion-icon name="log-in-outline"></ion-icon>
          <span>Sign In / Create Account</span>
        </a>
      </div>

      <!-- Quick Navigation Label -->
      <div class="sidebar-section-label">Quick Navigation</div>

      <!-- Navigation Cards -->
      <ul class="user-menu-list user-menu">

        <li>
          <a href="<?php echo htmlspecialchars($link_profile); ?>" id="aside-profile-link" class="panel-nav-card panel-link">
            <div class="nav-icon-box cyan">
              <ion-icon name="person-outline"></ion-icon>
            </div>
            <div class="nav-text-group">
              <span class="nav-label">My Profile</span>
              <span class="nav-desc">Personal details & account info</span>
            </div>
            <ion-icon name="chevron-forward-outline" class="nav-arrow"></ion-icon>
          </a>
        </li>

        <li>
          <a href="<?php echo htmlspecialchars($link_orders); ?>" id="aside-orders-link" class="panel-nav-card panel-link">
            <div class="nav-icon-box purple">
              <ion-icon name="bag-handle-outline"></ion-icon>
            </div>
            <div class="nav-text-group">
              <span class="nav-label">My Orders</span>
              <span class="nav-desc">Track bookings & service history</span>
            </div>
            <ion-icon name="chevron-forward-outline" class="nav-arrow"></ion-icon>
          </a>
        </li>

        <li>
          <a href="<?php echo htmlspecialchars($link_addresses); ?>" id="aside-addresses-link" class="panel-nav-card panel-link">
            <div class="nav-icon-box emerald">
              <ion-icon name="location-outline"></ion-icon>
            </div>
            <div class="nav-text-group">
              <span class="nav-label">My Addresses</span>
              <span class="nav-desc">Saved locations for home service</span>
            </div>
            <ion-icon name="chevron-forward-outline" class="nav-arrow"></ion-icon>
          </a>
        </li>

        <li>
          <a href="<?php echo htmlspecialchars($link_wishlist); ?>" class="panel-nav-card panel-link">
            <div class="nav-icon-box rose">
              <ion-icon name="heart-outline"></ion-icon>
            </div>
            <div class="nav-text-group">
              <span class="nav-label">Wishlist</span>
              <span class="nav-desc">Saved favorite services</span>
            </div>
            <ion-icon name="chevron-forward-outline" class="nav-arrow"></ion-icon>
          </a>
        </li>

        <!-- Logout Item (always in DOM, visibility controlled by session and token state) -->
        <li id="aside-logout-li" style="<?php echo $is_logged_in ? '' : 'display: none;'; ?>">
          <a href="<?php echo htmlspecialchars($link_logout); ?>" class="panel-nav-card panel-link logout-card" onclick="confirmLogout(event, this.href)">
            <div class="nav-icon-box red">
              <ion-icon name="log-out-outline"></ion-icon>
            </div>
            <div class="nav-text-group">
              <span class="nav-label">Logout</span>
              <span class="nav-desc">Safely end this session</span>
            </div>
            <ion-icon name="chevron-forward-outline" class="nav-arrow"></ion-icon>
          </a>
        </li>

      </ul>

      <!-- Sidebar Help Box -->
      <div class="sidebar-help-card">
        <div class="help-icon-wrap">
          <ion-icon name="headset-outline"></ion-icon>
        </div>
        <div class="help-info">
          <p class="help-heading">Need Support?</p>
          <p class="help-caption">24/7 Helpline & Concierge</p>
        </div>
        <a href="<?php echo htmlspecialchars($link_contact); ?>" class="help-link-btn">Help</a>
      </div>

    </div><!-- /user-panel -->

  </div><!-- /side-panel user -->

</aside>

<!-- Sidebar Backdrop & Keyboard Controls -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const backdrop = document.getElementById('sidePanelBackdrop');
  const sidePanels = document.querySelectorAll('[data-side-panel]');
  const panelBtns = document.querySelectorAll('[data-panel-btn]');

  function syncBackdropState() {
    let hasOpenPanel = false;
    for (let i = 0; i < sidePanels.length; i++) {
      if (sidePanels[i].classList.contains('active')) {
        hasOpenPanel = true;
        break;
      }
    }
    if (backdrop) {
      if (hasOpenPanel) {
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
      } else {
        backdrop.classList.remove('active');
        document.body.style.overflow = '';
      }
    }
  }

  // Intercept panel toggles to update backdrop smoothly & refresh wishlist drawer
  panelBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      setTimeout(syncBackdropState, 50);
      if (btn.getAttribute('data-panel-btn') === 'whishlist' && window.ServEasyWishlist) {
        window.ServEasyWishlist.renderAsideDrawer();
      }
    });
  });

  // Clicking backdrop closes all drawers
  if (backdrop) {
    backdrop.addEventListener('click', function () {
      sidePanels.forEach(function (panel) {
        panel.classList.remove('active');
      });
      syncBackdropState();
    });
  }

  // Pressing Escape key closes drawer
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      sidePanels.forEach(function (panel) {
        panel.classList.remove('active');
      });
      syncBackdropState();
    }
  });

  // Ensure notify.css and notify.js are available for alert dialogs
  if (!document.querySelector('link[href*="notify.css"]')) {
    var nl = document.createElement('link');
    nl.rel = 'stylesheet';
    nl.href = '<?php echo htmlspecialchars($p_assets); ?>css/notify.css';
    document.head.appendChild(nl);
  }
  if (typeof window.Notify === 'undefined') {
    var ns = document.createElement('script');
    ns.src = '<?php echo htmlspecialchars($p_assets); ?>js/notify.js';
    document.head.appendChild(ns);
  }

  // Ensure wishlist.js is loaded and drawer is initialized on every page
  if (typeof window.ServEasyWishlist === 'undefined') {
    var ws = document.createElement('script');
    ws.src = '<?php echo htmlspecialchars($p_assets); ?>js/wishlist.js';
    ws.onload = function () {
      if (window.ServEasyWishlist) {
        window.ServEasyWishlist.renderAsideDrawer();
      }
    };
    document.head.appendChild(ws);
  } else {
    window.ServEasyWishlist.renderAsideDrawer();
  }

  window.addEventListener('wishlist:updated', function () {
    if (window.ServEasyWishlist) {
      window.ServEasyWishlist.renderAsideDrawer();
    }
  });
});

// Dual-Channel Auth Synchronization Controller (Server Session + Client Token)
(function initServEasyAuth() {
  function applyAuthState(isLoggedIn, user) {
    const statusDot = document.getElementById('aside-status-indicator');
    if (statusDot) {
      statusDot.className = 'user-status-indicator ' + (isLoggedIn ? 'online' : 'offline');
      statusDot.title = isLoggedIn ? 'Active Session' : 'Guest';
    }

    const headerDot = document.getElementById('header-user-dot');
    if (headerDot) {
      if (isLoggedIn) {
        headerDot.classList.add('online');
        headerDot.title = 'Active Session';
      } else {
        headerDot.classList.remove('online');
        headerDot.title = 'Guest';
      }
    }

    const guestBanner = document.getElementById('aside-guest-banner');
    if (guestBanner) guestBanner.style.display = isLoggedIn ? 'none' : 'block';

    const logoutLi = document.getElementById('aside-logout-li');
    if (logoutLi) logoutLi.style.display = isLoggedIn ? 'block' : 'none';

    const verifiedBadge = document.getElementById('aside-verified-badge');
    if (verifiedBadge) verifiedBadge.style.display = isLoggedIn ? 'inline-flex' : 'none';

    if (isLoggedIn && user) {
      const name = (user.full_name || user.name || '').trim();
      const email = (user.email || user.email_address || '').trim();
      const phone = (user.phone || user.phone_number || '').trim();

      const nameEl = document.getElementById('aside-user-name');
      if (nameEl) nameEl.textContent = name || email || phone || 'ServEasy Member';

      const identEl = document.getElementById('aside-user-ident');
      if (identEl) identEl.textContent = phone || email || 'Verified Customer';

      const subEl = document.getElementById('aside-user-sub');
      if (subEl) {
        subEl.textContent = (email && phone) ? email : 'Verified Customer';
        subEl.style.display = 'block';
      }

      const monogramEl = document.getElementById('aside-user-monogram');
      if (monogramEl) {
        let initials = 'SE';
        if (name) {
          const parts = name.split(/\s+/);
          initials = parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : name.substring(0, 2).toUpperCase();
        } else if (email) {
          initials = email.substring(0, 2).toUpperCase();
        }
        monogramEl.textContent = initials;
      }
    } else if (!isLoggedIn) {
      const nameEl = document.getElementById('aside-user-name');
      if (nameEl) nameEl.textContent = 'Guest User';

      const identEl = document.getElementById('aside-user-ident');
      if (identEl) identEl.textContent = 'Login or Register';

      const subEl = document.getElementById('aside-user-sub');
      if (subEl) {
        subEl.textContent = 'Access your bookings & profile';
        subEl.style.display = 'block';
      }

      const monogramEl = document.getElementById('aside-user-monogram');
      if (monogramEl) {
        monogramEl.innerHTML = '<ion-icon name="person"></ion-icon>';
      }
    }
  }

  window.setServEasyAuthState = applyAuthState;

  try {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('logged_out')) {
      try {
        localStorage.removeItem('serveasy_access_token');
        localStorage.removeItem('serveasy_user');
        sessionStorage.clear();
      } catch (e) {}
      applyAuthState(false);
      // Clean query parameter from address bar
      urlParams.delete('logged_out');
      const newQuery = urlParams.toString();
      const newUrl = window.location.pathname + (newQuery ? '?' + newQuery : '') + window.location.hash;
      window.history.replaceState({}, '', newUrl);
      return;
    }

    const token = localStorage.getItem('serveasy_access_token');
    if (token) {
      const rawUser = localStorage.getItem('serveasy_user');
      const user = rawUser ? JSON.parse(rawUser) : null;
      applyAuthState(true, user);

      // Silent sync with PHP session if server rendered as guest
      <?php if (!$is_logged_in): ?>
      fetch('<?php echo htmlspecialchars($web_root); ?>/auth/session-sync.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'access_token=' + encodeURIComponent(token)
      }).catch(function() {});
      <?php endif; ?>
    } else {
      <?php if (!$is_logged_in): ?>
      applyAuthState(false);
      <?php endif; ?>
    }
  } catch (e) {}
})();

// Logout confirmation alert handler
function confirmLogout(event, href) {
  if (event && event.preventDefault) event.preventDefault();
  var logoutUrl = href || '<?php echo htmlspecialchars($link_logout); ?>';

  var executeLogout = function() {
    try {
      localStorage.removeItem('serveasy_access_token');
      localStorage.removeItem('serveasy_user');
      sessionStorage.clear();
      document.cookie = 'access_token=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT; SameSite=Lax; Secure';
      document.cookie = 'access_token=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
      document.cookie = 'PHPSESSID=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT; SameSite=Lax; Secure';
      document.cookie = 'PHPSESSID=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    } catch (e) {}
    if (window.setServEasyAuthState) {
      window.setServEasyAuthState(false);
    }
    window.location.href = logoutUrl;
  };

  if (typeof Notify !== 'undefined' && typeof Notify.confirm === 'function') {
    Notify.confirm('Are you sure you want to log out of your ServEasy account?', {
      title: 'Confirm Logout',
      confirmText: 'Log Out',
      cancelText: 'Stay Logged In',
      type: 'danger'
    }).then(function (confirmed) {
      if (confirmed) {
        executeLogout();
      }
    });
  } else if (window.confirm('Are you sure you want to log out of your account?')) {
    executeLogout();
  }
}
</script>
