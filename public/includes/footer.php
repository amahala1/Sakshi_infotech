<?php
if (!defined('BASE_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/config/config.php';
}
?>
<footer class="main-footer">
  <div class="footer-top">
    <div class="footer-col">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:15px;">
        <img src="<?= asset('images/logo.png') ?>" alt="<?= APP_NAME ?>" style="height:46px;width:auto;">
      </div>
      <p style="font-size:0.88rem;color:#475569;line-height:1.6;margin-bottom:15px;">
        <?= APP_TAGLINE ?>. Jaipur's leading supplier for commercial laptops, desktop computers, laser toners, copier paper, and office stationery supplies.
      </p>
      <div style="font-size:0.85rem;color:#64748b;">
        <p><i class="fas fa-map-marker-alt" style="color:var(--primary);margin-right:8px;"></i> <?= APP_ADDRESS ?></p>
        <p style="margin-top:6px;"><i class="fas fa-phone-alt" style="color:var(--primary);margin-right:8px;"></i> <?= APP_PHONE ?></p>
        <p style="margin-top:6px;"><i class="fas fa-envelope" style="color:var(--primary);margin-right:8px;"></i> <?= APP_EMAIL ?></p>
        <p style="margin-top:6px;"><i class="fas fa-receipt" style="color:#059669;margin-right:8px;"></i> GSTIN: <strong><?= APP_GSTIN ?></strong></p>
      </div>
    </div>

    <div class="footer-col">
      <h4>Computer Hardware</h4>
      <ul>
        <li><a href="<?= url('shop.php?category_slug=laptops-desktops') ?>">Laptops &amp; Desktops</a></li>
        <li><a href="<?= url('shop.php?category_slug=keyboards-mice') ?>">Keyboards &amp; Mice</a></li>
        <li><a href="<?= url('shop.php?category_slug=printers-scanners') ?>">Printers &amp; Scanners</a></li>
        <li><a href="<?= url('shop.php?category_slug=storage-memory') ?>">NVMe SSDs &amp; RAM</a></li>
        <li><a href="<?= url('shop.php?category_slug=networking-cables') ?>">Wi-Fi Routers &amp; Cables</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Office Stationery</h4>
      <ul>
        <li><a href="<?= url('shop.php?category_slug=paper-supplies') ?>">JK Copier Paper 75/80 GSM</a></li>
        <li><a href="<?= url('shop.php?category_slug=toners-cartridges') ?>">Laser Toners &amp; Ink Bottles</a></li>
        <li><a href="<?= url('shop.php?category_slug=registers-files') ?>">Hard Bound Registers &amp; Files</a></li>
        <li><a href="<?= url('shop.php?category_slug=pens-markers') ?>">Casio Calculators &amp; Pens</a></li>
        <li><a href="<?= url('shop.php') ?>">Browse All Office Supplies</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Customer Support &amp; Store</h4>
      <p style="font-size:0.85rem;color:#64748b;margin-bottom:12px;line-height:1.5;">
        <?= APP_ADDRESS ?><br>
        <strong>Helpline:</strong> <?= APP_PHONE ?><br>
        <strong>Email:</strong> <?= APP_EMAIL ?>
      </p>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <a href="<?= url('shop.php') ?>" class="btn-primary-si" style="padding:9px 15px;font-size:0.82rem;justify-content:center;">
          <i class="fas fa-shopping-bag"></i> Browse Products
        </a>
        <a href="<?= url('admin/') ?>" class="btn-outline-si" style="padding:9px 15px;font-size:0.82rem;justify-content:center;">
          <i class="fas fa-user-shield"></i> Admin &amp; Staff Login
        </a>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <div style="max-width:var(--page-max-width);margin:0 auto;padding:0 var(--page-padding);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
      <p>© <?= date('Y') ?> <strong><?= APP_NAME ?></strong>. All Rights Reserved. Indian GST Compliant E-Commerce Platform.</p>
      <p style="color:#64748b;font-size:0.8rem;">Authorized Commercial IT Hardware &amp; Stationery Distribution Hub</p>
    </div>
  </div>
</footer>

<script>
  window.__BASE_URL__ = "<?= url() ?>";
</script>
<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
