<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <title>PayGlobe Merchant Console v3.0</title>
  <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no' name='viewport' />

  <!-- Modern Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- Legacy CSS for compatibility -->
  <link rel="stylesheet" type="text/css" media="screen" href="../css/none/jquery-ui-1.8.21.custom.css" />
  <link rel="stylesheet" type="text/css" media="screen" href="css/ui.jqgrid.css" />
  <link rel="stylesheet" type="text/css" media="screen" href="css/jquery.multiselect.css" />
  <link href="assets/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Toastify for notifications -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

  <!-- Legacy Scripts -->
  <script type="text/ecmascript" src="js/jquery.min.js"></script>
  <script type="text/ecmascript" src="js/jquery-ui.min.js"></script>
  <script type="text/ecmascript" src="js/jquery.jqGrid.js"></script>
  <script type="text/ecmascript" src="js/jquery.multiselect.js"></script>
  <script type="text/ecmascript" src="js/grid.locale-en.js"></script>
  <script src="assets/js/core/popper.min.js"></script>
  <script src="assets/js/core/bootstrap.min.js"></script>
  <script src="assets/js/plugins/perfect-scrollbar.jquery.min.js"></script>
  <script src="assets/js/plugins/chartjs.min.js"></script>
  <script src="assets/js/plugins/bootstrap-notify.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<style>
:root {
  --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --primary-blue: #667eea;
  --primary-purple: #764ba2;
  --accent-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  --shadow-sm: 0 2px 4px rgba(0,0,0,0.08);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
  --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15), 0 10px 10px rgba(0, 0, 0, 0.04);
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --radius-xl: 24px;
  --space-xs: 4px;
  --space-sm: 8px;
  --space-md: 16px;
  --space-lg: 24px;
  --space-xl: 32px;
  --font-sans: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  --transition-base: 250ms cubic-bezier(0.4, 0, 0.2, 1);
  --bg-body: #f8f9fc;
  --bg-card: #ffffff;
  --text-primary: #1a202c;
  --text-secondary: #718096;
  --border-color: #e2e8f0;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: var(--font-sans);
  background: var(--bg-body);
  color: var(--text-primary);
  line-height: 1.6;
  margin: 0;
  padding: 0;
  overflow-x: hidden;
}

/* Top Navigation Bar */
.top-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 70px;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  box-shadow: var(--shadow-md);
  z-index: 1000;
  display: flex;
  align-items: center;
  padding: 0 var(--space-lg);
  border-bottom: 1px solid var(--border-color);
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  text-decoration: none;
  color: var(--text-primary);
  font-weight: 700;
  font-size: 1.25rem;
  margin-right: var(--space-xl);
}

.navbar-brand svg {
  height: 40px;
  width: auto;
}

.navbar-brand .version {
  font-size: 0.75rem;
  color: var(--text-secondary);
  background: var(--primary-gradient);
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 600;
}

/* Main Navigation Menu */
.nav-menu {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: var(--space-sm);
  flex: 1;
}

.nav-item {
  position: relative;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  color: var(--text-secondary);
  text-decoration: none;
  border-radius: var(--radius-sm);
  font-weight: 500;
  font-size: 0.9rem;
  transition: all var(--transition-base);
  white-space: nowrap;
}

.nav-link:hover {
  background: rgba(102, 126, 234, 0.1);
  color: var(--primary-blue);
  transform: translateY(-2px);
}

.nav-link i {
  font-size: 1.1rem;
  width: 20px;
  text-align: center;
}

/* Dropdown Menu */
.nav-item.dropdown.open .dropdown-menu {
  display: block;
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  min-width: 220px;
  padding: var(--space-sm);
  display: none;
  opacity: 0;
  transform: translateY(-10px);
  transition: all var(--transition-base);
  border: 1px solid var(--border-color);
  z-index: 10001;
  pointer-events: none;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  color: var(--text-primary);
  text-decoration: none;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  transition: all var(--transition-base);
}

.dropdown-item:hover {
  background: var(--primary-gradient);
  color: white;
  transform: translateX(4px);
}

.dropdown-item i {
  width: 20px;
  text-align: center;
  font-size: 0.95rem;
}

/* User Menu */
.navbar-right {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  margin-left: auto;
}

.user-menu {
  position: relative;
}

.user-button {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  background: var(--primary-gradient);
  color: white;
  border: none;
  border-radius: var(--radius-lg);
  cursor: pointer;
  font-family: var(--font-sans);
  font-weight: 600;
  font-size: 0.9rem;
  transition: all var(--transition-base);
  box-shadow: var(--shadow-sm);
}

.user-button:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.user-menu:hover .user-dropdown {
  display: block;
  opacity: 1;
  transform: translateY(0);
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  min-width: 240px;
  padding: var(--space-md);
  display: none;
  opacity: 0;
  transform: translateY(-10px);
  transition: all var(--transition-base);
  border: 1px solid var(--border-color);
}

.user-info {
  padding-bottom: var(--space-md);
  border-bottom: 1px solid var(--border-color);
  margin-bottom: var(--space-sm);
}

.user-info-item {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-xs) 0;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.user-info-item i {
  color: var(--primary-blue);
  width: 18px;
}

.user-info-item strong {
  color: var(--text-primary);
  margin-left: auto;
}

/* Main Content Area */
.main-content {
  margin-top: 70px;
  padding: var(--space-xl);
  min-height: calc(100vh - 70px);
}

/* Mobile Responsive */
.mobile-menu-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--text-primary);
  cursor: pointer;
  padding: var(--space-sm);
  margin-left: auto;
}

@media (max-width: 992px) {
  .mobile-menu-toggle {
    display: block;
  }

  .nav-menu {
    position: fixed;
    top: 70px;
    left: -100%;
    width: 280px;
    height: calc(100vh - 70px);
    background: white;
    flex-direction: column;
    padding: var(--space-lg);
    box-shadow: var(--shadow-xl);
    transition: left var(--transition-base);
    overflow-y: auto;
  }

  .nav-menu.active {
    left: 0;
  }

  .navbar-right {
    margin-left: 0;
  }

  .dropdown-menu {
    position: static;
    display: none;
    box-shadow: none;
    border: none;
    border-left: 2px solid var(--primary-blue);
    margin-left: var(--space-lg);
    margin-top: var(--space-sm);
  }

  .nav-item.dropdown.active .dropdown-menu {
    display: block;
  }
}

/* Page Header */
.page-header {
  background: white;
  border-radius: var(--radius-lg);
  padding: var(--space-xl);
  margin-bottom: var(--space-xl);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: var(--space-sm);
}

.page-subtitle {
  color: var(--text-secondary);
  font-size: 1rem;
}

/* Cards */
.card {
  background: white;
  border-radius: var(--radius-lg);
  padding: var(--space-xl);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  transition: all var(--transition-base);
}

.card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

/* Animation */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.main-content > * {
  animation: fadeInUp 0.6s ease-out;
}

/* Legacy compatibility */
.ui-jqgrid .ui-pg-table td {
  color: white;
}
</style>

</head>

<body>
  <!-- Top Navigation Bar -->
  <nav class="top-navbar">
    <a href="" class="navbar-brand">
      <svg width="120" height="40" preserveAspectRatio="xMidYMid meet" viewBox="0 0 761.13 177.1" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#488dec;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#9a1bf1;stop-opacity:1" />
          </linearGradient>
        </defs>
        <g>
          <path d="M431.25 46.38c20.22 0 30.1 10 30.1 10l-8.73 10.34s-7.7-6.78-21.03-6.78c-20.68 0-34.01 17.12-34.01 34.7 0 14.36 9.65 22.52 22.4 22.52s22.29-8.85 22.29-8.85l1.84-9.19h-12.64L434 86.37h25.85l-8.39 42.97h-12.64l.8-4.02c.34-1.72.81-3.45.81-3.45h-.23s-8.5 8.85-23.32 8.85c-18.73 0-34.58-13.44-34.58-35.16 0-26.66 21.83-49.18 48.94-49.18" fill="#06112c"/>
          <path fill="#06112c" d="M484.11 47.76h14.82l-13.44 68.83h35.27l-2.65 12.75h-49.86l15.86-81.58z"/>
          <path d="M577.86 46.39c22.4 0 37.23 14.71 37.23 35.04 0 26.65-23.78 49.29-48.37 49.29-22.52 0-37.11-15.05-37.11-35.85 0-26.2 23.44-48.49 48.26-48.49m-11.03 70.77c16.31 0 32.97-15.51 32.97-34.81 0-13.33-9.08-22.4-22.06-22.4-16.66 0-32.97 15.28-32.97 34.12 0 13.67 9.08 23.09 22.06 23.09" fill="#06112c"/>
          <path d="M189.47 48.68h29.64c14.82 0 25.51 10 25.51 25.39S233.94 99.8 219.11 99.8h-18.27v29.99h-11.37V48.68Zm27.8 41.25c9.77 0 15.74-6.09 15.74-15.85s-5.97-15.51-15.63-15.51h-16.54v31.37h16.43Z" fill="#06112c"/>
          <path d="M103.24 30.59c7.31-6.18 8.23-17.12 2.06-24.44-6.19-7.32-17.12-8.24-24.44-2.06-7.31 6.18-8.24 17.12-2.06 24.44 6.18 7.31 17.12 8.24 24.44 2.06Z" fill="url(#grad1)"/>
          <path d="M139.67 142.35c28.74-41.92 13.35-99.92-20.92-105.49-41.38-6.95-66.36 65.65-58.42 65.09 4.6-.32 16.89-20 31.61-32.54 5.54-4.7 15.12-11.34 21.88-8.72 6.4 2.54 9.62 12.45 10.5 19.65 2.1 16.81-4.75 32.71-9.57 38.9-13.07 16.81-31.6 26.31-46.84 27.13-24.12 1.29-45.56-14.91-52.06-37.52-6.55-22.71 2.28-50.29 26.54-68.58 3.45-2.59 5.56-3.81 5.55-3.83 0 0-33.33 11.61-44.62 47.17-18.78 58.97 45.13 114.34 106.28 85.75 5.61-2.72 15.19-9.32 23.24-18.33-.03.04-.07.07-.1.11.15-.16.31-.33.45-.49 2.36-2.67 4.51-5.44 6.45-8.28" fill="url(#grad1)"/>
        </g>
      </svg>
      <span class="version">MC5</span>
    </a>

    <button class="mobile-menu-toggle" id="mobileMenuToggle">
      <i class="fas fa-bars"></i>
    </button>

    <ul class="nav-menu" id="navMenu">
<?php
error_reporting(0);
if ($role == "Admin") {
?>
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

      <li class="nav-item dropdown">
        <a href="#" class="nav-link">
          <i class="fas fa-credit-card"></i>
          <span>Transazioni</span>
          <i class="fas fa-chevron-down" style="margin-left: 4px; font-size: 0.7rem;"></i>
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
<?php
}
if ($role == "Reader" || $role == "Admin") {
?>
      <li class="nav-item">
        <a href="internazionale-ecom-4.php" class="nav-link">
          <i class="fas fa-shopping-cart"></i>
          <span>eCommerce</span>
        </a>
      </li>
<?php } ?>

      <li class="nav-item">
        <a href="https://mpos.payglobe.it/Payglobe/portale/" target="_blank" class="nav-link">
          <i class="fas fa-rocket"></i>
          <span>Back Office MPOS</span>
        </a>
      </li>
    </ul>

    <div class="navbar-right">
      <div class="user-menu">
        <button class="user-button">
          <i class="fas fa-user-circle"></i>
          <span><?php echo $user['Username'] ?? 'User'; ?></span>
          <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
        </button>
        <div class="user-dropdown">
          <div class="user-info">
            <div class="user-info-item">
              <i class="fas fa-user"></i>
              <span>Username:</span>
              <strong><?php echo $user['Username'] ?? 'N/A'; ?></strong>
            </div>
            <div class="user-info-item">
              <i class="fas fa-shield-alt"></i>
              <span>Ruolo:</span>
              <strong><?php echo $role ?? 'N/A'; ?></strong>
            </div>
            <div class="user-info-item">
              <i class="fas fa-users"></i>
              <span>Gruppo:</span>
              <strong><?php echo $application ?? 'N/A'; ?></strong>
            </div>
          </div>
          <a href="/logout.php" class="dropdown-item" style="color: #f5576c;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="main-content">

<script>
// Dropdown menu handler - click based instead of hover for better UX
document.addEventListener('DOMContentLoaded', function() {
  const dropdownToggles = document.querySelectorAll('.nav-item.dropdown > .nav-link');

  dropdownToggles.forEach(toggle => {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      const dropdown = this.parentElement;
      const isOpen = dropdown.classList.contains('open');

      // Close all other dropdowns
      document.querySelectorAll('.nav-item.dropdown.open').forEach(d => {
        if (d !== dropdown) {
          d.classList.remove('open');
        }
      });

      // Toggle current dropdown
      dropdown.classList.toggle('open');
    });
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-item.dropdown')) {
      document.querySelectorAll('.nav-item.dropdown.open').forEach(d => {
        d.classList.remove('open');
      });
    }
  });

  // Prevent dropdown from closing when clicking inside dropdown menu
  document.querySelectorAll('.dropdown-menu').forEach(menu => {
    menu.addEventListener('click', function(e) {
      e.stopPropagation();
    });
  });
});
</script>
