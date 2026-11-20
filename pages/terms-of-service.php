<?php
/**
 * ServEasy Customer Web Portal - Terms of Service
 * Comprehensive Customer & Service Provider Agreement
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
  <title>Terms of Service — ServEasy On-Demand Services</title>
  <meta name="description" content="ServEasy Terms of Service: Master user agreement governing customer bookings, verified service provider conduct, cancellations, payments, and platform policies.">
  <link rel="canonical" href="https://silver-penguin-156353.hostingersite.com/pages/terms-of-service.php">

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
          <ion-icon name="document-text-outline"></ion-icon>
          User Agreement
        </span>
        <h1 class="legal-title">Terms of Service</h1>
        <p class="legal-meta">Effective Date: November 1, 2026 &bull; Last Updated: November 2026</p>
      </div>

      <div class="legal-card">

        <div class="legal-notice-box">
          <strong>Notice for Legal Compliance:</strong> These Terms of Service constitute a legally binding agreement between you and ServEasy. Final terms remain subject to formal review and ratification by ServEasy legal counsel.
        </div>

        <h2><ion-icon name="checkmark-done-circle-outline"></ion-icon> 1. Acceptance of Terms</h2>
        <p>
          By creating an account, browsing service catalogs, scheduling bookings, or making payments through the ServEasy platform, you confirm that you are at least 18 years of age and legally capable of entering into binding contracts. If you do not agree to these terms, you must refrain from using the platform.
        </p>

        <h2><ion-icon name="business-outline"></ion-icon> 2. Platform Role & Relationship</h2>
        <p>
          ServEasy is a technology platform that connects customers seeking vehicle care, domestic cleaning, skilled assistance, transportation, and emergency roadside help with vetted, independent third-party service providers ("Technicians" or "Providers").
        </p>
        <p>
          Unless explicitly stated otherwise for specific company-operated pilot hubs, ServEasy acts as an intermediary marketplace facilitator. Service contracts for execution of work are formed directly between the customer and the assigned provider, backed by ServEasy's service guarantees, quality standards, and dispute escalation mechanism.
        </p>

        <h2><ion-icon name="person-circle-outline"></ion-icon> 3. Account Security and Responsibilities</h2>
        <p>When creating an account, you agree to:</p>
        <ul>
          <li>Provide accurate, current, and complete registration information.</li>
          <li>Maintain the confidentiality of your login credentials and one-time passwords (OTPs).</li>
          <li>Promptly notify ServEasy of any unauthorized access or security breach regarding your account.</li>
          <li>Assume responsibility for all service requests and bookings dispatched through your authenticated account.</li>
        </ul>

        <h2><ion-icon name="calendar-outline"></ion-icon> 4. Booking, Dispatch, and Execution</h2>
        <p>
          All service bookings are subject to technician availability, geographic dispatch radius, and operational scheduling:
        </p>
        <ul>
          <li><strong>Service Estimates:</strong> Displayed prices represent base service rates. Final costs may adjust if optional add-ons, extra labor hours, or specialized parts are requested on-site with your prior verbal or written consent.</li>
          <li><strong>Site Access:</strong> Customers must ensure safe, clear, and unencumbered physical access to the designated vehicle or premises at the scheduled arrival time.</li>
          <li><strong>Safety & Water/Power Supplies:</strong> For services requiring utilities (such as high-pressure vehicle washing or deep carpet extraction), the customer must facilitate access to standard household water or power connections as indicated during checkout.</li>
        </ul>

        <h2><ion-icon name="card-outline"></ion-icon> 5. Pricing, Convenience Fees, and Payments</h2>
        <p>
          All payments must be settled through the authorized platform payment gateway or official cash-on-delivery options where supported.
        </p>
        <ul>
          <li><strong>Convenience Fee:</strong> A nominal platform technology and insurance fee may apply to each booking to cover verification, live dispatch tracking, and 24/7 incident coordination.</li>
          <li><strong>Prohibition of Off-Platform Payments:</strong> Customers and providers are strictly prohibited from soliciting or settling payments outside the platform for services booked through ServEasy. Doing so voids all service warranties and insurance coverages.</li>
        </ul>

        <h2><ion-icon name="close-circle-outline"></ion-icon> 6. Cancellation, Rescheduling & Refund Policy</h2>
        <p>
          We maintain a fair cancellation framework designed to respect both customer flexibility and service provider preparation:
        </p>
        <ul>
          <li><strong>Free Cancellation Window:</strong> Bookings canceled at least 2 hours prior to the scheduled appointment window incur no cancellation penalty.</li>
          <li><strong>Late Cancellation:</strong> If a booking is canceled after the assigned technician has been dispatched or is within 1 hour of the arrival window, a dispatch mobilization fee (up to $10 or equivalent local currency) may be deducted.</li>
          <li><strong>Refund Claims:</strong> In the rare event of service dissatisfaction, incomplete work, or verifiable property damage, customers must lodge a dispute via the ServEasy Support Helpdesk within 48 hours of service completion. Following technician review and image inspection, eligible refunds are credited to the original payment source within 5–7 business days.</li>
        </ul>

        <h2><ion-icon name="shield-outline"></ion-icon> 7. Customer & Provider Code of Conduct</h2>
        <p>
          ServEasy enforces zero tolerance for abusive, discriminatory, or harassing behavior directed toward service providers or support representatives. We reserve the absolute right to terminate accounts, cancel active bookings, and report serious misconduct to law enforcement authorities.
        </p>

        <h2><ion-icon name="warning-outline"></ion-icon> 8. Limitation of Liability</h2>
        <p>
          To the maximum extent permitted by applicable law, ServEasy shall not be liable for indirect, incidental, punitive, or consequential damages arising from the use of or inability to use the platform services. Our aggregate liability for direct claims shall not exceed the total service fees paid by the customer for the specific booking giving rise to the claim.
        </p>

        <h2><ion-icon name="flag-outline"></ion-icon> 9. Governing Law and Dispute Resolution</h2>
        <p>
          These Terms shall be governed by and construed in accordance with the substantive laws of India. Any legal dispute, controversy, or claim arising out of or relating to these terms shall be submitted to the exclusive jurisdiction of the competent courts in Kolkata, West Bengal.
        </p>

        <div class="legal-contact-box">
          <h3 style="color: #fff; margin-bottom: 10px; font-size: 1.15rem; font-weight: 700;">
            <ion-icon name="chatbox-ellipses-outline" style="vertical-align: -2px; color: #00f2fe;"></ion-icon>
            Questions Regarding Terms?
          </h3>
          <p style="margin-bottom: 8px;">
            If you have questions, feedback, or require clarification regarding these Terms of Service, please reach out to our legal and customer operations desk:
          </p>
          <ul style="list-style-type: none; margin-left: 0;">
            <li><strong>Email:</strong> <a href="mailto:support@serveasy.in" style="color: #00f2fe;">support@serveasy.in</a></li>
            <li><strong>Support Concierge:</strong> +91 96474 14029</li>
            <li><strong>Operations Center:</strong> Salt Lake, Sector V, Greater Kolkata, West Bengal, 700091, India</li>
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
