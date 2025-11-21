  </div>
  <!-- End Main Content -->

  <!-- Modern Footer -->
  <footer style="
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
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
        </div>

        <!-- Quick Links -->
        <div>
          <h4 style="font-weight: var(--font-bold); font-size: var(--text-base); margin-bottom: var(--space-4); color: white;">
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
      </div>
    </div>
  </footer>

</body>
</html>
