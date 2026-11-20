<?php
/**
 * ServEasy Customer Web Portal - About Us & Interactive Team Organization Tree
 * High-Tech, Cyber-Glassmorphic Visual Organogram with Animated Glowing Branch Connectors
 */
require_once __DIR__ . '/../common/auth_session.php';
if (!defined('SERVEASY_ASSET_VER')) {
    define('SERVEASY_ASSET_VER', '20261120v3');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About ServEasy — Leadership Team & Interactive Organization Tree</title>
  <meta name="description" content="Explore the ServEasy leadership tree: Sharthak Sen (Founder & CEO), 3 executive directors, and 7 functional command leads powering on-demand vehicle and home services.">
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
    /* ── Ambient Background Lighting ─────────────────────────────────── */
    .tree-stage {
      position: relative;
      background: radial-gradient(ellipse at 50% 15%, rgba(0, 242, 254, 0.12) 0%, rgba(15, 23, 42, 0) 65%),
                  radial-gradient(ellipse at 85% 60%, rgba(168, 85, 247, 0.08) 0%, rgba(15, 23, 42, 0) 60%),
                  radial-gradient(ellipse at 15% 75%, rgba(245, 158, 11, 0.08) 0%, rgba(15, 23, 42, 0) 60%);
      padding: 30px 10px 80px;
      border-radius: 32px;
      border: 1px solid rgba(255, 255, 255, 0.06);
      box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.8);
      overflow-x: auto;
      margin-bottom: 70px;
    }

    /* ── Hero Section ────────────────────────────────────────────────── */
    .about-hero-section {
      padding: 50px 20px 35px;
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
      margin: 0 auto 35px;
      line-height: 1.7;
    }

    /* ── Controls HUD ────────────────────────────────────────────────── */
    .tree-hud-bar {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 12px;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }
    .hud-chip {
      padding: 8px 18px;
      border-radius: 50px;
      background: rgba(30, 41, 59, 0.7);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #cbd5e1;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.25s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .hud-chip:hover, .hud-chip.active {
      background: linear-gradient(135deg, rgba(0, 242, 254, 0.25), rgba(99, 102, 241, 0.25));
      border-color: #00f2fe;
      color: #00f2fe;
      box-shadow: 0 4px 20px rgba(0, 242, 254, 0.3);
      transform: translateY(-2px);
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

    /* ── PURE CSS ELECTRIC TREE CHART ─────────────────────────────────── */
    .org-tree {
      display: flex;
      justify-content: center;
      min-width: 1120px;
      padding: 20px 0;
    }
    .org-tree ul {
      padding-top: 40px;
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
      padding: 40px 14px 0 14px;
      transition: all 0.5s ease;
    }

    /* Animated Branch Connectors with Glowing Pulses */
    .org-tree li::before, .org-tree li::after {
      content: '';
      position: absolute;
      top: 0;
      right: 50%;
      border-top: 2px solid #00f2fe;
      width: 50%;
      height: 40px;
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
      height: 40px;
      filter: drop-shadow(0 0 6px rgba(0, 242, 254, 0.6));
    }

    /* ── CARD BASE DESIGN SYSTEM ─────────────────────────────────────── */
    .cyber-node {
      display: inline-block;
      text-align: center;
      text-decoration: none;
      position: relative;
      border-radius: 24px;
      background: linear-gradient(145deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.85));
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
      cursor: pointer;
    }
    .cyber-node:hover {
      transform: translateY(-8px) scale(1.02);
      z-index: 10;
    }

    /* ── LEVEL 1: SHARTHAK SEN (FOUNDER & CEO / SUPREME LEADER) ──────── */
    .node-crown {
      width: 440px;
      padding: 35px 28px 30px;
      border: 2px solid #00f2fe;
      box-shadow: 0 0 40px rgba(0, 242, 254, 0.3), inset 0 0 30px rgba(0, 242, 254, 0.1);
      background: linear-gradient(160deg, rgba(15, 23, 42, 0.98), rgba(30, 41, 59, 0.92));
    }
    .node-crown::before {
      content: '';
      position: absolute;
      inset: -2px;
      border-radius: 26px;
      background: linear-gradient(135deg, #00f2fe, #f59e0b, #4facfe, #00f2fe);
      background-size: 300% 300%;
      z-index: -1;
      opacity: 0.7;
      animation: borderGlow 6s ease infinite;
    }
    @keyframes borderGlow {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .crown-banner {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.25), rgba(217, 119, 6, 0.25));
      border: 1px solid rgba(245, 158, 11, 0.6);
      color: #fbbf24;
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 1px;
      text-transform: uppercase;
      padding: 5px 16px;
      border-radius: 50px;
      margin-bottom: 16px;
      box-shadow: 0 0 15px rgba(245, 158, 11, 0.3);
    }
    .crown-avatar-frame {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00f2fe 0%, #4facfe 50%, #f59e0b 100%);
      color: #0b132b;
      font-size: 2.2rem;
      font-weight: 900;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 15px;
      border: 3px solid rgba(255, 255, 255, 0.4);
      box-shadow: 0 0 30px rgba(0, 242, 254, 0.5);
      position: relative;
    }
    .status-badge-online {
      position: absolute;
      bottom: 2px;
      right: 2px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: #10b981;
      border: 3px solid #0f172a;
      box-shadow: 0 0 10px #10b981;
    }
    .node-title-l1 {
      font-size: 1.85rem;
      font-weight: 900;
      color: #ffffff;
      margin-bottom: 4px;
      letter-spacing: -0.5px;
    }
    .node-role-l1 {
      font-size: 1.02rem;
      font-weight: 800;
      color: #00f2fe;
      margin-bottom: 12px;
      letter-spacing: 0.2px;
    }
    .node-bio-l1 {
      color: #94a3b8;
      font-size: 0.92rem;
      line-height: 1.6;
      margin-bottom: 18px;
    }
    .chip-group {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 6px;
    }
    .skill-chip {
      font-size: 0.72rem;
      font-family: 'JetBrains Mono', monospace;
      padding: 3px 10px;
      border-radius: 6px;
      background: rgba(255, 255, 255, 0.06);
      color: #cbd5e1;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .skill-chip.gold { background: rgba(245, 158, 11, 0.12); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3); }
    .skill-chip.cyan { background: rgba(0, 242, 254, 0.12); color: #00f2fe; border-color: rgba(0, 242, 254, 0.3); }
    .skill-chip.purple { background: rgba(168, 85, 247, 0.12); color: #c084fc; border-color: rgba(168, 85, 247, 0.3); }

    /* ── LEVEL 2: 3 DIRECTORATE CHIEFS ──────────────────────────────── */
    .node-l2 {
      width: 320px;
      padding: 26px 20px 22px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
    }
    .node-l2.cyan-theme {
      border-top: 4px solid #00f2fe;
      box-shadow: 0 10px 30px rgba(0, 242, 254, 0.15);
    }
    .node-l2.purple-theme {
      border-top: 4px solid #c084fc;
      box-shadow: 0 10px 30px rgba(168, 85, 247, 0.15);
    }
    .node-l2.amber-theme {
      border-top: 4px solid #f59e0b;
      box-shadow: 0 10px 30px rgba(245, 158, 11, 0.15);
    }

    .avatar-l2-frame {
      width: 68px;
      height: 68px;
      border-radius: 50%;
      font-size: 1.6rem;
      font-weight: 900;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 12px;
      position: relative;
    }
    .avatar-l2-frame.cyan { background: linear-gradient(135deg, rgba(0,242,254,0.3), rgba(79,172,254,0.5)); color: #00f2fe; border: 2px solid #00f2fe; }
    .avatar-l2-frame.purple { background: linear-gradient(135deg, rgba(168,85,247,0.3), rgba(192,132,252,0.5)); color: #c084fc; border: 2px solid #c084fc; }
    .avatar-l2-frame.amber { background: linear-gradient(135deg, rgba(245,158,11,0.3), rgba(251,191,36,0.5)); color: #fbbf24; border: 2px solid #f59e0b; }

    .node-name-l2 {
      font-size: 1.35rem;
      font-weight: 800;
      color: #ffffff;
      margin-bottom: 4px;
    }
    .node-role-l2 {
      font-size: 0.88rem;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .node-desc-l2 {
      font-size: 0.84rem;
      color: #94a3b8;
      line-height: 1.55;
      margin-bottom: 14px;
    }

    /* ── LEVEL 3: 7 FUNCTIONAL SPECIALISTS ──────────────────────────── */
    .node-l3 {
      width: 200px;
      padding: 18px 14px 16px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(30, 41, 59, 0.75);
      border-radius: 18px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }
    .node-l3:hover {
      border-color: #00f2fe;
      background: rgba(30, 41, 59, 0.95);
      box-shadow: 0 12px 30px rgba(0, 242, 254, 0.25);
    }
    .avatar-l3-frame {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.08);
      border: 2px solid rgba(255, 255, 255, 0.15);
      color: #38bdf8;
      font-size: 1.1rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 10px;
    }
    .node-name-l3 {
      font-size: 1.05rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 3px;
      line-height: 1.25;
    }
    .node-role-l3 {
      font-size: 0.78rem;
      color: #38bdf8;
      font-weight: 700;
      margin-bottom: 8px;
      line-height: 1.35;
    }
    .tag-l3-pill {
      display: inline-block;
      font-size: 0.68rem;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 6px;
      background: rgba(0, 242, 254, 0.08);
      color: #7dd3fc;
      border: 1px solid rgba(0, 242, 254, 0.2);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* ── Live Stats HUD Bar ─────────────────────────────────────────── */
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
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .hud-stat-lbl {
      color: #94a3b8;
      font-size: 0.88rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    /* ── Core Value Pillars ─────────────────────────────────────────── */
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

    /* ── CTA Banner ─────────────────────────────────────────────────── */
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

    /* ── Interactive Modal for Team Member Details ──────────────────── */
    .team-modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(8px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .team-modal-box {
      background: #0f172a;
      border: 1px solid #00f2fe;
      border-radius: 24px;
      max-width: 500px;
      width: 100%;
      padding: 35px 30px;
      text-align: center;
      position: relative;
      box-shadow: 0 25px 60px rgba(0, 242, 254, 0.3);
      animation: modalPop 0.3s ease;
    }
    @keyframes modalPop {
      0% { transform: scale(0.9); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }
    .btn-modal-close {
      position: absolute;
      top: 15px;
      right: 18px;
      background: transparent;
      border: none;
      color: #94a3b8;
      font-size: 1.8rem;
      cursor: pointer;
    }
    .btn-modal-close:hover { color: #00f2fe; }
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
          <a href="../index.php" style="color: #94a3b8; text-decoration: none;">Home</a> / <span style="color: #00f2fe;">About ServEasy & Organizational Command Tree</span>
        </span>
      </div>

      <!-- Hero Header -->
      <section class="about-hero-section">
        <div class="hero-badge-pill">
          <span class="pulse-dot"></span>
          <span>Official Organization Command Tree</span>
        </div>
        <h1 class="about-hero-title">
          Meet the Minds Behind <span class="gradient-text">ServEasy</span>
        </h1>
        <p class="about-hero-desc">
          An interactive architectural site map of our executive governance, technology directors, and field specialist command leads steering on-demand excellence across vehicle care, home hygiene, and 24/7 roadside rescue.
        </p>

        <!-- Interactive HUD Filter Buttons -->
        <div class="tree-hud-bar">
          <button type="button" class="hud-chip active" onclick="highlightTree('all', this)">
            <ion-icon name="git-network-outline"></ion-icon> Complete Network (11 Members)
          </button>
          <button type="button" class="hud-chip" onclick="highlightTree('l1', this)">
            <ion-icon name="shield-checkmark"></ion-icon> Top Authority (Sharthak Sen)
          </button>
          <button type="button" class="hud-chip" onclick="highlightTree('tech', this)">
            <ion-icon name="code-slash-outline"></ion-icon> Technology Command
          </button>
          <button type="button" class="hud-chip" onclick="highlightTree('ops', this)">
            <ion-icon name="speedometer-outline"></ion-icon> Operations Grid
          </button>
          <button type="button" class="hud-chip" onclick="highlightTree('prod', this)">
            <ion-icon name="car-sport-outline"></ion-icon> Automotive & Fleet
          </button>
        </div>
        <div class="scroll-hint-pill">
          <ion-icon name="swap-horizontal-outline"></ion-icon> Swipe horizontally to inspect reporting branches
        </div>
      </section>

      <!-- ===============================================================
           HIGH-TECH VISUAL ORGANIZATIONAL TREE STAGE
           =============================================================== -->
      <section class="tree-stage">
        <div class="org-tree">
          <ul>
            <!-- ROOT / LEVEL 1: SHARTHAK SEN -->
            <li class="branch-all branch-l1">
              <div class="cyber-node node-crown" onclick="openMemberModal('Sharthak Sen', 'Founder & Chief Executive Officer (Super Admin)', 'Executive Directorate & Supreme Governance', 'Steers the overarching strategic vision, technology roadmap, investment governance, and nationwide expansion of the ServEasy on-demand ecosystem.', ['Strategic Direction', 'Ecosystem Architecture', 'Superadmin Control'])">
                <div class="crown-banner">
                  <ion-icon name="sparkles-outline"></ion-icon> Supreme Executive Authority &bull; Level 1
                </div>
                <div class="crown-avatar-frame">
                  SS
                  <span class="status-badge-online" title="Active on Network"></span>
                </div>
                <h2 class="node-title-l1">Sharthak Sen</h2>
                <div class="node-role-l1">Founder & Chief Executive Officer (Super Admin)</div>
                <p class="node-bio-l1">
                  Master architect behind ServEasy's multi-portal on-demand ecosystem, real-time dispatch infrastructure, and verified partner network.
                </p>
                <div class="chip-group">
                  <span class="skill-chip gold">Executive Board</span>
                  <span class="skill-chip cyan">Platform Architecture</span>
                  <span class="skill-chip purple">Super Admin Control</span>
                </div>
              </div>

              <!-- BRANCH DOWN TO LEVEL 2: 3 DIRECTORATE CHIEFS -->
              <ul>
                <!-- LEVEL 2 &bull; PILLAR 1: SAIKAT SASMAL (TECHNOLOGY) -->
                <li class="branch-all branch-tech">
                  <div class="cyber-node node-l2 cyan-theme" onclick="openMemberModal('Saikat Sasmal', 'Chief Technology Officer & Lead Architect', 'Engineering & Technology Command', 'Directs the engineering team, multi-portal API bridges, database integrity, and high-concurrency dispatch telemetry.', ['Cloud Architecture', 'MySQL Security', 'REST APIs', 'System Defense'])">
                    <div class="avatar-l2-frame cyan">
                      SS
                      <span class="status-badge-online"></span>
                    </div>
                    <h3 class="node-name-l2">Saikat Sasmal</h3>
                    <div class="node-role-l2" style="color: #00f2fe;">Chief Technology Officer (CTO)</div>
                    <p class="node-desc-l2">
                      Architect of multi-portal security, automated API bridges, and high-availability database engines.
                    </p>
                    <div class="chip-group">
                      <span class="skill-chip cyan">API Architecture</span>
                      <span class="skill-chip cyan">Database Security</span>
                    </div>
                  </div>

                  <!-- SUB-BRANCH TO LEVEL 3 (2 SPECIALISTS UNDER SAIKAT) -->
                  <ul>
                    <li class="branch-all branch-tech">
                      <div class="cyber-node node-l3" onclick="openMemberModal('David Chen', 'Emergency SOS Dispatch Lead', 'Emergency Rapid Response Unit', 'Manages live GPS roadside breakdown tracking, emergency tow truck dispatch, and technician response times.', ['24/7 Roadside SOS', 'GPS Telemetry', 'Towing Logistics'])">
                        <div class="avatar-l3-frame">DC</div>
                        <h4 class="node-name-l3">David Chen</h4>
                        <div class="node-role-l3">Emergency SOS Dispatch</div>
                        <span class="tag-l3-pill">Rapid SOS</span>
                      </div>
                    </li>
                    <li class="branch-all branch-tech">
                      <div class="cyber-node node-l3" onclick="openMemberModal('Sophia Lin', 'Cybersecurity & Privacy Lead', 'Information Security Directorate', 'Audits OWASP defenses, executes penetration testing, and ensures end-to-end customer credential encryption.', ['AppSec Hardening', 'Zero Trust', 'Data Privacy'])">
                        <div class="avatar-l3-frame">SL</div>
                        <h4 class="node-name-l3">Sophia Lin</h4>
                        <div class="node-role-l3">Cybersecurity Lead</div>
                        <span class="tag-l3-pill">AppSec & Privacy</span>
                      </div>
                    </li>
                  </ul>
                </li>

                <!-- LEVEL 2 &bull; PILLAR 2: ELENA ROSTOVA (OPERATIONS) -->
                <li class="branch-all branch-ops">
                  <div class="cyber-node node-l2 purple-theme" onclick="openMemberModal('Elena Rostova', 'Chief Operating Officer (COO)', 'Field Operations & Service Grid', 'Oversees nationwide technician fulfillment, partner vetting compliance, dispute arbitration, and customer satisfaction.', ['Field Operations', 'SLA Enforcement', 'Partner Network', 'Service Quality'])">
                    <div class="avatar-l2-frame purple">
                      ER
                      <span class="status-badge-online"></span>
                    </div>
                    <h3 class="node-name-l2">Elena Rostova</h3>
                    <div class="node-role-l2" style="color: #c084fc;">Chief Operating Officer (COO)</div>
                    <p class="node-desc-l2">
                      Enforces doorstep service standards, partner SLA compliance, and multi-channel customer concierge.
                    </p>
                    <div class="chip-group">
                      <span class="skill-chip purple">Operations Grid</span>
                      <span class="skill-chip purple">SLA Quality</span>
                    </div>
                  </div>

                  <!-- SUB-BRANCH TO LEVEL 3 (3 SPECIALISTS UNDER ELENA) -->
                  <ul>
                    <li class="branch-all branch-ops">
                      <div class="cyber-node node-l3" onclick="openMemberModal('Priya Sharma', 'Customer Concierge Lead', 'Customer Success & Experience', 'Coordinates live booking assistance, rescheduling requests, and VIP customer concierge desk.', ['Concierge Desk', 'Support Resolution', 'Customer NPS'])">
                        <div class="avatar-l3-frame">PS</div>
                        <h4 class="node-name-l3">Priya Sharma</h4>
                        <div class="node-role-l3">Customer Concierge</div>
                        <span class="tag-l3-pill">Concierge Desk</span>
                      </div>
                    </li>
                    <li class="branch-all branch-ops">
                      <div class="cyber-node node-l3" onclick="openMemberModal('Rahul Verma', 'Partner KYC & Vetting Lead', 'Compliance & Onboarding Unit', 'Directs multi-stage government ID checks, trade skill tests, and criminal background verification for providers.', ['Government KYC', 'Criminal Checks', 'Trade Audits'])">
                        <div class="avatar-l3-frame">RV</div>
                        <h4 class="node-name-l3">Rahul Verma</h4>
                        <div class="node-role-l3">Partner KYC Lead</div>
                        <span class="tag-l3-pill">KYC & Vetting</span>
                      </div>
                    </li>
                    <li class="branch-all branch-ops">
                      <div class="cyber-node node-l3" onclick="openMemberModal('Ananya Das', 'Quality Assurance & Field Auditor', 'Safety & Sanitation Audit Desk', 'Conducts surprise on-site service inspections, hygienic protocol audits, and customer review reconciliations.', ['Field Audits', 'Hygiene Standards', 'Sanitation Safety'])">
                        <div class="avatar-l3-frame">AD</div>
                        <h4 class="node-name-l3">Ananya Das</h4>
                        <div class="node-role-l3">QA & Field Auditor</div>
                        <span class="tag-l3-pill">Quality Audit</span>
                      </div>
                    </li>
                  </ul>
                </li>

                <!-- LEVEL 2 &bull; PILLAR 3: MARCUS STERLING (PRODUCT & AUTOMOTIVE) -->
                <li class="branch-all branch-prod">
                  <div class="cyber-node node-l2 amber-theme" onclick="openMemberModal('Marcus Sterling', 'Chief Product Officer (CPO)', 'Automotive & Product Engineering', 'Directs automotive mechanical diagnostic standards, car & bike detailing protocols, and equipment partnerships.', ['Automotive Engineering', 'Tooling Specs', 'Product UX', 'Fleet Standards'])">
                    <div class="avatar-l2-frame amber">
                      MS
                      <span class="status-badge-online"></span>
                    </div>
                    <h3 class="node-name-l2">Marcus Sterling</h3>
                    <div class="node-role-l2" style="color: #fbbf24;">Chief Product Officer (CPO)</div>
                    <p class="node-desc-l2">
                      Leads automotive mechanical standards, vehicle wash innovations, and fleet equipment specifications.
                    </p>
                    <div class="chip-group">
                      <span class="skill-chip gold">Automotive Standards</span>
                      <span class="skill-chip gold">Fleet R&D</span>
                    </div>
                  </div>

                  <!-- SUB-BRANCH TO LEVEL 3 (2 SPECIALISTS UNDER MARCUS) -->
                  <ul>
                    <li class="branch-all branch-prod">
                      <div class="cyber-node node-l3" onclick="openMemberModal('Vikram Malhotra', 'Fleet Logistics & Rental Manager', 'Fleet & Transport Logistics', 'Coordinates car and bike rental fleets, cargo carrier operations, and mechanical maintenance schedules.', ['Rental Logistics', 'Cargo Express', 'Vehicle Maintenance'])">
                        <div class="avatar-l3-frame">VM</div>
                        <h4 class="node-name-l3">Vikram Malhotra</h4>
                        <div class="node-role-l3">Fleet Logistics Manager</div>
                        <span class="tag-l3-pill">Fleet Logistics</span>
                      </div>
                    </li>
                    <li class="branch-all branch-prod">
                      <div class="cyber-node node-l3" onclick="openMemberModal('Amit Roy', 'Merchant Billing & Settlements Lead', 'Financial Escrow & Settlements Desk', 'Manages transparent payment gateway settlements, automated technician payouts, and verified dispute refund audits.', ['Instant Payouts', 'Gateway Escrow', 'Refund Audits'])">
                        <div class="avatar-l3-frame">AR</div>
                        <h4 class="node-name-l3">Amit Roy</h4>
                        <div class="node-role-l3">Merchant Settlements</div>
                        <span class="tag-l3-pill">Billing & Payouts</span>
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

  <!-- Interactive Modal for Leader Bios -->
  <div class="team-modal-backdrop" id="memberModal" onclick="closeMemberModal(event)">
    <div class="team-modal-box" onclick="event.stopPropagation()">
      <button type="button" class="btn-modal-close" onclick="closeMemberModal()">&times;</button>
      <div id="modalAvatar" class="avatar-l1" style="width: 70px; height: 70px; font-size: 1.6rem; margin-bottom: 15px;"></div>
      <h3 id="modalName" style="color: #fff; font-size: 1.6rem; font-weight: 800; margin-bottom: 4px;"></h3>
      <div id="modalRole" style="color: #00f2fe; font-size: 0.95rem; font-weight: 700; margin-bottom: 8px;"></div>
      <div id="modalDept" style="color: #fbbf24; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 16px;"></div>
      <p id="modalBio" style="color: #94a3b8; font-size: 0.92rem; line-height: 1.65; margin-bottom: 20px;"></p>
      <div id="modalSkills" class="chip-group"></div>
    </div>
  </div>

  <!-- Footer -->
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- Interactive Scripts -->
  <script>
    function highlightTree(branch, btn) {
      document.querySelectorAll('.hud-chip').forEach(c => c.classList.remove('active'));
      btn.classList.add('active');

      const allNodes = document.querySelectorAll('.cyber-node');
      allNodes.forEach(node => {
        node.style.opacity = '1';
        node.style.filter = 'none';
      });

      if (branch === 'all') return;

      allNodes.forEach(node => {
        const parentLi = node.closest('li');
        if (parentLi && parentLi.classList.contains('branch-' + branch)) {
          node.style.opacity = '1';
          node.style.transform = 'scale(1.05)';
        } else if (branch === 'l1' && parentLi && parentLi.classList.contains('branch-l1')) {
          node.style.opacity = '1';
        } else {
          node.style.opacity = '0.35';
          node.style.filter = 'grayscale(80%)';
          node.style.transform = 'scale(0.96)';
        }
      });
    }

    function openMemberModal(name, role, dept, bio, skills) {
      document.getElementById('modalName').textContent = name;
      document.getElementById('modalRole').textContent = role;
      document.getElementById('modalDept').textContent = dept;
      document.getElementById('modalBio').textContent = bio;

      const initials = name.split(' ').map(n => n[0]).join('');
      document.getElementById('modalAvatar').textContent = initials;

      const skillsContainer = document.getElementById('modalSkills');
      skillsContainer.innerHTML = '';
      if (Array.isArray(skills)) {
        skills.forEach(s => {
          const sp = document.createElement('span');
          sp.className = 'skill-chip cyan';
          sp.textContent = s;
          skillsContainer.appendChild(sp);
        });
      }

      const modal = document.getElementById('memberModal');
      modal.style.display = 'flex';
    }

    function closeMemberModal() {
      document.getElementById('memberModal').style.display = 'none';
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
