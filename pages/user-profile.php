<?php
require_once __DIR__ . '/../common/auth_session.php';

if (!isset($_SESSION['user_id'])) {
    if (isset($_POST['ajax_action']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized session. Please log in.']);
        exit;
    }
    // Client-side auto-rehydration fallback before redirecting to login
    $target_url = $_SERVER['REQUEST_URI'] ?? './user-profile.php';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authenticating | ServEasy</title>
    <style>
        body { margin: 0; padding: 0; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #fff; }
        .spinner-box { text-align: center; }
        .spinner { width: 44px; height: 44px; border: 3px solid rgba(255,255,255,0.1); border-top-color: #06b6d4; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { color: #94a3b8; font-size: 15px; margin: 0; }
    </style>
</head>
<body>
    <div class="spinner-box">
        <div class="spinner"></div>
        <p>Verifying secure session...</p>
    </div>
    <script>
        (async function() {
            try {
                const token = localStorage.getItem('serveasy_access_token');
                if (token) {
                    const res = await fetch('../auth/session-sync.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'access_token=' + encodeURIComponent(token)
                    });
                    const data = await res.json();
                    if (data && data.status === 'success') {
                        window.location.reload();
                        return;
                    }
                }
            } catch (e) {
                console.error("Auto-sync error:", e);
            }
            window.location.href = '../auth/auth.php?redirect=' + encodeURIComponent(window.location.href);
        })();
    </script>
</body>
</html>
    <?php
    exit;
}

$bPath = file_exists(__DIR__ . '/../../backend/api/bootstrap.php')
    ? __DIR__ . '/../../backend/api/bootstrap.php'
    : (file_exists(__DIR__ . '/../../../backend/api/bootstrap.php') 
        ? __DIR__ . '/../../../backend/api/bootstrap.php' 
        : null);
if ($bPath && file_exists($bPath)) { require_once $bPath; }
$db = class_exists('App\Helpers\DB') ? \App\Helpers\DB::getConnection() : null;
if (!$db && file_exists(__DIR__ . '/../common/db_connection.php')) {
    require_once __DIR__ . '/../common/db_connection.php';
    if (class_exists('ServEasyWebDB')) {
        $db = ServEasyWebDB::connect();
    }
}
$user_id = (int)$_SESSION['user_id'];

// ──────────────────────────────────────────────────────────────────────────────
// Handle AJAX Actions (Profile Updates & Address Management)
// ──────────────────────────────────────────────────────────────────────────────
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    if (!$db) {
        if (file_exists(__DIR__ . '/../common/ApiBridge.php')) {
            require_once __DIR__ . '/../common/ApiBridge.php';
            $postData = $_POST;
            $postData['action'] = $_POST['ajax_action'];
            $apiRes = \Frontend\ApiBridge::callApi('web-portal-api.php', 'POST', $postData);
            if (is_array($apiRes)) {
                if (($_POST['ajax_action'] === 'update_personal_info') && (($apiRes['status'] ?? '') === 'ok')) {
                    if (!empty($_POST['full_name'])) $_SESSION['full_name'] = trim($_POST['full_name']);
                    if (!empty($_POST['email_address'])) $_SESSION['email'] = trim($_POST['email_address']);
                    if (!empty($_POST['phone_number'])) $_SESSION['phone'] = trim($_POST['phone_number']);
                }
                echo json_encode($apiRes);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Unable to reach Central Backend API.']);
        exit;
    }
    try {
        // ACTION: Update Personal Info in site_user
        if ($_POST['ajax_action'] === 'update_personal_info') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email_address'] ?? '');
            $phone = trim($_POST['phone_number'] ?? '');

            if ($fullName === '') {
                echo json_encode(['status' => 'error', 'message' => 'Full Name cannot be empty']);
                exit;
            }

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address']);
                exit;
            }

            // Check if email already used by another user
            if (!empty($email)) {
                $emailCheck = $db->prepare("SELECT id FROM `site_user` WHERE email_address = :email AND id != :uid");
                $emailCheck->execute([':email' => $email, ':uid' => $user_id]);
                if ($emailCheck->fetch()) {
                    echo json_encode(['status' => 'error', 'message' => 'This email address is already in use by another account']);
                    exit;
                }
            }

            // Update in site_user
            $stmt = $db->prepare("
                UPDATE `site_user`
                SET full_name = :name, email_address = :email, phone_number = :phone, updated_at = NOW()
                WHERE id = :uid
            ");
            $stmt->execute([
                ':name' => $fullName,
                ':email' => !empty($email) ? $email : null,
                ':phone' => !empty($phone) ? $phone : null,
                ':uid' => $user_id,
            ]);

            echo json_encode(['status' => 'ok', 'message' => 'Your profile information has been successfully updated!']);
            exit;
        }

        // ACTION: Save Address
        elseif ($_POST['ajax_action'] === 'profile_save_address') {
            $addr_id  = !empty($_POST['addr_id']) ? (int)$_POST['addr_id'] : 0;
            $address  = trim($_POST['address']  ?? '');
            $landmark = trim($_POST['landmark'] ?? '');
            $pincode  = trim($_POST['pincode']  ?? '');
            $label    = trim($_POST['label']    ?? 'Home');
            $lat      = !empty($_POST['latitude'])  ? (float)$_POST['latitude']  : null;
            $lng      = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

            if ($address === '' || $pincode === '') {
                echo json_encode(['status' => 'error', 'message' => 'Full address and Pincode are required']);
                exit;
            }

            if ($label === '') $label = 'Home';

            if ($addr_id > 0) {
                // Update existing
                $stmt = $db->prepare("
                    UPDATE `user_addresses`
                    SET label=:label, address=:address, landmark=:landmark, pincode=:pincode, latitude=:lat, longitude=:lng, updated_at=NOW()
                    WHERE id=:id AND user_id=:uid
                ");
                $stmt->execute([
                    ':label'    => $label,
                    ':address'  => $address,
                    ':landmark' => $landmark,
                    ':pincode'  => $pincode,
                    ':lat'      => $lat,
                    ':lng'      => $lng,
                    ':id'       => $addr_id,
                    ':uid'      => $user_id
                ]);
            } else {
                // Check default status
                $check = $db->prepare("SELECT COUNT(*) FROM `user_addresses` WHERE user_id = ?");
                $check->execute([$user_id]);
                $count = $check->fetchColumn();
                $is_default = ($count == 0) ? 1 : 0;

                $stmt = $db->prepare("
                    INSERT INTO `user_addresses`
                        (user_id, label, address, landmark, pincode, latitude, longitude, is_default, created_at, updated_at)
                    VALUES
                        (:uid, :label, :address, :landmark, :pincode, :lat, :lng, :is_default, NOW(), NOW())
                ");
                $stmt->execute([
                    ':uid'        => $user_id,
                    ':label'      => $label,
                    ':address'    => $address,
                    ':landmark'   => $landmark,
                    ':pincode'    => $pincode,
                    ':lat'        => $lat,
                    ':lng'        => $lng,
                    ':is_default' => $is_default
                ]);
            }
            echo json_encode(['status' => 'ok', 'message' => 'Address successfully saved!']);
            exit;
        }

        // ACTION: Delete Address
        elseif ($_POST['ajax_action'] === 'profile_delete_address') {
            $addr_id = (int)$_POST['addr_id'];
            $db->prepare("DELETE FROM `user_addresses` WHERE id = ? AND user_id = ?")->execute([$addr_id, $user_id]);
            
            // Reassign default if default was deleted
            $check = $db->prepare("SELECT id FROM `user_addresses` WHERE user_id = ? AND is_default = 1");
            $check->execute([$user_id]);
            if (!$check->fetch()) {
                $db->prepare("UPDATE `user_addresses` SET is_default = 1 WHERE user_id = ? ORDER BY id DESC LIMIT 1")->execute([$user_id]);
            }
            echo json_encode(['status' => 'ok', 'message' => 'Address removed.']);
            exit;
        }

        // ACTION: Set Default Address
        elseif ($_POST['ajax_action'] === 'profile_set_default') {
            $addr_id = (int)$_POST['addr_id'];
            $db->prepare("UPDATE `user_addresses` SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
            $db->prepare("UPDATE `user_addresses` SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$addr_id, $user_id]);
            echo json_encode(['status' => 'ok', 'message' => 'Default address set.']);
            exit;
        }
    } catch (\Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Fetch Complete Dynamic Database Profile Columns
// ──────────────────────────────────────────────────────────────────────────────

// 1. Fetch site_user, KYC, stats, addresses, recent bookings (via local DB or Central Backend API)
$user = null;
$kyc = null;
$stats = [];
$rawAddresses = [];
$recentBookings = [];

if ($db) {
    $stmtUser = $db->prepare("SELECT id, full_name, email_address, phone_number, is_active, created_at, updated_at FROM `site_user` WHERE id = ? LIMIT 1");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $userFallback = $db->query("SELECT id, full_name, email_address, phone_number, is_active, created_at, updated_at FROM `site_user` ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($userFallback) {
            $user = $userFallback;
            $user_id = (int)$user['id'];
            $_SESSION['user_id'] = $user_id;
        }
    }

    $stmtKyc = $db->prepare("
        SELECT verification_id, id_type, id_number, verification_status, created_at, verified_at 
        FROM `id_verifications` 
        WHERE user_id = ? 
        ORDER BY verification_id DESC 
        LIMIT 1
    ");
    $stmtKyc->execute([$user_id]);
    $kyc = $stmtKyc->fetch(PDO::FETCH_ASSOC);

    $stmtStats = $db->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN status IN ('pending', 'accepted', 'in_progress') THEN 1 ELSE 0 END) as active_bookings,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_spent
        FROM `vehicle_wash` 
        WHERE customer_id = ?
    ");
    $stmtStats->execute([$user_id]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmtAddr = $db->prepare("SELECT * FROM `user_addresses` WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmtAddr->execute([$user_id]);
    $rawAddresses = $stmtAddr->fetchAll(PDO::FETCH_ASSOC);

    $stmtRecent = $db->prepare("
        SELECT id, booking_number, vehicle_type, vehicle_brand, vehicle_model, vehicle_reg_no, schedule_date, schedule_time, total_amount, status, payment_status, payment_method, created_at
        FROM `vehicle_wash`
        WHERE customer_id = ?
        ORDER BY id DESC
        LIMIT 4
    ");
    $stmtRecent->execute([$user_id]);
    $recentBookings = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
} else {
    $user = [
        'id'            => $user_id,
        'full_name'     => $_SESSION['full_name'] ?? '',
        'email_address' => $_SESSION['email'] ?? '',
        'phone_number'  => $_SESSION['phone'] ?? '',
        'is_active'     => 1,
        'created_at'    => null,
        'updated_at'    => null,
    ];
    if (file_exists(__DIR__ . '/../common/ApiBridge.php')) {
        require_once __DIR__ . '/../common/ApiBridge.php';
        $initRes = \Frontend\ApiBridge::callApi('web-portal-api.php?action=user_profile_init');
        if (!empty($initRes['status']) && $initRes['status'] === 'success') {
            if (!empty($initRes['user']) && is_array($initRes['user'])) {
                $user = array_merge($user, $initRes['user']);
            }
            $kyc            = $initRes['kyc'] ?? null;
            $stats          = is_array($initRes['stats'] ?? null) ? $initRes['stats'] : [];
            $rawAddresses   = is_array($initRes['addresses'] ?? null) ? $initRes['addresses'] : [];
            $recentBookings = is_array($initRes['recentBookings'] ?? null) ? $initRes['recentBookings'] : [];
        }
    }
}

// Dynamic display values
$displayName = !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Verified Customer';
$displayEmail = !empty($user['email_address']) ? htmlspecialchars($user['email_address']) : 'Not provided yet';
$displayPhone = !empty($user['phone_number']) ? htmlspecialchars($user['phone_number']) : 'Not provided yet';
$memberSince = !empty($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : 'October 2026';
$registeredDateFormatted = !empty($user['created_at']) ? date('M d, Y h:i A', strtotime($user['created_at'])) : 'N/A';
$lastUpdatedFormatted = !empty($user['updated_at']) ? date('M d, Y h:i A', strtotime($user['updated_at'])) : 'N/A';

// Calculate Initials for avatar
$nameParts = explode(' ', trim($user['full_name'] ?? ''));
$initials = '';
if (!empty($nameParts[0])) $initials .= strtoupper(substr($nameParts[0], 0, 1));
if (!empty($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));
if (empty($initials)) $initials = 'SE';

$kycStatus = $kyc ? ($kyc['verification_status'] ?: 'unverified') : 'unverified';
$maskedIdNumber = '';
if (!empty($kyc['id_number'])) {
    $rawId = (string)$kyc['id_number'];
    $maskedIdNumber = strlen($rawId) > 4 ? str_repeat('*', strlen($rawId) - 4) . substr($rawId, -4) : $rawId;
}

$totalBookings = (int)($stats['total_bookings'] ?? 0);
$completedBookings = (int)($stats['completed_bookings'] ?? 0);
$activeBookings = (int)($stats['active_bookings'] ?? 0);
$totalSpent = number_format((float)($stats['total_spent'] ?? 0), 2);

$seenKeys = [];
$addresses = [];
foreach ($rawAddresses as $addr) {
    $key = strtolower(trim($addr['address'] ?? '')) . '|' . trim($addr['pincode'] ?? '');
    if (!isset($seenKeys[$key])) {
        $seenKeys[$key] = true;
        $addresses[] = $addr;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Profile & Account — ServEasy</title>

  <!-- Favicon -->
  <link rel="shortcut icon" href="./assets/favicon.svg" type="image/svg+xml">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,800;1,600&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Core Stylesheet imports -->
  <link rel="stylesheet" href="./assets/css/main.css">
  <link rel="stylesheet" href="./assets/css/global.css">
  <link rel="stylesheet" href="./assets/css/home.css">
  <link rel="stylesheet" href="./assets/css/notify.css">
  
  <!-- Leaflet CSS for Address Pin Drop -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

  <style>
    /* ═══════════════════════════════════════════════════════════════════
       PREMIUM GLASSMORPHISM PROFILE THEME
       ═══════════════════════════════════════════════════════════════════ */
    body {
      background-color: #08090C !important;
      color: #E2E8F0;
      min-height: 100vh;
      overflow-x: hidden;
    }

    .profile-page-wrapper {
      max-width: 1240px;
      margin: 30px auto 70px auto;
      padding: 0 20px;
      position: relative;
      z-index: 2;
    }

    /* Ambient Background Glow Spheres */
    .glow-sphere {
      position: fixed;
      border-radius: 50%;
      filter: blur(120px);
      pointer-events: none;
      z-index: 0;
      opacity: 0.55;
    }
    .sphere-1 { width: 450px; height: 450px; top: -100px; left: -100px; background: rgba(0, 132, 255, 0.22); }
    .sphere-2 { width: 500px; height: 500px; top: 30%; right: -150px; background: rgba(138, 43, 226, 0.16); }
    .sphere-3 { width: 400px; height: 400px; bottom: 50px; left: 10%; background: rgba(48, 209, 88, 0.14); }

    /* Glass Container Utilities */
    .glass-panel {
      background: rgba(18, 22, 31, 0.65);
      backdrop-filter: blur(25px);
      -webkit-backdrop-filter: blur(25px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.1);
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    .glass-panel:hover {
      border-color: rgba(0, 132, 255, 0.25);
    }

    /* ── Hero Profile Banner ── */
    .profile-hero-card {
      padding: 30px;
      margin-bottom: 25px;
      position: relative;
      overflow: hidden;
    }
    .profile-hero-card::after {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 320px;
      height: 100%;
      background: radial-gradient(circle at 80% 20%, rgba(0, 132, 255, 0.12), transparent 70%);
      pointer-events: none;
    }
    .hero-main-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 24px;
    }
    .hero-identity-left {
      display: flex;
      align-items: center;
      gap: 22px;
    }
    .avatar-wrapper {
      position: relative;
      width: 94px;
      height: 94px;
      flex-shrink: 0;
    }
    .avatar-circle {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: linear-gradient(135deg, #0084FF 0%, #8A2BE2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.2rem;
      font-weight: 800;
      color: #FFFFFF;
      box-shadow: 0 10px 25px rgba(0, 132, 255, 0.35);
      border: 3px solid rgba(255, 255, 255, 0.15);
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .avatar-status-badge {
      position: absolute;
      bottom: 2px;
      right: 2px;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: #30D158;
      border: 3px solid #08090C;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0 10px rgba(48, 209, 88, 0.8);
    }
    .hero-meta h1 {
      margin: 0 0 6px 0;
      font-size: 1.85rem !important;
      font-weight: 800;
      color: #FFFFFF;
      letter-spacing: -0.3px;
    }
    .hero-badges-row {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 12px;
    }
    .badge-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.75rem !important;
      font-weight: 700;
      letter-spacing: 0.3px;
      text-transform: uppercase;
    }
    .badge-verified { background: rgba(48, 209, 88, 0.14); color: #30D158; border: 1px solid rgba(48, 209, 88, 0.35); }
    .badge-customer { background: rgba(0, 132, 255, 0.14); color: #0084FF; border: 1px solid rgba(0, 132, 255, 0.35); }
    .badge-since { background: rgba(255, 255, 255, 0.06); color: #94A3B8; border: 1px solid rgba(255, 255, 255, 0.1); }
    .badge-kyc-pending { background: rgba(255, 159, 10, 0.14); color: #FF9F0A; border: 1px solid rgba(255, 159, 10, 0.35); }
    
    .hero-contacts-preview {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      color: #94A3B8;
      font-size: 0.85rem;
    }
    .hero-contact-item {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .hero-contact-item ion-icon {
      font-size: 1.1rem;
      color: #0084FF;
    }

    .hero-actions-right {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
    }
    .btn-gradient-primary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #0084FF 0%, #0058CC 100%);
      color: #FFFFFF;
      padding: 10px 20px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 0.9rem;
      border: 1px solid rgba(255, 255, 255, 0.15);
      cursor: pointer;
      box-shadow: 0 8px 20px rgba(0, 132, 255, 0.3);
      transition: all 0.25s ease;
      text-decoration: none;
    }
    .btn-gradient-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 25px rgba(0, 132, 255, 0.45);
      color: #FFFFFF;
    }
    .btn-glass-secondary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.05);
      color: #FFFFFF;
      padding: 10px 18px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 0.9rem;
      border: 1px solid rgba(255, 255, 255, 0.1);
      cursor: pointer;
      transition: all 0.25s ease;
      text-decoration: none;
    }
    .btn-glass-secondary:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.2);
      color: #FFFFFF;
    }

    /* ── Metrics Stats Cards Grid ── */
    .stats-cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 16px;
      margin-bottom: 28px;
    }
    .metric-card {
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .metric-icon-box {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      flex-shrink: 0;
    }
    .icon-box-blue { background: rgba(0, 132, 255, 0.12); color: #0084FF; border: 1px solid rgba(0, 132, 255, 0.3); }
    .icon-box-emerald { background: rgba(48, 209, 88, 0.12); color: #30D158; border: 1px solid rgba(48, 209, 88, 0.3); }
    .icon-box-amber { background: rgba(255, 159, 10, 0.12); color: #FF9F0A; border: 1px solid rgba(255, 159, 10, 0.3); }
    .icon-box-purple { background: rgba(138, 43, 226, 0.12); color: #A855F7; border: 1px solid rgba(138, 43, 226, 0.3); }

    .metric-data h4 {
      margin: 0;
      font-size: 1.6rem !important;
      font-weight: 800;
      color: #FFFFFF;
      font-family: 'Poppins', sans-serif !important;
    }
    .metric-data p {
      margin: 3px 0 0 0;
      font-size: 0.8rem;
      color: #94A3B8;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
    }

    /* ── Navigation Tabs ── */
    .profile-tabs-header {
      display: flex;
      align-items: center;
      gap: 8px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      margin-bottom: 24px;
      padding-bottom: 4px;
      overflow-x: auto;
    }
    .tab-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      border-radius: 12px;
      background: transparent;
      border: 1px solid transparent;
      color: #94A3B8;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .tab-btn:hover {
      color: #FFFFFF;
      background: rgba(255, 255, 255, 0.04);
    }
    .tab-btn.active {
      color: #FFFFFF;
      background: rgba(0, 132, 255, 0.15);
      border-color: rgba(0, 132, 255, 0.4);
      box-shadow: 0 4px 15px rgba(0, 132, 255, 0.15);
    }
    .tab-btn.active ion-icon {
      color: #0084FF;
    }

    /* Tab Content Panels */
    .tab-panel {
      display: none;
    }
    .tab-panel.active {
      display: block;
      animation: fadeIn 0.3s ease forwards;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* ── Two Column Layout for Profile Data ── */
    .profile-grid-layout {
      display: grid;
      grid-template-columns: 1.15fr 0.85fr;
      gap: 24px;
    }
    @media (max-width: 900px) {
      .profile-grid-layout {
        grid-template-columns: 1fr;
      }
    }

    .section-card {
      padding: 24px;
      margin-bottom: 24px;
    }
    .section-header-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      padding-bottom: 14px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .section-header-title h3 {
      margin: 0;
      font-size: 1.25rem !important;
      color: #FFFFFF;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .section-header-title h3 ion-icon {
      color: #0084FF;
    }

    /* Forms & Fields */
    .form-group-custom {
      margin-bottom: 18px;
    }
    .form-group-custom label {
      display: block;
      margin-bottom: 6px;
      color: #94A3B8;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .form-input-glass {
      width: 100%;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      padding: 12px 14px;
      color: #FFFFFF;
      font-size: 0.95rem;
      outline: none;
      transition: all 0.25s ease;
      box-sizing: border-box;
    }
    .form-input-glass:focus {
      border-color: #0084FF;
      background: rgba(0, 132, 255, 0.04);
      box-shadow: 0 0 12px rgba(0, 132, 255, 0.2);
    }
    .form-input-glass[readonly] {
      background: rgba(255, 255, 255, 0.02);
      color: #94A3B8;
      cursor: not-allowed;
      border-color: rgba(255, 255, 255, 0.05);
    }

    /* Database Columns Metadata Grid */
    .db-meta-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
      margin-top: 10px;
    }
    .db-meta-box {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 10px;
      padding: 12px 14px;
    }
    .db-meta-key {
      font-size: 0.72rem;
      color: #64748B;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .db-meta-val {
      font-size: 0.9rem;
      color: #E2E8F0;
      font-weight: 600;
    }

    /* ── Address Cards Grid ── */
    .address-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .address-card-glass {
      padding: 18px;
      position: relative;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.03);
      transition: all 0.25s ease;
    }
    .address-card-glass.default-card {
      border-color: rgba(0, 132, 255, 0.5);
      background: rgba(0, 132, 255, 0.05);
      box-shadow: 0 4px 20px rgba(0, 132, 255, 0.12);
    }
    .address-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .addr-type-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      background: rgba(255, 255, 255, 0.06);
      color: #FFFFFF;
    }
    .default-tag {
      background: rgba(0, 132, 255, 0.15);
      color: #0084FF;
      border: 1px solid rgba(0, 132, 255, 0.3);
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
    }
    .addr-body-text {
      font-size: 0.88rem;
      color: #CBD5E1;
      line-height: 1.5;
      margin-bottom: 14px;
    }
    .addr-landmark-sub {
      color: #94A3B8;
      font-size: 0.8rem;
      margin-top: 4px;
    }
    .addr-actions-bar {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 8px;
      padding-top: 10px;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
    }
    .addr-icon-btn {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.08);
      color: #94A3B8;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .addr-icon-btn:hover {
      color: #FFFFFF;
      background: rgba(0, 132, 255, 0.2);
      border-color: #0084FF;
    }
    .addr-icon-btn.delete-btn:hover {
      background: rgba(255, 59, 48, 0.2);
      border-color: #FF3B30;
      color: #FF3B30;
    }

    /* ── Leaflet Map Box ── */
    #profile-loc-map {
      width: 100%;
      height: 280px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      margin: 12px 0 16px 0;
      background: #14161B;
    }

    /* ── Recent Bookings Table / Cards ── */
    .booking-history-item {
      padding: 16px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.06);
      background: rgba(255, 255, 255, 0.02);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      transition: background 0.2s;
    }
    .booking-history-item:hover {
      background: rgba(255, 255, 255, 0.04);
    }
    .b-ref-title {
      font-size: 0.95rem;
      font-weight: 700;
      color: #FFFFFF;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .b-meta-line {
      font-size: 0.8rem;
      color: #94A3B8;
      margin-top: 4px;
    }
    .b-price-val {
      font-size: 1.15rem;
      font-weight: 800;
      color: #30D158;
      text-align: right;
    }

    /* Status Pills */
    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
    }
    .status-pill.completed { background: rgba(48, 209, 88, 0.15); color: #30D158; border: 1px solid rgba(48, 209, 88, 0.35); }
    .status-pill.accepted  { background: rgba(0, 132, 255, 0.15); color: #0084FF; border: 1px solid rgba(0, 132, 255, 0.35); }
    .status-pill.pending   { background: rgba(255, 159, 10, 0.15); color: #FF9F0A; border: 1px solid rgba(255, 159, 10, 0.35); }
    .status-pill.cancelled { background: rgba(255, 59, 48, 0.15); color: #FF3B30; border: 1px solid rgba(255, 59, 48, 0.35); }

    /* Toast Alert Notification */
    #profile-toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: rgba(18, 22, 31, 0.92);
      backdrop-filter: blur(15px);
      border: 1px solid rgba(0, 132, 255, 0.4);
      color: #FFFFFF;
      padding: 14px 22px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      z-index: 9999;
      display: none;
      align-items: center;
      gap: 12px;
      font-size: 0.9rem;
      font-weight: 600;
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
    <div class="profile-page-wrapper">
      
      <!-- ══════════════════════════════════════════════════════════════ -->
      <!-- 1. HERO PROFILE CARD (DYNAMIC COLUMNS FROM site_user)          -->
      <!-- ══════════════════════════════════════════════════════════════ -->
      <div class="glass-panel profile-hero-card">
        <div class="hero-main-row">
          
          <div class="hero-identity-left">
            <div class="avatar-wrapper">
              <div class="avatar-circle">
                <?php echo $initials; ?>
              </div>
              <div class="avatar-status-badge" title="Active Verified Account">
                <ion-icon name="checkmark-outline" style="color:#08090C; font-size: 13px; font-weight:900;"></ion-icon>
              </div>
            </div>

            <div class="hero-meta">
              <h1 id="hero-display-name"><?php echo $displayName; ?></h1>
              
              <div class="hero-badges-row">
                <span class="badge-pill badge-verified">
                  <ion-icon name="shield-checkmark"></ion-icon>
                  <?php echo (!empty($user['is_active'])) ? 'Active Customer' : 'Inactive'; ?>
                </span>
                <span class="badge-pill badge-customer">
                  <ion-icon name="person-outline"></ion-icon>
                  Account #<?php echo $user_id; ?>
                </span>
                <span class="badge-pill badge-since">
                  <ion-icon name="calendar-outline"></ion-icon>
                  Member since <?php echo $memberSince; ?>
                </span>
                <?php if ($kycStatus === 'approved'): ?>
                  <span class="badge-pill badge-verified">
                    <ion-icon name="checkmark-circle"></ion-icon> ID Verified
                  </span>
                <?php else: ?>
                  <span class="badge-pill badge-kyc-pending">
                    <ion-icon name="time-outline"></ion-icon> KYC: <?php echo ucfirst($kycStatus); ?>
                  </span>
                <?php endif; ?>
              </div>

              <div class="hero-contacts-preview">
                <div class="hero-contact-item">
                  <ion-icon name="call-outline"></ion-icon>
                  <span id="hero-display-phone"><?php echo $displayPhone; ?></span>
                </div>
                <div class="hero-contact-item">
                  <ion-icon name="mail-outline"></ion-icon>
                  <span id="hero-display-email"><?php echo $displayEmail; ?></span>
                </div>
                <div class="hero-contact-item">
                  <ion-icon name="location-outline"></ion-icon>
                  <span><?php echo count($addresses); ?> Saved Addresses</span>
                </div>
              </div>
            </div>
          </div>

          <div class="hero-actions-right">
            <button type="button" class="btn-gradient-primary" onclick="switchProfileTab('personal')">
              <ion-icon name="create-outline"></ion-icon> Edit Profile
            </button>
            <a href="./orders.php" class="btn-glass-secondary">
              <ion-icon name="receipt-outline"></ion-icon> My Bookings
            </a>
          </div>

        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════════ -->
      <!-- 2. METRICS HIGHLIGHTS (DYNAMIC FROM vehicle_wash & addresses)  -->
      <!-- ══════════════════════════════════════════════════════════════ -->
      <div class="stats-cards-grid">
        <div class="glass-panel metric-card">
          <div class="metric-icon-box icon-box-blue">
            <ion-icon name="car-sport-outline"></ion-icon>
          </div>
          <div class="metric-data">
            <h4><?php echo $totalBookings; ?></h4>
            <p>Total Bookings</p>
          </div>
        </div>

        <div class="glass-panel metric-card">
          <div class="metric-icon-box icon-box-amber">
            <ion-icon name="flash-outline"></ion-icon>
          </div>
          <div class="metric-data">
            <h4><?php echo $activeBookings; ?></h4>
            <p>Active Requests</p>
          </div>
        </div>

        <div class="glass-panel metric-card">
          <div class="metric-icon-box icon-box-emerald">
            <ion-icon name="checkmark-done-circle-outline"></ion-icon>
          </div>
          <div class="metric-data">
            <h4><?php echo $completedBookings; ?></h4>
            <p>Completed Services</p>
          </div>
        </div>

        <div class="glass-panel metric-card">
          <div class="metric-icon-box icon-box-purple">
            <ion-icon name="map-outline"></ion-icon>
          </div>
          <div class="metric-data">
            <h4><?php echo count($addresses); ?></h4>
            <p>Saved Addresses</p>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════════ -->
      <!-- 3. PROFILE TABS CONTROLLER                                    -->
      <!-- ══════════════════════════════════════════════════════════════ -->
      <div class="profile-tabs-header">
        <button type="button" class="tab-btn active" id="tab-btn-personal" onclick="switchProfileTab('personal')">
          <ion-icon name="person-circle-outline"></ion-icon> Personal Information
        </button>
        <button type="button" class="tab-btn" id="tab-btn-addresses" onclick="switchProfileTab('addresses')">
          <ion-icon name="home-outline"></ion-icon> Saved Address Book (<?php echo count($addresses); ?>)
        </button>
        <button type="button" class="tab-btn" id="tab-btn-bookings" onclick="switchProfileTab('bookings')">
          <ion-icon name="calendar-outline"></ion-icon> Recent Bookings
        </button>
        <button type="button" class="tab-btn" id="tab-btn-security" onclick="switchProfileTab('security')">
          <ion-icon name="shield-checkmark-outline"></ion-icon> KYC & Security
        </button>
      </div>

      <!-- ══════════════════════════════════════════════════════════════ -->
      <!-- TAB 1: PERSONAL INFORMATION (DYNAMIC COLUMNS OF site_user)     -->
      <!-- ══════════════════════════════════════════════════════════════ -->
      <div class="tab-panel active" id="panel-personal">
        <div class="profile-grid-layout">
          
          <!-- Editable Personal Info Form -->
          <div class="glass-panel section-card">
            <div class="section-header-title">
              <h3><ion-icon name="create-outline"></ion-icon> Edit Profile Details</h3>
              <span style="font-size: 0.8rem; color: #94A3B8;">Direct Database Persistence</span>
            </div>

            <form id="edit-profile-form" onsubmit="saveProfileDetails(event)">
              <div class="form-group-custom">
                <label>Full Name *</label>
                <input type="text" id="prof-full-name" class="form-input-glass" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Enter your full name" required>
              </div>

              <div class="form-group-custom">
                <label>Email Address</label>
                <input type="email" id="prof-email" class="form-input-glass" value="<?php echo htmlspecialchars($user['email_address'] ?? ''); ?>" placeholder="name@example.com">
              </div>

              <div class="form-group-custom">
                <label>Mobile Number (Primary Identity)</label>
                <input type="text" id="prof-phone" class="form-input-glass" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="+91 98765 43210">
              </div>

              <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" id="btn-save-profile" class="btn-gradient-primary" style="flex: 1; justify-content: center;">
                  <ion-icon name="save-outline"></ion-icon> Save Changes
                </button>
                <button type="button" class="btn-glass-secondary" onclick="resetProfileForm()">
                  <ion-icon name="refresh-outline"></ion-icon> Reset
                </button>
              </div>
            </form>
          </div>

          <!-- Read-Only Database Columns Metadata -->
          <div class="glass-panel section-card">
            <div class="section-header-title">
              <h3><ion-icon name="server-outline"></ion-icon> Account & Database Columns</h3>
            </div>

            <p style="font-size: 0.85rem; color: #94A3B8; margin-top: 0; margin-bottom: 16px;">
              These audit columns are securely tracked inside database table <code style="color:#0084FF;">`site_user`</code>.
            </p>

            <div class="db-meta-grid">
              <div class="db-meta-box">
                <div class="db-meta-key"><ion-icon name="finger-print-outline"></ion-icon> Column: id</div>
                <div class="db-meta-val">#<?php echo htmlspecialchars((string)($user['id'] ?? $user_id)); ?></div>
              </div>
              <div class="db-meta-box">
                <div class="db-meta-key"><ion-icon name="toggle-outline"></ion-icon> Column: is_active</div>
                <div class="db-meta-val" style="color: #30D158;">
                  <?php echo (!empty($user['is_active'])) ? '1 (Active)' : '0 (Suspended)'; ?>
                </div>
              </div>
              <div class="db-meta-box">
                <div class="db-meta-key"><ion-icon name="time-outline"></ion-icon> Column: created_at</div>
                <div class="db-meta-val" style="font-size: 0.8rem;"><?php echo htmlspecialchars((string)($registeredDateFormatted ?? 'N/A')); ?></div>
              </div>
              <div class="db-meta-box">
                <div class="db-meta-key"><ion-icon name="sync-outline"></ion-icon> Column: updated_at</div>
                <div class="db-meta-val" style="font-size: 0.8rem;"><?php echo htmlspecialchars((string)($lastUpdatedFormatted ?? 'N/A')); ?></div>
              </div>
            </div>

            <!-- KYC Card Preview -->
            <div style="margin-top: 20px; padding: 14px; border-radius: 12px; background: rgba(0, 132, 255, 0.05); border: 1px solid rgba(0, 132, 255, 0.2);">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <span style="font-weight: 700; font-size: 0.85rem; color: #FFFFFF;">KYC Verification Status</span>
                <span class="status-pill <?php echo $kycStatus === 'approved' ? 'completed' : 'pending'; ?>">
                  <?php echo ucfirst($kycStatus); ?>
                </span>
              </div>
              <p style="font-size: 0.8rem; color: #94A3B8; margin: 0;">
                <?php if ($kyc && !empty($kyc['id_type'])): ?>
                  Document: <strong><?php echo strtoupper($kyc['id_type']); ?></strong> (<?php echo $maskedIdNumber; ?>)
                <?php else: ?>
                  No government identity document uploaded yet.
                <?php endif; ?>
              </p>
            </div>
          </div>

        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════════ -->
      <!-- TAB 2: SAVED ADDRESS BOOK (DYNAMIC FROM user_addresses)        -->
      <!-- ══════════════════════════════════════════════════════════════ -->
      <div class="tab-panel" id="panel-addresses">
        <div class="profile-grid-layout">
          
          <!-- Address List -->
          <div>
            <div class="section-header-title">
              <h3><ion-icon name="business-outline"></ion-icon> Saved Address Book (<?php echo count($addresses); ?>)</h3>
              <button type="button" class="btn-gradient-primary" onclick="focusAddressForm()" style="padding: 7px 14px; font-size: 0.8rem;">
                <ion-icon name="add-circle-outline"></ion-icon> Add New Address
              </button>
            </div>

            <div class="address-grid">
              <?php if (empty($addresses)): ?>
                <div class="glass-panel" style="padding: 30px; text-align: center; grid-column: 1 / -1;">
                  <ion-icon name="location-outline" style="font-size: 3rem; color: #64748B;"></ion-icon>
                  <p style="color: #94A3B8; margin-top: 10px;">No saved addresses found. Add an address below for swift 1-click booking!</p>
                </div>
              <?php else: ?>
                <?php foreach ($addresses as $addr): ?>
                  <div class="address-card-glass <?php echo $addr['is_default'] ? 'default-card' : ''; ?>">
                    <div class="address-card-header">
                      <span class="addr-type-pill">
                        <?php 
                          $l = strtolower($addr['label']);
                          $icon = 'location-outline';
                          if (str_contains($l, 'home')) $icon = 'home-outline';
                          elseif (str_contains($l, 'work') || str_contains($l, 'office')) $icon = 'briefcase-outline';
                        ?>
                        <ion-icon name="<?php echo $icon; ?>"></ion-icon>
                        <?php echo htmlspecialchars($addr['label']); ?>
                      </span>

                      <?php if ($addr['is_default']): ?>
                        <span class="default-tag">Default</span>
                      <?php endif; ?>
                    </div>

                    <div class="addr-body-text">
                      <?php echo htmlspecialchars($addr['address']); ?>
                      <?php if (!empty($addr['landmark'])): ?>
                        <div class="addr-landmark-sub">Landmark: <?php echo htmlspecialchars($addr['landmark']); ?></div>
                      <?php endif; ?>
                      <div class="addr-landmark-sub">Pincode: <?php echo htmlspecialchars($addr['pincode']); ?></div>
                    </div>

                    <div class="addr-actions-bar">
                      <?php if (!$addr['is_default']): ?>
                        <button type="button" class="addr-icon-btn" title="Set as Default Address" onclick="profSetDefault(<?php echo $addr['id']; ?>)">
                          <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </button>
                      <?php endif; ?>
                      <button type="button" class="addr-icon-btn" title="Edit Address" onclick='profEditAddress(<?php echo json_encode($addr); ?>)'>
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button type="button" class="addr-icon-btn delete-btn" title="Delete Address" onclick="profDeleteAddress(<?php echo $addr['id']; ?>)">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- Add / Edit Address Panel with Leaflet Map -->
          <div class="glass-panel section-card" id="address-form-box">
            <div class="section-header-title">
              <h3 id="addr-form-heading"><ion-icon name="map-outline"></ion-icon> Add New Address</h3>
            </div>

            <button type="button" class="btn-gradient-primary" id="prof-locate-btn" onclick="profLocateMe()" style="margin-bottom: 12px; width: 100%; justify-content: center;">
              <ion-icon name="locate-outline"></ion-icon> Pin Current Location
            </button>
            <p style="text-align: center; color: #94A3B8; font-size: 0.78rem; margin: 0 0 10px 0;">
              Or drag the pin on the map to pinpoint your doorstep location:
            </p>

            <div id="profile-loc-map"></div>

            <form id="add-address-form" onsubmit="profSaveAddress(event)">
              <div class="form-group-custom">
                <label>Address Label</label>
                <input type="text" id="prof-label" class="form-input-glass" placeholder="e.g. Home, Work, Friend's Place" value="Home" required>
              </div>

              <div class="form-group-custom">
                <label>Full Address *</label>
                <input type="text" id="prof-address" class="form-input-glass" placeholder="House/Flat No, Street, Building, Area" required>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group-custom">
                  <label>Landmark</label>
                  <input type="text" id="prof-landmark" class="form-input-glass" placeholder="Near Apollo Hospital etc.">
                </div>
                <div class="form-group-custom">
                  <label>Pincode *</label>
                  <input type="text" id="prof-pincode" class="form-input-glass" placeholder="e.g. 700001" required>
                </div>
              </div>

              <input type="hidden" id="prof-addr-id" value="">
              <input type="hidden" id="prof-lat" value="">
              <input type="hidden" id="prof-lng" value="">

              <div style="display: flex; gap: 10px; margin-top: 14px;">
                <button type="submit" id="prof-submit-btn" class="btn-gradient-primary" style="flex: 1; justify-content: center;">
                  <ion-icon name="save-outline"></ion-icon> Save Address
                </button>
                <button type="button" id="prof-cancel-edit-btn" class="btn-glass-secondary" style="display:none; justify-content: center;" onclick="profCancelEdit()">
                  <ion-icon name="close-outline"></ion-icon> Cancel
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════════ -->
      <!-- TAB 3: RECENT BOOKINGS (DYNAMIC FROM vehicle_wash)             -->
      <!-- ══════════════════════════════════════════════════════════════ -->
      <div class="tab-panel" id="panel-bookings">
        <div class="glass-panel section-card">
          <div class="section-header-title">
            <h3><ion-icon name="receipt-outline"></ion-icon> Recent Bookings Overview</h3>
            <a href="./orders.php" class="btn-gradient-primary" style="padding: 7px 16px; font-size: 0.82rem;">
              View All Orders <ion-icon name="arrow-forward-outline"></ion-icon>
            </a>
          </div>

          <?php if (empty($recentBookings)): ?>
            <div style="padding: 40px; text-align: center;">
              <ion-icon name="calendar-outline" style="font-size: 3rem; color: #64748B;"></ion-icon>
              <p style="color: #94A3B8; margin-top: 10px;">No bookings found yet. Explore our service catalog to book your first verified service!</p>
              <a href="./services.php" class="btn-gradient-primary" style="margin-top: 14px;">Explore Services</a>
            </div>
          <?php else: ?>
            <?php foreach ($recentBookings as $b): ?>
              <?php 
                $statusKey = strtolower($b['status']);
                $pillClass = 'pending';
                if ($statusKey === 'completed') $pillClass = 'completed';
                elseif ($statusKey === 'accepted' || $statusKey === 'in_progress') $pillClass = 'accepted';
                elseif ($statusKey === 'cancelled') $pillClass = 'cancelled';
              ?>
              <div class="booking-history-item">
                <div>
                  <div class="b-ref-title">
                    <span><?php echo htmlspecialchars($b['booking_number']); ?></span>
                    <span class="status-pill <?php echo $pillClass; ?>"><?php echo strtoupper($b['status']); ?></span>
                  </div>
                  <div class="b-meta-line">
                    <ion-icon name="car-outline"></ion-icon>
                    <strong><?php echo htmlspecialchars($b['vehicle_type']); ?>:</strong> <?php echo htmlspecialchars($b['vehicle_brand'] . ' ' . $b['vehicle_model']); ?> 
                    <?php if (!empty($b['vehicle_reg_no'])): ?> (<?php echo htmlspecialchars($b['vehicle_reg_no']); ?>)<?php endif; ?>
                  </div>
                  <div class="b-meta-line">
                    <ion-icon name="calendar-clear-outline"></ion-icon>
                    Scheduled: <?php echo date('M d, Y', strtotime($b['schedule_date'])); ?> at <?php echo date('h:i A', strtotime($b['schedule_time'])); ?>
                    • Payment: <?php echo htmlspecialchars($b['payment_method'] ?? 'COD'); ?> (<?php echo htmlspecialchars($b['payment_status'] ?? 'UNPAID'); ?>)
                  </div>
                </div>

                <div>
                  <div class="b-price-val">₹<?php echo number_format((float)$b['total_amount'], 2); ?></div>
                  <div style="font-size: 0.72rem; color: #64748B; text-align: right; margin-top: 4px;">
                    <?php echo date('M d, Y', strtotime($b['created_at'])); ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════════ -->
      <!-- TAB 4: KYC & SECURITY CREDENTIALS (DYNAMIC FROM id_verifications)-->
      <!-- ══════════════════════════════════════════════════════════════ -->
      <div class="tab-panel" id="panel-security">
        <div class="profile-grid-layout">
          <div class="glass-panel section-card">
            <div class="section-header-title">
              <h3><ion-icon name="shield-checkmark-outline"></ion-icon> Government ID Verification (KYC)</h3>
              <span class="status-pill <?php echo $kycStatus === 'approved' ? 'completed' : 'pending'; ?>">
                <?php echo strtoupper($kycStatus); ?>
              </span>
            </div>

            <p style="font-size: 0.88rem; color: #94A3B8; line-height: 1.6; margin-top: 0;">
              ServEasy requires verified identification to safeguard doorstep service technicians, concierge dispatches, and emergency SOS responders.
            </p>

            <div class="db-meta-grid" style="margin-top: 20px;">
              <div class="db-meta-box">
                <div class="db-meta-key">Document Type</div>
                <div class="db-meta-val"><?php echo $kyc ? strtoupper($kyc['id_type']) : 'Aadhaar / Passport'; ?></div>
              </div>
              <div class="db-meta-box">
                <div class="db-meta-key">Masked ID Number</div>
                <div class="db-meta-val"><?php echo $maskedIdNumber ?: 'Not on file'; ?></div>
              </div>
              <div class="db-meta-box">
                <div class="db-meta-key">Submitted On</div>
                <div class="db-meta-val"><?php echo $kyc && !empty($kyc['created_at']) ? date('M d, Y', strtotime($kyc['created_at'])) : 'N/A'; ?></div>
              </div>
              <div class="db-meta-box">
                <div class="db-meta-key">Verification Status</div>
                <div class="db-meta-val" style="color: <?php echo $kycStatus === 'approved' ? '#30D158' : '#FF9F0A'; ?>;">
                  <?php echo ucfirst($kycStatus); ?>
                </div>
              </div>
            </div>
          </div>

          <div class="glass-panel section-card">
            <div class="section-header-title">
              <h3><ion-icon name="lock-closed-outline"></ion-icon> Account Credentials</h3>
            </div>

            <div class="form-group-custom">
              <label>Authentication Mode</label>
              <input type="text" class="form-input-glass" value="Mobile OTP & Password Authentication" readonly>
            </div>

            <div class="form-group-custom">
              <label>Primary Verified Identity</label>
              <input type="text" class="form-input-glass" value="<?php echo htmlspecialchars(($user['phone_number'] ?? '') ?: ($user['email_address'] ?? '') ?: 'Mobile User'); ?>" readonly>
            </div>

            <div style="margin-top: 18px; padding: 12px; border-radius: 10px; background: rgba(48, 209, 88, 0.08); border: 1px solid rgba(48, 209, 88, 0.2);">
              <span style="font-size: 0.82rem; color: #30D158; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                <ion-icon name="checkmark-circle"></ion-icon> Multi-Factor Protected
              </span>
              <p style="font-size: 0.78rem; color: #94A3B8; margin: 4px 0 0 0;">
                One-Time Passwords (OTPs) are automatically expired every 10 minutes.
              </p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- Toast Notification Popup -->
  <div id="profile-toast">
    <ion-icon name="checkmark-circle-outline" style="color: #30D158; font-size: 1.4rem;"></ion-icon>
    <span id="toast-text">Changes saved successfully!</span>
  </div>

  <!-- FOOTER -->
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- JS imports -->
  <script src="./assets/js/script.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  
  <!-- Leaflet JS for Map -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    // ── Tab Switching Logic ──
    function switchProfileTab(tabName) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

      const activeBtn = document.getElementById('tab-btn-' + tabName);
      const activePanel = document.getElementById('panel-' + tabName);

      if (activeBtn) activeBtn.classList.add('active');
      if (activePanel) activePanel.classList.add('active');

      if (tabName === 'addresses') {
        setTimeout(function() {
          if (!profMap) {
            let lat = parseFloat(document.getElementById('prof-lat') ? document.getElementById('prof-lat').value : 0);
            let lng = parseFloat(document.getElementById('prof-lng') ? document.getElementById('prof-lng').value : 0);
            if (!isNaN(lat) && !isNaN(lng) && lat !== 0) {
              initProfMap(lat, lng, 15);
            } else {
              initProfMap(20.5937, 78.9629, 5);
            }
          } else {
            profMap.invalidateSize();
          }
        }, 200);
      }
    }

    // ── Toast Notification Helper ──
    function showProfileToast(message, isError = false) {
      const toast = document.getElementById('profile-toast');
      const text = document.getElementById('toast-text');
      const icon = toast.querySelector('ion-icon');

      text.innerText = message;
      if (isError) {
        icon.setAttribute('name', 'alert-circle-outline');
        icon.style.color = '#FF3B30';
        toast.style.borderColor = 'rgba(255, 59, 48, 0.4)';
      } else {
        icon.setAttribute('name', 'checkmark-circle-outline');
        icon.style.color = '#30D158';
        toast.style.borderColor = 'rgba(0, 132, 255, 0.4)';
      }

      toast.style.display = 'flex';
      setTimeout(() => {
        toast.style.display = 'none';
      }, 3500);
    }

    // ── Save Personal Info via AJAX ──
    function saveProfileDetails(e) {
      e.preventDefault();
      const saveBtn = document.getElementById('btn-save-profile');
      saveBtn.disabled = true;
      saveBtn.innerHTML = '<ion-icon name="sync-outline" class="spin"></ion-icon> Saving...';

      const fullName = document.getElementById('prof-full-name').value.trim();
      const email = document.getElementById('prof-email').value.trim();
      const phone = document.getElementById('prof-phone').value.trim();

      const formData = new FormData();
      formData.append('ajax_action', 'update_personal_info');
      formData.append('full_name', fullName);
      formData.append('email_address', email);
      formData.append('phone_number', phone);

      fetch('./user-profile.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<ion-icon name="save-outline"></ion-icon> Save Changes';

        if (data.status === 'ok') {
          showProfileToast(data.message || 'Profile saved successfully!');
          // Live update hero header without page refresh
          document.getElementById('hero-display-name').innerText = fullName || 'Verified Customer';
          document.getElementById('hero-display-phone').innerText = phone || 'Not provided';
          document.getElementById('hero-display-email').innerText = email || 'Not provided';
        } else {
          showProfileToast(data.message || 'Failed to save changes.', true);
        }
      })
      .catch(err => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<ion-icon name="save-outline"></ion-icon> Save Changes';
        showProfileToast('Network error while saving profile.', true);
      });
    }

    function resetProfileForm() {
      document.getElementById('edit-profile-form').reset();
    }

    // ── Leaflet Map Geolocation & Address Management ──
    let profMap = null, profMarker = null;

    function initProfMap(lat, lng, zoom = 13) {
      if (profMap) {
        profMap.setView([lat, lng], zoom);
        if (profMarker) profMarker.setLatLng([lat, lng]);
        return;
      }

      const mapContainer = document.getElementById('profile-loc-map');
      if (!mapContainer) return;

      profMap = L.map('profile-loc-map', { zoomControl: false }).setView([lat, lng], zoom);
      L.control.zoom({ position: 'bottomright' }).addTo(profMap);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(profMap);

      profMarker = L.marker([lat, lng], { draggable: true }).addTo(profMap);

      profMarker.on('dragend', function(e) {
        const coord = e.target.getLatLng();
        updateFormCoordinates(coord.lat, coord.lng);
        reverseGeocode(coord.lat, coord.lng);
      });

      profMap.on('click', function(e) {
        profMarker.setLatLng(e.latlng);
        updateFormCoordinates(e.latlng.lat, e.latlng.lng);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
      });
    }

    function updateFormCoordinates(lat, lng) {
      document.getElementById('prof-lat').value = lat;
      document.getElementById('prof-lng').value = lng;
    }

    function formatUserDoorstepAddress(bdc, nom, lat, lng, arc) {
      const a = (nom && nom.address) ? nom.address : {};
      const arcAddr = (arc && arc.address) ? arc.address : (arc || {});

      // 1. Specific spot / building / apartment / POI
      let spot = arcAddr.PlaceName || '';
      if (!spot && (arcAddr.Addr_type === 'POI' || arcAddr.Addr_type === 'PointAddress')) {
        spot = arcAddr.ShortLabel || arcAddr.Address || '';
      }
      if (!spot) {
        spot = a.building || a.house_name || a.amenity || a.shop || a.office || '';
      }
      const houseNo = a.house_number || arcAddr.AddNum || '';

      // 2. Road / Street
      let road = a.road || a.pedestrian || a.street || '';
      if (!road && arcAddr.Address && arcAddr.Address !== spot) {
        road = arcAddr.Address;
      }
      if (road === spot) road = '';

      // 3. Lane / Gali / Sector / Colony / Block
      const sector = arcAddr.Sector || arcAddr.Neighborhood || arcAddr.Block || '';
      let laneGali = sector;
      if (laneGali === road || laneGali === spot) laneGali = '';

      // 4. Suburb / Local Area (from Nominatim or BigDataCloud locality or ArcGIS District)
      let suburb = a.suburb || a.neighbourhood || a.subdistrict || a.village || (bdc && bdc.locality ? bdc.locality : '') || arcAddr.District || '';
      if (suburb === laneGali || suburb === road || suburb === spot) suburb = '';

      // 5. City / Town
      let city = (bdc && bdc.city) || a.city || a.town || a.municipality || 'Kolkata';
      if (city.toLowerCase().indexOf('greater kolkata') !== -1) city = 'Kolkata';
      if (city === suburb || city === laneGali) city = '';

      // 6. State
      const state = (bdc && bdc.principalSubdivision) || a.state || arcAddr.Region || 'West Bengal';

      // 7. Pincode
      let pincode = a.postcode || arcAddr.Postal || (bdc && bdc.postcode) || '';
      if (!pincode && nom && nom.display_name) {
        const m = String(nom.display_name).match(/\b([1-9][0-9]{5})\b/);
        if (m) pincode = m[1];
      }

      // Assemble comprehensive clean doorstep parts (building, houseNo, road, lane/gali, suburb, city, state)
      const rawParts = [spot, houseNo, road, laneGali, suburb, city, state].filter(v => v && String(v).trim().length > 0);
      const uniqueParts = [];
      rawParts.forEach(function(p) {
        const s = String(p).trim();
        if (s && !uniqueParts.some(function(u) { return u.toLowerCase() === s.toLowerCase(); })) {
          uniqueParts.push(s);
        }
      });

      let address = uniqueParts.join(', ');

      // Strip administrative noise anywhere in string
      address = address
        .replace(/,\s*(Greater\s*Kolkata|Barrackpur\s*[-–]\s*II|Barrackpore\s*[-–]?\s*[I|1|2|II]+|North\s*24\s*Parganas|South\s*24\s*Parganas|India)\b/gi, '')
        .replace(/\b(Greater\s*Kolkata|Barrackpur\s*[-–]\s*II|Barrackpore\s*[-–]?\s*[I|1|2|II]+|North\s*24\s*Parganas|South\s*24\s*Parganas|India),\s*/gi, '')
        .replace(/,\s*India$/gi, '')
        .replace(/\s*,\s*,+/g, ', ')
        .trim()
        .replace(/^,+|,+$/g, '')
        .trim();

      // Landmark: best candidate for recognizable local doorstep reference
      let landmark = '';
      if (spot && laneGali && spot !== laneGali) {
        landmark = `Near ${spot}, ${laneGali}`;
      } else if (spot && road && spot !== road) {
        landmark = `Near ${spot}, ${road}`;
      } else if (spot) {
        landmark = `Near ${spot}`;
      } else if (laneGali) {
        landmark = `Near ${laneGali}`;
      } else if (road && suburb && road !== suburb) {
        landmark = `Near ${road}, ${suburb}`;
      } else if (road) {
        landmark = `Near ${road}`;
      } else if (suburb) {
        landmark = `Near ${suburb}`;
      }

      return { address: address || `${lat.toFixed(5)}, ${lng.toFixed(5)}`, landmark: landmark, pincode: pincode };
    }

    function reverseGeocode(lat, lng) {
      let bdcData = null;
      let nomData = null;
      let arcData = null;
      let done = false;

      function applyAddress() {
        if (done) return;
        done = true;
        const res = formatUserDoorstepAddress(bdcData, nomData, lat, lng, arcData);
        const addrInput = document.getElementById('prof-address');
        const lndInput = document.getElementById('prof-landmark');
        const pinInput = document.getElementById('prof-pincode');
        if (addrInput && res.address) addrInput.value = res.address;
        if (lndInput && res.landmark) lndInput.value = res.landmark;
        if (pinInput && res.pincode) pinInput.value = res.pincode;
      }

      // 1. ArcGIS World Geocoder (Granular building, sector, gali, lane, road, pincode)
      fetch(`https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/reverseGeocode?location=${lng},${lat}&f=pjson`)
        .then(r => r.json())
        .then(arc => {
          arcData = arc && arc.address ? arc.address : arc;
          if (nomData && bdcData) applyAddress();
        })
        .catch(() => {
          if (nomData && bdcData) applyAddress();
        });

      // 2. BigDataCloud Client Reverse Geocoder
      fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=en`)
        .then(r => r.json())
        .then(bdc => {
          bdcData = bdc;
          if (arcData && nomData) applyAddress();
        })
        .catch(() => {
          if (arcData && nomData) applyAddress();
        });

      // 3. OSM Nominatim
      fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(r => r.json())
        .then(nom => {
          nomData = nom;
          applyAddress();
        })
        .catch(() => {
          if (arcData || bdcData) applyAddress();
        });

      setTimeout(() => { applyAddress(); }, 4500);
    }

    function profOpenGoogleMaps() {
      let lat = parseFloat(document.getElementById('prof-lat').value);
      let lng = parseFloat(document.getElementById('prof-lng').value);
      const gmapsUrl = (!isNaN(lat) && !isNaN(lng) && lat !== 0) ? `https://www.google.com/maps/@${lat},${lng},16z` : `https://www.google.com/maps`;
      window.open(gmapsUrl, '_blank');
      showProfileToast('📍 On Google Maps: Right-click location, copy coordinates, and paste into search!');
    }

    function profSearchLocation() {
      const input = document.getElementById('prof-map-search-input');
      const val = input ? input.value.trim() : '';
      if (!val) {
        showProfileToast('Please enter an area name or coordinates', true);
        return;
      }

      // 1. Direct coordinates
      let coordMatch = val.match(/^(-?\d{1,2}\.\d+)[,\s]+(-?\d{1,3}\.\d+)$/);
      if (coordMatch) {
        const lat = parseFloat(coordMatch[1]);
        const lng = parseFloat(coordMatch[2]);
        initProfMap(lat, lng, 16);
        updateFormCoordinates(lat, lng);
        reverseGeocode(lat, lng);
        showProfileToast(`Location pinned: ${lat.toFixed(4)}, ${lng.toFixed(4)}`);
        return;
      }

      // 2. Google Maps URL
      let atMatch = val.match(/@(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/);
      if (atMatch) {
        const lat = parseFloat(atMatch[1]);
        const lng = parseFloat(atMatch[2]);
        initProfMap(lat, lng, 16);
        updateFormCoordinates(lat, lng);
        reverseGeocode(lat, lng);
        showProfileToast(`Location pinned: ${lat.toFixed(4)}, ${lng.toFixed(4)}`);
        return;
      }

      // 3. Universal India-wide search + global fallback
      fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(val)}&format=json&countrycodes=in&limit=1`, {
        headers: { 'Accept-Language': 'en' }
      })
      .then(r => r.json())
      .then(res => {
        if (res && res.length > 0) {
          const lat = parseFloat(res[0].lat);
          const lng = parseFloat(res[0].lon);
          initProfMap(lat, lng, 16);
          updateFormCoordinates(lat, lng);
          reverseGeocode(lat, lng);
          showProfileToast(`Found: ${res[0].display_name.split(',')[0]}`);
        } else {
          // Global fallback
          fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(val)}&format=json&limit=1`, {
            headers: { 'Accept-Language': 'en' }
          })
          .then(r2 => r2.json())
          .then(res2 => {
            if (res2 && res2.length > 0) {
              const lat = parseFloat(res2[0].lat);
              const lng = parseFloat(res2[0].lon);
              initProfMap(lat, lng, 16);
              updateFormCoordinates(lat, lng);
              reverseGeocode(lat, lng);
              showProfileToast(`Found: ${res2[0].display_name.split(',')[0]}`);
            } else {
              showProfileToast('Location not found. Try pasting Google Maps coordinates.', true);
            }
          });
        }
      })
      .catch(() => {
        showProfileToast('Search service unavailable. Please try again.', true);
      });
    }

    function profLocateMe() {
      const btn = document.getElementById('prof-locate-btn');
      btn.innerHTML = '<ion-icon name="sync-outline" class="spin"></ion-icon> Locating...';

      function fallbackBdcClient() {
        fetch('https://api.bigdatacloud.net/data/reverse-geocode-client?localityLanguage=en')
          .then(r => r.json())
          .then(bdc => {
            btn.innerHTML = '<ion-icon name="locate-outline"></ion-icon> Use Live Location';
            const lat = parseFloat(bdc.latitude) || 22.6100;
            const lng = parseFloat(bdc.longitude) || 88.4400;
            initProfMap(lat, lng, 16);
            updateFormCoordinates(lat, lng);
            reverseGeocode(lat, lng);
            showProfileToast('Location detected successfully!');
          })
          .catch(() => {
            btn.innerHTML = '<ion-icon name="locate-outline"></ion-icon> Use Live Location';
            showProfileToast('Could not access device location. Please search your area or paste Google Maps coordinates.', true);
          });
      }

      if (!navigator.geolocation) {
        fallbackBdcClient();
        return;
      }

      navigator.geolocation.getCurrentPosition(
        pos => {
          btn.innerHTML = '<ion-icon name="locate-outline"></ion-icon> Use Live Location';
          const lat = pos.coords.latitude;
          const lng = pos.coords.longitude;
          initProfMap(lat, lng, 16);
          updateFormCoordinates(lat, lng);
          reverseGeocode(lat, lng);
          showProfileToast('Location detected successfully!');
        },
        err => {
          fallbackBdcClient();
        },
        { enableHighAccuracy: true, timeout: 6000, maximumAge: 10000 }
      );
    }

    function focusAddressForm() {
      switchProfileTab('addresses');
      document.getElementById('address-form-box').scrollIntoView({ behavior: 'smooth' });
    }

    function profSaveAddress(e) {
      e.preventDefault();
      const submitBtn = document.getElementById('prof-submit-btn');
      submitBtn.disabled = true;

      const formData = new FormData();
      formData.append('ajax_action', 'profile_save_address');
      formData.append('addr_id', document.getElementById('prof-addr-id').value);
      formData.append('label', document.getElementById('prof-label').value.trim());
      formData.append('address', document.getElementById('prof-address').value.trim());
      formData.append('landmark', document.getElementById('prof-landmark').value.trim());
      formData.append('pincode', document.getElementById('prof-pincode').value.trim());
      formData.append('latitude', document.getElementById('prof-lat').value);
      formData.append('longitude', document.getElementById('prof-lng').value);

      fetch('./user-profile.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        submitBtn.disabled = false;
        if (data.status === 'ok') {
          showProfileToast(data.message || 'Address saved!');
          setTimeout(() => location.reload(), 800);
        } else {
          showProfileToast(data.message || 'Failed to save address', true);
        }
      })
      .catch(() => {
        submitBtn.disabled = false;
        showProfileToast('Server error saving address.', true);
      });
    }

    function profEditAddress(addr) {
      switchProfileTab('addresses');
      document.getElementById('addr-form-heading').innerHTML = '<ion-icon name="create-outline"></ion-icon> Edit Address';
      document.getElementById('prof-addr-id').value = addr.id;
      document.getElementById('prof-label').value = addr.label || 'Home';
      document.getElementById('prof-address').value = addr.address;
      document.getElementById('prof-landmark').value = addr.landmark || '';
      document.getElementById('prof-pincode').value = addr.pincode;
      document.getElementById('prof-lat').value = addr.latitude || '';
      document.getElementById('prof-lng').value = addr.longitude || '';
      document.getElementById('prof-cancel-edit-btn').style.display = 'inline-flex';
      document.getElementById('prof-submit-btn').innerHTML = '<ion-icon name="save-outline"></ion-icon> Update Address';

      const lat = parseFloat(addr.latitude);
      const lng = parseFloat(addr.longitude);
      if (!isNaN(lat) && !isNaN(lng) && lat !== 0) {
        initProfMap(lat, lng, 15);
      } else {
        initProfMap(20.5937, 78.9629, 5);
      }

      document.getElementById('address-form-box').scrollIntoView({ behavior: 'smooth' });
    }

    function profCancelEdit() {
      document.getElementById('addr-form-heading').innerHTML = '<ion-icon name="map-outline"></ion-icon> Add New Address';
      document.getElementById('add-address-form').reset();
      document.getElementById('prof-addr-id').value = '';
      document.getElementById('prof-lat').value = '';
      document.getElementById('prof-lng').value = '';
      document.getElementById('prof-cancel-edit-btn').style.display = 'none';
      document.getElementById('prof-submit-btn').innerHTML = '<ion-icon name="save-outline"></ion-icon> Save Address';
      if (document.getElementById('prof-map-search-input')) {
        document.getElementById('prof-map-search-input').value = '';
      }
    }

    function profDeleteAddress(id) {
      if (!confirm('Are you sure you want to delete this address from your address book?')) return;
      const formData = new FormData();
      formData.append('ajax_action', 'profile_delete_address');
      formData.append('addr_id', id);

      fetch('./user-profile.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            showProfileToast(data.message || 'Address deleted.');
            setTimeout(() => location.reload(), 600);
          } else {
            showProfileToast(data.message || 'Failed to delete address.', true);
          }
        });
    }

    function profSetDefault(id) {
      const formData = new FormData();
      formData.append('ajax_action', 'profile_set_default');
      formData.append('addr_id', id);

      fetch('./user-profile.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            showProfileToast('Default address updated!');
            setTimeout(() => location.reload(), 600);
          }
        });
    }
  </script>
</body>
</html>
