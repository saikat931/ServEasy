<?php
/**
 * ServEasy Customer Web Portal - Global Footer Component
 */
$footer_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (($fpos = stripos($footer_script, '/frontend-web')) !== false) {
    $f_root = substr($footer_script, 0, $fpos + strlen('/frontend-web'));
} elseif (($fpos = strpos($footer_script, '/Frontend/Web')) !== false) {
    $f_root = substr($footer_script, 0, $fpos + strlen('/Frontend/Web'));
} else {
    $f_pages_pos = strpos($footer_script, '/pages');
    $f_root = ($f_pages_pos !== false) ? substr($footer_script, 0, $f_pages_pos) : '';
}
$f_home       = ($f_root !== '' ? $f_root : '') . '/index.php';
$f_about      = ($f_root !== '' ? $f_root : '') . '/pages/about.php';
$f_services   = ($f_root !== '' ? $f_root : '') . '/pages/services.php';
$f_categories = ($f_root !== '' ? $f_root : '') . '/pages/service-category.php';
$f_blog       = ($f_root !== '' ? $f_root : '') . '/pages/blog.php';
$f_contact    = ($f_root !== '' ? $f_root : '') . '/pages/contact.php';
$f_terms      = ($f_root !== '' ? $f_root : '') . '/pages/terms-of-service.php';
$f_privacy    = ($f_root !== '' ? $f_root : '') . '/pages/privacy-policy.php';
$current_year = date('Y');
?>
  <footer class="footer">

    <div class="footer-top">
      <div class="container">

        <div class="footer-brand">

          <a href="<?= htmlspecialchars($f_home) ?>" class="logo" style="text-decoration: none;">
            <?php
            $logo_path = __DIR__ . '/../assets/images/logo.png';
            if (file_exists($logo_path)) {
                $footer_web_root = isset($nav_web_root) ? $nav_web_root : (isset($web_root) ? $web_root : '.');
                $assetVer = defined('SERVEASY_ASSET_VER') ? SERVEASY_ASSET_VER : '20261119v1';
                echo '<img src="' . htmlspecialchars($footer_web_root) . '/pages/assets/images/logo.png?v=' . htmlspecialchars($assetVer) . '" alt="ServEasy Logo" style="max-height: 40px; width: auto; display: block;">';
            } else {
                echo '<span style="font-weight: 800; font-size: 1.6rem; color: #fff; letter-spacing: -0.5px;">Serv<span style="color: #00f2fe;">Easy</span></span>';
            }
            ?>
          </a>

          <p class="footer-text">
            ServEasy connects households and vehicle owners with certified, background-checked service professionals for doorstep vehicle maintenance, deep home cleaning, personal domestic assistance, and 24/7 roadside emergency rescue.
          </p>

          <ul class="social-list">

            <li>
              <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook">
                <ion-icon name="logo-facebook"></ion-icon>
              </a>
            </li>

            <li>
              <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Twitter">
                <ion-icon name="logo-twitter"></ion-icon>
              </a>
            </li>

            <li>
              <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram">
                <ion-icon name="logo-instagram"></ion-icon>
              </a>
            </li>

            <li>
              <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="LinkedIn">
                <ion-icon name="logo-linkedin"></ion-icon>
              </a>
            </li>

          </ul>

        </div>

        <ul class="footer-list">

          <li>
            <p class="footer-list-title">Explore & Company</p>
          </li>

          <li>
            <a href="<?= htmlspecialchars($f_about) ?>" class="footer-link">About ServEasy</a>
          </li>

          <li>
            <a href="<?= htmlspecialchars($f_services) ?>" class="footer-link">All Services</a>
          </li>

          <li>
            <a href="<?= htmlspecialchars($f_categories) ?>" class="footer-link">Service Categories</a>
          </li>

          <li>
            <a href="<?= htmlspecialchars($f_blog) ?>" class="footer-link">Guides & Blog</a>
          </li>

          <li>
            <a href="<?= htmlspecialchars($f_contact) ?>" class="footer-link">Contact & Support</a>
          </li>

        </ul>

        <ul class="footer-list">

          <li>
            <p class="footer-list-title">Verified Support</p>
          </li>

          <li class="footer-item">
            <div class="contact-icon">
              <ion-icon name="location-outline"></ion-icon>
            </div>

            <address class="contact-link" style="font-style: normal;">
              ServEasy Hub, Greater Kolkata, West Bengal, 700157, India
            </address>
          </li>

          <li class="footer-item">
            <div class="contact-icon">
              <ion-icon name="call-outline"></ion-icon>
            </div>

            <a href="tel:+919647414029" class="contact-link">+91 96474 14029</a>
          </li>

          <li class="footer-item">
            <div class="contact-icon">
              <ion-icon name="mail-outline"></ion-icon>
            </div>

            <a href="mailto:support@serveasy.in" class="contact-link">support@serveasy.in</a>
          </li>

        </ul>

        <div class="footer-list">

          <p class="footer-list-title">Stay Informed</p>

          <p class="newsletter-text">
            Subscribe to receive seasonal vehicle maintenance checklists, home hygiene discounts, and service updates.
          </p>

          <form action="<?= htmlspecialchars($f_contact) ?>" method="GET" class="footer-form">
            <input type="email" name="email" placeholder="Enter your email *" required class="footer-input">

            <button type="submit" class="footer-form-btn" aria-label="Subscribe to newsletter">
              <ion-icon name="mail-outline"></ion-icon>
            </button>
          </form>

        </div>

      </div>
    </div>

    <div class="footer-bottom">
      <div class="container">

        <p class="copyright">
          &copy; <?= htmlspecialchars((string)$current_year) ?> <a href="<?= htmlspecialchars($f_home) ?>" class="copyright-link">ServEasy</a>. All Rights Reserved.
        </p>

        <ul class="footer-bottom-list">

          <li>
            <a href="<?= htmlspecialchars($f_terms) ?>" class="footer-bottom-link">Terms of Service</a>
          </li>

          <li>
            <a href="<?= htmlspecialchars($f_privacy) ?>" class="footer-bottom-link">Privacy Policy</a>
          </li>

        </ul>

      </div>
    </div>

  </footer>

<?php
if (class_exists('SystemLogger')) {
    SystemLogger::logRequest();
}
?>
