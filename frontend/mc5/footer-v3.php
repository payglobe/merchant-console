  </div>
  <!-- End Main Content -->

  <!-- Modern Footer -->
  <footer style="
    background: var(--gradient-dark);
    color: white;
    padding: var(--space-8) var(--space-6);
    margin-top: var(--space-10);
    border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
    box-shadow: var(--shadow-2xl);
  ">
    <div class="container">
      <div style="
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-8);
        align-items: center;
      ">
        <!-- Logo & Info -->
        <div>
          <div style="display: flex; align-items: center; gap: var(--space-4); margin-bottom: var(--space-4);">
            <img src="/logohome.png" style="width: 60px; height: auto; filter: brightness(0) invert(1);">
            <div>
              <div style="font-weight: var(--font-bold); font-size: var(--text-lg);">PayGlobe</div>
              <div style="font-size: var(--text-sm); opacity: 0.8;">Merchant Console MC5 v3.0</div>
            </div>
          </div>
          <p style="font-size: var(--text-sm); opacity: 0.8; line-height: 1.6;">
            Piattaforma di gestione merchant moderna e potente. Design system ultra-moderno senza Bootstrap.
          </p>
        </div>

        <!-- Quick Links -->
        <div>
          <h4 style="font-weight: var(--font-bold); font-size: var(--text-base); margin-bottom: var(--space-4);">
            Link Rapidi
          </h4>
          <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="margin-bottom: var(--space-2);">
              <a href="index.php" style="color: white; opacity: 0.8; text-decoration: none; font-size: var(--text-sm); transition: opacity var(--transition-base);" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                <i class="fas fa-chart-line" style="width: 20px;"></i> Dashboard
              </a>
            </li>
            <li style="margin-bottom: var(--space-2);">
              <a href="stores.php" style="color: white; opacity: 0.8; text-decoration: none; font-size: var(--text-sm); transition: opacity var(--transition-base);" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                <i class="fas fa-store" style="width: 20px;"></i> Negozi
              </a>
            </li>
            <li style="margin-bottom: var(--space-2);">
              <a href="tutte-5.php" style="color: white; opacity: 0.8; text-decoration: none; font-size: var(--text-sm); transition: opacity var(--transition-base);" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                <i class="fas fa-credit-card" style="width: 20px;"></i> Transazioni
              </a>
            </li>
          </ul>
        </div>

        <!-- Tech Stack -->
        <div>
          <h4 style="font-weight: var(--font-bold); font-size: var(--text-base); margin-bottom: var(--space-4);">
            Tech Stack
          </h4>
          <div style="display: flex; flex-wrap: wrap; gap: var(--space-2);">
            <span style="
              background: rgba(255, 255, 255, 0.1);
              backdrop-filter: blur(10px);
              padding: var(--space-1) var(--space-3);
              border-radius: var(--radius-full);
              font-size: var(--text-xs);
              font-weight: var(--font-semibold);
            ">Alpine.js 3.x</span>
            <span style="
              background: rgba(255, 255, 255, 0.1);
              backdrop-filter: blur(10px);
              padding: var(--space-1) var(--space-3);
              border-radius: var(--radius-full);
              font-size: var(--text-xs);
              font-weight: var(--font-semibold);
            ">ApexCharts</span>
            <span style="
              background: rgba(255, 255, 255, 0.1);
              backdrop-filter: blur(10px);
              padding: var(--space-1) var(--space-3);
              border-radius: var(--radius-full);
              font-size: var(--text-xs);
              font-weight: var(--font-semibold);
            ">CSS Grid</span>
            <span style="
              background: rgba(255, 255, 255, 0.1);
              backdrop-filter: blur(10px);
              padding: var(--space-1) var(--space-3);
              border-radius: var(--radius-full);
              font-size: var(--text-xs);
              font-weight: var(--font-semibold);
            ">Glassmorphism</span>
            <span style="
              background: rgba(255, 255, 255, 0.1);
              backdrop-filter: blur(10px);
              padding: var(--space-1) var(--space-3);
              border-radius: var(--radius-full);
              font-size: var(--text-xs);
              font-weight: var(--font-semibold);
            ">NO Bootstrap</span>
          </div>
        </div>
      </div>

      <!-- Copyright -->
      <div style="
        margin-top: var(--space-8);
        padding-top: var(--space-6);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
        font-size: var(--text-sm);
        opacity: 0.8;
      ">
        <p style="margin: 0;">
          © 2018-2025 <strong>PayGlobe</strong> - All rights reserved.
        </p>
        <p style="margin: var(--space-2) 0 0 0;">
          Powered by <strong>MC5 v3.0 Design System</strong> 🚀
        </p>
      </div>
    </div>
  </footer>

</body>
</html>
