<footer class="footer">

    <div class="footer-top">
      <div class="container">

        <div class="footer-brand">

          <a href="./index.php" class="logo">
            <?php
            $logo_path = __DIR__ . '/../assets/images/logo.png';
            if (file_exists($logo_path)) {
                $footer_web_root = isset($nav_web_root) ? $nav_web_root : (isset($web_root) ? $web_root : '.');
                $assetVer = defined('SERVEASY_ASSET_VER') ? SERVEASY_ASSET_VER : '20261119v1';
                echo '<img src="' . $footer_web_root . '/pages/assets/images/logo.png?v=' . $assetVer . '" alt="ServEasy Logo" style="max-height: 40px; width: auto; display: block;">';
            } else {
                echo 'Serv<span class="span">Easy</span>';
            }
            ?>
          </a>

          <p class="footer-text">
            It was popularised in the 1960s with the release of Letraset sheets containing Lorem passages and more
            recently with
            desktop publishing software like including.
          </p>

          <ul class="social-list">

            <li>
              <a href="#" class="social-link">
                <ion-icon name="logo-facebook"></ion-icon>
              </a>
            </li>

            <li>
              <a href="#" class="social-link">
                <ion-icon name="logo-twitter"></ion-icon>
              </a>
            </li>

            <li>
              <a href="#" class="social-link">
                <ion-icon name="logo-skype"></ion-icon>
              </a>
            </li>

            <li>
              <a href="#" class="social-link">
                <ion-icon name="logo-linkedin"></ion-icon>
              </a>
            </li>

          </ul>

        </div>

        <ul class="footer-list">

          <li>
            <p class="footer-list-title">Company</p>
          </li>

          <li>
            <a href="./about.php" class="footer-link">About Us</a>
          </li>

          <li>
            <a href="./shop.php" class="footer-link">Shop</a>
          </li>

          <li>
            <a href="./blog.php" class="footer-link">Blog</a>
          </li>

          <li>
            <a href="./shop.php" class="footer-link">Product</a>
          </li>

          <li>
            <a href="./contact.php" class="footer-link">Contact Us</a>
          </li>

        </ul>

        <ul class="footer-list">

          <li>
            <p class="footer-list-title">Contact</p>
          </li>

          <li class="footer-item">
            <div class="contact-icon">
              <ion-icon name="location-outline"></ion-icon>
            </div>

            <address class="contact-link">
              7 Green Lake Street Crawfordsville, IN 47933
            </address>
          </li>

          <li class="footer-item">
            <div class="contact-icon">
              <ion-icon name="call-outline"></ion-icon>
            </div>

            <a href="tel:+1800123456789" class="contact-link">+1 800 123 456 789</a>
          </li>

          <li class="footer-item">
            <div class="contact-icon">
              <ion-icon name="mail-outline"></ion-icon>
            </div>

            <a href="mailto:serveasy@help.com" class="contact-link">serveasy@help.com</a>
          </li>

        </ul>

        <div class="footer-list">

          <p class="footer-list-title">Newsletter</p>

          <p class="newsletter-text">
            You will be notified when somthing new will be appear.
          </p>

          <form action="" class="footer-form">
            <input type="email" name="email" placeholder="Email Address *" required class="footer-input">

            <button type="submit" class="footer-form-btn" aria-label="Submit">
              <ion-icon name="mail-outline"></ion-icon>
            </button>
          </form>

        </div>

      </div>
    </div>

    <div class="footer-bottom">
      <div class="container">

        <p class="copyright">
          &copy; 2022 <a href="#" class="copyright-link">S.S.R</a>. All Rights Reserved.
        </p>

        <ul class="footer-bottom-list">

          <li>
            <a href="#" class="footer-bottom-link">Term and Service</a>
          </li>

          <li>
            <a href="#" class="footer-bottom-link">Privacy Policy</a>
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