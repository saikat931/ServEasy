<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$bPath = file_exists(__DIR__ . '/../../../backend/api/bootstrap.php')
    ? __DIR__ . '/../../../backend/api/bootstrap.php'
    : (file_exists(__DIR__ . '/../../../../backend/api/bootstrap.php') ? __DIR__ . '/../../../../backend/api/bootstrap.php' : null);
if (file_exists($bPath)) { require_once $bPath; }
header('Content-Type: text/html; charset=utf-8');

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    $target_url = $_SERVER['REQUEST_URI'] ?? './personal-staff-&-maids.php';
    header("Location: ../../auth/auth.php?redirect=" . urlencode($target_url));
    exit;
}

$db = class_exists('App\Helpers\DB') ? \App\Helpers\DB::getConnection() : null;
if (!$db && file_exists(__DIR__ . '/../../common/db_connection.php')) {
    require_once __DIR__ . '/../../common/db_connection.php';
    if (class_exists('ServEasyWebDB')) {
        $db = ServEasyWebDB::connect();
    }
}

// Fetch convenience fee from platform_settings in serveasy-admin
$initial_convenience_fee = 20.00;
$initial_fee_type = 'percentage';
$initial_fee_active = 1;
$initial_fee_scope = 'all';
$initial_applicable_services = [];
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

$is_maids_in_fee_scope = ($initial_fee_scope === 'all')
    || in_array('personal-staff-and-maids', $initial_applicable_services)
    || in_array('personal-staff-&-maids', $initial_applicable_services)
    || in_array('maids', $initial_applicable_services)
    || in_array('care', $initial_applicable_services)
    || in_array('3', $initial_applicable_services)
    || in_array(3, $initial_applicable_services)
    || in_array('13', $initial_applicable_services)
    || in_array(13, $initial_applicable_services);

// AJAX: Handle Booking Submission
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'create_maid_booking') {
    header('Content-Type: application/json');
    try {
        $customer_id = (int)$_SESSION['user_id'];
        $provider_id = (int)$_POST['provider_id'];
        $profile_id = (int)$_POST['profile_id'];
        $subcategory_id = (int)$_POST['subcategory_id'];
        $schedule_date = $_POST['schedule_date'];
        $schedule_time = $_POST['schedule_time'] ?? null;
        $work_type = $_POST['work_type'];
        $address = trim($_POST['address'] ?? '');
        $landmark = trim($_POST['landmark'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $total_amount = (float)($_POST['total_amount'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? 'COD';

        if (empty($provider_id) || empty($profile_id) || empty($subcategory_id) || empty($schedule_date) || empty($address)) {
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
            exit;
        }

        $booking_number = 'MB-' . strtoupper(uniqid());

        $stmt = $db->prepare("
            INSERT INTO `maid_bookings`
                (booking_number, customer_id, provider_id, profile_id, subcategory_id, schedule_date, schedule_time, work_type, address, landmark, pincode, notes, total_amount, status, payment_method, payment_status)
            VALUES
                (:num, :cust, :prov, :prof, :sub, :sdate, :stime, :wtype, :addr, :land, :pin, :notes, :amt, 'pending', :pay_m, 'UNPAID')
        ");
        $stmt->execute([
            ':num' => $booking_number,
            ':cust' => $customer_id,
            ':prov' => $provider_id,
            ':prof' => $profile_id,
            ':sub' => $subcategory_id,
            ':sdate' => $schedule_date,
            ':stime' => $schedule_time ?: null,
            ':wtype' => $work_type,
            ':addr' => $address,
            ':land' => $landmark,
            ':pin' => $pincode,
            ':notes' => $notes,
            ':amt' => $total_amount,
            ':pay_m' => $payment_method
        ]);

        echo json_encode(['status' => 'success', 'booking_number' => $booking_number, 'message' => 'Your booking request has been submitted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Booking error: ' . $e->getMessage()]);
    }
    exit;
}

// Fetch saved customer addresses
$userAddresses = [];
if ($db) {
    try {
        $stmtAddr = $db->prepare("
            SELECT id, label, address, landmark, pincode 
            FROM `user_addresses` 
            WHERE user_id = ?
            ORDER BY is_default DESC, updated_at DESC
        ");
        $stmtAddr->execute([$_SESSION['user_id']]);
        $rawAddresses = $stmtAddr->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $seenKeys = [];
        $userAddresses = [];
        foreach ($rawAddresses as $addr) {
            $key = strtolower(trim($addr['address'])) . '|' . trim($addr['pincode']);
            if (!isset($seenKeys[$key])) {
                $seenKeys[$key] = true;
                $userAddresses[] = $addr;
            }
        }
    } catch (\Throwable $e) {}
}

// Fetch subcategories under Maids & Staff (Category ID: 3, slug: personal-staff-&-maids)
$subcategories = [];
if ($db) {
    try {
        $stmtSub = $db->prepare("
            SELECT sc.* FROM `serveasy-subcategories` sc
            JOIN `serveasy-categories` c ON sc.category_id = c.id
            WHERE c.slug = 'personal-staff-&-maids' AND sc.is_active = 1
            ORDER BY sc.name ASC
        ");
        $stmtSub->execute();
        $subcategories = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
} elseif (file_exists(__DIR__ . '/../../common/ApiBridge.php')) {
    require_once __DIR__ . '/../../common/ApiBridge.php';
    $subRes = \Frontend\ApiBridge::callApi('categories.php?action=subcategories&category_id=3');
    if (!empty($subRes['data']) && is_array($subRes['data'])) {
        $subcategories = $subRes['data'];
    }
}

// Fetch all approved profiles
$profiles = [];
if ($db) {
    try {
        $stmtProfiles = $db->prepare("
            SELECT mp.*, sp.id AS sp_provider_id, COALESCE(c.company_name, sp.full_name) AS company_name
            FROM `provider_profiles` mp
            JOIN `serveasy-providers` sp ON mp.provider_id = sp.id
            LEFT JOIN `serveasy-company` c ON c.provider_id = sp.id
            WHERE mp.status = 'Approved'
            ORDER BY mp.years_of_experience DESC, mp.id DESC
        ");
        $stmtProfiles->execute();
        $profiles = $stmtProfiles->fetchAll(PDO::FETCH_ASSOC);

        // Attach skills and subcategories details
        foreach ($profiles as &$p) {
            $profile_id = (int)$p['id'];
            
            // Skills
            $stmtSk = $db->prepare("SELECT skill_name FROM `provider_skills` WHERE profile_id = :pid");
            $stmtSk->execute([':pid' => $profile_id]);
            $p['skills'] = $stmtSk->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // Custom Skills
            $stmtCustSk = $db->prepare("SELECT skill_name FROM `provider_custom_skills` WHERE profile_id = :pid");
            $stmtCustSk->execute([':pid' => $profile_id]);
            $p['custom_skills'] = $stmtCustSk->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // Subcategories
            $stmtSubs = $db->prepare("
                SELECT sc.id, sc.name FROM `provider_services` ps
                JOIN `serveasy-subcategories` sc ON ps.subcategory_id = sc.id
                WHERE ps.profile_id = :pid
            ");
            $stmtSubs->execute([':pid' => $profile_id]);
            $p['subcategories'] = $stmtSubs->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Availability
            $stmtAvail = $db->prepare("SELECT day_of_week, time_slot, is_available, emergency_availability FROM `provider_availability` WHERE profile_id = :pid");
            $stmtAvail->execute([':pid' => $profile_id]);
            $p['availability'] = $stmtAvail->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Service Areas
            $stmtAreas = $db->prepare("SELECT area_name, pincode FROM `provider_service_areas` WHERE profile_id = :pid");
            $stmtAreas->execute([':pid' => $profile_id]);
            $p['areas'] = $stmtAreas->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        unset($p);
    } catch (\Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hire Personal Staff & Maids | ServEasy Wizard</title>
    
    <base href="../">
    
    <link rel="shortcut icon" href="./assets/favicon.svg" type="image/svg+xml">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="./assets/css/main.css">
    <link rel="stylesheet" href="./assets/css/global.css">
    <link rel="stylesheet" href="./assets/css/home.css">
    <link rel="stylesheet" href="./assets/css/notify.css">
    
    <!-- Premium Wizard Styles (Mirroring vehicle-car-&-bike-wash.php) -->
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            overflow-x: hidden;
        }

        .wizard-container {
            max-width: 950px;
            margin: 60px auto;
            padding: 0 15px;
            position: relative;
            z-index: 10;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        .wizard-card-glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px;
            color: #f8fafc;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .wizard-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .wizard-title {
            font-size: 2.4rem;
            font-family: 'Playfair Display', serif;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #34d399, #05c279, #3b82f6);
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
            background: linear-gradient(90deg, #05c279, #3b82f6);
            width: 20%;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .step-indicator-text {
            text-align: center;
            font-weight: 700;
            font-size: 0.9rem;
            color: #34d399;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Steps Panel */
        .wizard-step {
            display: none;
            animation: fadeIn 0.4s ease forwards;
        }

        .wizard-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Grid layouts */
        .selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .selection-card {
            background: rgba(255, 255, 255, 0.03);
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
            border-color: rgba(5, 194, 121, 0.4);
            transform: translateY(-4px);
        }

        .selection-card.selected {
            background: rgba(5, 194, 121, 0.15);
            border-color: #05c279;
            box-shadow: 0 0 20px rgba(5, 194, 121, 0.2);
        }

        .selection-icon {
            font-size: 2.8rem;
            color: #34d399;
        }

        .selection-title {
            font-size: 1.05rem;
            font-weight: 700;
        }

        /* Candidates grid layout */
        .candidates-list {
            margin: 25px 0;
        }

        .candidate-card-glass {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .candidate-card-glass:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(5, 194, 121, 0.3);
            transform: scale(1.01);
        }

        .candidate-card-glass.selected {
            background: rgba(5, 194, 121, 0.12);
            border-color: #05c279;
            box-shadow: 0 0 20px rgba(5, 194, 121, 0.15);
        }

        /* Buttons layout */
        .wizard-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-wizard-prev {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
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
            background: linear-gradient(135deg, #05c279, #3b82f6);
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: 12px 32px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(5, 194, 121, 0.3);
            transition: all 0.2s;
        }

        .btn-wizard-next:hover {
            box-shadow: 0 6px 20px rgba(5, 194, 121, 0.4);
            transform: translateY(-2px);
        }

        /* Forms styles */
        .glass-form-group {
            margin-bottom: 20px;
        }
        .glass-form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 8px;
            display: block;
        }
        .glass-form-input, .glass-form-select, .glass-form-textarea {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            color: #fff !important;
            padding: 12px 16px !important;
            font-size: 0.9rem !important;
            width: 100%;
        }
        .glass-form-input:focus, .glass-form-select:focus, .glass-form-textarea:focus {
            border-color: #05c279 !important;
            box-shadow: 0 0 10px rgba(5, 194, 121, 0.3) !important;
            outline: none !important;
        }

        .badge-skill {
            background: rgba(5, 194, 121, 0.08);
            color: #34d399;
            border: 1px solid rgba(5, 194, 121, 0.15);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            display: inline-block;
            margin: 2px;
        }

        /* Glow spheres */
        .glow-sphere {
            position: fixed;
            border-radius: 50%;
            filter: blur(150px);
            opacity: 0.15;
            z-index: 1;
            pointer-events: none;
        }
        .sphere-1 { width: 400px; height: 400px; background: #05c279; top: -100px; left: -100px; }
        .sphere-2 { width: 500px; height: 500px; background: #3b82f6; bottom: -150px; right: -100px; }
    </style>
</head>

<body>

    <div class="glow-sphere sphere-1"></div>
    <div class="glow-sphere sphere-2"></div>
    <div class="glow-sphere sphere-3"></div>

    <!-- NAVIGATION HEADER -->
    <?php include __DIR__ . '/../layout/header.php'; ?>

    <!-- SIDEBAR PANELS (Cart, Wishlist, Profile) -->
    <?php include __DIR__ . '/../constants/aside.php'; ?>

    <div class="wizard-container">
        <div class="wizard-card-glass">
            <div class="wizard-header">
                <h2 class="wizard-title">Hire Personal Staff & Maids</h2>
                <p class="wizard-subtitle" id="step-desc">Step 1: Select specialized service specialization</p>
            </div>

            <!-- Progress Bar -->
            <div class="progress-bar-wrapper">
                <div class="progress-bar-fill" id="progressFill"></div>
            </div>
            <div class="step-indicator-text mb-4" id="stepIndicator">Step 1 of 5</div>

            <form id="maidBookingWizardForm">
                <!-- Inputs to collect values dynamically -->
                <input type="hidden" name="subcategory_id" id="val_subcategory_id">
                <input type="hidden" name="work_type" id="val_work_type">
                <input type="hidden" name="profile_id" id="val_profile_id">
                <input type="hidden" name="provider_id" id="val_provider_id">
                <input type="hidden" name="total_amount" id="val_total_amount">

                <!-- STEP 1: CHOOSE SERVICE SPECIALIZATION -->
                <div class="wizard-step active" id="step-panel-1">
                    <h5 class="font-weight-bold text-white mb-3">Choose Specialization</h5>
                    <div class="selection-grid">
                        <?php 
                        $icons_map = [
                            'house-maid' => 'people-outline',
                            'babysitter' => 'rose-outline',
                            'cook' => 'restaurant-outline',
                            'driver' => 'car-sport-outline',
                            'elder-care' => 'heart-outline',
                            'home-nurse' => 'medical-outline',
                            'housekeeper' => 'home-outline',
                            'cleaner' => 'sparkles-outline',
                            'gardener' => 'leaf-outline',
                            'security-guard' => 'shield-half-outline'
                        ];
                        foreach ($subcategories as $sub): 
                            $icon = $icons_map[$sub['slug']] ?? 'people-outline';
                        ?>
                            <div class="selection-card" onclick="selectSubcategory(<?= $sub['id'] ?>, '<?= htmlspecialchars($sub['name']) ?>', this)">
                                <div class="selection-icon"><ion-icon name="<?= $icon ?>"></ion-icon></div>
                                <div class="selection-title"><?= htmlspecialchars($sub['name']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- STEP 2: SELECT WORK SHIFT -->
                <div class="wizard-step" id="step-panel-2">
                    <h5 class="font-weight-bold text-white mb-3">Preferred Shift Period</h5>
                    <div class="selection-grid">
                        <div class="selection-card" onclick="selectWorkType('Full Time', this)">
                            <div class="selection-icon"><ion-icon name="time-outline"></ion-icon></div>
                            <div class="selection-title">Full Time</div>
                            <div class="selection-desc">8-10 Hours Shift</div>
                        </div>
                        <div class="selection-card" onclick="selectWorkType('Part Time', this)">
                            <div class="selection-icon"><ion-icon name="hourglass-outline"></ion-icon></div>
                            <div class="selection-title">Part Time</div>
                            <div class="selection-desc">Daily specific hours</div>
                        </div>
                        <div class="selection-card" onclick="selectWorkType('Live-In', this)">
                            <div class="selection-icon"><ion-icon name="bed-outline"></ion-icon></div>
                            <div class="selection-title">Live-In</div>
                            <div class="selection-desc">Stay at home care</div>
                        </div>
                        <div class="selection-card" onclick="selectWorkType('Live-Out', this)">
                            <div class="selection-icon"><ion-icon name="exit-outline"></ion-icon></div>
                            <div class="selection-title">Live-Out</div>
                            <div class="selection-desc">Commuting staff</div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: SELECT CANDIDATE -->
                <div class="wizard-step" id="step-panel-3">
                    <h5 class="font-weight-bold text-white mb-3">Available Candidates Matching Requirements</h5>
                    <div class="candidates-list" id="candidatesListContainer">
                        <!-- Loaded dynamically via JS filter -->
                    </div>
                </div>

                <!-- STEP 4: SERVICE ADDRESS & TIMING -->
                <div class="wizard-step" id="step-panel-4">
                    <h5 class="font-weight-bold text-white mb-3">Booking Address & Date</h5>
                    
                    <div class="glass-form-group">
                        <label class="glass-form-label">Pre-saved Address</label>
                        <select class="glass-form-select" onchange="fillSavedAddress(this)">
                            <option value="">-- Choose Pre-saved Address --</option>
                            <?php foreach ($userAddresses as $addr): ?>
                                <option value="<?= htmlspecialchars(json_encode($addr)) ?>"><?= htmlspecialchars($addr['label'] . ' - ' . $addr['address']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="glass-form-group">
                        <label class="glass-form-label">Full Service Address <span class="text-danger">*</span></label>
                        <textarea name="address" id="book_address" class="glass-form-textarea" rows="3" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 glass-form-group">
                            <label class="glass-form-label">Landmark</label>
                            <input type="text" name="landmark" id="book_landmark" class="glass-form-input">
                        </div>
                        <div class="col-md-6 glass-form-group">
                            <label class="glass-form-label">Pincode <span class="text-danger">*</span></label>
                            <input type="text" name="pincode" id="book_pincode" class="glass-form-input" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 glass-form-group">
                            <label class="glass-form-label">Schedule Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="schedule_date" class="glass-form-input" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 glass-form-group">
                            <label class="glass-form-label">Preferred Hour Slot</label>
                            <select name="schedule_time" class="glass-form-select">
                                <option value="08:00:00">Morning (8:00 AM)</option>
                                <option value="13:00:00">Afternoon (1:00 PM)</option>
                                <option value="17:00:00">Evening (5:00 PM)</option>
                                <option value="20:00:00">Night (8:00 PM)</option>
                            </select>
                        </div>
                    </div>

                    <div class="glass-form-group">
                        <label class="glass-form-label">Special Requirements / Notes</label>
                        <textarea name="notes" class="glass-form-textarea" rows="2" placeholder="e.g. Vegetarian cook preference, pet care experience..."></textarea>
                    </div>
                </div>

                <!-- STEP 5: REVIEW & CONFIRM -->
                <div class="wizard-step" id="step-panel-5">
                    <h5 class="font-weight-bold text-white mb-3">Review Booking Summary</h5>
                    <div class="p-4 border rounded mb-4" style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08);">
                        <div class="row mb-2">
                            <div class="col-sm-4 text-muted small">Specialization:</div>
                            <div class="col-sm-8 font-weight-bold" id="rev_specialization">-</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 text-muted small">Work Shift:</div>
                            <div class="col-sm-8 font-weight-bold" id="rev_shift">-</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 text-muted small">Candidate Name:</div>
                            <div class="col-sm-8 font-weight-bold text-emerald" style="color:#34d399;" id="rev_candidate">-</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 text-muted small">Address:</div>
                            <div class="col-sm-8" id="rev_address">-</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 text-muted small">Booking Date:</div>
                            <div class="col-sm-8 font-weight-bold" id="rev_date">-</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-6 text-muted small">Service Provider Service Rate:</div>
                            <div class="col-sm-6 text-right font-weight-bold" id="rev_base_rate">₹0.00</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-6 text-muted small" id="rev_fee_label">Platform Charge (<?= $initial_fee_type === 'percentage' ? htmlspecialchars($initial_convenience_fee) . '%' : '' ?>):</div>
                            <div class="col-sm-6 text-right font-weight-bold text-info" id="rev_fee_amt">₹0.00</div>
                        </div>
                        <hr style="border-color: rgba(255,255,255,0.1);">
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="font-weight-bold">Total Amount (Paid by Customer):</span>
                            <h3 class="font-weight-bold text-emerald mb-0" style="color:#34d399;" id="rev_total_amt">₹0.00</h3>
                        </div>
                    </div>

                    <div class="glass-form-group">
                        <label class="glass-form-label">Choose Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="glass-form-select" required>
                            <option value="COD">Cash on Delivery (COD)</option>
                            <option value="Online">Online Banking / UPI</option>
                        </select>
                    </div>
                </div>

                <!-- Footer Wizard Buttons -->
                <div class="wizard-buttons">
                    <button type="button" class="btn-wizard-prev" id="prevBtn" onclick="navigateStep(-1)" style="display: none;"><i class="fas fa-arrow-left mr-2"></i> Back</button>
                    <button type="button" class="btn-wizard-next ml-auto" id="nextBtn" onclick="navigateStep(1)">Next <i class="fas fa-arrow-right ml-2"></i></button>
                </div>
            </form>
        </div>
    </div>

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

    <!-- Bootstrap Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/notify.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

    <script>
        const allCandidates = <?= json_encode($profiles) ?>;
        let currentStep = 1;
        const totalSteps = 5;

        const stepDescriptions = [
            "Step 1: Select specialized service specialization",
            "Step 2: Select preferred shift duration",
            "Step 3: Select matching candidate profile",
            "Step 4: Provide scheduling details & service address",
            "Step 5: Review summary & confirm booking"
        ];

        function navigateStep(dir) {
            // Validate step input values before moving forward
            if (dir === 1 && !validateCurrentStep()) return;

            // Hide active step
            $(`#step-panel-${currentStep}`).removeClass('active');

            currentStep += dir;
            if (currentStep < 1) currentStep = 1;
            if (currentStep > totalSteps) currentStep = totalSteps;

            // Show active step
            $(`#step-panel-${currentStep}`).addClass('active');

            updateWizardUI();
        }

        function updateWizardUI() {
            // Fill progress bar
            let fillPct = (currentStep / totalSteps) * 100;
            $('#progressFill').css('width', fillPct + '%');
            
            // Header instructions
            $('#step-desc').text(stepDescriptions[currentStep - 1]);
            $('#stepIndicator').text(`Step ${currentStep} of ${totalSteps}`);

            // Prev button visibility
            if (currentStep === 1) {
                $('#prevBtn').hide();
            } else {
                $('#prevBtn').show();
            }

            // Next button formatting
            if (currentStep === totalSteps) {
                $('#nextBtn').html('<i class="fas fa-check-circle mr-2"></i> Confirm Booking').removeClass('btn-wizard-next').addClass('btn-wizard-next').css('background', 'linear-gradient(135deg, #05c279, #10b981)');
            } else {
                $('#nextBtn').html('Next <i class="fas fa-arrow-right ml-2"></i>').css('background', '');
            }

            if (currentStep === 3) {
                filterCandidates();
            }

            if (currentStep === 5) {
                fillReviewSummary();
            }
        }

        function validateCurrentStep() {
            if (currentStep === 1) {
                if (!$('#val_subcategory_id').val()) {
                    Notify.warning('Please select a service specialization card.');
                    return false;
                }
            }
            if (currentStep === 2) {
                if (!$('#val_work_type').val()) {
                    Notify.warning('Please select a preferred shift card.');
                    return false;
                }
            }
            if (currentStep === 3) {
                if (!$('#val_profile_id').val()) {
                    Notify.warning('Please select one candidate card.');
                    return false;
                }
            }
            if (currentStep === 4) {
                let addr = $('#book_address').val().trim();
                let pin = $('#book_pincode').val().trim();
                let sdate = $('input[name="schedule_date"]').val();
                if (addr === '' || pin === '' || sdate === '') {
                    Notify.warning('Please fill in all required scheduling fields.');
                    return false;
                }
            }
            return true;
        }

        function selectSubcategory(id, name, element) {
            $('#val_subcategory_id').val(id);
            $('#rev_specialization').text(name);
            $('.selection-grid .selection-card').removeClass('selected');
            $(element).addClass('selected');
            setTimeout(() => navigateStep(1), 300);
        }

        function selectWorkType(type, element) {
            $('#val_work_type').val(type);
            $('#rev_shift').text(type);
            $('#step-panel-2 .selection-card').removeClass('selected');
            $(element).addClass('selected');
            setTimeout(() => navigateStep(1), 300);
        }

        function filterCandidates() {
            let selectedSubcat = parseInt($('#val_subcategory_id').val());
            let selectedWorkType = $('#val_work_type').val();

            // Clear previous selections in step 3
            $('#val_profile_id').val('');
            $('#val_provider_id').val('');

            let container = $('#candidatesListContainer');
            container.empty();

            let matches = allCandidates.filter(c => {
                let matchesSub = c.subcategories.some(s => s.id === selectedSubcat);
                let matchesShift = c.preferred_work_type.split(',').map(s => s.trim()).includes(selectedWorkType);
                return matchesSub && matchesShift;
            });

            if (matches.length === 0) {
                container.html(`
                    <div class="text-center p-4 text-muted border rounded" style="background:rgba(255,255,255,0.02); border-color:rgba(255,255,255,0.08);">
                        <i class="fas fa-users-slash fa-2x mb-2"></i>
                        <p class="mb-0">No matching candidates are currently available. You can change your requirements in the previous steps.</p>
                    </div>
                `);
                return;
            }

            matches.forEach(c => {
                let photo = c.profile_photo ? c.profile_photo : 'Frontend/images/user-placeholder.jpg';
                let exp = c.years_of_experience;
                let rate = 0;
                if (selectedWorkType === 'Full Time' || selectedWorkType === 'Live-In' || selectedWorkType === 'Live-Out') {
                    rate = parseFloat(c.expected_salary || c.monthly_rate || 0);
                } else if (selectedWorkType === 'Part Time') {
                    rate = parseFloat(c.daily_rate || 800);
                } else {
                    rate = parseFloat(c.hourly_rate || 150) * 8;
                }

                let skills = c.skills.concat(c.custom_skills).map(sk => `<span class="badge-skill">${sk}</span>`).join('');

                let cardHtml = `
                    <div class="candidate-card-glass" onclick="selectCandidate(${c.id}, ${c.sp_provider_id}, '${escapeQuote(c.full_name)}', ${rate}, this)">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <img src="${photo}" class="rounded-circle border" style="width:80px; height:80px; object-fit:cover; border-color:#05c279 !important;">
                            </div>
                            <div class="col-md-7">
                                <h5 class="font-weight-bold text-white mb-1">${c.full_name}</h5>
                                <p class="text-muted small mb-2"><i class="fas fa-history mr-1"></i> ${exp} Years Experience | <i class="fas fa-map-marker-alt mr-1"></i> ${c.city}, ${c.state}</p>
                                <div class="mb-2">${skills}</div>
                                <p class="small text-gray-400 font-italic mb-0">"${c.about_me.substring(0, 100)}..."</p>
                            </div>
                            <div class="col-md-3 text-md-right text-center mt-3 mt-md-0">
                                <div class="small text-muted mb-1">Expected Rate:</div>
                                <h5 class="font-weight-bold text-emerald mb-2" style="color:#34d399;">₹${rate.toLocaleString('en-IN')}</h5>
                                <a href="https://wa.me/${c.whatsapp_number}?text=Hello%20${encodeURIComponent(c.full_name)},%20I%20saw%20your%20profile%20on%20ServEasy%20and%20would%20like%20to%20hire%20you." target="_blank" class="btn btn-xs btn-outline-success px-3 py-1 font-weight-bold" style="font-size:0.75rem; border-radius:6px;" onclick="event.stopPropagation()"><i class="fab fa-whatsapp"></i> Chat</a>
                            </div>
                        </div>
                    </div>
                `;
                container.append(cardHtml);
            });
        }

        function escapeQuote(str) {
            return str.replace(/'/g, "\\'");
        }

        let platformConvenienceFee = <?php echo json_encode($initial_convenience_fee); ?>;
        let platformFeeType = <?php echo json_encode($initial_fee_type); ?>;
        let platformFeeActive = <?php echo json_encode((bool)$initial_fee_active); ?>;
        let platformFeeInScope = <?php echo json_encode((bool)$is_maids_in_fee_scope); ?>;
        let selectedBaseRate = 0;

        function updateTotals(rate) {
            selectedBaseRate = parseFloat(rate || 0);
            let fee = 0;
            if (platformFeeActive && platformFeeInScope) {
                if (platformFeeType === 'percentage') {
                    fee = (selectedBaseRate * platformConvenienceFee) / 100;
                } else {
                    fee = platformConvenienceFee;
                }
            }
            let total = selectedBaseRate + fee;

            $('#rev_base_rate').text('₹' + selectedBaseRate.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#rev_fee_label').text('Platform Charge (' + (platformFeeType === 'percentage' ? (platformConvenienceFee + '%') : '') + '):');
            $('#rev_fee_amt').text('₹' + fee.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#rev_total_amt').text('₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#val_total_amount').val(total.toFixed(2));
        }

        function selectCandidate(id, provId, name, rate, element) {
            $('#val_profile_id').val(id);
            $('#val_provider_id').val(provId);
            $('#rev_candidate').text(name);
            updateTotals(rate);
            $('.candidate-card-glass').removeClass('selected');
            $(element).addClass('selected');
            setTimeout(() => navigateStep(1), 300);
        }

        function fillSavedAddress(select) {
            let val = $(select).val();
            if (val !== '') {
                let data = JSON.parse(select.value);
                $('#book_address').val(data.address);
                $('#book_landmark').val(data.landmark);
                $('#book_pincode').val(data.pincode);
            } else {
                $('#book_address, #book_landmark, #book_pincode').val('');
            }
        }

        function fillReviewSummary() {
            let addr = $('#book_address').val() + ', ' + $('#book_landmark').val() + ' - ' + $('#book_pincode').val();
            $('#rev_address').text(addr);
            $('#rev_date').text($('input[name="schedule_date"]').val() + ' ' + $('select[name="schedule_time"] option:selected').text());
        }

        // On last step, confirm submit
        $('#nextBtn').on('click', function() {
            if (currentStep === totalSteps) {
                let data = $('#maidBookingWizardForm').serialize() + '&ajax_action=create_maid_booking';
                Notify.info('Sending booking details...', { title: 'Scheduling Booking' });

                $.post('services/personal-staff-&-maids.php', data, function(res) {
                    if (res.status === 'success') {
                        Notify.success(res.message);
                        setTimeout(() => window.location = 'index.php', 1500);
                    } else {
                        Notify.error(res.message);
                    }
                }, 'json').fail(function() {
                    Notify.error('Server error.');
                });
            }
        });
    </script>
</body>

</html>
