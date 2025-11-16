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

  <!-- Design System (NO BOOTSTRAP!) -->
  <link rel="stylesheet" href="assets/css/design-system.css">

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
      color: var(--text-primary);
      text-decoration: none;
      font-weight: var(--font-medium);
      font-size: var(--text-sm);
      border-radius: var(--radius-md);
      transition: all var(--transition-base);
    }

    .dropdown-item:hover {
      background: var(--gradient-primary);
      color: white;
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
      <svg width="120" height="40" viewBox="0 0 761.13 177.1" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#488dec;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#9a1bf1;stop-opacity:1" />
          </linearGradient>
        </defs>
        <g>
          <path d="M431.25 46.38c20.22 0 30.1 10 30.1 10l-8.73 10.34s-7.7-6.78-21.03-6.78c-20.68 0-34.01 17.12-34.01 34.7 0 14.36 9.65 22.52 22.4 22.52s22.29-8.85 22.29-8.85l1.84-9.19h-12.64L434 86.37h25.85l-8.39 42.97h-12.64l.8-4.02c.34-1.72.81-3.45.81-3.45h-.23s-8.5 8.85-23.32 8.85c-18.73 0-34.58-13.44-34.58-35.16 0-26.66 21.83-49.18 48.94-49.18" fill="#06112c"/>
          <path d="M103.24 30.59c7.31-6.18 8.23-17.12 2.06-24.44-6.19-7.32-17.12-8.24-24.44-2.06-7.31 6.18-8.24 17.12-2.06 24.44 6.18 7.31 17.12 8.24 24.44 2.06Z" fill="url(#grad1)"/>
          <path d="M189.47 48.68h29.64c14.82 0 25.51 10 25.51 25.39S233.94 99.8 219.11 99.8h-18.27v29.99h-11.37V48.68Zm27.8 41.25c9.77 0 15.74-6.09 15.74-15.85s-5.97-15.51-15.63-15.51h-16.54v31.37h16.43Z" fill="#06112c"/>
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

      <li class="nav-item">
        <a href="https://mpos.payglobe.it/Payglobe/portale/" target="_blank" class="nav-link">
          <i class="fas fa-rocket"></i>
          <span>Back Office MPOS</span>
        </a>
      </li>
    </ul>

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
        <a href="/logout.php" class="logout-btn">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </a>
      </div>
    </div>
  </nav>

  <!-- Main Content Area -->
  <div class="main-content">
