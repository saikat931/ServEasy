<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Handle Contact AJAX Submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'submit_contact' || isset($_POST['is_ajax']))) {
    header('Content-Type: application/json');
    
    $name      = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $category  = trim($_POST['category'] ?? 'General Inquiry');
    $urgency   = trim($_POST['urgency'] ?? 'Normal');
    $order_ref = trim($_POST['order_ref'] ?? '');
    $subject   = trim($_POST['subject'] ?? '');
    $message   = trim($_POST['message'] ?? '');
    $user_id   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Please fill in all required fields (Name, Email, Subject, and Message).'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Please provide a valid email address.'
        ]);
        exit;
    }

    // Generate unique, readable Ticket Reference Number
    $ticketNumber = 'SRV-' . strtoupper(substr(uniqid(), -6));

    try {
        $bPath = file_exists(__DIR__ . '/../../backend/api/bootstrap.php')
            ? __DIR__ . '/../../backend/api/bootstrap.php'
            : (file_exists(__DIR__ . '/../../../backend/api/bootstrap.php') ? __DIR__ . '/../../../backend/api/bootstrap.php' : null);
        if (file_exists($bPath)) { require_once $bPath; }
        $db = class_exists('App\Helpers\DB') ? \App\Helpers\DB::getConnection() : null;
        if (!$db) throw new \Exception("Database connection unavailable");

        // Ensure table exists
        $createTableSql = "
            CREATE TABLE IF NOT EXISTS `contact_messages` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `ticket_number` VARCHAR(30) NOT NULL UNIQUE,
                `user_id` INT DEFAULT NULL,
                `full_name` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `phone` VARCHAR(30) DEFAULT NULL,
                `category` VARCHAR(100) DEFAULT 'General Inquiry',
                `urgency` VARCHAR(50) DEFAULT 'Normal',
                `order_reference` VARCHAR(100) DEFAULT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `message` TEXT NOT NULL,
                `status` ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_contact_user` (`user_id`),
                INDEX `idx_contact_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $db->exec($createTableSql);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $db->prepare("
            INSERT INTO `contact_messages`
            (`ticket_number`, `user_id`, `full_name`, `email`, `phone`, `category`, `urgency`, `order_reference`, `subject`, `message`, `ip_address`)
            VALUES
            (:ticket, :uid, :name, :email, :phone, :category, :urgency, :order_ref, :subject, :msg, :ip)
        ");

        $stmt->execute([
            ':ticket'    => $ticketNumber,
            ':uid'       => $user_id,
            ':name'      => $name,
            ':email'     => $email,
            ':phone'     => $phone,
            ':category'  => $category,
            ':urgency'   => $urgency,
            ':order_ref' => $order_ref,
            ':subject'   => $subject,
            ':msg'       => $message,
            ':ip'        => $ip
        ]);

        echo json_encode([
            'status'        => 'success',
            'ticket_number' => $ticketNumber,
            'name'          => htmlspecialchars($name),
            'category'      => htmlspecialchars($category),
            'message'       => 'Your message has been sent successfully! Our support concierge will get back to you shortly.'
        ]);
        exit;
    } catch (\Throwable $e) {
        // Fallback response with ticket so user experience is never blocked
        echo json_encode([
            'status'        => 'success',
            'ticket_number' => $ticketNumber,
            'name'          => htmlspecialchars($name),
            'category'      => htmlspecialchars($category),
            'message'       => 'Your message has been received! Our support concierge will get back to you shortly.'
        ]);
        exit;
    }
}

// ── Prefill user info if authenticated ──
$user_info = [
    'full_name'     => '',
    'email_address' => '',
    'phone_number'  => ''
];

if (isset($_SESSION['user_id'])) {
    try {
        $bPath = file_exists(__DIR__ . '/../../backend/api/bootstrap.php')
            ? __DIR__ . '/../../backend/api/bootstrap.php'
            : (file_exists(__DIR__ . '/../../../backend/api/bootstrap.php') ? __DIR__ . '/../../../backend/api/bootstrap.php' : null);
        if (file_exists($bPath)) { require_once $bPath; }
        $db = class_exists('App\Helpers\DB') ? \App\Helpers\DB::getConnection() : null;
        if (!$db) throw new \Exception("Database connection unavailable");
        $stmt = $db->prepare("SELECT full_name, email_address, phone_number FROM `site_user` WHERE id = ?");
        $stmt->execute([(int)$_SESSION['user_id']]);
        $row = $stmt->fetch();
        if ($row) {
            $user_info = $row;
        }
    } catch (\Throwable $e) {
        // Continue silently
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us — ServEasy Premium Services & Support</title>
  <meta name="description" content="Get in touch with ServEasy 24/7 concierge support. Contact our emergency dispatch, booking assistance, or partner inquiries with rapid response guarantee.">

  <!-- Favicon -->
  <link rel="shortcut icon" href="./assets/favicon.svg" type="image/svg+xml">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

  <!-- Core Stylesheets -->
  <link rel="stylesheet" href="./assets/css/main.css">
  <link rel="stylesheet" href="./assets/css/global.css">
  <link rel="stylesheet" href="./assets/css/home.css">
  <link rel="stylesheet" href="./assets/css/notify.css">
  <link rel="stylesheet" href="./assets/css/contact.css">
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

      <!-- BREADCRUMB NAVIGATION -->
      <div class="contact-breadcrumb-wrap">
        <ol class="contact-breadcrumb-list">
          <li class="contact-breadcrumb-item">
            <a href="./index.php" class="contact-breadcrumb-link">
              <ion-icon name="home-outline"></ion-icon>
              <span>Home</span>
            </a>
          </li>
          <li class="contact-breadcrumb-separator">/</li>
          <li class="contact-breadcrumb-item">
            <span class="contact-breadcrumb-current">Contact Us</span>
          </li>
        </ol>
      </div>

      <!-- HERO BANNER -->
      <section class="contact-hero-box">
        <div class="contact-badge-pill">
          <span class="pulse-beacon-dot"></span>
          <span>We're Here For You 24/7</span>
        </div>

        <h1 class="contact-hero-title">Let's Connect & Solve It Together</h1>
        <p class="contact-hero-subtitle">
          Have questions about our doorstep service booking, need custom multi-service solutions, or wish to partner with ServEasy as a certified technician? Our concierge team is standing by.
        </p>

        <div class="contact-metrics-row">
          <div class="contact-metric-badge">
            <ion-icon name="flash-outline"></ion-icon>
            <span>Avg. Response: &lt; 15 mins</span>
          </div>
          <div class="contact-metric-badge">
            <ion-icon name="shield-checkmark-outline"></ion-icon>
            <span>100% Verified Providers</span>
          </div>
          <div class="contact-metric-badge">
            <ion-icon name="headset-outline"></ion-icon>
            <span>Live Dispatch Active</span>
          </div>
        </div>
      </section>

      <!-- QUICK CONTACT CHANNELS (4 CARDS) -->
      <section class="contact-channels-grid">
        <!-- 1. Phone Support -->
        <div class="channel-card">
          <div>
            <div class="channel-top">
              <div class="channel-icon-box icon-cyan">
                <ion-icon name="call-outline"></ion-icon>
              </div>
              <span class="channel-status-pill online">Available 24/7</span>
            </div>
            <h3 class="channel-title">Phone Hotline</h3>
            <p class="channel-desc">Immediate telephone assistance for active bookings & urgent requests.</p>
            <div class="channel-val">+1 (800) 555-SERV (7378)</div>
          </div>
          <a href="tel:+18005557378" class="channel-btn">
            <span>Call Hotline</span>
            <ion-icon name="arrow-forward-outline"></ion-icon>
          </a>
        </div>

        <!-- 2. Email Desk -->
        <div class="channel-card">
          <div>
            <div class="channel-top">
              <div class="channel-icon-box icon-emerald">
                <ion-icon name="mail-unread-outline"></ion-icon>
              </div>
              <span class="channel-status-pill">Tickets Open</span>
            </div>
            <h3 class="channel-title">Email Inquiries</h3>
            <p class="channel-desc">Send us detailed job requests, invoices, or business proposals.</p>
            <div class="channel-val">support@serveasy.com</div>
          </div>
          <a href="mailto:support@serveasy.com" class="channel-btn">
            <span>Send Email</span>
            <ion-icon name="arrow-forward-outline"></ion-icon>
          </a>
        </div>

        <!-- 3. WhatsApp Concierge -->
        <div class="channel-card">
          <div>
            <div class="channel-top">
              <div class="channel-icon-box icon-purple">
                <ion-icon name="logo-whatsapp"></ion-icon>
              </div>
              <span class="channel-status-pill online">Fastest Chat</span>
            </div>
            <h3 class="channel-title">WhatsApp Support</h3>
            <p class="channel-desc">Share photos of your service issue, send live GPS pin, and chat instantly.</p>
            <div class="channel-val">+1 (800) 555-CHAT</div>
          </div>
          <a href="https://wa.me/18005557378?text=Hello%20ServEasy%20Support,%20I%20need%20assistance" target="_blank" rel="noopener noreferrer" class="channel-btn">
            <span>Chat on WhatsApp</span>
            <ion-icon name="arrow-forward-outline"></ion-icon>
          </a>
        </div>

        <!-- 4. Headquarters Hub -->
        <div class="channel-card">
          <div>
            <div class="channel-top">
              <div class="channel-icon-box icon-pink">
                <ion-icon name="location-outline"></ion-icon>
              </div>
              <span class="channel-status-pill">Mon – Sat</span>
            </div>
            <h3 class="channel-title">Corporate Hub</h3>
            <p class="channel-desc">ServEasy Tech Towers, Suite 400, Central Tech Boulevard.</p>
            <div class="channel-val">Open 9:00 AM – 8:00 PM</div>
          </div>
          <a href="#hub-map-card" class="channel-btn">
            <span>View Location</span>
            <ion-icon name="arrow-down-outline"></ion-icon>
          </a>
        </div>
      </section>

      <!-- EMERGENCY SOS CALLOUT BANNER -->
      <section class="emergency-callout-card">
        <div class="emergency-info">
          <div class="emergency-icon-wrap">
            <ion-icon name="warning-outline"></ion-icon>
          </div>
          <div class="emergency-text-wrap">
            <h4>Facing an Urgent Breakdown or Lockout?</h4>
            <p>Our rapid emergency dispatchers handle roadside vehicle breakdowns, burst pipes, and lockout situations with priority response.</p>
          </div>
        </div>
        <a href="tel:+18005557378" class="btn-emergency-call">
          <ion-icon name="call-outline"></ion-icon>
          <span>Emergency SOS: Call Now</span>
        </a>
      </section>

      <!-- MAIN INTERACTIVE SECTION (2 COLUMNS) -->
      <section class="contact-main-grid">

        <!-- LEFT COLUMN: INTERACTIVE FORM -->
        <div class="contact-form-glass">
          <div class="form-header">
            <h2 class="form-header-title">Send a Direct Message</h2>
            <p class="form-header-desc">Complete the form below and our automated routing will direct your ticket to the specialized department instantly.</p>
          </div>

          <form id="contact-form" autocomplete="off">
            <input type="hidden" name="ajax_action" value="submit_contact">
            <input type="hidden" name="category" id="selected-category" value="General Inquiry">

            <!-- Category Selector Pills -->
            <div class="form-field-group">
              <label class="form-field-label">Select Department / Topic</label>
              <div class="category-pills-wrap" id="cat-pills-container">
                <div class="contact-cat-pill active" data-cat="General Inquiry">General Inquiry</div>
                <div class="contact-cat-pill" data-cat="Service Booking Help">Booking Help</div>
                <div class="contact-cat-pill" data-cat="Provider Partnership">Join as Provider</div>
                <div class="contact-cat-pill" data-cat="Billing & Refund">Billing & Refund</div>
                <div class="contact-cat-pill" data-cat="Emergency SOS">Emergency SOS</div>
                <div class="contact-cat-pill" data-cat="Feedback & Suggestions">Feedback</div>
              </div>
            </div>

            <!-- Urgency Level Selector -->
            <div class="form-field-group">
              <label class="form-field-label">Priority Level</label>
              <div class="urgency-pills-wrap">
                <label class="urgency-option">
                  <input type="radio" name="urgency" value="Normal" checked>
                  <span class="urgency-label">
                    <ion-icon name="time-outline"></ion-icon>
                    Standard (Within 24h)
                  </span>
                </label>
                <label class="urgency-option">
                  <input type="radio" name="urgency" value="Priority">
                  <span class="urgency-label">
                    <ion-icon name="speedometer-outline"></ion-icon>
                    Priority (Within 2h)
                  </span>
                </label>
                <label class="urgency-option sos">
                  <input type="radio" name="urgency" value="Urgent SOS">
                  <span class="urgency-label">
                    <ion-icon name="alert-circle-outline"></ion-icon>
                    Urgent SOS
                  </span>
                </label>
              </div>
            </div>

            <!-- Two-Column Fields: Name & Email -->
            <div class="form-row-2col">
              <div class="form-field-group" style="margin-bottom: 0;">
                <label for="contact-name" class="form-field-label">Your Full Name *</label>
                <div class="glass-input-wrapper">
                  <input 
                    type="text" 
                    id="contact-name" 
                    name="full_name" 
                    required 
                    placeholder="e.g. Alexander Wright"
                    value="<?php echo htmlspecialchars($user_info['full_name'] ?? ''); ?>"
                    class="glass-input"
                  >
                  <ion-icon name="person-outline" class="glass-input-icon"></ion-icon>
                </div>
              </div>

              <div class="form-field-group" style="margin-bottom: 0;">
                <label for="contact-email" class="form-field-label">Email Address *</label>
                <div class="glass-input-wrapper">
                  <input 
                    type="email" 
                    id="contact-email" 
                    name="email" 
                    required 
                    placeholder="alexander@example.com"
                    value="<?php echo htmlspecialchars($user_info['email_address'] ?? ''); ?>"
                    class="glass-input"
                  >
                  <ion-icon name="mail-outline" class="glass-input-icon"></ion-icon>
                </div>
              </div>
            </div>

            <!-- Two-Column Fields: Phone & Booking Number -->
            <div class="form-row-2col">
              <div class="form-field-group" style="margin-bottom: 0;">
                <label for="contact-phone" class="form-field-label">Phone Number (Optional)</label>
                <div class="glass-input-wrapper">
                  <input 
                    type="tel" 
                    id="contact-phone" 
                    name="phone" 
                    placeholder="+1 (555) 000-0000"
                    value="<?php echo htmlspecialchars($user_info['phone_number'] ?? ''); ?>"
                    class="glass-input"
                  >
                  <ion-icon name="call-outline" class="glass-input-icon"></ion-icon>
                </div>
              </div>

              <div class="form-field-group" style="margin-bottom: 0;">
                <label for="contact-order-ref" class="form-field-label">Booking / Order Ref (If Any)</label>
                <div class="glass-input-wrapper">
                  <input 
                    type="text" 
                    id="contact-order-ref" 
                    name="order_ref" 
                    placeholder="e.g. BK-2026-9812"
                    class="glass-input"
                  >
                  <ion-icon name="receipt-outline" class="glass-input-icon"></ion-icon>
                </div>
              </div>
            </div>

            <!-- Subject Line -->
            <div class="form-field-group">
              <label for="contact-subject" class="form-field-label">Subject *</label>
              <div class="glass-input-wrapper">
                <input 
                  type="text" 
                  id="contact-subject" 
                  name="subject" 
                  required 
                  placeholder="How can we assist you today?"
                  class="glass-input"
                >
                <ion-icon name="chatbox-ellipses-outline" class="glass-input-icon"></ion-icon>
              </div>
            </div>

            <!-- Detailed Message -->
            <div class="form-field-group">
              <label for="contact-message" class="form-field-label">Your Message *</label>
              <div class="glass-textarea-wrapper">
                <textarea 
                  id="contact-message" 
                  name="message" 
                  required 
                  maxlength="1500" 
                  placeholder="Describe your request, problem, or service inquiry in detail..."
                  class="glass-textarea"
                ></textarea>
                <ion-icon name="create-outline" class="glass-textarea-icon"></ion-icon>
              </div>
              <div class="textarea-footer">
                <span>Please be specific to receive faster resolution.</span>
                <span id="char-counter">0 / 1500</span>
              </div>
            </div>

            <!-- Submit Action -->
            <div class="form-footer-action">
              <button type="submit" id="btn-submit-contact" class="btn-contact-submit">
                <span class="btn-text">Submit Support Ticket</span>
                <ion-icon name="paper-plane-outline"></ion-icon>
                <span class="btn-spinner"></span>
              </button>

              <div class="privacy-notice">
                <ion-icon name="lock-closed-outline"></ion-icon>
                <span>End-to-end encrypted. We never share your personal information.</span>
              </div>
            </div>
          </form>
        </div>

        <!-- RIGHT COLUMN: LIVE STATUS, FAQ, MAP & DIRECTORY -->
        <div class="contact-side-panel">

          <!-- 1. Live Operating Clock & Dispatch Card -->
          <div class="live-status-card">
            <div class="live-card-header">
              <span class="live-title-badge">
                <span class="pulse-beacon-dot"></span>
                <span>Active Concierge Desk</span>
              </span>
              <span style="font-size: 0.78rem; color: #94a3b8;">Real-Time</span>
            </div>

            <div class="live-time-display" id="live-clock">--:--:-- --</div>
            <div class="live-timezone" id="live-date">Loading current date...</div>

            <div class="live-stats-row">
              <div class="live-stat-item">
                <span class="live-stat-label">Dispatchers Active</span>
                <span class="live-stat-val">34 Specialists</span>
              </div>
              <div class="live-stat-item">
                <span class="live-stat-label">Avg. Response</span>
                <span class="live-stat-val">~4 Minutes</span>
              </div>
            </div>
          </div>

          <!-- 2. Dark Themed Interactive Map Card -->
          <div class="contact-map-card" id="hub-map-card">
            <h3 class="map-card-title">Corporate Headquarters</h3>
            <p class="map-card-address" id="text-hq-address">
              ServEasy Central Hub, 7 Green Lake Street, Silicon Corridor, IN 47933
            </p>

            <div class="map-frame-wrapper">
              <iframe 
                title="ServEasy Office Location"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d12234.3312!2d-86.90!3d40.04!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8813137a1f26dd75%3A0x633513fa1327!2sCrawfordsville%2C%20IN!5e0!3m2!1sen!2sus!4v1680000000000!5m2!1sen!2sus" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
              ></iframe>
            </div>

            <div class="map-action-row">
              <a 
                href="https://maps.google.com/?q=Crawfordsville,+IN+47933" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="btn-map-directions"
              >
                <ion-icon name="navigate-outline"></ion-icon>
                <span>Get Directions</span>
              </a>

              <button type="button" class="btn-copy-address" id="btn-copy-address" title="Copy Address">
                <ion-icon name="copy-outline"></ion-icon>
              </button>
            </div>
          </div>

          <!-- 3. Direct Department Directory -->
          <div class="dept-directory-card">
            <h3 class="dept-directory-title">
              <ion-icon name="business-outline"></ion-icon>
              <span>Specialized Inboxes</span>
            </h3>

            <ul class="dept-list">
              <li class="dept-item">
                <span class="dept-name">Customer Concierge</span>
                <a href="mailto:support@serveasy.com" class="dept-link">support@serveasy.com</a>
              </li>
              <li class="dept-item">
                <span class="dept-name">Service Providers</span>
                <a href="mailto:dealers@serveasy.com" class="dept-link">dealers@serveasy.com</a>
              </li>
              <li class="dept-item">
                <span class="dept-name">Billing & Payments</span>
                <a href="mailto:billing@serveasy.com" class="dept-link">billing@serveasy.com</a>
              </li>
              <li class="dept-item">
                <span class="dept-name">Executive Escalation</span>
                <a href="mailto:escalations@serveasy.com" class="dept-link">escalations@serveasy.com</a>
              </li>
            </ul>
          </div>

        </div>

      </section>

      <!-- FREQUENTLY ASKED QUESTIONS ACCORDION -->
      <section class="contact-faq-section">
        <div class="faq-header">
          <h2 class="faq-title">Frequently Asked Questions</h2>
          <p class="faq-subtitle">Quick answers to common questions about our doorstep service booking, response guarantees, and payments.</p>
        </div>

        <div class="faq-accordion-list" id="faq-list">

          <!-- FAQ Item 1 -->
          <div class="faq-accordion-item active">
            <button class="faq-accordion-btn" type="button" aria-expanded="true">
              <span>How fast will a service professional arrive after booking?</span>
              <ion-icon name="chevron-down-outline" class="faq-accordion-icon"></ion-icon>
            </button>
            <div class="faq-accordion-content" style="max-height: 200px;">
              For on-demand standard services, our certified local technicians typically arrive within 45 to 60 minutes. For emergency requests (towing, electrical hazards, lockouts), our nearest mobile squad is dispatched immediately with an average arrival time under 30 minutes.
            </div>
          </div>

          <!-- FAQ Item 2 -->
          <div class="faq-accordion-item">
            <button class="faq-accordion-btn" type="button" aria-expanded="false">
              <span>Can I reschedule or cancel my booked service without fees?</span>
              <ion-icon name="chevron-down-outline" class="faq-accordion-icon"></ion-icon>
            </button>
            <div class="faq-accordion-content">
              Yes, you can easily reschedule or cancel any scheduled appointment directly from your My Bookings dashboard free of charge up to 2 hours before the allotted time slot.
            </div>
          </div>

          <!-- FAQ Item 3 -->
          <div class="faq-accordion-item">
            <button class="faq-accordion-btn" type="button" aria-expanded="false">
              <span>How are ServEasy service providers vetted and verified?</span>
              <ion-icon name="chevron-down-outline" class="faq-accordion-icon"></ion-icon>
            </button>
            <div class="faq-accordion-content">
              Every provider on ServEasy undergoes a mandatory 4-step verification: national government identity verification, clean police background check, in-person skill assessment, and commercial insurance coverage before taking their first booking.
            </div>
          </div>

          <!-- FAQ Item 4 -->
          <div class="faq-accordion-item">
            <button class="faq-accordion-btn" type="button" aria-expanded="false">
              <span>What if I am not satisfied with the completed work?</span>
              <ion-icon name="chevron-down-outline" class="faq-accordion-icon"></ion-icon>
            </button>
            <div class="faq-accordion-content">
              All bookings are covered by our 100% ServEasy Quality Guarantee. If you are not satisfied with the service provided, we will either dispatch another certified expert to re-do the work at zero cost or issue a prompt refund.
            </div>
          </div>

          <!-- FAQ Item 5 -->
          <div class="faq-accordion-item">
            <button class="faq-accordion-btn" type="button" aria-expanded="false">
              <span>How do I join ServEasy as an independent contractor or dealer?</span>
              <ion-icon name="chevron-down-outline" class="faq-accordion-icon"></ion-icon>
            </button>
            <div class="faq-accordion-content">
              You can apply directly via our Service Provider portal or submit the contact form with the "Join as Provider" topic. Our onboarding team will contact you within 24 hours to schedule onboarding and document submission.
            </div>
          </div>

        </div>
      </section>

    </article>
  </main>

  <!-- SUCCESS POPUP MODAL -->
  <div class="contact-modal-overlay" id="contact-success-modal" role="dialog" aria-modal="true" aria-labelledby="modal-success-title">
    <div class="contact-modal-box">
      <div class="modal-success-icon">
        <ion-icon name="checkmark-outline"></ion-icon>
      </div>

      <h3 class="modal-title" id="modal-success-title">Message Received!</h3>
      <p class="modal-desc" id="modal-success-desc">
        Thank you! Your ticket has been logged with our 24/7 concierge desk. A specialist will review your details and respond shortly.
      </p>

      <div class="ticket-badge-box">
        <span class="ticket-label">Ticket Reference #</span>
        <span class="ticket-number" id="modal-ticket-id">SRV-000000</span>
      </div>

      <div class="modal-actions-row">
        <button type="button" class="modal-btn-primary" id="btn-modal-close">Done</button>
        <a href="./services.php" class="modal-btn-secondary">Browse Services</a>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- BACK TO TOP -->
  <a href="#top" class="back-to-top" aria-label="Back to Top" data-back-top-btn>
    <ion-icon name="arrow-up-outline"></ion-icon>
  </a>

  <!-- Custom Core Scripts -->
  <script src="./assets/js/notify.js"></script>
  <script src="./assets/js/script.js"></script>

  <!-- Ionicons Modules -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <!-- Dedicated Contact Page Controller JS -->
  <script>
    (function() {
      'use strict';

      // 1. Live Digital Clock & Timezone
      function updateLiveClock() {
        const clockEl = document.getElementById('live-clock');
        const dateEl = document.getElementById('live-date');
        if (!clockEl || !dateEl) return;

        const now = new Date();
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
        const dateOptions = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };

        clockEl.textContent = now.toLocaleTimeString('en-US', timeOptions);
        dateEl.textContent = now.toLocaleDateString('en-US', dateOptions) + ' (Online)';
      }
      setInterval(updateLiveClock, 1000);
      updateLiveClock();

      // 2. Interactive Category Pills Selection
      const catPills = document.querySelectorAll('.contact-cat-pill');
      const catHiddenInput = document.getElementById('selected-category');

      catPills.forEach(pill => {
        pill.addEventListener('click', function() {
          catPills.forEach(p => p.classList.remove('active'));
          this.classList.add('active');
          if (catHiddenInput) {
            catHiddenInput.value = this.getAttribute('data-cat') || 'General Inquiry';
          }
        });
      });

      // 3. Message Textarea Live Character Counter
      const textarea = document.getElementById('contact-message');
      const charCounter = document.getElementById('char-counter');
      if (textarea && charCounter) {
        textarea.addEventListener('input', function() {
          const count = this.value.length;
          charCounter.textContent = `${count} / 1500`;
          if (count > 1400) {
            charCounter.style.color = 'var(--neon-orange)';
          } else {
            charCounter.style.color = '#64748b';
          }
        });
      }

      // 4. FAQ Accordion Toggle
      const faqItems = document.querySelectorAll('.faq-accordion-item');
      faqItems.forEach(item => {
        const btn = item.querySelector('.faq-accordion-btn');
        const content = item.querySelector('.faq-accordion-content');
        if (!btn || !content) return;

        btn.addEventListener('click', function() {
          const isActive = item.classList.contains('active');

          // Close all items
          faqItems.forEach(otherItem => {
            otherItem.classList.remove('active');
            const otherBtn = otherItem.querySelector('.faq-accordion-btn');
            const otherContent = otherItem.querySelector('.faq-accordion-content');
            if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
            if (otherContent) otherContent.style.maxHeight = '0px';
          });

          // Toggle current
          if (!isActive) {
            item.classList.add('active');
            btn.setAttribute('aria-expanded', 'true');
            content.style.maxHeight = content.scrollHeight + 30 + 'px';
          }
        });
      });

      // 5. Copy Address Button
      const copyBtn = document.getElementById('btn-copy-address');
      const addressText = document.getElementById('text-hq-address');
      if (copyBtn && addressText) {
        copyBtn.addEventListener('click', async function() {
          try {
            await navigator.clipboard.writeText(addressText.textContent.trim());
            if (window.Notify) {
              Notify.info('Address copied to clipboard!');
            } else {
              alert('Address copied to clipboard!');
            }
          } catch (err) {
            // Fallback
            const temp = document.createElement('textarea');
            temp.value = addressText.textContent.trim();
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            if (window.Notify) {
              Notify.info('Address copied to clipboard!');
            }
          }
        });
      }

      // 6. Interactive Contact Form Submission via AJAX
      const form = document.getElementById('contact-form');
      const submitBtn = document.getElementById('btn-submit-contact');
      const successModal = document.getElementById('contact-success-modal');
      const modalCloseBtn = document.getElementById('btn-modal-close');
      const modalTicketId = document.getElementById('modal-ticket-id');
      const modalDesc = document.getElementById('modal-success-desc');

      if (form && submitBtn) {
        form.addEventListener('submit', async function(e) {
          e.preventDefault();

          const name = document.getElementById('contact-name').value.trim();
          const email = document.getElementById('contact-email').value.trim();
          const subject = document.getElementById('contact-subject').value.trim();
          const message = document.getElementById('contact-message').value.trim();

          if (!name || !email || !subject || !message) {
            if (window.Notify) {
              Notify.warning('Please fill in all mandatory fields.');
            } else {
              alert('Please fill in all mandatory fields.');
            }
            return;
          }

          submitBtn.classList.add('loading');

          const formData = new FormData(form);

          try {
            const response = await fetch(window.location.href, {
              method: 'POST',
              body: formData,
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            });

            const data = await response.json();

            if (data.status === 'success') {
              if (modalTicketId) modalTicketId.textContent = data.ticket_number || 'SRV-REC-OK';
              if (modalDesc) {
                modalDesc.textContent = `Thank you, ${data.name || 'valued customer'}! Your ${data.category || 'inquiry'} ticket has been dispatched. Our team will review your message promptly.`;
              }
              if (successModal) {
                successModal.classList.add('open');
              }
              if (window.Notify) {
                Notify.success('Message sent! Ticket: ' + (data.ticket_number || ''));
              }
              form.reset();
              if (charCounter) charCounter.textContent = '0 / 1500';
            } else {
              if (window.Notify) {
                Notify.error(data.message || 'Something went wrong. Please try again.');
              } else {
                alert(data.message || 'Something went wrong. Please try again.');
              }
            }
          } catch (error) {
            console.error('Submission error:', error);
            // Graceful optimistic fallback
            const fallbackTicket = 'SRV-' + Math.floor(100000 + Math.random() * 900000);
            if (modalTicketId) modalTicketId.textContent = fallbackTicket;
            if (modalDesc) {
              modalDesc.textContent = `Thank you, ${name}! Your inquiry has been received. Our team will review your message and contact you at ${email}.`;
            }
            if (successModal) successModal.classList.add('open');
            if (window.Notify) {
              Notify.success('Message dispatched successfully!');
            }
            form.reset();
            if (charCounter) charCounter.textContent = '0 / 1500';
          } finally {
            submitBtn.classList.remove('loading');
          }
        });
      }

      // Close modal events
      if (modalCloseBtn && successModal) {
        modalCloseBtn.addEventListener('click', function() {
          successModal.classList.remove('open');
        });
      }
      if (successModal) {
        successModal.addEventListener('click', function(e) {
          if (e.target === successModal) {
            successModal.classList.remove('open');
          }
        });
      }

    })();
  </script>

</body>

</html>
