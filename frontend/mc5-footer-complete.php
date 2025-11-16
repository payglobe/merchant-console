  </div>
  <!-- End Main Content -->

  <!-- Modern Footer -->
  <footer style="
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 32px 24px;
    margin-top: 48px;
    border-radius: 16px 16px 0 0;
    box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.1);
  ">
    <div style="
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    ">
      <div style="display: flex; align-items: center; gap: 16px;">
        <img src="/logohome.png" style="width: 60px; height: auto; filter: brightness(0) invert(1);">
        <div>
          <div style="font-weight: 700; font-size: 1.1rem;">PayGlobe Merchant Console</div>
          <div style="font-size: 0.85rem; opacity: 0.9;">Powered by MC5 Technology</div>
        </div>
      </div>
      <div style="text-align: right;">
        <div style="font-size: 0.85rem; opacity: 0.9;">© 2018-2025 PayGlobe</div>
        <div style="font-size: 0.85rem; opacity: 0.9;">All rights reserved</div>
      </div>
    </div>
  </footer>

<style>
/* Modern Card Styling */
.content .card {
  border: 1px solid #e2e8f0;
  border-radius: 16px !important;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05) !important;
  transition: all 250ms cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  background: white;
}

.content .card:hover {
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1) !important;
  transform: translateY(-4px);
}

.content .card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  color: white !important;
  border: none !important;
  padding: 20px 24px !important;
  font-weight: 600 !important;
}

.content .card-header.card-header-rose {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
}

.content .card-body {
  padding: 24px !important;
}

.content .card-text small {
  color: #718096;
  font-size: 0.9rem;
}

/* Modern Tabs */
.nav-tabs {
  border-bottom: 2px solid rgba(255, 255, 255, 0.2) !important;
  gap: 8px;
}

.nav-tabs .nav-item {
  margin-bottom: 0 !important;
}

.nav-tabs .nav-link {
  background: rgba(255, 255, 255, 0.1) !important;
  border: none !important;
  color: rgba(255, 255, 255, 0.8) !important;
  border-radius: 8px 8px 0 0 !important;
  padding: 12px 24px !important;
  font-weight: 600 !important;
  transition: all 250ms !important;
}

.nav-tabs .nav-link:hover {
  background: rgba(255, 255, 255, 0.2) !important;
  color: white !important;
}

.nav-tabs .nav-link.active {
  background: white !important;
  color: #667eea !important;
  border-bottom: 3px solid #667eea !important;
}

/* Modern Form Elements */
.content input[type="text"],
.content input[type="date"],
.content select {
  border: 2px solid #e2e8f0 !important;
  border-radius: 12px !important;
  padding: 12px 16px !important;
  font-family: "Inter", sans-serif !important;
  font-size: 0.95rem !important;
  transition: all 250ms !important;
  background: white !important;
}

.content input[type="text"]:focus,
.content input[type="date"]:focus,
.content select:focus {
  border-color: #667eea !important;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
  outline: none !important;
}

.content input[type="text"]::placeholder {
  color: #a0aec0;
}

/* Modern Buttons */
.content .btn {
  border-radius: 12px !important;
  padding: 12px 24px !important;
  font-weight: 600 !important;
  font-family: "Inter", sans-serif !important;
  transition: all 250ms cubic-bezier(0.4, 0, 0.2, 1) !important;
  border: none !important;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}

.content .btn-outline-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  color: white !important;
  border: none !important;
}

.content .btn-outline-primary:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3) !important;
}

.content .btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  color: white !important;
}

.content .btn-success {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
  color: white !important;
}

.content .btn-danger {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
  color: white !important;
}

/* Modern Row Spacing */
.content .row {
  margin-bottom: 24px;
}

/* Modern Container */
.content .container-fluid {
  padding: 0 !important;
  max-width: 1400px;
  margin: 0 auto;
}

.content {
  animation: fadeInUp 0.6s ease-out;
}

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

/* Modern Chart Containers */
#chart-container,
#chart-container2,
#chart-container3 {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  margin-bottom: 24px;
}

/* Modern Icons in Cards */
.content .card-body i.fa,
.content .card-body i.fas {
  color: #667eea;
  font-size: 1.5rem;
  margin-right: 8px;
}

/* Fieldset Modern Styling */
.content fieldset {
  border: 2px solid #e2e8f0 !important;
  border-radius: 16px !important;
  padding: 24px !important;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
}

/* jqGrid Modern Styling */
.ui-jqgrid {
  border-radius: 16px !important;
  border: 1px solid #e2e8f0 !important;
  overflow: hidden !important;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05) !important;
}

.ui-jqgrid-hdiv {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  border: none !important;
}

.ui-jqgrid-htable thead th {
  background: transparent !important;
  color: white !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
  font-family: "Inter", sans-serif !important;
  font-weight: 600 !important;
  padding: 16px 12px !important;
}

.ui-jqgrid-bdiv {
  border: none !important;
}

.ui-jqgrid tr.ui-row-ltr td {
  border-color: #e2e8f0 !important;
  padding: 14px 12px !important;
  font-family: "Inter", sans-serif !important;
}

.ui-jqgrid tr.ui-row-ltr:hover {
  background: linear-gradient(90deg, rgba(102,126,234,0.05) 0%, transparent 100%) !important;
}

/* Pagination Modern Style */
.ui-jqgrid-pager {
  background: white !important;
  border: none !important;
  border-top: 1px solid #e2e8f0 !important;
}

.ui-pg-table td {
  font-family: "Inter", sans-serif !important;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .content .card {
    margin-bottom: 16px;
  }

  .content .row {
    margin-left: 0 !important;
    margin-right: 0 !important;
  }

  footer > div {
    flex-direction: column;
    text-align: center;
  }

  footer > div > div:last-child {
    text-align: center !important;
  }
}
</style>

<script>
// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const navMenu = document.getElementById('navMenu');

  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', function() {
      navMenu.classList.toggle('active');
      const icon = this.querySelector('i');
      if (navMenu.classList.contains('active')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
      } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
      }
    });
  }

  // Handle dropdown clicks on mobile
  const dropdownItems = document.querySelectorAll('.nav-item.dropdown > .nav-link');
  dropdownItems.forEach(item => {
    item.addEventListener('click', function(e) {
      if (window.innerWidth <= 992) {
        e.preventDefault();
        this.parentElement.classList.toggle('active');
      }
    });
  });

  // Close mobile menu when clicking outside
  document.addEventListener('click', function(e) {
    if (window.innerWidth <= 992 && mobileMenuToggle && navMenu) {
      if (!navMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
        navMenu.classList.remove('active');
        const icon = mobileMenuToggle.querySelector('i');
        if (icon) {
          icon.classList.remove('fa-times');
          icon.classList.add('fa-bars');
        }
      }
    }
  });
});

// Enhanced jqGrid styling when loaded
if (typeof jQuery !== 'undefined') {
  $(document).ready(function() {
    // Add modern classes to jqGrid tables after they load
    setTimeout(function() {
      $('.ui-jqgrid').addClass('modern-grid');
      $('.ui-jqgrid-htable').addClass('modern-grid-header');
      $('.ui-jqgrid-bdiv').addClass('modern-grid-body');

      // Animate cards on scroll
      const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
      };

      const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }
        });
      }, observerOptions);

      document.querySelectorAll('.card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(card);
      });
    }, 500);
  });
}

// Modern toast notifications (if Toastify is available)
if (typeof Toastify !== 'undefined') {
  window.showToast = function(message, type = 'success') {
    const backgrounds = {
      success: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
      error: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
      info: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      warning: 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'
    };

    Toastify({
      text: message,
      duration: 3000,
      gravity: "top",
      position: "right",
      style: {
        background: backgrounds[type] || backgrounds.info,
        borderRadius: "12px",
        padding: "16px 24px",
        fontFamily: "Inter, sans-serif",
        fontWeight: "600",
        boxShadow: "0 10px 15px rgba(0, 0, 0, 0.2)"
      }
    }).showToast();
  };
}
</script>

</body>
</html>
