<?php
/**
 * ServEasy Customer Web Portal - About Us & Professional Team Tree
 * Clean, compact visual organogram: Professional image icon & name in tree;
 * Full biography, reporting hierarchy, and skills displayed in rich popup modal on click.
 */
require_once __DIR__ . '/../common/auth_session.php';
if (!defined('SERVEASY_ASSET_VER')) {
    define('SERVEASY_ASSET_VER', '20261120v4');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About ServEasy — Leadership Team & Professional Organization Tree</title>
  <meta name="description" content="Explore the ServEasy leadership tree: Sharthak Sen (Founder & CEO), 3 executive directors, and 7 functional command leads with clickable profiles and department insights.">
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
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Nunito:wght@400;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

  <style>
    /* ── Ambient Glow Stage ──────────────────────────────────────────── */
    .tree-stage {
      position: relative;
      background: radial-gradient(ellipse at 50% 12%, rgba(0, 242, 254, 0.12) 0%, rgba(15, 23, 42, 0) 65%),
                  radial-gradient(ellipse at 85% 60%, rgba(168, 85, 247, 0.08) 0%, rgba(15, 23, 42, 0) 60%),
                  radial-gradient(ellipse at 15% 75%, rgba(245, 158, 11, 0.08) 0%, rgba(15, 23, 42, 0) 60%);
      padding: 40px 10px 70px;
      border-radius: 32px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.8);
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      margin-bottom: 70px;
    }

    /* ── Hero Section ────────────────────────────────────────────────── */
    .about-hero-section {
      padding: 50px 20px 30px;
      text-align: center;
      position: relative;
    }
    .hero-badge-pill {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 8px 22px;
      border-radius: 50px;
      background: linear-gradient(135deg, rgba(0, 242, 254, 0.12), rgba(79, 172, 254, 0.12));
      border: 1px solid rgba(0, 242, 254, 0.4);
      color: #00f2fe;
      font-size: 0.85rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      margin-bottom: 20px;
      box-shadow: 0 0 25px rgba(0, 242, 254, 0.25);
    }
    .pulse-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #00f2fe;
      box-shadow: 0 0 10px #00f2fe;
      animation: pulseAnim 2s infinite;
    }
    @keyframes pulseAnim {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.6); opacity: 0.5; }
    }

    .about-hero-title {
      font-size: 3.2rem;
      font-weight: 900;
      color: #ffffff;
      line-height: 1.15;
      max-width: 950px;
      margin: 0 auto 18px;
      letter-spacing: -1px;
    }
    .about-hero-title .gradient-text {
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 50%, #c084fc 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      filter: drop-shadow(0 2px 20px rgba(0, 242, 254, 0.3));
    }
    .about-hero-desc {
      font-size: 1.15rem;
      color: #94a3b8;
      max-width: 820px;
      margin: 0 auto 30px;
      line-height: 1.7;
    }
    .tree-interactive-tip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(0, 242, 254, 0.08);
      border: 1px dashed rgba(0, 242, 254, 0.3);
      padding: 7px 18px;
      border-radius: 50px;
      color: #38bdf8;
      font-size: 0.88rem;
      font-weight: 600;
      margin-bottom: 25px;
    }
    .scroll-hint-pill {
      display: none;
      text-align: center;
      color: #64748b;
      font-size: 0.82rem;
      margin-bottom: 20px;
      font-family: 'JetBrains Mono', monospace;
    }
    @media (max-width: 1100px) {
      .scroll-hint-pill { display: block; }
    }

    /* ── PURE CSS ORGANIZATIONAL TREE DIAGRAM ────────────────────────── */
    .org-tree {
      display: flex;
      justify-content: center;
      min-width: 1050px;
      padding: 20px 0;
    }
    .org-tree ul {
      padding-top: 36px;
      position: relative;
      transition: all 0.5s ease;
      display: flex;
      justify-content: center;
      margin: 0;
      padding-left: 0;
      list-style-type: none;
    }
    .org-tree li {
      float: left;
      text-align: center;
      list-style-type: none;
      position: relative;
      padding: 36px 14px 0 14px;
      transition: all 0.5s ease;
    }

    /* Tree Branch Connectors with Cyan Neon Glow */
    .org-tree li::before, .org-tree li::after {
      content: '';
      position: absolute;
      top: 0;
      right: 50%;
      border-top: 2px solid #00f2fe;
      width: 50%;
      height: 36px;
      filter: drop-shadow(0 0 6px rgba(0, 242, 254, 0.6));
    }
    .org-tree li::after {
      right: auto;
      left: 50%;
      border-left: 2px solid #00f2fe;
    }
    .org-tree li:only-child::after, .org-tree li:only-child::before {
      display: none;
    }
    .org-tree li:only-child {
      padding-top: 0;
    }
    .org-tree li:first-child::before, .org-tree li:last-child::after {
      border: 0 none;
    }
    .org-tree li:last-child::before {
      border-right: 2px solid #00f2fe;
      border-radius: 0 14px 0 0;
    }
    .org-tree li:first-child::after {
      border-radius: 14px 0 0 0;
    }
    .org-tree ul ul::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      border-left: 2px solid #00f2fe;
      width: 0;
      height: 36px;
      filter: drop-shadow(0 0 6px rgba(0, 242, 254, 0.6));
    }

    /* ── COMPACT PROFESSIONAL NODE CARD ─────────────────────────────── */
    .pro-node {
      display: inline-flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      position: relative;
      background: linear-gradient(145deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.9));
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 20px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }
    .pro-node:hover {
      transform: translateY(-6px) scale(1.04);
      z-index: 20;
    }

    /* Avatar Container with Photo & Online Beacon */
    .node-avatar-frame {
      position: relative;
      border-radius: 50%;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #1e293b;
      margin: 0 auto;
    }
    .node-avatar-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      border-radius: 50%;
    }
    .node-avatar-fallback {
      display: none;
      width: 100%;
      height: 100%;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      color: #fff;
      border-radius: 50%;
    }
    .node-status-dot {
      position: absolute;
      bottom: 2px;
      right: 2px;
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: #10b981;
      border: 2px solid #0f172a;
      box-shadow: 0 0 8px #10b981;
      z-index: 2;
    }

    /* Node Typography */
    .node-name {
      font-weight: 800;
      color: #ffffff;
      margin-top: 10px;
      margin-bottom: 4px;
      letter-spacing: -0.2px;
      white-space: nowrap;
    }
    .node-role-pill {
      font-size: 0.76rem;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 12px;
      white-space: nowrap;
      letter-spacing: 0.3px;
    }
    .node-click-hint {
      margin-top: 8px;
      font-size: 0.72rem;
      color: #64748b;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: color 0.2s;
    }
    .pro-node:hover .node-click-hint {
      color: #00f2fe;
    }

    /* ── TIER-SPECIFIC STYLING ───────────────────────────────────────── */
    /* Level 1: Sharthak Sen (Top Leader) */
    .pro-node-l1 {
      width: 250px;
      padding: 22px 18px 16px;
      border: 2px solid #00f2fe;
      box-shadow: 0 0 35px rgba(0, 242, 254, 0.3), inset 0 0 20px rgba(0, 242, 254, 0.08);
    }
    .pro-node-l1:hover {
      box-shadow: 0 0 45px rgba(0, 242, 254, 0.5);
      border-color: #38bdf8;
    }
    .pro-node-l1 .node-avatar-frame {
      width: 80px;
      height: 80px;
      border: 3px solid #00f2fe;
      box-shadow: 0 0 20px rgba(0, 242, 254, 0.4);
    }
    .pro-node-l1 .node-name {
      font-size: 1.35rem;
    }
    .pro-node-l1 .node-role-pill {
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(217, 119, 6, 0.2));
      border: 1px solid rgba(245, 158, 11, 0.5);
      color: #fbbf24;
    }

    /* Level 2: 3 Directorate Heads */
    .pro-node-l2 {
      width: 200px;
      padding: 18px 14px 14px;
    }
    .pro-node-l2 .node-avatar-frame {
      width: 62px;
      height: 62px;
    }
    .pro-node-l2.cyan {
      border-top: 3px solid #00f2fe;
      box-shadow: 0 10px 25px rgba(0, 242, 254, 0.15);
    }
    .pro-node-l2.cyan .node-avatar-frame { border: 2px solid #00f2fe; }
    .pro-node-l2.cyan .node-role-pill { background: rgba(0, 242, 254, 0.12); color: #00f2fe; border: 1px solid rgba(0, 242, 254, 0.3); }

    .pro-node-l2.purple {
      border-top: 3px solid #c084fc;
      box-shadow: 0 10px 25px rgba(168, 85, 247, 0.15);
    }
    .pro-node-l2.purple .node-avatar-frame { border: 2px solid #c084fc; }
    .pro-node-l2.purple .node-role-pill { background: rgba(168, 85, 247, 0.12); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }

    .pro-node-l2.amber {
      border-top: 3px solid #f59e0b;
      box-shadow: 0 10px 25px rgba(245, 158, 11, 0.15);
    }
    .pro-node-l2.amber .node-avatar-frame { border: 2px solid #f59e0b; }
    .pro-node-l2.amber .node-role-pill { background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }

    .pro-node-l2 .node-name {
      font-size: 1.15rem;
    }

    /* Level 3: 7 Functional Leads */
    .pro-node-l3 {
      width: 145px;
      padding: 14px 10px 12px;
      background: rgba(30, 41, 59, 0.7);
    }
    .pro-node-l3:hover {
      border-color: #00f2fe;
      background: rgba(30, 41, 59, 0.95);
      box-shadow: 0 12px 25px rgba(0, 242, 254, 0.25);
    }
    .pro-node-l3 .node-avatar-frame {
      width: 48px;
      height: 48px;
      border: 2px solid rgba(255, 255, 255, 0.15);
    }
    .pro-node-l3 .node-name {
      font-size: 0.92rem;
    }
    .pro-node-l3 .node-role-pill {
      font-size: 0.7rem;
      background: rgba(255, 255, 255, 0.06);
      color: #94a3b8;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ── POPUP DETAIL MODAL ─────────────────────────────────────────── */
    .member-modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(11, 19, 43, 0.85);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      z-index: 99999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .member-modal-box {
      background: linear-gradient(145deg, #0f172a, #1e293b);
      border: 1px solid #00f2fe;
      border-radius: 28px;
      max-width: 540px;
      width: 100%;
      padding: 40px 32px 35px;
      text-align: center;
      position: relative;
      box-shadow: 0 25px 70px rgba(0, 242, 254, 0.35);
      animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    @keyframes modalPop {
      0% { transform: scale(0.85); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }
    .btn-modal-close {
      position: absolute;
      top: 18px;
      right: 20px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      width: 36px;
      height: 36px;
      border-radius: 50%;
      color: #94a3b8;
      font-size: 1.4rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }
    .btn-modal-close:hover {
      background: #f43f5e;
      border-color: #f43f5e;
      color: #fff;
    }

    .modal-avatar-lg {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin: 0 auto 18px;
      position: relative;
      border: 3px solid #00f2fe;
      box-shadow: 0 0 25px rgba(0, 242, 254, 0.4);
      overflow: hidden;
    }
    .modal-avatar-lg img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .modal-name {
      font-size: 1.85rem;
      font-weight: 800;
      color: #ffffff;
      margin-bottom: 4px;
    }
    .modal-role {
      font-size: 1.05rem;
      font-weight: 700;
      color: #00f2fe;
      margin-bottom: 8px;
    }
    .modal-dept {
      display: inline-block;
      font-size: 0.8rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      padding: 4px 14px;
      border-radius: 50px;
      background: rgba(245, 158, 11, 0.12);
      border: 1px solid rgba(245, 158, 11, 0.4);
      color: #fbbf24;
      margin-bottom: 18px;
    }
    .modal-reports-to {
      font-size: 0.82rem;
      color: #64748b;
      margin-bottom: 15px;
      font-family: 'JetBrains Mono', monospace;
    }
    .modal-reports-to span {
      color: #38bdf8;
      font-weight: 700;
    }
    .modal-bio {
      color: #94a3b8;
      font-size: 0.95rem;
      line-height: 1.65;
      margin-bottom: 22px;
      text-align: left;
      background: rgba(15, 23, 42, 0.6);
      padding: 16px 20px;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, 0.06);
    }
    .modal-skills-box {
      display: flex;
      justify-content: flex-start;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 25px;
    }
    .modal-skill-pill {
      font-size: 0.75rem;
      font-family: 'JetBrains Mono', monospace;
      padding: 4px 12px;
      border-radius: 6px;
      background: rgba(0, 242, 254, 0.1);
      border: 1px solid rgba(0, 242, 254, 0.3);
      color: #00f2fe;
    }
    .modal-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
    }
    .modal-btn-email {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #00f2fe, #4facfe);
      color: #0f172a;
      font-weight: 700;
      padding: 10px 22px;
      border-radius: 10px;
      text-decoration: none;
      font-size: 0.9rem;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .modal-btn-email:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 242, 254, 0.35);
    }

    /* ── Metrics Banner & Pillars ───────────────────────────────────── */
    .hud-metrics-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
      gap: 20px;
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 30px 25px;
      margin: 20px 0 65px;
      text-align: center;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    .hud-stat-num {
      font-size: 2.6rem;
      font-weight: 900;
      background: linear-gradient(135deg, #00f2fe, #4facfe);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      line-height: 1;
      margin-bottom: 8px;
    }
    .hud-stat-lbl {
      color: #94a3b8;
      font-size: 0.88rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    .about-grid-3 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 25px;
      margin-bottom: 60px;
    }
    .about-card {
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 22px;
      padding: 35px 30px;
      transition: all 0.3s ease;
      position: relative;
    }
    .about-card:hover {
      transform: translateY(-5px);
      border-color: rgba(0, 242, 254, 0.4);
      box-shadow: 0 15px 35px rgba(0, 242, 254, 0.15);
    }
    .about-card-icon {
      width: 58px;
      height: 58px;
      border-radius: 16px;
      background: rgba(0, 242, 254, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      color: #00f2fe;
      margin-bottom: 20px;
      border: 1px solid rgba(0, 242, 254, 0.25);
    }
    .about-card-icon.emerald { background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: rgba(16, 185, 129, 0.25); }
    .about-card-icon.purple { background: rgba(168, 85, 247, 0.1); color: #c084fc; border-color: rgba(168, 85, 247, 0.25); }

    .cta-banner-glass {
      background: linear-gradient(135deg, rgba(6, 182, 212, 0.18), rgba(99, 102, 241, 0.18));
      border: 1px solid rgba(0, 242, 254, 0.35);
      border-radius: 28px;
      padding: 55px 30px;
      text-align: center;
      margin: 60px 0 80px;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
    }
    .btn-about-primary {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 15px 34px;
      border-radius: 12px;
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
      color: #0f172a;
      font-weight: 800;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 1rem;
    }
    .btn-about-primary:hover {
      box-shadow: 0 8px 30px rgba(0, 242, 254, 0.45);
      transform: translateY(-2px);
    }
    .btn-about-secondary {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 15px 34px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.18);
      color: #fff;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.3s ease;
      margin-left: 15px;
      font-size: 1rem;
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
          <a href="../index.php" style="color: #94a3b8; text-decoration: none;">Home</a> / <span style="color: #00f2fe;">About ServEasy & Team Tree</span>
        </span>
      </div>

      <!-- Hero Header -->
      <section class="about-hero-section">
        <div class="hero-badge-pill">
          <span class="pulse-dot"></span>
          <span>Official Leadership Site Map</span>
        </div>
        <h1 class="about-hero-title">
          ServEasy <span>Team Organization Tree</span>
        </h1>
        <p class="about-hero-desc">
          Meet our tiered leadership architecture. Click any member's profile icon in the tree to open their detailed department responsibilities, credentials, and direct reachout desk.
        </p>

        <div class="tree-interactive-tip">
          <ion-icon name="finger-print-outline" style="font-size: 1.1rem;"></ion-icon>
          <span>Click on any leader or specialist card to view complete profile & bio</span>
        </div>

        <div class="scroll-hint-pill">
          <ion-icon name="swap-horizontal-outline"></ion-icon> Swipe horizontally to inspect reporting branches
        </div>
      </section>

      <!-- ===============================================================
           CLEAN, COMPACT PURE CSS ORGANIZATIONAL TREE
           (Professional Image Icon + Name, Details on Click Popup)
           =============================================================== -->
      <section class="tree-stage">
        <div class="org-tree">
          <ul>
            <!-- ROOT / LEVEL 1: SHARTHAK SEN -->
            <li>
              <div class="pro-node pro-node-l1" onclick="openMemberModal('sharthak')">
                <div class="node-avatar-frame">
                  <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80" alt="Sharthak Sen" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                  <div class="node-avatar-fallback" style="background: linear-gradient(135deg, #00f2fe, #4facfe);">SS</div>
                  <span class="node-status-dot"></span>
                </div>
                <div class="node-name">Sharthak Sen</div>
                <div class="node-role-pill">Founder & CEO</div>
                <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> View Profile</div>
              </div>

              <!-- BRANCH DOWN TO LEVEL 2: 3 DIRECTORATE HEADS -->
              <ul>
                <!-- LEVEL 2 &bull; 1. SAIKAT SASMAL (TECH) -->
                <li>
                  <div class="pro-node pro-node-l2 cyan" onclick="openMemberModal('saikat')">
                    <div class="node-avatar-frame">
                      <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&q=80" alt="Saikat Sasmal" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                      <div class="node-avatar-fallback" style="background: #00f2fe; color: #0b132b;">SS</div>
                      <span class="node-status-dot"></span>
                    </div>
                    <div class="node-name">Saikat Sasmal</div>
                    <div class="node-role-pill">Chief Technology Officer</div>
                    <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> Details</div>
                  </div>

                  <!-- SUB-BRANCH TO LEVEL 3 (2 SPECIALISTS UNDER SAIKAT) -->
                  <ul>
                    <li>
                      <div class="pro-node pro-node-l3" onclick="openMemberModal('david')">
                        <div class="node-avatar-frame">
                          <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=400&q=80" alt="David Chen" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                          <div class="node-avatar-fallback" style="background: #38bdf8; color: #0b132b;">DC</div>
                          <span class="node-status-dot"></span>
                        </div>
                        <div class="node-name">David Chen</div>
                        <div class="node-role-pill">Roadside SOS</div>
                        <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> Info</div>
                      </div>
                    </li>
                    <li>
                      <div class="pro-node pro-node-l3" onclick="openMemberModal('sophia')">
                        <div class="node-avatar-frame">
                          <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=400&q=80" alt="Sophia Lin" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                          <div class="node-avatar-fallback" style="background: #38bdf8; color: #0b132b;">SL</div>
                          <span class="node-status-dot"></span>
                        </div>
                        <div class="node-name">Sophia Lin</div>
                        <div class="node-role-pill">Cybersecurity</div>
                        <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> Info</div>
                      </div>
                    </li>
                  </ul>
                </li>

                <!-- LEVEL 2 &bull; 2. ELENA ROSTOVA (OPERATIONS) -->
                <li>
                  <div class="pro-node pro-node-l2 purple" onclick="openMemberModal('elena')">
                    <div class="node-avatar-frame">
                      <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=400&q=80" alt="Elena Rostova" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                      <div class="node-avatar-fallback" style="background: #c084fc; color: #0b132b;">ER</div>
                      <span class="node-status-dot"></span>
                    </div>
                    <div class="node-name">Elena Rostova</div>
                    <div class="node-role-pill">Chief Operating Officer</div>
                    <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> Details</div>
                  </div>

                  <!-- SUB-BRANCH TO LEVEL 3 (3 SPECIALISTS UNDER ELENA) -->
                  <ul>
                    <li>
                      <div class="pro-node pro-node-l3" onclick="openMemberModal('priya')">
                        <div class="node-avatar-frame">
                          <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80" alt="Priya Sharma" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                          <div class="node-avatar-fallback" style="background: #c084fc; color: #0b132b;">PS</div>
                          <span class="node-status-dot"></span>
                        </div>
                        <div class="node-name">Priya Sharma</div>
                        <div class="node-role-pill">Concierge</div>
                        <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> Info</div>
                      </div>
                    </li>
                    <li>
                      <div class="pro-node pro-node-l3" onclick="openMemberModal('rahul')">
                        <div class="node-avatar-frame">
                          <img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=400&q=80" alt="Rahul Verma" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                          <div class="node-avatar-fallback" style="background: #c084fc; color: #0b132b;">RV</div>
                          <span class="node-status-dot"></span>
                        </div>
                        <div class="node-name">Rahul Verma</div>
                        <div class="node-role-pill">KYC Lead</div>
                        <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> Info</div>
                      </div>
                    </li>
                    <li>
                      <div class="pro-node pro-node-l3" onclick="openMemberModal('ananya')">
                        <div class="node-avatar-frame">
                          <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=400&q=80" alt="Ananya Das" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                          <div class="node-avatar-fallback" style="background: #c084fc; color: #0b132b;">AD</div>
                          <span class="node-status-dot"></span>
                        </div>
                        <div class="node-name">Ananya Das</div>
                        <div class="node-role-pill">QA Auditor</div>
                        <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> Info</div>
                      </div>
                    </li>
                  </ul>
                </li>

                <!-- LEVEL 2 &bull; 3. MARCUS STERLING (AUTOMOTIVE & FLEET) -->
                <li>
                  <div class="pro-node pro-node-l2 amber" onclick="openMemberModal('marcus')">
                    <div class="node-avatar-frame">
                      <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=400&q=80" alt="Marcus Sterling" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                      <div class="node-avatar-fallback" style="background: #fbbf24; color: #0b132b;">MS</div>
                      <span class="node-status-dot"></span>
                    </div>
                    <div class="node-name">Marcus Sterling</div>
                    <div class="node-role-pill">Chief Product Officer</div>
                    <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> Details</div>
                  </div>

                  <!-- SUB-BRANCH TO LEVEL 3 (2 SPECIALISTS UNDER MARCUS) -->
                  <ul>
                    <li>
                      <div class="pro-node pro-node-l3" onclick="openMemberModal('vikram')">
                        <div class="node-avatar-frame">
                          <img src="https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=400&q=80" alt="Vikram Malhotra" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                          <div class="node-avatar-fallback" style="background: #fbbf24; color: #0b132b;">VM</div>
                          <span class="node-status-dot"></span>
                        </div>
                        <div class="node-name">Vikram Malhotra</div>
                        <div class="node-role-pill">Fleet Logistics</div>
                        <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> Info</div>
                      </div>
                    </li>
                    <li>
                      <div class="pro-node pro-node-l3" onclick="openMemberModal('amit')">
                        <div class="node-avatar-frame">
                          <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=400&q=80" alt="Amit Roy" class="node-avatar-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                          <div class="node-avatar-fallback" style="background: #fbbf24; color: #0b132b;">AR</div>
                          <span class="node-status-dot"></span>
                        </div>
                        <div class="node-name">Amit Roy</div>
                        <div class="node-role-pill">Settlements</div>
                        <div class="node-click-hint"><ion-icon name="information-circle"></ion-icon> Info</div>
                      </div>
                    </li>
                  </ul>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </section>

      <!-- Organization Metrics HUD Bar -->
      <div class="hud-metrics-row">
        <div>
          <div class="hud-stat-num">1</div>
          <div class="hud-stat-lbl">Supreme Executive Vision</div>
        </div>
        <div>
          <div class="hud-stat-num">3</div>
          <div class="hud-stat-lbl">Directorate Pillars</div>
        </div>
        <div>
          <div class="hud-stat-num">7</div>
          <div class="hud-stat-lbl">Specialist Command Leads</div>
        </div>
        <div>
          <div class="hud-stat-num">100%</div>
          <div class="hud-stat-lbl">Verified Field Network</div>
        </div>
      </div>

      <!-- Core Mission Pillars -->
      <div class="about-grid-3">
        <div class="about-card">
          <div class="about-card-icon">
            <ion-icon name="ribbon-outline"></ion-icon>
          </div>
          <h3 style="color: #fff; font-size: 1.3rem; font-weight: 800; margin-bottom: 10px;">Verified Specialists</h3>
          <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.65;">
            Every single technician undergoes multi-tier criminal record checks, trade skill tests, and government KYC vetting before deployment.
          </p>
        </div>

        <div class="about-card">
          <div class="about-card-icon emerald">
            <ion-icon name="cash-outline"></ion-icon>
          </div>
          <h3 style="color: #fff; font-size: 1.3rem; font-weight: 800; margin-bottom: 10px;">Transparent Upfront Pricing</h3>
          <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.65;">
            No surprise price surges or hidden labor fees. Itemized invoices are locked in before technician dispatch with digital or cash settlement.
          </p>
        </div>

        <div class="about-card">
          <div class="about-card-icon purple">
            <ion-icon name="navigate-outline"></ion-icon>
          </div>
          <h3 style="color: #fff; font-size: 1.3rem; font-weight: 800; margin-bottom: 10px;">Live Telemetry & Tracking</h3>
          <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.65;">
            Real-time appointment slots, live technician GPS navigation tracking, and direct in-app emergency SOS dispatch coordinate support.
          </p>
        </div>
      </div>

      <!-- Call to Action Banner -->
      <section class="cta-banner-glass">
        <h2 style="color: #fff; font-size: 2.4rem; font-weight: 900; margin-bottom: 16px; letter-spacing: -0.5px;">Experience ServEasy Excellence Today</h2>
        <p style="color: #94a3b8; font-size: 1.15rem; max-width: 680px; margin: 0 auto 32px; line-height: 1.7;">
          Join thousands of satisfied drivers and households who trust ServEasy for high-precision vehicle maintenance and home services.
        </p>
        <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 16px;">
          <a href="./services.php" class="btn-about-primary">
            <span>Explore All Services</span>
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

  <!-- ===================================================================
       RICH POPUP MODAL FOR TEAM MEMBER DETAILS
       =================================================================== -->
  <div class="member-modal-backdrop" id="memberModal" onclick="closeMemberModal(event)">
    <div class="member-modal-box" onclick="event.stopPropagation()">
      <button type="button" class="btn-modal-close" onclick="closeMemberModal()" aria-label="Close Profile Details">&times;</button>
      
      <div class="modal-avatar-lg">
        <img id="modalImg" src="" alt="Leader Photo">
      </div>
      
      <h3 id="modalName" class="modal-name"></h3>
      <div id="modalRole" class="modal-role"></div>
      <div><span id="modalDept" class="modal-dept"></span></div>
      
      <div id="modalReportsTo" class="modal-reports-to"></div>
      
      <div id="modalBio" class="modal-bio"></div>
      
      <div style="text-align: left; margin-bottom: 8px;">
        <span style="font-size: 0.78rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Core Competencies & Responsibilities:</span>
      </div>
      <div id="modalSkills" class="modal-skills-box"></div>

      <div class="modal-actions">
        <a id="modalEmail" href="mailto:support@serveasy.in" class="modal-btn-email">
          <ion-icon name="mail-outline"></ion-icon>
          <span>Connect via Support Desk</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- Interactive Data & Modal Handler -->
  <script>
    const teamDatabase = {
      'sharthak': {
        name: 'Sharthak Sen',
        role: 'Founder & Chief Executive Officer (Super Admin)',
        dept: 'Executive Directorate & Supreme Governance',
        reportsTo: 'Directing Supreme Board',
        img: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80',
        bio: 'Sharthak Sen steers the overarching strategic vision, technology roadmap, capital investments, governance, and nationwide expansion of the ServEasy on-demand ecosystem. He leads the multi-portal architecture connecting customers, service providers, and administrators into one unified platform.',
        skills: ['Strategic Governance', 'Platform Architecture', 'Superadmin Control', 'Partner Network Scaling', 'Product Innovation'],
        email: 'mailto:support@serveasy.in?subject=Inquiry for CEO Office (Sharthak Sen)'
      },
      'saikat': {
        name: 'Saikat Sasmal',
        role: 'Chief Technology Officer & Lead Architect',
        dept: 'Engineering & Technology Command',
        reportsTo: 'Sharthak Sen (Founder & CEO)',
        img: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&q=80',
        bio: 'Saikat Sasmal directs core software engineering, multi-portal API architecture, database integrity, real-time GPS telemetry, and OWASP application security. He oversees continuous deployment reliability across our cloud hosting infrastructure.',
        skills: ['API Architecture', 'Database Security', 'High Availability', 'System Defense', 'Microservices'],
        email: 'mailto:support@serveasy.in?subject=Inquiry for CTO Desk (Saikat Sasmal)'
      },
      'elena': {
        name: 'Elena Rostova',
        role: 'Chief Operating Officer (COO)',
        dept: 'Field Operations & Service Grid',
        reportsTo: 'Sharthak Sen (Founder & CEO)',
        img: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=400&q=80',
        bio: 'Elena Rostova manages daily field fulfillment, service provider network compliance, quality benchmarks, and multi-channel customer concierge. She ensures every booking meets strict hygiene, punctuality, and satisfaction standards.',
        skills: ['Field Operations', 'SLA Enforcement', 'Partner Network', 'Service Quality', 'Dispute Shield'],
        email: 'mailto:support@serveasy.in?subject=Inquiry for COO Desk (Elena Rostova)'
      },
      'marcus': {
        name: 'Marcus Sterling',
        role: 'Chief Product Officer (CPO)',
        dept: 'Automotive & Product Standards',
        reportsTo: 'Sharthak Sen (Founder & CEO)',
        img: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=400&q=80',
        bio: 'Marcus Sterling directs automotive technical standards, mechanical diagnostic protocols, doorstep vehicle detailing specs, and rental fleet logistics. He ensures technicians deploy with certified commercial-grade tooling.',
        skills: ['Automotive Engineering', 'Fleet Standards', 'Mechanical R&D', 'Tooling Specs', 'Product UX'],
        email: 'mailto:support@serveasy.in?subject=Inquiry for CPO Desk (Marcus Sterling)'
      },
      'david': {
        name: 'David Chen',
        role: 'Emergency SOS Dispatch Lead',
        dept: 'Rapid Response Unit',
        reportsTo: 'Saikat Sasmal (CTO)',
        img: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=400&q=80',
        bio: 'Coordinates 24/7 live GPS roadside breakdown tracking, emergency tow truck dispatch, battery jumpstarts, and flat-tire rescue across major highway corridors.',
        skills: ['24/7 Roadside SOS', 'GPS Telemetry', 'Towing Logistics', 'Emergency Incident Response'],
        email: 'mailto:support@serveasy.in?subject=Emergency SOS Operations (David Chen)'
      },
      'sophia': {
        name: 'Sophia Lin',
        role: 'Cybersecurity & Privacy Lead',
        dept: 'Information Security Directorate',
        reportsTo: 'Saikat Sasmal (CTO)',
        img: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=400&q=80',
        bio: 'Conducts continuous penetration testing, audits OWASP defensive controls, and secures customer payment gateway tokens and session encryption.',
        skills: ['AppSec Hardening', 'Zero Trust Architecture', 'Data Privacy', 'Penetration Testing'],
        email: 'mailto:support@serveasy.in?subject=Security & Privacy Desk (Sophia Lin)'
      },
      'priya': {
        name: 'Priya Sharma',
        role: 'Customer Concierge Lead',
        dept: 'Customer Success & Experience',
        reportsTo: 'Elena Rostova (COO)',
        img: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80',
        bio: 'Leads our multi-channel customer concierge team, resolving scheduling adjustments, booking preferences, and customer satisfaction tickets.',
        skills: ['Concierge Desk', 'Support Resolution', 'Customer NPS', 'Booking Logistics'],
        email: 'mailto:support@serveasy.in?subject=Customer Concierge (Priya Sharma)'
      },
      'rahul': {
        name: 'Rahul Verma',
        role: 'Partner KYC & Vetting Lead',
        dept: 'Compliance & Onboarding Unit',
        reportsTo: 'Elena Rostova (COO)',
        img: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=400&q=80',
        bio: 'Enforces rigorous 4-step partner onboarding: government identity verification, criminal background checks, skill testing, and trade certification.',
        skills: ['Government KYC', 'Criminal Checks', 'Trade Audits', 'Compliance Verification'],
        email: 'mailto:support@serveasy.in?subject=Partner KYC Compliance (Rahul Verma)'
      },
      'ananya': {
        name: 'Ananya Das',
        role: 'Quality Assurance & Field Auditor',
        dept: 'Safety & Sanitation Audit Desk',
        reportsTo: 'Elena Rostova (COO)',
        img: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=400&q=80',
        bio: 'Conducts unannounced on-site quality spot checks, verifies technician sanitation gear, and ensures residential cleaning protocols meet hospital-grade standards.',
        skills: ['Field Audits', 'Hygiene Standards', 'Sanitation Safety', 'Service Spot Checks'],
        email: 'mailto:support@serveasy.in?subject=Field Quality Audit (Ananya Das)'
      },
      'vikram': {
        name: 'Vikram Malhotra',
        role: 'Fleet Logistics & Rental Manager',
        dept: 'Fleet & Transport Logistics',
        reportsTo: 'Marcus Sterling (CPO)',
        img: 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=400&q=80',
        bio: 'Manages the ServEasy car and bike rental fleets, cargo carrier dispatch, vehicle health inspections, and preventative maintenance rotations.',
        skills: ['Rental Logistics', 'Cargo Express', 'Vehicle Maintenance', 'Fleet Telematics'],
        email: 'mailto:support@serveasy.in?subject=Fleet Logistics (Vikram Malhotra)'
      },
      'amit': {
        name: 'Amit Roy',
        role: 'Merchant Billing & Settlements Lead',
        dept: 'Financial Escrow & Settlements Desk',
        reportsTo: 'Marcus Sterling (CPO)',
        img: 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=400&q=80',
        bio: 'Oversees payment gateway reconciliations, escrow accounts, same-day technician payouts, and verified customer refund disbursements.',
        skills: ['Instant Payouts', 'Gateway Escrow', 'Refund Audits', 'Financial Reconciliation'],
        email: 'mailto:support@serveasy.in?subject=Billing & Settlements (Amit Roy)'
      }
    };

    function openMemberModal(key) {
      const data = teamDatabase[key];
      if (!data) return;

      document.getElementById('modalImg').src = data.img;
      document.getElementById('modalName').textContent = data.name;
      document.getElementById('modalRole').textContent = data.role;
      document.getElementById('modalDept').textContent = data.dept;
      document.getElementById('modalReportsTo').innerHTML = 'Reports to: <span>' + data.reportsTo + '</span>';
      document.getElementById('modalBio').textContent = data.bio;
      document.getElementById('modalEmail').href = data.email;

      const skillsBox = document.getElementById('modalSkills');
      skillsBox.innerHTML = '';
      if (Array.isArray(data.skills)) {
        data.skills.forEach(s => {
          const pill = document.createElement('span');
          pill.className = 'modal-skill-pill';
          pill.textContent = s;
          skillsBox.appendChild(pill);
        });
      }

      const modal = document.getElementById('memberModal');
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeMemberModal() {
      document.getElementById('memberModal').style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeMemberModal();
    });
  </script>

  <!-- Ionicons -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
