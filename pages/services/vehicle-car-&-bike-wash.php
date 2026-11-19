<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$bPath = file_exists(__DIR__ . '/../../../backend/api/bootstrap.php')
    ? __DIR__ . '/../../../backend/api/bootstrap.php'
    : (file_exists(__DIR__ . '/../../../../backend/api/bootstrap.php') 
        ? __DIR__ . '/../../../../backend/api/bootstrap.php' 
        : null);
if ($bPath && file_exists($bPath)) { require_once $bPath; }
header('Content-Type: text/html; charset=utf-8');

// Verify authentication
$isLoggedIn = isset($_SESSION['user_id']);

// Auto-redirect if not logged in
if (!$isLoggedIn) {
  $target_url = $_SERVER['REQUEST_URI'] ?? './vehicle-car-&-bike-wash.php';
  header("Location: ../../auth/auth.php?redirect=" . urlencode($target_url));
  exit;
}

// Connect to DB
$db = class_exists('App\Helpers\DB') ? \App\Helpers\DB::getConnection() : null;
if (!$db && file_exists(__DIR__ . '/../../common/db_connection.php')) {
  require_once __DIR__ . '/../../common/db_connection.php';
  if (class_exists('ServEasyWebDB')) {
    $db = ServEasyWebDB::connect();
  }
}

// Fetch convenience fee from platform_settings in serveasy-admin
$initial_convenience_fee = 20.00;
$initial_fee_type = 'fixed';
$initial_fee_active = 1;
$initial_fee_scope = 'all';
$initial_applicable_services = [];
if ($db) {
  try {
    $fee_stmt = $db->query("SELECT fee_amount, fee_type, is_active, service_scope, applicable_services FROM `platform_settings` WHERE setting_key = 'convenience_fee' AND country_code = 'IN' LIMIT 1");
    if ($fee_stmt) {
      $fee_row = $fee_stmt->fetch(PDO::FETCH_ASSOC);
      if ($fee_row) {
        $initial_convenience_fee = (float)($fee_row['fee_amount'] ?? 20.00);
        $initial_fee_type = ($fee_row['fee_type'] === 'percentage') ? 'percentage' : 'fixed';
        $initial_fee_active = !empty($fee_row['is_active']) ? 1 : 0;
        $initial_fee_scope = $fee_row['service_scope'] ?? 'all';
        if (!empty($fee_row['applicable_services'])) {
          $decoded = json_decode($fee_row['applicable_services'], true);
          $initial_applicable_services = is_array($decoded) ? $decoded : [];
        }
      }
    }
  } catch (\Throwable $t) { }
}

// Check if vehicle wash subcategories are within fee scope
$is_vehicle_in_fee_scope = ($initial_fee_scope === 'all') 
  || in_array('car-wash', $initial_applicable_services) 
  || in_array('bike-wash', $initial_applicable_services)
  || in_array('vehicle-repair', $initial_applicable_services)
  || in_array('others-wash-services', $initial_applicable_services)
  || in_array('vehicle-services', $initial_applicable_services)
  || in_array('1', $initial_applicable_services)
  || in_array('2', $initial_applicable_services)
  || in_array(1, $initial_applicable_services)
  || in_array(2, $initial_applicable_services);

// -------------------------------------------------------
// AJAX: Save customer address → user_addresses
// Table created by migration 07_create_user_addresses_table.sql
// -------------------------------------------------------
$rawAddressInput = file_get_contents('php://input');
$jsonAddressReq = !empty($rawAddressInput) ? json_decode($rawAddressInput, true) : null;
$addressReq = is_array($jsonAddressReq) ? array_merge($_REQUEST, $jsonAddressReq) : $_REQUEST;
$addressAction = $addressReq['ajax_action'] ?? $addressReq['action'] ?? null;

if ($addressAction === 'save_user_address') {
  header('Content-Type: application/json');
  if (!$db) {
    if (file_exists(__DIR__ . '/../../common/ApiBridge.php')) {
      require_once __DIR__ . '/../../common/ApiBridge.php';
      $payload = [
        'action'    => 'save_user_address',
        'label'     => trim($addressReq['label'] ?? 'Home'),
        'address'   => trim($addressReq['address'] ?? ''),
        'landmark'  => trim($addressReq['landmark'] ?? ''),
        'pincode'   => trim($addressReq['pincode'] ?? ''),
        'latitude'  => $addressReq['latitude'] ?? ($addressReq['lat'] ?? ''),
        'longitude' => $addressReq['longitude'] ?? ($addressReq['lng'] ?? ''),
      ];
      $apiRes = \Frontend\ApiBridge::callApi('web-portal-api.php', 'POST', $payload);
      if (is_array($apiRes)) {
        echo json_encode($apiRes);
        exit;
      }
    }
    echo json_encode(['status' => 'error', 'message' => 'Unable to reach Central Backend API to save address.']);
    exit;
  }
  try {
    $uid = (int) ($_SESSION['user_id'] ?? $addressReq['user_id'] ?? 0);
    $address = trim($addressReq['address'] ?? '');
    $landmark = trim($addressReq['landmark'] ?? '');
    $pincode = trim($addressReq['pincode'] ?? '');
    $lat = !empty($addressReq['latitude']) ? (float) $addressReq['latitude'] : (!empty($addressReq['lat']) ? (float)$addressReq['lat'] : null);
    $lng = !empty($addressReq['longitude']) ? (float) $addressReq['longitude'] : (!empty($addressReq['lng']) ? (float)$addressReq['lng'] : null);
    $label = trim($addressReq['label'] ?? 'Home');

    if ($uid <= 0) {
      echo json_encode(['status' => 'error', 'message' => 'User authentication required']);
      exit;
    }

    if ($address === '') {
      echo json_encode(['status' => 'skip', 'message' => 'Empty address — nothing saved']);
      exit;
    }

    // Check if identical address already exists for this user to avoid duplicates
    $checkStmt = $db->prepare("
        SELECT id, label, address, landmark, pincode, latitude, longitude
        FROM `user_addresses`
        WHERE user_id = :uid 
          AND LOWER(TRIM(address)) = LOWER(TRIM(:address))
          AND pincode = :pincode
        LIMIT 1
    ");
    $checkStmt->execute([
      ':uid' => $uid,
      ':address' => $address,
      ':pincode' => $pincode
    ]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
      if ($lat !== null && $lng !== null) {
        $upStmt = $db->prepare("
          UPDATE `user_addresses`
          SET landmark = :landmark, latitude = :lat, longitude = :lng, updated_at = NOW()
          WHERE id = :id
        ");
        $upStmt->execute([
          ':landmark' => $landmark,
          ':lat' => $lat,
          ':lng' => $lng,
          ':id' => $existing['id']
        ]);
        $existing['landmark'] = $landmark;
        $existing['latitude'] = $lat;
        $existing['longitude'] = $lng;
      }
      echo json_encode([
        'status' => 'ok',
        'already_exists' => true,
        'id' => (int)$existing['id'],
        'address' => [
          'id' => (int)$existing['id'],
          'label' => $existing['label'],
          'address' => $existing['address'],
          'landmark' => $existing['landmark'],
          'pincode' => $existing['pincode'],
          'latitude' => $existing['latitude'],
          'longitude' => $existing['longitude']
        ]
      ]);
      exit;
    }

    // Insert new address
    $stmt = $db->prepare("
        INSERT INTO `user_addresses`
            (user_id, label, address, landmark, pincode, latitude, longitude, is_default)
        VALUES
            (:uid, :label, :address, :landmark, :pincode, :lat, :lng, 0)
    ");
    $stmt->execute([
      ':uid' => $uid,
      ':label' => $label,
      ':address' => $address,
      ':landmark' => $landmark,
      ':pincode' => $pincode,
      ':lat' => $lat,
      ':lng' => $lng,
    ]);

    $newId = (int)$db->lastInsertId();

    echo json_encode([
      'status' => 'ok',
      'already_exists' => false,
      'id' => $newId,
      'address' => [
        'id' => $newId,
        'label' => $label,
        'address' => $address,
        'landmark' => $landmark,
        'pincode' => $pincode,
        'latitude' => $lat,
        'longitude' => $lng
      ]
    ]);
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

// Pre-fill Step 3 from the customer's saved default address in user_addresses
$currentUser = [];
$userAddresses = [];
$verifiedPhone = (!empty($_SESSION['phone']) && strpos($_SESSION['phone'], '@') === false) ? trim($_SESSION['phone']) : '';
$verifiedName = !empty($_SESSION['full_name']) ? trim($_SESSION['full_name']) : 'Verified Customer';
$verifiedEmail = $_SESSION['email'] ?? '';
if ($db) {
  try {
    $stmtUser = $db->prepare("
          SELECT address, landmark, pincode, latitude, longitude
          FROM `user_addresses`
          WHERE user_id = ? AND is_default = 1
          ORDER BY updated_at DESC
          LIMIT 1
      ");
    $stmtUser->execute([$_SESSION['user_id']]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmtAll = $db->prepare("
          SELECT id, label, address, landmark, pincode, latitude, longitude, is_default
          FROM `user_addresses`
          WHERE user_id = ?
          ORDER BY is_default DESC, updated_at DESC
      ");
    $stmtAll->execute([$_SESSION['user_id']]);
    $rawAddresses = $stmtAll->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $seenKeys = [];
    $userAddresses = [];
    foreach ($rawAddresses as $addr) {
      $key = strtolower(trim($addr['address'])) . '|' . trim($addr['pincode']);
      if (!isset($seenKeys[$key])) {
        $seenKeys[$key] = true;
        $userAddresses[] = $addr;
      }
    }

    // Pre-fetch verified profile attributes from site_user
    $userProfileStmt = $db->prepare("SELECT full_name, email_address, phone_number FROM `site_user` WHERE id = ? LIMIT 1");
    $userProfileStmt->execute([$_SESSION['user_id']]);
    $dbUserProfile = $userProfileStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    if (!empty($dbUserProfile['phone_number']) && strpos($dbUserProfile['phone_number'], '@') === false) {
      $verifiedPhone = trim($dbUserProfile['phone_number']);
    }
    if (!empty($dbUserProfile['full_name'])) {
      $verifiedName = trim($dbUserProfile['full_name']);
    }
    if (!empty($dbUserProfile['email_address'])) {
      $verifiedEmail = trim($dbUserProfile['email_address']);
    }
  } catch (Exception $e) {
    // Table may not exist in dev — graceful fallback
  }
}


$allSubcategories = [];
$allServices = [];
$providers = [];
$providerBranches = [];

if ($db) {
  try {
    // -------------------------------------------------------
    // 1. Fetch all subcategories for vehicle/car/bike services
    //    Subcategory IDs 1-7 = vehicle wash / repair / servicing
    // -------------------------------------------------------
    $stmtSub = $db->prepare("
        SELECT sc.id, sc.name, sc.slug, c.id as cat_id, c.name as category_name
        FROM `serveasy-subcategories` sc
        JOIN `serveasy-categories` c ON sc.category_id = c.id
        WHERE sc.is_active = 1
        ORDER BY sc.id ASC
    ");
    $stmtSub->execute();
    $allSubcategories = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

    // -------------------------------------------------------
    // 2. Fetch vehicle-wash-related services (subcategories 1-7)
    //    with their packages, provider info, and images
    // -------------------------------------------------------
    $stmtAll = $db->prepare("
        SELECT s.*,
               COALESCE(c.company_name, sp.full_name) AS business_name,
               4.5            AS provider_rating,
               sp.is_active   AS provider_available,
               sc.name        AS subcategory_name,
               sc.slug        AS subcategory_slug,
               COALESCE(c.company_name, sp.full_name) AS provider_company,
               sp.location_address AS provider_address,
               s.service_range_km
        FROM `serveasy-services` s
        JOIN `serveasy-providers` sp ON s.provider_id = sp.id
        LEFT JOIN `serveasy-company` c ON c.provider_id = sp.id
        JOIN `serveasy-subcategories` sc ON s.subcategory_id = sc.id
        WHERE s.is_active = 1
          AND s.approval_status = 'approved'
          AND sp.verified_status = 'approved'
          AND s.subcategory_id IN (1, 2, 3, 6, 7, 8)
        ORDER BY s.id ASC
    ");
    $stmtAll->execute();
    $allServices = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

    // Attach packages, addons and primary image for each service
    foreach ($allServices as &$s) {
      // Packages
      $stmtPkg = $db->prepare("
            SELECT id, name, description, price, features
            FROM `serveasy-service_packages`
            WHERE service_id = :id
            ORDER BY price ASC
        ");
      $stmtPkg->execute(['id' => $s['id']]);
      $s['packages'] = $stmtPkg->fetchAll(PDO::FETCH_ASSOC);

      // Addons
      $stmtAddons = $db->prepare("
            SELECT id, name, price
            FROM `serveasy-service_addons`
            WHERE service_id = :id
            ORDER BY id ASC
        ");
      $stmtAddons->execute(['id' => $s['id']]);
      $s['addons'] = $stmtAddons->fetchAll(PDO::FETCH_ASSOC);

      // Primary image
      $stmtImg = $db->prepare("
            SELECT image_url FROM `serveasy-service_images`
            WHERE service_id = :id AND is_primary = 1
            LIMIT 1
        ");
      $stmtImg->execute(['id' => $s['id']]);
      $imgRow = $stmtImg->fetch(PDO::FETCH_ASSOC);
      $s['primary_image'] = $imgRow ? $imgRow['image_url'] : null;
    }
    unset($s);

    // -------------------------------------------------------
    // 3. Unique providers who have at least one active vehicle service
    // -------------------------------------------------------
    $stmtProviders = $db->prepare("
        SELECT 
            sp.id, COALESCE(c.company_name, sp.full_name) AS business_name, 4.5 AS rating, sp.is_active AS is_available,
            COALESCE(c.company_name, sp.full_name) AS company_name, c.id AS company_id,
            (CASE WHEN c.id IS NOT NULL AND c.company_name IS NOT NULL AND TRIM(c.company_name) != '' THEN 0 ELSE 1 END) AS is_single_provider,
            sp.location_address, MAX(s.service_range_km) AS service_range_km,
            sp.full_name, sp.phone AS phone_number
        FROM `serveasy-providers` sp
        LEFT JOIN `serveasy-company` c ON c.provider_id = sp.id
        JOIN `serveasy-services` s ON s.provider_id = sp.id
        WHERE s.is_active = 1
          AND s.approval_status = 'approved'
          AND sp.verified_status = 'approved'
          AND s.subcategory_id IN (1, 2, 3, 6, 7, 8)
        GROUP BY sp.id
    ");
    $stmtProviders->execute();
    $providers = $stmtProviders->fetchAll(PDO::FETCH_ASSOC);

    // -------------------------------------------------------
    // 4. Provider branches for the "Visit Service Center" step
    // -------------------------------------------------------
    $stmtBranches = $db->prepare("
        SELECT b.id, b.branch_name, b.address, b.phone,
               COALESCE(c.company_name, sp.full_name) AS business_name,
               sp.id AS provider_sp_id,
               sp.id AS provider_id
        FROM `serveasy-provider-branches` b
        JOIN `serveasy-providers` sp ON b.provider_id = sp.id
        LEFT JOIN `serveasy-company` c ON c.provider_id = sp.id
        JOIN `serveasy-services` s ON s.provider_id = sp.id
        WHERE b.is_active = 1
          AND s.is_active = 1
          AND s.subcategory_id IN (1, 2, 3, 6, 7, 8)
        GROUP BY b.id
    ");
    $stmtBranches->execute();
    $providerBranches = $stmtBranches->fetchAll(PDO::FETCH_ASSOC);
  } catch (\Throwable $e) { }
} elseif (file_exists(__DIR__ . '/../../common/ApiBridge.php')) {
  require_once __DIR__ . '/../../common/ApiBridge.php';
  $initEndpoint = 'web-portal-api.php?action=vehicle_wash_init' . (!empty($_GET['booking_id']) ? '&booking_id=' . (int)$_GET['booking_id'] : '');
  $initData = \Frontend\ApiBridge::callApi($initEndpoint);
  if (!empty($initData['status']) && $initData['status'] === 'success') {
    if (!empty($initData['fee'])) {
      $initial_convenience_fee     = (float)($initData['fee']['fee_amount'] ?? 20.00);
      $initial_fee_type            = ($initData['fee']['fee_type'] ?? 'fixed') === 'percentage' ? 'percentage' : 'fixed';
      $initial_fee_active          = !empty($initData['fee']['is_active']) ? 1 : 0;
      $initial_fee_scope           = $initData['fee']['service_scope'] ?? 'all';
      $initial_applicable_services = is_array($initData['fee']['applicable_services'] ?? null) ? $initData['fee']['applicable_services'] : [];
      $is_vehicle_in_fee_scope     = ($initial_fee_scope === 'all')
        || in_array('car-wash', $initial_applicable_services)
        || in_array('bike-wash', $initial_applicable_services)
        || in_array('vehicle-repair', $initial_applicable_services)
        || in_array('others-wash-services', $initial_applicable_services)
        || in_array('vehicle-services', $initial_applicable_services)
        || in_array('1', $initial_applicable_services)
        || in_array('2', $initial_applicable_services);
    }
    $currentUser      = is_array($initData['currentUser'] ?? null) ? $initData['currentUser'] : [];
    $userAddresses    = is_array($initData['userAddresses'] ?? null) ? $initData['userAddresses'] : [];
    $allSubcategories = is_array($initData['allSubcategories'] ?? null) ? $initData['allSubcategories'] : [];
    $allServices      = is_array($initData['allServices'] ?? null) ? $initData['allServices'] : [];
    $providers        = is_array($initData['providers'] ?? null) ? $initData['providers'] : [];
    $providerBranches = is_array($initData['providerBranches'] ?? null) ? $initData['providerBranches'] : [];
    if (!empty($initData['profile']) && is_array($initData['profile'])) {
      $p = $initData['profile'];
      if (!empty($p['phone_number']) && strpos($p['phone_number'], '@') === false) $verifiedPhone = trim($p['phone_number']);
      if (!empty($p['full_name'])) $verifiedName = trim($p['full_name']);
      if (!empty($p['email_address'])) $verifiedEmail = trim($p['email_address']);
    }
    $apiPreselectedBooking = $initData['preselectedBooking'] ?? null;
  }
}

// -------------------------------------------------------
// 5. Build wizard category map from subcategories
//    Maps wizard category slug → subcategory IDs
// -------------------------------------------------------
$categoryMap = [
  'car_wash' => [1],          // Car Wash
  'bike_wash' => [2],         // Bike Wash
  'vehicle_repair' => [3],    // Vehicle Repair
  'vehicle_service' => [6, 7, 8], // Vehicle Servicing, Parts, Garage
  'home_vehicle' => [6],      // Home Vehicle Service (doorstep)
];

// -------------------------------------------------------
// 6. Optional: Pre-selected service, vehicle or booking
// -------------------------------------------------------
$preselectedServiceId = isset($_GET['service_id']) ? (int) $_GET['service_id'] : null;
$preselectedPackageId = isset($_GET['package_id']) ? (int) $_GET['package_id'] : null;
$preselectedServiceSlug = isset($_GET['service']) ? trim($_GET['service']) : null;
$preselectedSubcat = isset($_GET['subcat']) ? trim($_GET['subcat']) : null;
$preselectedVehicle = isset($_GET['vehicle']) ? trim($_GET['vehicle']) : null;
$isSelectVehicleMode = (isset($_GET['select_vehicle']) && $_GET['select_vehicle'] == '1');

$preselectedBooking = $apiPreselectedBooking ?? null;
if (isset($_GET['booking_id']) && $db) {
  try {
    $stmtB = $db->prepare("
          SELECT b.*, s.name as service_name, COALESCE(c.company_name, sp.full_name) as provider_name
          FROM vehicle_wash b
          JOIN `serveasy-services` s ON b.service_id = s.id
          JOIN `serveasy-providers` sp ON b.provider_id = sp.id
          LEFT JOIN `serveasy-company` c ON c.provider_id = sp.id
          WHERE b.id = :id AND b.customer_id = :cust_id
      ");
    $stmtB->execute(['id' => (int) $_GET['booking_id'], 'cust_id' => (int) $_SESSION['user_id']]);
    $preselectedBooking = $stmtB->fetch(PDO::FETCH_ASSOC);
  } catch (\Throwable $e) { }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ServEasy - Vehicle Car & Bike Wash Wizard</title>

  <base href="../">

  <!-- Solution A: Customer Web Independent Config & API Bridge (Cache-Busted for Hostinger) -->
  <script src="../config.js?v=20261118v2" onerror="this.onerror=null;this.src='../../config.js?v=20261118v2';"></script>
  <script src="./assets/js/serveasy-api-bridge.js?v=20261118v2"></script>

  <!-- Favicon -->
  <link rel="shortcut icon" href="./assets/favicon.svg" type="image/svg+xml">

  <!-- Font & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:wght@300;400;500;700;900&display=swap"
    rel="stylesheet">

  <!-- Core Stylesheet imports -->
  <link rel="stylesheet" href="./assets/css/main.css">
  <link rel="stylesheet" href="./assets/css/global.css">
  <link rel="stylesheet" href="./assets/css/home.css">
  <link rel="stylesheet" href="./assets/css/notify.css">

  <style>
    .wizard-container {
      max-width: 900px;
      margin: 40px auto;
      padding: 0 15px;
      background: transparent !important;
      border: none !important;
      box-shadow: none !important;
      backdrop-filter: none !important;
      -webkit-backdrop-filter: none !important;
    }

    .wizard-card-glass {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 24px;
      padding: 40px;
      color: var(--white);
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .wizard-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .wizard-title {
      font-size: 2.2rem;
      font-family: 'Playfair Display', serif;
      margin-bottom: 8px;
      background: linear-gradient(135deg, #a78bfa, #8b5cf6, #3b82f6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .wizard-subtitle {
      color: #94a3b8;
      font-size: 0.95rem;
    }

    /* Progress Bar */
    .progress-bar-wrapper {
      background: rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      height: 8px;
      width: 100%;
      margin: 20px 0 35px 0;
      position: relative;
      overflow: hidden;
    }

    .progress-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #8b5cf6, #3b82f6);
      width: 7.7%;
      /* 1/13 initially */
      transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .step-indicator-text {
      text-align: center;
      font-weight: 700;
      font-size: 0.9rem;
      color: #8b5cf6;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Wizard Step Content */
    .wizard-step {
      display: none;
      animation: fadeIn 0.4s ease forwards;
    }

    .wizard-step.active {
      display: block;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(8px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Selection Grid & Cards */
    .selection-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin: 25px 0;
    }

    .selection-card {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 24px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
    }

    .selection-card:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(139, 92, 246, 0.4);
      transform: translateY(-4px);
    }

    .selection-card.selected {
      background: rgba(139, 92, 246, 0.15);
      border-color: #8b5cf6;
      box-shadow: 0 0 20px rgba(139, 92, 246, 0.2);
    }

    .selection-icon {
      font-size: 2.8rem;
      color: #a78bfa;
    }

    .selection-title {
      font-size: 1.1rem;
      font-weight: 700;
    }

    .selection-desc {
      font-size: 0.85rem;
      color: #94a3b8;
    }

    /* Horizontal Buttons Footer */
    .wizard-buttons {
      display: flex;
      justify-content: space-between;
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-wizard-prev {
      background: rgba(255, 255, 255, 0.08);
      color: var(--white);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 30px;
      padding: 12px 28px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-wizard-prev:hover {
      background: rgba(255, 255, 255, 0.15);
    }

    .btn-wizard-next {
      background: linear-gradient(135deg, #8b5cf6, #3b82f6);
      color: var(--white);
      border: none;
      border-radius: 30px;
      padding: 12px 32px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .btn-wizard-next:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
    }

    .btn-wizard-next:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none !important;
      box-shadow: none !important;
    }

    /* Specific Form Styles */
    .form-group-glass {
      margin-bottom: 20px;
    }

    .form-label-glass {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      font-size: 0.95rem;
      color: #cbd5e1;
    }

    .form-input-glass {
      width: 100%;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 12px 16px;
      color: var(--white);
      font-size: 1rem;
      transition: all 0.2s;
    }

    .form-input-glass:focus {
      outline: none;
      border-color: #8b5cf6;
      background: rgba(255, 255, 255, 0.08);
      box-shadow: 0 0 10px rgba(139, 92, 246, 0.15);
    }

    /* Row Form */
    .form-row-glass {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    @media (max-width: 600px) {
      .form-row-glass {
        grid-template-columns: 1fr;
        gap: 0;
      }
    }

    /* Extras List */
    .extras-list {
      display: flex;
      flex-direction: column;
      gap: 14px;
      margin: 20px 0;
    }

    .extras-item-glass {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 14px;
      padding: 16px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      transition: all 0.2s;
    }

    .extras-item-glass:hover {
      background: rgba(255, 255, 255, 0.08);
    }

    .extras-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .extras-checkbox {
      width: 18px;
      height: 18px;
      accent-color: #8b5cf6;
      cursor: pointer;
    }

    .extras-name {
      font-weight: 600;
    }

    .extras-price {
      font-weight: 700;
      color: #10b981;
    }

    /* Date & Time Slot Grid */
    .slots-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 12px;
      margin-top: 15px;
    }

    .slot-pill {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 12px 10px;
      text-align: center;
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 500;
      transition: all 0.2s;
    }

    .slot-pill:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .slot-pill.selected {
      background: #8b5cf6;
      border-color: #8b5cf6;
      font-weight: 700;
      box-shadow: 0 0 10px rgba(139, 92, 246, 0.3);
    }

    .slot-pill.slot-booked {
      opacity: 0.38 !important;
      cursor: not-allowed !important;
      background: rgba(239, 68, 68, 0.08) !important;
      border-color: rgba(239, 68, 68, 0.35) !important;
      color: #fca5a5 !important;
      text-decoration: line-through;
    }

    .slot-booked-badge {
      display: inline-block;
      margin-left: 8px;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 6px;
      background: rgba(239, 68, 68, 0.25);
      color: #f87171;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      vertical-align: middle;
      text-decoration: none !important;
    }

    /* OTP Simulator Section */
    .otp-sim-box {
      background: rgba(139, 92, 246, 0.08);
      border: 1px dashed rgba(139, 92, 246, 0.4);
      border-radius: 16px;
      padding: 20px;
      margin-top: 15px;
      text-align: center;
    }

    .otp-sim-code {
      font-size: 1.6rem;
      font-weight: 800;
      letter-spacing: 4px;
      color: #a78bfa;
      margin: 10px 0;
    }

    /* Summary Breakdown */
    .summary-box-glass {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 24px;
      margin: 20px 0;
    }

    .summary-heading {
      font-size: 1.1rem;
      font-weight: 700;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding-bottom: 8px;
      margin-bottom: 12px;
      color: #a78bfa;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      font-size: 0.95rem;
      margin-bottom: 8px;
      color: #cbd5e1;
    }

    .summary-total-row {
      display: flex;
      justify-content: space-between;
      font-size: 1.3rem;
      font-weight: 800;
      color: #10b981;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding-top: 12px;
      margin-top: 12px;
    }

    /* Success / Tracking Screen */
    .success-icon-large {
      font-size: 5rem;
      color: #10b981;
      margin: 20px auto;
      animation: pulse 2s infinite;
      display: block;
      text-align: center;
    }

    /* ── Provider Acceptance Radar Animation (Step 11) ── */
    .provider-radar-container {
      position: relative;
      width: 150px;
      height: 150px;
      margin: 10px auto 20px auto;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .radar-ring {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      border-radius: 50%;
      border: 1px solid rgba(139, 92, 246, 0.4);
      pointer-events: none;
    }

    .radar-ring-1 {
      width: 80px;
      height: 80px;
      animation: radarPulse 2.4s cubic-bezier(0.2, 0.8, 0.2, 1) infinite;
    }

    .radar-ring-2 {
      width: 80px;
      height: 80px;
      animation: radarPulse 2.4s cubic-bezier(0.2, 0.8, 0.2, 1) 0.8s infinite;
    }

    .radar-ring-3 {
      width: 80px;
      height: 80px;
      animation: radarPulse 2.4s cubic-bezier(0.2, 0.8, 0.2, 1) 1.6s infinite;
    }

    @keyframes radarPulse {
      0% {
        width: 70px;
        height: 70px;
        opacity: 0.9;
        border-color: rgba(139, 92, 246, 0.7);
        box-shadow: 0 0 10px rgba(139, 92, 246, 0.5);
      }
      100% {
        width: 170px;
        height: 170px;
        opacity: 0;
        border-color: rgba(59, 130, 246, 0);
        box-shadow: 0 0 25px rgba(59, 130, 246, 0);
      }
    }

    .radar-orbital-spinner {
      position: absolute;
      top: 50%;
      left: 50%;
      margin-top: -55px;
      margin-left: -55px;
      width: 110px;
      height: 110px;
      border-radius: 50%;
      border: 2px solid rgba(255, 255, 255, 0.05);
      border-top: 2px solid #a78bfa;
      border-right: 2px solid #3b82f6;
      animation: radarSpin 1.8s linear infinite;
      transform-origin: center center;
      pointer-events: none;
    }

    .radar-orbital-spinner::after {
      content: '';
      position: absolute;
      top: 4px;
      right: 12px;
      width: 8px;
      height: 8px;
      background: #60a5fa;
      border-radius: 50%;
      box-shadow: 0 0 10px #60a5fa, 0 0 20px #3b82f6;
    }

    .radar-orbital-counter {
      position: absolute;
      top: 50%;
      left: 50%;
      margin-top: -46px;
      margin-left: -46px;
      width: 92px;
      height: 92px;
      border-radius: 50%;
      border: 2px dashed rgba(167, 139, 250, 0.35);
      border-bottom: 2px solid #06b6d4;
      animation: radarSpinReverse 3.2s linear infinite;
      transform-origin: center center;
      pointer-events: none;
    }

    @keyframes radarSpin {
      0% {
        transform: rotate(0deg);
      }
      100% {
        transform: rotate(360deg);
      }
    }

    @keyframes radarSpinReverse {
      0% {
        transform: rotate(360deg);
      }
      100% {
        transform: rotate(0deg);
      }
    }

    .radar-hub {
      position: relative;
      z-index: 3;
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(30, 27, 75, 0.85), rgba(15, 23, 42, 0.95));
      border: 2px solid rgba(167, 139, 250, 0.5);
      box-shadow: 0 0 25px rgba(139, 92, 246, 0.4), inset 0 0 15px rgba(139, 92, 246, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #a78bfa;
      font-size: 2.2rem;
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .radar-hub ion-icon {
      display: inline-block;
      line-height: 1;
    }

    .radar-hub.pending ion-icon {
      animation: hubIconSpin 2.5s cubic-bezier(0.4, 0, 0.2, 1) infinite;
      transform-origin: center center;
    }

    @keyframes hubIconSpin {
      0% {
        transform: rotate(0deg);
      }
      100% {
        transform: rotate(360deg);
      }
    }

    .radar-hub.accepted {
      background: linear-gradient(135deg, rgba(6, 78, 59, 0.85), rgba(15, 23, 42, 0.95));
      border-color: #10b981;
      color: #10b981;
      box-shadow: 0 0 35px rgba(16, 185, 129, 0.5), inset 0 0 20px rgba(16, 185, 129, 0.25);
      animation: hubSuccessPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }

    .radar-hub.accepted ion-icon {
      animation: none;
    }

    .radar-hub.rejected {
      background: linear-gradient(135deg, rgba(127, 29, 29, 0.85), rgba(15, 23, 42, 0.95));
      border-color: #ef4444;
      color: #ef4444;
      box-shadow: 0 0 35px rgba(239, 68, 68, 0.5), inset 0 0 20px rgba(239, 68, 68, 0.25);
      animation: hubErrorShake 0.5s ease forwards;
    }

    .radar-hub.rejected ion-icon {
      animation: none;
    }

    @keyframes hubSuccessPop {
      0% { transform: scale(0.8); }
      50% { transform: scale(1.15); }
      100% { transform: scale(1); }
    }

    @keyframes hubErrorShake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-6px); }
      40%, 80% { transform: translateX(6px); }
    }

    /* Radar Provider Status Card */
    .radar-provider-card {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 10px 18px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 30px;
      margin: 12px auto;
      backdrop-filter: blur(8px);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .radar-provider-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #8b5cf6, #3b82f6);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-weight: 700;
      font-size: 0.9rem;
      border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .radar-live-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      background: rgba(59, 130, 246, 0.15);
      border: 1px solid rgba(59, 130, 246, 0.35);
      color: #60a5fa;
    }

    .radar-live-badge.accepted {
      background: rgba(16, 185, 129, 0.15);
      border-color: rgba(16, 185, 129, 0.4);
      color: #34d399;
    }

    .radar-live-badge.rejected {
      background: rgba(239, 68, 68, 0.15);
      border-color: rgba(239, 68, 68, 0.4);
      color: #f87171;
    }

    .radar-ping-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #60a5fa;
      box-shadow: 0 0 8px #60a5fa;
      animation: livePing 1.5s ease-in-out infinite;
    }

    .radar-live-badge.accepted .radar-ping-dot {
      background: #34d399;
      box-shadow: 0 0 8px #34d399;
    }

    .radar-live-badge.rejected .radar-ping-dot {
      background: #f87171;
      box-shadow: 0 0 8px #f87171;
    }

    @keyframes livePing {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(1.3); }
    }

    @keyframes pulse {
      0% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.08);
      }

      100% {
        transform: scale(1);
      }
    }

    /* Stepper Tracker */
    .tracker-stepper {
      display: flex;
      flex-direction: column;
      gap: 20px;
      margin: 30px 0;
      position: relative;
    }

    .tracker-stepper::before {
      content: '';
      position: absolute;
      left: 17px;
      top: 10px;
      bottom: 10px;
      width: 2px;
      background: rgba(255, 255, 255, 0.1);
      z-index: 1;
    }

    .tracker-step-item {
      display: flex;
      align-items: center;
      gap: 16px;
      position: relative;
      z-index: 2;
    }

    .tracker-circle {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      border: 2px solid rgba(255, 255, 255, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      color: #94a3b8;
      transition: all 0.3s;
    }

    .tracker-step-item.completed .tracker-circle {
      background: #10b981;
      border-color: #10b981;
      color: var(--white);
    }

    .tracker-step-item.active .tracker-circle {
      background: #8b5cf6;
      border-color: #8b5cf6;
      color: var(--white);
      box-shadow: 0 0 12px rgba(139, 92, 246, 0.6);
    }

    .tracker-step-label {
      font-weight: 600;
      color: #94a3b8;
    }

    .tracker-step-item.completed .tracker-step-label,
    .tracker-step-item.active .tracker-step-label {
      color: var(--white);
    }

    .tracker-step-time {
      font-size: 0.8rem;
      color: #64748b;
      margin-left: auto;
    }

    /* Simulation Control Panel */
    .sim-panel {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 20px;
      margin-top: 25px;
    }

    .sim-btn-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
      gap: 10px;
      margin-top: 12px;
    }

    .btn-sim {
      background: rgba(255, 255, 255, 0.08);
      color: #cbd5e1;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      padding: 10px 8px;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-sim:hover:not(:disabled) {
      background: #8b5cf6;
      color: var(--white);
      border-color: #8b5cf6;
    }

    .btn-sim:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }

    /* Notification Simulation buttons */
    .notif-btn-row {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin: 15px 0;
      flex-wrap: wrap;
    }

    .btn-notif {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 8px 16px;
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      gap: 6px;
      color: #cbd5e1;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-notif:hover {
      background: rgba(255, 255, 255, 0.12);
      color: var(--white);
    }

    /* Star Ratings */
    .star-rating {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin: 20px 0;
      font-size: 2.2rem;
    }

    .star-rating ion-icon {
      color: rgba(255, 255, 255, 0.15);
      cursor: pointer;
      transition: transform 0.2s, color 0.2s;
    }

    .star-rating ion-icon:hover {
      transform: scale(1.15);
    }

    .star-rating ion-icon.selected {
      color: #fbbf24;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* ── Step 3 Map Styles ── */
    #loc-map {
      width: 100%;
      height: 300px;
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      margin: 16px 0;
      z-index: 1;
      background: #1a1a2e;
    }

    .map-locate-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: linear-gradient(135deg, #8b5cf6, #3b82f6);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 9px 18px;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
      margin-bottom: 14px;
    }

    .map-locate-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 14px rgba(139, 92, 246, 0.4);
    }

    .map-locate-btn:disabled {
      opacity: 0.55;
      cursor: not-allowed;
      transform: none;
    }

    .map-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 20px;
      margin-left: 10px;
    }

    .map-status-badge.detecting {
      background: rgba(59, 130, 246, 0.15);
      color: #60a5fa;
      border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .map-status-badge.found {
      background: rgba(16, 185, 129, 0.15);
      color: #34d399;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .map-status-badge.error {
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .provider-addr-card {
      background: rgba(139, 92, 246, 0.08);
      border: 1px solid rgba(139, 92, 246, 0.25);
      border-radius: 14px;
      padding: 14px 18px;
      margin-top: 18px;
      display: none;
    }

    .provider-addr-card.visible {
      display: block;
    }

    .provider-addr-label {
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #a78bfa;
      margin-bottom: 6px;
    }

    .provider-addr-value {
      font-size: 0.92rem;
      color: #e2e8f0;
      font-weight: 500;
    }

    .use-provider-addr-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-top: 10px;
      background: rgba(139, 92, 246, 0.18);
      border: 1px solid rgba(139, 92, 246, 0.4);
      color: #c4b5fd;
      border-radius: 8px;
      padding: 6px 14px;
      font-size: 0.8rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
    }

    .use-provider-addr-btn:hover {
      background: rgba(139, 92, 246, 0.3);
      color: #fff;
    }

    .map-hint {
      font-size: 0.78rem;
      color: #64748b;
      text-align: center;
      margin-top: 4px;
    }

    /* ── Saved Address Radios Styling ── */
    .saved-address-radio-item {
      display: flex !important;
      align-items: flex-start !important;
      gap: 16px !important;
      padding: 16px 20px !important;
      border-radius: 16px !important;
      background: rgba(255, 255, 255, 0.03) !important;
      border: 1px solid rgba(255, 255, 255, 0.08) !important;
      cursor: pointer !important;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
      position: relative;
      text-align: left !important;
      width: 100% !important;
      box-sizing: border-box !important;

      /* Explicitly reset heights to prevent text clipping */
      height: auto !important;
      min-height: unset !important;
      max-height: unset !important;
      overflow: visible !important;
    }
    
    .saved-address-radio-item:hover {
      background: rgba(255, 255, 255, 0.07) !important;
      border-color: rgba(139, 92, 246, 0.4) !important;
      transform: translateY(-2px);
    }
    
    .saved-address-radio-item input[type="radio"] {
      display: inline-block !important;
      width: 20px !important;
      height: 20px !important;
      accent-color: #8b5cf6 !important;
      cursor: pointer !important;
      flex-shrink: 0 !important;
      margin: 4px 0 0 0 !important;
    }
    
    .saved-address-radio-item:has(input[type="radio"]:checked) {
      background: rgba(139, 92, 246, 0.1) !important;
      border-color: rgba(139, 92, 246, 0.8) !important;
      box-shadow: 0 8px 24px rgba(139, 92, 246, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }

    /* Override main.css global layout breaks */
    .saved-address-radio-item div {
      display: block !important;
      background: none !important;
      padding: 0 !important;
      margin: 0 !important;
      height: auto !important;
      min-height: unset !important;
      max-height: unset !important;
    }

    .saved-address-radio-item strong {
      display: inline-block !important;
      font-size: 0.95rem !important;
      color: #a78bfa !important;
      font-weight: 700 !important;
      margin: 0 !important;
      height: auto !important;
    }

    .saved-address-radio-item span {
      display: inline-block !important;
      font-size: 0.65rem !important;
      line-height: 1 !important;
      margin-left: 8px !important;
      vertical-align: middle !important;
      background: rgba(139, 92, 246, 0.2) !important;
      color: #c084fc !important;
      padding: 2px 8px !important;
      border-radius: 10px !important;
      border: 1px solid rgba(139, 92, 246, 0.3) !important;
      height: auto !important;
    }

    .saved-address-radio-item p {
      display: block !important;
      margin: 6px 0 !important;
      font-size: 0.88rem !important;
      color: #f1f5f9 !important;
      line-height: 1.5 !important;
      opacity: 0.9 !important;
      height: auto !important;
      min-height: unset !important;
      max-height: unset !important;
      white-space: normal !important;
      overflow: visible !important;
    }

    .saved-address-radio-item small {
      display: block !important;
      margin-top: 4px !important;
      font-size: 0.78rem !important;
      color: #94a3b8 !important;
      line-height: 1.4 !important;
      opacity: 0.75 !important;
      height: auto !important;
      min-height: unset !important;
      max-height: unset !important;
      white-space: normal !important;
      overflow: visible !important;
    }

    /* ── Location Option Cards Styling ── */
    .location-option-card {
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
      box-sizing: border-box !important;
    }
    
    .location-option-card:hover {
      background: rgba(255, 255, 255, 0.07) !important;
      border-color: rgba(139, 92, 246, 0.4) !important;
      transform: translateY(-2px);
    }
    
    .location-option-card:has(input[type="radio"]:checked) {
      background: rgba(139, 92, 246, 0.1) !important;
      border-color: rgba(139, 92, 246, 0.8) !important;
      box-shadow: 0 8px 24px rgba(139, 92, 246, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }
  </style>

  <!-- Leaflet CSS (free, no API key) -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>

<body id="top">

  <!-- Floating glowing background circles for 3D depth -->
  <div class="glow-sphere sphere-1"></div>
  <div class="glow-sphere sphere-2"></div>
  <div class="glow-sphere sphere-3"></div>

  <!-- NAVIGATION HEADER -->
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <!-- SIDEBAR PANELS (Cart, Wishlist, Profile) -->
  <?php include __DIR__ . '/../constants/aside.php'; ?>

  <main>
    <div class="wizard-container">

      <div class="wizard-card-glass">

        <!-- HEADER BLOCK -->
        <div class="wizard-header">
          <h2 class="wizard-title" id="main-wizard-title">Vehicle Car & Bike Wash</h2>
          <p class="wizard-subtitle" id="main-wizard-subtitle">Book high-end detailing or quick foaming washes instantly
          </p>
        </div>

        <!-- STEP PROGRESS BAR -->
        <div class="step-indicator-text" id="step-number-label">Step 1 of 13: Homepage</div>
        <div class="progress-bar-wrapper">
          <div class="progress-bar-fill" id="progress-bar-fill"></div>
        </div>



        <!-- ============================================== -->
        <!-- STEP 2: SELECT VEHICLE TYPE -->
        <!-- ============================================== -->
        <div class="wizard-step active" id="step-2">
          <h3 class="section-title-left" style="margin-top: 0;">Select Vehicle Type</h3>
          <p class="wizard-subtitle">Choose your vehicle type to customize prices and specifications.</p>

          <div class="selection-grid">
            <div class="selection-card vehicle-card" data-vehicle="Bike" onclick="selectVehicle('Bike')">
              <div class="selection-icon"><ion-icon name="bicycle-outline"></ion-icon></div>
              <div class="selection-title">Bike</div>
              <div class="selection-desc">Motorcycles & Cruisers</div>
            </div>

            <div class="selection-card vehicle-card" data-vehicle="Scooter" onclick="selectVehicle('Scooter')">
              <div class="selection-icon"><ion-icon name="flash-outline"></ion-icon></div>
              <div class="selection-title">Scooter</div>
              <div class="selection-desc">Scootys & Mopeds</div>
            </div>

            <div class="selection-card vehicle-card" data-vehicle="Car" onclick="selectVehicle('Car')">
              <div class="selection-icon"><ion-icon name="car-outline"></ion-icon></div>
              <div class="selection-title">Car</div>
              <div class="selection-desc">Hatchbacks & Sedans</div>
            </div>

            <div class="selection-card vehicle-card" data-vehicle="SUV" onclick="selectVehicle('SUV')">
              <div class="selection-icon"><ion-icon name="car-sport-outline"></ion-icon></div>
              <div class="selection-title">SUV</div>
              <div class="selection-desc">SUVs & Compact MUVs</div>
            </div>

            <div class="selection-card vehicle-card" data-vehicle="Luxury Vehicle" onclick="selectVehicle('Luxury')">
              <div class="selection-icon"><ion-icon name="star-outline"></ion-icon></div>
              <div class="selection-title">Luxury Vehicle</div>
              <div class="selection-desc">Sports, Luxury & Exotic Cars</div>
            </div>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 3: CHOOSE SERVICE LOCATION               -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-3">
          <h3 class="section-title-left" style="margin-top: 0;">Choose Service Location</h3>
          <p class="wizard-subtitle">Opt for doorstep servicing or visit a neighboring center.</p>

          <div class="form-group-glass">
            <label class="form-label-glass" style="margin-bottom: 12px; display: block;">Location Option</label>
            <div style="display: flex; gap: 16px; margin: 10px 0; flex-wrap: wrap;">
              <label class="location-option-card" style="flex: 1; min-width: 250px; display: flex; align-items: center; gap: 14px; padding: 18px 20px; border-radius: 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); cursor: pointer; transition: all 0.25s ease;">
                <input type="radio" name="loc-option" value="doorstep" checked onclick="toggleLocationView('doorstep')"
                  style="accent-color: #8b5cf6; width: 18px; height: 18px; flex-shrink: 0; margin: 0;">
                <div style="text-align: left;">
                  <strong style="color: #a78bfa; font-size: 0.95rem; display: block;">Doorstep Service</strong>
                  <span style="font-size: 0.8rem; opacity: 0.7; color: #cbd5e1; margin-top: 2px; display: block;">We wash your vehicle at your location.</span>
                </div>
              </label>
              
              <label class="location-option-card" style="flex: 1; min-width: 250px; display: flex; align-items: center; gap: 14px; padding: 18px 20px; border-radius: 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); cursor: pointer; transition: all 0.25s ease;">
                <input type="radio" name="loc-option" value="center" onclick="toggleLocationView('center')"
                  style="accent-color: #8b5cf6; width: 18px; height: 18px; flex-shrink: 0; margin: 0;">
                <div style="text-align: left;">
                  <strong style="color: #a78bfa; font-size: 0.95rem; display: block;">Visit Service Center</strong>
                  <span style="font-size: 0.8rem; opacity: 0.7; color: #cbd5e1; margin-top: 2px; display: block;">Bring your vehicle to the provider's center.</span>
                </div>
              </label>
            </div>
            
            <!-- ── Doorstep Fields ── -->
          <div id="doorstep-fields" style="margin-top: 20px;">
            <div class="form-group-glass">
              <label class="form-label-glass" style="margin-bottom: 8px; display: block;">Select Saved Address</label>
              
              <div id="saved-addresses-radios-container" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                <?php if (empty($userAddresses)): ?>
                  <div id="no-addresses-notice" style="padding: 16px; border-radius: 12px; background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); text-align: center; font-size: 0.85rem; color: #94a3b8;">
                    No saved addresses found. Add a new one below.
                  </div>
                <?php else: ?>
                  <?php foreach ($userAddresses as $addr): ?>
                    <?php 
                      $jsonData = json_encode([
                        'id' => $addr['id'],
                        'label' => $addr['label'],
                        'address' => $addr['address'],
                        'landmark' => $addr['landmark'],
                        'pincode' => $addr['pincode'],
                        'latitude' => $addr['latitude'],
                        'longitude' => $addr['longitude']
                      ]);
                    ?>
                    <label class="saved-address-radio-item">
                      <input type="radio" name="saved-address-choice" value="<?= htmlspecialchars($jsonData) ?>" <?= $addr['is_default'] ? 'checked' : '' ?> onclick="onSavedAddressRadioChange(this)">
                      <div>
                        <div>
                          <strong><?= htmlspecialchars($addr['label']) ?></strong>
                          <?php if ($addr['is_default']): ?>
                            <span>Default</span>
                          <?php endif; ?>
                        </div>
                        <p><?= htmlspecialchars($addr['address']) ?></p>
                        <small>Landmark: <?= htmlspecialchars($addr['landmark'] ?: 'N/A') ?> | Pincode: <?= htmlspecialchars($addr['pincode']) ?></small>
                      </div>
                    </label>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>

              <div style="margin-top: 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <button type="button" class="map-locate-btn" onclick="openAddAddressModal()" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; margin: 0;">
                  <ion-icon name="add-circle-outline"></ion-icon> Add New Address
                </button>
              </div>
            </div>

            <!-- Address Fields (Readonly view in main screen) -->
            <div class="form-group-glass" style="margin-top: 14px;">
              <label class="form-label-glass" for="loc-address">Full Address *</label>
              <input type="text" id="loc-address" class="form-input-glass"
                placeholder="Select an address or add a new one"
                value="<?= htmlspecialchars($currentUser['address'] ?? '') ?>" readonly oninput="validateStepRequirements()">
            </div>
            <div class="form-row-glass">
              <div class="form-group-glass">
                <label class="form-label-glass" for="loc-landmark">Landmark *</label>
                <input type="text" id="loc-landmark" class="form-input-glass" placeholder="Select an address or add a new one"
                  value="<?= htmlspecialchars($currentUser['landmark'] ?? '') ?>" readonly>
              </div>
              <div class="form-group-glass">
                <label class="form-label-glass" for="loc-pincode">Pincode *</label>
                <input type="text" id="loc-pincode" class="form-input-glass" placeholder="e.g. 700001"
                  value="<?= htmlspecialchars($currentUser['pincode'] ?? '') ?>" readonly oninput="validateStepRequirements()">
              </div>
            </div>

            <!-- Hidden coordinate fields -->
            <input type="hidden" id="loc-lat" value="<?= htmlspecialchars($currentUser['latitude'] ?? '') ?>">
            <input type="hidden" id="loc-lng" value="<?= htmlspecialchars($currentUser['longitude'] ?? '') ?>">
          </div>
          </div>

          <!-- ── Visit Service Center Fields ── -->
          <div id="center-fields" style="margin-top: 20px; display: none;">
            <div class="form-group-glass" style="padding: 18px; border-radius: 16px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
              <p class="wizard-subtitle" style="font-size: 0.95rem; color: #cbd5e1; line-height: 1.6; margin: 0; display: flex; align-items: flex-start; gap: 8px;">
                <span style="font-size: 1.1rem; line-height: 1;">📍</span>
                <span><strong>Visit Service Center:</strong> You will bring your vehicle directly to the selected service provider's center. You will select your preferred service provider and view their center address details in the next step.</span>
              </p>
            </div>
            <!-- Hidden input to satisfy validation requirements -->
            <input type="hidden" id="nearest-branch" value="Selected Provider Center">
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 4: SELECT SERVICE PROVIDER -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-4">
          <h3 class="section-title-left" style="margin-top: 0;">Select Service Provider</h3>
          <p class="wizard-subtitle">Choose from our nearest active service providers list with best rating rankings.
          </p>

          <div class="selection-grid" id="providers-container">
            <!-- Loaded dynamically based on location details -->
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 5: SELECT SERVICE PLAN -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-5">
          <h3 class="section-title-left" style="margin-top: 0;">Select Service Plan</h3>
          <p class="wizard-subtitle" id="service-plan-subtitle">Choose an active wash or care package.</p>

          <div class="selection-grid" id="service-options-container">
            <!-- Loaded dynamically based on selected provider -->
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 6: SELECT DATE & TIME SLOT -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-6">
          <h3 class="section-title-left" style="margin-top: 0;">Schedule Date & Time Slot</h3>
          <p class="wizard-subtitle">Pick an available calendar slot for your service.</p>

          <div class="form-group-glass">
            <label class="form-label-glass" for="sch-date">Select Date *</label>
            <input type="date" id="sch-date" class="form-input-glass" required>
          </div>

          <div class="form-group-glass" style="margin-top: 20px;">
            <label class="form-label-glass">Available Time Slots *</label>
            <div class="slots-grid">
              <div class="slot-pill" onclick="selectSlot(this, '09:00 AM – 10:00 AM')">09:00 AM – 10:00 AM</div>
              <div class="slot-pill" onclick="selectSlot(this, '10:00 AM – 11:00 AM')">10:00 AM – 11:00 AM</div>
              <div class="slot-pill" onclick="selectSlot(this, '11:00 AM – 12:00 PM')">11:00 AM – 12:00 PM</div>
              <div class="slot-pill" onclick="selectSlot(this, '01:00 PM – 02:00 PM')">01:00 PM – 02:00 PM</div>
              <div class="slot-pill" onclick="selectSlot(this, '02:00 PM – 03:00 PM')">02:00 PM – 03:00 PM</div>
              <div class="slot-pill" onclick="selectSlot(this, '03:00 PM – 04:00 PM')">03:00 PM – 04:00 PM</div>
            </div>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 7: ENTER VEHICLE SPECIFICATIONS -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-7">
          <h3 class="section-title-left" style="margin-top: 0;">Enter Vehicle Specifications</h3>
          <p class="wizard-subtitle">Provide information about your vehicle for our service team.</p>

          <div class="form-row-glass">
            <div class="form-group-glass">
              <label class="form-label-glass" for="v-brand">Vehicle Brand *</label>
              <input type="text" id="v-brand" class="form-input-glass" placeholder="e.g. Honda, Suzuki, Hyundai"
                required>
            </div>
            <div class="form-group-glass">
              <label class="form-label-glass" for="v-model">Model Name *</label>
              <input type="text" id="v-model" class="form-input-glass" placeholder="e.g. Activa 125, Creta, Swift"
                required>
            </div>
          </div>

          <div class="form-row-glass" style="margin-top: 10px;">
            <div class="form-group-glass">
              <label class="form-label-glass" for="v-reg">Registration Number *</label>
              <input type="text" id="v-reg" class="form-input-glass" placeholder="e.g. WB12AB1234, DL3C1234" required>
            </div>
            <div class="form-group-glass">
              <label class="form-label-glass" for="v-color">Color (Optional)</label>
              <input type="text" id="v-color" class="form-input-glass" placeholder="e.g. Matte Black, Pearl White">
            </div>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 8: ADD PREMIUM EXTRAS -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-8">
          <h3 class="section-title-left" style="margin-top: 0;">Add Premium Extras</h3>
          <p class="wizard-subtitle">Select add-on treatments to pamper your vehicle. (Prices automatically update)</p>

          <div class="extras-list" id="extras-list-container">
            <!-- Dynamic extras will be loaded here -->
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 9: CUSTOMER INFORMATION -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-9">
          <h3 class="section-title-left" style="margin-top: 0;">Customer Information</h3>
          <p class="wizard-subtitle">Provide contact coordinates for booking tracking and updates.</p>

          <?php if (!empty($verifiedPhone)): ?>
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(48, 209, 88, 0.12); border: 1px solid rgba(48, 209, 88, 0.25); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.85rem; color: #30D158;">
              <ion-icon name="checkmark-circle-outline" style="font-size: 1.1rem;"></ion-icon>
              <span>Auto-filled from your verified profile (<?= htmlspecialchars($verifiedPhone) ?>)</span>
            </div>
          <?php elseif (!empty($verifiedEmail)): ?>
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.85rem; color: #60a5fa;">
              <ion-icon name="information-circle-outline" style="font-size: 1.1rem;"></ion-icon>
              <span>Profile verified (<?= htmlspecialchars($verifiedEmail) ?>). Please enter your contact mobile number below to proceed.</span>
            </div>
          <?php endif; ?>

          <div class="form-group-glass">
            <label class="form-label-glass" for="cust-name">Full Name *</label>
            <input type="text" id="cust-name" class="form-input-glass" placeholder="Your name"
              value="<?php echo htmlspecialchars($verifiedName); ?>" required>
          </div>
          <div class="form-row-glass" style="margin-top: 10px;">
            <div class="form-group-glass">
              <label class="form-label-glass" for="cust-phone">Mobile Number *</label>
              <input type="tel" id="cust-phone" class="form-input-glass" placeholder="Enter 10-digit mobile number"
                value="<?php echo htmlspecialchars($verifiedPhone); ?>" required oninput="validateStepRequirements()">
              <small id="cust-phone-help" style="font-size: 0.78rem; color: #f59e0b; display: none; margin-top: 4px;">* Valid 10-digit mobile number required</small>
            </div>
            <div class="form-group-glass">
              <label class="form-label-glass" for="cust-alt-phone">Alternative Mobile Number (Optional)</label>
              <input type="tel" id="cust-alt-phone" class="form-input-glass" placeholder="Alternative number">
            </div>
          </div>
          <div class="form-group-glass" style="margin-top: 10px;">
            <label class="form-label-glass" for="cust-email">Email (Optional)</label>
            <input type="email" id="cust-email" class="form-input-glass" placeholder="you@example.com"
              value="<?php echo htmlspecialchars($verifiedEmail); ?>">
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 10: ORDER SUMMARY -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-10">
          <h3 class="section-title-left" style="margin-top: 0;">Order Summary</h3>
          <p class="wizard-subtitle">Double check your booking details before final checkout.</p>

          <div class="summary-box-glass">
            <div class="summary-heading">Vehicle & Location</div>
            <div class="summary-row">
              <span>Vehicle Type:</span>
              <span id="sum-v-type" style="font-weight: 700;">--</span>
            </div>
            <div class="summary-row">
              <span>Vehicle Specs:</span>
              <span id="sum-v-specs" style="font-weight: 500;">--</span>
            </div>
            <div class="summary-row">
              <span>Location:</span>
              <span id="sum-location" style="font-weight: 500;">--</span>
            </div>
            <div class="summary-row">
              <span>Schedule:</span>
              <span id="sum-schedule" style="font-weight: 700; color: #a78bfa;">--</span>
            </div>

            <div class="summary-heading" style="margin-top: 20px;">Services & Extras</div>
            <div class="summary-row">
              <span id="sum-service-name">--</span>
              <span id="sum-service-price">₹0.00</span>
            </div>
            <div id="sum-extras-list-container">
              <!-- Extras rows appended here dynamically -->
            </div>

            <div class="summary-heading" style="margin-top: 20px;">Bill Summary</div>
            <div class="summary-row">
              <span>Service Provider Service Amount:</span>
              <span id="sum-subtotal">₹0.00</span>
            </div>
            <div class="summary-row">
              <span id="sum-convenience-fee-label">Platform Charge:</span>
              <span id="sum-convenience-fee">₹<?php echo number_format($initial_convenience_fee, 2); ?></span>
            </div>
            <div class="summary-row" id="sum-discount-row" style="display: none; color: #a78bfa;">
              <span>Subscription Discount (10%):</span>
              <span id="sum-discount">-₹0.00</span>
            </div>
            <div class="summary-total-row">
              <span>Total Amount (Paid by Customer):</span>
              <span id="sum-total">₹0.00</span>
            </div>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- ============================================== -->
        <!-- STEP 11: PROVIDER RESPONSE STATUS -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-11">
          <h3 class="section-title-left" style="margin-top: 0;">Provider Acceptance</h3>
          <p class="wizard-subtitle" style="margin-bottom: 20px;">We have sent your request to <span id="accept-provider-name"
              style="font-weight: 700; color: #a78bfa;">[Provider]</span> &mdash; waiting for their confirmation.</p>

          <div id="accept-status-box" class="summary-box-glass" style="text-align: center; padding: 35px 25px;">
            <!-- Radar Sonar & Orbital Loader -->
            <div class="provider-radar-container">
              <div class="radar-ring radar-ring-1"></div>
              <div class="radar-ring radar-ring-2"></div>
              <div class="radar-ring radar-ring-3"></div>
              <div class="radar-orbital-spinner" id="radar-orbital-spinner"></div>
              <div class="radar-orbital-counter" id="radar-orbital-counter"></div>
              <div id="accept-status-spinner" class="radar-hub pending">
                <ion-icon name="radio-outline"></ion-icon>
              </div>
            </div>

            <!-- Provider Live Preview Card -->
            <div class="radar-provider-card">
              <div class="radar-provider-avatar" id="accept-provider-avatar">P</div>
              <div style="text-align: left;">
                <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;" id="accept-provider-card-name">Service Provider</div>
                <div style="font-size: 0.75rem; color: #94a3b8;" id="accept-provider-card-service">Vehicle Wash Request</div>
              </div>
              <div class="radar-live-badge" id="radar-live-badge">
                <span class="radar-ping-dot"></span>
                <span id="radar-live-status-text">Connecting...</span>
              </div>
            </div>

            <!-- Waiting Timer -->
            <div id="accept-elapsed-timer" style="font-family: 'Courier New', monospace; font-size: 0.85rem; color: #94a3b8; margin: 6px auto 14px auto;">
              <ion-icon name="time-outline" style="vertical-align: -2px; margin-right: 4px; color: #a78bfa;"></ion-icon>
              Waiting elapsed: <span id="accept-timer-count" style="font-weight: 700; color: #cbd5e1;">00:00</span>
            </div>

            <div id="accept-status-title" style="font-size: 1.35rem; font-weight: 700; margin-top: 5px; color: #f8fafc;">
              Awaiting Provider Response...
            </div>
            <p id="accept-status-desc" style="color: #94a3b8; font-size: 0.95rem; margin-top: 8px; max-width: 520px; margin-left: auto; margin-right: auto; line-height: 1.5;">
              The service provider has been instantly notified of your wash request and schedule.
            </p>
          </div>

        </div>


        <!-- ============================================== -->
        <!-- STEP 12: PAYMENT METHOD -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-12">
          <h3 class="section-title-left" style="margin-top: 0;">Payment Method</h3>
          <p class="wizard-subtitle">Select how you'd like to pay for your service.</p>

          <div class="form-group-glass">
            <div class="selection-grid" style="grid-template-columns: 1fr 1fr;">
              <div class="selection-card pay-method-card selected" id="pay-cod" onclick="selectPayMethod('COD')">
                <div class="selection-icon"><ion-icon name="cash-outline"></ion-icon></div>
                <div class="selection-title">Cash on Delivery</div>
                <div class="selection-desc">Pay at door after wash completion</div>
              </div>

              <div class="selection-card pay-method-card" id="pay-online" onclick="selectPayMethod('Online')">
                <div class="selection-icon"><ion-icon name="card-outline"></ion-icon></div>
                <div class="selection-title">Online Payment</div>
                <div class="selection-desc">Pay instantly using UPI, Cards, NetBanking</div>
              </div>
            </div>
          </div>

          <div id="online-pay-details" style="display: none; margin-top: 25px; animation: fadeIn 0.3s ease;">
            <div style="padding: 16px; border-radius: 12px; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); display: flex; align-items: center; gap: 14px;">
              <div style="font-size: 28px; color: #10b981; display: flex; align-items: center;"><ion-icon name="shield-checkmark-outline"></ion-icon></div>
              <div>
                <div style="font-size: 14px; font-weight: 700; color: #10b981;">Secure Razorpay Checkout</div>
                <div style="font-size: 12px; color: var(--text-secondary); line-height: 1.5; margin-top: 2px;">
                  Instant confirmation via UPI, Google Pay, PhonePe, Debit/Credit Cards & NetBanking. Click <strong>Next</strong> to open the secure payment window.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 13: BOOKING CONFIRMATION -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-13">
          <div class="success-icon-large"><ion-icon name="checkmark-circle-outline"></ion-icon></div>
          <h3 class="wizard-title" style="text-align: center; margin-top: 0;">Booking Successful!</h3>
          <p class="wizard-subtitle" style="text-align: center;">Your vehicle care order has been recorded in our
            system.</p>

          <div class="summary-box-glass" style="margin-top: 25px;">
            <div class="summary-row">
              <span>Booking Reference:</span>
              <span id="conf-booking-id" style="font-weight: 800; color: #a78bfa;">BW-20260613-3482</span>
            </div>
            <div class="summary-row">
              <span>Service Selected:</span>
              <span id="conf-service-name" style="font-weight: 600;">Superbike Detailing & Wash</span>
            </div>
            <div class="summary-row">
              <span>Location Type:</span>
              <span id="conf-location-type" style="font-weight: 500;">Doorstep Service</span>
            </div>
            <div class="summary-row">
              <span>Payment Status:</span>
              <span id="conf-pay-status" style="font-weight: 700; color: #10b981;">Pay on Service (COD)</span>
            </div>
          </div>

          <div class="notif-btn-row">
            <button class="btn-notif" onclick="triggerNotificationSim('SMS')">
              <ion-icon name="chatbox-ellipses-outline"></ion-icon> Send SMS Confirmation
            </button>
            <button class="btn-notif" onclick="triggerNotificationSim('WhatsApp')">
              <ion-icon name="logo-whatsapp"></ion-icon> Send WhatsApp Confirmation
            </button>
            <button class="btn-notif" onclick="triggerNotificationSim('Email')">
              <ion-icon name="mail-unread-outline"></ion-icon> Send Email Confirmation
            </button>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 14: REAL-TIME SERVICE TRACKING -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-14">
          <h3 class="section-title-left" style="margin-top: 0;">Live Service Tracking</h3>
          <p class="wizard-subtitle">Track your vehicle booking state in real-time.</p>

          <div class="tracker-stepper">
            <div class="tracker-step-item completed" id="track-step-pending">
              <div class="tracker-circle"><ion-icon name="checkmark-outline"></ion-icon></div>
              <div class="tracker-step-label">Booking Confirmed</div>
              <div class="tracker-step-time" id="time-pending">Now</div>
            </div>

            <div class="tracker-step-item" id="track-step-confirmed">
              <div class="tracker-circle">2</div>
              <div class="tracker-step-label">Staff Assigned</div>
              <div class="tracker-step-time" id="time-confirmed">-</div>
            </div>

            <div class="tracker-step-item" id="track-step-on_the_way">
              <div class="tracker-circle">3</div>
              <div class="tracker-step-label">Staff On the Way</div>
              <div class="tracker-step-time" id="time-on_the_way">-</div>
            </div>

            <div class="tracker-step-item" id="track-step-in_progress">
              <div class="tracker-circle">4</div>
              <div class="tracker-step-label">Service Started</div>
              <div class="tracker-step-time" id="time-in_progress">-</div>
            </div>

            <div class="tracker-step-item" id="track-step-completed">
              <div class="tracker-circle">5</div>
              <div class="tracker-step-label">Service Completed</div>
              <div class="tracker-step-time" id="time-completed">-</div>
            </div>
          </div>

          <!-- REAL-TIME LIVE TECHNICIAN TRACKER MAP -->
          <div id="web-live-technician-tracker" style="margin-top: 18px; border-radius: 14px; background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(59, 130, 246, 0.3); padding: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
              <div style="display: flex; align-items: center; gap: 8px;">
                <ion-icon name="navigate-circle" style="font-size: 1.4rem; color: #34d399;"></ion-icon>
                <div>
                  <h4 style="margin: 0; font-size: 0.95rem; color: #f8fafc; font-weight: 700;">Live Technician GPS Tracker</h4>
                  <p id="web-tracker-subtitle" style="margin: 2px 0 0 0; font-size: 0.78rem; color: #94a3b8;">Streaming live coordinates from service provider...</p>
                </div>
              </div>
              <div id="web-tracker-badge" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; color: #34d399; font-weight: 700;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #34d399;"></span>
                <span id="web-tracker-badge-text">LIVE GPS</span>
              </div>
            </div>

            <!-- Distance, ETA, and Assigned Provider Row -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 14px;">
              <div style="background: rgba(30, 41, 59, 0.6); padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                <div style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase;">Estimated Arrival</div>
                <div id="web-tracker-eta" style="font-size: 1.05rem; font-weight: 800; color: #60a5fa; margin-top: 2px;">~5 mins</div>
              </div>
              <div style="background: rgba(30, 41, 59, 0.6); padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                <div style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase;">Distance Away</div>
                <div id="web-tracker-distance" style="font-size: 1.05rem; font-weight: 800; color: #34d399; margin-top: 2px;">Nearby</div>
              </div>
              <div style="background: rgba(30, 41, 59, 0.6); padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                <div style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase;">Assigned Professional</div>
                <div id="web-tracker-provider-name" style="font-size: 0.92rem; font-weight: 700; color: #f8fafc; margin-top: 2px;">Verified Partner</div>
              </div>
            </div>

            <!-- Leaflet Tracking Map Container -->
            <div id="web-tracker-map-wrapper" style="height: 220px; border-radius: 10px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); position: relative;">
              <div id="web-tracker-map" style="width: 100%; height: 100%;"></div>
            </div>

            <!-- Action buttons: Sync & Open in Maps -->
            <div style="display: flex; gap: 8px; margin-top: 10px; justify-content: flex-end;">
              <button type="button" onclick="pollLiveTechnicianLocation(true)" class="map-locate-btn" style="padding: 6px 14px; font-size: 0.78rem; margin: 0; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.5); color: #93c5fd; display: inline-flex; align-items: center; gap: 4px;">
                <ion-icon name="refresh-outline"></ion-icon> Sync GPS
              </button>
              <button type="button" id="btn-web-open-route" onclick="openWebTrackerInGoogleMaps()" class="map-locate-btn" style="padding: 6px 14px; font-size: 0.78rem; margin: 0; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.5); color: #34d399; display: inline-flex; align-items: center; gap: 4px;">
                <ion-icon name="map-outline"></ion-icon> Open Navigation Route
              </button>
            </div>
          </div>

          <!-- DEVELOPER ACTION SIMULATOR PANEL -->
          <div class="sim-panel">
            <h4 style="margin: 0; color: #a78bfa;"><ion-icon name="construct-outline"></ion-icon> Staff Actions
              Simulator</h4>
            <p style="margin: 4px 0 0 0; font-size: 0.8rem; color: #94a3b8;">Simulate backend updates from provider app,
              updating the bookings table state instantly!</p>

            <div class="sim-btn-grid">
              <button class="btn-sim" id="btn-sim-assign" onclick="simulateBackendStatus('confirmed')">Assign
                Staff</button>
              <button class="btn-sim" id="btn-sim-otw" onclick="simulateBackendStatus('on_the_way')" disabled>Dispatch
                Staff</button>
              <button class="btn-sim" id="btn-sim-start" onclick="simulateBackendStatus('in_progress')" disabled>Start
                Service</button>
              <button class="btn-sim" id="btn-sim-complete" onclick="simulateBackendStatus('completed')"
                disabled>Complete Service</button>
            </div>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- STEP 15: RATE & REVIEW -->
        <!-- ============================================== -->
        <div class="wizard-step" id="step-15">
          <h3 class="section-title-left" style="margin-top: 0; text-align: center;">Rate & Review Your Experience</h3>
          <p class="wizard-subtitle" style="text-align: center;">Please rate the wash quality. Your feedback is
            appreciated!</p>

          <div class="star-rating" id="review-stars-container">
            <ion-icon name="star-outline" data-val="1" onclick="rateStars(1)"></ion-icon>
            <ion-icon name="star-outline" data-val="2" onclick="rateStars(2)"></ion-icon>
            <ion-icon name="star-outline" data-val="3" onclick="rateStars(3)"></ion-icon>
            <ion-icon name="star-outline" data-val="4" onclick="rateStars(4)"></ion-icon>
            <ion-icon name="star-outline" data-val="5" onclick="rateStars(5)"></ion-icon>
          </div>

          <div class="form-group-glass">
            <label class="form-label-glass" for="review-comment">Review Comment</label>
            <textarea id="review-comment" class="form-input-glass" rows="4"
              placeholder="How was the cleaning service? Share details about staff behavior or overall quality."></textarea>
          </div>

          <div class="form-group-glass" style="margin-top: 15px;">
            <label class="form-label-glass" for="review-photo">Upload Vehicle Photos (Optional)</label>
            <input type="file" id="review-photo" class="form-input-glass" accept="image/*">
            <p style="margin: 4px 0 0 0; font-size: 0.8rem; color: #94a3b8;">Supported formats: JPEG, PNG</p>
          </div>

          <button class="btn-wizard-next" onclick="submitReview()" style="margin: 30px auto 0 auto; display: flex;">
            Submit Review & Exit
          </button>
        </div>

        <!-- ============================================== -->
        <!-- WIZARD BUTTONS FOOTER -->
        <!-- ============================================== -->
        <div class="wizard-buttons" id="wizard-control-buttons">
          <button class="btn-wizard-prev" id="btn-prev" onclick="prevStep()">Previous</button>
          <button class="btn-wizard-next" id="btn-next" onclick="nextStep()">
            Next <ion-icon name="arrow-forward-outline"></ion-icon>
          </button>
        </div>

      </div>
    </div>
  </main>

  <!-- FOOTER -->
  <?php include __DIR__ . '/../layout/footer.php'; ?>

  <!-- BACK TO TOP BUTTON -->
  <a href="#top" class="back-to-top" aria-label="Back to Top" data-back-top-btn>
    <ion-icon name="arrow-up-outline"></ion-icon>
  </a>

  <!-- JS imports -->
  <script src="./assets/js/script.js"></script>
  <script src="./assets/js/notify.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <!-- Leaflet JS (free, no API key needed) -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    let _modalMap = null;          // Leaflet map instance inside modal
    let _modalMarker = null;       // Draggable marker inside modal
    let _modalMapInitialized = false;

    // Default fallback center (West Bengal / Kolkata service region)
    const DEFAULT_LAT = 22.5726;
    const DEFAULT_LNG = 88.3639;
    const DEFAULT_ZOOM = 12;
    const DETAIL_ZOOM = 16;

    /** Initialize the Leaflet map inside #modal-loc-map */
    function initModalMap(lat, lng, zoom) {
      if (typeof L === 'undefined') {
        setModalMapStatus('error', 'Map library unavailable – please enter address manually');
        return;
      }

      if (_modalMapInitialized) {
        _modalMap.setView([lat, lng], zoom);
        _modalMarker.setLatLng([lat, lng]);
        document.getElementById('modal-addr-lat').value = lat.toFixed(7);
        document.getElementById('modal-addr-lng').value = lng.toFixed(7);
        setTimeout(() => { if (_modalMap) _modalMap.invalidateSize(); }, 150);
        return;
      }

      _modalMap = L.map('modal-loc-map', { zoomControl: true }).setView([lat, lng], zoom);

      // Google Maps Roadmap tile layer as default base layer
      const googleRoads = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        attribution: '&copy; Google Maps',
        maxZoom: 20
      });
      const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
      });
      googleRoads.addTo(_modalMap);
      L.control.layers({ "Google Maps": googleRoads, "OpenStreetMap": osmLayer }, null, { position: 'topright' }).addTo(_modalMap);

      // Custom pin icon
      const pinIcon = L.divIcon({
        className: '',
        html: `<div style="
          width:34px;height:34px;background:linear-gradient(135deg,#8b5cf6,#3b82f6);
          border-radius:50% 50% 50% 0;transform:rotate(-45deg);
          box-shadow:0 3px 12px rgba(139,92,246,0.6);
          border:3px solid #fff;
        "></div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 34],
        popupAnchor: [0, -36]
      });

      _modalMarker = L.marker([lat, lng], { icon: pinIcon, draggable: true }).addTo(_modalMap);
      _modalMarker.bindPopup('<b>📍 Pin Location</b><br>Drag me to adjust!').openPopup();

      document.getElementById('modal-addr-lat').value = lat.toFixed(7);
      document.getElementById('modal-addr-lng').value = lng.toFixed(7);

      // Reverse geocode on drag end
      _modalMarker.on('dragend', function (e) {
        const pos = e.target.getLatLng();
        document.getElementById('modal-addr-lat').value = pos.lat.toFixed(7);
        document.getElementById('modal-addr-lng').value = pos.lng.toFixed(7);
        const locLatEl = document.getElementById('loc-lat');
        const locLngEl = document.getElementById('loc-lng');
        if (locLatEl) locLatEl.value = pos.lat.toFixed(7);
        if (locLngEl) locLngEl.value = pos.lng.toFixed(7);
        bookingState.lat = pos.lat;
        bookingState.lng = pos.lng;
        reverseGeocodeModal(pos.lat, pos.lng);
      });

      // Click on map moves marker
      _modalMap.on('click', function (e) {
        _modalMarker.setLatLng(e.latlng);
        document.getElementById('modal-addr-lat').value = e.latlng.lat.toFixed(7);
        document.getElementById('modal-addr-lng').value = e.latlng.lng.toFixed(7);
        const locLatEl = document.getElementById('loc-lat');
        const locLngEl = document.getElementById('loc-lng');
        if (locLatEl) locLatEl.value = e.latlng.lat.toFixed(7);
        if (locLngEl) locLngEl.value = e.latlng.lng.toFixed(7);
        bookingState.lat = e.latlng.lat;
        bookingState.lng = e.latlng.lng;
        reverseGeocodeModal(e.latlng.lat, e.latlng.lng);
      });

      _modalMapInitialized = true;
      setTimeout(() => { if (_modalMap) _modalMap.invalidateSize(); }, 200);
    }

    /** Toggle the Live Pin Map visibility inside the modal */
    function toggleModalPinMap(forceOpen) {
      const wrapper = document.getElementById('modal-map-wrapper');
      const btn = document.getElementById('btn-modal-toggle-map');
      if (!wrapper) return;

      const shouldShow = (forceOpen === true) ? true : (wrapper.style.display === 'none');
      wrapper.style.display = shouldShow ? 'block' : 'none';

      if (btn) {
        if (shouldShow) {
          btn.style.background = 'rgba(59, 130, 246, 0.4)';
          btn.style.borderColor = 'rgba(59, 130, 246, 0.8)';
          btn.style.color = '#bfdbfe';
        } else {
          btn.style.background = 'rgba(59, 130, 246, 0.22)';
          btn.style.borderColor = 'rgba(59, 130, 246, 0.45)';
          btn.style.color = '#60a5fa';
        }
      }

      if (shouldShow) {
        setTimeout(() => {
          if (_modalMap) _modalMap.invalidateSize();
        }, 150);
      }
    }

    /** Auto-format 5-digit typos like 70052 -> 700052 */
    function normalizePincode(str) {
      if (!str) return '';
      let clean = String(str).trim();
      if (/^700\d{2}$/.test(clean)) {
        clean = clean.replace(/^700(\d{2})$/, '7000$1');
      }
      return clean;
    }

    let _pincodeTimer = null;
    function handleModalPincodeChange(val) {
      clearTimeout(_pincodeTimer);
      const clean = normalizePincode(val);
      if (clean !== val && document.getElementById('modal-addr-pincode')) {
        document.getElementById('modal-addr-pincode').value = clean;
      }
      if (/^\d{6}$/.test(clean)) {
        _pincodeTimer = setTimeout(() => {
          snapMapToPincode(clean);
        }, 500);
      }
    }

    function handleModalPincodeBlur(input) {
      if (!input) return;
      const clean = normalizePincode(input.value);
      input.value = clean;
      if (/^\d{6}$/.test(clean)) {
        snapMapToPincode(clean);
      }
    }

    /** Snap map and pin directly to the entered pincode */
    function snapMapToPincode(explicitPin) {
      const pinInput = document.getElementById('modal-addr-pincode');
      let pin = explicitPin || (pinInput ? pinInput.value.trim() : '');
      pin = normalizePincode(pin);
      if (pinInput && pinInput.value !== pin) pinInput.value = pin;

      if (!/^\d{6}$/.test(pin)) {
        if (typeof Notify !== 'undefined') Notify.warning('Please enter a valid 6-digit Pincode (e.g. 700052).');
        else alert('Please enter a valid 6-digit Pincode.');
        return;
      }

      setModalMapStatus('detecting', `⏳ Locating Pincode ${pin}...`);
      fetch(`https://nominatim.openstreetmap.org/search?postalcode=${pin}&country=india&format=json&addressdetails=1`, {
        headers: { 'Accept-Language': 'en' }
      })
        .then(r => r.json())
        .then(results => {
          if (!results || results.length === 0) {
            // Fallback: search query with West Bengal context
            return fetch(`https://nominatim.openstreetmap.org/search?q=${pin},+West+Bengal,+India&format=json&addressdetails=1`, {
              headers: { 'Accept-Language': 'en' }
            }).then(r => r.json());
          }
          return results;
        })
        .then(results => {
          if (!results || results.length === 0) {
            setModalMapStatus('error', `⚠ Pincode ${pin} not found on map`);
            return;
          }
          const res = results[0];
          const lat = parseFloat(res.lat);
          const lng = parseFloat(res.lon);

          toggleModalPinMap(true);
          document.getElementById('modal-addr-lat').value = lat.toFixed(7);
          document.getElementById('modal-addr-lng').value = lng.toFixed(7);
          const locLatEl = document.getElementById('loc-lat');
          const locLngEl = document.getElementById('loc-lng');
          if (locLatEl) locLatEl.value = lat.toFixed(7);
          if (locLngEl) locLngEl.value = lng.toFixed(7);
          bookingState.lat = lat;
          bookingState.lng = lng;

          if (!_modalMapInitialized) {
            initModalMap(lat, lng, DETAIL_ZOOM);
          } else {
            _modalMap.setView([lat, lng], DETAIL_ZOOM);
            _modalMarker.setLatLng([lat, lng]);
            _modalMap.invalidateSize();
          }
          reverseGeocodeModal(lat, lng, true); // preserve user-chosen pincode
          setModalMapStatus('found', `✓ Pinned to Pincode ${pin}`);
        })
        .catch(() => setModalMapStatus('error', '⚠ Network error locating pincode'));
    }

    function parseCustomerGoogleMapsInput(text) {
      if (!text || typeof text !== 'string') return null;
      text = text.trim();

      let coordMatch = text.match(/^(-?\d{1,2}\.\d+)[,\s]+(-?\d{1,3}\.\d+)$/);
      if (coordMatch) {
        const lat = parseFloat(coordMatch[1]);
        const lng = parseFloat(coordMatch[2]);
        if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) return { lat, lng };
      }

      let atMatch = text.match(/@(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/);
      if (atMatch) {
        const lat = parseFloat(atMatch[1]);
        const lng = parseFloat(atMatch[2]);
        if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) return { lat, lng };
      }

      let qMatch = text.match(/[?&](?:q|query|ll|destination|center)=(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/i);
      if (qMatch) {
        const lat = parseFloat(qMatch[1]);
        const lng = parseFloat(qMatch[2]);
        if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) return { lat, lng };
      }

      return null;
    }

    function normalizeCustomerLocationQuery(raw) {
      if (!raw) return '';
      let q = raw.trim();
      q = q.replace(/\bchinarpark\b/gi, 'chinar park')
           .replace(/\bchinar\s*park\b/gi, 'chinar park')
           .replace(/\bnewtown\b/gi, 'new town')
           .replace(/\bsaltlake\b/gi, 'salt lake')
           .replace(/\bcitycentre\b/gi, 'city centre')
           .replace(/\bviproad\b/gi, 'vip road')
           .replace(/\becospace\b/gi, 'eco space')
           .replace(/\brajarhatgopalpur\b/gi, 'rajarhat gopalpur');
      return q;
    }

    function setCustomerQuickLocation(lat, lng, address, pincode, landmark) {
      toggleModalPinMap(true);
      document.getElementById('modal-addr-lat').value = lat.toFixed(7);
      document.getElementById('modal-addr-lng').value = lng.toFixed(7);
      const locLatEl = document.getElementById('loc-lat');
      const locLngEl = document.getElementById('loc-lng');
      if (locLatEl) locLatEl.value = lat.toFixed(7);
      if (locLngEl) locLngEl.value = lng.toFixed(7);
      bookingState.lat = lat;
      bookingState.lng = lng;

      if (!_modalMapInitialized) {
        initModalMap(lat, lng, DETAIL_ZOOM);
      } else {
        _modalMap.setView([lat, lng], DETAIL_ZOOM);
        _modalMarker.setLatLng([lat, lng]);
        _modalMap.invalidateSize();
      }

      if (address && document.getElementById('modal-addr-address')) {
        document.getElementById('modal-addr-address').value = address;
      }
      if (pincode && document.getElementById('modal-addr-pincode')) {
        document.getElementById('modal-addr-pincode').value = pincode;
      }
      if (landmark && document.getElementById('modal-addr-landmark')) {
        document.getElementById('modal-addr-landmark').value = landmark;
      }

      setModalMapStatus('found', '✓ ' + (landmark || 'Location set'));
      if (typeof Notify !== 'undefined' && Notify.success) {
        Notify.success('📍 Location set to ' + (landmark || address.split(',')[0]) + '!', { single: true });
      }
    }

    function openCustomerGoogleMaps() {
      let lat = bookingState.lat || DEFAULT_LAT;
      let lng = bookingState.lng || DEFAULT_LNG;
      const gmapsUrl = `https://www.google.com/maps/@${lat},${lng},16z`;
      window.open(gmapsUrl, '_blank');
      if (typeof Notify !== 'undefined' && Notify.info) {
        Notify.info('📍 On Google Maps: Right-click your location and click the coordinates to copy, then paste them here!', { single: true });
      }
    }

    /** Search an area / locality / pincode and pan map to that location */
    function searchLocationOnModalMap() {
      const input = document.getElementById('modal-map-search-input');
      let rawQuery = input ? input.value.trim() : '';
      if (!rawQuery) {
        if (typeof Notify !== 'undefined') Notify.warning('Please enter an area, landmark or paste Google Maps link/coords.', { single: true });
        else alert('Please enter an area, landmark or paste Google Maps link/coords.');
        return;
      }

      // Check if user pasted coordinates or a Google Maps URL
      const parsedGmaps = parseCustomerGoogleMapsInput(rawQuery);
      if (parsedGmaps) {
        toggleModalPinMap(true);
        const lat = parsedGmaps.lat;
        const lng = parsedGmaps.lng;
        document.getElementById('modal-addr-lat').value = lat.toFixed(7);
        document.getElementById('modal-addr-lng').value = lng.toFixed(7);
        const locLatEl = document.getElementById('loc-lat');
        const locLngEl = document.getElementById('loc-lng');
        if (locLatEl) locLatEl.value = lat.toFixed(7);
        if (locLngEl) locLngEl.value = lng.toFixed(7);
        bookingState.lat = lat;
        bookingState.lng = lng;

        if (!_modalMapInitialized) {
          initModalMap(lat, lng, DETAIL_ZOOM);
        } else {
          _modalMap.setView([lat, lng], DETAIL_ZOOM);
          _modalMarker.setLatLng([lat, lng]);
          _modalMap.invalidateSize();
        }
        reverseGeocodeModal(lat, lng);
        setModalMapStatus('found', '✓ Google Maps location pinned');
        if (typeof Notify !== 'undefined' && Notify.success) {
          Notify.success(`📍 Location set: ${lat.toFixed(5)}, ${lng.toFixed(5)}!`, { single: true });
        }
        return;
      }

      let query = normalizeCustomerLocationQuery(rawQuery);
      query = normalizePincode(query);
      if (input) input.value = query;

      // If user typed a 6-digit pincode directly, snap map to pincode
      if (/^\d{6}$/.test(query)) {
        if (document.getElementById('modal-addr-pincode')) {
          document.getElementById('modal-addr-pincode').value = query;
        }
        snapMapToPincode(query);
        return;
      }

      setModalMapStatus('detecting', '⏳ Searching location...');
      const searchUrl = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&addressdetails=1&countrycodes=in&limit=5`;
      fetch(searchUrl, {
        headers: { 'Accept-Language': 'en' }
      })
        .then(r => r.json())
        .then(results => {
          if (!results || results.length === 0) {
            setModalMapStatus('error', '⚠ Location not found');
            return;
          }
          const res = results[0];
          const lat = parseFloat(res.lat);
          const lng = parseFloat(res.lon);

          toggleModalPinMap(true);
          document.getElementById('modal-addr-lat').value = lat.toFixed(7);
          document.getElementById('modal-addr-lng').value = lng.toFixed(7);
          const locLatEl = document.getElementById('loc-lat');
          const locLngEl = document.getElementById('loc-lng');
          if (locLatEl) locLatEl.value = lat.toFixed(7);
          if (locLngEl) locLngEl.value = lng.toFixed(7);
          bookingState.lat = lat;
          bookingState.lng = lng;

          if (!_modalMapInitialized) {
            initModalMap(lat, lng, DETAIL_ZOOM);
          } else {
            _modalMap.setView([lat, lng], DETAIL_ZOOM);
            _modalMarker.setLatLng([lat, lng]);
            _modalMap.invalidateSize();
          }
          reverseGeocodeModal(lat, lng);
          setModalMapStatus('found', '✓ Location found & pinned');
        })
        .catch(() => setModalMapStatus('error', '⚠ Search network error'));
    }

    /** Reverse geocode lat/lng → fill modal address fields via ArcGIS + BigDataCloud + Nominatim Tri-Fusion */
    function reverseGeocodeModal(lat, lng, keepExistingPincode = false) {
      setModalMapStatus('detecting', '⏳ Fetching address...');
      
      let bdcData = null;
      let nomData = null;
      let arcData = null;
      let applied = false;

      function renderModalAddress() {
        if (applied) return;
        applied = true;

        const a = (nomData && nomData.address) ? nomData.address : {};
        const arcAddr = (arcData && arcData.address) ? arcData.address : (arcData || {});

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
        let suburb = a.suburb || a.neighbourhood || a.subdistrict || a.village || (bdcData && bdcData.locality ? bdcData.locality : '') || arcAddr.District || '';
        if (suburb === laneGali || suburb === road || suburb === spot) suburb = '';

        // 5. City / Town
        let city = (bdcData && bdcData.city) || a.city || a.town || a.municipality || 'Kolkata';
        if (city.toLowerCase().indexOf('greater kolkata') !== -1) city = 'Kolkata';
        if (city === suburb || city === laneGali) city = '';

        // 6. State
        const state = (bdcData && bdcData.principalSubdivision) || a.state || arcAddr.Region || 'West Bengal';

        // 7. Pincode
        let pin = a.postcode || arcAddr.Postal || (bdcData && bdcData.postcode) || '';
        if (!pin && nomData && nomData.display_name) {
          const m = String(nomData.display_name).match(/\b([1-9][0-9]{5})\b/);
          if (m) pin = m[1];
        }

        // Assemble clean doorstep parts
        const parts = [spot, houseNo, road, laneGali, suburb, city, state].filter(v => v && String(v).trim().length > 0);
        const uniqueParts = [];
        parts.forEach(function(p) {
          const s = String(p).trim();
          if (s && !uniqueParts.some(function(u) { return u.toLowerCase() === s.toLowerCase(); })) {
            uniqueParts.push(s);
          }
        });

        let fullAddr = uniqueParts.join(', ');

        fullAddr = fullAddr
          .replace(/,\s*(Greater\s*Kolkata|Barrackpur\s*[-–]\s*II|Barrackpore\s*[-–]?\s*[I|1|2|II]+|North\s*24\s*Parganas|South\s*24\s*Parganas|India)\b/gi, '')
          .replace(/\b(Greater\s*Kolkata|Barrackpur\s*[-–]\s*II|Barrackpore\s*[-–]?\s*[I|1|2|II]+|North\s*24\s*Parganas|South\s*24\s*Parganas|India),\s*/gi, '')
          .replace(/,\s*India$/gi, '')
          .replace(/\s*,\s*,+/g, ', ')
          .trim()
          .replace(/^,+|,+$/g, '')
          .trim();

        if (fullAddr && document.getElementById('modal-addr-address')) {
          document.getElementById('modal-addr-address').value = fullAddr;
        }

        let landmarkVal = '';
        if (spot && laneGali && spot !== laneGali) {
          landmarkVal = `Near ${spot}, ${laneGali}`;
        } else if (spot && road && spot !== road) {
          landmarkVal = `Near ${spot}, ${road}`;
        } else if (spot) {
          landmarkVal = `Near ${spot}`;
        } else if (laneGali) {
          landmarkVal = `Near ${laneGali}`;
        } else if (road && suburb && road !== suburb) {
          landmarkVal = `Near ${road}, ${suburb}`;
        } else if (road) {
          landmarkVal = `Near ${road}`;
        } else if (suburb) {
          landmarkVal = `Near ${suburb}`;
        }

        if (landmarkVal && document.getElementById('modal-addr-landmark')) {
          document.getElementById('modal-addr-landmark').value = landmarkVal;
        }

        const currentPin = document.getElementById('modal-addr-pincode') ? document.getElementById('modal-addr-pincode').value.trim() : '';
        if (pin && (!keepExistingPincode || !currentPin) && document.getElementById('modal-addr-pincode')) {
          document.getElementById('modal-addr-pincode').value = pin;
        }

        setModalMapStatus('found', '✓ Location detected');
      }

      // 1. ArcGIS World Geocoder (Granular building, sector, gali, lane, road, pincode)
      fetch(`https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/reverseGeocode?location=${lng},${lat}&f=pjson`)
        .then(r => r.json())
        .then(arc => {
          arcData = arc && arc.address ? arc.address : arc;
          if (nomData && bdcData) renderModalAddress();
        })
        .catch(() => {
          if (nomData && bdcData) renderModalAddress();
        });

      // 2. BigDataCloud Client Reverse Geocoder
      fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=en`)
        .then(r => r.json())
        .then(bdc => {
          bdcData = bdc;
          if (arcData && nomData) renderModalAddress();
        })
        .catch(() => {
          if (arcData && nomData) renderModalAddress();
        });

      // 3. OpenStreetMap Nominatim
      fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1`, {
        headers: { 'Accept-Language': 'en' }
      })
        .then(r => r.json())
        .then(data => {
          nomData = data;
          renderModalAddress();
        })
        .catch(() => {
          if (arcData || bdcData) renderModalAddress();
        });

      setTimeout(() => { renderModalAddress(); }, 4500);
    }

    /** Set the modal map status badge text + class */
    function setModalMapStatus(type, text) {
      const badge = document.getElementById('modal-map-status');
      if (!badge) return;
      badge.className = `map-status-badge ${type}`;
      badge.style.display = 'inline-flex';
      badge.textContent = text;
    }

    /** "Locate Me" button handler in modal */
    function locateMeOnModalMap() {
      const btn = document.getElementById('btn-modal-locate');
      btn.disabled = true;
      setModalMapStatus('detecting', '⏳ Detecting location...');

      // Ensure map is visible when user clicks Locate Me
      toggleModalPinMap(true);

      function applyModalCoords(lat, lng) {
        document.getElementById('modal-addr-lat').value = lat.toFixed(7);
        document.getElementById('modal-addr-lng').value = lng.toFixed(7);
        const locLatEl = document.getElementById('loc-lat');
        const locLngEl = document.getElementById('loc-lng');
        if (locLatEl) locLatEl.value = lat.toFixed(7);
        if (locLngEl) locLngEl.value = lng.toFixed(7);
        bookingState.lat = lat;
        bookingState.lng = lng;

        if (!_modalMapInitialized) {
          initModalMap(lat, lng, DETAIL_ZOOM);
        } else {
          _modalMap.setView([lat, lng], DETAIL_ZOOM);
          _modalMarker.setLatLng([lat, lng]);
          _modalMap.invalidateSize();
        }
        reverseGeocodeModal(lat, lng, false);
        btn.disabled = false;
        setModalMapStatus('found', '✓ Location detected');
        if (typeof Notify !== 'undefined' && Notify.success) {
          Notify.success('📍 Live location set!', { single: true });
        }
      }

      function fallbackBdcClientModal() {
        fetch('https://api.bigdatacloud.net/data/reverse-geocode-client?localityLanguage=en')
          .then(r => r.json())
          .then(bdc => {
            const lat = parseFloat(bdc.latitude) || 22.6100;
            const lng = parseFloat(bdc.longitude) || 88.4400;
            applyModalCoords(lat, lng);
          })
          .catch(() => {
            setModalMapStatus('error', '⚠ GPS unavailable – enter address or search map');
            btn.disabled = false;
            if (!_modalMapInitialized) initModalMap(DEFAULT_LAT, DEFAULT_LNG, DEFAULT_ZOOM);
          });
      }

      if (!navigator.geolocation) {
        fallbackBdcClientModal();
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          applyModalCoords(pos.coords.latitude, pos.coords.longitude);
        },
        (err) => {
          fallbackBdcClientModal();
        },
        { enableHighAccuracy: true, timeout: 6000, maximumAge: 10000 }
      );
    }

    /** Modal View Toggles */
    function openAddAddressModal() {
      const modal = document.getElementById('add-address-modal');
      modal.style.display = 'flex';

      // Always clear previous inputs when adding a new address!
      if (document.getElementById('modal-addr-address')) document.getElementById('modal-addr-address').value = '';
      if (document.getElementById('modal-addr-landmark')) document.getElementById('modal-addr-landmark').value = '';
      if (document.getElementById('modal-addr-pincode')) document.getElementById('modal-addr-pincode').value = '';
      if (document.getElementById('modal-addr-lat')) document.getElementById('modal-addr-lat').value = '';
      if (document.getElementById('modal-addr-lng')) document.getElementById('modal-addr-lng').value = '';
      if (document.getElementById('modal-map-search-input')) document.getElementById('modal-map-search-input').value = '';
      const badge = document.getElementById('modal-map-status');
      if (badge) badge.style.display = 'none';

      setTimeout(() => {
        modal.classList.add('se-confirm-visible');
        let initialLat = DEFAULT_LAT;
        let initialLng = DEFAULT_LNG;
        let initialZoom = DEFAULT_ZOOM;

        initModalMap(initialLat, initialLng, initialZoom);
        if (_modalMap) _modalMap.invalidateSize();
      }, 200);
    }

    function closeAddAddressModal() {
      const modal = document.getElementById('add-address-modal');
      modal.classList.remove('se-confirm-visible');
      setTimeout(() => {
        modal.style.display = 'none';
      }, 300);
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function escapeHtmlForAttr(str) {
      if (!str) return '';
      return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
    }

    /** Save new address and auto-select it immediately (Atomic 1-POST optimization) */
    function saveNewAddressFromModal() {
      const label = document.getElementById('modal-addr-label').value.trim() || 'Home';
      const address = document.getElementById('modal-addr-address').value.trim();
      const landmark = document.getElementById('modal-addr-landmark').value.trim();
      const pincode = document.getElementById('modal-addr-pincode').value.trim();
      const lat = document.getElementById('modal-addr-lat').value;
      const lng = document.getElementById('modal-addr-lng').value;

      if (!address || !pincode) {
        if (typeof Notify !== 'undefined') Notify.warning('Please enter your full address and pincode.');
        else alert('Please enter your full address and pincode.');
        return;
      }

      // Populate bookingState locally immediately (0 wait time, atomic booking)
      bookingState.address = address;
      bookingState.landmark = landmark;
      bookingState.pincode = pincode;
      bookingState.lat = parseFloat(lat) || null;
      bookingState.lng = parseFloat(lng) || null;
      bookingState.addressLabel = label;
      bookingState.isNewAddress = true;

      // Update location input display
      const locInput = document.getElementById('loc-address');
      if (locInput) locInput.value = address;
      const locLandmark = document.getElementById('loc-landmark');
      if (locLandmark) locLandmark.value = landmark;
      const locPincode = document.getElementById('loc-pincode');
      if (locPincode) locPincode.value = pincode;

      const addrObj = {
        id: 'new_' + Date.now(),
        label: label,
        address: address,
        landmark: landmark || 'N/A',
        pincode: pincode,
        latitude: lat,
        longitude: lng
      };

      const container = document.getElementById('saved-addresses-radios-container');
      const notice = document.getElementById('no-addresses-notice');
      if (notice) notice.remove();

      // Check if radio already exists in container to prevent DOM duplication
      let existingRadio = null;
      if (container) {
        const radios = container.querySelectorAll('input[name="saved-address-choice"]');
        radios.forEach(r => {
          try {
            const parsed = JSON.parse(r.value);
            if (parsed.address && parsed.address.toLowerCase().trim() === address.toLowerCase().trim() && parsed.pincode == pincode) {
              existingRadio = r;
            }
          } catch(e) {}
        });

        if (existingRadio) {
          existingRadio.value = JSON.stringify(addrObj);
          existingRadio.checked = true;
          onSavedAddressRadioChange(existingRadio);
        } else {
          const jsonDataStr = JSON.stringify(addrObj);
          const wrapper = document.createElement('label');
          wrapper.className = 'saved-address-radio-item';
          wrapper.innerHTML = `
            <input type="radio" name="saved-address-choice" value="${escapeHtmlForAttr(jsonDataStr)}" checked onclick="onSavedAddressRadioChange(this)">
            <div>
              <div>
                <strong>${escapeHtml(label)}</strong>
              </div>
              <p>${escapeHtml(address)}</p>
              <small>Landmark: ${escapeHtml(landmark || 'N/A')} | Pincode: ${escapeHtml(pincode)}</small>
            </div>
          `;
          container.appendChild(wrapper);
          onSavedAddressRadioChange(wrapper.querySelector('input[type="radio"]'));
        }
      }

      closeAddAddressModal();
      if (typeof Notify !== 'undefined') {
        Notify.success('📍 Address selected — saved atomically with your booking!');
      }

      validateStepRequirements();
    }

    /** Auto-fill form fields when a saved address radio option is checked */
    function onSavedAddressRadioChange(radioInput) {
      if (!radioInput || !radioInput.checked) return;

      try {
        const addr = JSON.parse(radioInput.value);
        document.getElementById('loc-address').value = addr.address || '';
        document.getElementById('loc-landmark').value = addr.landmark || '';
        document.getElementById('loc-pincode').value = addr.pincode || '';
        document.getElementById('loc-lat').value = addr.latitude || '';
        document.getElementById('loc-lng').value = addr.longitude || '';
        bookingState.address = addr.address || '';
        bookingState.landmark = addr.landmark || '';
        bookingState.pincode = addr.pincode || '';
        bookingState.addressLabel = addr.label || 'Home';
        if (addr.latitude && !isNaN(parseFloat(addr.latitude))) {
          bookingState.lat = parseFloat(addr.latitude);
        }
        if (addr.longitude && !isNaN(parseFloat(addr.longitude))) {
          bookingState.lng = parseFloat(addr.longitude);
        }
        validateStepRequirements();
      } catch (e) {
        console.error(e);
      }
    }

    function selectDefaultRadioOnLoad() {
      const activeRadio = document.querySelector('input[name="saved-address-choice"]:checked');
      if (activeRadio) {
        onSavedAddressRadioChange(activeRadio);
      }
    }

    /** Show provider address from DB when we enter step 3 */
    function syncProviderAddressCard() {
      const card = document.getElementById('provider-addr-card');
      const value = document.getElementById('provider-addr-value');
      if (!card || !value) return;

      // Use currently selected provider from bookingState
      let provAddr = '';
      const pid = bookingState && bookingState.providerId;
      if (pid) {
        const prov = dbProviders.find(p => parseInt(p.id) === parseInt(pid));
        provAddr = prov ? (prov.location_address || prov.provider_address || '') : '';
      }

      // Fall back to first active provider's address
      if (!provAddr && dbProviders.length > 0) {
        provAddr = dbProviders[0].location_address || dbProviders[0].provider_address || '';
      }

      if (provAddr) {
        const provName = pid
          ? (dbProviders.find(p => parseInt(p.id) === parseInt(pid)) || dbProviders[0]).business_name
          : dbProviders[0].business_name;
        value.textContent = `${provName}: ${provAddr}`;
        card.classList.add('visible');
      } else {
        card.classList.remove('visible');
      }
    }

    /** "Copy to My Address" button handler */
    function useProviderAddress() {
      const raw = document.getElementById('provider-addr-value').textContent;
      const colon = raw.indexOf(':');
      const addr = colon !== -1 ? raw.slice(colon + 1).trim() : raw;
      if (addr) {
        document.getElementById('loc-address').value = addr;
        document.getElementById('loc-landmark').value = 'Provider Area';
        document.getElementById('loc-pincode').value = '700001'; // standard fallback pincode
        validateStepRequirements();
      }
    }

    /* ── Watch for Step 3 becoming active ── */
    (function observeStep3() {
      const step3 = document.getElementById('step-3');
      if (!step3) return;

      const obs = new MutationObserver(() => {
        if (step3.classList.contains('active')) {
          setTimeout(() => {
            syncProviderAddressCard();
            selectDefaultRadioOnLoad();
          }, 120);
        }
      });

      obs.observe(step3, { attributes: true, attributeFilter: ['class'] });
    })();

    /* ─── Save user address to site_user via AJAX ─── */
    function saveUserAddressToDb(address, landmark, pincode, lat, lng) {
      if (!address && !pincode) return; // nothing to save

      const body = new URLSearchParams({
        ajax_action: 'save_user_address',
        address: address || '',
        landmark: landmark || '',
        pincode: pincode || '',
        latitude: lat || '',
        longitude: lng || '',
      });

      fetch(window.location.pathname + window.location.search, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(r => r.json())
        .then(data => {
          if (data.status === 'ok') {
            showToast('📍 Address saved to your profile', 'success');
          }
        })
        .catch(() => { }); // Silent fail — never block wizard
    }

    /* ─── Lightweight toast notification ─── */
    (function injectToastStyles() {
      if (document.getElementById('se-toast-style')) return;
      const s = document.createElement('style');
      s.id = 'se-toast-style';
      s.textContent = `
        #se-toast {
          position: fixed;
          bottom: 30px;
          left: 50%;
          transform: translateX(-50%) translateY(20px);
          background: rgba(16,185,129,0.92);
          color: #fff;
          padding: 11px 22px;
          border-radius: 30px;
          font-size: 0.88rem;
          font-weight: 700;
          box-shadow: 0 6px 24px rgba(0,0,0,0.35);
          opacity: 0;
          transition: opacity 0.3s ease, transform 0.3s ease;
          z-index: 9999;
          pointer-events: none;
          white-space: nowrap;
        }
        #se-toast.show {
          opacity: 1;
          transform: translateX(-50%) translateY(0);
        }
        #se-toast.error { background: rgba(239,68,68,0.92); }
      `;
      document.head.appendChild(s);

      const el = document.createElement('div');
      el.id = 'se-toast';
      document.body.appendChild(el);
    })();

    function showToast(msg, type = 'success') {
      const el = document.getElementById('se-toast');
      if (!el) return;
      el.textContent = msg;
      el.className = type === 'error' ? 'error' : '';
      el.classList.add('show');
      clearTimeout(el._timer);
      el._timer = setTimeout(() => el.classList.remove('show'), 3200);
    }
  </script>

  <script>
    // Injected database-driven active services and providers list
    const dbProviders = <?php echo json_encode($providers); ?>;
    const dbAllServices = <?php echo json_encode($allServices); ?>;
    const dbCategoryMap = <?php echo json_encode($categoryMap); ?>;
    const dbBranches = <?php echo json_encode($providerBranches); ?>;
    const dbSubcategories = <?php echo json_encode($allSubcategories); ?>;

    // SessionStorage Cache (15-min TTL) to ensure 0 HTTP requests on reload/catalog revisit
    try {
      const CACHE_KEY = 'serveasy_vwash_cache';
      if (dbAllServices && dbAllServices.length > 0) {
        sessionStorage.setItem(CACHE_KEY, JSON.stringify({
          time: Date.now(),
          services: dbAllServices,
          providers: dbProviders,
          categoryMap: dbCategoryMap
        }));
      }
    } catch (ce) {}

    const preselectedServiceId = <?php echo $preselectedServiceId ? $preselectedServiceId : 'null'; ?>;
    const preselectedPackageId = <?php echo $preselectedPackageId ? $preselectedPackageId : 'null'; ?>;
    const preselectedBooking = <?php echo $preselectedBooking ? json_encode($preselectedBooking) : 'null'; ?>;
    const preselectedServiceSlug = <?php echo $preselectedServiceSlug ? json_encode($preselectedServiceSlug) : 'null'; ?>;
    const preselectedSubcat = <?php echo $preselectedSubcat ? json_encode($preselectedSubcat) : 'null'; ?>;
    const preselectedVehicle = <?php echo $preselectedVehicle ? json_encode($preselectedVehicle) : 'null'; ?>;
    const isSelectVehicleMode = <?php echo $isSelectVehicleMode ? 'true' : 'false'; ?>;

    const urlParams = new URLSearchParams(window.location.search);
    const urlService = urlParams.get('service') || preselectedServiceSlug;
    const urlVehicle = urlParams.get('vehicle') || preselectedVehicle;
    const urlSubcat = urlParams.get('subcat') || preselectedSubcat;
    const urlPackageId = urlParams.get('package_id') || preselectedPackageId;
    const urlSelectVehicle = urlParams.get('select_vehicle') || (isSelectVehicleMode ? '1' : null);

    // Set default date range to tomorrow onwards
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dateInput = document.getElementById('sch-date');
    if (dateInput) {
      dateInput.min = today.toISOString().split('T')[0];
      dateInput.value = tomorrow.toISOString().split('T')[0];
    }

    // Global Tracker & Polling State (Hoisted to top of Script 2 to eliminate Temporal Dead Zone ReferenceErrors)
    var _trackerInterval = null;
    var _trackerMap = null;
    var _trackerProviderMarker = null;
    var _trackerCustomerMarker = null;
    var _lastTrackerData = null;
    var _pollingInterval = null;
    var _acceptTimerInterval = null;
    var _acceptStartTime = null;
    var _customerEventSource = null;

    // Booking Wizard State
    let currentStep = 2;
    const maxSteps = 15;

    let platformConvenienceFee = <?php echo json_encode($initial_convenience_fee); ?>;
    let platformFeeType = <?php echo json_encode($initial_fee_type); ?>;
    let platformFeeActive = <?php echo json_encode((bool)$initial_fee_active); ?>;
    let platformFeeInScope = <?php echo json_encode((bool)$is_vehicle_in_fee_scope); ?>;
    let platformFeeScope = <?php echo json_encode($initial_fee_scope); ?>;
    let platformFeeScopeList = <?php echo json_encode($initial_applicable_services); ?>;

    const bookingState = {
      category: null,      // bike_wash, car_wash, vehicle_repair, etc.
      categorySubIds: [],  // DB subcategory IDs for the selected category
      vehicleType: null,   // Bike, Scooter, Car, SUV, Luxury Vehicle
      providerId: null,    // Selected Service Provider ID
      providerName: null,  // Selected Service Provider name
      serviceId: null,     // Service ID from DB
      serviceName: null,   // Selected plan name
      servicePrice: 0.00,  // Base price
      packageId: urlPackageId ? parseInt(urlPackageId, 10) : null,     // Selected package ID (if any)
      brand: '',
      model: '',
      regNo: '',
      color: '',
      locationType: 'doorstep', // doorstep, center
      address: '',
      landmark: '',
      pincode: '',
      branch: '',
      date: '',
      timeSlot: '',
      extras: [],          // Array of {name: string, price: number}
      fullName: '',
      phone: '',
      altPhone: '',
      email: '',
      paymentMethod: 'COD', // COD, Online
      bookingDbId: null,   // Real primary key returned by DB
      bookingRefNum: '',   // Real human reference like BW-YYYYMMDD-XXXX
      customerId: <?= (int)($_SESSION['user_id'] ?? (\App\Helpers\Auth::id() ?? 0)) ?>,
      rating: 0,
      providerAccepted: false,
      rejectedProviders: []
    };

    // Auto-bootstrapping for pre-selected service routing
    if (preselectedServiceId) {
      let found = false;
      for (const s of dbAllServices) {
        if (parseInt(s.id) === preselectedServiceId) {
          found = true;
          bookingState.serviceId = s.id;
          bookingState.serviceName = s.name;
          bookingState.servicePrice = parseFloat(s.base_price);
          bookingState.providerId = s.provider_id;
          bookingState.providerName = s.business_name || s.provider_company;

          // Derive category + subcategory IDs from the service's subcategory_id
          const subId = parseInt(s.subcategory_id);
          for (const [catSlug, subIds] of Object.entries(dbCategoryMap)) {
            if (subIds.includes(subId)) {
              bookingState.category = catSlug;
              bookingState.categorySubIds = subIds;
              break;
            }
          }
          if (!bookingState.category) {
            bookingState.category = subId === 2 ? 'bike_wash' : 'car_wash';
            bookingState.categorySubIds = dbCategoryMap[bookingState.category] || (subId === 2 ? [2] : [1]);
          }
          // Auto transition to location selection
          currentStep = 3;
          updateStepDisplay();
        }
      }
    } else if (preselectedBooking) {
      bookingState.bookingDbId = parseInt(preselectedBooking.id);
      bookingState.bookingRefNum = preselectedBooking.booking_number;
      bookingState.serviceName = preselectedBooking.service_name;
      bookingState.vehicleType = preselectedBooking.vehicle_type || 'Vehicle';
      bookingState.brand = preselectedBooking.vehicle_brand || '';
      bookingState.model = preselectedBooking.vehicle_model || '';
      bookingState.regNo = preselectedBooking.vehicle_reg_no || '';
      bookingState.color = preselectedBooking.vehicle_color || '';
      bookingState.locationType = preselectedBooking.location_type || 'doorstep';
      bookingState.address = preselectedBooking.address || '';
      bookingState.date = preselectedBooking.schedule_date || '';
      bookingState.timeSlot = preselectedBooking.schedule_time || '';
      bookingState.paymentMethod = preselectedBooking.payment_method || 'COD';
      bookingState.totalAmount = parseFloat(preselectedBooking.total_amount);

      const dbStatus = preselectedBooking.status.toLowerCase();
      if (dbStatus === 'completed') {
        currentStep = 15; // Rate & Review (Step 15)
      } else {
        currentStep = 14; // Live Tracking (Step 14)

        // Populate the live tracking state based on current dbStatus
        setTimeout(() => {
          const statuses = ['pending', 'confirmed', 'assigned', 'on_the_way', 'in_progress', 'completed'];
          const targetIdx = statuses.indexOf(dbStatus);

          for (let i = 0; i <= targetIdx && i < 5; i++) {
            const statusKey = statuses[i];
            const node = document.getElementById(`track-step-${statusKey}`);
            if (node) {
              node.classList.add('completed');
              node.querySelector('.tracker-step-time').textContent = 'Active';
            }
          }

          // Disable/enable simulation buttons according to status
          if (dbStatus === 'confirmed') {
            document.getElementById('btn-sim-assign').disabled = true;
            document.getElementById('btn-sim-otw').disabled = false;
          } else if (dbStatus === 'on_the_way' || dbStatus === 'assigned') {
            document.getElementById('btn-sim-assign').disabled = true;
            document.getElementById('btn-sim-otw').disabled = true;
            document.getElementById('btn-sim-start').disabled = false;
          } else if (dbStatus === 'in_progress') {
            document.getElementById('btn-sim-assign').disabled = true;
            document.getElementById('btn-sim-otw').disabled = true;
            document.getElementById('btn-sim-start').disabled = true;
            document.getElementById('btn-sim-complete').disabled = false;
          }
        }, 100);
      }
    } else if (urlVehicle || urlSubcat === 'car-wash' || urlSubcat === 'bike-wash') {
      // ── Direct Booking Flow (Explore Service Categories: Car Wash / Bike Wash) ──
      sessionStorage.removeItem('washBookingStep');
      sessionStorage.removeItem('washBookingState');

      let targetVehicle = urlVehicle;
      if (!targetVehicle) {
        targetVehicle = (urlSubcat === 'bike-wash') ? 'Bike' : 'Car';
      }
      if (targetVehicle === 'Luxury') targetVehicle = 'Luxury Vehicle';

      bookingState.vehicleType = targetVehicle;
      const vLower = targetVehicle.toLowerCase();
      if (vLower === 'bike' || vLower === 'scooter') {
        bookingState.category = 'bike_wash';
        bookingState.categorySubIds = dbCategoryMap['bike_wash'] || [2];
      } else {
        bookingState.category = 'car_wash';
        bookingState.categorySubIds = dbCategoryMap['car_wash'] || [1];
      }

      // Pre-select matching vehicle card in Step 2
      document.querySelectorAll('#step-2 .selection-card').forEach(c => {
        const v = c.getAttribute('data-vehicle');
        if (v === targetVehicle || (targetVehicle === 'Luxury' && v === 'Luxury Vehicle') || (targetVehicle === 'Luxury Vehicle' && v === 'Luxury Vehicle')) {
          c.classList.add('selected');
        } else {
          c.classList.remove('selected');
        }
      });

      // Display Step 2 (Select Vehicle Type) with target vehicle pre-selected (or step 3 if explicitly requested)
      const requestedStep = urlParams.get('step');
      currentStep = (requestedStep === '3') ? 3 : 2;
      updateStepDisplay();
    } else if (urlSelectVehicle === '1' || (urlService && !urlVehicle && !urlSubcat)) {
      // ── Select Vehicle Mode (Available Bookings -> Vehicle Car & Bike Wash "Book Now") ──
      sessionStorage.removeItem('washBookingStep');
      sessionStorage.removeItem('washBookingState');

      bookingState.vehicleType = null;
      document.querySelectorAll('#step-2 .selection-card').forEach(c => c.classList.remove('selected'));

      currentStep = 2;
      updateStepDisplay();
    }

    // Step Transition Display Manager
    function updateStepDisplay() {
      if (typeof saveBookingState === 'function') {
        saveBookingState();
      }

      // Toggle wizard step boxes
      document.querySelectorAll('.wizard-step').forEach(step => {
        step.classList.remove('active');
      });
      document.getElementById(`step-${currentStep}`).classList.add('active');

      if (currentStep === 14) {
        if (typeof pollLiveTechnicianLocation === 'function') {
          pollLiveTechnicianLocation();
        }
        if (!_trackerInterval && typeof pollLiveTechnicianLocation === 'function') {
          _trackerInterval = setInterval(pollLiveTechnicianLocation, 7000);
        }
      } else if (_trackerInterval) {
        clearInterval(_trackerInterval);
        _trackerInterval = null;
      }

      // Update progress bar
      const percent = (currentStep / maxSteps) * 100;
      document.getElementById('progress-bar-fill').style.width = `${percent}%`;

      // Update Step title label
      const stepNames = {
        2: "Select Vehicle Type",
        3: "Choose Service Location",
        4: "Select Service Provider",
        5: "Select Service Plan",
        6: "Schedule Date & Time",
        7: "Vehicle Specifications",
        8: "Add Premium Extras",
        9: "Customer Information",
        10: "Order Summary",
        11: "Provider Acceptance",
        12: "Choose Payment Method",
        13: "Booking Confirmation",
        14: "Live Service Tracking",
        15: "Rate & Review"
      };

      document.getElementById('step-number-label').textContent = `Step ${currentStep} of ${maxSteps}: ${stepNames[currentStep]}`;

      // Manage Previous / Next Buttons visibility
      const btnPrev = document.getElementById('btn-prev');
      const btnNext = document.getElementById('btn-next');
      const footerBtnBox = document.getElementById('wizard-control-buttons');

      // If step is select vehicle, hide Prev button
      if (currentStep === 2) {
        btnPrev.style.visibility = 'hidden';
      } else {
        btnPrev.style.visibility = 'visible';
      }

      // Hide core buttons in tracking, confirmation, and review steps, let them use special control paths
      if (currentStep >= 13) {
        footerBtnBox.style.display = 'none';
      } else {
        footerBtnBox.style.display = 'flex';
      }

      if (currentStep === 8) {
        renderDynamicExtras();
      }
      if (currentStep === 6) {
        updateSlotAvailability();
      }
      if (currentStep === 10) {
        renderSummaryBreakdown();
      }

      // Perform step specific validation checks
      validateStepRequirements();
    }

    function validateStepRequirements() {
      const btnNext = document.getElementById('btn-next');
      let isValid = true;

      if (currentStep === 2 && !bookingState.vehicleType) isValid = false;

      if (currentStep === 3) {
        if (bookingState.locationType === 'doorstep') {
          const address = document.getElementById('loc-address').value.trim();
          const landmark = document.getElementById('loc-landmark').value.trim();
          const pin = document.getElementById('loc-pincode').value.trim();
          if (!address || !landmark || !pin) isValid = false;
        } else {
          const branch = document.getElementById('nearest-branch').value;
          if (!branch) isValid = false;
        }
      }

      if (currentStep === 4 && !bookingState.providerId) isValid = false;
      if (currentStep === 5 && !bookingState.serviceId) isValid = false;

      if (currentStep === 6) {
        const dt = document.getElementById('sch-date').value;
        const selectedPill = document.querySelector('.slots-grid .slot-pill.selected');
        const isBooked = selectedPill && selectedPill.classList.contains('slot-booked');
        if (!dt || !bookingState.timeSlot || isBooked) isValid = false;
      }

      if (currentStep === 7) {
        const brand = document.getElementById('v-brand').value.trim();
        const model = document.getElementById('v-model').value.trim();
        const reg = document.getElementById('v-reg').value.trim();
        if (!brand || !model || !reg) isValid = false;
      }

      if (currentStep === 9) {
        const name = document.getElementById('cust-name').value.trim();
        const phone = document.getElementById('cust-phone').value.trim();
        const cleanPhone = phone.replace(/[\s\-+()]/g, '');
        const isEmail = phone.includes('@');
        const isValidPhone = !isEmail && /^\d{10,12}$/.test(cleanPhone);
        const helpText = document.getElementById('cust-phone-help');
        if (helpText) {
          helpText.style.display = (!phone || !isValidPhone) ? 'block' : 'none';
        }
        if (!name || !phone || !isValidPhone) isValid = false;
      }

      if (currentStep === 11 && !bookingState.providerAccepted) isValid = false;

      btnNext.disabled = !isValid;
    }

    // Bind event listeners for input validation
    document.addEventListener('input', (e) => {
      if (['v-brand', 'v-model', 'v-reg', 'v-color', 'loc-address', 'loc-landmark', 'loc-pincode', 'cust-name', 'cust-phone', 'sch-date'].includes(e.target.id)) {
        validateStepRequirements();
        if (typeof saveBookingState === 'function') {
          saveBookingState();
        }
      }
    });
    // Date picker fires 'change' not 'input'
    document.addEventListener('change', (e) => {
      if (e.target.id === 'sch-date') {
        bookingState.date = e.target.value;
        updateSlotAvailability();
        validateStepRequirements();
      }
    });

    // Step Navigation API
    function nextStep() {
      validateStepRequirements();
      const btnNext = document.getElementById('btn-next');
      if (btnNext && btnNext.disabled) {
        if (currentStep === 2 && !bookingState.vehicleType) {
          Notify.warning('Please select a vehicle type to proceed.');
        } else if (currentStep === 4 && !bookingState.providerId) {
          Notify.warning('Please select a service provider to proceed.');
        } else if (currentStep === 5 && !bookingState.serviceId) {
          Notify.warning('Please select a service plan to proceed.');
        }
        return;
      }

      if (currentStep === 4 && !bookingState.providerId) {
        Notify.warning('Please select a service provider to proceed.');
        return;
      }

      if (currentStep === 5 && !bookingState.serviceId) {
        const container = document.getElementById('service-options-container');
        const firstCard = container?.querySelector('.selection-card');
        if (firstCard) {
          firstCard.click();
        }
        if (!bookingState.serviceId) {
          Notify.warning('Please select a service plan to proceed.');
          return;
        }
      }

      if (currentStep === 3) {
        bookingState.address = document.getElementById('loc-address').value.trim();
        bookingState.landmark = document.getElementById('loc-landmark').value.trim();
        bookingState.pincode = document.getElementById('loc-pincode').value.trim();
        bookingState.branch = document.getElementById('nearest-branch').value;

        const latVal = parseFloat(document.getElementById('loc-lat')?.value) || parseFloat(document.getElementById('modal-addr-lat')?.value) || null;
        const lngVal = parseFloat(document.getElementById('loc-lng')?.value) || parseFloat(document.getElementById('modal-addr-lng')?.value) || null;
        if (latVal && !isNaN(latVal) && latVal !== 0) bookingState.lat = latVal;
        if (lngVal && !isNaN(lngVal) && lngVal !== 0) bookingState.lng = lngVal;

        // Render providers based on chosen location without auto-inserting duplicate profile addresses
        renderProviders();
      }
      if (currentStep === 6) {
        bookingState.date = document.getElementById('sch-date').value; // Save selected date
      }
      if (currentStep === 7) {
        bookingState.brand = document.getElementById('v-brand').value.trim();
        bookingState.model = document.getElementById('v-model').value.trim();
        bookingState.regNo = document.getElementById('v-reg').value.trim();
        bookingState.color = document.getElementById('v-color').value.trim();
      }
      if (currentStep === 9) {
        const name = document.getElementById('cust-name').value.trim();
        const phone = document.getElementById('cust-phone').value.trim();
        const cleanPhone = phone.replace(/[\s\-+()]/g, '');
        if (!name) {
          alert('Please enter your full name.');
          return;
        }
        if (!phone || phone.includes('@') || !/^\d{10,12}$/.test(cleanPhone)) {
          alert('Please enter a valid 10-digit mobile number to proceed.');
          return;
        }
        bookingState.fullName = name;
        bookingState.phone = phone;
        bookingState.altPhone = document.getElementById('cust-alt-phone').value.trim();
        bookingState.email = document.getElementById('cust-email').value.trim();
      }
      if (currentStep === 10) {
        renderSummaryBreakdown();

        // Reset status box UI to pending state when entering Step 11
        resetAcceptStatusUI();

        bookingState.providerAccepted = false;

        // Create booking in DB as PENDING, then start polling for provider response
        createBookingAndStartPolling();
        return; // Transition is handled inside createBookingAndStartPolling()
      }
      if (currentStep === 12) {
        // Save payment method and advance to confirmation
        updateBookingPaymentInDB();
        return; // Transition handled inside promise callback
      }

      if (currentStep < maxSteps) {
        currentStep++;
        updateStepDisplay();
      }
    }

    function prevStep() {
      if (currentStep > 2) {
        // Stop polling if leaving the provider acceptance step
        if (currentStep === 11) {
          stopPolling();
          bookingState.bookingDbId = null;
          bookingState.bookingRefNum = null;
          bookingState.providerAccepted = false;
        }
        currentStep--;
        updateStepDisplay();
      }
    }

    // Step 1 Category Handler
    function selectCategory(cat) {
      bookingState.category = cat;

      // Clear selections down the line to trigger fresh updates
      bookingState.vehicleType = null;
      bookingState.providerId = null;
      bookingState.serviceId = null;
      document.querySelectorAll('#step-1 .selection-card').forEach(c => c.classList.remove('selected'));
      if (typeof event !== 'undefined' && event && event.currentTarget) {
        event.currentTarget.classList.add('selected');
      }

      // Store which subcategory IDs apply to this category
      bookingState.categorySubIds = dbCategoryMap[cat] || (cat === 'bike_wash' ? [2] : [1]);

      currentStep = 2;
      updateStepDisplay();
    }

    // Step 2 Vehicle Type Handler
    function selectVehicle(type) {
      if (type === 'Luxury') type = 'Luxury Vehicle';
      bookingState.vehicleType = type;

      // Auto-assign category
      const typeLower = type.toLowerCase();
      if (typeLower === 'bike' || typeLower === 'scooter') {
        bookingState.category = 'bike_wash';
        bookingState.categorySubIds = dbCategoryMap['bike_wash'] || [2];
      } else {
        bookingState.category = 'car_wash';
        bookingState.categorySubIds = dbCategoryMap['car_wash'] || [1];
      }

      document.querySelectorAll('#step-2 .selection-card').forEach(c => {
        const v = c.getAttribute('data-vehicle');
        if (v === type || (type === 'Luxury' && v === 'Luxury Vehicle') || (type === 'Luxury Vehicle' && v === 'Luxury Vehicle')) {
          c.classList.add('selected');
        } else {
          c.classList.remove('selected');
        }
      });

      currentStep = 3;
      updateStepDisplay();
    }

    // Step 4 Render Providers List
    function renderProviders() {
      const container = document.getElementById('providers-container');
      container.innerHTML = '';

      // Filter out rejected providers; only show those with matching services for selected category
      const categorySubIds = bookingState.categorySubIds || [];
      const activeProviders = dbProviders.filter(p => {
        if (bookingState.rejectedProviders.includes(parseInt(p.id))) return false;
        // If we have category subcategory IDs, only show providers that have at least 1 matching service
        if (categorySubIds.length > 0) {
          return dbAllServices.some(s => {
            if (parseInt(s.provider_id) !== parseInt(p.id)) return false;
            const subId = parseInt(s.subcategory_id);
            return categorySubIds.includes(subId);
          });
        }
        return true;
      });

      if (activeProviders.length === 0) {
        container.innerHTML = '<p class="wizard-subtitle" style="grid-column: span 2; text-align: center;">No active service providers found for this category in your area.</p>';
        return;
      }

      activeProviders.forEach(provider => {
        const card = document.createElement('div');
        card.className = 'selection-card';
        if (bookingState.providerId && parseInt(bookingState.providerId) === parseInt(provider.id)) {
          card.classList.add('selected');
        }
        card.onclick = () => selectProvider(card, provider.id, provider.business_name);

        const rating = parseFloat(provider.rating || 5.0).toFixed(1);
        const displayName = provider.company_name || provider.business_name;
        const addressLine = provider.location_address || 'Location on request';
        const rangeKm = provider.service_range_km ? parseFloat(provider.service_range_km).toFixed(0) + ' km radius' : 'Flexible range';
        const availBadge = (provider.is_available == 1)
          ? '<span style="font-size:0.72rem; color:#10b981; font-weight:700;">● Available</span>'
          : '<span style="font-size:0.72rem; color:#ef4444; font-weight:700;">● Busy</span>';

        card.innerHTML = `
          <div class="selection-icon"><ion-icon name="business-outline"></ion-icon></div>
          <div class="selection-title">${displayName}</div>
          <div class="selection-desc" style="font-weight: 700; color: #fbbf24; display: flex; align-items: center; gap: 4px; justify-content: center;">
            <ion-icon name="star"></ion-icon> ${rating} / 5.0
          </div>
          <div class="selection-desc" style="font-style: italic; font-size:0.8rem;">${addressLine}</div>
          <div style="font-size: 0.78rem; color: #64748b; margin-top: 4px;">${rangeKm} &nbsp;|&nbsp; ${availBadge}</div>
        `;
        container.appendChild(card);
      });
    }

    function selectProvider(cardElement, providerId, businessName) {
      bookingState.providerId = providerId;
      bookingState.providerName = businessName;

      document.querySelectorAll('#providers-container .selection-card').forEach(c => c.classList.remove('selected'));
      cardElement.classList.add('selected');

      // Set the service plan header text to personalize provider choice
      document.getElementById('service-plan-subtitle').textContent = `Choose an active wash or care package from ${businessName}.`;

      // Dynamically assign service center address of selected provider
      if (bookingState.locationType === 'center') {
        const prov = dbProviders.find(p => parseInt(p.id) === parseInt(providerId));
        if (prov) {
          bookingState.branch = prov.location_address || prov.provider_address || 'Service Provider Center';
        }
      }

      renderServicePlans();
      updateSlotAvailability();
      validateStepRequirements();
    }

    // Render Service Plans based on selected provider and vehicle selections
    function renderServicePlans() {
      const container = document.getElementById('service-options-container');
      container.innerHTML = '';

      const selectedProviderId = bookingState.providerId;
      if (!selectedProviderId) return;

      // Filter services by provider_id
      let plansToRender = dbAllServices.filter(s => parseInt(s.provider_id) === parseInt(selectedProviderId));

      // Filter by category subcategory IDs from DB map (strictly show only selected category services)
      const categorySubIds = bookingState.categorySubIds || [];
      if (categorySubIds.length > 0) {
        plansToRender = plansToRender.filter(s => {
          const subId = parseInt(s.subcategory_id);
          return categorySubIds.includes(subId);
        });
      }

      if (plansToRender.length === 0) {
        container.innerHTML = '<p class="wizard-subtitle" style="grid-column: span 2; text-align: center;">No active services found for this provider and vehicle type. Try a different category.</p>';
        return;
      }

      plansToRender.forEach(service => {
        const duration = service.duration_minutes || 60;
        const subcatLabel = service.subcategory_name || '';

        // Extract works list for service
        let serviceWorks = [];
        if (service.works_list) {
          try {
            serviceWorks = typeof service.works_list === 'string' ? JSON.parse(service.works_list) : service.works_list;
          } catch (e) { }
        }
        if (!Array.isArray(serviceWorks) || serviceWorks.length === 0) {
          if (service.description) {
            const descLines = service.description.split(/[\r\n]+/).map(s => s.trim()).filter(Boolean);
            if (descLines.length > 1) {
              serviceWorks = descLines;
            }
          }
        }

        if (service.packages && service.packages.length > 0) {
          service.packages.forEach(pkg => {
            const card = document.createElement('div');
            card.className = 'selection-card';
            if (bookingState.serviceId && parseInt(bookingState.serviceId) === parseInt(service.id) && bookingState.packageId && parseInt(bookingState.packageId) === parseInt(pkg.id)) {
              card.classList.add('selected');
            }
            card.onclick = () => selectServicePlan(card, service.id, `${service.name} — ${pkg.name}`, parseFloat(pkg.price), pkg.id);

            // Parse package features or fallback to service works list
            let featuresHtml = '';
            let feats = [];
            if (pkg.features) {
              try {
                feats = typeof pkg.features === 'string' ? JSON.parse(pkg.features) : pkg.features;
              } catch (e) { }
            }
            if (!Array.isArray(feats) || feats.length === 0) {
              feats = serviceWorks;
            }
            if (Array.isArray(feats) && feats.length > 0) {
              featuresHtml = '<ul style="text-align:left; font-size:0.82rem; color:#cbd5e1; margin:10px 0 10px 0; padding-left:0; list-style:none;">' +
                feats.map(f => `<li style="display:flex; align-items:center; gap:8px; margin-bottom:5px;"><ion-icon name="checkmark-circle" style="color:#10b981; flex-shrink:0; font-size:1.1rem;"></ion-icon> <span>${f}</span></li>`).join('') +
                '</ul>';
            }

            card.innerHTML = `
              <div class="selection-icon"><ion-icon name="construct-outline"></ion-icon></div>
              <div class="selection-title">${pkg.name}</div>
              <div style="font-size: 0.8rem; color: #a78bfa; font-weight: 700; margin-top: -4px;">${service.name}</div>
              <div class="selection-desc" style="font-style: italic;">${pkg.description || 'Professional service plan'}</div>
              ${featuresHtml}
              <div style="margin-top: 8px; font-weight: 700; color: #10b981; font-size: 1.2rem;">₹${parseFloat(pkg.price).toFixed(2)}</div>
              <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">⏱ ${duration} mins &bull; ${service.service_range_km ? parseFloat(service.service_range_km).toFixed(0) : '10'} km range</div>
            `;
            container.appendChild(card);
          });
        } else {
          const card = document.createElement('div');
          card.className = 'selection-card';
          if (bookingState.serviceId && parseInt(bookingState.serviceId) === parseInt(service.id)) {
            card.classList.add('selected');
          }
          card.onclick = () => selectServicePlan(card, service.id, service.name, parseFloat(service.base_price), null);

          let worksHtml = '';
          if (Array.isArray(serviceWorks) && serviceWorks.length > 0) {
            worksHtml = '<ul style="text-align:left; font-size:0.82rem; color:#cbd5e1; margin:10px 0 10px 0; padding-left:0; list-style:none;">' +
              serviceWorks.map(w => `<li style="display:flex; align-items:center; gap:8px; margin-bottom:5px;"><ion-icon name="checkmark-circle" style="color:#10b981; flex-shrink:0; font-size:1.1rem;"></ion-icon> <span>${w}</span></li>`).join('') +
              '</ul>';
          }

          card.innerHTML = `
            <div class="selection-icon"><ion-icon name="construct-outline"></ion-icon></div>
            <div class="selection-title">${service.name}</div>
            <div style="font-size: 0.8rem; color: #a78bfa; font-weight: 700; margin-top: -4px;">${subcatLabel}</div>
            <div class="selection-desc">${service.description || 'High quality professional service'}</div>
            ${worksHtml}
            <div style="margin-top: 8px; font-weight: 700; color: #10b981; font-size: 1.2rem;">₹${parseFloat(service.base_price).toFixed(2)}</div>
            <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">⏱ ${duration} mins &bull; ${service.service_range_km ? parseFloat(service.service_range_km).toFixed(0) : '10'} km range</div>
          `;
          container.appendChild(card);
        }
      });

      // Auto-select first available service plan if none is currently selected
      if (!bookingState.serviceId && plansToRender.length > 0) {
        const firstCard = container.querySelector('.selection-card');
        const firstSvc = plansToRender[0];
        if (firstCard && firstSvc) {
          if (firstSvc.packages && firstSvc.packages.length > 0) {
            const firstPkg = firstSvc.packages[0];
            selectServicePlan(firstCard, firstSvc.id, `${firstSvc.name} — ${firstPkg.name}`, parseFloat(firstPkg.price), firstPkg.id);
          } else {
            selectServicePlan(firstCard, firstSvc.id, firstSvc.name, parseFloat(firstSvc.base_price), null);
          }
        }
      }
    }

    function selectServicePlan(cardElement, serviceId, name, price, packageId) {
      bookingState.serviceId = parseInt(serviceId, 10);
      bookingState.serviceName = name;
      bookingState.servicePrice = parseFloat(price);
      bookingState.packageId = packageId ? parseInt(packageId, 10) : null;
      bookingState.extras = [];

      document.querySelectorAll('#service-options-container .selection-card').forEach(c => c.classList.remove('selected'));
      if (cardElement) {
        cardElement.classList.add('selected');
      }

      validateStepRequirements();
      if (typeof saveBookingState === 'function') {
        saveBookingState();
      }
    }

    // Step 3 Location Option Toggle
    function toggleLocationView(type) {
      bookingState.locationType = type;
      if (type === 'doorstep') {
        document.getElementById('doorstep-fields').style.display = 'block';
        document.getElementById('center-fields').style.display = 'none';
      } else {
        document.getElementById('doorstep-fields').style.display = 'none';
        document.getElementById('center-fields').style.display = 'block';
      }
      validateStepRequirements();
      if (typeof saveBookingState === 'function') {
        saveBookingState();
      }
    }

    // Step 6 Slot Selection
    function selectSlot(pill, slotText) {
      if (pill && pill.classList.contains('slot-booked')) {
        const alertMsg = "This service provider is already booked at this time. Please choose another slot.";
        if (typeof Swal !== 'undefined' && Swal.fire) {
          Swal.fire({
            icon: 'warning',
            title: 'Already Booked',
            text: alertMsg,
            confirmButtonText: 'OK',
            confirmButtonColor: '#8b5cf6'
          });
        } else if (typeof Notify !== 'undefined' && Notify.warning) {
          Notify.warning(alertMsg);
        } else {
          alert(alertMsg);
        }
        return;
      }
      bookingState.timeSlot = slotText;
      document.querySelectorAll('.slots-grid .slot-pill').forEach(p => p.classList.remove('selected'));
      pill.classList.add('selected');
      validateStepRequirements();
      if (typeof saveBookingState === 'function') {
        saveBookingState();
      }
    }

    async function updateSlotAvailability() {
      const dateInput = document.getElementById('sch-date');
      const dateVal = dateInput && dateInput.value ? dateInput.value : bookingState.date;
      const provId = bookingState.providerId;
      if (!dateVal || !provId) return;

      try {
        const availUrl = (window.serveasyApiUrl ? window.serveasyApiUrl(`provider-availability.php?provider_id=${encodeURIComponent(provId)}&date=${encodeURIComponent(dateVal)}`) : `../../../backend/api/provider-availability.php?provider_id=${encodeURIComponent(provId)}&date=${encodeURIComponent(dateVal)}`);
        const res = await fetch(availUrl);
        const json = await res.json();
        if (json.success && json.data) {
          const isSingle = !!json.data.is_single_provider;
          const bookedSlots = Array.isArray(json.data.booked_slots) ? json.data.booked_slots : [];
          applySlotAvailability(isSingle, bookedSlots);
        }
      } catch (err) {
        console.error('Error fetching provider availability:', err);
      }
    }

    function applySlotAvailability(isSingle, bookedSlots) {
      document.querySelectorAll('.slots-grid .slot-pill').forEach(pill => {
        let slotText = pill.getAttribute('data-slot');
        if (!slotText) {
          slotText = pill.textContent.trim().replace(/\s*Booked\s*$/i, '');
          pill.setAttribute('data-slot', slotText);
        }

        const isBooked = isSingle && bookedSlots.includes(slotText);
        if (isBooked) {
          pill.classList.add('slot-booked');
          pill.title = "This service provider is already booked at this time. Please choose another slot.";
          if (!pill.querySelector('.slot-booked-badge')) {
            const badge = document.createElement('span');
            badge.className = 'slot-booked-badge';
            badge.textContent = 'Booked';
            pill.appendChild(badge);
          }
          if (pill.classList.contains('selected')) {
            pill.classList.remove('selected');
            bookingState.timeSlot = '';
            validateStepRequirements();
          }
        } else {
          pill.classList.remove('slot-booked');
          pill.removeAttribute('title');
          const badge = pill.querySelector('.slot-booked-badge');
          if (badge) badge.remove();
        }
      });
    }

    // Step 8 Extras Selection
    function renderDynamicExtras() {
      const container = document.getElementById('extras-list-container');
      if (!container) return;
      container.innerHTML = '';

      const serviceId = bookingState.serviceId;
      if (!serviceId) {
        container.innerHTML = '<p class="wizard-subtitle" style="text-align: center;">Please select a service plan first.</p>';
        return;
      }

      const service = dbAllServices.find(s => parseInt(s.id) === parseInt(serviceId));
      if (!service || !service.addons || service.addons.length === 0) {
        container.innerHTML = '<p class="wizard-subtitle" style="text-align: center; color: #94a3b8; font-style: italic;">No additional small services/addons available for this plan.</p>';
        return;
      }

      service.addons.forEach(addon => {
        const extraName = addon.name;
        const extraPrice = parseFloat(addon.price);

        const card = document.createElement('div');
        card.className = 'extras-item-glass';

        const isChecked = bookingState.extras.some(e => e.name === extraName);
        if (isChecked) {
          card.classList.add('selected');
        }

        card.onclick = () => toggleExtra(card, extraName, extraPrice);

        card.innerHTML = `
          <div class="extras-left">
            <input type="checkbox" class="extras-checkbox" ${isChecked ? 'checked' : ''} readonly>
            <div>
              <span class="extras-name">${extraName}</span>
            </div>
          </div>
          <span class="extras-price">+₹${extraPrice.toFixed(2)}</span>
        `;
        container.appendChild(card);
      });
    }

    function toggleExtra(itemElement, extraName, price) {
      const checkbox = itemElement.querySelector('.extras-checkbox');
      const idx = bookingState.extras.findIndex(e => e.name === extraName);

      if (idx > -1) {
        bookingState.extras.splice(idx, 1);
        checkbox.checked = false;
        itemElement.classList.remove('selected');
      } else {
        bookingState.extras.push({ name: extraName, price: price });
        checkbox.checked = true;
        itemElement.classList.add('selected');
      }
      if (typeof saveBookingState === 'function') {
        saveBookingState();
      }
    }

    // Step 10 Summary Renderer
    function renderSummaryBreakdown() {
      // Sync vehicle inputs if available
      const brandInput = document.getElementById('v-brand');
      if (brandInput && brandInput.value.trim()) bookingState.brand = brandInput.value.trim();
      const modelInput = document.getElementById('v-model');
      if (modelInput && modelInput.value.trim()) bookingState.model = modelInput.value.trim();
      const regInput = document.getElementById('v-reg');
      if (regInput && regInput.value.trim()) bookingState.regNo = regInput.value.trim();
      const colorInput = document.getElementById('v-color');
      if (colorInput) bookingState.color = colorInput.value.trim();

      // Sync location inputs if available
      const addrInput = document.getElementById('loc-address');
      if (addrInput && addrInput.value.trim()) bookingState.address = addrInput.value.trim();
      const lmkInput = document.getElementById('loc-landmark');
      if (lmkInput && lmkInput.value.trim()) bookingState.landmark = lmkInput.value.trim();
      const pinInput = document.getElementById('loc-pincode');
      if (pinInput && pinInput.value.trim()) bookingState.pincode = pinInput.value.trim();
      const branchInput = document.getElementById('nearest-branch');
      if (branchInput && branchInput.value) bookingState.branch = branchInput.value;
      const latInput = document.getElementById('loc-lat');
      const lngInput = document.getElementById('loc-lng');
      if (latInput && latInput.value) {
        const parsedLat = parseFloat(latInput.value);
        if (!isNaN(parsedLat) && parsedLat !== 0) bookingState.lat = parsedLat;
      }
      if (lngInput && lngInput.value) {
        const parsedLng = parseFloat(lngInput.value);
        if (!isNaN(parsedLng) && parsedLng !== 0) bookingState.lng = parsedLng;
      }

      // Sync schedule date if available
      const dateInput = document.getElementById('sch-date');
      if (dateInput && dateInput.value) bookingState.date = dateInput.value;

      const sumVType = document.getElementById('sum-v-type');
      if (sumVType) sumVType.textContent = bookingState.vehicleType || 'Bike';

      const specParts = [];
      const brandClean = (bookingState.brand || '').trim();
      const modelClean = (bookingState.model || '').trim();
      let vehicleName = '';
      if (brandClean && modelClean) {
        if (brandClean.toLowerCase() === modelClean.toLowerCase()) {
          vehicleName = brandClean;
        } else if (modelClean.toLowerCase().startsWith(brandClean.toLowerCase())) {
          vehicleName = modelClean;
        } else if (brandClean.toLowerCase().startsWith(modelClean.toLowerCase())) {
          vehicleName = brandClean;
        } else {
          vehicleName = `${brandClean} ${modelClean}`;
        }
      } else {
        vehicleName = brandClean || modelClean || '';
      }
      if (vehicleName) specParts.push(vehicleName);
      let specString = specParts.join(' ');
      if (bookingState.regNo) specString += ` (${bookingState.regNo})`;
      if (bookingState.color) specString += ` - ${bookingState.color}`;
      const sumVSpecs = document.getElementById('sum-v-specs');
      if (sumVSpecs) sumVSpecs.textContent = specString.trim() || 'N/A';

      const locString = bookingState.locationType === 'doorstep'
        ? `Doorstep Service: ${bookingState.address || ''}${bookingState.landmark ? ', Landmark: ' + bookingState.landmark : ''}${bookingState.pincode ? ', Pincode: ' + bookingState.pincode : ''}`
        : `Visit Branch: ${bookingState.branch || 'Nearest Branch'}`;
      const sumLocation = document.getElementById('sum-location');
      if (sumLocation) sumLocation.textContent = locString;

      const sumSchedule = document.getElementById('sum-schedule');
      if (sumSchedule) sumSchedule.textContent = `${bookingState.date || ''}${bookingState.timeSlot ? ' at ' + bookingState.timeSlot : ''}`;

      // Auto-resolve missing serviceId & base price from provider if available
      if (!bookingState.serviceId && bookingState.providerId) {
        const catSubIds = bookingState.categorySubIds || [];
        const matchingSvc = dbAllServices.find(s => 
          parseInt(s.provider_id, 10) === parseInt(bookingState.providerId, 10) &&
          (catSubIds.length === 0 || catSubIds.includes(parseInt(s.subcategory_id, 10)))
        ) || dbAllServices.find(s => parseInt(s.provider_id, 10) === parseInt(bookingState.providerId, 10));
        
        if (matchingSvc) {
          bookingState.serviceId = parseInt(matchingSvc.id, 10);
          bookingState.serviceName = matchingSvc.name;
          bookingState.servicePrice = parseFloat(matchingSvc.base_price);
        }
      }

      const sumServiceName = document.getElementById('sum-service-name');
      if (sumServiceName) sumServiceName.textContent = `${bookingState.serviceName || 'Vehicle Wash'}${bookingState.providerName ? ' (by ' + bookingState.providerName + ')' : ''}`;

      const sumServicePrice = document.getElementById('sum-service-price');
      if (sumServicePrice) sumServicePrice.textContent = `₹${(bookingState.servicePrice || 0).toFixed(2)}`;

      // Display Extras
      const extrasContainer = document.getElementById('sum-extras-list-container');
      let extrasSum = 0;
      if (extrasContainer) {
        extrasContainer.innerHTML = '';
        (bookingState.extras || []).forEach(e => {
          extrasSum += (e.price || 0);
          const row = document.createElement('div');
          row.className = 'summary-row';
          row.style.color = '#94a3b8';
          row.innerHTML = `
            <span>+ ${e.name} (Add-on)</span>
            <span>₹${(e.price || 0).toFixed(2)}</span>
          `;
          extrasContainer.appendChild(row);
        });
      }

      // Calculate total
      const subtotal = (bookingState.servicePrice || 0) + extrasSum;
      const sumSubtotal = document.getElementById('sum-subtotal');
      if (sumSubtotal) sumSubtotal.textContent = `₹${subtotal.toFixed(2)}`;

      // Discount
      let discount = 0;
      const discountRow = document.getElementById('sum-discount-row');
      const sumDiscount = document.getElementById('sum-discount');
      if (bookingState.category === 'subscription') {
        discount = subtotal * 0.10;
        if (discountRow) discountRow.style.display = 'flex';
        if (sumDiscount) sumDiscount.textContent = `-₹${discount.toFixed(2)}`;
      } else {
        if (discountRow) discountRow.style.display = 'none';
      }

      // Convenience Fee: dynamic subcategory scope check
      let isSubcatInScope = true;
      if (platformFeeScope === 'specific') {
        const checkSlugs = ['car-wash', 'bike-wash', 'vehicle-repair', 'others-wash-services', '1', '2', '3', '41'];
        if (bookingState.category === 'bike_wash') {
          isSubcatInScope = platformFeeScopeList.includes('bike-wash') || platformFeeScopeList.includes('2') || platformFeeScopeList.includes(2);
        } else if (bookingState.category === 'car_wash') {
          isSubcatInScope = platformFeeScopeList.includes('car-wash') || platformFeeScopeList.includes('1') || platformFeeScopeList.includes(1);
        } else if (Array.isArray(bookingState.categorySubIds) && bookingState.categorySubIds.length > 0) {
          isSubcatInScope = bookingState.categorySubIds.some(id => platformFeeScopeList.includes(String(id)) || (id == 1 && platformFeeScopeList.includes('car-wash')) || (id == 2 && platformFeeScopeList.includes('bike-wash')));
        } else {
          isSubcatInScope = platformFeeScopeList.some(s => checkSlugs.includes(s));
        }
      }

      let convenienceFee = 0;
      if (platformFeeActive && isSubcatInScope) {
        if (platformFeeType === 'percentage') {
          convenienceFee = (subtotal * platformConvenienceFee) / 100;
        } else {
          convenienceFee = platformConvenienceFee;
        }
      }

      const convFeeLabel = document.getElementById('sum-convenience-fee-label');
      const convFeeEl = document.getElementById('sum-convenience-fee');
      if (convFeeLabel) {
        if (platformFeeActive && !isSubcatInScope) {
          convFeeLabel.textContent = 'Platform Charge (Waived for Subcategory):';
        } else if (platformFeeActive && platformFeeType === 'percentage') {
          convFeeLabel.textContent = `Platform Charge (${platformConvenienceFee}%):`;
        } else {
          convFeeLabel.textContent = 'Platform Charge:';
        }
      }
      if (convFeeEl) {
        convFeeEl.textContent = `₹${convenienceFee.toFixed(2)}`;
      }

      const total = subtotal + convenienceFee - discount;
      const sumTotal = document.getElementById('sum-total');
      if (sumTotal) sumTotal.textContent = `₹${total.toFixed(2)}`;

      // Save totals into state
      bookingState.totalAmount = total;
    }

    // Step 11 Payment Option Select
    function selectPayMethod(method) {
      bookingState.paymentMethod = method;
      document.querySelectorAll('.pay-method-card').forEach(c => c.classList.remove('selected'));

      if (method === 'COD') {
        document.getElementById('pay-cod').classList.add('selected');
        document.getElementById('online-pay-details').style.display = 'none';
      } else {
        document.getElementById('pay-online').classList.add('selected');
        document.getElementById('online-pay-details').style.display = 'block';
      }
      if (typeof saveBookingState === 'function') {
        saveBookingState();
      }
    }

    // ----------------------------------------------------------
    // Step 10 → 11: Create booking as PENDING then start polling
    // ----------------------------------------------------------
    // Polling and Timer state variables initialized at script top: _pollingInterval, _acceptTimerInterval, _acceptStartTime, _customerEventSource

    function startAcceptTimer() {
      stopAcceptTimer();
      _acceptStartTime = Date.now();
      const countEl = document.getElementById('accept-timer-count');
      if (countEl) countEl.textContent = '00:00';
      _acceptTimerInterval = setInterval(() => {
        const elapsedSecs = Math.floor((Date.now() - _acceptStartTime) / 1000);
        const mins = String(Math.floor(elapsedSecs / 60)).padStart(2, '0');
        const secs = String(elapsedSecs % 60).padStart(2, '0');
        if (countEl) countEl.textContent = `${mins}:${secs}`;
      }, 1000);
    }

    function stopAcceptTimer() {
      if (_acceptTimerInterval) {
        clearInterval(_acceptTimerInterval);
        _acceptTimerInterval = null;
      }
    }

    function resetAcceptStatusUI() {
      const provName = bookingState.providerName || 'Service Provider';
      const elProvName = document.getElementById('accept-provider-name');
      if (elProvName) elProvName.textContent = provName;
      const cardName = document.getElementById('accept-provider-card-name');
      if (cardName) cardName.textContent = provName;
      const cardAvatar = document.getElementById('accept-provider-avatar');
      if (cardAvatar) cardAvatar.textContent = provName.charAt(0).toUpperCase();
      const cardService = document.getElementById('accept-provider-card-service');
      if (cardService) cardService.textContent = `${bookingState.vehicleType || 'Vehicle'} • ${bookingState.plan || 'Wash'}`;

      const statusSpinner = document.getElementById('accept-status-spinner');
      if (statusSpinner) {
        statusSpinner.className = 'radar-hub pending';
        statusSpinner.style.animation = '';
        statusSpinner.style.color = '';
        statusSpinner.innerHTML = '<ion-icon name="radio-outline"></ion-icon>';
      }
      const orbital1 = document.getElementById('radar-orbital-spinner');
      if (orbital1) orbital1.style.borderColor = '';
      const orbital2 = document.getElementById('radar-orbital-counter');
      if (orbital2) orbital2.style.display = 'block';
      const liveBadge = document.getElementById('radar-live-badge');
      if (liveBadge) {
        liveBadge.className = 'radar-live-badge';
        const stText = document.getElementById('radar-live-status-text');
        if (stText) stText.textContent = 'Connecting...';
      }
      const statusTitle = document.getElementById('accept-status-title');
      if (statusTitle) statusTitle.textContent = 'Awaiting Provider Response...';
      const statusDesc = document.getElementById('accept-status-desc');
      if (statusDesc) statusDesc.textContent = 'The service provider has been instantly notified of your wash request and schedule.';
    }

    function stopPolling() {
      if (_customerEventSource) {
        try { _customerEventSource.close(); } catch (e) {}
        _customerEventSource = null;
      }
      if (_pollingInterval) {
        clearInterval(_pollingInterval);
        _pollingInterval = null;
      }
      stopAcceptTimer();
    }

    async function createBookingAndStartPolling() {
      // Show loading on Next button while creating booking
      const btnNext = document.getElementById('btn-next');
      const origText = btnNext.innerHTML;
      btnNext.innerHTML = '<ion-icon name="sync-outline" style="animation:spin 1s linear infinite;"></ion-icon> Sending...';
      btnNext.disabled = true;

      // Build formatted address
      let formattedAddress = bookingState.address;
      if (bookingState.locationType === 'center') {
        formattedAddress = `Service Center: ${bookingState.branch}`;
      } else {
        formattedAddress = `${bookingState.address}, Landmark: ${bookingState.landmark || 'N/A'}, Pincode: ${bookingState.pincode}`;
      }

      // Notes include vehicle color and alt phone
      let notes = `Vehicle Color: ${bookingState.color || 'N/A'}`;
      if (bookingState.altPhone) {
        notes += ` | Alt Contact: ${bookingState.altPhone}`;
      }

      // Ensure service_id is resolved before submitting booking payload (Zero-Defect Multi-Probability Guard)
      if (!bookingState.serviceId && bookingState.providerId) {
        const catSubIds = bookingState.categorySubIds || [];
        const matchingSvc = dbAllServices.find(s => 
          parseInt(s.provider_id, 10) === parseInt(bookingState.providerId, 10) &&
          (catSubIds.length === 0 || catSubIds.includes(parseInt(s.subcategory_id, 10)))
        ) || dbAllServices.find(s => parseInt(s.provider_id, 10) === parseInt(bookingState.providerId, 10));
        
        if (matchingSvc) {
          bookingState.serviceId = parseInt(matchingSvc.id, 10);
          bookingState.serviceName = matchingSvc.name;
          bookingState.servicePrice = parseFloat(matchingSvc.base_price);
          if (typeof renderSummaryBreakdown === 'function') {
            renderSummaryBreakdown();
          }
        }
      }

      if (!bookingState.serviceId) {
        btnNext.innerHTML = origText;
        btnNext.disabled = false;
        Notify.error('Service plan missing. Please select a service plan to continue.');
        currentStep = 5;
        updateStepDisplay();
        return;
      }

      // Resolve coordinates
      const latVal = (bookingState.lat && !isNaN(bookingState.lat) && bookingState.lat !== 0)
        ? bookingState.lat
        : (parseFloat(document.getElementById('loc-lat')?.value) || parseFloat(document.getElementById('modal-addr-lat')?.value) || null);
      const lngVal = (bookingState.lng && !isNaN(bookingState.lng) && bookingState.lng !== 0)
        ? bookingState.lng
        : (parseFloat(document.getElementById('loc-lng')?.value) || parseFloat(document.getElementById('modal-addr-lng')?.value) || null);

      // Build payload – status=PENDING, no payment info yet (Atomic Booking with Address & Slot Validation)
      const effectiveCustomerId = bookingState.customerId || <?= (int)($_SESSION['user_id'] ?? 0) ?>;
      const payload = {
        customer_id: effectiveCustomerId,
        service_id: bookingState.serviceId,
        package_id: bookingState.packageId,
        provider_id: bookingState.providerId,
        schedule_date: bookingState.date,
        schedule_time: String(bookingState.timeSlot || '').split(/\s*[–-]\s*/)[0].trim(),
        address: formattedAddress,
        raw_address: bookingState.address || null,
        address_label: bookingState.addressLabel || 'Home',
        landmark: bookingState.landmark || null,
        pincode: bookingState.pincode || null,
        save_address: 1,
        latitude: latVal,
        longitude: lngVal,
        lat: latVal,
        lng: lngVal,
        notes: notes,
        vehicle_type: bookingState.vehicleType,
        vehicle_brand: bookingState.brand,
        vehicle_model: bookingState.model,
        vehicle_reg_no: bookingState.regNo,
        vehicle_color: bookingState.color || null,
        location_type: bookingState.locationType,
        extras: JSON.stringify(bookingState.extras),
        total_amount: bookingState.totalAmount,
        status: 'PENDING'
      };

      try {
        const isLocalHost = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');
        let bUrl = (window.serveasyApiUrl ? window.serveasyApiUrl("bookings.php") : ((window.SERVEASY_API_BASE || "https://beige-curlew-610555.hostingersite.com/api/") + "bookings.php"));
        if (!isLocalHost && (bUrl.indexOf('ServEasy-v2') !== -1 || bUrl.indexOf(window.location.origin) === 0 || bUrl.indexOf('../') === 0)) {
          bUrl = 'https://beige-curlew-610555.hostingersite.com/api/bookings.php';
        }
        const res = await fetch(bUrl, {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Customer-Id': String(effectiveCustomerId)
          },
          body: JSON.stringify(payload)
        });
        const rawText = await res.text();
        let result;
        try {
          result = JSON.parse(rawText);
        } catch (parseErr) {
          throw new Error(`Invalid server response (HTTP ${res.status} from ${bUrl})`);
        }

        if (result.success && result.data) {
          const booking = result.data;
          bookingState.bookingDbId = booking.id;
          bookingState.bookingRefNum = booking.booking_number;
          if (booking.customer_id) {
            bookingState.customerId = parseInt(booking.customer_id);
          }

          // Move to Step 11 and begin listening via SSE
          const provName = bookingState.providerName || 'Service Provider';
          const elProvName = document.getElementById('accept-provider-name');
          if (elProvName) elProvName.textContent = provName;
          const cardName = document.getElementById('accept-provider-card-name');
          if (cardName) cardName.textContent = provName;
          const cardAvatar = document.getElementById('accept-provider-avatar');
          if (cardAvatar) cardAvatar.textContent = provName.charAt(0).toUpperCase();
          const cardService = document.getElementById('accept-provider-card-service');
          if (cardService) cardService.textContent = `${bookingState.vehicleType || 'Vehicle'} • ${bookingState.plan || 'Wash'}`;

          resetAcceptStatusUI();
          currentStep = 11;
          updateStepDisplay();

          if (typeof Notify !== 'undefined' && Notify.clearAll) {
            Notify.clearAll();
          }
          Notify.info(`Booking request sent to ${provName}. Awaiting confirmation...`, { title: 'Request Dispatched', clearExisting: true });

          try {
            startProviderPolling();
          } catch (pollErr) {
            console.warn('Provider polling start warning:', pollErr);
          }
        } else {
          Notify.error('Booking creation failed: ' + (result.message || 'Unknown error'));
        }
      } catch (err) {
        console.error('Booking API Error', err);
        const msg = (err && err.message && err.message !== 'Failed to fetch') 
          ? err.message 
          : 'Failed to connect to backend. Check your connection.';
        Notify.error(msg);
      } finally {
        btnNext.innerHTML = origText;
        validateStepRequirements();
      }
    }

    // Connect to Real-Time SSE Stream with Instant Direct Verification & Active Fallback Polling (Multi-Probability Architecture)
    function startProviderPolling() {
      stopPolling(); // Clear any existing connection/timers
      startAcceptTimer();

      const custId = bookingState.customerId || <?= (int)($_SESSION['user_id'] ?? (\App\Helpers\Auth::id() ?? 0)) ?>;

      // Active Database Status Verification (Dimension 2 & Dimension 5)
      async function checkBookingStatus() {
        if (!bookingState.bookingDbId) return;
        try {
          const pollUrl = (window.serveasyApiUrl ? window.serveasyApiUrl(`bookings.php?id=${bookingState.bookingDbId}&customer_id=${custId}`) : `../../../backend/api/bookings.php?id=${bookingState.bookingDbId}&customer_id=${custId}`);
          const res = await fetch(pollUrl, {
            method: 'GET',
            credentials: 'include',
            cache: 'no-store',
            headers: {
              'Accept': 'application/json',
              'X-Customer-Id': String(custId)
            }
          });
          if (!res.ok) return;
          const result = await res.json();
          if (result && result.success && result.data) {
            // Dismiss lingering connection error toasts on active backend recovery
            const tc = document.getElementById('se-toast-container');
            if (tc) {
              tc.querySelectorAll('.se-toast--error').forEach(el => el.remove());
            }

            const dbStatus = String(result.data.status || '').toLowerCase().trim();
            if (['accepted', 'confirmed', 'in_progress', 'completed'].includes(dbStatus)) {
              stopPolling();
              onProviderAccepted();
            } else if (['rejected', 'cancelled', 'declined'].includes(dbStatus)) {
              stopPolling();
              onProviderRejected();
            }
          }
        } catch (err) {
          console.debug('Status check poll error:', err);
        }
      }

      // 1. Immediate 0ms check in case provider accepted before/during step transition
      checkBookingStatus();

      // 2. Active fallback poll every 2 seconds ensures the screen NEVER freezes even if SSE is queued or drops
      _pollingInterval = setInterval(checkBookingStatus, 2000);

      // 3. Real-Time Push Stream (SSE for zero-latency instant updates)
      if (window.EventSource) {
        const sseUrl = (window.serveasyApiUrl ? window.serveasyApiUrl(`sse-events.php?channel=customer&customer_id=${custId}`) : `../../../backend/api/sse-events.php?channel=customer&customer_id=${custId}`);
        _customerEventSource = new EventSource(sseUrl, { withCredentials: true });

        // Event: Provider accepted booking
        _customerEventSource.addEventListener('booking.accepted', function (e) {
          try {
            const data = JSON.parse(e.data);
            if (!bookingState.bookingDbId || parseInt(data.booking_id) === parseInt(bookingState.bookingDbId)) {
              stopPolling();
              onProviderAccepted();
            }
          } catch (err) {
            console.warn('SSE booking.accepted parse error', err);
          }
        });

        // Event: Provider rejected booking
        _customerEventSource.addEventListener('booking.rejected', function (e) {
          try {
            const data = JSON.parse(e.data);
            if (!bookingState.bookingDbId || parseInt(data.booking_id) === parseInt(bookingState.bookingDbId)) {
              stopPolling();
              onProviderRejected();
            }
          } catch (err) {
            console.warn('SSE booking.rejected parse error', err);
          }
        });

        // Event: General booking status updates
        _customerEventSource.addEventListener('booking.updated', function (e) {
          try {
            const data = JSON.parse(e.data);
            if (!bookingState.bookingDbId || parseInt(data.booking_id) === parseInt(bookingState.bookingDbId)) {
              const dbStatus = String(data.status || '').toLowerCase().trim();
              if (['accepted', 'confirmed', 'in_progress', 'completed'].includes(dbStatus)) {
                stopPolling();
                onProviderAccepted();
              } else if (['rejected', 'cancelled', 'declined'].includes(dbStatus)) {
                stopPolling();
                onProviderRejected();
              }
            }
          } catch (err) {
            console.warn('SSE booking.updated parse error', err);
          }
        });

        _customerEventSource.onerror = function (err) {
          console.debug('SSE stream cycling or reconnecting...', err);
          checkBookingStatus();
        };
      }
    }

    function onProviderAccepted() {
      stopAcceptTimer();
      const provName = bookingState.providerName || 'Service Provider';
      const elProvName = document.getElementById('accept-provider-name');
      if (elProvName) elProvName.textContent = provName;
      const cardName = document.getElementById('accept-provider-card-name');
      if (cardName) cardName.textContent = provName;
      const cardAvatar = document.getElementById('accept-provider-avatar');
      if (cardAvatar) cardAvatar.textContent = provName.charAt(0).toUpperCase();
      const cardService = document.getElementById('accept-provider-card-service');
      if (cardService) cardService.textContent = `${bookingState.vehicleType || 'Vehicle'} • ${bookingState.plan || 'Wash'}`;

      const statusSpinner = document.getElementById('accept-status-spinner');
      const statusTitle = document.getElementById('accept-status-title');
      const statusDesc = document.getElementById('accept-status-desc');
      if (statusSpinner) {
        statusSpinner.className = 'radar-hub accepted';
        statusSpinner.style.animation = '';
        statusSpinner.style.color = '';
        statusSpinner.innerHTML = '<ion-icon name="checkmark-outline"></ion-icon>';
      }
      const orbital1 = document.getElementById('radar-orbital-spinner');
      if (orbital1) orbital1.style.borderColor = 'rgba(16, 185, 129, 0.4)';
      const liveBadge = document.getElementById('radar-live-badge');
      if (liveBadge) {
        liveBadge.className = 'radar-live-badge accepted';
        const stText = document.getElementById('radar-live-status-text');
        if (stText) stText.textContent = 'Accepted';
      }
      if (statusTitle) statusTitle.innerHTML = '<span style="color:#10b981;"><ion-icon name="checkmark-circle" style="vertical-align:-2px; margin-right:4px;"></ion-icon> Request Accepted!</span>';
      if (statusDesc) statusDesc.textContent = `${bookingState.providerName || 'The service provider'} has accepted your booking! Click "Next" to choose your payment method.`;
      bookingState.providerAccepted = true;
      validateStepRequirements();
      if (typeof saveBookingState === 'function') {
        saveBookingState();
      }

      if (typeof Notify !== 'undefined' && Notify.clearAll) {
        Notify.clearAll();
      }
      Notify.success(`🎉 ${provName} accepted your booking! Click "Next" to choose payment method.`, { title: 'Booking Confirmed', clearExisting: true });

      // Seamless Auto-Advance: Transition smoothly to Payment (Step 12) after 1.8 seconds
      if (window._autoAdvanceTimeout) clearTimeout(window._autoAdvanceTimeout);
      window._autoAdvanceTimeout = setTimeout(() => {
        if (currentStep === 11 && bookingState.providerAccepted) {
          currentStep = 12;
          updateStepDisplay();
        }
      }, 1800);
    }

    function onProviderRejected() {
      stopAcceptTimer();
      const statusSpinner = document.getElementById('accept-status-spinner');
      const statusTitle = document.getElementById('accept-status-title');
      const statusDesc = document.getElementById('accept-status-desc');
      if (statusSpinner) {
        statusSpinner.className = 'radar-hub rejected';
        statusSpinner.style.animation = '';
        statusSpinner.style.color = '';
        statusSpinner.innerHTML = '<ion-icon name="close-outline"></ion-icon>';
      }
      const orbital1 = document.getElementById('radar-orbital-spinner');
      if (orbital1) orbital1.style.borderColor = 'rgba(239, 68, 68, 0.4)';
      const liveBadge = document.getElementById('radar-live-badge');
      if (liveBadge) {
        liveBadge.className = 'radar-live-badge rejected';
        const stText = document.getElementById('radar-live-status-text');
        if (stText) stText.textContent = 'Declined';
      }
      if (statusTitle) statusTitle.innerHTML = '<span style="color:#ef4444;"><ion-icon name="close-circle" style="vertical-align:-2px; margin-right:4px;"></ion-icon> Request Declined</span>';
      if (statusDesc) {
        statusDesc.innerHTML = `
          <p style="margin-bottom:18px;">${bookingState.providerName} is currently unavailable or fully booked for this time slot.</p>
          <button class="btn-wizard-next" style="margin:0 auto;display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:10px 22px;border-radius:12px;" onclick="goToSelectNewProvider()">
            <ion-icon name="refresh-outline"></ion-icon> Select New Provider
          </button>
        `;
      }
      if (bookingState.providerId && !bookingState.rejectedProviders.includes(parseInt(bookingState.providerId))) {
        bookingState.rejectedProviders.push(parseInt(bookingState.providerId));
      }
      bookingState.providerAccepted = false;
      validateStepRequirements();

      if (typeof Notify !== 'undefined' && Notify.clearAll) {
        Notify.clearAll();
      }
      Notify.warning(`${bookingState.providerName || 'The service provider'} is unavailable for this time slot. Please select another provider.`, { title: 'Request Declined', clearExisting: true });
    }

    // ----------------------------------------------------------
    // Step 12: Save payment method then move to confirmation
    // ----------------------------------------------------------
    async function updateBookingPaymentInDB() {
      const btnNext = document.getElementById('btn-next');
      const origText = btnNext.innerHTML;
      btnNext.innerHTML = '<ion-icon name="sync-outline" style="animation:spin 1s linear infinite;"></ion-icon> Processing...';
      btnNext.disabled = true;

      try {
        if (bookingState.paymentMethod === 'Online' && bookingState.bookingDbId) {
          // Initialize Razorpay Order via backend/api/create-payment-order.php
          const orderApiUrl = (window.serveasyApiUrl ? window.serveasyApiUrl("create-payment-order.php") : ((window.SERVEASY_API_BASE || "../../../backend/api/") + "create-payment-order.php"));
          const orderRes = await fetch(orderApiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              booking_id: bookingState.bookingDbId,
              amount: bookingState.totalAmount || 40.00
            })
          });
          const orderJson = await orderRes.json();
          if (orderJson.success && orderJson.data) {
            const orderData = orderJson.data;
            if (typeof window.Razorpay === 'function') {
              const rzp = new window.Razorpay({
                key: orderData.key_id,
                amount: orderData.amount,
                currency: orderData.currency || 'INR',
                name: 'ServEasy Services',
                description: 'Booking #' + (orderData.booking_number || bookingState.bookingRefNum),
                order_id: orderData.order_id,
                prefill: {
                  name: bookingState.fullName || '',
                  email: bookingState.email || '',
                  contact: bookingState.phone || ''
                },
                theme: { color: '#10b981' },
                handler: async function (response) {
                  try {
                    const verifyApiUrl = (window.serveasyApiUrl ? window.serveasyApiUrl("verify-payment.php") : ((window.SERVEASY_API_BASE || "../../../backend/api/") + "verify-payment.php"));
                    await fetch(verifyApiUrl, {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify({
                        booking_id: bookingState.bookingDbId,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_signature: response.razorpay_signature
                      })
                    });
                  } catch (e) {
                    console.error('Payment verification error', e);
                  }
                  document.getElementById('conf-booking-id').textContent = bookingState.bookingRefNum;
                  document.getElementById('conf-service-name').textContent = bookingState.serviceName;
                  document.getElementById('conf-location-type').textContent = bookingState.locationType === 'doorstep' ? 'Doorstep Clean' : 'Center Visit';
                  document.getElementById('conf-pay-status').textContent = 'Paid Online (Razorpay Verified)';
                  currentStep = 13;
                  updateStepDisplay();
                },
                modal: {
                  ondismiss: function () {
                    btnNext.innerHTML = origText;
                    btnNext.disabled = false;
                  }
                }
              });
              rzp.open();
              return;
            }
          }
        }

        // Update payment method on existing booking record (COD or fallback)
        if (bookingState.bookingDbId) {
          const bUrl2 = (window.serveasyApiUrl ? window.serveasyApiUrl("bookings.php") : ((window.SERVEASY_API_BASE || "../../../backend/api/") + "bookings.php"));
          await fetch(bUrl2, {
            method: 'POST',
            credentials: 'include',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Customer-Id': String(bookingState.customerId || 0)
            },
            body: JSON.stringify({
              action: 'update_payment',
              id: bookingState.bookingDbId,
              payment_method: bookingState.paymentMethod
            })
          });
        }

        // Populate confirmation step details
        document.getElementById('conf-booking-id').textContent = bookingState.bookingRefNum;
        document.getElementById('conf-service-name').textContent = bookingState.serviceName;
        document.getElementById('conf-location-type').textContent = bookingState.locationType === 'doorstep' ? 'Doorstep Clean' : 'Center Visit';
        document.getElementById('conf-pay-status').textContent = bookingState.paymentMethod === 'COD' ? 'Pay on Service (COD)' : 'Paid Online (Cards)';

        currentStep = 13;
        updateStepDisplay();

        if (typeof Notify !== 'undefined' && Notify.clearAll) {
          Notify.clearAll();
        }
        Notify.success(`Booking confirmed! Reference: ${bookingState.bookingRefNum}`, { title: 'Booking Confirmed', clearExisting: true });
      } catch (err) {
        console.error('Payment update error', err);
        currentStep = 13;
        updateStepDisplay();
      } finally {
        btnNext.innerHTML = origText;
        validateStepRequirements();
      }
    }

    // Step 13 Notification Simulation Alerts
    function triggerNotificationSim(channel) {
      const custPhone = bookingState.phone || 'your mobile number';
      const custEmail = bookingState.email || 'your registered email';
      const custName = bookingState.fullName || 'Customer';
      const totalAmt = (bookingState.totalAmount || 0).toFixed(2);
      const payLabel = bookingState.paymentMethod === 'COD' ? 'Pay on Service (Cash)' : 'Online Paid';
      const schedDate = bookingState.date || 'today';
      const schedTime = bookingState.timeSlot || 'scheduled slot';

      const textMap = {
        'SMS': `Simulated SMS sent to ${custPhone}: "ServEasy Booking confirmed! Reference ID: ${bookingState.bookingRefNum}. Representative scheduled for ${schedDate} at ${schedTime}. Total: ₹${totalAmt} (${payLabel})."`,
        'WhatsApp': `Simulated WhatsApp dispatch to ${custPhone}: "Hello ${custName}! Your ServEasy Vehicle Care booking is confirmed. Ref: ${bookingState.bookingRefNum}. Price: ₹${totalAmt}. Status: Confirmed (${payLabel})."`,
        'Email': `Simulated Email dispatched to ${custEmail}: "ServEasy Order Receipt. Thank you for booking ${bookingState.serviceName}. Scheduled for ${schedDate} at ${schedTime}. Billing Total: ₹${totalAmt} (${payLabel})."`
      };

      if (typeof Notify !== 'undefined' && Notify.clearAll) {
        Notify.clearAll();
      }
      Notify.info(textMap[channel], { title: channel + ' Confirmation', clearExisting: true });

      // Auto move user to Live tracking step 14
      currentStep = 14;
      updateStepDisplay();
    }

    // Step 13 Tracking Simulator Backend Integrations
    async function simulateBackendStatus(targetStatus) {
      if (!bookingState.bookingDbId) {
        Notify.warning("No active database booking ID found to track.");
        return;
      }

      const statusMap = {
        'confirmed': 'CONFIRMED',
        'on_the_way': 'ON_THE_WAY',
        'in_progress': 'IN_PROGRESS',
        'completed': 'COMPLETED'
      };

      const dbStatus = statusMap[targetStatus];

      try {
        const bUrl = (window.serveasyApiUrl ? window.serveasyApiUrl("bookings.php") : ((window.SERVEASY_API_BASE || "../../../backend/api/") + "bookings.php"));
        const res = await fetch(bUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            id: bookingState.bookingDbId,
            status: dbStatus,
            remarks: `Simulated shift to ${dbStatus} via provider client controller dashboard.`
          })
        });

        const result = await res.json();

        if (result.success) {
          // Highlight tracking nodes in UI
          const currentHour = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

          if (targetStatus === 'confirmed') {
            document.getElementById('track-step-confirmed').classList.add('completed');
            document.getElementById('time-confirmed').textContent = currentHour;
            document.getElementById('btn-sim-assign').disabled = true;
            document.getElementById('btn-sim-otw').disabled = false;
          } else if (targetStatus === 'on_the_way') {
            document.getElementById('track-step-on_the_way').classList.add('completed');
            document.getElementById('time-on_the_way').textContent = currentHour;
            document.getElementById('btn-sim-otw').disabled = true;
            document.getElementById('btn-sim-start').disabled = false;
          } else if (targetStatus === 'in_progress') {
            document.getElementById('track-step-in_progress').classList.add('completed');
            document.getElementById('time-in_progress').textContent = currentHour;
            document.getElementById('btn-sim-start').disabled = true;
            document.getElementById('btn-sim-complete').disabled = false;
            pollLiveTechnicianLocation();
            if (!_trackerInterval) {
              _trackerInterval = setInterval(pollLiveTechnicianLocation, 7000);
            }
          } else if (targetStatus === 'completed') {
            document.getElementById('track-step-completed').classList.add('completed');
            document.getElementById('time-completed').textContent = currentHour;
            document.getElementById('btn-sim-complete').disabled = true;
            if (_trackerInterval) {
              clearInterval(_trackerInterval);
              _trackerInterval = null;
            }

            Notify.success("Service Completed! Let's rate & review.");

            // Wait 1.5 seconds then transition to step 14
            setTimeout(() => {
              currentStep = 15;
              updateStepDisplay();
            }, 1500);
          }
        } else {
          Notify.error("State change failed: " + result.message);
        }
      } catch (err) {
        console.error("Tracking Sim Error", err);
        Notify.error("Failed to simulate status update on database. Check server connection.");
      }
    }

    // ─── Real-Time Live Technician Tracker Functions ─────────────────────────
    // State variables initialized at script top: _trackerMap, _trackerProviderMarker, _trackerCustomerMarker, _trackerInterval, _lastTrackerData

    function initWebTrackerMap(lat, lng) {
      if (typeof L === 'undefined') return;
      const container = document.getElementById('web-tracker-map');
      if (!container) return;
      if (_trackerMap) {
        _trackerMap.setView([lat, lng], 14);
        _trackerMap.invalidateSize();
        return;
      }
      _trackerMap = L.map('web-tracker-map', { zoomControl: false }).setView([lat, lng], 14);
      L.control.zoom({ position: 'bottomright' }).addTo(_trackerMap);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
      }).addTo(_trackerMap);

      const providerIcon = L.divIcon({
        className: 'custom-leaflet-marker',
        html: '<div style="background: #34d399; width: 34px; height: 34px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 14px rgba(52, 211, 153, 0.8); display: flex; align-items: center; justify-content: center; font-size: 16px;">🛵</div>',
        iconSize: [34, 34],
        iconAnchor: [17, 17]
      });
      _trackerProviderMarker = L.marker([lat, lng], { icon: providerIcon }).addTo(_trackerMap)
        .bindPopup('<b>Technician</b><br>Live GPS Position');
    }

    async function pollLiveTechnicianLocation(isManual = false) {
      const bookingId = bookingState.bookingDbId;
      if (!bookingId) return;

      try {
        const locApiUrl = (window.serveasyApiUrl ? window.serveasyApiUrl(`provider-location.php?booking_id=${bookingId}`) : ((window.SERVEASY_API_BASE || "../../../backend/api/") + `provider-location.php?booking_id=${bookingId}`));
        const res = await fetch(locApiUrl, {
          credentials: 'include',
          cache: 'no-store',
          headers: {
            'Accept': 'application/json',
            'X-Customer-Id': String(bookingState.customerId || 0)
          }
        });
        if (!res.ok) return;
        const json = await res.json();
        if (!json.success || !json.data) return;

        const data = json.data;
        _lastTrackerData = data;

        // Auto-synchronize stepper nodes from live booking status
        const bStatus = String(data.booking_status || '').toLowerCase().trim();
        const currentHour = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        if (['confirmed', 'assigned'].includes(bStatus)) {
          const s1 = document.getElementById('track-step-confirmed');
          if (s1 && !s1.classList.contains('completed')) {
            s1.classList.add('completed');
            const t1 = document.getElementById('time-confirmed');
            if (t1) t1.textContent = currentHour;
          }
          const bAssign = document.getElementById('btn-sim-assign');
          if (bAssign) bAssign.disabled = true;
          const bOtw = document.getElementById('btn-sim-otw');
          if (bOtw) bOtw.disabled = false;
        } else if (bStatus === 'on_the_way') {
          ['track-step-confirmed', 'track-step-on_the_way'].forEach(id => {
            const el = document.getElementById(id);
            if (el && !el.classList.contains('completed')) {
              el.classList.add('completed');
              const tEl = el.querySelector('.tracker-step-time');
              if (tEl) tEl.textContent = currentHour;
            }
          });
          const bOtw = document.getElementById('btn-sim-otw');
          if (bOtw) bOtw.disabled = true;
          const bStart = document.getElementById('btn-sim-start');
          if (bStart) bStart.disabled = false;
        } else if (bStatus === 'in_progress') {
          ['track-step-confirmed', 'track-step-on_the_way', 'track-step-in_progress'].forEach(id => {
            const el = document.getElementById(id);
            if (el && !el.classList.contains('completed')) {
              el.classList.add('completed');
              const tEl = el.querySelector('.tracker-step-time');
              if (tEl) tEl.textContent = currentHour;
            }
          });
          const bStart = document.getElementById('btn-sim-start');
          if (bStart) bStart.disabled = true;
          const bComp = document.getElementById('btn-sim-complete');
          if (bComp) bComp.disabled = false;
        } else if (bStatus === 'completed') {
          ['track-step-confirmed', 'track-step-on_the_way', 'track-step-in_progress', 'track-step-completed'].forEach(id => {
            const el = document.getElementById(id);
            if (el && !el.classList.contains('completed')) {
              el.classList.add('completed');
              const tEl = el.querySelector('.tracker-step-time');
              if (tEl) tEl.textContent = currentHour;
            }
          });
          if (_trackerInterval) {
            clearInterval(_trackerInterval);
            _trackerInterval = null;
          }
          if (typeof Notify !== 'undefined' && Notify.success) {
            Notify.success("Service Completed! Let's rate & review.");
          }
          setTimeout(() => {
            if (currentStep === 14) {
              currentStep = 15;
              updateStepDisplay();
            }
          }, 1500);
        }

        if (data.eta_minutes != null) {
          const etaEl = document.getElementById('web-tracker-eta');
          if (etaEl) etaEl.textContent = `~${data.eta_minutes} mins`;
        } else {
          const etaEl = document.getElementById('web-tracker-eta');
          if (etaEl) etaEl.textContent = 'In Progress';
        }

        if (data.distance_km != null) {
          const distEl = document.getElementById('web-tracker-distance');
          if (distEl) distEl.textContent = `${data.distance_km} km`;
        } else {
          const distEl = document.getElementById('web-tracker-distance');
          if (distEl) distEl.textContent = 'En Route';
        }

        if (data.provider_name) {
          const nameEl = document.getElementById('web-tracker-provider-name');
          if (nameEl) nameEl.textContent = data.provider_name;
        }

        const badgeText = document.getElementById('web-tracker-badge-text');
        const subtitle = document.getElementById('web-tracker-subtitle');
        if (data.is_live) {
          if (badgeText) badgeText.textContent = 'LIVE GPS';
          if (subtitle) subtitle.textContent = `Live GPS coordinates received ${data.seconds_ago ? data.seconds_ago + 's ago' : 'just now'}.`;
        } else {
          if (badgeText) badgeText.textContent = 'STATIONARY';
          if (subtitle) subtitle.textContent = 'Technician base location active.';
        }

        if (data.latitude && data.longitude) {
          const pLat = parseFloat(data.latitude);
          const pLng = parseFloat(data.longitude);

          if (!_trackerMap) {
            initWebTrackerMap(pLat, pLng);
          } else if (_trackerProviderMarker) {
            _trackerProviderMarker.setLatLng([pLat, pLng]);
          }

          if (data.customer_lat && data.customer_lng && _trackerMap) {
            const cLat = parseFloat(data.customer_lat);
            const cLng = parseFloat(data.customer_lng);
            if (!_trackerCustomerMarker) {
              const custIcon = L.divIcon({
                className: 'custom-customer-marker',
                html: '<div style="background: #3b82f6; width: 30px; height: 30px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 10px rgba(59, 130, 246, 0.7); display: flex; align-items: center; justify-content: center; font-size: 14px;">🏠</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
              });
              _trackerCustomerMarker = L.marker([cLat, cLng], { icon: custIcon }).addTo(_trackerMap)
                .bindPopup('<b>Your Location</b><br>Service Address');
            }
            _trackerMap.fitBounds([[pLat, pLng], [cLat, cLng]], { padding: [40, 40] });
          } else if (_trackerMap) {
            _trackerMap.setView([pLat, pLng], 14);
          }
          if (_trackerMap) _trackerMap.invalidateSize();
        }

        if (isManual && typeof Notify !== 'undefined') {
          Notify.success('GPS Tracker Synced! Live coordinates updated.');
        }
      } catch (err) {
        console.error('Tracker poll error:', err);
      }
    }

    function openWebTrackerInGoogleMaps() {
      if (!_lastTrackerData || !_lastTrackerData.latitude) {
        if (typeof Notify !== 'undefined') Notify.warning('Technician coordinates not received yet.');
        return;
      }
      const origin = `${_lastTrackerData.latitude},${_lastTrackerData.longitude}`;
      const dest = (_lastTrackerData.customer_lat && _lastTrackerData.customer_lng)
        ? `${_lastTrackerData.customer_lat},${_lastTrackerData.customer_lng}`
        : encodeURIComponent(_lastTrackerData.customer_address || '');
      window.open(`https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${dest}&travelmode=driving`, '_blank');
    }

    // Step 14 Ratings Stars Highlights
    function rateStars(val) {
      bookingState.rating = val;
      const stars = document.querySelectorAll('#review-stars-container ion-icon');

      stars.forEach(s => {
        const starVal = parseInt(s.getAttribute('data-val'));
        if (starVal <= val) {
          s.classList.add('selected');
          s.setAttribute('name', 'star');
        } else {
          s.classList.remove('selected');
          s.setAttribute('name', 'star-outline');
        }
      });
    }

    async function submitReview() {
      if (bookingState.rating === 0) {
        Notify.warning("Please select a rating before submitting.");
        return;
      }

      const comment = document.getElementById('review-comment').value.trim();

      try {
        const rUrl = (window.serveasyApiUrl ? window.serveasyApiUrl("reviews.php") : ((window.SERVEASY_API_BASE || "../../../backend/api/") + "reviews.php"));
        const response = await fetch(rUrl, {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Customer-Id': String(bookingState.customerId || 0)
          },
          body: JSON.stringify({
            booking_id: bookingState.bookingDbId,
            rating: bookingState.rating,
            comment: comment
          })
        });

        const result = await response.json();

        if (result.success) {
          Notify.success("Review successfully recorded! Redirecting...");
          sessionStorage.removeItem('washBookingStep');
          sessionStorage.removeItem('washBookingState');
          window.location.href = './orders.php';
        } else {
          Notify.error("Failed to submit review: " + result.message);
        }
      } catch (err) {
        console.error("Reviews API Error", err);
        Notify.error("Failed to submit review to server. Review skipped.");
        sessionStorage.removeItem('washBookingStep');
        sessionStorage.removeItem('washBookingState');
        window.location.href = './orders.php';
      }
    }


    function goToSelectNewProvider() {
      // Stop any active polling
      stopPolling();

      // Reset provider-related choices and booking identifiers
      bookingState.providerId = null;
      bookingState.providerName = null;
      bookingState.serviceId = null;
      bookingState.serviceName = null;
      bookingState.servicePrice = 0.00;
      bookingState.packageId = null;
      bookingState.bookingDbId = null;
      bookingState.bookingRefNum = null;
      bookingState.providerAccepted = false;

      // Update providers container and service options
      renderProviders();
      const optionsContainer = document.getElementById('service-options-container');
      if (optionsContainer) optionsContainer.innerHTML = '';

      // Go back to Step 4 (Select Service Provider)
      currentStep = 4;
      updateStepDisplay();
    }

    function saveBookingState() {
      const addressNode = document.getElementById('loc-address');
      if (addressNode) bookingState.address = addressNode.value;
      const landmarkNode = document.getElementById('loc-landmark');
      if (landmarkNode) bookingState.landmark = landmarkNode.value;
      const pinNode = document.getElementById('loc-pincode');
      if (pinNode) bookingState.pincode = pinNode.value;
      const branchSelect = document.getElementById('nearest-branch');
      if (branchSelect) bookingState.branch = branchSelect.value;
      const locLatNode = document.getElementById('loc-lat');
      if (locLatNode && locLatNode.value) {
        const pLat = parseFloat(locLatNode.value);
        if (!isNaN(pLat) && pLat !== 0) bookingState.lat = pLat;
      }
      const locLngNode = document.getElementById('loc-lng');
      if (locLngNode && locLngNode.value) {
        const pLng = parseFloat(locLngNode.value);
        if (!isNaN(pLng) && pLng !== 0) bookingState.lng = pLng;
      }

      const brandNode = document.getElementById('v-brand');
      if (brandNode) bookingState.brand = brandNode.value;
      const modelNode = document.getElementById('v-model');
      if (modelNode) bookingState.model = modelNode.value;
      const regNode = document.getElementById('v-reg');
      if (regNode) bookingState.regNo = regNode.value;
      const colorNode = document.getElementById('v-color');
      if (colorNode) bookingState.color = colorNode.value;

      const nameNode = document.getElementById('cust-name');
      if (nameNode) bookingState.fullName = nameNode.value;
      const phoneNode = document.getElementById('cust-phone');
      if (phoneNode) bookingState.phone = phoneNode.value;
      const altPhoneNode = document.getElementById('cust-alt-phone');
      if (altPhoneNode) bookingState.altPhone = altPhoneNode.value;
      const emailNode = document.getElementById('cust-email');
      if (emailNode) bookingState.email = emailNode.value;

      const dateNode = document.getElementById('sch-date');
      if (dateNode) bookingState.date = dateNode.value;

      sessionStorage.setItem('washBookingStep', currentStep);
      sessionStorage.setItem('washBookingState', JSON.stringify(bookingState));
    }

    function restoreBookingState() {
      const urlParams = new URLSearchParams(window.location.search);
      if (
        urlParams.has('booking_id') ||
        urlParams.has('service_id') ||
        urlParams.has('vehicle') ||
        urlParams.has('subcat') ||
        urlParams.get('select_vehicle') === '1' ||
        isSelectVehicleMode ||
        preselectedVehicle ||
        (urlParams.has('service') && (urlParams.get('service') === 'vehicle-car-and-bike-wash' || urlParams.get('service') === 'vehicle-car-&-bike-wash'))
      ) {
        sessionStorage.removeItem('washBookingStep');
        sessionStorage.removeItem('washBookingState');
        return;
      }

      const savedStep = sessionStorage.getItem('washBookingStep');
      const savedStateStr = sessionStorage.getItem('washBookingState');
      if (savedStep && savedStateStr) {
        try {
          const savedState = JSON.parse(savedStateStr);
          Object.assign(bookingState, savedState);
          currentStep = parseInt(savedStep);
          updateStepDisplay();

          if (bookingState.vehicleType) {
            document.querySelectorAll('#step-2 .selection-card').forEach(c => {
              const attr = c.getAttribute('data-vehicle');
              if (attr === bookingState.vehicleType || (bookingState.vehicleType === 'Luxury' && attr === 'Luxury Vehicle')) {
                c.classList.add('selected');
              } else {
                c.classList.remove('selected');
              }
            });
          }

          if (bookingState.locationType) {
            const locOpt = document.querySelector(`input[name="loc-option"][value="${bookingState.locationType}"]`);
            if (locOpt) {
              locOpt.checked = true;
              toggleLocationView(bookingState.locationType);
            }
          }
          const addressNode = document.getElementById('loc-address');
          if (addressNode) addressNode.value = bookingState.address || '';
          const landmarkNode = document.getElementById('loc-landmark');
          if (landmarkNode) landmarkNode.value = bookingState.landmark || '';
          const pinNode = document.getElementById('loc-pincode');
          if (pinNode) pinNode.value = bookingState.pincode || '';
          const branchSelect = document.getElementById('nearest-branch');
          if (branchSelect) branchSelect.value = bookingState.branch || '';
          if (bookingState.lat) {
            const latNode = document.getElementById('loc-lat');
            if (latNode) latNode.value = bookingState.lat;
          }
          if (bookingState.lng) {
            const lngNode = document.getElementById('loc-lng');
            if (lngNode) lngNode.value = bookingState.lng;
          }

          const dateNode = document.getElementById('sch-date');
          if (dateNode) dateNode.value = bookingState.date || '';
          if (bookingState.timeSlot) {
            document.querySelectorAll('.slot-pill').forEach(pill => {
              if (pill.textContent.trim() === bookingState.timeSlot) {
                pill.classList.add('selected');
              } else {
                pill.classList.remove('selected');
              }
            });
          }

          const brandNode = document.getElementById('v-brand');
          if (brandNode) brandNode.value = bookingState.brand || '';
          const modelNode = document.getElementById('v-model');
          if (modelNode) modelNode.value = bookingState.model || '';
          const regNode = document.getElementById('v-reg');
          if (regNode) regNode.value = bookingState.regNo || '';
          const colorNode = document.getElementById('v-color');
          if (colorNode) colorNode.value = bookingState.color || '';

          const nameNode = document.getElementById('cust-name');
          if (nameNode) nameNode.value = bookingState.fullName || '';
          const phoneNode = document.getElementById('cust-phone');
          if (phoneNode) phoneNode.value = bookingState.phone || '';
          const altPhoneNode = document.getElementById('cust-alt-phone');
          if (altPhoneNode) altPhoneNode.value = bookingState.altPhone || '';
          const emailNode = document.getElementById('cust-email');
          if (emailNode) emailNode.value = bookingState.email || '';

          if (bookingState.paymentMethod) {
            selectPayMethod(bookingState.paymentMethod);
          }

          if (currentStep >= 4) {
            renderProviders();
          }
          if (currentStep >= 5) {
            renderServicePlans();
          }
          if (currentStep >= 8) {
            renderDynamicExtras();
          }
          if (currentStep >= 10) {
            renderSummaryBreakdown();
          }

          if (currentStep === 11 && bookingState.bookingDbId) {
            const provName = bookingState.providerName || 'Service Provider';
            const elProvName = document.getElementById('accept-provider-name');
            if (elProvName) elProvName.textContent = provName;
            const cardName = document.getElementById('accept-provider-card-name');
            if (cardName) cardName.textContent = provName;
            const cardAvatar = document.getElementById('accept-provider-avatar');
            if (cardAvatar) cardAvatar.textContent = provName.charAt(0).toUpperCase();
            const cardService = document.getElementById('accept-provider-card-service');
            if (cardService) cardService.textContent = `${bookingState.vehicleType || 'Vehicle'} • ${bookingState.plan || 'Wash'}`;

            if (bookingState.providerAccepted) {
              onProviderAccepted();
            } else {
              startProviderPolling();
            }
          } else if (currentStep === 14 && bookingState.bookingDbId) {
            const bRestoreUrl = (window.serveasyApiUrl ? window.serveasyApiUrl(`bookings.php?id=${bookingState.bookingDbId}`) : ((window.SERVEASY_API_BASE || "../../../backend/api/") + `bookings.php?id=${bookingState.bookingDbId}`));
            fetch(bRestoreUrl)
              .then(res => res.json())
              .then(result => {
                if (result.success && result.data) {
                  const dbStatus = (result.data.status || '').toLowerCase();
                  const statuses = ['pending', 'confirmed', 'assigned', 'on_the_way', 'in_progress', 'completed'];
                  const targetIdx = statuses.indexOf(dbStatus);

                  for (let i = 0; i <= targetIdx && i < 5; i++) {
                    const statusKey = statuses[i];
                    const node = document.getElementById(`track-step-${statusKey}`);
                    if (node) {
                      node.classList.add('completed');
                      node.querySelector('.tracker-step-time').textContent = 'Active';
                    }
                  }

                  if (dbStatus === 'confirmed') {
                    document.getElementById('btn-sim-assign').disabled = true;
                    document.getElementById('btn-sim-otw').disabled = false;
                  } else if (dbStatus === 'on_the_way' || dbStatus === 'assigned') {
                    document.getElementById('btn-sim-assign').disabled = true;
                    document.getElementById('btn-sim-otw').disabled = true;
                    document.getElementById('btn-sim-start').disabled = false;
                  } else if (dbStatus === 'in_progress') {
                    document.getElementById('btn-sim-assign').disabled = true;
                    document.getElementById('btn-sim-otw').disabled = true;
                    document.getElementById('btn-sim-start').disabled = true;
                    document.getElementById('btn-sim-complete').disabled = false;
                  }
                }
              })
              .catch(e => console.warn(e));
          }

          updateStepDisplay();
        } catch (e) {
          console.error("Error restoring booking state", e);
        }
      } else {
        updateStepDisplay();
      }
    }

    // Bind event listeners to input elements for real-time auto-saving
    document.querySelectorAll('input, textarea, select').forEach(el => {
      el.addEventListener('input', saveBookingState);
      el.addEventListener('change', saveBookingState);
    });

    // Execute restore state on page load
    restoreBookingState();
  </script>

  <!-- ── Add Address Modal Popup ── -->
  <div class="se-confirm-overlay" id="add-address-modal" style="display: none; align-items: center; justify-content: center; z-index: 100005;">
    <div class="se-confirm-card" style="max-width: 600px; text-align: left; padding: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 class="se-confirm__title" style="margin: 0; font-family: 'Playfair Display', serif; font-size: 1.5rem;">Add New Address</h3>
        <button type="button" onclick="closeAddAddressModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem;">&times;</button>
      </div>

      <!-- Action Toolbar inside Modal -->
      <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
          <button type="button" class="map-locate-btn" id="btn-modal-locate" onclick="locateMeOnModalMap()" style="padding: 7px 14px; font-size: 0.82rem; margin: 0; display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: 1px solid rgba(255,255,255,0.2);">
            <ion-icon name="locate-outline"></ion-icon> Locate Me
          </button>
          <button type="button" class="map-locate-btn" id="btn-modal-toggle-map" onclick="toggleModalPinMap()" style="padding: 7px 14px; font-size: 0.82rem; margin: 0; display: inline-flex; align-items: center; gap: 6px; background: rgba(59, 130, 246, 0.22); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.45);">
            <ion-icon name="map-outline"></ion-icon> Pin Map Live
          </button>
        </div>
        <span class="map-status-badge" id="modal-map-status" style="display:none; font-size: 0.75rem; padding: 4px 10px; margin: 0;"></span>
      </div>

      <!-- Live Pin Map Container inside Modal -->
      <div id="modal-map-wrapper" style="margin-bottom: 14px; border-radius: 12px; background: rgba(15, 23, 42, 0.6); padding: 10px; border: 1px solid rgba(255,255,255,0.08);">
        <div id="modal-loc-map" style="width: 100%; height: 210px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: #1a1a2e; z-index: 2;"></div>
        <p style="font-size: 0.75rem; color: #94a3b8; margin: 6px 0 0 0;">📍 Drag the pin or click on map to set your exact doorstep location</p>
      </div>

      <!-- Fields inside modal -->
      <div class="form-group-glass" style="margin-bottom: 12px;">
        <label class="form-label-glass" for="modal-addr-label">Label *</label>
        <input type="text" id="modal-addr-label" class="form-input-glass" placeholder="e.g. Home, Office, Custom" value="Home" required>
      </div>

      <div class="form-group-glass" style="margin-bottom: 12px;">
        <label class="form-label-glass" for="modal-addr-address">Full Address *</label>
        <input type="text" id="modal-addr-address" class="form-input-glass" placeholder="House/Flat No, Apartment, Street Name" required>
      </div>

      <div class="form-row-glass" style="margin-bottom: 20px;">
        <div class="form-group-glass">
          <label class="form-label-glass" for="modal-addr-landmark">Landmark *</label>
          <input type="text" id="modal-addr-landmark" class="form-input-glass" placeholder="Near hospital, park, etc." required>
        </div>
        <div class="form-group-glass">
          <label class="form-label-glass" for="modal-addr-pincode">Pincode *</label>
          <div style="display: flex; gap: 6px;">
            <input type="text" id="modal-addr-pincode" class="form-input-glass" placeholder="e.g. 700052" required oninput="handleModalPincodeChange(this.value)" onblur="handleModalPincodeBlur(this)" style="margin: 0; flex: 1;">
            <button type="button" onclick="snapMapToPincode()" class="map-locate-btn" id="btn-snap-pincode" style="padding: 7px 12px; font-size: 0.78rem; margin: 0; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.5); color: #34d399; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;">
              <ion-icon name="pin-outline"></ion-icon> Pin
            </button>
          </div>
        </div>
      </div>

      <!-- Hidden coordinates inside modal -->
      <input type="hidden" id="modal-addr-lat">
      <input type="hidden" id="modal-addr-lng">

      <div class="se-confirm__actions">
        <button type="button" class="se-confirm__btn se-confirm__btn--cancel" onclick="closeAddAddressModal()">Cancel</button>
        <button type="button" class="se-confirm__btn se-confirm__btn--confirm" onclick="saveNewAddressFromModal()">Save & Use</button>
      </div>
    </div>
  </div>
</body>

</html>