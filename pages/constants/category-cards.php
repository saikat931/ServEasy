<?php
/**
 * Shared Category & Subcategory Cards Component for ServEasy
 * Used on Home Page (Frontend/Web/index.php) and Category Page (Frontend/Web/pages/service-category.php)
 */

if (!isset($categoriesData) || empty($categoriesData) || !isset($subcategoriesData) || empty($subcategoriesData)) {
    try {
        $dbConfigPath = file_exists(__DIR__ . '/../../../backend/config/db_connection.php')
            ? __DIR__ . '/../../../backend/config/db_connection.php'
            : (file_exists(__DIR__ . '/../../../../backend/config/db_connection.php') ? __DIR__ . '/../../../../backend/config/db_connection.php' : null);
    $hasDb = false;
    $db_categories = [];
    $subcategories_raw = [];

    if ($dbConfigPath && file_exists($dbConfigPath)) {
        try {
            require_once $dbConfigPath;
            $catPdo = Database::connect();
            $cat_stmt = $catPdo->query("SELECT * FROM `serveasy-categories` WHERE is_active = 1 ORDER BY id ASC");
            $db_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Subcategories with service mappings
            $sub_stmt = $catPdo->query("
                SELECT 
                    sub.id AS subcategory_id,
                    sub.category_id,
                    sub.name AS subcategory_name,
                    sub.slug AS subcategory_slug,
                    sub.icon_class,
                    cat.name AS category_name,
                    cat.slug AS category_slug,
                    s.id AS service_id,
                    s.name AS service_name,
                    s.slug AS service_slug,
                    s.base_price AS service_price
                FROM `serveasy-subcategories` sub
                JOIN `serveasy-categories` cat ON sub.category_id = cat.id
                LEFT JOIN `serveasy-services` s 
                    ON s.subcategory_id = sub.id AND s.is_active = 1
                WHERE sub.is_active = 1
                ORDER BY sub.category_id ASC, sub.id ASC
            ");
            $subcategories_raw = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
            $hasDb = true;
        } catch (\Throwable $e) {}
    }

    if (!$hasDb) {
        // Multi-domain split deployment fallback (Backend on separate domain)
        try {
            $bridgePath = file_exists(__DIR__ . '/../common/ApiBridge.php') ? __DIR__ . '/../common/ApiBridge.php' : __DIR__ . '/../../common/ApiBridge.php';
            if (file_exists($bridgePath)) {
                require_once $bridgePath;
            }
            if (class_exists('Frontend\ApiBridge')) {
                $apiRes = \Frontend\ApiBridge::callApi('categories.php');
                if (!empty($apiRes['data']) && is_array($apiRes['data'])) {
                    $db_categories = $apiRes['data'];
                }

                $srvRes = \Frontend\ApiBridge::callApi('services.php?limit=250&sort=default');
                if (!empty($srvRes['data']) && is_array($srvRes['data'])) {
                    foreach ($srvRes['data'] as $s) {
                        $subcategories_raw[] = [
                            'subcategory_id'   => (int)($s['subcategory_id'] ?? $s['id']),
                            'category_id'      => (int)($s['category_id'] ?? 1),
                            'subcategory_name' => $s['subcategory_name'] ?? $s['name'],
                            'subcategory_slug' => $s['slug'] ?? '',
                            'icon_class'       => $s['subcategory_icon'] ?? 'grid-outline',
                            'category_name'    => $s['category_name'] ?? '',
                            'category_slug'    => $s['category_slug'] ?? '',
                            'service_id'       => (int)($s['id'] ?? 0),
                            'service_name'     => $s['name'] ?? '',
                            'service_slug'     => $s['slug'] ?? '',
                            'service_price'    => $s['base_price'] ?? 20.00
                        ];
                    }
                }
            }
        } catch (\Throwable $t) {}

        if (empty($db_categories)) {
            $db_categories = [
                ['id' => 1, 'name' => 'Automotive & Vehicles', 'slug' => 'vehicle-services', 'icon_class' => 'car-sport-outline'],
                ['id' => 2, 'name' => 'Home & Living Services', 'slug' => 'home-services', 'icon_class' => 'home-outline'],
                ['id' => 3, 'name' => 'Personal Staff & Maids', 'slug' => 'personal-staff-&-maids', 'icon_class' => 'people-outline'],
                ['id' => 4, 'name' => 'Transport & Fleet Rental', 'slug' => 'transport-&-rental', 'icon_class' => 'bus-outline'],
                ['id' => 5, 'name' => 'Rooms & Lodging Stays', 'slug' => 'rooms-&-lodging', 'icon_class' => 'bed-outline'],
                ['id' => 6, 'name' => 'Food & Culinary Services', 'slug' => 'food-&-dining', 'icon_class' => 'restaurant-outline'],
                ['id' => 7, 'name' => 'Emergency SOS & Roadside', 'slug' => 'emergency-sos', 'icon_class' => 'warning-outline'],
            ];
        }

        if (empty($subcategories_raw)) {
            $subcategories_raw = [
                // Cat 1: Automotive
                ['subcategory_id' => 1, 'category_id' => 1, 'subcategory_name' => 'Car Wash & Detailing', 'subcategory_slug' => 'car-wash', 'icon_class' => 'water-outline', 'category_name' => 'Automotive', 'category_slug' => 'vehicle-services', 'service_id' => 1, 'service_slug' => 'vehicle-car-and-bike-wash', 'service_price' => 25.00],
                ['subcategory_id' => 2, 'category_id' => 1, 'subcategory_name' => 'Bike & Motorcycle Wash', 'subcategory_slug' => 'bike-wash', 'icon_class' => 'bicycle-outline', 'category_name' => 'Automotive', 'category_slug' => 'vehicle-services', 'service_id' => 2, 'service_slug' => 'vehicle-car-and-bike-wash', 'service_price' => 15.00],
                ['subcategory_id' => 3, 'category_id' => 1, 'subcategory_name' => 'Engine & Brake Tune-Up', 'subcategory_slug' => 'engine-tuneup', 'icon_class' => 'build-outline', 'category_name' => 'Automotive', 'category_slug' => 'vehicle-services', 'service_id' => 17, 'service_slug' => 'vehicle-brake-engine-tuneup', 'service_price' => 45.00],
                ['subcategory_id' => 4, 'category_id' => 1, 'subcategory_name' => 'Emergency Towing & Tyre', 'subcategory_slug' => 'towing-tyre', 'icon_class' => 'car-outline', 'category_name' => 'Automotive', 'category_slug' => 'vehicle-services', 'service_id' => 18, 'service_slug' => 'emergency-sos-help', 'service_price' => 35.00],
                // Cat 2: Home
                ['subcategory_id' => 10, 'category_id' => 2, 'subcategory_name' => 'Deep Home Cleaning', 'subcategory_slug' => 'deep-cleaning', 'icon_class' => 'sparkles-outline', 'category_name' => 'Home Services', 'category_slug' => 'home-services', 'service_id' => 1, 'service_slug' => 'complete-interior-deep-clean-polish', 'service_price' => 39.00],
                ['subcategory_id' => 11, 'category_id' => 2, 'subcategory_name' => 'AC Repair & Servicing', 'subcategory_slug' => 'ac-services', 'icon_class' => 'snow-outline', 'category_name' => 'Home Services', 'category_slug' => 'home-services', 'service_id' => 7, 'service_slug' => 'electronics-and-ac-services', 'service_price' => 29.00],
                ['subcategory_id' => 12, 'category_id' => 2, 'subcategory_name' => 'Electrician & Plumbing', 'subcategory_slug' => 'electrician-plumbing', 'icon_class' => 'flash-outline', 'category_name' => 'Home Services', 'category_slug' => 'home-services', 'service_id' => 8, 'service_slug' => 'room-cleaning-and-decor', 'service_price' => 20.00],
                ['subcategory_id' => 13, 'category_id' => 2, 'subcategory_name' => 'Laundry & Dry Clean', 'subcategory_slug' => 'laundry-clean', 'icon_class' => 'shirt-outline', 'category_name' => 'Home Services', 'category_slug' => 'home-services', 'service_id' => 9, 'service_slug' => 'laundry-and-dry-clean', 'service_price' => 18.00],
                // Cat 3: Personal Staff
                ['subcategory_id' => 20, 'category_id' => 3, 'subcategory_name' => 'House Maid & Cleaning', 'subcategory_slug' => 'house-maid', 'icon_class' => 'people-outline', 'category_name' => 'Personal Staff', 'category_slug' => 'personal-staff-&-maids', 'service_id' => 5, 'service_slug' => 'personal-staff-and-maids', 'service_price' => 30.00],
                ['subcategory_id' => 21, 'category_id' => 3, 'subcategory_name' => 'Home Chef & Cook', 'subcategory_slug' => 'home-chef', 'icon_class' => 'restaurant-outline', 'category_name' => 'Personal Staff', 'category_slug' => 'personal-staff-&-maids', 'service_id' => 5, 'service_slug' => 'personal-staff-and-maids', 'service_price' => 40.00],
                ['subcategory_id' => 22, 'category_id' => 3, 'subcategory_name' => 'Baby Sitter & Childcare', 'subcategory_slug' => 'baby-sitter', 'icon_class' => 'heart-outline', 'category_name' => 'Personal Staff', 'category_slug' => 'personal-staff-&-maids', 'service_id' => 5, 'service_slug' => 'personal-staff-and-maids', 'service_price' => 35.00],
                // Cat 4: Transport
                ['subcategory_id' => 30, 'category_id' => 4, 'subcategory_name' => 'Car & Bike Rentals', 'subcategory_slug' => 'car-rentals', 'icon_class' => 'car-sport-outline', 'category_name' => 'Transport', 'category_slug' => 'transport-&-rental', 'service_id' => 9, 'service_slug' => 'car-and-bike-rentals', 'service_price' => 50.00],
                ['subcategory_id' => 31, 'category_id' => 4, 'subcategory_name' => 'Cargo & Goods Transport', 'subcategory_slug' => 'cargo-transport', 'icon_class' => 'bus-outline', 'category_name' => 'Transport', 'category_slug' => 'transport-&-rental', 'service_id' => 11, 'service_slug' => 'goods-and-cargo-carrier', 'service_price' => 60.00],
                // Cat 5: Rooms
                ['subcategory_id' => 40, 'category_id' => 5, 'subcategory_name' => 'Short Period Stays', 'subcategory_slug' => 'short-stays', 'icon_class' => 'bed-outline', 'category_name' => 'Rooms & Lodging', 'category_slug' => 'rooms-&-lodging', 'service_id' => 13, 'service_slug' => 'short-period-rooms', 'service_price' => 45.00],
                ['subcategory_id' => 41, 'category_id' => 5, 'subcategory_name' => 'Sightseeing & Guide', 'subcategory_slug' => 'sightseeing', 'icon_class' => 'compass-outline', 'category_name' => 'Rooms & Lodging', 'category_slug' => 'rooms-&-lodging', 'service_id' => 10, 'service_slug' => 'sightseeing-and-tours', 'service_price' => 30.00],
                // Cat 6: Food
                ['subcategory_id' => 50, 'category_id' => 6, 'subcategory_name' => 'Restaurant Food Delivery', 'subcategory_slug' => 'food-delivery', 'icon_class' => 'fast-food-outline', 'category_name' => 'Food & Dining', 'category_slug' => 'food-&-dining', 'service_id' => 16, 'service_slug' => 'restaurant-orders-and-dining', 'service_price' => 15.00],
                // Cat 7: Emergency SOS
                ['subcategory_id' => 60, 'category_id' => 7, 'subcategory_name' => '24/7 Roadside Rescue', 'subcategory_slug' => 'roadside-rescue', 'icon_class' => 'warning-outline', 'category_name' => 'Emergency SOS', 'category_slug' => 'emergency-sos', 'service_id' => 6, 'service_slug' => 'emergency-sos-help', 'service_price' => 50.00],
                ['subcategory_id' => 61, 'category_id' => 7, 'subcategory_name' => 'Emergency Locksmith', 'subcategory_slug' => 'emergency-locksmith', 'icon_class' => 'key-outline', 'category_name' => 'Emergency SOS', 'category_slug' => 'emergency-sos', 'service_id' => 6, 'service_slug' => 'emergency-sos-help', 'service_price' => 40.00],
            ];
        }
    }

        $cat_slugs_by_id = [];
        foreach ($db_categories as $cat) {
            $cat_slugs_by_id[$cat['id']] = $cat['slug'];
        }

        // Fallback mapping for subcategories that don't have a direct 1:1 subcategory_id in serveasy-services
        $subcat_service_fallback = [
            2  => 'vehicle-car-and-bike-wash',            // Bike Wash -> Service 2 (Vehicle Car & Bike Wash)
            6  => 'vehicle-brake-engine-tuneup',          // Vehicle Servicing -> Service 17 (Vehicle Brake & Engine Tune-Up)
            7  => 'vehicle-brake-engine-tuneup',          // Vehicle Parts -> Service 17
            8  => 'vehicle-brake-engine-tuneup',          // Garage Service -> Service 17
            9  => 'electronics-and-ac-services',          // Home Maintenance -> Service 7 (Electronics & AC Services)
            10 => 'room-cleaning-and-decor',              // Bathroom Wash -> Service 3 (Room Cleaning & Decor)
            11 => 'complete-interior-deep-clean-polish',  // Kitchen Wash -> Service 1 (Complete Interior Deep Clean & Polish)
            13 => 'room-cleaning-and-decor',              // Room Decor -> Service 3
            15 => 'electronics-and-ac-services',          // Fridge Repair -> Service 7
            16 => 'electronics-and-ac-services',          // Washer Repair -> Service 7
            18 => 'laundry-and-dry-clean',                // Dry Cleaning -> Service 8 (Laundry & Dry Clean)
            19 => 'personal-staff-and-maids',             // Watchman Guard -> Service 5 (Personal Staff & Maids)
            20 => 'personal-staff-and-maids',             // Home Chef -> Service 5
            21 => 'personal-staff-and-maids',             // Baby Sitter -> Service 5
            22 => 'personal-staff-and-maids',             // Elderly Care -> Service 5
            23 => 'car-and-bike-rentals',                 // Scooter Rental -> Service 9 (Car & Bike Rentals)
            25 => 'goods-and-cargo-carrier',              // Cargo Bike Express -> Service 11 (Goods & Cargo Carrier)
            28 => 'bus-ticket-booking',                   // Bus Seat Reservation -> Service 4 (Bus Ticket Booking)
            29 => 'short-period-rooms',                   // One Night Room -> Service 13 (Short-Period Rooms)
            34 => 'sightseeing-and-tours',                // Heritage Guide -> Service 10 (Sightseeing & Tours)
            37 => 'restaurant-orders-and-dining',         // Home Food Delivery -> Service 16 (Restaurant Orders & Dining)
            38 => 'emergency-sos-help',                   // Medical Emergency SOS -> Service 6 (Emergency SOS Help)
            40 => 'emergency-sos-help',                   // Emergency Locksmith -> Service 6
        ];

        $icon_map = [
            'car-wash-icon'       => 'water-outline',
            'bike-wash-icon'      => 'bicycle-outline',
            'wrench-icon'         => 'build-outline',
            'home-cleaning-icon'  => 'sparkles-outline',
            'maid-icon'           => 'people-outline',
        ];

        $icon_config = [
            1 => ['filter' => 'automotive', 'icon' => 'construct-outline', 'theme' => 'cat-icon-emerald'],
            2 => ['filter' => 'home', 'icon' => 'home-outline', 'theme' => 'cat-icon-purple'],
            3 => ['filter' => 'care', 'icon' => 'people-outline', 'theme' => 'cat-icon-pink'],
            4 => ['filter' => 'transport', 'icon' => 'car-sport-outline', 'theme' => 'cat-icon-cyan'],
            5 => ['filter' => 'stay', 'icon' => 'bed-outline', 'theme' => 'cat-icon-amber'],
            6 => ['filter' => 'food', 'icon' => 'restaurant-outline', 'theme' => 'cat-icon-amber'],
            7 => ['filter' => 'emergency', 'icon' => 'warning-outline', 'theme' => 'cat-icon-orange'],
        ];

        $subs_by_cat = [];
        $subcategoriesData = [];
        $seen_subs = [];

        foreach ($subcategories_raw as $s) {
            $cid = (int)$s['category_id'];
            $subId = (int)$s['subcategory_id'];
            $subSlug = $s['subcategory_slug'] ?? '';
            $subName = $s['subcategory_name'] ?? '';
            $catSlug = $cat_slugs_by_id[$cid] ?? 'all';
            $sSlug = !empty($s['service_slug']) ? $s['service_slug'] : ($subcat_service_fallback[$subId] ?? '');
            $priceStr = !empty($s['service_price']) ? '$' . number_format((float)$s['service_price'], 2) : 'From $20.00';

            // Dedicated Booking Pages connect with slug
            if ($sSlug === 'vehicle-car-and-bike-wash' || $sSlug === 'vehicle-car-&-bike-wash' || $subSlug === 'car-wash' || $subSlug === 'bike-wash' || stripos($subName, 'wash') !== false) {
                $subLower = strtolower($subSlug . ' ' . $subName);
                if (stripos($subLower, 'bike') !== false || stripos($subLower, 'motorcycle') !== false) {
                    $targetLink = "./services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=bike-wash&vehicle=Bike";
                } elseif (stripos($subLower, 'scooter') !== false) {
                    $targetLink = "./services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=bike-wash&vehicle=Scooter";
                } elseif (stripos($subLower, 'suv') !== false) {
                    $targetLink = "./services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=car-wash&vehicle=SUV";
                } elseif (stripos($subLower, 'luxury') !== false) {
                    $targetLink = "./services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=car-wash&vehicle=Luxury";
                } else {
                    $targetLink = "./services/vehicle-car-&-bike-wash.php?service=vehicle-car-and-bike-wash&subcat=car-wash&vehicle=Car";
                }
            } elseif ($sSlug === 'personal-staff-and-maids' || $sSlug === 'personal-staff-&-maids' || $cid === 3 || $subSlug === 'house-maid') {
                $targetLink = "./services/personal-staff-&-maids.php?service=personal-staff-and-maids&subcat=" . urlencode($subSlug);
            } elseif (!empty($sSlug)) {
                $targetLink = "./services.php?category=" . urlencode($catSlug) . "&service=" . urlencode($sSlug);
            } else {
                $targetLink = "./services.php?category=" . urlencode($catSlug) . "&query=" . urlencode($subName);
            }

            if (!isset($seen_subs[$subId])) {
                $seen_subs[$subId] = true;
                $cfg = $icon_config[$cid] ?? ['filter' => 'all', 'icon' => 'grid-outline', 'theme' => 'cat-icon-emerald'];
                $subIcon = $s['icon_class'] ?: 'grid-outline';
                if (isset($icon_map[$subIcon])) $subIcon = $icon_map[$subIcon];

                $short_domain_map = [
                    'Automotive & Vehicles'  => 'Automotive',
                    'Vehicle Services'       => 'Automotive',
                    'Home Services'          => 'Home & Living',
                    'Personal Staff & Maids' => 'Personal Staff',
                    'Emergency SOS & Help'   => 'Emergency SOS',
                    'Transport & Rental'     => 'Transport',
                    'Rooms & Lodging'        => 'Rooms & Lodging',
                    'Food & Dining'          => 'Food & Dining',
                ];
                $displayCatName = $short_domain_map[$s['category_name']] ?? $s['category_name'];
                $priceStr = !empty($s['service_price']) ? '$' . number_format((float)$s['service_price'], 2) : 'From $20.00';

                $subcategoriesData[] = [
                    'id'            => $subId,
                    'category_id'   => $cid,
                    'category_name' => $displayCatName,
                    'full_cat_name' => $s['category_name'],
                    'filter_tag'    => $cfg['filter'],
                    'theme'         => $cfg['theme'],
                    'name'          => $subName,
                    'slug'          => $subSlug,
                    'icon'          => $subIcon,
                    'price'         => $priceStr,
                    'link'          => $targetLink
                ];
            }

            $subs_by_cat[$cid][] = [
                'id'    => $subId,
                'name'  => $subName,
                'slug'  => $subSlug,
                'link'  => $targetLink
            ];
        }

        // Min base_price & accurate individual service count per category
        $stats_by_cat = [];
        if ($hasDb && !empty($catPdo)) {
            try {
                $stats_stmt = $catPdo->query("
                    SELECT 
                        sub.category_id,
                        COUNT(DISTINCT s.id) AS service_count,
                        MIN(s.base_price) AS min_price
                    FROM `serveasy-services` s
                    JOIN `serveasy-subcategories` sub ON s.subcategory_id = sub.id
                    WHERE s.is_active = 1
                    GROUP BY sub.category_id
                ");
                foreach ($stats_stmt->fetchAll(PDO::FETCH_ASSOC) as $st) {
                    $stats_by_cat[$st['category_id']] = $st;
                }
            } catch (\Throwable $e) {}
        }

        // Compute stats from subcategories_raw for multi-domain deployments
        if (empty($stats_by_cat)) {
            foreach ($subcategories_raw as $s) {
                $cid = (int)$s['category_id'];
                if (!isset($stats_by_cat[$cid])) {
                    $stats_by_cat[$cid] = ['category_id' => $cid, 'service_count' => 0, 'min_price' => 999999];
                }
                $stats_by_cat[$cid]['service_count']++;
                $price = (float)($s['service_price'] ?? 20);
                if ($price > 0 && $price < $stats_by_cat[$cid]['min_price']) {
                    $stats_by_cat[$cid]['min_price'] = $price;
                }
            }
            foreach ($stats_by_cat as $cid => $st) {
                if ($st['min_price'] >= 999999) {
                    $stats_by_cat[$cid]['min_price'] = 20.00;
                }
            }
        }

        $categoriesData = [];
        foreach ($db_categories as $cat) {
            $cid = (int)$cat['id'];
            $cfg = $icon_config[$cid] ?? ['filter' => $cat['slug'], 'icon' => 'grid-outline', 'theme' => 'cat-icon-emerald'];
            $subs = $subs_by_cat[$cid] ?? [];
            $stats = $stats_by_cat[$cid] ?? null;
            $srvCount = $stats ? (int)$stats['service_count'] : 0;
            $minPrice = $stats && $stats['min_price'] !== null ? '$' . number_format((float)$stats['min_price'], 2) : 'Starts from $10.00';

            $categoriesData[] = [
                'id'            => $cid,
                'filter_tag'    => $cfg['filter'],
                'title'         => $cat['name'],
                'icon'          => $cfg['icon'],
                'icon_theme'    => $cfg['theme'],
                'badge'         => $srvCount > 0 ? "{$srvCount} Service" . ($srvCount > 1 ? 's' : '') : 'Domain',
                'price_from'    => $minPrice,
                'price_unit'    => '',
                'description'   => "Explore verified " . strtolower($cat['name']) . " available directly on ServEasy.",
                'subcategories' => $subs,
                'service_link'  => "./services.php?category=" . urlencode($cat['slug'])
            ];
        }
    } catch (\Throwable $e) {
        error_log("Failed to load category cards data: " . $e->getMessage());
        $categoriesData = [];
        $subcategoriesData = [];
    }
}

$categories = $categoriesData;
$sectionTitle = $categorySectionTitle ?? 'All Service Sectors';
$isHomePage = isset($isHomeContext) && $isHomeContext;
$displayedCategories = $isHomePage ? array_slice($categoriesData, 0, 6) : $categoriesData;
?>

<!-- CATEGORIES & SUBCATEGORIES SHOWCASE SECTION (SHARED COMPONENT) -->
<section class="categories-showcase-section" id="categories-section">
  <div class="cat-section-header">
    <div class="cat-title-wrap">
      <h2 class="cat-section-title" id="cat-main-heading"><?= htmlspecialchars((string)($sectionTitle ?? '')) ?></h2>
      <span class="cat-results-counter" id="categories-count">Showing <?= count($displayedCategories) ?> domains</span>
    </div>

    <div class="cat-header-actions">
      <!-- Toggle View: Categories (6 on home / 7 on category page) vs Subcategories (9 on home / 40 on category page) -->
      <div class="cat-view-switcher" role="tablist" aria-label="Category view mode">
        <button type="button" class="cat-view-btn active" id="btn-view-categories" onclick="window.switchCategoryView('categories')" title="Browse <?= $isHomePage ? '6 Featured' : 'All ' . count($categoriesData) ?> Service Sectors">
          <ion-icon name="grid-outline"></ion-icon>
          <span>Domains (<?= count($displayedCategories) ?>)</span>
        </button>
        <button type="button" class="cat-view-btn" id="btn-view-subcategories" onclick="window.switchCategoryView('subcategories')" title="Browse verified subcategory specializations">
          <ion-icon name="apps-outline"></ion-icon>
          <span><?= $isHomePage ? 'Subcategories (9)' : 'All Subcategories (' . count($subcategoriesData) . ')' ?></span>
        </button>
      </div>

      <?php if ($isHomePage): ?>
        <a href="./service-category.php" class="btn-book-glass cat-view-all-btn" id="cat-view-all-link" style="text-decoration: none; padding: 7px 16px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;" title="View all service categories">
          <span id="cat-view-all-text">All Categories</span> <ion-icon name="arrow-forward-outline"></ion-icon>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- 1. MAIN CATEGORIES SHOWCASE GRID (<?= count($displayedCategories) ?> DOMAINS) -->
  <div class="categories-showcase-grid" id="categories-grid">
    <?php foreach ($displayedCategories as $cat): ?>
      <div class="cat-showcase-card category-item" 
           data-domain="<?= htmlspecialchars((string)($cat['filter_tag'] ?? '')) ?>" 
           data-cat-id="<?= (int)($cat['id'] ?? 0) ?>" 
           data-search="<?= htmlspecialchars(strtolower((string)($cat['title'] ?? '') . ' ' . (string)($cat['description'] ?? '') . ' ' . implode(' ', array_column($cat['subcategories'] ?? [], 'name')))) ?>">
        
        <div>
          <div class="cat-card-top">
            <div class="cat-icon-container <?= htmlspecialchars((string)($cat['icon_theme'] ?? '')) ?>">
              <ion-icon name="<?= htmlspecialchars((string)($cat['icon'] ?? '')) ?>"></ion-icon>
            </div>
            <span class="cat-count-badge"><?= htmlspecialchars((string)($cat['badge'] ?? '')) ?></span>
          </div>

          <h3 class="cat-title"><?= htmlspecialchars((string)($cat['title'] ?? '')) ?></h3>
          <p class="cat-desc"><?= htmlspecialchars((string)($cat['description'] ?? '')) ?></p>

          <!-- Subcategories Pill List -->
          <?php if (!empty($cat['subcategories'])): ?>
            <div class="cat-subs-container">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span class="cat-subs-label">Specializations</span>
                <?php if ($isHomePage): ?>
                  <a href="./service-category.php?view=subcategories&domain=<?= urlencode((string)($cat['filter_tag'] ?? '')) ?>" class="btn-view-subs-inline" style="text-decoration: none;">
                    View all <?= count($cat['subcategories']) ?> &rarr;
                  </a>
                <?php else: ?>
                  <button type="button" class="btn-view-subs-inline" onclick="window.switchCategoryView('subcategories', '<?= htmlspecialchars((string)($cat['filter_tag'] ?? ''), ENT_QUOTES) ?>')">
                    View all <?= count($cat['subcategories']) ?> &rarr;
                  </button>
                <?php endif; ?>
              </div>
              <div class="cat-sub-tags">
                <?php foreach (array_slice($cat['subcategories'], 0, 5) as $sub): ?>
                  <a href="<?= htmlspecialchars((string)($sub['link'] ?? '')) ?>" class="cat-sub-tag">
                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                    <span><?= htmlspecialchars((string)($sub['name'] ?? '')) ?></span>
                  </a>
                <?php endforeach; ?>
                <?php if (count($cat['subcategories']) > 5): ?>
                  <?php if ($isHomePage): ?>
                    <a href="./service-category.php?view=subcategories&domain=<?= urlencode((string)($cat['filter_tag'] ?? '')) ?>" class="cat-sub-tag more-tag" style="text-decoration: none;">
                      <span>+<?= count($cat['subcategories']) - 5 ?> more</span>
                    </a>
                  <?php else: ?>
                    <a href="javascript:void(0)" onclick="window.switchCategoryView('subcategories', '<?= htmlspecialchars((string)($cat['filter_tag'] ?? ''), ENT_QUOTES) ?>')" class="cat-sub-tag more-tag">
                      <span>+<?= count($cat['subcategories']) - 5 ?> more</span>
                    </a>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Footer with Pricing & Explore Button -->
        <div class="cat-card-footer">
          <div class="cat-price-info">
            <span class="cat-price-caption">Starting From</span>
            <div class="cat-price-text"><?= htmlspecialchars((string)($cat['price_from'] ?? '')) ?> <span><?= htmlspecialchars((string)($cat['price_unit'] ?? '')) ?></span></div>
          </div>

          <a href="<?= htmlspecialchars((string)($cat['service_link'] ?? '')) ?>" class="btn-cat-explore">
            <span>Explore Services</span>
            <ion-icon name="arrow-forward-outline"></ion-icon>
          </a>
        </div>

      </div>
    <?php endforeach; ?>

    <?php if ($isHomePage && count($categoriesData) > 6): ?>
      <div class="cat-home-footer-cta">
        <a href="./service-category.php" class="btn-explore-all-cta" title="Explore all <?= count($categoriesData) ?> verified service domains">
          <span>Explore All</span>
          <ion-icon name="arrow-forward-outline"></ion-icon>
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- 2. SUBCATEGORIES SHOWCASE GRID (40 ON CATEGORY PAGE, TOP 9 ON HOME) -->
  <div class="subcategories-showcase-grid" id="subcategories-grid" style="display: none;">
    <?php 
      $displaySubs = $isHomePage ? array_slice($subcategoriesData, 0, 9) : $subcategoriesData;
      foreach ($displaySubs as $sub): 
    ?>
      <div class="subcat-showcase-card subcategory-item" 
           data-domain="<?= htmlspecialchars((string)($sub['filter_tag'] ?? '')) ?>" 
           data-cat-id="<?= (int)($sub['category_id'] ?? 0) ?>" 
           data-sub-id="<?= (int)($sub['id'] ?? 0) ?>" 
           data-search="<?= htmlspecialchars(strtolower((string)($sub['name'] ?? '') . ' ' . (string)($sub['category_name'] ?? '') . ' ' . (string)($sub['slug'] ?? ''))) ?>">
        
        <div>
          <div class="subcat-card-header">
            <div class="subcat-icon-container <?= htmlspecialchars((string)($sub['theme'] ?? '')) ?>">
              <ion-icon name="<?= htmlspecialchars((string)($sub['icon'] ?? '')) ?>"></ion-icon>
            </div>
            <span class="subcat-domain-badge"><?= htmlspecialchars((string)($sub['category_name'] ?? '')) ?></span>
          </div>

          <h3 class="subcat-title"><?= htmlspecialchars((string)($sub['name'] ?? '')) ?></h3>
          <p class="subcat-desc">Professional <?= strtolower(htmlspecialchars((string)($sub['name'] ?? ''))) ?> by verified background-checked service partners.</p>
        </div>

        <div class="subcat-card-footer">
          <div class="subcat-price-wrap">
            <span class="subcat-price-label">Starts at</span>
            <span class="subcat-price-val"><?= htmlspecialchars((string)($sub['price'] ?? '')) ?></span>
          </div>

          <a href="<?= htmlspecialchars((string)($sub['link'] ?? '')) ?>" class="btn-subcat-book">
            <span>Book Now</span>
            <ion-icon name="arrow-forward-outline"></ion-icon>
          </a>
        </div>

      </div>
    <?php endforeach; ?>

    <?php if ($isHomePage): ?>
      <div class="subcat-home-footer-cta">
        <a href="./service-category.php?view=subcategories" class="btn-explore-all-cta" title="Explore all <?= count($subcategoriesData) ?> verified subcategories on Category Page">
          <span>Explore All</span>
          <ion-icon name="arrow-forward-outline"></ion-icon>
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Unified View Switcher Controller -->
<script>
  window.switchCategoryView = function(mode, filterDomain = null) {
    const catGrid = document.getElementById('categories-grid');
    const subGrid = document.getElementById('subcategories-grid');
    const btnCat = document.getElementById('btn-view-categories');
    const btnSub = document.getElementById('btn-view-subcategories');
    const heading = document.getElementById('cat-main-heading');
    const counter = document.getElementById('categories-count');
    const viewAllLink = document.getElementById('cat-view-all-link');
    const viewAllText = document.getElementById('cat-view-all-text');
    const isHome = <?= $isHomePage ? 'true' : 'false' ?>;

    if (mode === 'subcategories') {
      if (catGrid) catGrid.style.display = 'none';
      if (subGrid) subGrid.style.display = 'grid';
      if (btnCat) btnCat.classList.remove('active');
      if (btnSub) btnSub.classList.add('active');

      if (heading) {
        heading.textContent = isHome 
          ? 'Top Subcategories & Specializations' 
          : 'All <?= count($subcategoriesData) ?> Subcategories & Specializations';
      }
      if (counter) {
        counter.textContent = isHome 
          ? 'Showing 9 subcategories' 
          : 'Showing <?= count($subcategoriesData) ?> subcategories';
      }

      // When on home page, update the link beside the switcher to All Subcategories
      if (viewAllLink && viewAllText) {
        viewAllLink.href = './service-category.php?view=subcategories';
        viewAllText.textContent = 'All Subcategories';
        viewAllLink.setAttribute('title', 'Browse all <?= count($subcategoriesData) ?> subcategories on Category Page');
      }

      // Update breadcrumb on category page if present
      const breadcrumbCurrent = document.getElementById('cat-breadcrumb-current');
      if (breadcrumbCurrent) breadcrumbCurrent.textContent = 'All Subcategories';

      if (!isHome) {
        localStorage.setItem('serveasy_cat_view', 'subcategories');
      }
    } else {
      if (subGrid) subGrid.style.display = 'none';
      if (catGrid) catGrid.style.display = 'grid';
      if (btnSub) btnSub.classList.remove('active');
      if (btnCat) btnCat.classList.add('active');

      if (heading) {
        heading.textContent = '<?= addslashes($sectionTitle) ?>';
      }
      if (counter) {
        counter.textContent = 'Showing <?= count($displayedCategories) ?> domains';
      }

      // When on home page, restore the link beside the switcher to All Categories
      if (viewAllLink && viewAllText) {
        viewAllLink.href = './service-category.php';
        viewAllText.textContent = 'All Categories';
        viewAllLink.setAttribute('title', 'View all service categories');
      }

      // Update breadcrumb on category page if present
      const breadcrumbCurrent = document.getElementById('cat-breadcrumb-current');
      if (breadcrumbCurrent) breadcrumbCurrent.textContent = 'All Categories';

      if (!isHome) {
        localStorage.setItem('serveasy_cat_view', 'categories');
      }
    }

    if (filterDomain) {
      // Find matching pill and trigger it
      const pills = document.querySelectorAll('.categories-pills .pill, .cat-filter-pills .cat-pill');
      pills.forEach(p => {
        const f = p.getAttribute('data-filter') || p.getAttribute('data-slug');
        if (f === filterDomain) {
          p.click();
        }
      });
    } else {
      if (window.ServEasyCards && typeof window.ServEasyCards.filterCategories === 'function') {
        window.ServEasyCards.filterCategories(window.activeCategoryFilter || 'all', window.activeCategorySearch || '');
      }
    }
  };

  // Restore view mode from URL param (or localStorage on category page only)
  document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get('view');
    const isHome = <?= $isHomePage ? 'true' : 'false' ?>;
    const saved = localStorage.getItem('serveasy_cat_view');

    if (viewParam === 'subcategories' || (!isHome && saved === 'subcategories' && !urlParams.has('domain'))) {
      window.switchCategoryView('subcategories');
    }
  });
</script>
