<?php
/**
 * ServEasy Customer Web Portal - 404 Error Page
 * Branded Not Found Page with Navigation Fallbacks
 */
http_response_code(404);
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
  <title>404 Page Not Found — ServEasy Doorstep Services</title>
  <meta name="robots" content="noindex, follow">

  <!-- Favicon -->
    <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
  <link rel="alternate icon" type="image/x-icon" href="./assets/favicon.ico">
  <link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon-32x32.png">
  <link rel="apple-touch-icon" sizes="180x180" href="./assets/apple-touch-icon.png">

  <!-- Core Stylesheets -->
  <link rel="stylesheet" href="./assets/css/main.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/global.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/home.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/notify.css?v=<?= SERVEASY_ASSET_VER ?>">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

  <style>
    .not-found-wrapper {
      min-height: 70vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 20px;
      position: relative;
    }
    .not-found-card {
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 24px;
      padding: 50px 30px;
      max-width: 650px;
      width: 100%;
      text-align: center;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }
    .not-found-glitch {
      font-size: 6.5rem;
      font-weight: 900;
      line-height: 1;
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 50%, #f43f5e 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 15px;
      letter-spacing: -2px;
    }
    .not-found-title {
      font-size: 1.85rem;
      font-weight: 800;
      color: #ffffff;
      margin-bottom: 12px;
    }
    .not-found-desc {
      color: #94a3b8;
      font-size: 1.05rem;
      line-height: 1.6;
      margin-bottom: 30px;
    }
    .not-found-actions {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 15px;
    }
    .btn-nf-primary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
      color: #0b132b;
      font-weight: 700;
      padding: 12px 24px;
      border-radius: 12px;
      text-decoration: none;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-nf-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 242, 254, 0.35);
    }
    .btn-nf-secondary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #ffffff;
      font-weight: 600;
      padding: 12px 24px;
      border-radius: 12px;
      text-decoration: none;
      transition: background 0.2s;
    }
    .btn-nf-secondary:hover {
      background: rgba(255, 255, 255, 0.12);
    }
  </style>
</head>

<body id="top">

  <!-- Header -->
  <?php include __DIR__ . '/layout/header.php'; ?>

  <!-- Sidebar Overlays -->
  <?php include __DIR__ . '/constants/aside.php'; ?>

  <main>
    <div class="not-found-wrapper">
      <div class="not-found-card">
        <div class="not-found-glitch">404</div>
        <h1 class="not-found-title">Service Route Not Found</h1>
        <p class="not-found-desc">
          The requested page or service route could not be located. It may have been relocated, rescheduled, or temporarily removed from the catalog.
        </p>

        <div class="not-found-actions">
          <a href="../index.php" class="btn-nf-primary">
            <ion-icon name="home-outline"></ion-icon>
            <span>Back to Homepage</span>
          </a>
          <a href="./services.php" class="btn-nf-secondary">
            <ion-icon name="grid-outline"></ion-icon>
            <span>Browse All Services</span>
          </a>
          <a href="./contact.php" class="btn-nf-secondary">
            <ion-icon name="chatbubbles-outline"></ion-icon>
            <span>Contact Support</span>
          </a>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- Ionicons -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
