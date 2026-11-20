<?php
/**
 * ServEasy Customer Web Portal - About Us & Visual Team Organization Tree
 * Hierarchical Org Chart: Level 1 (Sharthak Sen) -> Level 2 (3 Directorate Heads) -> Level 3 (7 Functional Leads)
 */
require_once __DIR__ . '/../common/auth_session.php';
if (!defined('SERVEASY_ASSET_VER')) {
    define('SERVEASY_ASSET_VER', '20261120v2');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About ServEasy — Leadership Team & Visual Organization Tree</title>
  <meta name="description" content="Visual organizational site map and leadership tree for ServEasy, led by Sharthak Sen (Founder & CEO), 3 directorate department heads, and 7 functional specialist leads.">
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
    /* ── Hero Section ────────────────────────────────────────────────── */
    .about-hero-section {
      padding: 60px 20px 30px;
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
      max-width: 950px;
      margin: 0 auto 18px;
    }
    .about-hero-title span {
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .about-hero-desc {
      font-size: 1.15rem;
      color: #94a3b8;
      max-width: 820px;
      margin: 0 auto 30px;
      line-height: 1.7;
    }

    /* ── View Switcher Bar ───────────────────────────────────────────── */
    .view-switcher-bar {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 15px;
      margin-bottom: 35px;
      flex-wrap: wrap;
    }
    .btn-view-toggle {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 22px;
      border-radius: 50px;
      background: rgba(30, 41, 59, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: #cbd5e1;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.25s ease;
    }
    .btn-view-toggle:hover,
    .btn-view-toggle.active {
      background: linear-gradient(135deg, rgba(0, 242, 254, 0.25), rgba(79, 172, 254, 0.25));
      border-color: #00f2fe;
      color: #00f2fe;
      box-shadow: 0 4px 15px rgba(0, 242, 254, 0.25);
    }

    .tree-scroll-hint {
      display: none;
      text-align: center;
      color: #94a3b8;
      font-size: 0.85rem;
      margin-bottom: 15px;
    }
    @media (max-width: 1024px) {
      .tree-scroll-hint { display: block; }
    }

    /* ── PURE CSS ORGANIZATIONAL TREE DIAGRAM ────────────────────────── */
    .org-chart-wrapper {
      width: 100%;
      overflow-x: auto;
      padding: 30px 10px 60px;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 24px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
      margin-bottom: 60px;
    }

    .tree {
      display: flex;
      justify-content: center;
      min-width: 980px;
    }

    .tree ul {
      padding-top: 30px;
      position: relative;
      transition: all 0.5s;
      display: flex;
      justify-content: center;
      margin: 0;
      padding-left: 0;
      list-style-type: none;
    }

    .tree li {
      float: left;
      text-align: center;
      list-style-type: none;
      position: relative;
      padding: 30px 14px 0 14px;
      transition: all 0.5s;
    }

    /* Tree Branch Connectors using ::before and ::after */
    .tree li::before, .tree li::after {
      content: '';
      position: absolute;
      top: 0;
      right: 50%;
      border-top: 2px solid #00f2fe;
      width: 50%;
      height: 30px;
    }
    .tree li::after {
      right: auto;
      left: 50%;
      border-left: 2px solid #00f2fe;
    }

    .tree li:only-child::after, .tree li:only-child::before {
      display: none;
    }
    .tree li:only-child {
      padding-top: 0;
    }

    .tree li:first-child::before, .tree li:last-child::after {
      border: 0 none;
    }

    .tree li:last-child::before {
      border-right: 2px solid #00f2fe;
      border-radius: 0 10px 0 0;
    }
    .tree li:first-child::after {
      border-radius: 10px 0 0 0;
    }

    /* Downward connector line from parent node */
    .tree ul ul::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      border-left: 2px solid #00f2fe;
      width: 0;
      height: 30px;
    }

    /* Node Card Base Styling */
    .tree-node-box {
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 20px 18px;
      text-decoration: none;
      color: #cbd5e1;
      display: inline-block;
      border-radius: 18px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      background: rgba(15, 23, 42, 0.95);
      backdrop-filter: blur(12px);
      position: relative;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }
    .tree-node-box:hover {
      transform: translateY(-4px);
      box-shadow: 0 15px 35px rgba(0, 242, 254, 0.25);
    }

    /* ── Node Tiers ─────────────────────────────────────────────────── */
    /* Level 1: Sharthak Sen */
    .node-l1 {
      width: 380px;
      border: 2px solid #00f2fe;
      background: linear-gradient(145deg, rgba(15, 23, 42, 0.98), rgba(30, 41, 59, 0.9));
      box-shadow: 0 0 30px rgba(0, 242, 254, 0.25), inset 0 0 20px rgba(0, 242, 254, 0.08);
      padding: 26px 22px;
    }
    .avatar-l1 {
      width: 76px;
      height: 76px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 50%, #6366f1 100%);
      color: #0b132b;
      font-size: 1.8rem;
      font-weight: 900;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 12px;
      border: 3px solid rgba(255, 255, 255, 0.25);
      box-shadow: 0 0 20px rgba(0, 242, 254, 0.4);
    }
    .name-l1 {
      font-size: 1.6rem;
      font-weight: 800;
      color: #ffffff;
      margin-bottom: 4px;
    }
    .role-l1 {
      font-size: 0.95rem;
      font-weight: 700;
      color: #00f2fe;
      margin-bottom: 10px;
    }
    .badge-l1 {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: rgba(245, 158, 11, 0.15);
      border: 1px solid rgba(245, 158, 11, 0.4);
      color: #fbbf24;
      padding: 4px 14px;
      border-radius: 50px;
      font-size: 0.78rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.6px;
    }

    /* Level 2: Directorate Heads (3 Members) */
    .node-l2 {
      width: 280px;
      padding: 20px 16px;
    }
    .node-l2.cyan { border-top: 4px solid #00f2fe; border-color: rgba(0, 242, 254, 0.3); }
    .node-l2.purple { border-top: 4px solid #c084fc; border-color: rgba(192, 132, 252, 0.3); }
    .node-l2.amber { border-top: 4px solid #f59e0b; border-color: rgba(245, 158, 11, 0.3); }

    .avatar-l2 {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      font-size: 1.35rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 10px;
    }
    .avatar-l2.cyan { background: rgba(0, 242, 254, 0.15); color: #00f2fe; border: 2px solid rgba(0, 242, 254, 0.3); }
    .avatar-l2.purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 2px solid rgba(168, 85, 247, 0.3); }
    .avatar-l2.amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 2px solid rgba(245, 158, 11, 0.3); }

    .name-l2 {
      font-size: 1.25rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 4px;
    }
    .role-l2 {
      font-size: 0.82rem;
      font-weight: 600;
      margin-bottom: 8px;
    }
    .role-l2.cyan { color: #00f2fe; }
    .role-l2.purple { color: #c084fc; }
    .role-l2.amber { color: #fbbf24; }

    .dept-l2 {
      display: inline-block;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .dept-l2.cyan { background: rgba(0, 242, 254, 0.1); color: #00f2fe; border: 1px solid rgba(0, 242, 254, 0.25); }
    .dept-l2.purple { background: rgba(168, 85, 247, 0.1); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.25); }
    .dept-l2.amber { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25); }

    /* Level 3: Functional Leads & Specialists (7 Members) */
    .node-l3 {
      width: 175px;
      padding: 14px 10px;
      border-radius: 14px;
      background: rgba(30, 41, 59, 0.7);
    }
    .avatar-l3 {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #38bdf8;
      font-size: 1rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 8px;
    }
    .name-l3 {
      font-size: 0.95rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 2px;
      line-height: 1.2;
    }
    .role-l3 {
      font-size: 0.74rem;
      color: #38bdf8;
      font-weight: 600;
      margin-bottom: 6px;
      line-height: 1.3;
    }
    .tag-l3 {
      display: inline-block;
      font-size: 0.65rem;
      font-weight: 600;
      padding: 2px 6px;
      border-radius: 6px;
      background: rgba(255, 255, 255, 0.06);
      color: #cbd5e1;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ── Alternate Mobile/List View ─────────────────────────────────── */
    .org-list-view {
      display: none;
      max-width: 900px;
      margin: 0 auto 60px;
    }
    .list-tier-block {
      background: rgba(15, 23, 42, 0.8);
      backdrop-filter: blur(14px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 25px;
      margin-bottom: 25px;
    }
    .list-tier-title {
      font-size: 1.15rem;
      font-weight: 800;
      color: #00f2fe;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .list-members-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
    }
    .list-member-card {
      background: rgba(30, 41, 59, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 12px;
      padding: 15px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* ── Stats & Pillars ────────────────────────────────────────────── */
    .stats-banner-glass {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 35px 25px;
      margin: 40px 0 60px;
      text-align: center;
    }
    .stat-number {
      font-size: 2.4rem;
      font-weight: 800;
      color: #00f2fe;
      line-height: 1;
      margin-bottom: 8px;
    }
    .stat-label {
      color: #94a3b8;
      font-size: 0.9rem;
      font-weight: 600;
    }

    .about-grid-3 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 25px;
      margin-bottom: 60px;
    }
    .about-card {
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 35px 30px;
      transition: all 0.3s ease;
    }
    .about-card:hover {
      transform: translateY(-5px);
      border-color: rgba(0, 242, 254, 0.4);
      box-shadow: 0 12px 30px rgba(0, 242, 254, 0.15);
    }
    .about-card-icon {
      width: 56px;
      height: 56px;
      border-radius: 16px;
      background: rgba(0, 242, 254, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      color: #00f2fe;
      margin-bottom: 20px;
    }
    .about-card-icon.emerald { background: rgba(16, 185, 129, 0.1); color: #34d399; }
    .about-card-icon.purple { background: rgba(168, 85, 247, 0.1); color: #c084fc; }

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

  <!-- Sidebar Overlays -->
  <?php include __DIR__ . '/constants/aside.php'; ?>

  <main>
    <div class="container">

      <!-- Breadcrumbs -->
      <div style="padding: 25px 0 10px;">
        <span style="color: #64748b; font-size: 0.9rem;">
          <a href="../index.php" style="color: #94a3b8; text-decoration: none;">Home</a> / <span style="color: #00f2fe;">About ServEasy & Team Hierarchy Tree</span>
        </span>
      </div>

      <!-- Hero Section -->
      <section class="about-hero-section">
        <div class="about-badge">
          <ion-icon name="git-network-outline"></ion-icon>
          <span>Leadership & Team Hierarchy Tree</span>
        </div>
        <h1 class="about-hero-title">
          ServEasy <span>Team Organization Tree</span>
        </h1>
        <p class="about-hero-desc">
          Explore the official organizational site map of ServEasy: structured from top executive leadership under Sharthak Sen down to the 3 directorate department heads and 7 operational specialists.
        </p>

        <!-- View Toggle Controls -->
        <div class="view-switcher-bar">
          <button type="button" class="btn-view-toggle active" id="btn-show-tree" onclick="switchView('tree')">
            <ion-icon name="git-branch-outline"></ion-icon> Visual Tree Diagram
          </button>
          <button type="button" class="btn-view-toggle" id="btn-show-list" onclick="switchView('list')">
            <ion-icon name="list-outline"></ion-icon> Detailed Tier List
          </button>
        </div>
        <div class="tree-scroll-hint">
          <ion-icon name="swap-horizontal-outline"></ion-icon> Scroll horizontally to view complete hierarchy branches
        </div>
      </section>

      <!-- ===============================================================
           1. PURE CSS / SVG VISUAL ORGANIZATIONAL TREE DIAGRAM
           =============================================================== -->
      <div class="org-chart-wrapper" id="view-tree-container">
        <div class="tree">
          <ul>
            <!-- ROOT / LEVEL 1: SHARTHAK SEN -->
            <li>
              <div class="tree-node-box node-l1">
                <div class="avatar-l1">SS</div>
                <h2 class="name-l1">Sharthak Sen</h2>
                <div class="role-l1">Founder & Chief Executive Officer (Super Admin)</div>
                <div class="badge-l1">
                  <ion-icon name="ribbon-outline"></ion-icon> Level 1: Top Authority & Vision
                </div>
              </div>

              <!-- BRANCH DOWN TO LEVEL 2: 3 DIRECTORATE HEADS -->
              <ul>
                <!-- LEVEL 2 - MEMBER 1: SAIKAT SASMAL (TECH) -->
                <li>
                  <div class="tree-node-box node-l2 cyan">
                    <div class="avatar-l2 cyan">SS</div>
                    <h3 class="name-l2">Saikat Sasmal</h3>
                    <div class="role-l2 cyan">Chief Technology Officer & Lead Architect</div>
                    <span class="dept-l2 cyan">Engineering & Systems</span>
                  </div>

                  <!-- SUB-BRANCH TO LEVEL 3 (2 SPECIALISTS UNDER SAIKAT) -->
                  <ul>
                    <li>
                      <div class="tree-node-box node-l3">
                        <div class="avatar-l3">DC</div>
                        <h4 class="name-l3">David Chen</h4>
                        <div class="role-l3">Emergency SOS Dispatch</div>
                        <span class="tag-l3">Roadside SOS</span>
                      </div>
                    </li>
                    <li>
                      <div class="tree-node-box node-l3">
                        <div class="avatar-l3">SL</div>
                        <h4 class="name-l3">Sophia Lin</h4>
                        <div class="role-l3">Cybersecurity Lead</div>
                        <span class="tag-l3">AppSec & Privacy</span>
                      </div>
                    </li>
                  </ul>
                </li>

                <!-- LEVEL 2 - MEMBER 2: ELENA ROSTOVA (OPERATIONS) -->
                <li>
                  <div class="tree-node-box node-l2 purple">
                    <div class="avatar-l2 purple">ER</div>
                    <h3 class="name-l2">Elena Rostova</h3>
                    <div class="role-l2 purple">Chief Operating Officer (COO)</div>
                    <span class="dept-l2 purple">Operations & Service Grid</span>
                  </div>

                  <!-- SUB-BRANCH TO LEVEL 3 (3 SPECIALISTS UNDER ELENA) -->
                  <ul>
                    <li>
                      <div class="tree-node-box node-l3">
                        <div class="avatar-l3">PS</div>
                        <h4 class="name-l3">Priya Sharma</h4>
                        <div class="role-l3">Customer Concierge</div>
                        <span class="tag-l3">Concierge Desk</span>
                      </div>
                    </li>
                    <li>
                      <div class="tree-node-box node-l3">
                        <div class="avatar-l3">RV</div>
                        <h4 class="name-l3">Rahul Verma</h4>
                        <div class="role-l3">Partner KYC Lead</div>
                        <span class="tag-l3">Vetting & Compliance</span>
                      </div>
                    </li>
                    <li>
                      <div class="tree-node-box node-l3">
                        <div class="avatar-l3">AD</div>
                        <h4 class="name-l3">Ananya Das</h4>
                        <div class="role-l3">QA & Field Auditor</div>
                        <span class="tag-l3">Quality Standards</span>
                      </div>
                    </li>
                  </ul>
                </li>

                <!-- LEVEL 2 - MEMBER 3: MARCUS STERLING (PRODUCT & AUTOMOTIVE) -->
                <li>
                  <div class="tree-node-box node-l2 amber">
                    <div class="avatar-l2 amber">MS</div>
                    <h3 class="name-l2">Marcus Sterling</h3>
                    <div class="role-l2 amber">Chief Product Officer (CPO)</div>
                    <span class="dept-l2 amber">Automotive & Product Standards</span>
                  </div>

                  <!-- SUB-BRANCH TO LEVEL 3 (2 SPECIALISTS UNDER MARCUS) -->
                  <ul>
                    <li>
                      <div class="tree-node-box node-l3">
                        <div class="avatar-l3">VM</div>
                        <h4 class="name-l3">Vikram Malhotra</h4>
                        <div class="role-l3">Fleet Logistics Manager</div>
                        <span class="tag-l3">Fleet & Cargo</span>
                      </div>
                    </li>
                    <li>
                      <div class="tree-node-box node-l3">
                        <div class="avatar-l3">AR</div>
                        <h4 class="name-l3">Amit Roy</h4>
                        <div class="role-l3">Merchant Settlements</div>
                        <span class="tag-l3">Billing & Payouts</span>
                      </div>
                    </li>
                  </ul>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>

      <!-- ===============================================================
           2. DETAILED TIER LIST VIEW (ALTERNATE DISPLAY)
           =============================================================== -->
      <div class="org-list-view" id="view-list-container">
        <!-- Tier 1 -->
        <div class="list-tier-block">
          <div class="list-tier-title">
            <ion-icon name="star"></ion-icon> Level 1: Executive Top Leadership (1 Member)
          </div>
          <div class="list-member-card" style="border-left: 4px solid #00f2fe;">
            <div class="avatar-l1" style="width: 50px; height: 50px; font-size: 1.2rem; margin: 0;">SS</div>
            <div>
              <h3 style="color: #fff; font-size: 1.2rem; margin-bottom: 2px;">Sharthak Sen</h3>
              <p style="color: #00f2fe; font-size: 0.85rem; font-weight: 700; margin: 0;">Founder & Chief Executive Officer (Super Admin)</p>
              <p style="color: #94a3b8; font-size: 0.82rem; margin-top: 4px;">Top platform authority steering strategy, governance, and business expansion.</p>
            </div>
          </div>
        </div>

        <!-- Tier 2 -->
        <div class="list-tier-block">
          <div class="list-tier-title">
            <ion-icon name="layers"></ion-icon> Level 2: Directorate & Department Heads (3 Members)
          </div>
          <div class="list-members-grid">
            <div class="list-member-card" style="border-left: 4px solid #00f2fe;">
              <div class="avatar-l2 cyan" style="width: 44px; height: 44px; font-size: 1.1rem; margin: 0;">SS</div>
              <div>
                <h4 style="color: #fff; font-size: 1.05rem; margin-bottom: 2px;">Saikat Sasmal</h4>
                <p style="color: #00f2fe; font-size: 0.8rem; font-weight: 600; margin: 0;">Chief Technology Officer</p>
                <span class="dept-l2 cyan" style="margin-top: 4px;">Engineering</span>
              </div>
            </div>
            <div class="list-member-card" style="border-left: 4px solid #c084fc;">
              <div class="avatar-l2 purple" style="width: 44px; height: 44px; font-size: 1.1rem; margin: 0;">ER</div>
              <div>
                <h4 style="color: #fff; font-size: 1.05rem; margin-bottom: 2px;">Elena Rostova</h4>
                <p style="color: #c084fc; font-size: 0.8rem; font-weight: 600; margin: 0;">Chief Operating Officer</p>
                <span class="dept-l2 purple" style="margin-top: 4px;">Operations</span>
              </div>
            </div>
            <div class="list-member-card" style="border-left: 4px solid #fbbf24;">
              <div class="avatar-l2 amber" style="width: 44px; height: 44px; font-size: 1.1rem; margin: 0;">MS</div>
              <div>
                <h4 style="color: #fff; font-size: 1.05rem; margin-bottom: 2px;">Marcus Sterling</h4>
                <p style="color: #fbbf24; font-size: 0.8rem; font-weight: 600; margin: 0;">Chief Product Officer</p>
                <span class="dept-l2 amber" style="margin-top: 4px;">Product</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tier 3 -->
        <div class="list-tier-block">
          <div class="list-tier-title">
            <ion-icon name="people"></ion-icon> Level 3: Functional Specialist Leads (7 Members)
          </div>
          <div class="list-members-grid">
            <div class="list-member-card">
              <div class="avatar-l3" style="margin: 0;">DC</div>
              <div>
                <h5 style="color: #fff; font-size: 0.95rem; margin-bottom: 2px;">David Chen</h5>
                <p style="color: #38bdf8; font-size: 0.75rem; margin: 0;">Emergency SOS Dispatch</p>
              </div>
            </div>
            <div class="list-member-card">
              <div class="avatar-l3" style="margin: 0;">PS</div>
              <div>
                <h5 style="color: #fff; font-size: 0.95rem; margin-bottom: 2px;">Priya Sharma</h5>
                <p style="color: #38bdf8; font-size: 0.75rem; margin: 0;">Customer Concierge</p>
              </div>
            </div>
            <div class="list-member-card">
              <div class="avatar-l3" style="margin: 0;">RV</div>
              <div>
                <h5 style="color: #fff; font-size: 0.95rem; margin-bottom: 2px;">Rahul Verma</h5>
                <p style="color: #38bdf8; font-size: 0.75rem; margin: 0;">Partner KYC & Vetting</p>
              </div>
            </div>
            <div class="list-member-card">
              <div class="avatar-l3" style="margin: 0;">AD</div>
              <div>
                <h5 style="color: #fff; font-size: 0.95rem; margin-bottom: 2px;">Ananya Das</h5>
                <p style="color: #38bdf8; font-size: 0.75rem; margin: 0;">QA & Field Safety Auditor</p>
              </div>
            </div>
            <div class="list-member-card">
              <div class="avatar-l3" style="margin: 0;">VM</div>
              <div>
                <h5 style="color: #fff; font-size: 0.95rem; margin-bottom: 2px;">Vikram Malhotra</h5>
                <p style="color: #38bdf8; font-size: 0.75rem; margin: 0;">Fleet Logistics Manager</p>
              </div>
            </div>
            <div class="list-member-card">
              <div class="avatar-l3" style="margin: 0;">SL</div>
              <div>
                <h5 style="color: #fff; font-size: 0.95rem; margin-bottom: 2px;">Sophia Lin</h5>
                <p style="color: #38bdf8; font-size: 0.75rem; margin: 0;">Cybersecurity & Privacy</p>
              </div>
            </div>
            <div class="list-member-card">
              <div class="avatar-l3" style="margin: 0;">AR</div>
              <div>
                <h5 style="color: #fff; font-size: 0.95rem; margin-bottom: 2px;">Amit Roy</h5>
                <p style="color: #38bdf8; font-size: 0.75rem; margin: 0;">Merchant Settlements</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Metrics Banner -->
      <div class="stats-banner-glass">
        <div>
          <div class="stat-number">1</div>
          <div class="stat-label">Executive Leadership</div>
        </div>
        <div>
          <div class="stat-number">3</div>
          <div class="stat-label">Directorate Pillars</div>
        </div>
        <div>
          <div class="stat-number">7</div>
          <div class="stat-label">Functional Specialist Units</div>
        </div>
        <div>
          <div class="stat-number">100%</div>
          <div class="stat-label">Verified Technicians</div>
        </div>
      </div>

      <!-- Core Mission Pillars -->
      <div class="about-grid-3">
        <div class="about-card">
          <div class="about-card-icon">
            <ion-icon name="ribbon-outline"></ion-icon>
          </div>
          <h3 style="color: #fff; font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Verified Specialists</h3>
          <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.6;">
            Every service professional undergoes multi-tier identity checks, criminal history vetting, and skill trade testing before onboarding.
          </p>
        </div>

        <div class="about-card">
          <div class="about-card-icon emerald">
            <ion-icon name="cash-outline"></ion-icon>
          </div>
          <h3 style="color: #fff; font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Transparent Upfront Pricing</h3>
          <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.6;">
            Zero hidden charges. Itemized quotes, add-ons, and convenience fees are stated upfront with secure digital gateway or cash settlement.
          </p>
        </div>

        <div class="about-card">
          <div class="about-card-icon purple">
            <ion-icon name="navigate-outline"></ion-icon>
          </div>
          <h3 style="color: #fff; font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Live Telemetry & Tracking</h3>
          <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.6;">
            Stay informed with scheduled appointment windows, real-time technician dispatch updates, and verified digital receipts.
          </p>
        </div>
      </div>

      <!-- Call to Action Banner -->
      <section class="cta-banner-glass">
        <h2 style="color: #fff; font-size: 2.2rem; font-weight: 800; margin-bottom: 15px;">Experience ServEasy Excellence Today</h2>
        <p style="color: #94a3b8; font-size: 1.1rem; max-width: 650px; margin: 0 auto 30px; line-height: 1.6;">
          Join thousands of satisfied households and drivers who rely on ServEasy for verified doorstep vehicle maintenance and home services.
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

  <!-- Interactive Tree / List View Switcher Script -->
  <script>
    function switchView(mode) {
      const treeBtn = document.getElementById('btn-show-tree');
      const listBtn = document.getElementById('btn-show-list');
      const treeContainer = document.getElementById('view-tree-container');
      const listContainer = document.getElementById('view-list-container');

      if (mode === 'tree') {
        treeBtn.classList.add('active');
        listBtn.classList.remove('active');
        treeContainer.style.display = 'block';
        listContainer.style.display = 'none';
      } else {
        listBtn.classList.add('active');
        treeBtn.classList.remove('active');
        treeContainer.style.display = 'none';
        listContainer.style.display = 'block';
      }
    }
  </script>

  <!-- Ionicons -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
