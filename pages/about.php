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
  <title>About ServEasy — Trusted Doorstep On-Demand Services</title>
  <meta name="description" content="Learn about ServEasy's mission to deliver verified, transparent, and high-quality doorstep services for vehicles, homes, domestic care, and roadside emergency assistance.">
  <link rel="canonical" href="https://silver-penguin-156353.hostingersite.com/pages/about.php">

  <!-- Favicon -->
  <link rel="shortcut icon" href="./assets/favicon.svg" type="image/svg+xml">

  <!-- Core Stylesheets -->
  <link rel="stylesheet" href="./assets/css/main.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/global.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/home.css?v=<?= SERVEASY_ASSET_VER ?>">
  <link rel="stylesheet" href="./assets/css/notify.css?v=<?= SERVEASY_ASSET_VER ?>">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

  <style>
    .about-hero-section {
      padding: 60px 20px 40px;
      text-align: center;
      position: relative;
    }
    .about-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 18px;
      border-radius: 50px;
      background: rgba(0, 242, 254, 0.1);
      border: 1px solid rgba(0, 242, 254, 0.3);
      color: #00f2fe;
      font-size: 0.85rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 20px;
    }
    .about-hero-title {
      font-size: 2.8rem;
      font-weight: 800;
      color: #ffffff;
      line-height: 1.2;
      max-width: 900px;
      margin: 0 auto 20px;
    }
    .about-hero-title span {
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .about-hero-desc {
      font-size: 1.15rem;
      color: #94a3b8;
      max-width: 750px;
      margin: 0 auto 40px;
      line-height: 1.7;
    }

    .about-grid-3 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 25px;
      margin-bottom: 60px;
    }
    .about-card {
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 35px 30px;
      transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    }
    .about-card:hover {
      transform: translateY(-5px);
      border-color: rgba(0, 242, 254, 0.4);
      box-shadow: 0 12px 30px rgba(0, 242, 254, 0.15);
    }
    .about-card-icon {
      width: 60px;
      height: 60px;
      border-radius: 16px;
      background: rgba(0, 242, 254, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      color: #00f2fe;
      margin-bottom: 20px;
    }
    .about-card-icon.purple { background: rgba(168, 85, 247, 0.1); color: #c084fc; }
    .about-card-icon.emerald { background: rgba(16, 185, 129, 0.1); color: #34d399; }
    .about-card-icon.amber { background: rgba(245, 158, 11, 0.1); color: #fbbf24; }
    .about-card-title {
      font-size: 1.35rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 12px;
    }
    .about-card-text {
      color: #94a3b8;
      font-size: 0.95rem;
      line-height: 1.6;
    }

    .process-timeline {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
      margin-top: 40px;
    }
    .process-step {
      background: rgba(30, 41, 59, 0.5);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 18px;
      padding: 30px 25px;
      position: relative;
    }
    .step-number {
      display: inline-block;
      width: 36px;
      height: 36px;
      line-height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00f2fe, #4facfe);
      color: #0f172a;
      font-weight: 800;
      font-size: 1.1rem;
      text-align: center;
      margin-bottom: 18px;
    }
    .step-title {
      font-size: 1.2rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 10px;
    }
    .step-desc {
      color: #94a3b8;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    .cta-banner-glass {
      background: linear-gradient(135deg, rgba(6, 182, 212, 0.15), rgba(59, 130, 246, 0.15));
      border: 1px solid rgba(0, 242, 254, 0.3);
      border-radius: 24px;
      padding: 50px 30px;
      text-align: center;
      margin: 60px 0;
    }
    .btn-about-primary {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 14px 32px;
      border-radius: 12px;
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
      color: #0f172a;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    .btn-about-primary:hover {
      box-shadow: 0 8px 25px rgba(0, 242, 254, 0.4);
      transform: translateY(-2px);
    }
    .btn-about-secondary {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 14px 32px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #fff;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.3s ease;
      margin-left: 15px;
    }
    .btn-about-secondary:hover {
      background: rgba(255, 255, 255, 0.15);
      transform: translateY(-2px);
    }
  </style>
</head>

<body id="top">

  <!-- Header -->
  <?php include __DIR__ . '/layout/header.php'; ?>

  <!-- Profile / Wishlist / Cart Aside Drawer -->
  <?php include __DIR__ . '/constants/aside.php'; ?>

  <main>
    <div class="container">

      <!-- Breadcrumbs -->
      <div style="padding: 25px 0 10px;">
        <span style="color: #64748b; font-size: 0.9rem;">
          <a href="../index.php" style="color: #94a3b8; text-decoration: none;">Home</a> / <span style="color: #00f2fe;">About Us</span>
        </span>
      </div>

      <!-- Hero Section -->
      <section class="about-hero-section">
        <div class="about-badge">
          <ion-icon name="shield-checkmark"></ion-icon>
          <span>The ServEasy Ecosystem</span>
        </div>
        <h1 class="about-hero-title">
          Connecting Households & Drivers with <span>Trusted Service Specialists</span>
        </h1>
        <p class="about-hero-desc">
          ServEasy was built on a simple premise: requesting certified vehicle maintenance, residential deep cleaning, domestic helpers, and emergency roadside rescue should be as effortless, transparent, and reliable as modern digital commerce.
        </p>
      </section>

      <!-- Mission & Core Pillars -->
      <div class="about-grid-3">
        <div class="about-card">
          <div class="about-card-icon">
            <ion-icon name="ribbon-outline"></ion-icon>
          </div>
          <h3 class="about-card-title">Verified Specialists</h3>
          <p class="about-card-text">
            Every service provider in our network undergoes rigorous government ID validation, background verification, and skill certification before accepting jobs on the platform.
          </p>
        </div>

        <div class="about-card">
          <div class="about-card-icon emerald">
            <ion-icon name="cash-outline"></ion-icon>
          </div>
          <h3 class="about-card-title">Transparent Pricing</h3>
          <p class="about-card-text">
            Zero hidden charges or surprise markups. View detailed itemized pricing, package inclusions, and optional add-ons upfront with clear pay-on-service or secure digital payment options.
          </p>
        </div>

        <div class="about-card">
          <div class="about-card-icon purple">
            <ion-icon name="navigate-outline"></ion-icon>
          </div>
          <h3 class="about-card-title">Live Tracking & ETA</h3>
          <p class="about-card-text">
            Stay in complete control with scheduled arrival slots, live GPS provider dispatch coordinates, and automated status updates straight to your dashboard and mobile notifications.
          </p>
        </div>
      </div>

      <!-- How ServEasy Works Section -->
      <section style="margin: 60px 0;">
        <div style="text-align: center; margin-bottom: 30px;">
          <span style="color: #00f2fe; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Seamless Fulfillment</span>
          <h2 style="color: #fff; font-size: 2.2rem; font-weight: 800; margin-top: 8px;">How ServEasy Works</h2>
        </div>

        <div class="process-timeline">
          <div class="process-step">
            <span class="step-number">1</span>
            <h4 class="step-title">Select Your Service</h4>
            <p class="step-desc">
              Browse automotive detailing, deep home sanitization, personal staff, or 24/7 emergency towing. Compare packages tailored to your exact requirement.
            </p>
          </div>

          <div class="process-step">
            <span class="step-number">2</span>
            <h4 class="step-title">Schedule Slot & Location</h4>
            <p class="step-desc">
              Choose your preferred date, convenient time window, and doorstep address. Receive an instant unique booking reference upon server confirmation.
            </p>
          </div>

          <div class="process-step">
            <span class="step-number">3</span>
            <h4 class="step-title">Expert Arrival & Settlement</h4>
            <p class="step-desc">
              Your assigned verified partner arrives fully equipped. Inspect the completed work and settle seamlessly via cash, UPI QR, or debit/credit card.
            </p>
          </div>
        </div>
      </section>

      <!-- Call to Action Banner -->
      <section class="cta-banner-glass">
        <h2 style="color: #fff; font-size: 2.2rem; font-weight: 800; margin-bottom: 15px;">Experience Better Service Today</h2>
        <p style="color: #94a3b8; font-size: 1.1rem; max-width: 650px; margin: 0 auto 30px; line-height: 1.6;">
          Join thousands of satisfied customers who trust ServEasy for high-precision doorstep vehicle care and home hygiene.
        </p>
        <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 15px;">
          <a href="./services.php" class="btn-about-primary">
            <span>Browse All Services</span>
            <ion-icon name="arrow-forward-outline"></ion-icon>
          </a>
          <a href="./contact.php" class="btn-about-secondary">
            <span>Contact Support Concierge</span>
            <ion-icon name="chatbubbles-outline"></ion-icon>
          </a>
        </div>
      </section>

    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- Ionicons -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
