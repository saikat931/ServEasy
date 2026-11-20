<?php
/**
 * ServEasy Customer Web Portal - About Us & Leadership Team Hierarchy
 * Comprehensive Team Organization Site Map
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
  <title>About ServEasy — Leadership Team & Organization Hierarchy</title>
  <meta name="description" content="Explore the ServEasy leadership team tree and organizational site map led by Sharthak Sen, executive directorate members, and 7 functional department specialists.">
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
      margin: 0 auto 20px;
    }
    .about-hero-title span {
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .about-hero-desc {
      font-size: 1.15rem;
      color: #94a3b8;
      max-width: 800px;
      margin: 0 auto 35px;
      line-height: 1.7;
    }

    /* ── Filter Pills ───────────────────────────────────────────────── */
    .org-filter-bar {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 45px;
    }
    .org-filter-btn {
      padding: 10px 22px;
      border-radius: 50px;
      background: rgba(30, 41, 59, 0.7);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #94a3b8;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.25s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .org-filter-btn:hover,
    .org-filter-btn.active {
      background: linear-gradient(135deg, rgba(0, 242, 254, 0.2), rgba(79, 172, 254, 0.2));
      border-color: #00f2fe;
      color: #00f2fe;
      box-shadow: 0 4px 15px rgba(0, 242, 254, 0.2);
    }

    /* ── Organization Tree Wrapper & Connectors ─────────────────────── */
    .org-tree-container {
      position: relative;
      margin-bottom: 80px;
      padding: 20px 10px;
    }

    .org-tree-level {
      position: relative;
      margin-bottom: 60px;
    }

    .level-heading-tag {
      text-align: center;
      margin-bottom: 25px;
    }
    .level-heading-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 1px;
      text-transform: uppercase;
      padding: 5px 14px;
      border-radius: 20px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: #e2e8f0;
    }

    /* Tree Branch Connectors */
    .tree-connector-down {
      width: 2px;
      height: 40px;
      background: linear-gradient(180deg, #00f2fe, #38bdf8);
      margin: 0 auto;
      position: relative;
    }
    .tree-connector-down::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: -3px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #00f2fe;
      box-shadow: 0 0 10px #00f2fe;
    }

    .tree-connector-horizontal {
      height: 2px;
      max-width: 780px;
      margin: 0 auto 35px;
      background: linear-gradient(90deg, transparent 0%, #00f2fe 15%, #38bdf8 50%, #00f2fe 85%, transparent 100%);
      position: relative;
    }

    /* ── Level 1 Card (CEO / Founder) ───────────────────────────────── */
    .org-l1-wrap {
      display: flex;
      justify-content: center;
    }
    .org-card-l1 {
      background: linear-gradient(145deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.85));
      border: 2px solid rgba(0, 242, 254, 0.4);
      border-radius: 24px;
      padding: 40px 35px;
      max-width: 620px;
      width: 100%;
      text-align: center;
      position: relative;
      box-shadow: 0 20px 45px rgba(0, 242, 254, 0.15), inset 0 0 25px rgba(0, 242, 254, 0.05);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .org-card-l1:hover {
      transform: translateY(-6px);
      border-color: #00f2fe;
      box-shadow: 0 25px 60px rgba(0, 242, 254, 0.3);
    }
    .org-avatar-l1 {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 50%, #6366f1 100%);
      color: #0b132b;
      font-size: 2.2rem;
      font-weight: 900;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 18px;
      border: 4px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 0 25px rgba(0, 242, 254, 0.4);
    }
    .org-name-l1 {
      font-size: 1.85rem;
      font-weight: 800;
      color: #ffffff;
      margin-bottom: 6px;
      letter-spacing: -0.5px;
    }
    .org-role-l1 {
      font-size: 1.05rem;
      font-weight: 700;
      color: #00f2fe;
      margin-bottom: 12px;
    }
    .org-bio-l1 {
      color: #94a3b8;
      font-size: 0.96rem;
      line-height: 1.65;
      margin-bottom: 18px;
    }
    .org-badge-executive {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(245, 158, 11, 0.15);
      border: 1px solid rgba(245, 158, 11, 0.4);
      color: #fbbf24;
      padding: 6px 16px;
      border-radius: 50px;
      font-size: 0.82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* ── Level 2 Grid (3 Directorate Members) ───────────────────────── */
    .org-grid-l2 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 28px;
      max-width: 1140px;
      margin: 0 auto;
    }
    .org-card-l2 {
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 30px 25px;
      text-align: center;
      position: relative;
      transition: all 0.3s ease;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    }
    .org-card-l2:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    }
    .org-card-l2.card-cyan { border-top: 4px solid #00f2fe; }
    .org-card-l2.card-purple { border-top: 4px solid #c084fc; }
    .org-card-l2.card-amber { border-top: 4px solid #f59e0b; }

    .org-avatar-l2 {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 1.6rem;
      font-weight: 800;
      border: 2px solid rgba(255, 255, 255, 0.15);
    }
    .org-avatar-l2.bg-cyan { background: linear-gradient(135deg, rgba(0,242,254,0.2), rgba(56,189,248,0.4)); color: #00f2fe; }
    .org-avatar-l2.bg-purple { background: linear-gradient(135deg, rgba(168,85,247,0.2), rgba(192,132,252,0.4)); color: #c084fc; }
    .org-avatar-l2.bg-amber { background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(251,191,36,0.4)); color: #fbbf24; }

    .org-name-l2 {
      font-size: 1.4rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 4px;
    }
    .org-role-l2 {
      font-size: 0.95rem;
      font-weight: 600;
      margin-bottom: 12px;
    }
    .org-desc-l2 {
      color: #94a3b8;
      font-size: 0.88rem;
      line-height: 1.6;
      margin-bottom: 16px;
    }
    .dept-pill {
      display: inline-block;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .dept-pill.cyan { background: rgba(0, 242, 254, 0.1); color: #00f2fe; border: 1px solid rgba(0, 242, 254, 0.3); }
    .dept-pill.purple { background: rgba(168, 85, 247, 0.1); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
    .dept-pill.amber { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }

    /* ── Level 3 Grid (7 Functional Specialists) ────────────────────── */
    .org-grid-l3 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
      max-width: 1240px;
      margin: 0 auto;
    }
    .org-card-l3 {
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 24px 20px;
      text-align: center;
      transition: all 0.25s ease;
      position: relative;
    }
    .org-card-l3:hover {
      transform: translateY(-4px);
      border-color: rgba(0, 242, 254, 0.35);
      background: rgba(30, 41, 59, 0.75);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }
    .org-avatar-l3 {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: #38bdf8;
      font-size: 1.25rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 12px;
    }
    .org-name-l3 {
      font-size: 1.15rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 4px;
    }
    .org-role-l3 {
      font-size: 0.85rem;
      color: #38bdf8;
      font-weight: 600;
      margin-bottom: 8px;
    }
    .org-desc-l3 {
      font-size: 0.82rem;
      color: #94a3b8;
      line-height: 1.5;
      margin-bottom: 12px;
    }
    .tag-l3 {
      display: inline-block;
      font-size: 0.72rem;
      font-weight: 600;
      padding: 3px 10px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.05);
      color: #cbd5e1;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ── Metrics Counter Banner ─────────────────────────────────────── */
    .stats-banner-glass {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 35px 25px;
      margin: 60px 0;
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

    /* ── Mission & Pillars ──────────────────────────────────────────── */
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

    /* ── CTA Banner ─────────────────────────────────────────────────── */
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
          <a href="../index.php" style="color: #94a3b8; text-decoration: none;">Home</a> / <span style="color: #00f2fe;">About ServEasy & Team Site Map</span>
        </span>
      </div>

      <!-- Hero Section -->
      <section class="about-hero-section">
        <div class="about-badge">
          <ion-icon name="git-network-outline"></ion-icon>
          <span>ServEasy Organization Site Map</span>
        </div>
        <h1 class="about-hero-title">
          The Leadership & Specialists Behind <span>ServEasy</span>
        </h1>
        <p class="about-hero-desc">
          ServEasy is powered by a tiered team of engineering architects, operational directors, and field specialists dedicated to transforming doorstep on-demand vehicle care, residential cleaning, and emergency roadside assistance.
        </p>

        <!-- Quick Filter Pills -->
        <div class="org-filter-bar">
          <button type="button" class="org-filter-btn active" onclick="filterOrg('all', this)">
            <ion-icon name="apps-outline"></ion-icon> All Tiers (11 Members)
          </button>
          <button type="button" class="org-filter-btn" onclick="filterOrg('l1', this)">
            <ion-icon name="star-outline"></ion-icon> Level 1: Executive Leader
          </button>
          <button type="button" class="org-filter-btn" onclick="filterOrg('l2', this)">
            <ion-icon name="layers-outline"></ion-icon> Level 2: Directorate (3 Members)
          </button>
          <button type="button" class="org-filter-btn" onclick="filterOrg('l3', this)">
            <ion-icon name="people-outline"></ion-icon> Level 3: Functional Leads (7 Members)
          </button>
        </div>
      </section>

      <!-- ===============================================================
           ORGANIZATION SITE MAP & TEAM HIERARCHY TREE
           =============================================================== -->
      <section class="org-tree-container">

        <!-- LEVEL 1: TOP EXECUTIVE (Sharthak Sen) -->
        <div class="org-tree-level tier-box" id="tier-l1">
          <div class="level-heading-tag">
            <span class="level-heading-badge">
              <ion-icon name="shield-checkmark" style="color: #fbbf24;"></ion-icon>
              Level 1 &bull; Executive Leadership
            </span>
          </div>

          <div class="org-l1-wrap">
            <div class="org-card-l1">
              <div class="org-avatar-l1">SS</div>
              <h2 class="org-name-l1">Sharthak Sen</h2>
              <div class="org-role-l1">Founder & Chief Executive Officer (Super Admin)</div>
              <p class="org-bio-l1">
                Directs the overarching strategic vision, technology roadmap, governance, and business expansion of the ServEasy on-demand multi-service ecosystem across regional hubs.
              </p>
              <span class="org-badge-executive">
                <ion-icon name="ribbon-outline"></ion-icon>
                Executive Directorate & Bull; Top Authority
              </span>
            </div>
          </div>

          <!-- Tree Vertical Line from Level 1 down to Level 2 -->
          <div class="tree-connector-down" style="margin-top: 25px;"></div>
        </div>

        <!-- LEVEL 2: 3 CORE DEPARTMENT HEADS / DIRECTORS -->
        <div class="org-tree-level tier-box" id="tier-l2">
          <!-- Horizontal Crossbar linking Level 2 -->
          <div class="tree-connector-horizontal"></div>

          <div class="level-heading-tag">
            <span class="level-heading-badge">
              <ion-icon name="git-branch-outline" style="color: #00f2fe;"></ion-icon>
              Level 2 &bull; Directorate & Department Heads (3 Members)
            </span>
          </div>

          <div class="org-grid-l2">
            <!-- Level 2 - Member 1 -->
            <div class="org-card-l2 card-cyan">
              <div class="org-avatar-l2 bg-cyan">SS</div>
              <h3 class="org-name-l2">Saikat Sasmal</h3>
              <div class="org-role-l2" style="color: #00f2fe;">Chief Technology Officer & Lead Architect</div>
              <p class="org-desc-l2">
                Leads multi-portal infrastructure, database integrity, real-time dispatch APIs, and technical platform reliability.
              </p>
              <span class="dept-pill cyan">Engineering & Systems</span>
            </div>

            <!-- Level 2 - Member 2 -->
            <div class="org-card-l2 card-purple">
              <div class="org-avatar-l2 bg-purple">ER</div>
              <h3 class="org-name-l2">Elena Rostova</h3>
              <div class="org-role-l2" style="color: #c084fc;">Chief Operating Officer (COO)</div>
              <p class="org-desc-l2">
                Manages daily field fulfillment, partner network performance benchmarks, customer concierge, and service quality.
              </p>
              <span class="dept-pill purple">Operations & Service Grid</span>
            </div>

            <!-- Level 2 - Member 3 -->
            <div class="org-card-l2 card-amber">
              <div class="org-avatar-l2 bg-amber">MS</div>
              <h3 class="org-name-l2">Marcus Sterling</h3>
              <div class="org-role-l2" style="color: #fbbf24;">Chief Product Officer (CPO)</div>
              <p class="org-desc-l2">
                Directs automotive technical standards, mechanical inspection protocols, and product innovation roadmaps.
              </p>
              <span class="dept-pill amber">Product & Technical Standards</span>
            </div>
          </div>

          <!-- Tree Vertical Line from Level 2 down to Level 3 -->
          <div class="tree-connector-down" style="margin-top: 35px;"></div>
        </div>

        <!-- LEVEL 3: 7 FUNCTIONAL SPECIALISTS & TEAM LEADS -->
        <div class="org-tree-level tier-box" id="tier-l3">
          <!-- Horizontal Crossbar linking Level 3 -->
          <div class="tree-connector-horizontal" style="max-width: 980px;"></div>

          <div class="level-heading-tag">
            <span class="level-heading-badge">
              <ion-icon name="people" style="color: #38bdf8;"></ion-icon>
              Level 3 &bull; Operations & Functional Specialist Leads (7 Members)
            </span>
          </div>

          <div class="org-grid-l3">
            <!-- 1. David Chen -->
            <div class="org-card-l3">
              <div class="org-avatar-l3">DC</div>
              <h4 class="org-name-l3">David Chen</h4>
              <div class="org-role-l3">Emergency SOS Dispatch Lead</div>
              <p class="org-desc-l3">Coordinates rapid vehicle breakdown dispatch and 24/7 highway roadside towing.</p>
              <span class="tag-l3">Roadside SOS & Towing</span>
            </div>

            <!-- 2. Priya Sharma -->
            <div class="org-card-l3">
              <div class="org-avatar-l3">PS</div>
              <h4 class="org-name-l3">Priya Sharma</h4>
              <div class="org-role-l3">Customer Concierge Lead</div>
              <p class="org-desc-l3">Directs multi-channel customer inquiries, booking reschedules, and feedback satisfaction.</p>
              <span class="tag-l3">Customer Success</span>
            </div>

            <!-- 3. Rahul Verma -->
            <div class="org-card-l3">
              <div class="org-avatar-l3">RV</div>
              <h4 class="org-name-l3">Rahul Verma</h4>
              <div class="org-role-l3">Provider Onboarding & KYC Lead</div>
              <p class="org-desc-l3">Conducts government identity verification, background vetting, and trade certification.</p>
              <span class="tag-l3">Partner KYC & Vetting</span>
            </div>

            <!-- 4. Ananya Das -->
            <div class="org-card-l3">
              <div class="org-avatar-l3">AD</div>
              <h4 class="org-name-l3">Ananya Das</h4>
              <div class="org-role-l3">Quality Assurance & Safety Auditor</div>
              <p class="org-desc-l3">Conducts surprise site visits, tool compliance audits, and home sanitization standards.</p>
              <span class="tag-l3">Quality & Safety Audit</span>
            </div>

            <!-- 5. Vikram Malhotra -->
            <div class="org-card-l3">
              <div class="org-avatar-l3">VM</div>
              <h4 class="org-name-l3">Vikram Malhotra</h4>
              <div class="org-role-l3">Fleet Logistics & Transport Manager</div>
              <p class="org-desc-l3">Oversees rental vehicle fleet, cargo carriers, and citywide vehicle relocation logistics.</p>
              <span class="tag-l3">Fleet & Cargo Logistics</span>
            </div>

            <!-- 6. Sophia Lin -->
            <div class="org-card-l3">
              <div class="org-avatar-l3">SL</div>
              <h4 class="org-name-l3">Sophia Lin</h4>
              <div class="org-role-l3">Cybersecurity & Data Privacy Lead</div>
              <p class="org-desc-l3">Enforces OWASP application security, credential hashing, and client data protection.</p>
              <span class="tag-l3">AppSec & Privacy</span>
            </div>

            <!-- 7. Amit Roy -->
            <div class="org-card-l3">
              <div class="org-avatar-l3">AR</div>
              <h4 class="org-name-l3">Amit Roy</h4>
              <div class="org-role-l3">Merchant Billing & Settlements Lead</div>
              <p class="org-desc-l3">Oversees payment gateway reconciliations, provider payouts, and verified refund audits.</p>
              <span class="tag-l3">Payouts & Settlement</span>
            </div>
          </div>
        </div>

      </section>

      <!-- Organization Metrics Banner -->
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

  <!-- Interactive Hierarchy Filter Script -->
  <script>
    function filterOrg(tier, btn) {
      document.querySelectorAll('.org-filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const l1 = document.getElementById('tier-l1');
      const l2 = document.getElementById('tier-l2');
      const l3 = document.getElementById('tier-l3');

      if (tier === 'all') {
        l1.style.display = 'block';
        l2.style.display = 'block';
        l3.style.display = 'block';
      } else if (tier === 'l1') {
        l1.style.display = 'block';
        l2.style.display = 'none';
        l3.style.display = 'none';
      } else if (tier === 'l2') {
        l1.style.display = 'none';
        l2.style.display = 'block';
        l3.style.display = 'none';
      } else if (tier === 'l3') {
        l1.style.display = 'none';
        l2.style.display = 'none';
        l3.style.display = 'block';
      }
    }
  </script>

  <!-- Ionicons -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
