<?php
/**
 * ServEasy Customer Web Portal - Privacy Policy
 * Comprehensive Data Privacy & Protection Policy
 */
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
  <title>Privacy Policy — ServEasy On-Demand Services</title>
  <meta name="description" content="ServEasy's Privacy Policy explains how we collect, use, protect, and handle your personal data across our on-demand services platform.">
  <link rel="canonical" href="https://silver-penguin-156353.hostingersite.com/pages/privacy-policy.php">

  <!-- Favicon -->
  <link rel="shortcut icon" href="./assets/favicon.svg" type="image/svg+xml">

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
    .legal-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 50px 20px 80px;
    }
    .legal-header {
      text-align: center;
      margin-bottom: 45px;
    }
    .legal-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 16px;
      border-radius: 50px;
      background: rgba(0, 242, 254, 0.1);
      border: 1px solid rgba(0, 242, 254, 0.3);
      color: #00f2fe;
      font-size: 0.85rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 16px;
    }
    .legal-title {
      font-size: 2.6rem;
      font-weight: 800;
      color: #ffffff;
      line-height: 1.25;
      margin-bottom: 15px;
    }
    .legal-meta {
      font-size: 0.95rem;
      color: #94a3b8;
    }
    .legal-card {
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 40px;
      color: #cbd5e1;
      line-height: 1.75;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }
    .legal-card h2 {
      font-size: 1.45rem;
      font-weight: 700;
      color: #ffffff;
      margin-top: 35px;
      margin-bottom: 14px;
      padding-bottom: 8px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .legal-card h2:first-of-type {
      margin-top: 0;
    }
    .legal-card h2 ion-icon {
      color: #00f2fe;
      font-size: 1.3rem;
    }
    .legal-card p {
      margin-bottom: 16px;
      font-size: 0.98rem;
    }
    .legal-card ul {
      margin: 0 0 20px 24px;
      list-style-type: disc;
    }
    .legal-card li {
      margin-bottom: 8px;
      font-size: 0.96rem;
    }
    .legal-notice-box {
      background: rgba(245, 158, 11, 0.1);
      border-left: 4px solid #f59e0b;
      padding: 16px 20px;
      border-radius: 8px;
      margin: 25px 0;
      color: #fde68a;
      font-size: 0.92rem;
    }
    .legal-contact-box {
      background: rgba(0, 242, 254, 0.05);
      border: 1px solid rgba(0, 242, 254, 0.2);
      border-radius: 12px;
      padding: 24px;
      margin-top: 35px;
    }
  </style>
</head>

<body id="top">

  <!-- Header -->
  <?php include __DIR__ . '/layout/header.php'; ?>

  <!-- Sidebar Overlays -->
  <?php include __DIR__ . '/constants/aside.php'; ?>

  <main>
    <div class="legal-container">

      <div class="legal-header">
        <span class="legal-badge">
          <ion-icon name="shield-checkmark-outline"></ion-icon>
          Data Protection & Privacy
        </span>
        <h1 class="legal-title">Privacy Policy</h1>
        <p class="legal-meta">Effective Date: November 1, 2026 &bull; Last Updated: November 2026</p>
      </div>

      <div class="legal-card">

        <div class="legal-notice-box">
          <strong>Notice for Legal Compliance:</strong> This Privacy Policy outlines the operational and technical data practices of the ServEasy platform. Final statutory revisions are subject to review and confirmation by ServEasy legal counsel.
        </div>

        <h2><ion-icon name="information-circle-outline"></ion-icon> 1. Introduction and Scope</h2>
        <p>
          ServEasy ("we", "us", or "our") operates the on-demand service marketplace connecting registered customers with independent, verified service providers for automotive wash and repair, domestic cleaning, skilled personal staff, transport, lodging, and roadside assistance.
        </p>
        <p>
          This Privacy Policy explains how we collect, store, process, and protect your personal information when you access our website (<a href="https://silver-penguin-156353.hostingersite.com" style="color: #00f2fe;">silver-penguin-156353.hostingersite.com</a>), mobile interfaces, APIs, and associated customer services.
        </p>

        <h2><ion-icon name="finger-print-outline"></ion-icon> 2. Information We Collect</h2>
        <p>To provide high-precision doorstep service fulfillment, we collect the following categories of information:</p>
        <ul>
          <li><strong>Identity & Contact Information:</strong> Full name, telephone/mobile number, email address, and profile photo (if uploaded).</li>
          <li><strong>Service Delivery Address & Location:</strong> Physical residential or commercial street address, city, state, postal code, and optional GPS coordinates to dispatch technicians accurately.</li>
          <li><strong>Vehicle & Property Details:</strong> Vehicle make, model, registration number, or apartment/facility specifications required to fulfill dedicated service requests.</li>
          <li><strong>Booking & Transaction Records:</strong> Service requests, appointment dates and times, selected add-ons, convenience fee records, and service notes.</li>
          <li><strong>Payment Information:</strong> We do NOT store complete credit card or debit card numbers on our servers. All digital payments are processed through certified PCI-DSS compliant payment gateways (such as Razorpay or Stripe). We retain only transaction references, payment mode, and order reconciliation IDs.</li>
          <li><strong>Technical & Device Data:</strong> IP address, browser type, operating system, device identifiers, and platform logs collected for security auditing and rate limiting.</li>
        </ul>

        <h2><ion-icon name="sync-outline"></ion-icon> 3. How We Use Your Information</h2>
        <p>We process your personal information strictly for legitimate operational purposes:</p>
        <ul>
          <li>Dispatching verified service professionals to your requested location.</li>
          <li>Sending automated SMS, WhatsApp, and email order confirmations, OTP verification codes, and technician arrival alerts.</li>
          <li>Processing secure payments, convenience fees, and verified refunds.</li>
          <li>Responding to customer support tickets, inquiries, and dispute investigations.</li>
          <li>Detecting, preventing, and mitigating fraudulent transactions, account takeover attempts, and abusive platform activities.</li>
          <li>Improving service recommendations and user experience based on aggregated usage analytics.</li>
        </ul>

        <h2><ion-icon name="share-social-outline"></ion-icon> 4. Information Sharing and Disclosure</h2>
        <p>
          ServEasy maintains a strict policy: <strong>We do not sell, rent, or trade your personal data to any third-party marketing companies.</strong>
        </p>
        <p>Information is disclosed solely in the following controlled scenarios:</p>
        <ul>
          <li><strong>Assigned Service Providers:</strong> Once a booking is confirmed, the assigned technician receives your name, service address, vehicle/property notes, and contact number solely to execute the scheduled service. Providers are contractually prohibited from using your data for off-platform communication.</li>
          <li><strong>Infrastructure & Service Processors:</strong> Trusted technical vendors supporting our hosting, transactional email delivery, and SMS/OTP gateways under confidentiality agreements.</li>
          <li><strong>Legal & Regulatory Authorities:</strong> Where mandated by applicable law, court order, or official governmental request to comply with legal processes or protect vital personal safety.</li>
        </ul>

        <h2><ion-icon name="lock-closed-outline"></ion-icon> 5. Data Security and Technical Safeguards</h2>
        <p>We implement enterprise-grade technical and organizational safeguards to protect your personal data:</p>
        <ul>
          <li><strong>Transport Layer Security (TLS/SSL):</strong> All web communication between your browser and ServEasy servers is encrypted in transit.</li>
          <li><strong>Credential Protection:</strong> Passwords are protected using salted bcrypt/Argon2 one-way hashing algorithms. We never store plain-text passwords.</li>
          <li><strong>Database Defense:</strong> Backend queries utilize PDO prepared statements with strict parameter binding to eliminate SQL injection vectors.</li>
          <li><strong>Role-Based Access Control (RBAC):</strong> Administrative access to user data is segregated by roles (Customer, Provider, Admin, Superadmin) with audit logging.</li>
        </ul>

        <h2><ion-icon name="archive-outline"></ion-icon> 6. Data Retention and Account Deletion</h2>
        <p>
          We retain your personal information for as long as your account remains active or as required to fulfill tax, legal accounting, warranty, and dispute resolution obligations (typically up to 3 years from the date of the last completed booking).
        </p>
        <p>
          You have the right to request the permanent deletion of your account and associated personal data. Upon receiving a verified request, we will deactivate your account and purge or anonymize your personal records, except where statutory retention laws require preservation.
        </p>

        <h2><ion-icon name="globe-outline"></ion-icon> 7. Cookies and Client-Side Storage</h2>
        <p>
          ServEasy uses functional cookies and browser <code>localStorage</code> to manage authenticated session state, remember your selected service preferences, and synchronize your cart and wishlist counters across navigation turns. You may disable cookies in your browser settings, though certain authenticated features may not function properly.
        </p>

        <h2><ion-icon name="people-outline"></ion-icon> 8. Your Privacy Rights</h2>
        <p>Depending on your jurisdiction, you possess the right to:</p>
        <ul>
          <li>Access the personal data we hold about you.</li>
          <li>Request correction of inaccurate or outdated profile information.</li>
          <li>Object to or restrict specific processing activities.</li>
          <li>Request account deletion and data portability.</li>
        </ul>

        <div class="legal-contact-box">
          <h3 style="color: #fff; margin-bottom: 10px; font-size: 1.15rem; font-weight: 700;">
            <ion-icon name="mail-outline" style="vertical-align: -2px; color: #00f2fe;"></ion-icon>
            Privacy Officer & Data Inquiries
          </h3>
          <p style="margin-bottom: 8px;">
            To exercise your privacy rights, request data deletion, or report a security concern, contact our dedicated Data Privacy Desk:
          </p>
          <ul style="list-style-type: none; margin-left: 0;">
            <li><strong>Email:</strong> <a href="mailto:support@serveasy.in" style="color: #00f2fe;">support@serveasy.in</a></li>
            <li><strong>Postal Address:</strong> ServEasy Customer Operations Hub, Salt Lake, Sector V, Greater Kolkata, West Bengal, 700091, India</li>
            <li><strong>Helpline:</strong> +91 96474 14029 (Mon – Sat, 9:00 AM – 7:00 PM IST)</li>
          </ul>
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
