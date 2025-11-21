<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png">

  <title>PayGlobe Merchant Console v3.0</title>

  <!-- Modern Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Font Awesome 6.5 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Design System - Modern Style -->
  <link rel="stylesheet" href="assets/css/design-modern.css">

  <style>
    /* ========================================
       TOP NAVBAR - GLASSMORPHISM
       ======================================== */
    .top-nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 70px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.18);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08);
      z-index: var(--z-sticky);
      display: flex;
      align-items: center;
      padding: 0 var(--space-6);
      gap: var(--space-6);
    }

    .nav-brand {
      display: flex;
      align-items: center;
      gap: var(--space-3);
      text-decoration: none;
      color: var(--text-primary);
      font-weight: var(--font-bold);
      font-size: var(--text-xl);
      white-space: nowrap;
    }

    .nav-brand svg {
      height: 40px;
      width: auto;
    }

    .version-badge {
      background: var(--gradient-primary);
      color: white;
      padding: var(--space-1) var(--space-3);
      border-radius: var(--radius-full);
      font-size: var(--text-xs);
      font-weight: var(--font-bold);
      letter-spacing: 0.5px;
    }

    /* ========================================
       NAVIGATION MENU
       ======================================== */
    .nav-menu {
      display: flex;
      align-items: center;
      gap: var(--space-2);
      flex: 1;
      list-style: none;
      margin: 0;
      padding: 0;
    }

    .nav-item {
      position: relative;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: var(--space-2);
      padding: var(--space-3) var(--space-4);
      color: var(--text-secondary);
      text-decoration: none;
      font-weight: var(--font-medium);
      font-size: var(--text-sm);
      border-radius: var(--radius-md);
      transition: all var(--transition-base);
      white-space: nowrap;
    }

    .nav-link:hover {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
      color: var(--primary-600);
      transform: translateY(-2px);
    }

    .nav-link i {
      font-size: 1.1em;
      width: 18px;
      text-align: center;
    }

    .nav-link .chevron {
      font-size: 0.7em;
      margin-left: var(--space-1);
      transition: transform var(--transition-base);
    }

    .nav-item.open .chevron {
      transform: rotate(180deg);
    }

    /* ========================================
       DROPDOWN MENU
       ======================================== */
    .dropdown-menu {
      position: absolute;
      top: calc(100% + var(--space-2));
      left: 0;
      min-width: 240px;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-xl);
      border: 1px solid rgba(255, 255, 255, 0.18);
      padding: var(--space-3);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all var(--transition-base);
      z-index: var(--z-dropdown);
    }

    .nav-item.open .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .dropdown-item {
      display: flex;
      align-items: center;
      gap: var(--space-3);
      padding: var(--space-3) var(--space-4);
      color: var(--gray-900);
      text-decoration: none;
      font-weight: var(--font-medium);
      font-size: var(--text-sm);
      border-radius: var(--radius-md);
      transition: all var(--transition-base);
      background: transparent;
    }

    .dropdown-item:hover {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      background-color: #667eea;
      color: #ffffff !important;
      transform: translateX(4px);
    }

    .dropdown-item i {
      width: 18px;
      text-align: center;
      font-size: 1em;
    }

    /* ========================================
       USER MENU
       ======================================== */
    .user-menu {
      position: relative;
    }

    .user-button {
      display: flex;
      align-items: center;
      gap: var(--space-2);
      padding: var(--space-2) var(--space-4);
      background: var(--gradient-primary);
      color: white;
      border: none;
      border-radius: var(--radius-full);
      font-family: var(--font-sans);
      font-weight: var(--font-semibold);
      font-size: var(--text-sm);
      cursor: pointer;
      transition: all var(--transition-base);
      box-shadow: var(--shadow-sm);
    }

    .user-button:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .user-button i {
      font-size: 1.2em;
    }

    .user-button .chevron {
      font-size: 0.7em;
      transition: transform var(--transition-base);
    }

    .user-menu.open .user-button .chevron {
      transform: rotate(180deg);
    }

    .user-dropdown {
      position: absolute;
      top: calc(100% + var(--space-2));
      right: 0;
      min-width: 280px;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-xl);
      border: 1px solid rgba(255, 255, 255, 0.18);
      padding: var(--space-4);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all var(--transition-base);
      z-index: var(--z-dropdown);
    }

    .user-menu.open .user-dropdown {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .user-info {
      padding-bottom: var(--space-4);
      border-bottom: 2px solid var(--gray-100);
      margin-bottom: var(--space-3);
    }

    .user-info-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: var(--space-2) 0;
      font-size: var(--text-sm);
    }

    .user-info-row .label {
      color: var(--text-secondary);
      font-weight: var(--font-medium);
      display: flex;
      align-items: center;
      gap: var(--space-2);
    }

    .user-info-row .label i {
      color: var(--primary-500);
      width: 18px;
    }

    .user-info-row .value {
      color: var(--text-primary);
      font-weight: var(--font-semibold);
    }

    .logout-btn {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: var(--space-2);
      padding: var(--space-3) var(--space-4);
      background: var(--gradient-danger);
      color: white;
      border: none;
      border-radius: var(--radius-md);
      font-family: var(--font-sans);
      font-weight: var(--font-semibold);
      font-size: var(--text-sm);
      cursor: pointer;
      transition: all var(--transition-base);
      text-decoration: none;
    }

    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    /* ========================================
       MOBILE MENU TOGGLE
       ======================================== */
    .mobile-toggle {
      display: none;
      background: none;
      border: none;
      color: var(--text-primary);
      font-size: 1.5rem;
      cursor: pointer;
      padding: var(--space-2);
      margin-left: auto;
    }

    @media (max-width: 992px) {
      .mobile-toggle {
        display: block;
      }

      .nav-menu {
        position: fixed;
        top: 70px;
        left: -100%;
        width: 320px;
        height: calc(100vh - 70px);
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        flex-direction: column;
        align-items: stretch;
        padding: var(--space-6);
        box-shadow: var(--shadow-2xl);
        transition: left var(--transition-slow);
        overflow-y: auto;
      }

      .nav-menu.active {
        left: 0;
      }

      .nav-link {
        justify-content: flex-start;
      }

      .dropdown-menu {
        position: static;
        opacity: 1;
        visibility: visible;
        transform: none;
        box-shadow: none;
        border: none;
        border-left: 2px solid var(--primary-500);
        margin-left: var(--space-4);
        margin-top: var(--space-2);
        display: none;
      }

      .nav-item.open .dropdown-menu {
        display: block;
      }
    }

    /* ========================================
       MAIN CONTENT AREA
       ======================================== */
    .main-content {
      margin-top: 70px;
      padding: var(--space-8) var(--space-6);
      min-height: calc(100vh - 70px);
      animation: fadeInUp 0.6s var(--transition-base);
    }
  </style>
</head>

<body x-data="{ mobileMenuOpen: false }">

  <!-- Top Navigation -->
  <nav class="top-nav">
    <!-- Brand -->
    <a href="" class="nav-brand">
      <svg width="140" height="40" viewBox="0 0 761.13 177.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <defs>
          <style>
            .cls-1 { fill: url(#Nuovo_campione_sfumatura_2); }
            .cls-2 { fill: #06112c; }
            .cls-3 { fill: url(#Nuovo_campione_sfumatura_2-2); }
          </style>
          <linearGradient id="Nuovo_campione_sfumatura_2" x1="74.71" y1="17.34" x2="109.39" y2="17.34" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#488dec"/>
            <stop offset="1" stop-color="#9a1bf1"/>
          </linearGradient>
          <linearGradient id="Nuovo_campione_sfumatura_2-2" x1="0" y1="106.75" x2="154.73" y2="106.75" xlink:href="#Nuovo_campione_sfumatura_2"/>
        </defs>
        <g>
          <path class="cls-2" d="m431.25,46.38c20.22,0,30.1,10,30.1,10l-8.73,10.34s-7.7-6.78-21.03-6.78c-20.68,0-34.01,17.12-34.01,34.7,0,14.36,9.65,22.52,22.4,22.52s22.29-8.85,22.29-8.85l1.84-9.19h-12.64l2.53-12.75h25.85l-8.39,42.97h-12.64l.8-4.02c.34-1.72.81-3.45.81-3.45h-.23s-8.5,8.85-23.32,8.85c-18.73,0-34.58-13.44-34.58-35.16,0-26.66,21.83-49.18,48.94-49.18"/>
          <polygon class="cls-2" points="484.11 47.76 498.93 47.76 485.49 116.59 520.76 116.59 518.11 129.34 468.25 129.34 484.11 47.76"/>
          <path class="cls-2" d="m577.86,46.39c22.4,0,37.23,14.71,37.23,35.04,0,26.65-23.78,49.29-48.37,49.29-22.52,0-37.11-15.05-37.11-35.85,0-26.2,23.44-48.49,48.26-48.49m-11.03,70.77c16.31,0,32.97-15.51,32.97-34.81,0-13.33-9.08-22.4-22.06-22.4-16.66,0-32.97,15.28-32.97,34.12,0,13.67,9.08,23.09,22.06,23.09"/>
          <path class="cls-2" d="m637.85,47.76h26.2c4.71,0,8.85.46,12.29,1.72,7.47,2.64,11.83,8.73,11.83,16.77,0,8.62-5.28,16.43-13.33,20.11v.23c6.55,2.41,9.88,8.62,9.88,15.85,0,12.52-7.81,21.26-18.27,24.93-3.91,1.38-8.16,1.95-12.41,1.95h-32.05l15.85-81.57Zm17.23,68.82c2.53,0,4.83-.46,6.78-1.5,4.59-2.41,7.7-7.35,7.7-12.98s-3.56-9.08-9.88-9.08h-15.85l-4.6,23.56h15.85Zm5.4-35.5c7.24,0,12.41-5.86,12.41-12.87,0-4.48-2.64-7.7-8.62-7.7h-14.13l-4.02,20.57h14.36Z"/>
          <polygon class="cls-2" points="712.42 47.76 761.13 47.76 758.61 60.52 724.6 60.52 720.46 81.89 747.92 81.89 745.39 94.64 717.93 94.64 713.68 116.59 749.53 116.59 747.12 129.34 696.45 129.34 712.42 47.76"/>
          <path class="cls-2" d="m189.47,48.68h29.64c14.82,0,25.51,10,25.51,25.39s-10.68,25.73-25.51,25.73h-18.27v29.99h-11.37V48.68Zm27.8,41.25c9.77,0,15.74-6.09,15.74-15.85s-5.97-15.51-15.63-15.51h-16.54v31.37h16.43Z"/>
          <path class="cls-2" d="m297.02,106.47h-30.56l-8.04,23.32h-11.72l29.18-81.12h11.95l29.18,81.12h-11.83l-8.16-23.32Zm-15.28-46.65s-1.84,7.35-3.22,11.49l-9.08,25.73h24.59l-8.96-25.73c-1.38-4.14-3.1-11.49-3.1-11.49h-.23Z"/>
          <path class="cls-2" d="m341.14,95.44l-27.23-46.76h12.87l15.05,26.66c2.53,4.48,4.94,10.23,4.94,10.23h.23s2.41-5.63,4.94-10.23l14.82-26.66h12.87l-27.11,46.76v34.35h-11.38v-34.35Z"/>
          <path class="cls-1" d="m103.24,30.59c7.31-6.18,8.23-17.12,2.06-24.44-6.19-7.32-17.12-8.24-24.44-2.06-7.31,6.18-8.24,17.12-2.06,24.44,6.18,7.31,17.12,8.24,24.44,2.06Z"/>
          <path class="cls-3" d="m139.67,142.35c28.74-41.92,13.35-99.92-20.92-105.49-41.38-6.95-66.36,65.65-58.42,65.09,0,0,0,0,0,0,4.6-.32,16.89-20,31.61-32.54,5.54-4.7,15.12-11.34,21.88-8.72,6.4,2.54,9.62,12.45,10.5,19.65,2.1,16.81-4.75,32.71-9.57,38.9-13.07,16.81-31.6,26.31-46.84,27.13-24.12,1.29-45.56-14.91-52.06-37.52-6.55-22.71,2.28-50.29,26.54-68.58,0,0,0,0,0,0,3.45-2.59,5.56-3.81,5.55-3.83,0,0-33.33,11.61-44.62,47.17-18.78,58.97,45.13,114.34,106.28,85.75,5.61-2.72,15.19-9.32,23.24-18.33-.03.04-.07.07-.1.11.15-.16.31-.33.45-.49,2.36-2.67,4.51-5.44,6.45-8.28"/>
        </g>
      </svg>
      <span class="version-badge">MC5 v3.0</span>
    </a>

    <!-- Mobile Toggle -->
    <button class="mobile-toggle" @click="mobileMenuOpen = !mobileMenuOpen">
      <i class="fas" :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'"></i>
    </button>

    <!-- Main Navigation -->
    <ul class="nav-menu" :class="{ 'active': mobileMenuOpen }" x-data="{ openDropdown: null }">
      <?php if ($role == "Admin"): ?>
        <li class="nav-item">
          <a href="index.php" class="nav-link">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <li class="nav-item">
          <a href="stores.php" class="nav-link">
            <i class="fas fa-store"></i>
            <span>Negozi</span>
          </a>
        </li>

        <li class="nav-item" :class="{ 'open': openDropdown === 'transactions' }">
          <a href="#" class="nav-link" @click.prevent="openDropdown = openDropdown === 'transactions' ? null : 'transactions'">
            <i class="fas fa-credit-card"></i>
            <span>Transazioni</span>
            <i class="fas fa-chevron-down chevron"></i>
          </a>
          <div class="dropdown-menu">
            <a href="tutte-5.php" class="dropdown-item">
              <i class="fas fa-list"></i>
              <span>Tutte</span>
            </a>
            <a href="scarti.php" class="dropdown-item">
              <i class="fas fa-times-circle"></i>
              <span>Rifiutate</span>
            </a>
            <a href="binlist.php" class="dropdown-item">
              <i class="fas fa-fingerprint"></i>
              <span>BIN TABLE</span>
            </a>
          </div>
        </li>
      <?php endif; ?>

      <?php if ($role == "Reader" || $role == "Admin"): ?>
        <li class="nav-item">
          <a href="internazionale-ecom-4.php" class="nav-link">
            <i class="fas fa-shopping-cart"></i>
            <span>eCommerce</span>
          </a>
        </li>
      <?php endif; ?>

    </ul>

    <!-- Logout Button -->
    <a href="logout.php" class="btn btn-outline" style="display: flex; align-items: center; gap: var(--space-2); white-space: nowrap;">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>

    <!-- User Menu -->
    <div class="user-menu" x-data="{ open: false }" @click.away="open = false">
      <button class="user-button" @click="open = !open">
        <i class="fas fa-user-circle"></i>
        <span><?php echo $user['Username'] ?? 'User'; ?></span>
        <i class="fas fa-chevron-down chevron"></i>
      </button>

      <div class="user-dropdown" :class="{ 'open': open }">
        <div class="user-info">
          <div class="user-info-row">
            <span class="label">
              <i class="fas fa-user"></i>
              Username
            </span>
            <span class="value"><?php echo $user['Username'] ?? 'N/A'; ?></span>
          </div>
          <div class="user-info-row">
            <span class="label">
              <i class="fas fa-shield-alt"></i>
              Ruolo
            </span>
            <span class="value"><?php echo $role ?? 'N/A'; ?></span>
          </div>
          <div class="user-info-row">
            <span class="label">
              <i class="fas fa-users"></i>
              Gruppo
            </span>
            <span class="value"><?php echo $application ?? 'N/A'; ?></span>
          </div>
        </div>
        <a href="logout.php" class="logout-btn">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </a>
      </div>
    </div>
  </nav>

  <!-- Main Content Area -->
  <div class="main-content">
