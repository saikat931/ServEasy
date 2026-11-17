<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    $target_url = $_SERVER['REQUEST_URI'] ?? './orders.php';
    header("Location: ../auth/auth.php?redirect=" . urlencode($target_url));
    exit;
}

$bootstrapPath = file_exists(__DIR__ . '/../../backend/api/bootstrap.php')
    ? __DIR__ . '/../../backend/api/bootstrap.php'
    : (file_exists(__DIR__ . '/../../../backend/api/bootstrap.php') ? __DIR__ . '/../../../backend/api/bootstrap.php' : null);
if ($bootstrapPath && file_exists($bootstrapPath)) {
    require_once $bootstrapPath;
}
header('Content-Type: text/html; charset=utf-8');
$bookings = [];
if (class_exists('App\Repositories\BookingRepository')) {
    try {
        $bookingRepo = new \App\Repositories\BookingRepository();
        $bookings = $bookingRepo->getBookingsByCustomer((int)$_SESSION['user_id']);
    } catch (\Throwable $e) {
        error_log("Failed to load customer bookings locally: " . $e->getMessage());
    }
} else {
    // Multi-Domain Cloud API Fallback
    $bridgePath = file_exists(__DIR__ . '/../common/ApiBridge.php') ? __DIR__ . '/../common/ApiBridge.php' : __DIR__ . '/../../common/ApiBridge.php';
    if (file_exists($bridgePath)) {
        require_once $bridgePath;
        $apiRes = \Frontend\ApiBridge::callApi('bookings.php?customer_id=' . (int)$_SESSION['user_id']);
        if (!empty($apiRes['data']) && is_array($apiRes['data'])) {
            $bookings = $apiRes['data'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ServEasy - My Bookings</title>

  <!-- Favicon -->
  <link rel="shortcut icon" href="./assets/favicon.svg" type="image/svg+xml">

  <!-- Font & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

  <!-- Core Stylesheet imports -->
  <link rel="stylesheet" href="./assets/css/main.css">
  <link rel="stylesheet" href="./assets/css/global.css">
  <link rel="stylesheet" href="./assets/css/home.css">

  <style>
    .orders-container {
      max-width: 1000px;
      margin: 40px auto;
      padding: 0 15px;
    }

    .orders-header {
      margin-bottom: 30px;
    }

    .orders-title {
      font-size: 2.2rem;
      color: var(--white);
      margin-bottom: 10px;
      font-family: 'Playfair Display', serif;
    }

    .orders-subtitle {
      color: #94a3b8;
      font-size: 1rem;
    }

    .orders-list {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .order-card-glass {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 24px;
      color: var(--white);
      display: flex;
      flex-direction: column;
      gap: 16px;
      transition: all 0.3s ease;
    }

    .order-card-glass:hover {
      transform: translateY(-2px);
      border-color: rgba(255, 255, 255, 0.2);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    }

    .order-row-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding-bottom: 16px;
    }

    .order-ref {
      font-weight: 700;
      color: #7c3aed;
      font-size: 1.1rem;
    }

    .order-status-badge {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-pending {
      background: rgba(245, 158, 11, 0.2);
      color: #f59e0b;
      border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .status-accepted {
      background: rgba(59, 130, 246, 0.2);
      color: #3b82f6;
      border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .status-rejected {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .status-in_progress {
      background: rgba(139, 92, 246, 0.2);
      color: #8b5cf6;
      border: 1px solid rgba(139, 92, 246, 0.3);
    }

    .status-completed {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .status-cancelled {
      background: rgba(107, 114, 128, 0.2);
      color: #6b7280;
      border: 1px solid rgba(107, 114, 128, 0.3);
    }

    .order-body {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 20px;
    }

    @media (max-width: 768px) {
      .order-body {
        grid-template-columns: 1fr;
      }
    }

    .order-service-info {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .order-service-name {
      font-size: 1.4rem;
      font-weight: 700;
    }

    .order-provider-name {
      font-size: 0.95rem;
      color: #94a3b8;
    }

    .order-detail-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.95rem;
      color: #cbd5e1;
    }

    .order-detail-item ion-icon {
      color: #7c3aed;
      font-size: 1.2rem;
    }

    .order-price-box {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-end;
      border-left: 1px solid rgba(255, 255, 255, 0.1);
      padding-left: 20px;
    }

    @media (max-width: 768px) {
      .order-price-box {
        border-left: none;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-left: 0;
        padding-top: 16px;
        align-items: flex-start;
      }
    }

    .order-total-label {
      font-size: 0.85rem;
      color: #94a3b8;
    }

    .order-total-value {
      font-size: 1.8rem;
      font-weight: 800;
      color: #10b981;
    }

    .payment-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.4px;
      text-transform: uppercase;
    }

    .payment-paid {
      background: rgba(16, 185, 129, 0.15);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .payment-cod {
      background: rgba(59, 130, 246, 0.15);
      color: #60a5fa;
      border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .payment-unpaid, .payment-pending {
      background: rgba(245, 158, 11, 0.15);
      color: #f59e0b;
      border: 1px solid rgba(245, 158, 11, 0.3);
    }

    /* Actions Bar */
    .order-actions-bar {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
      padding-top: 14px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .btn-action {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      border-radius: 10px;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
      text-decoration: none;
      outline: none;
    }

    .btn-action ion-icon {
      font-size: 1.05rem;
    }

    .btn-quick-view {
      background: rgba(59, 130, 246, 0.12);
      border: 1px solid #3b82f6;
      color: #60a5fa;
    }
    .btn-quick-view:hover {
      background: rgba(59, 130, 246, 0.25);
      transform: translateY(-1px);
    }

    .btn-edit-booking {
      background: rgba(168, 85, 247, 0.12);
      border: 1px solid #a855f7;
      color: #c084fc;
    }
    .btn-edit-booking:hover {
      background: rgba(168, 85, 247, 0.25);
      transform: translateY(-1px);
    }

    .btn-cancel-booking {
      background: rgba(239, 68, 68, 0.12);
      border: 1px solid #ef4444;
      color: #f87171;
    }
    .btn-cancel-booking:hover {
      background: rgba(239, 68, 68, 0.25);
      transform: translateY(-1px);
    }

    .btn-track {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid #10b981;
      color: #34d399;
    }
    .btn-track:hover {
      background: rgba(16, 185, 129, 0.25);
      transform: translateY(-1px);
    }

    /* Modal System */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      padding: 20px;
      opacity: 0;
      transition: opacity 0.25s ease;
    }

    .modal-backdrop.active {
      display: flex;
      opacity: 1;
    }

    .modal-glass-card {
      background: #111827;
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 20px;
      width: 100%;
      max-width: 620px;
      max-height: 88vh;
      overflow-y: auto;
      padding: 26px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
      position: relative;
      color: #f8fafc;
      animation: modalSlideIn 0.25s ease-out;
    }

    @keyframes modalSlideIn {
      from { transform: scale(0.95) translateY(10px); opacity: 0; }
      to { transform: scale(1) translateY(0); opacity: 1; }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding-bottom: 14px;
    }

    .modal-title {
      font-size: 1.35rem;
      font-weight: 800;
      color: #fff;
    }

    .modal-subtitle {
      font-size: 0.85rem;
      color: #94a3b8;
      margin-top: 4px;
    }

    .modal-close-btn {
      background: rgba(255, 255, 255, 0.08);
      border: none;
      color: #cbd5e1;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      cursor: pointer;
      transition: all 0.2s;
    }

    .modal-close-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      color: #fff;
    }

    .modal-spec-card {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 12px;
    }

    .modal-spec-title {
      font-size: 0.75rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #94a3b8;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .modal-spec-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 6px;
      font-size: 0.9rem;
    }

    .modal-spec-label {
      color: #94a3b8;
    }

    .modal-spec-value {
      font-weight: 600;
      color: #f1f5f9;
      text-align: right;
    }

    .notice-box-warning {
      background: rgba(245, 158, 11, 0.12);
      border: 1px solid rgba(245, 158, 11, 0.35);
      border-radius: 10px;
      padding: 12px 14px;
      color: #f59e0b;
      font-size: 0.85rem;
      line-height: 1.45;
      margin-bottom: 16px;
      display: flex;
      gap: 10px;
      align-items: flex-start;
    }

    .reason-pill-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin: 12px 0 16px 0;
    }

    .reason-pill {
      padding: 8px 14px;
      border-radius: 20px;
      border: 1px solid rgba(255, 255, 255, 0.15);
      background: rgba(255, 255, 255, 0.04);
      color: #cbd5e1;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.2s;
      user-select: none;
    }

    .reason-pill.active {
      background: rgba(239, 68, 68, 0.2);
      border-color: #ef4444;
      color: #f87171;
      font-weight: 700;
    }

    .modal-form-group {
      margin-bottom: 14px;
    }

    .modal-form-label {
      display: block;
      font-size: 0.8rem;
      font-weight: 700;
      color: #cbd5e1;
      margin-bottom: 6px;
    }

    .modal-input, .modal-textarea {
      width: 100%;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 10px;
      padding: 10px 14px;
      color: #fff;
      font-size: 0.9rem;
      outline: none;
      box-sizing: border-box;
      transition: border-color 0.2s;
    }

    .modal-input:focus, .modal-textarea:focus {
      border-color: #7c3aed;
    }

    .modal-textarea {
      resize: vertical;
      min-height: 70px;
    }

    /* Date & Time Picker Wrappers */
    .picker-input-wrap {
      position: relative;
      display: flex;
      align-items: center;
      width: 100%;
    }

    .picker-input-wrap .modal-input {
      padding-right: 44px;
      cursor: pointer;
    }

    .input-picker-icon-btn {
      position: absolute;
      right: 8px;
      background: rgba(124, 58, 237, 0.15);
      border: 1px solid rgba(124, 58, 237, 0.3);
      border-radius: 8px;
      color: #c084fc;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      font-size: 1.15rem;
    }

    .input-picker-icon-btn:hover {
      background: rgba(124, 58, 237, 0.3);
      color: #fff;
      transform: scale(1.05);
    }

    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="time"]::-webkit-calendar-picker-indicator {
      cursor: pointer;
      opacity: 0.85;
      filter: invert(0.85);
      font-size: 1.15rem;
      padding: 4px;
    }

    .quick-slots-row {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 8px;
    }

    .quick-slot-pill {
      padding: 4px 10px;
      border-radius: 14px;
      font-size: 0.75rem;
      font-weight: 600;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: #cbd5e1;
      cursor: pointer;
      transition: all 0.15s ease;
      user-select: none;
    }

    .quick-slot-pill:hover {
      background: rgba(16, 185, 129, 0.2);
      border-color: #10b981;
      color: #34d399;
    }

    .modal-footer-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding-top: 16px;
    }

    .btn-modal-cancel {
      padding: 9px 18px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.15);
      background: rgba(255, 255, 255, 0.05);
      color: #cbd5e1;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.85rem;
      transition: all 0.2s;
    }
    .btn-modal-cancel:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
    }

    .btn-modal-confirm-danger {
      padding: 9px 20px;
      border-radius: 10px;
      border: none;
      background: #ef4444;
      color: #fff;
      font-weight: 700;
      cursor: pointer;
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .btn-modal-confirm-danger:hover {
      background: #dc2626;
    }

    .btn-modal-confirm-primary {
      padding: 9px 20px;
      border-radius: 10px;
      border: none;
      background: #7c3aed;
      color: #fff;
      font-weight: 700;
      cursor: pointer;
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .btn-modal-confirm-primary:hover {
      background: #6d28d9;
    }

    .empty-orders {
      text-align: center;
      padding: 60px 20px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      color: #cbd5e1;
    }

    .empty-icon {
      font-size: 4rem;
      color: #7c3aed;
      margin-bottom: 20px;
    }

    .btn-browse {
      display: inline-block;
      margin-top: 20px;
      padding: 12px 30px;
      background: #7c3aed;
      color: #fff;
      font-weight: 700;
      border-radius: 30px;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .btn-browse:hover {
      background: #6d28d9;
      transform: translateY(-2px);
    }
  </style>
</head>

<body id="top">

  <!-- Floating glowing background circles for 3D depth -->
  <div class="glow-sphere sphere-1"></div>
  <div class="glow-sphere sphere-2"></div>
  <div class="glow-sphere sphere-3"></div>

  <!-- NAVIGATION HEADER -->
  <?php include __DIR__ . '/layout/header.php'; ?>

  <!-- SIDEBAR PANELS (Cart, Wishlist, Profile) -->
  <?php include __DIR__ . '/constants/aside.php'; ?>

  <main>
    <div class="orders-container">
      <div class="orders-header">
        <h2 class="orders-title">My Bookings</h2>
        <p class="orders-subtitle">Track the status, schedule, and payout history of your requested services.</p>
      </div>

      <?php if (empty($bookings)): ?>
        <div class="empty-orders">
          <div class="empty-icon"><ion-icon name="calendar-outline"></ion-icon></div>
          <h3>No Bookings Found</h3>
          <p>You haven't requested any services yet. Browse our service catalog to book trusted local providers.</p>
          <a href="./services.php" class="btn-browse">Explore Services</a>
        </div>
      <?php else: ?>
        <div class="orders-list">
          <?php foreach ($bookings as $b): ?>
            <?php 
              $status = strtolower($b['status'] ?? 'pending');
              $statusClass = 'status-' . $status;
              $formattedDate = date('F j, Y', strtotime($b['schedule_date']));
              $formattedTime = date('g:i A', strtotime($b['schedule_time']));
              $formattedCreated = date('M j, Y g:i A', strtotime($b['created_at']));
              $canCancel = in_array($status, ['pending', 'accepted', 'confirmed']);
              $isCompleted = ($status === 'completed');
              $jsonBooking = htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="order-card-glass" id="booking-card-<?php echo $b['id']; ?>">
              <div class="order-row-top">
                <div>
                  <span class="order-ref"><?php echo htmlspecialchars($b['booking_number']); ?></span>
                  <span style="font-size: 0.85rem; color: #94a3b8; margin-left: 10px;">Requested: <?php echo $formattedCreated; ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <?php 
                    $isCod = (strtoupper($b['payment_status'] ?? '') === 'COD' || strtoupper($b['payment_method'] ?? '') === 'COD');
                    $displayPayStatus = $isCod ? 'COD' : ($b['payment_status'] ?? 'PENDING');
                  ?>
                  <span class="payment-badge payment-<?php echo strtolower($displayPayStatus); ?>">
                    <?php echo htmlspecialchars($displayPayStatus); ?>
                  </span>
                  <span class="order-status-badge <?php echo $statusClass; ?>">
                    <?php echo str_replace('_', ' ', htmlspecialchars($b['status'])); ?>
                  </span>
                </div>
              </div>

              <!-- Concise Card Body (Full details available via Quick View) -->
              <div class="order-body">
                <div class="order-service-info">
                  <h3 class="order-service-name">
                    <?php echo htmlspecialchars($b['service_name']); ?>
                    <?php if (!empty($b['package_name'])): ?>
                      <span style="font-size: 0.95rem; font-weight: normal; color: #94a3b8;">(<?php echo htmlspecialchars($b['package_name']); ?>)</span>
                    <?php endif; ?>
                  </h3>
                  <p class="order-provider-name">Provider: <strong><?php echo htmlspecialchars($b['provider_name']); ?></strong></p>
                  
                  <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;">
                    <div class="order-detail-item">
                      <ion-icon name="calendar-clear-outline"></ion-icon>
                      <span>Schedule: <strong><?php echo $formattedDate; ?> at <?php echo $formattedTime; ?></strong></span>
                    </div>
                    <?php if (!empty($b['payment_method'])): ?>
                      <div class="order-detail-item">
                        <ion-icon name="wallet-outline"></ion-icon>
                        <span>Payment Mode: <strong><?php echo htmlspecialchars($b['payment_method']); ?></strong></span>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="order-price-box">
                  <span class="order-total-label">Total Amount</span>
                  <span class="order-total-value">$<?php echo number_format($b['total_amount'], 2); ?></span>
                </div>
              </div>

              <!-- Action Buttons: Quick View, Edit, Cancel -->
              <div class="order-actions-bar">
                <button type="button" class="btn-action btn-quick-view" data-booking='<?php echo $jsonBooking; ?>'>
                  <ion-icon name="eye-outline"></ion-icon>
                  Quick View
                </button>

                <?php if ($canCancel): ?>
                  <button type="button" class="btn-action btn-edit-booking" data-booking='<?php echo $jsonBooking; ?>'>
                    <ion-icon name="pencil-outline"></ion-icon>
                    Edit Booking
                  </button>
                  <button type="button" class="btn-action btn-cancel-booking" data-id="<?php echo $b['id']; ?>" data-ref="<?php echo htmlspecialchars($b['booking_number']); ?>" data-service="<?php echo htmlspecialchars($b['service_name']); ?>">
                    <ion-icon name="close-circle-outline"></ion-icon>
                    Cancel Booking
                  </button>
                <?php elseif ($isCompleted): ?>
                  <?php if (strtoupper($b['payment_status'] ?? '') !== 'PAID'): ?>
                    <button type="button" class="btn-action btn-pay-online" data-booking='<?php echo $jsonBooking; ?>' style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: 1px solid #10b981; font-weight: 600;">
                      <ion-icon name="card-outline"></ion-icon>
                      Pay Online (₹<?php echo number_format($b['total_amount'], 2); ?>)
                    </button>
                  <?php endif; ?>
                  <?php if (!empty($b['vehicle_type'])): ?>
                    <a href="./services/vehicle-car-&-bike-wash.php?booking_id=<?php echo $b['id']; ?>" class="btn-action btn-track">
                      <ion-icon name="star-outline"></ion-icon>
                      Rate & Review
                    </a>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- ══════════════════════════════════════════════════════════════════════════
       QUICK VIEW MODAL
       ══════════════════════════════════════════════════════════════════════════ -->
  <div class="modal-backdrop" id="quickViewModal">
    <div class="modal-glass-card">
      <div class="modal-header">
        <div>
          <h3 class="modal-title" id="qv-service-title">Service Details</h3>
          <p class="modal-subtitle" id="qv-ref-subtitle">Ref: #00000000</p>
        </div>
        <button type="button" class="modal-close-btn" data-close-modal="#quickViewModal">&times;</button>
      </div>

      <div id="qv-modal-content">
        <!-- Status & Pricing Spec Card -->
        <div class="modal-spec-card">
          <div class="modal-spec-title"><ion-icon name="receipt-outline"></ion-icon> Overview & Payout</div>
          <div class="modal-spec-row">
            <span class="modal-spec-label">Service Package:</span>
            <span class="modal-spec-value" id="qv-package-name">-</span>
          </div>
          <div class="modal-spec-row">
            <span class="modal-spec-label">Assigned Provider:</span>
            <span class="modal-spec-value" id="qv-provider-name">-</span>
          </div>
          <div class="modal-spec-row">
            <span class="modal-spec-label">Booking Status:</span>
            <span class="modal-spec-value" id="qv-status-badge">-</span>
          </div>
          <div class="modal-spec-row">
            <span class="modal-spec-label">Total Amount:</span>
            <span class="modal-spec-value" style="color: #10b981; font-weight: 800;" id="qv-total-amount">$0.00</span>
          </div>
          <div class="modal-spec-row">
            <span class="modal-spec-label">Payment Mode:</span>
            <span class="modal-spec-value" id="qv-payment-info">-</span>
          </div>
        </div>

        <!-- Schedule & Location Spec Card -->
        <div class="modal-spec-card">
          <div class="modal-spec-title"><ion-icon name="time-outline"></ion-icon> Schedule & Location</div>
          <div class="modal-spec-row">
            <span class="modal-spec-label">Scheduled For:</span>
            <span class="modal-spec-value" id="qv-schedule-time">-</span>
          </div>
          <div class="modal-spec-row">
            <span class="modal-spec-label">Service Address:</span>
            <span class="modal-spec-value" id="qv-address">-</span>
          </div>
          <div class="modal-spec-row" id="qv-landmark-row" style="display: none;">
            <span class="modal-spec-label">Landmark:</span>
            <span class="modal-spec-value" id="qv-landmark">-</span>
          </div>
          <div class="modal-spec-row" id="qv-pincode-row" style="display: none;">
            <span class="modal-spec-label">Pincode:</span>
            <span class="modal-spec-value" id="qv-pincode">-</span>
          </div>
        </div>

        <!-- Vehicle Details Spec Card -->
        <div class="modal-spec-card" id="qv-vehicle-card" style="display: none;">
          <div class="modal-spec-title"><ion-icon name="car-outline"></ion-icon> Vehicle Specifications</div>
          <div class="modal-spec-row">
            <span class="modal-spec-label">Type & Make:</span>
            <span class="modal-spec-value" id="qv-vehicle-make">-</span>
          </div>
          <div class="modal-spec-row">
            <span class="modal-spec-label">Registration No:</span>
            <span class="modal-spec-value" id="qv-vehicle-reg">-</span>
          </div>
          <div class="modal-spec-row" id="qv-vehicle-color-row" style="display: none;">
            <span class="modal-spec-label">Color:</span>
            <span class="modal-spec-value" id="qv-vehicle-color">-</span>
          </div>
        </div>

        <!-- Add-ons Spec Card -->
        <div class="modal-spec-card" id="qv-extras-card" style="display: none;">
          <div class="modal-spec-title"><ion-icon name="sparkles-outline"></ion-icon> Selected Add-ons</div>
          <div id="qv-extras-list" style="display: flex; flex-direction: column; gap: 4px;"></div>
        </div>

        <!-- Special Instructions Spec Card -->
        <div class="modal-spec-card" id="qv-notes-card" style="display: none;">
          <div class="modal-spec-title"><ion-icon name="document-text-outline"></ion-icon> Instructions / Notes</div>
          <p id="qv-notes" style="font-size: 0.9rem; color: #cbd5e1; font-style: italic; margin: 0;"></p>
        </div>

        <!-- Review Spec Card -->
        <div class="modal-spec-card" id="qv-review-card" style="display: none;">
          <div class="modal-spec-title"><ion-icon name="star-outline"></ion-icon> Verified Customer Rating</div>
          <div class="modal-spec-row">
            <span class="modal-spec-label">Rating:</span>
            <span class="modal-spec-value" id="qv-review-stars" style="color: #f59e0b;">-</span>
          </div>
          <p id="qv-review-comment" style="font-size: 0.85rem; color: #cbd5e1; margin-top: 6px;"></p>
        </div>
      </div>

      <div class="modal-footer-actions">
        <button type="button" class="btn-action btn-edit-booking" id="qv-edit-btn" style="display: none;">
          <ion-icon name="pencil-outline"></ion-icon>
          Edit Booking
        </button>
        <button type="button" class="btn-action btn-cancel-booking" id="qv-cancel-btn" style="display: none;">
          <ion-icon name="close-circle-outline"></ion-icon>
          Cancel Booking
        </button>
        <button type="button" class="btn-action btn-pay-online" id="qv-pay-btn" style="display: none; background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: 1px solid #10b981; font-weight: 600;">
          <ion-icon name="card-outline"></ion-icon>
          Pay Online
        </button>
        <button type="button" class="btn-modal-cancel" data-close-modal="#quickViewModal">Close</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════════════
       CANCEL BOOKING MODAL (WITH VALID REASONS)
       ══════════════════════════════════════════════════════════════════════════ -->
  <div class="modal-backdrop" id="cancelBookingModal">
    <div class="modal-glass-card" style="max-width: 500px;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title" style="color: #f87171;">Cancel Booking</h3>
          <p class="modal-subtitle" id="cancel-modal-subtitle">Ref: #00000000</p>
        </div>
        <button type="button" class="modal-close-btn" data-close-modal="#cancelBookingModal">&times;</button>
      </div>

      <div>
        <p style="font-size: 0.9rem; color: #cbd5e1; margin-bottom: 10px;">
          Please select a valid reason for cancellation:
        </p>

        <!-- Reason Selection Chips -->
        <div class="reason-pill-grid" id="cancel-reasons-container">
          <div class="reason-pill active" data-reason="Change of plans">Change of plans</div>
          <div class="reason-pill" data-reason="Booked by mistake">Booked by mistake</div>
          <div class="reason-pill" data-reason="Incorrect date or time">Incorrect date or time</div>
          <div class="reason-pill" data-reason="Found alternative service">Found alternative service</div>
          <div class="reason-pill" data-reason="Price too high">Price too high</div>
          <div class="reason-pill" data-reason="Other reason">Other reason</div>
        </div>

        <div class="modal-form-group">
          <label class="modal-form-label" for="cancel-custom-reason">Additional remarks / explanation (optional):</label>
          <textarea class="modal-textarea" id="cancel-custom-reason" placeholder="Explain why you wish to cancel this booking..."></textarea>
        </div>
      </div>

      <div class="modal-footer-actions">
        <button type="button" class="btn-modal-cancel" data-close-modal="#cancelBookingModal">Keep Booking</button>
        <button type="button" class="btn-modal-confirm-danger" id="btn-confirm-cancel">
          <ion-icon name="close-circle-outline"></ion-icon>
          Confirm Cancellation
        </button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════════════
       EDIT BOOKING MODAL (WITH INTELLIGENT RE-APPROVAL ROUTING)
       ══════════════════════════════════════════════════════════════════════════ -->
  <div class="modal-backdrop" id="editBookingModal">
    <div class="modal-glass-card" style="max-width: 580px;">
      <div class="modal-header">
        <div>
          <h3 class="modal-title">Edit Booking Details</h3>
          <p class="modal-subtitle" id="edit-modal-subtitle">Ref: #00000000</p>
        </div>
        <button type="button" class="modal-close-btn" data-close-modal="#editBookingModal">&times;</button>
      </div>

      <!-- Warning note about re-approval -->
      <div class="notice-box-warning">
        <ion-icon name="alert-circle-outline" style="font-size: 1.4rem; flex-shrink: 0; margin-top: 2px;"></ion-icon>
        <div>
          <strong>Re-approval Notice:</strong> Normal detail updates (vehicle specs, notes) update immediately.
          If you change the <strong>Schedule Date</strong>, <strong>Schedule Time</strong>, or <strong>Address</strong>, this booking will be re-submitted to your service provider for real-time live approval.
        </div>
      </div>

      <form id="edit-booking-form">
        <input type="hidden" id="edit-booking-id" value="">

        <!-- Sensitive fields -->
        <div style="font-size: 0.8rem; font-weight: 800; color: #60a5fa; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
          Schedule & Location (Triggers Live Re-approval)
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="modal-form-group" style="margin-bottom: 0;">
            <label class="modal-form-label" for="edit-schedule-date">Date (Click to open Calendar) *</label>
            <div class="picker-input-wrap">
              <input type="date" class="modal-input" id="edit-schedule-date" required>
              <button type="button" class="input-picker-icon-btn" id="btn-trigger-date" title="Open Calendar">
                <ion-icon name="calendar-outline"></ion-icon>
              </button>
            </div>
          </div>
          <div class="modal-form-group" style="margin-bottom: 0;">
            <label class="modal-form-label" for="edit-schedule-time">Time (Click to open Clock) *</label>
            <div class="picker-input-wrap">
              <input type="time" class="modal-input" id="edit-schedule-time" required>
              <button type="button" class="input-picker-icon-btn" id="btn-trigger-time" title="Open Clock">
                <ion-icon name="time-outline"></ion-icon>
              </button>
            </div>
          </div>
        </div>

        <!-- Quick Time Slot Selection Helper -->
        <div style="margin-bottom: 14px;">
          <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">Quick Time Slots:</div>
          <div class="quick-slots-row" id="quick-slots-container">
            <span class="quick-slot-pill" data-time="09:00">09:00 AM</span>
            <span class="quick-slot-pill" data-time="10:00">10:00 AM</span>
            <span class="quick-slot-pill" data-time="11:30">11:30 AM</span>
            <span class="quick-slot-pill" data-time="13:00">01:00 PM</span>
            <span class="quick-slot-pill" data-time="14:30">02:30 PM</span>
            <span class="quick-slot-pill" data-time="16:00">04:00 PM</span>
            <span class="quick-slot-pill" data-time="18:00">06:00 PM</span>
          </div>
        </div>

        <div class="modal-form-group">
          <label class="modal-form-label" for="edit-address">Service Address / Location *</label>
          <textarea class="modal-textarea" id="edit-address" placeholder="Enter complete service address..." required></textarea>
        </div>

        <!-- Normal fields -->
        <div style="font-size: 0.8rem; font-weight: 800; color: #34d399; text-transform: uppercase; letter-spacing: 0.5px; margin: 16px 0 10px 0;">
          Vehicle & Notes (Direct Update)
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 12px;">
          <div class="modal-form-group" style="margin-bottom: 0;">
            <label class="modal-form-label" for="edit-vehicle-brand">Brand</label>
            <input type="text" class="modal-input" id="edit-vehicle-brand" placeholder="e.g. Honda">
          </div>
          <div class="modal-form-group" style="margin-bottom: 0;">
            <label class="modal-form-label" for="edit-vehicle-model">Model</label>
            <input type="text" class="modal-input" id="edit-vehicle-model" placeholder="e.g. Civic">
          </div>
          <div class="modal-form-group" style="margin-bottom: 0;">
            <label class="modal-form-label" for="edit-vehicle-reg">Reg No</label>
            <input type="text" class="modal-input" id="edit-vehicle-reg" placeholder="WB 01 AB 1234">
          </div>
        </div>

        <div class="modal-form-group">
          <label class="modal-form-label" for="edit-notes">Special Instructions / Notes</label>
          <textarea class="modal-textarea" id="edit-notes" placeholder="Notes for service technician..."></textarea>
        </div>

        <div class="modal-footer-actions">
          <button type="button" class="btn-modal-cancel" data-close-modal="#editBookingModal">Cancel</button>
          <button type="submit" class="btn-modal-confirm-primary" id="btn-save-edit">
            <ion-icon name="save-outline"></ion-icon>
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
    </div>
  </main>

  <!-- FOOTER -->
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- BACK TO TOP BUTTON -->
  <a href="#top" class="back-to-top" aria-label="Back to Top" data-back-top-btn>
    <ion-icon name="arrow-up-outline"></ion-icon>
  </a>

  <!-- JS imports -->
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <script src="./assets/js/services-cards.js"></script>
  <script src="./assets/js/script.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      let activeBookingForCancel = null;
      let activeReasonPreset = 'Change of plans';

      // Helpers to open and close modals
      function openModal(modalId) {
        const modal = document.querySelector(modalId);
        if (modal) {
          modal.classList.add('active');
          document.body.style.overflow = 'hidden';
        }
      }

      function closeModal(modalId) {
        const modal = document.querySelector(modalId);
        if (modal) {
          modal.classList.remove('active');
          if (!document.querySelector('.modal-backdrop.active')) {
            document.body.style.overflow = '';
          }
        }
      }

      // Close modal on [data-close-modal] buttons
      document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
          const target = btn.getAttribute('data-close-modal');
          closeModal(target);
        });
      });

      // Close modal on backdrop click
      document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
          if (e.target === backdrop) {
            backdrop.classList.remove('active');
            if (!document.querySelector('.modal-backdrop.active')) {
              document.body.style.overflow = '';
            }
          }
        });
      });

      // Close modal on Escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          document.querySelectorAll('.modal-backdrop.active').forEach(m => m.classList.remove('active'));
          document.body.style.overflow = '';
        }
      });

      // ─────────────────────────────────────────────────────────────────────────
      // QUICK VIEW MODAL
      // ─────────────────────────────────────────────────────────────────────────
      function populateQuickView(b) {
        document.getElementById('qv-service-title').textContent = b.service_name || 'Service Details';
        document.getElementById('qv-ref-subtitle').textContent = 'Ref: #' + (b.booking_number || 'N/A');
        document.getElementById('qv-package-name').textContent = b.package_name || 'Standard Package';
        document.getElementById('qv-provider-name').textContent = b.provider_name || 'Assigned Provider';
        
        // Status Badge
        const status = (b.status || 'pending').toLowerCase();
        const statusBadge = document.getElementById('qv-status-badge');
        statusBadge.textContent = (b.status || 'PENDING').replace('_', ' ').toUpperCase();
        statusBadge.className = 'order-status-badge status-' + status;

        // Pricing & Payment
        document.getElementById('qv-total-amount').textContent = '$' + parseFloat(b.total_amount || 0).toFixed(2);
        const payMethod = (b.payment_method === 'Cash on Delivery' || b.payment_method === 'COD') ? 'COD' : (b.payment_method || 'Online');
        const payStatus = (b.payment_status === 'COD' || b.payment_status === 'Cash on Delivery') ? 'COD' : (b.payment_status || 'PENDING');
        document.getElementById('qv-payment-info').textContent = (payMethod === 'COD' || payStatus === 'COD') ? 'COD' : (payMethod + ' (' + payStatus + ')');

        // Schedule & Location
        document.getElementById('qv-schedule-time').textContent = (b.schedule_date || '') + ' at ' + (b.schedule_time || '');
        document.getElementById('qv-address').textContent = b.address || 'Address on file';

        const lmRow = document.getElementById('qv-landmark-row');
        if (b.landmark) {
          document.getElementById('qv-landmark').textContent = b.landmark;
          lmRow.style.display = 'flex';
        } else {
          lmRow.style.display = 'none';
        }

        const pinRow = document.getElementById('qv-pincode-row');
        if (b.pincode) {
          document.getElementById('qv-pincode').textContent = b.pincode;
          pinRow.style.display = 'flex';
        } else {
          pinRow.style.display = 'none';
        }

        // Vehicle specifications
        const vCard = document.getElementById('qv-vehicle-card');
        if (b.vehicle_type || b.vehicle_brand || b.vehicle_reg_no) {
          document.getElementById('qv-vehicle-make').textContent = [b.vehicle_type, b.vehicle_brand, b.vehicle_model].filter(Boolean).join(' ') || 'Standard Vehicle';
          document.getElementById('qv-vehicle-reg').textContent = b.vehicle_reg_no || 'N/A';
          const vColorRow = document.getElementById('qv-vehicle-color-row');
          if (b.vehicle_color) {
            document.getElementById('qv-vehicle-color').textContent = b.vehicle_color;
            vColorRow.style.display = 'flex';
          } else {
            vColorRow.style.display = 'none';
          }
          vCard.style.display = 'block';
        } else {
          vCard.style.display = 'none';
        }

        // Add-ons / Extras
        const extrasCard = document.getElementById('qv-extras-card');
        const extrasList = document.getElementById('qv-extras-list');
        extrasList.innerHTML = '';
        let extras = [];
        try {
          if (typeof b.extras === 'string') {
            extras = JSON.parse(b.extras);
          } else if (Array.isArray(b.extras)) {
            extras = b.extras;
          }
        } catch (e) {
          extras = [];
        }

        if (Array.isArray(extras) && extras.length > 0) {
          extras.forEach(item => {
            const row = document.createElement('div');
            row.style.cssText = 'display: flex; justify-content: space-between; font-size: 0.85rem; padding: 4px 0; border-bottom: 1px solid rgba(255,255,255,0.05);';
            row.innerHTML = `<span style="color: #cbd5e1;">${item.name || 'Extra Service'}</span><span style="color: #34d399; font-weight: 700;">+$${parseFloat(item.price || 0).toFixed(2)}</span>`;
            extrasList.appendChild(row);
          });
          extrasCard.style.display = 'block';
        } else {
          extrasCard.style.display = 'none';
        }

        // Special Instructions
        const notesCard = document.getElementById('qv-notes-card');
        if (b.notes && b.notes.trim()) {
          document.getElementById('qv-notes').textContent = '"' + b.notes + '"';
          notesCard.style.display = 'block';
        } else {
          notesCard.style.display = 'none';
        }

        // Review Card
        const reviewCard = document.getElementById('qv-review-card');
        if (b.review_rating) {
          document.getElementById('qv-review-stars').textContent = '★'.repeat(parseInt(b.review_rating, 10)) + ' (' + b.review_rating + '/5)';
          document.getElementById('qv-review-comment').textContent = b.review_comment ? `"${b.review_comment}"` : 'No written feedback submitted.';
          reviewCard.style.display = 'block';
        } else {
          reviewCard.style.display = 'none';
        }

        // Action Buttons inside Quick View
        const canCancel = ['pending', 'accepted', 'confirmed'].includes(status);
        const qvEditBtn = document.getElementById('qv-edit-btn');
        const qvCancelBtn = document.getElementById('qv-cancel-btn');
        const qvPayBtn = document.getElementById('qv-pay-btn');
        const isDone = (b.status || '').toLowerCase() === 'completed';
        const isUnpaid = String(b.payment_status || '').toUpperCase() !== 'PAID';

        if (qvPayBtn) {
          if (isDone && isUnpaid) {
            qvPayBtn.style.display = 'inline-flex';
            qvPayBtn.innerHTML = '<ion-icon name="card-outline"></ion-icon> Pay Online (₹' + parseFloat(b.total_amount).toFixed(2) + ')';
            qvPayBtn.onclick = () => {
              closeModal('#quickViewModal');
              startOnlinePayment(b);
            };
          } else {
            qvPayBtn.style.display = 'none';
          }
        }

        if (canCancel) {
          qvEditBtn.style.display = 'inline-flex';
          qvCancelBtn.style.display = 'inline-flex';

          qvEditBtn.onclick = () => {
            closeModal('#quickViewModal');
            openEditModal(b);
          };

          qvCancelBtn.onclick = () => {
            closeModal('#quickViewModal');
            openCancelModal(b.id, b.booking_number, b.service_name);
          };
        } else {
          qvEditBtn.style.display = 'none';
          qvCancelBtn.style.display = 'none';
        }

        openModal('#quickViewModal');
      }

      // Quick View button clicks on cards
      document.querySelectorAll('.btn-quick-view').forEach(btn => {
        btn.addEventListener('click', () => {
          try {
            const data = JSON.parse(btn.getAttribute('data-booking'));
            populateQuickView(data);
          } catch (e) {
            console.error('Failed to parse booking data', e);
          }
        });
      });

      // ─────────────────────────────────────────────────────────────────────────
      // CANCEL BOOKING MODAL & FLOW
      // ─────────────────────────────────────────────────────────────────────────
      function openCancelModal(id, ref, serviceName) {
        activeBookingForCancel = { id, ref, serviceName };
        document.getElementById('cancel-modal-subtitle').textContent = `Ref: #${ref} • ${serviceName}`;
        document.getElementById('cancel-custom-reason').value = '';
        activeReasonPreset = 'Change of plans';

        // Reset chips
        document.querySelectorAll('#cancel-reasons-container .reason-pill').forEach(pill => {
          if (pill.getAttribute('data-reason') === 'Change of plans') {
            pill.classList.add('active');
          } else {
            pill.classList.remove('active');
          }
        });

        openModal('#cancelBookingModal');
      }

      // Reason pill selection
      document.querySelectorAll('#cancel-reasons-container .reason-pill').forEach(pill => {
        pill.addEventListener('click', () => {
          document.querySelectorAll('#cancel-reasons-container .reason-pill').forEach(p => p.classList.remove('active'));
          pill.classList.add('active');
          activeReasonPreset = pill.getAttribute('data-reason');
        });
      });

      // Cancel button clicks on cards
      document.querySelectorAll('.btn-cancel-booking').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-id');
          const ref = btn.getAttribute('data-ref');
          const service = btn.getAttribute('data-service');
          openCancelModal(id, ref, service);
        });
      });

      // Confirm Cancellation submission
      const btnConfirmCancel = document.getElementById('btn-confirm-cancel');
      if (btnConfirmCancel) {
        btnConfirmCancel.addEventListener('click', async () => {
          if (!activeBookingForCancel) return;
          const customText = document.getElementById('cancel-custom-reason').value.trim();
          const finalReason = customText || activeReasonPreset;

          btnConfirmCancel.disabled = true;
          btnConfirmCancel.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Cancelling...';

          try {
            const bookingsApiUrl = (window.serveasyApiUrl ? window.serveasyApiUrl('bookings.php') : ((window.SERVEASY_API_BASE || '../../../backend/api/') + 'bookings.php'));
            const res = await fetch(bookingsApiUrl, {
              method: 'POST',
              credentials: 'include',
              headers: {
                'Content-Type': 'application/json',
                'X-Customer-Id': '<?= (int)($_SESSION['user_id'] ?? 0) ?>'
              },
              body: JSON.stringify({
                action: 'cancel',
                customer_id: <?= (int)($_SESSION['user_id'] ?? 0) ?>,
                id: activeBookingForCancel.id,
                reason: finalReason
              })
            });
            const data = await res.json();

            if (data.status === 'success' || res.ok) {
              alert(`Booking Ref: #${activeBookingForCancel.ref} has been successfully cancelled.`);
              window.location.reload();
            } else {
              alert(data.message || 'Unable to cancel booking. Please try again.');
            }
          } catch (err) {
            console.error('Cancellation error:', err);
            alert('A network error occurred while cancelling booking.');
          } finally {
            btnConfirmCancel.disabled = false;
            btnConfirmCancel.innerHTML = '<ion-icon name="close-circle-outline"></ion-icon> Confirm Cancellation';
            closeModal('#cancelBookingModal');
          }
        });
      }

      // ─────────────────────────────────────────────────────────────────────────
      // EDIT BOOKING MODAL & FLOW
      // ─────────────────────────────────────────────────────────────────────────
      const dateInput = document.getElementById('edit-schedule-date');
      const timeInput = document.getElementById('edit-schedule-time');
      const btnTriggerDate = document.getElementById('btn-trigger-date');
      const btnTriggerTime = document.getElementById('btn-trigger-time');

      function openNativeDatePicker() {
        if (!dateInput) return;
        dateInput.focus();
        if (typeof dateInput.showPicker === 'function') {
          try {
            dateInput.showPicker();
          } catch (e) {
            console.log('Native date picker focus', e);
          }
        }
      }

      function openNativeTimePicker() {
        if (!timeInput) return;
        timeInput.focus();
        if (typeof timeInput.showPicker === 'function') {
          try {
            timeInput.showPicker();
          } catch (e) {
            console.log('Native time picker focus', e);
          }
        }
      }

      if (dateInput) {
        dateInput.addEventListener('click', openNativeDatePicker);
      }
      if (btnTriggerDate) {
        btnTriggerDate.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          openNativeDatePicker();
        });
      }

      if (timeInput) {
        timeInput.addEventListener('click', openNativeTimePicker);
      }
      if (btnTriggerTime) {
        btnTriggerTime.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          openNativeTimePicker();
        });
      }

      // Quick Time Slot Pills
      document.querySelectorAll('#quick-slots-container .quick-slot-pill').forEach(pill => {
        pill.addEventListener('click', () => {
          const t = pill.getAttribute('data-time');
          if (timeInput && t) {
            timeInput.value = t;
          }
        });
      });

      function openEditModal(b) {
        document.getElementById('edit-booking-id').value = b.id;
        document.getElementById('edit-modal-subtitle').textContent = `Ref: #${b.booking_number} • ${b.service_name}`;
        
        if (dateInput) {
          dateInput.value = b.schedule_date || '';
          dateInput.min = new Date().toISOString().slice(0, 10);
        }
        if (timeInput) {
          timeInput.value = (b.schedule_time || '').substring(0, 5);
        }
        document.getElementById('edit-address').value = b.address || '';
        document.getElementById('edit-vehicle-brand').value = b.vehicle_brand || '';
        document.getElementById('edit-vehicle-model').value = b.vehicle_model || '';
        document.getElementById('edit-vehicle-reg').value = b.vehicle_reg_no || '';
        document.getElementById('edit-notes').value = b.notes || '';

        openModal('#editBookingModal');
      }

      // Edit button clicks on cards
      document.querySelectorAll('.btn-edit-booking').forEach(btn => {
        btn.addEventListener('click', () => {
          try {
            const data = JSON.parse(btn.getAttribute('data-booking'));
            openEditModal(data);
          } catch (e) {
            console.error('Failed to parse booking for edit', e);
          }
        });
      });

      // Edit form submission
      const editForm = document.getElementById('edit-booking-form');
      if (editForm) {
        editForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          const bookingId = document.getElementById('edit-booking-id').value;
          const scheduleDate = document.getElementById('edit-schedule-date').value.trim();
          const scheduleTime = document.getElementById('edit-schedule-time').value.trim();
          const address = document.getElementById('edit-address').value.trim();
          const vehicleBrand = document.getElementById('edit-vehicle-brand').value.trim();
          const vehicleModel = document.getElementById('edit-vehicle-model').value.trim();
          const vehicleReg = document.getElementById('edit-vehicle-reg').value.trim();
          const notes = document.getElementById('edit-notes').value.trim();

          if (!scheduleDate || !scheduleTime || !address) {
            alert('Date, Time, and Service Address are required.');
            return;
          }

          const btnSave = document.getElementById('btn-save-edit');
          btnSave.disabled = true;
          btnSave.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Saving Changes...';

          try {
            const bookingsApiUrl = (window.serveasyApiUrl ? window.serveasyApiUrl('bookings.php') : ((window.SERVEASY_API_BASE || '../../../backend/api/') + 'bookings.php'));
            const res = await fetch(bookingsApiUrl, {
              method: 'POST',
              credentials: 'include',
              headers: {
                'Content-Type': 'application/json',
                'X-Customer-Id': '<?= (int)($_SESSION['user_id'] ?? 0) ?>'
              },
              body: JSON.stringify({
                action: 'edit_booking',
                customer_id: <?= (int)($_SESSION['user_id'] ?? 0) ?>,
                id: bookingId,
                schedule_date: scheduleDate,
                schedule_time: scheduleTime,
                address: address,
                notes: notes,
                vehicle_brand: vehicleBrand,
                vehicle_model: vehicleModel,
                vehicle_reg_no: vehicleReg
              })
            });
            const data = await res.json();

            if (data.status === 'success' || res.ok) {
              if (data.data && data.data.requires_approval) {
                alert('Reschedule Request Sent!\n\nBecause you changed the schedule time or address, this booking has been sent back to your service provider for real-time live approval.');
              } else {
                alert('Booking details updated successfully.');
              }
              window.location.reload();
            } else {
              alert(data.message || 'Unable to update booking details.');
            }
          } catch (err) {
            console.error('Edit booking error:', err);
            alert('A network error occurred while updating the booking.');
          } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = '<ion-icon name="save-outline"></ion-icon> Save Changes';
            closeModal('#editBookingModal');
          }
        });
      }
    });
  </script>

</body>

</html>
