<?php
require_once __DIR__ . '/../common/auth_session.php';

$dbPath = file_exists(__DIR__ . '/../../backend/config/db_connection.php')
    ? __DIR__ . '/../../backend/config/db_connection.php'
    : (file_exists(__DIR__ . '/../../../backend/config/db_connection.php')
        ? __DIR__ . '/../../../backend/config/db_connection.php'
        : null);
$pdo = null;
if ($dbPath && file_exists($dbPath)) {
    try {
        require_once $dbPath;
        if (class_exists('Database')) {
            $pdo = Database::connect();
        }
    } catch (\Throwable $t) {
        $pdo = null;
    }
}
if (!$pdo && file_exists(__DIR__ . '/../common/db_connection.php')) {
    require_once __DIR__ . '/../common/db_connection.php';
    if (class_exists('ServEasyWebDB')) {
        try {
            $pdo = ServEasyWebDB::connect();
        } catch (\Throwable $t) {
            $pdo = null;
        }
    }
}

// ── Handle AJAX Requests ────────────────────────────────────────────────
if (isset($_REQUEST['ajax_action'])) {
    header('Content-Type: application/json');
    if (!$pdo) {
        if (file_exists(__DIR__ . '/../common/ApiBridge.php')) {
            require_once __DIR__ . '/../common/ApiBridge.php';
            $payload = array_merge($_GET, $_POST, ['action' => $_REQUEST['ajax_action']]);
            $apiRes = \Frontend\ApiBridge::callApi('web-portal-api.php', 'POST', $payload);
            if (is_array($apiRes)) {
                echo json_encode($apiRes);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Blog API unavailable']);
        exit;
    }
    $action = $_REQUEST['ajax_action'];

    // 1. Like Post Action
    if ($action === 'like_post') {
        $post_id = (int)($_POST['id'] ?? 0);
        if ($post_id > 0) {
            $stmt = $pdo->prepare("UPDATE blog_posts SET likes = likes + 1 WHERE id = ?");
            $stmt->execute([$post_id]);

            $cnt_stmt = $pdo->prepare("SELECT likes FROM blog_posts WHERE id = ?");
            $cnt_stmt->execute([$post_id]);
            $likes = $cnt_stmt->fetchColumn();

            echo json_encode(['status' => 'success', 'likes' => (int)$likes]);
            exit;
        }
    }

    // 2. Fetch Single Post for Modal Reading View
    if ($action === 'get_modal_post') {
        $post_id = (int)($_GET['id'] ?? 0);
        if ($post_id > 0) {
            // Increment views count
            $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);

            $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ? AND status = 'published' LIMIT 1");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($post) {
                echo json_encode(['status' => 'success', 'data' => $post]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Post not found or is in draft.']);
            }
            exit;
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

// ── Single Post View Mode ───────────────────────────────────────────────
$single_post = null;
if ($pdo && (!empty($_GET['id']) || !empty($_GET['slug']))) {
    if (!empty($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ? AND status = 'published' LIMIT 1");
        $stmt->execute([(int)$_GET['id']]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([trim($_GET['slug'])]);
    }
    $single_post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($single_post) {
        // Increment views
        $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?")->execute([$single_post['id']]);
    }
}

// ── Fetch All Published Posts for Catalog Mode ──────────────────────────
$published_posts = [];
if ($pdo) {
    try {
        $published_posts = $pdo->query("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $t) {}
} elseif (file_exists(__DIR__ . '/../common/ApiBridge.php')) {
    require_once __DIR__ . '/../common/ApiBridge.php';
    $qs = 'web-portal-api.php?action=blog_init';
    if (!empty($_GET['id'])) $qs .= '&id=' . (int)$_GET['id'];
    if (!empty($_GET['slug'])) $qs .= '&slug=' . urlencode(trim($_GET['slug']));
    $bRes = \Frontend\ApiBridge::callApi($qs);
    if (!empty($bRes['status']) && $bRes['status'] === 'success') {
        $single_post     = $bRes['single_post'] ?? null;
        $published_posts = is_array($bRes['published_posts'] ?? null) ? $bRes['published_posts'] : [];
    }
}

// ── Verified Seed Articles & Content Enrichment (Finding 14 & 15) ────────
$top7_content = '<h2>1. Check Engine Fluids Before Departure</h2><p>Before any major highway journey or road trip, inspecting engine oil levels, coolant, transmission fluid, and brake fluid is paramount. Low or degraded fluid drastically increases friction, engine wear, and overheating risk within the engine block.</p><h2>2. Inspect Tire Pressure, Tread Depth & Spare Tire</h2><p>Tires are your vehicle\'s only contact point with the asphalt. Maintain manufacturer-recommended cold PSI levels (usually located on the driver-side door jamb sticker). Verify minimum 3mm tread depth across all wheels, inspect sidewalls for cracks, and never hit the highway without verifying the inflation and condition of your spare tire.</p><h2>3. Test All Exterior Lights & Hazard Indicators</h2><p>Ensure low beams, high beams, fog lamps, taillights, brake indicators, and turn signals function flawlessly. In poor visibility, heavy monsoon rainfall, or nighttime driving, functional exterior lighting is your primary collision-prevention safeguard.</p><h2>4. Verify Brake Pad Wear & Brake Fluid Integrity</h2><p>Highway braking requires maximum stopping efficiency. Inspect brake pads for uneven wear (replace if below 3mm thickness), listen for squealing or grinding noises, and verify brake pedal firmness. Spongy pedal response indicates moisture or air bubbles in brake fluid lines that demand immediate flushing.</p><h2>5. Inspect Car Battery Terminals & Charge Voltage</h2><p>Sudden battery failure is the leading cause of highway roadside stalls. Inspect battery terminals for white powdery acid corrosion, ensure tight cable clamp connections, and verify resting voltage is above 12.4V using a multimeter or doorstep battery analyzer.</p><h2>6. Replace Worn Windshield Wipers & Top Up Washer Fluid</h2><p>Streaking or skipping wiper blades compromise driver visibility during sudden downpours. Replace old wiper blades, ensure nozzle jets are unblocked, and fill the washer reservoir with dedicated bug-remover washer concentrate rather than plain tap water.</p><h2>7. Pack Emergency Roadside Kit & Schedule ServEasy Mobile Inspection</h2><p>Keep a certified roadside emergency kit inside your trunk containing reflective warning triangles, a heavy-duty tow strap, jumper cables, flashlight, tire inflator, and basic hand tools. To travel with total peace of mind, book a ServEasy certified mobile technician for a comprehensive 40-point pre-trip vehicle health inspection right at your doorstep.</p>';

$seed_articles = [
    [
        'id' => 1,
        'title' => 'Top 7 Essential Vehicle Maintenance Tips for Long Drives',
        'slug' => 'top-7-essential-vehicle-maintenance-tips',
        'category' => 'Vehicle Care',
        'excerpt' => 'Preparing for an extended highway journey? Discover critical fluid checks, tire pressure recommendations, and battery maintenance before you hit the road.',
        'content' => $top7_content,
        'featured_image' => './assets/images/blog-1.jpg',
        'author_name' => 'Marcus Sterling',
        'author_role' => 'Lead Automotive Specialist',
        'tags' => 'Vehicle, Road Trip, Maintenance, Car Care',
        'read_time' => '7 min read',
        'views' => 145,
        'likes' => 30,
        'status' => 'published',
        'created_at' => '2026-10-14 12:24:53'
    ],
    [
        'id' => 2,
        'title' => 'Deep Home Sanitization: Beyond Basic Dusting and Sweeping',
        'slug' => 'deep-home-sanitization-guide',
        'category' => 'Home & Living',
        'excerpt' => 'Learn how professional hygiene teams sterilize high-touch surfaces, sanitize air ducts, and eradicate unseen allergens from living spaces.',
        'content' => '<h2>Understanding the Difference Between Cleaning & Sanitizing</h2><p>Regular surface wiping removes visible grime, but sanitization actively kills microscopic pathogens, bacteria, and allergens embedded in carpets, curtains, and upholstery.</p><h2>Target High-Touch Surfaces</h2><p>Door handles, light switches, cabinet pulls, remote controls, and kitchen countertops carry dense concentrations of everyday microbes. Use certified antimicrobial disinfectants.</p><h2>Bathroom & Kitchen Micro-Climate</h2><p>High moisture levels create ideal breeding grounds for mildew and spores. Professional steam cleaning delivers high temperature heat that sterilizes without damaging fragile tile grouting.</p><h2>Book Professional Cleaners</h2><p>ServEasy certified cleaners utilize medical-grade sanitizers and high-pressure steam extraction equipment for comprehensive home renewal.</p>',
        'featured_image' => './assets/images/blog-2.jpg',
        'author_name' => 'Elena Rostova',
        'author_role' => 'Hygiene & Safety Consultant',
        'tags' => 'Cleaning, Hygiene, Home Care, Sanitization',
        'read_time' => '6 min read',
        'views' => 105,
        'likes' => 25,
        'status' => 'published',
        'created_at' => '2026-10-14 12:24:53'
    ],
    [
        'id' => 3,
        'title' => 'Emergency Roadside Breakdown: A Step-by-Step Survival Checklist',
        'slug' => 'emergency-roadside-breakdown-checklist',
        'category' => 'Emergency & Safety',
        'excerpt' => 'Vehicle stalled in the middle of traffic or on a dark road? Here is how to keep yourself safe and dispatch emergency towing in minutes.',
        'content' => '<h2>Step 1: Steer to Safety Immediately</h2><p>If the engine starts sputtering or steam emerges, gently maneuver your vehicle onto the shoulder or a well-lit service bay. Avoid stopping on blind corners or high-speed lanes.</p><h2>Step 2: Activate Hazard Lights & Deploy Warning Triangles</h2><p>Alert approaching motorists by switching on hazard blinkers immediately. If safe, position reflective warning triangles 50 meters behind the vehicle.</p><h2>Step 3: Remain Inside or Behind Safety Barriers</h2><p>Never stand directly on highway lanes or between vehicles. Stay seated with seatbelts fastened or wait safely behind roadside guard rails.</p><h2>Step 4: Trigger ServEasy 24/7 SOS Dispatch</h2><p>Call our direct emergency line or use the 1-click Emergency SOS on the ServEasy app. Our closest mobile tow truck and technician will be dispatched to your GPS coordinate instantly.</p>',
        'featured_image' => './assets/images/blog-3.jpg',
        'author_name' => 'David Chen',
        'author_role' => 'Emergency Dispatch Coordinator',
        'tags' => 'Emergency, Breakdown, Towing, Roadside',
        'read_time' => '5 min read',
        'views' => 215,
        'likes' => 51,
        'status' => 'published',
        'created_at' => '2026-10-14 12:24:53'
    ]
];

if (empty($published_posts)) {
    $published_posts = $seed_articles;
} else {
    // Enrich Post 1 to guarantee all 7 tips are present
    foreach ($published_posts as &$p) {
        if ((int)$p['id'] === 1 || ($p['slug'] ?? '') === 'top-7-essential-vehicle-maintenance-tips') {
            $p['read_time'] = '7 min read';
            if (substr_count($p['content'], '<h2>') < 7) {
                $p['content'] = $top7_content;
            }
        }
    }
    unset($p);
}

// Ensure single_post resolution for Post 1
if ($single_post) {
    if ((int)$single_post['id'] === 1 || ($single_post['slug'] ?? '') === 'top-7-essential-vehicle-maintenance-tips') {
        $single_post['read_time'] = '7 min read';
        if (substr_count($single_post['content'], '<h2>') < 7) {
            $single_post['content'] = $top7_content;
        }
    }
} elseif (!empty($_GET['id']) || !empty($_GET['slug'])) {
    $targetId = (int)($_GET['id'] ?? 0);
    $targetSlug = trim($_GET['slug'] ?? '');
    foreach ($published_posts as $sp) {
        if (($targetId > 0 && (int)$sp['id'] === $targetId) || ($targetSlug !== '' && ($sp['slug'] ?? '') === $targetSlug)) {
            $single_post = $sp;
            break;
        }
    }
}

// Extract featured post (first post) and regular grid posts
$featured_post = null;
$grid_posts    = [];
if (!empty($published_posts)) {
    $featured_post = $published_posts[0];
    $grid_posts    = array_slice($published_posts, 1);
}

// Extract unique categories for filter pills
$categories = [];
foreach ($published_posts as $p) {
    if (!empty($p['category']) && !in_array($p['category'], $categories)) {
        $categories[] = $p['category'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $single_post ? htmlspecialchars($single_post['title']) . ' — ServEasy Blog' : 'ServEasy Blog — Guides, Service Insights & Tips' ?></title>
  <meta name="description" content="<?= $single_post ? htmlspecialchars($single_post['excerpt']) : 'Explore maintenance guides, deep home cleaning tips, roadside assistance protocols, and updates on ServEasy.' ?>">

  <!-- Favicon -->
    <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
  <link rel="alternate icon" type="image/x-icon" href="./assets/favicon.ico">
  <link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon-32x32.png">
  <link rel="apple-touch-icon" sizes="180x180" href="./assets/apple-touch-icon.png">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

  <!-- Core Stylesheets -->
  <link rel="stylesheet" href="./assets/css/main.css">
  <link rel="stylesheet" href="./assets/css/global.css">
  <link rel="stylesheet" href="./assets/css/home.css">
  <link rel="stylesheet" href="./assets/css/notify.css">
  <link rel="stylesheet" href="./assets/css/blog.css">
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

      <!-- BREADCRUMBS -->
      <div class="blog-breadcrumb-wrap">
        <ol class="blog-breadcrumb-list">
          <li class="blog-breadcrumb-item">
            <a href="./index.php" class="blog-breadcrumb-link">
              <ion-icon name="home-outline"></ion-icon>
              <span>Home</span>
            </a>
          </li>
          <li class="blog-breadcrumb-separator">/</li>
          <?php if ($single_post): ?>
            <li class="blog-breadcrumb-item">
              <a href="./blog.php" class="blog-breadcrumb-link">Blog</a>
            </li>
            <li class="blog-breadcrumb-separator">/</li>
            <li class="blog-breadcrumb-item">
              <span class="blog-breadcrumb-current"><?= htmlspecialchars(substr($single_post['title'], 0, 35)) ?>...</span>
            </li>
          <?php else: ?>
            <li class="blog-breadcrumb-item">
              <span class="blog-breadcrumb-current">Blog & Insights</span>
            </li>
          <?php endif; ?>
        </ol>
      </div>

      <?php if ($single_post): ?>
        <!-- ==========================================
             SINGLE ARTICLE FULL READING VIEW
             ========================================== -->
        <section class="article-detail-container">
          <div class="article-hero-box">
            <span class="article-category-badge"><?= htmlspecialchars($single_post['category']) ?></span>
            <h1 class="article-main-title"><?= htmlspecialchars($single_post['title']) ?></h1>

            <div class="article-header-meta">
              <div class="author-chip">
                <div class="author-avatar"><?= strtoupper(substr($single_post['author_name'], 0, 1)) ?></div>
                <div class="author-info">
                  <h5><?= htmlspecialchars($single_post['author_name']) ?></h5>
                  <p><?= htmlspecialchars($single_post['author_role']) ?></p>
                </div>
              </div>

              <div class="d-flex align-items-center gap-3" style="font-size: 0.85rem; color: var(--text-secondary); display: flex; gap: 16px;">
                <span><ion-icon name="calendar-outline" style="vertical-align: -2px; color: var(--neon-cyan);"></ion-icon> <?= date('F j, Y', strtotime($single_post['created_at'])) ?></span>
                <span><ion-icon name="time-outline" style="vertical-align: -2px; color: var(--neon-cyan);"></ion-icon> <?= htmlspecialchars($single_post['read_time']) ?></span>
                <span><ion-icon name="eye-outline" style="vertical-align: -2px; color: var(--neon-cyan);"></ion-icon> <?= $single_post['views'] ?> views</span>
              </div>
            </div>

            <!-- Cover Image -->
            <div class="article-cover-frame">
              <img src="<?= htmlspecialchars($single_post['featured_image']) ?>" alt="<?= htmlspecialchars($single_post['title']) ?>" onerror="this.src='./assets/images/blog-1.jpg';">
            </div>

            <!-- Full Article Body -->
            <div class="article-body-content">
              <?= $single_post['content'] ?>
            </div>

            <!-- Tags -->
            <?php if (!empty($single_post['tags'])): 
              $tags_arr = array_map('trim', explode(',', $single_post['tags']));
            ?>
              <div class="article-tags-wrap">
                <span style="font-size: 0.85rem; color: #94a3b8; font-weight: 600;">Tags:</span>
                <?php foreach ($tags_arr as $tag): ?>
                  <span class="article-tag-pill">#<?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <!-- Share & Back Bar -->
            <div class="article-share-bar">
              <a href="./blog.php" class="btn-back-catalog">
                <ion-icon name="arrow-back-outline"></ion-icon>
                <span>Back to All Articles</span>
              </a>

              <div style="display: flex; gap: 10px; align-items: center;">
                <button type="button" class="btn-like-post" onclick="likePost(<?= $single_post['id'] ?>, this)">
                  <ion-icon name="heart-outline"></ion-icon>
                  <span class="like-counter"><?= $single_post['likes'] ?></span>
                </button>
                <button type="button" class="btn-back-catalog" onclick="shareArticle('<?= htmlspecialchars(addslashes($single_post['title'])) ?>')">
                  <ion-icon name="share-social-outline"></ion-icon>
                  <span>Share</span>
                </button>
              </div>
            </div>

          </div>
        </section>

      <?php else: ?>
        <!-- ==========================================
             CATALOG / ALL ARTICLES VIEW
             ========================================== -->

        <!-- HERO SECTION -->
        <section class="blog-hero-box">
          <div class="blog-badge-pill">
            <span class="pulse-blog-dot"></span>
            <span>ServEasy Knowledge & Insights</span>
          </div>

          <h1 class="blog-hero-title">Expert Guides, Service Insights & Tips</h1>
          <p class="blog-hero-subtitle">
            Explore trusted maintenance advice, professional hygiene protocols, vehicle care checklists, and official ServEasy news written by certified industry technicians.
          </p>

          <!-- Interactive Search Bar -->
          <div class="blog-search-container">
            <ion-icon name="search-outline" class="blog-search-icon"></ion-icon>
            <input type="text" id="blog-search" placeholder="Search guides, car care tips, home hygiene, emergency rescue..." class="blog-search-bar">
          </div>

          <!-- Category Filter Pills -->
          <div class="blog-categories-wrap" id="category-pills">
            <div class="blog-pill active" data-filter="all">All Topics</div>
            <?php foreach ($categories as $cat): ?>
              <div class="blog-pill" data-filter="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></div>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- FEATURED TOP POST (IF AVAILABLE) -->
        <?php if ($featured_post): ?>
          <section class="featured-article-card" id="featured-article-box" data-category="<?= htmlspecialchars($featured_post['category']) ?>" data-search="<?= htmlspecialchars(strtolower($featured_post['title'] . ' ' . $featured_post['excerpt'] . ' ' . $featured_post['tags'])) ?>">
            <div class="featured-img-wrap">
              <span class="featured-badge-highlight">Featured Article</span>
              <img src="<?= htmlspecialchars($featured_post['featured_image']) ?>" alt="<?= htmlspecialchars($featured_post['title']) ?>" onerror="this.src='./assets/images/blog-1.jpg';">
            </div>

            <div class="featured-content-wrap">
              <div class="featured-meta-row">
                <span class="featured-category"><?= htmlspecialchars($featured_post['category']) ?></span>
                <span>•</span>
                <span><?= date('M j, Y', strtotime($featured_post['created_at'])) ?></span>
                <span>•</span>
                <span><?= htmlspecialchars($featured_post['read_time']) ?></span>
              </div>

              <a href="./blog.php?id=<?= $featured_post['id'] ?>" style="text-decoration: none;">
                <h2 class="featured-title"><?= htmlspecialchars($featured_post['title']) ?></h2>
              </a>

              <p class="featured-excerpt">
                <?= htmlspecialchars($featured_post['excerpt']) ?>
              </p>

              <div class="featured-footer">
                <div class="author-chip">
                  <div class="author-avatar"><?= strtoupper(substr($featured_post['author_name'], 0, 1)) ?></div>
                  <div class="author-info">
                    <h5><?= htmlspecialchars($featured_post['author_name']) ?></h5>
                    <p><?= htmlspecialchars($featured_post['author_role']) ?></p>
                  </div>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                  <button type="button" class="btn-like-post" onclick="likePost(<?= $featured_post['id'] ?>, this)">
                    <ion-icon name="heart-outline"></ion-icon>
                    <span class="like-counter"><?= $featured_post['likes'] ?></span>
                  </button>

                  <a href="./blog.php?id=<?= $featured_post['id'] ?>" class="btn-read-featured">
                    <span>Read Guide</span>
                    <ion-icon name="arrow-forward-outline"></ion-icon>
                  </a>
                </div>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <!-- ALL ARTICLES GRID SECTION -->
        <section class="articles-catalog-section">
          <div class="articles-section-header">
            <h3 class="articles-section-title">Latest Articles</h3>
            <span class="articles-count-label" id="articles-counter">Showing <?= count($published_posts) ?> guides</span>
          </div>

          <div class="blog-grid" id="articles-grid">
            <?php if (empty($published_posts)): ?>
              <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px;">
                <ion-icon name="newspaper-outline" style="font-size: 3.5rem; color: var(--neon-cyan); margin-bottom: 15px;"></ion-icon>
                <h4 style="color: #fff; margin-bottom: 8px;">No Articles Published Yet</h4>
                <p style="color: var(--text-secondary);">Check back soon for expert tips, repair walkthroughs, and service insights.</p>
              </div>
            <?php else: ?>
              <?php foreach ($published_posts as $post): ?>
                <div class="blog-card-glass article-item" data-category="<?= htmlspecialchars($post['category']) ?>" data-search="<?= htmlspecialchars(strtolower($post['title'] . ' ' . $post['excerpt'] . ' ' . $post['tags'])) ?>">
                  
                  <div class="blog-card-banner">
                    <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy" onerror="this.src='./assets/images/blog-1.jpg';">
                    <span class="blog-card-category"><?= htmlspecialchars($post['category']) ?></span>
                  </div>

                  <div class="blog-card-body">
                    <div class="blog-card-meta">
                      <span><ion-icon name="calendar-outline"></ion-icon> <?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                      <span>•</span>
                      <span><ion-icon name="time-outline"></ion-icon> <?= htmlspecialchars($post['read_time']) ?></span>
                    </div>

                    <h4 class="blog-card-title">
                      <a href="./blog.php?id=<?= $post['id'] ?>" style="color: inherit; text-decoration: none;">
                        <?= htmlspecialchars($post['title']) ?>
                      </a>
                    </h4>

                    <p class="blog-card-excerpt">
                      <?= htmlspecialchars(substr($post['excerpt'], 0, 120)) ?>...
                    </p>

                    <div class="blog-card-footer">
                      <button type="button" class="btn-read-post" onclick="openPostModal(<?= $post['id'] ?>)">
                        <span>Quick Read</span>
                        <ion-icon name="arrow-forward-outline"></ion-icon>
                      </button>

                      <button type="button" class="btn-like-post" onclick="likePost(<?= $post['id'] ?>, this)">
                        <ion-icon name="heart-outline"></ion-icon>
                        <span class="like-counter"><?= $post['likes'] ?></span>
                      </button>
                    </div>
                  </div>

                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>

      <?php endif; ?>

    </article>
  </main>

  <!-- QUICK READ LIGHTBOX MODAL -->
  <div class="blog-modal-overlay" id="blog-reading-modal" role="dialog" aria-modal="true" aria-labelledby="modal-article-title">
    <div class="blog-modal-box">
      <div class="blog-modal-header">
        <span class="article-category-badge" id="modal-post-category" style="margin-bottom: 0;">Category</span>
        <button type="button" class="blog-modal-close" id="btn-close-reading-modal" aria-label="Close modal">
          <ion-icon name="close-outline"></ion-icon>
        </button>
      </div>

      <div class="blog-modal-body">
        <h2 class="article-main-title" id="modal-post-title" style="font-size: 1.8rem; margin-bottom: 12px;">Article Title</h2>

        <div class="d-flex align-items-center gap-3 mb-3" style="display: flex; gap: 16px; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 20px;">
          <span id="modal-post-author">By Author</span>
          <span>•</span>
          <span id="modal-post-date">Date</span>
          <span>•</span>
          <span id="modal-post-readtime">5 min read</span>
        </div>

        <div class="article-cover-frame" style="max-height: 280px; margin-bottom: 24px;">
          <img id="modal-post-img" src="" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <div class="article-body-content" id="modal-post-content" style="font-size: 0.98rem; line-height: 1.7;">
          <!-- Article HTML loaded via AJAX -->
        </div>

        <div style="margin-top: 25px; text-align: right;">
          <a id="modal-post-permalink" href="#" class="btn-read-featured">
            <span>Open Full Page</span>
            <ion-icon name="open-outline"></ion-icon>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- BACK TO TOP -->
  <a href="#top" class="back-to-top" aria-label="Back to Top" data-back-top-btn>
    <ion-icon name="arrow-up-outline"></ion-icon>
  </a>

  <!-- Custom Scripts -->
  <script src="./assets/js/notify.js"></script>
  <script src="./assets/js/script.js"></script>

  <!-- Ionicons Modules -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <!-- Dynamic Blog Filter & Interactive Script -->
  <script>
    (function() {
      'use strict';

      // 1. Live Client-Side Search and Category Filtering
      const searchInput = document.getElementById('blog-search');
      const pills = document.querySelectorAll('.blog-pill');
      const articles = document.querySelectorAll('.article-item');
      const featuredBox = document.getElementById('featured-article-box');
      const counterEl = document.getElementById('articles-counter');

      let currentCategory = 'all';
      let currentQuery = '';

      function filterArticles() {
        let visibleCount = 0;

        articles.forEach(card => {
          const cardCat = card.getAttribute('data-category') || '';
          const cardSearch = card.getAttribute('data-search') || '';

          const matchCat = (currentCategory === 'all' || cardCat.toLowerCase() === currentCategory.toLowerCase());
          const matchQuery = (currentQuery === '' || cardSearch.includes(currentQuery));

          if (matchCat && matchQuery) {
            card.style.display = '';
            visibleCount++;
          } else {
            card.style.display = 'none';
          }
        });

        if (featuredBox) {
          const featCat = featuredBox.getAttribute('data-category') || '';
          const featSearch = featuredBox.getAttribute('data-search') || '';
          const matchCat = (currentCategory === 'all' || featCat.toLowerCase() === currentCategory.toLowerCase());
          const matchQuery = (currentQuery === '' || featSearch.includes(currentQuery));

          if (matchCat && matchQuery) {
            featuredBox.style.display = '';
            visibleCount++;
          } else {
            featuredBox.style.display = 'none';
          }
        }

        if (counterEl) {
          counterEl.textContent = `Showing ${visibleCount} guides`;
        }
      }

      if (searchInput) {
        searchInput.addEventListener('input', function() {
          currentQuery = this.value.toLowerCase().trim();
          filterArticles();
        });
      }

      pills.forEach(pill => {
        pill.addEventListener('click', function() {
          pills.forEach(p => p.classList.remove('active'));
          this.classList.add('active');
          currentCategory = this.getAttribute('data-filter') || 'all';
          filterArticles();
        });
      });

      // 2. Lightbox Modal Quick Read
      const modal = document.getElementById('blog-reading-modal');
      const modalClose = document.getElementById('btn-close-reading-modal');

      window.openPostModal = async function(id) {
        try {
          const res = await fetch(`blog.php?ajax_action=get_modal_post&id=${id}`);
          const json = await res.json();

          if (json.status === 'success' && json.data) {
            const post = json.data;
            document.getElementById('modal-post-category').textContent = post.category;
            document.getElementById('modal-post-title').textContent = post.title;
            document.getElementById('modal-post-author').textContent = 'By ' + post.author_name;
            document.getElementById('modal-post-date').textContent = new Date(post.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            document.getElementById('modal-post-readtime').textContent = post.read_time;
            document.getElementById('modal-post-content').innerHTML = post.content;
            document.getElementById('modal-post-img').src = post.featured_image || './assets/images/blog-1.jpg';
            document.getElementById('modal-post-permalink').href = `blog.php?id=${post.id}`;

            if (modal) modal.classList.add('open');
          }
        } catch (e) {
          console.error('Modal error:', e);
          window.location.href = `blog.php?id=${id}`;
        }
      };

      if (modalClose && modal) {
        modalClose.addEventListener('click', function() {
          modal.classList.remove('open');
        });
        modal.addEventListener('click', function(e) {
          if (e.target === modal) {
            modal.classList.remove('open');
          }
        });
      }

      // 3. Like Post Asynchronously
      window.likePost = async function(id, btn) {
        try {
          const formData = new FormData();
          formData.append('ajax_action', 'like_post');
          formData.append('id', id);

          const res = await fetch('blog.php', {
            method: 'POST',
            body: formData
          });

          const json = await res.json();
          if (json.status === 'success') {
            btn.classList.add('liked');
            const counter = btn.querySelector('.like-counter');
            if (counter) counter.textContent = json.likes;
            if (window.Notify) {
              Notify.success('Thank you for liking this article!');
            }
          }
        } catch (e) {
          console.error('Like error:', e);
        }
      };

      // 4. Share Article Utility
      window.shareArticle = async function(title) {
        if (navigator.share) {
          try {
            await navigator.share({
              title: title,
              url: window.location.href
            });
          } catch (err) {
            // Cancelled
          }
        } else {
          try {
            await navigator.clipboard.writeText(window.location.href);
            if (window.Notify) {
              Notify.info('Article link copied to clipboard!');
            } else {
              alert('Article link copied to clipboard!');
            }
          } catch (e) {
            alert('Share URL: ' + window.location.href);
          }
        }
      };

    })();
  </script>

</body>

</html>
