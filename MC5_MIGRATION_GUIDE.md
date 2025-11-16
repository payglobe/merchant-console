# MC5 Migration Guide - Nuova Console Moderna

## 🎯 Obiettivo

Creare **mc5** come versione moderna della console, mantenendo **mc4 completamente intoccabile** e funzionante.

---

## 📁 Struttura Directory

```
/var/www/html/{application}/
├── mc4/                    # ❌ INTOCCABILE - Versione vecchia
│   ├── index.php
│   ├── menu.php
│   ├── stores.php
│   ├── assets/
│   ├── css/
│   ├── js/
│   └── scripts/
│
└── mc5/                    # ✅ NUOVA - Versione moderna
    ├── index.php           # Copiato da mc4, poi modernizzato
    ├── menu.php            # Copiato da mc4, poi modernizzato
    ├── stores.php          # Copiato da mc4, poi modernizzato
    ├── assets/
    │   ├── css/
    │   │   ├── modern-dashboard.css      # ⭐ NUOVO
    │   │   ├── modern-components.css     # ⭐ NUOVO
    │   │   ├── modern-animations.css     # ⭐ NUOVO
    │   │   └── (vecchi CSS per fallback)
    │   └── js/
    │       ├── modern-dashboard.js       # ⭐ NUOVO
    │       └── (vecchi JS)
    ├── css/                # CSS originali mc4 (backup)
    ├── js/                 # JS originali mc4 (backup)
    └── scripts/            # ✅ STESSO backend PHP di mc4
        └── (symlink a ../mc4/scripts/ oppure copia)
```

---

## 🚀 Step-by-Step Migration

### ✅ FASE 1: Setup Iniziale (COMPLETATO)

```bash
# 1. Creare mc5 copiando mc4 (FATTO su juice)
cd /var/www/html/juice
cp -r mc4 mc5

# Verificare
ls -la | grep mc
# Output:
# drwxr-xr-x mc4  ← Intoccabile
# drwxr-xr-x mc5  ← Nuova versione
```

### 🔄 FASE 2: Condivisione Scripts Backend PHP

**Opzione A: Symlink (Raccomandato)**
```bash
cd /var/www/html/juice/mc5
rm -rf scripts
ln -s ../mc4/scripts scripts

# Verifica
ls -la scripts
# Output: scripts -> ../mc4/scripts
```

**Opzione B: Shared Scripts Folder**
```bash
# Spostare scripts fuori da mc4
cd /var/www/html/juice
mv mc4/scripts ./shared-scripts

# Symlink da mc4
cd mc4
ln -s ../shared-scripts scripts

# Symlink da mc5
cd ../mc5
ln -s ../shared-scripts scripts
```

**Opzione C: Copia (Solo per test)**
```bash
# SOLO per ambiente di test/sviluppo
# In produzione usare symlink!
cp -r mc4/scripts mc5/scripts
```

### 🎨 FASE 3: Creare CSS Moderni

#### 3.1 Struttura CSS in mc5

```bash
cd /var/www/html/juice/mc5/assets

# Creare nuova struttura CSS
mkdir -p css/modern
```

#### 3.2 File CSS da Creare

**File: `assets/css/modern/variables.css`**
```css
:root {
  /* Primary Gradient - Come dashboard pgbe2 */
  --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --primary-gradient-hover: linear-gradient(135deg, #5568d3 0%, #6941a0 100%);

  /* Colors */
  --primary-blue: #667eea;
  --primary-purple: #764ba2;
  --accent-blue: #488dec;
  --accent-purple: #9a1bf1;

  /* Neutrals */
  --bg-light: #f7f8fa;
  --bg-white: #ffffff;
  --text-dark: #2d3748;
  --text-medium: #4a5568;
  --text-muted: #718096;
  --text-light: #a0aec0;

  /* Status Colors */
  --success: #48bb78;
  --success-light: #c6f6d5;
  --error: #f56565;
  --error-light: #fed7d7;
  --warning: #ed8936;
  --warning-light: #feebc8;
  --info: #4299e1;
  --info-light: #bee3f8;

  /* Shadows */
  --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.08);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
  --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15), 0 10px 10px rgba(0, 0, 0, 0.04);
  --shadow-gradient: 0 10px 40px rgba(102, 126, 234, 0.3);

  /* Border Radius */
  --radius-sm: 6px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --radius-xl: 24px;
  --radius-full: 9999px;

  /* Spacing */
  --space-xs: 0.25rem;
  --space-sm: 0.5rem;
  --space-md: 1rem;
  --space-lg: 1.5rem;
  --space-xl: 2rem;
  --space-2xl: 3rem;

  /* Typography */
  --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-size-xs: 0.75rem;
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;
  --font-size-xl: 1.25rem;
  --font-size-2xl: 1.5rem;
  --font-size-3xl: 1.875rem;

  /* Transitions */
  --transition-fast: 150ms ease-in-out;
  --transition-base: 250ms ease-in-out;
  --transition-slow: 350ms ease-in-out;
}
```

**File: `assets/css/modern/components.css`**
```css
/* Import Google Font */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

/* Base Overrides */
body {
  font-family: var(--font-sans);
  background: var(--bg-light);
  color: var(--text-dark);
}

/* Cards Moderne */
.card-modern {
  background: var(--bg-white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  transition: all var(--transition-base);
  border: none;
  overflow: hidden;
}

.card-modern:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-xl);
}

.card-gradient-header {
  background: var(--primary-gradient);
  color: white;
  padding: var(--space-xl);
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.card-gradient-header h3,
.card-gradient-header h4,
.card-gradient-header h5 {
  color: white;
  margin: 0;
  font-weight: 600;
}

.card-body-modern {
  padding: var(--space-xl);
}

/* Sidebar Moderna */
.sidebar {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  box-shadow: 4px 0 20px rgba(0, 0, 0, 0.05);
  border-right: 1px solid rgba(0, 0, 0, 0.05);
}

.sidebar .nav li a {
  border-radius: var(--radius-md);
  margin: var(--space-xs) var(--space-md);
  padding: var(--space-md) var(--space-lg);
  transition: all var(--transition-base);
  position: relative;
  overflow: hidden;
  color: var(--text-medium);
}

.sidebar .nav li a::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  width: 4px;
  height: 100%;
  background: var(--primary-gradient);
  transform: scaleY(0);
  transition: transform var(--transition-base);
}

.sidebar .nav li a:hover::before,
.sidebar .nav li.active a::before {
  transform: scaleY(1);
}

.sidebar .nav li a:hover {
  background: linear-gradient(90deg, rgba(102,126,234,0.08) 0%, transparent 100%);
  transform: translateX(4px);
  color: var(--primary-blue);
}

.sidebar .nav li.active a {
  background: linear-gradient(90deg, rgba(102,126,234,0.12) 0%, transparent 100%);
  color: var(--primary-purple);
  font-weight: 600;
}

/* Buttons Moderni */
.btn-gradient-primary {
  background: var(--primary-gradient);
  border: none;
  color: white;
  padding: var(--space-md) var(--space-xl);
  border-radius: var(--radius-md);
  font-weight: 600;
  transition: all var(--transition-base);
  box-shadow: var(--shadow-sm);
}

.btn-gradient-primary:hover {
  background: var(--primary-gradient-hover);
  transform: translateY(-2px);
  box-shadow: var(--shadow-gradient);
  color: white;
}

.btn-gradient-secondary {
  background: white;
  border: 2px solid;
  border-image: var(--primary-gradient) 1;
  color: var(--primary-purple);
  padding: var(--space-md) var(--space-xl);
  border-radius: var(--radius-md);
  font-weight: 600;
  transition: all var(--transition-base);
}

.btn-gradient-secondary:hover {
  background: var(--primary-gradient);
  color: white;
  transform: translateY(-2px);
}

/* Form Inputs Moderni */
.form-control-modern {
  border: 2px solid #e2e8f0;
  border-radius: var(--radius-md);
  padding: var(--space-md) var(--space-lg);
  transition: all var(--transition-base);
  font-size: var(--font-size-base);
}

.form-control-modern:focus {
  border-color: var(--primary-blue);
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  outline: none;
}

.form-label-gradient {
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-sm);
}

/* Badges */
.badge-gradient {
  background: var(--primary-gradient);
  color: white;
  padding: var(--space-xs) var(--space-md);
  border-radius: var(--radius-full);
  font-size: var(--font-size-xs);
  font-weight: 600;
}

.terminal-id-badge {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  padding: var(--space-xs) var(--space-sm);
  border-radius: var(--radius-sm);
  font-family: 'Courier New', monospace;
  font-size: var(--font-size-sm);
  font-weight: 600;
}
```

**File: `assets/css/modern/datatables.css`**
```css
/* DataTables Modernization */

/* Wrapper */
.dataTables_wrapper {
  background: white;
  border-radius: var(--radius-lg);
  padding: var(--space-lg);
  box-shadow: var(--shadow-md);
}

/* Header con Gradient */
.dataTables_wrapper .dataTables_filter {
  background: var(--primary-gradient);
  padding: var(--space-lg);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-lg);
}

.dataTables_wrapper .dataTables_filter label {
  color: white;
  font-weight: 600;
  margin-bottom: var(--space-sm);
}

.dataTables_wrapper .dataTables_filter input {
  border: none;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border-radius: var(--radius-md);
  padding: var(--space-md) var(--space-lg);
  backdrop-filter: blur(10px);
  width: 300px;
  transition: all var(--transition-base);
}

.dataTables_wrapper .dataTables_filter input::placeholder {
  color: rgba(255, 255, 255, 0.7);
}

.dataTables_wrapper .dataTables_filter input:focus {
  background: rgba(255, 255, 255, 0.3);
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.2);
}

/* Table Styling */
table.dataTable {
  border-collapse: separate;
  border-spacing: 0;
}

table.dataTable thead th {
  background: linear-gradient(to bottom, #f7fafc 0%, #edf2f7 100%);
  color: var(--text-dark);
  font-weight: 700;
  text-transform: uppercase;
  font-size: var(--font-size-xs);
  letter-spacing: 0.05em;
  padding: var(--space-lg);
  border-bottom: 2px solid var(--primary-blue);
}

table.dataTable tbody tr {
  transition: all var(--transition-fast);
  border-bottom: 1px solid #e2e8f0;
}

table.dataTable tbody tr:hover {
  background: linear-gradient(90deg, rgba(102,126,234,0.05) 0%, transparent 100%);
  transform: scale(1.005);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

table.dataTable tbody td {
  padding: var(--space-lg);
  vertical-align: middle;
}

/* Paginazione Moderna */
.dataTables_wrapper .dataTables_paginate {
  margin-top: var(--space-lg);
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: var(--radius-md);
  padding: var(--space-sm) var(--space-md);
  margin: 0 var(--space-xs);
  transition: all var(--transition-base);
  color: var(--text-medium) !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
  background: var(--primary-gradient);
  color: white !important;
  border-color: transparent;
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
  background: var(--primary-gradient) !important;
  color: white !important;
  border: none;
  box-shadow: var(--shadow-gradient);
}

.dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* Buttons Export */
.dt-buttons {
  margin-bottom: var(--space-lg);
  display: flex;
  gap: var(--space-sm);
}

.dt-button {
  background: white !important;
  border: 2px solid #e2e8f0 !important;
  color: var(--text-dark) !important;
  padding: var(--space-sm) var(--space-lg) !important;
  border-radius: var(--radius-md) !important;
  font-weight: 600 !important;
  transition: all var(--transition-base) !important;
  font-size: var(--font-size-sm) !important;
}

.dt-button:hover {
  background: var(--primary-gradient) !important;
  color: white !important;
  border-color: transparent !important;
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.dt-button i {
  margin-right: var(--space-xs);
}

/* Length Select */
.dataTables_wrapper .dataTables_length select {
  border: 2px solid #e2e8f0;
  border-radius: var(--radius-md);
  padding: var(--space-sm) var(--space-md);
  color: var(--text-dark);
  transition: all var(--transition-base);
}

.dataTables_wrapper .dataTables_length select:focus {
  border-color: var(--primary-blue);
  outline: none;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Info Text */
.dataTables_wrapper .dataTables_info {
  color: var(--text-muted);
  font-size: var(--font-size-sm);
  padding: var(--space-md) 0;
}

/* Loading Overlay */
.dataTables_wrapper .dataTables_processing {
  background: rgba(255, 255, 255, 0.95);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xl);
  padding: var(--space-xl);
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--primary-purple);
}
```

**File: `assets/css/modern/animations.css`**
```css
/* Keyframes Animations */

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

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes shimmer {
  0% { background-position: -1000px 0; }
  100% { background-position: 1000px 0; }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Animation Classes */

.fade-in-up {
  animation: fadeInUp 0.6s ease-out;
}

.fade-in {
  animation: fadeIn 0.4s ease-out;
}

.slide-in-right {
  animation: slideInRight 0.5s ease-out;
}

/* Stagger Delays */
.card-modern:nth-child(1) { animation-delay: 0.1s; }
.card-modern:nth-child(2) { animation-delay: 0.2s; }
.card-modern:nth-child(3) { animation-delay: 0.3s; }
.card-modern:nth-child(4) { animation-delay: 0.4s; }

/* Skeleton Loaders */
.skeleton {
  background: linear-gradient(
    90deg,
    #f0f0f0 25%,
    #e0e0e0 50%,
    #f0f0f0 75%
  );
  background-size: 1000px 100%;
  animation: shimmer 2s infinite;
  border-radius: var(--radius-sm);
}

.skeleton-text {
  height: 1rem;
  margin-bottom: var(--space-sm);
}

.skeleton-title {
  height: 1.5rem;
  width: 60%;
  margin-bottom: var(--space-md);
}

.skeleton-button {
  height: 2.5rem;
  width: 120px;
  border-radius: var(--radius-md);
}

.skeleton-table-row {
  height: 3rem;
  margin-bottom: var(--space-xs);
}

/* Loading Spinner */
.spinner {
  border: 3px solid rgba(102, 126, 234, 0.2);
  border-top-color: var(--primary-blue);
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 0.8s linear infinite;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border-width: 2px;
}

/* Hover Effects */
.hover-lift {
  transition: all var(--transition-base);
}

.hover-lift:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.hover-glow {
  transition: all var(--transition-base);
}

.hover-glow:hover {
  box-shadow: var(--shadow-gradient);
}

/* Content Transitions */
.content {
  animation: fadeInUp 0.6s ease-out;
}

/* Toast Notifications */
.toast-modern {
  background: var(--primary-gradient);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-xl);
  padding: var(--space-lg);
  color: white;
  font-weight: 600;
}
```

### 📝 FASE 4: Modificare menu.php in mc5

**File: `/var/www/html/juice/mc5/menu.php`**

Aggiungere prima dei vecchi CSS:

```php
<?php
// ... codice authentication esistente ...
?>

<!DOCTYPE html>
<html lang="en" class="perfect-scrollbar-off nav-close">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <title>Merchant Console v3.0</title>
  <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no' name='viewport' />

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- ⭐ NUOVO: CSS Moderni MC5 -->
  <link rel="stylesheet" href="assets/css/modern/variables.css">
  <link rel="stylesheet" href="assets/css/modern/components.css">
  <link rel="stylesheet" href="assets/css/modern/datatables.css">
  <link rel="stylesheet" href="assets/css/modern/animations.css">

  <!-- CSS Vecchi (fallback compatibilità) -->
  <link rel="stylesheet" type="text/css" href="../css/none/jquery-ui-1.8.21.custom.css" />
  <link rel="stylesheet" type="text/css" href="css/ui.jqgrid.css" />
  <link rel="stylesheet" type="text/css" href="css/jquery.multiselect.css" />
  <link href="assets/css/bootstrap.min.css" rel="stylesheet" />

  <!-- JavaScript (stesso di mc4) -->
  <script type="text/javascript" src="js/jquery.min.js"></script>
  <script type="text/javascript" src="js/jquery-ui.min.js"></script>
  <script type="text/javascript" src="js/jquery.jqGrid.js"></script>
  <script type="text/javascript" src="js/jquery.multiselect.js"></script>
  <script type="text/javascript" src="js/grid.locale-en.js"></script>
  <script src="assets/js/core/popper.min.js"></script>
  <script src="assets/js/core/bootstrap.min.js"></script>
  <script src="assets/js/plugins/perfect-scrollbar.jquery.min.js"></script>
  <script src="assets/js/plugins/chartjs.min.js"></script>
  <script src="assets/js/plugins/bootstrap-notify.js"></script>
  <script src="assets/js/paper-dashboard.min.js?v=2.0.0"></script>

  <!-- ⭐ NUOVO: Toast Notifications -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

  <style>
    /* Apply modern styles */
    .navbar-brand small {
      background: var(--primary-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-weight: 700;
    }

    /* Override vecchi stili Paper Dashboard */
    .card {
      border-radius: var(--radius-lg) !important;
      box-shadow: var(--shadow-md) !important;
    }

    .card-header {
      background: var(--primary-gradient) !important;
      border-radius: var(--radius-lg) var(--radius-lg) 0 0 !important;
      color: white !important;
    }

    /* Sidebar moderna */
    .sidebar {
      background: rgba(255, 255, 255, 0.98) !important;
      backdrop-filter: blur(20px);
    }
  </style>
</head>

<body class="">
  <div class="wrapper">
    <!-- Sidebar (stessa struttura, nuovi stili) -->
    <div class="sidebar" data-color="white" data-active-color="danger">
      <div class="logo">
        <a href="" class="simple-text logo-big">
          <div class="logo-image-big">
            <img src="payglobe.png" style="filter: drop-shadow(0 4px 12px rgba(0,0,0,0.1));">
          </div>
        </a>
      </div>

      <div class="sidebar-wrapper">
        <ul class="nav">
          <li class="<?= basename($_SERVER['PHP_SELF']) == 'stores.php' ? 'active' : '' ?>">
            <a href="stores.php">
              <i class="fa-solid fa-store"></i>
              <p>Negozi</p>
            </a>
          </li>
          <li class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <a href="index.php">
              <i class="fa-solid fa-credit-card"></i>
              <p>Transazioni</p>
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Main Panel -->
    <div class="main-panel">
      <!-- Navbar -->
      <nav class="navbar navbar-expand-lg navbar-absolute fixed-top navbar-transparent">
        <div class="container-fluid">
          <div class="navbar-wrapper">
            <div class="navbar-toggle">
              <button type="button" class="navbar-toggler">
                <span class="navbar-toggler-bar bar1"></span>
                <span class="navbar-toggler-bar bar2"></span>
                <span class="navbar-toggler-bar bar3"></span>
              </button>
            </div>
            <a class="navbar-brand" href="#">
              ✨ Merchant Console <small class="text-muted">v3.0 MC5</small>
            </a>
          </div>

          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navigation">
            <span class="navbar-toggler-bar navbar-kebab"></span>
            <span class="navbar-toggler-bar navbar-kebab"></span>
            <span class="navbar-toggler-bar navbar-kebab"></span>
          </button>

          <div class="collapse navbar-collapse justify-content-end" id="navigation">
            <ul class="navbar-nav">
              <li class="nav-item btn-rotate dropdown">
                <a class="nav-link dropdown-toggle" href="" id="navbarDropdownMenuLink" data-toggle="dropdown">
                  <i class="fa-solid fa-user-circle"></i>
                  <p>
                    <span class="d-lg-none d-md-block"><?php echo "{$user['Username']}/{$role}" ?></span>
                  </p>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                  <a class="dropdown-item" href="#">
                    <i class="fa-solid fa-user"></i> <?php echo "Username: {$user['Username']}" ?>
                  </a>
                  <a class="dropdown-item" href="#">
                    <i class="fa-solid fa-store"></i> <?php echo "Punto Vendita: {$role}" ?>
                  </a>
                  <a class="dropdown-item" href="#">
                    <i class="fa-solid fa-layer-group"></i> <?php echo "Gruppo: {$application}" ?>
                  </a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item text-danger" href="/logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                  </a>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </nav>
      <!-- End Navbar -->
```

### 🔐 FASE 5: Aggiornare Login.php per Routing mc5

**File: `/var/www/html/login.php`** (modificare routing)

```php
<?php
// ... codice esistente auth Cognito ...

if(empty($error)) {
    $application = encrypt_decrypt('decrypt', getCognitoAttribute($user['UserAttributes'], "custom:application"));

    if (!empty($application)) {
        // ⭐ NUOVO: Feature flag per mc5
        $useMC5 = isset($_COOKIE['use_mc5']) && $_COOKIE['use_mc5'] === 'true';

        if ($useMC5) {
            // Redirect to MC5 (nuova versione)
            header('Location: /' . $application . '/mc5/index.php');
        } else {
            // Redirect to MC4 (vecchia versione - default)
            header('Location: /' . $application . '/mc4/index.php');
        }
    } else {
        header('Location: /login.php?msg=Session(timeout!)&app=' . $application);
    }
    exit;
}
?>
```

**Feature Flag Toggle** (opzionale - per A/B testing):

Aggiungere alla fine di login.php:

```html
<div style="position: fixed; bottom: 20px; right: 20px; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
  <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
    <input type="checkbox" id="toggleMC5" onclick="toggleVersion()" <?= isset($_COOKIE['use_mc5']) && $_COOKIE['use_mc5'] === 'true' ? 'checked' : '' ?>>
    <span style="font-weight: 600;">✨ Usa Nuova Console (MC5)</span>
  </label>
</div>

<script>
function toggleVersion() {
  const useMC5 = document.getElementById('toggleMC5').checked;
  document.cookie = `use_mc5=${useMC5}; path=/; max-age=31536000`; // 1 anno

  if (useMC5) {
    showToast('Nuova console abilitata! Effettua login.', 'success');
  } else {
    showToast('Tornato alla console classica.', 'info');
  }
}

function showToast(message, type) {
  // Toast notification
  const colors = {
    success: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    info: 'linear-gradient(135deg, #4299e1 0%, #667eea 100%)'
  };

  const div = document.createElement('div');
  div.textContent = message;
  div.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${colors[type]};
    color: white;
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    font-weight: 600;
    z-index: 9999;
    animation: slideInRight 0.3s ease-out;
  `;

  document.body.appendChild(div);

  setTimeout(() => {
    div.style.animation = 'fadeOut 0.3s ease-out';
    setTimeout(() => div.remove(), 300);
  }, 3000);
}
</script>

<style>
@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes fadeOut {
  to {
    opacity: 0;
    transform: translateY(-20px);
  }
}
</style>
```

---

## 🧪 Testing

### Checklist Pre-Deployment

- [ ] mc4 rimane intoccabile e funzionante
- [ ] mc5 carica correttamente (URL: `/juice/mc5/index.php`)
- [ ] Scripts backend funzionano (symlink o copia)
- [ ] CSS moderni caricano
- [ ] DataTables rende con nuovo stile
- [ ] Sidebar moderna funziona
- [ ] Navigazione tra pagine OK
- [ ] Login routing funziona (mc4 vs mc5)
- [ ] Feature flag toggle funziona
- [ ] Logout reindirizza correttamente

### Test URLs

```
# MC4 (vecchia - intoccabile)
http://pgbe.example.com/juice/mc4/index.php

# MC5 (nuova - moderna)
http://pgbe.example.com/juice/mc5/index.php
```

---

## 🔄 Rollback Plan

Se mc5 ha problemi:

1. **Disattivare feature flag**:
   ```javascript
   document.cookie = 'use_mc5=false; path=/';
   ```

2. **Modificare login.php**:
   ```php
   // Forzare mc4
   header('Location: /' . $application . '/mc4/index.php');
   ```

3. **mc4 rimane sempre funzionante** - nessun impatto!

---

## 📊 Deployment Strategy

### Fase 1: Pilot (juice)
- ✅ Creare mc5 su juice
- ✅ Test interno team
- ✅ Fix issues
- ✅ Validazione UX

### Fase 2: Beta Users
- Feature flag abilitato per utenti selezionati
- Raccolta feedback
- Iterazioni

### Fase 3: Gradual Rollout
```
Settimana 1: juice (10% utenti con flag)
Settimana 2: juice (50% utenti)
Settimana 3: juice (100% utenti)
Settimana 4: medgroup (pilot)
...
```

### Fase 4: Full Migration
- mc5 diventa default
- mc4 mantenu to come fallback per 6 mesi
- Poi deprecazione mc4

---

## ✅ Vantaggi Approccio mc5

| Vantaggio | Descrizione |
|-----------|-------------|
| **Zero Risk** | mc4 intoccabile, sempre funzionante |
| **Easy Rollback** | Cookie/routing change immediato |
| **A/B Testing** | Confronto diretto mc4 vs mc5 |
| **Gradual Migration** | Utente per utente |
| **Parallel Development** | Fix mc4 urgenti mentre sviluppo mc5 |
| **User Choice** | Toggle tra vecchia/nuova |

---

## 🎯 Next Steps

1. ✅ **Creare CSS moderni** (assets/css/modern/)
2. ✅ **Modificare menu.php** (aggiungere link CSS)
3. ✅ **Testare mc5** su juice
4. ⏳ **Iterare** su design feedback
5. ⏳ **Rollout graduale**

---

**Pronto per iniziare? mc4 rimane INTOCCABILE! 🛡️**
