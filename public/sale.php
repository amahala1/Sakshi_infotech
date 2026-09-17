<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Product.php';
require_once BASE_PATH . '/src/Order.php';

$pageTitle = APP_NAME . " - Commercial Computers, Laptops, Printers & Stationery Solutions";

// Fetch data
$allCategories = Product::getCategoriesWithSubcategories();
$featuredProducts = Product::getProducts(['featured' => 1, 'limit' => 8]);
$computerProducts = Product::getProducts(['type' => 'computer', 'limit' => 4]);
$stationeryProducts = Product::getProducts(['type' => 'stationery', 'limit' => 4]);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Dual Split Navigation Track Bar (MegaCompu Dual Highway) -->
<div class="dual-split-bar">
  <a href="<?= url('shop.php?type=computer') ?>" class="split-track-card split-track-it">
    <div style="display:flex;align-items:center;gap:14px;">
      <div style="width:48px;height:48px;border-radius:12px;background:rgba(56,189,248,0.15);color:#38bdf8;display:flex;align-items:center;justify-content:center;font-size:1.4rem;border:1px solid rgba(56,189,248,0.3);flex-shrink:0;">
        <i class="fas fa-laptop-code"></i>
      </div>
      <div>
        <span style="font-size:0.72rem;text-transform:uppercase;color:#38bdf8;font-weight:800;letter-spacing:1px;">TRACK 01 // ENTERPRISE IT</span>
        <h3 style="font-size:1.15rem;font-weight:900;color:#ffffff;margin-top:2px;">IT Solutions &amp; Hardware Gadgets</h3>
        <p style="font-size:0.8rem;color:#94a3b8;margin:0;">Commercial Laptops, Mini PCs, Printers, NVMe SSDs &amp; Wi-Fi 6</p>
      </div>
    </div>
    <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:#38bdf8;font-size:0.9rem;flex-shrink:0;">
      <i class="fas fa-arrow-right"></i>
    </div>
  </a>

  <a href="<?= url('shop.php?type=stationery') ?>" class="split-track-card split-track-stationery">
    <div style="display:flex;align-items:center;gap:14px;">
      <div style="width:48px;height:48px;border-radius:12px;background:#ecfdf5;color:#059669;display:flex;align-items:center;justify-content:center;font-size:1.4rem;border:1px solid #a7f3d0;flex-shrink:0;">
        <i class="fas fa-pen-ruler"></i>
      </div>
      <div>
        <span style="font-size:0.72rem;text-transform:uppercase;color:#059669;font-weight:800;letter-spacing:1px;">TRACK 02 // OFFICE CREATIVE</span>
        <h3 style="font-size:1.15rem;font-weight:900;color:#0f172a;margin-top:2px;">Office &amp; Creative Stationery</h3>
        <p style="font-size:0.8rem;color:#64748b;margin:0;">JK Copier Paper 75/80 GSM, Toners, Box Files &amp; Calculators</p>
      </div>
    </div>
    <div style="width:36px;height:36px;border-radius:50%;background:#ecfdf5;display:flex;align-items:center;justify-content:center;color:#059669;font-size:0.9rem;flex-shrink:0;">
      <i class="fas fa-arrow-right"></i>
    </div>
  </a>
</div>

<!-- 1. MegaCompu-Inspired Hero Layout (Left Vertical Sidebar + Bento Grid Showcase) -->
<section class="hero-megacompu-section" style="margin-top:14px;">
  <div class="hero-megacompu-container">
    
    <!-- Left Permanent Categories Menu with Nested Subcategories (MegaCompu Style) -->
    <aside class="hero-sidebar-cats">
      <div class="hero-sidebar-head">
        <i class="fas fa-bars"></i>
        <span>SHOP BY CATEGORY</span>
      </div>
      <ul class="hero-sidebar-menu">
        <?php foreach ($allCategories as $c): ?>
          <li class="mega-cat-item">
            <div class="hero-sidebar-link-wrap">
              <a href="<?= url('shop.php?category_slug=' . urlencode($c['slug'])) ?>" class="hero-sidebar-link">
                <span class="hero-cat-icon"><i class="fas <?= htmlspecialchars($c['icon']) ?>"></i></span>
                <span class="hero-cat-name"><?= htmlspecialchars($c['name']) ?></span>
                <span class="hero-cat-count"><?= $c['product_count'] ?></span>
              </a>
              <?php if (!empty($c['subcategories'])): ?>
                <button type="button" class="subcat-toggle-btn" onclick="toggleSubmenu(this, 'subcat-menu-<?= $c['id'] ?>')" title="Toggle Sub-Categories">
                  <i class="fas fa-chevron-down"></i>
                </button>
              <?php endif; ?>
            </div>

            <?php if (!empty($c['subcategories'])): ?>
              <ul class="hero-sidebar-subcat-menu" id="subcat-menu-<?= $c['id'] ?>">
                <?php foreach ($c['subcategories'] as $sc): ?>
                  <li>
                    <a href="<?= url('shop.php?subcategory_slug=' . urlencode($sc['slug'])) ?>">
                      <span><?= htmlspecialchars($sc['name']) ?></span>
                      <span class="subcat-pill"><?= $sc['product_count'] ?></span>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="hero-sidebar-banner">
        <i class="fas fa-file-invoice-dollar" style="color:#059669;font-size:1.4rem;"></i>
        <div>
          <strong>100% GST Tax Credit</strong>
          <p>Instant B2B GST credit invoice with every order</p>
        </div>
      </div>
    </aside>

    <!-- Center/Right Bento Hero Showcase -->
    <div class="hero-bento-grid">
      
      <!-- Bento Card 1: Deep Tech + Stationery Synergy Hero Banner -->
      <div class="bento-cell bento-tech-hero">
        <div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
            <span style="background:rgba(56,189,248,0.2);color:#38bdf8;font-size:0.75rem;font-weight:800;padding:4px 10px;border-radius:20px;border:1px solid rgba(56,189,248,0.4);">
              <i class="fas fa-microchip"></i> Authorized Tech &amp; Supplies Partner
            </span>
            <span style="background:rgba(16,185,129,0.2);color:#34d399;font-size:0.75rem;font-weight:800;padding:4px 10px;border-radius:20px;border:1px solid rgba(16,185,129,0.4);">
              <i class="fas fa-bolt"></i> Same-Day Dispatch
            </span>
          </div>
          <h1 style="font-size:2.1rem;font-weight:900;line-height:1.2;margin-bottom:12px;color:#ffffff;letter-spacing:-0.5px;">
            High-Performance IT &amp; <span style="color:#38bdf8;">Creative Office Supplies</span>
          </h1>
          <p style="font-size:0.9rem;color:#cbd5e1;line-height:1.6;margin-bottom:20px;max-width:500px;">
            Commercial Lenovo ThinkPads, mini PCs, Crucial NVMe SSDs, genuine JK copier paper reams, and executive hard-bound registers delivered directly from our Jaipur distribution hub.
          </p>
        </div>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:auto;">
          <a href="<?= url('shop.php') ?>" class="btn-neon-cta">
            <i class="fas fa-shopping-bag"></i> Explore All Categories &rarr;
          </a>
          <a href="<?= url('shop.php?type=computer') ?>" style="color:#ffffff;background:rgba(255,255,255,0.12);padding:12px 18px;border-radius:12px;font-size:0.88rem;font-weight:700;text-decoration:none;border:1px solid rgba(255,255,255,0.2);">
            Browse Computers
          </a>
        </div>
      </div>

      <!-- Right Column Bento Cards Stack -->
      <div style="display:flex;flex-direction:column;gap:18px;justify-content:space-between;">
        
        <!-- Bento Card 2: Featured Tech Deal of the Week -->
        <div class="bento-cell bento-card-deal" style="padding:18px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span style="font-size:0.7rem;text-transform:uppercase;color:var(--primary);font-weight:800;letter-spacing:1px;">TOP TECH DEAL</span>
            <span style="background:#fee2e2;color:#b91c1c;font-size:0.75rem;font-weight:900;padding:3px 8px;border-radius:6px;">13% OFF</span>
          </div>
          <div style="display:flex;align-items:center;gap:15px;">
            <div style="width:85px;height:75px;background:#f8fafc;border-radius:8px;padding:6px;display:flex;align-items:center;justify-content:center;border:1px solid #e2e8f0;flex-shrink:0;">
              <img src="<?= product_image_url('laptop-lenovo.png') ?>" alt="ThinkPad E14" style="max-height:100%;max-width:100%;object-fit:contain;">
            </div>
            <div>
              <h4 style="font-size:0.92rem;font-weight:800;color:#0f172a;line-height:1.3;margin-bottom:4px;">Lenovo ThinkPad E14 i5</h4>
              <div style="font-size:1.15rem;font-weight:900;color:#0f172a;">₹59,990.00</div>
              <span style="font-size:0.72rem;color:#059669;font-weight:700;">Incl. 18% GST Credit</span>
            </div>
          </div>
          <button class="btn-primary-si btn-ajax-add-cart" data-product-id="1" style="width:100%;padding:8px;font-size:0.82rem;justify-content:center;margin-top:12px;">
            <i class="fas fa-cart-plus"></i> Add to Cart Now
          </button>
        </div>

        <!-- Bento Card 3: Creative Stationery Carton Deal -->
        <div class="bento-cell bento-card-stationery" style="padding:18px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span style="font-size:0.7rem;text-transform:uppercase;color:#059669;font-weight:800;letter-spacing:1px;">BULK INSTITUTION DEAL</span>
            <span style="background:#dcfce7;color:#15803d;font-size:0.75rem;font-weight:900;padding:3px 8px;border-radius:6px;">21% OFF</span>
          </div>
          <div style="display:flex;align-items:center;gap:15px;">
            <div style="width:85px;height:75px;background:#ffffff;border-radius:8px;padding:6px;display:flex;align-items:center;justify-content:center;border:1px solid #bbf7d0;flex-shrink:0;">
              <img src="<?= product_image_url('jk-copier-box.png') ?>" alt="JK Copier Carton" style="max-height:100%;max-width:100%;object-fit:contain;">
            </div>
            <div>
              <h4 style="font-size:0.92rem;font-weight:800;color:#0f172a;line-height:1.3;margin-bottom:4px;">JK Copier A4 (Box 5 Reams)</h4>
              <div style="font-size:1.15rem;font-weight:900;color:#059669;">₹1,499.00</div>
              <span style="font-size:0.72rem;color:#059669;font-weight:700;">Incl. 12% GST Credit</span>
            </div>
          </div>
          <button class="btn-primary-si btn-ajax-add-cart" data-product-id="11" style="background:#059669;width:100%;padding:8px;font-size:0.82rem;justify-content:center;margin-top:12px;">
            <i class="fas fa-cart-plus"></i> Add Bulk Carton
          </button>
        </div>

      </div>

    </div>

  </div>
</section>

<!-- Sidebar Subcategory Accordion Toggle Script -->
<script>
function toggleSubmenu(btn, menuId) {
  const menu = document.getElementById(menuId);
  if (!menu) return;
  const isOpen = menu.classList.contains('open');
  if (isOpen) {
    menu.classList.remove('open');
    btn.innerHTML = '<i class="fas fa-chevron-down"></i>';
  } else {
    menu.classList.add('open');
    btn.innerHTML = '<i class="fas fa-chevron-up"></i>';
  }
}
</script>

<!-- 2. Features Perks Strip -->
<div class="features-strip">
  <div class="features-grid">
    <div class="feature-item">
      <div class="feature-icon-box" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-truck-fast"></i></div>
      <div class="feature-info">
        <h4>Priority Dispatch</h4>
        <p>Verified tracking &amp; fast transit</p>
      </div>
    </div>
    <div class="feature-item">
      <div class="feature-icon-box" style="background:#ecfdf5;color:#059669;"><i class="fas fa-shield-halved"></i></div>
      <div class="feature-info">
        <h4>100% Genuine Brands</h4>
        <p>Direct manufacturer warranties</p>
      </div>
    </div>
    <div class="feature-item">
      <div class="feature-icon-box" style="background:#fef3c7;color:#d97706;"><i class="fas fa-file-invoice-dollar"></i></div>
      <div class="feature-info">
        <h4>GST Invoicing &amp; Credit</h4>
        <p>Claim up to 18% Input Tax Credit</p>
      </div>
    </div>
    <div class="feature-item">
      <div class="feature-icon-box" style="background:#e0f2fe;color:#0284c7;"><i class="fas fa-box-open"></i></div>
      <div class="feature-info">
        <h4>Safe &amp; Secure Delivery</h4>
        <p>Insured &amp; tamper-proof transit</p>
      </div>
    </div>
  </div>
</div>

<!-- 3. Popular Categories Grid (6 Clean Columns) -->
<div class="section-wrapper">
  <div class="section-header">
    <div>
      <span style="font-size:0.78rem;font-weight:800;color:var(--primary);text-transform:uppercase;letter-spacing:1px;">EXPLORE DEPARTMENTS</span>
      <h2 class="section-title">Popular Categories</h2>
    </div>
    <a href="<?= url('shop.php') ?>" style="font-size:0.88rem;font-weight:800;color:var(--primary);display:flex;align-items:center;gap:6px;text-decoration:none;">
      View All Categories <i class="fas fa-arrow-right"></i>
    </a>
  </div>

  <div class="popular-cats-grid">
    <?php foreach (array_slice($allCategories, 0, 6) as $c): ?>
      <a href="<?= url('shop.php?category_slug=' . urlencode($c['slug'])) ?>" style="background:#ffffff;border:1px solid var(--border-color);border-radius:14px;padding:20px 14px;text-align:center;transition:var(--transition);display:flex;flex-direction:column;align-items:center;justify-content:center;text-decoration:none;box-shadow:var(--shadow-sm);height:100%;" onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='var(--border-color)';this.style.transform='translateY(0)'">
        <div style="width:52px;height:52px;border-radius:50%;background:var(--primary-light);color:var(--primary);margin-bottom:12px;display:flex;align-items:center;justify-content:center;font-size:1.35rem;">
          <i class="fas <?= htmlspecialchars($c['icon']) ?>"></i>
        </div>
        <h4 style="font-size:0.9rem;font-weight:800;color:var(--text-main);margin-bottom:4px;"><?= htmlspecialchars($c['name']) ?></h4>
        <span style="font-size:0.75rem;color:var(--text-muted);font-weight:700;"><?= $c['product_count'] ?> Products</span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- 4. Featured Hot Deals Products -->
<div class="section-wrapper">
  <div class="section-header">
    <div>
      <span style="font-size:0.78rem;font-weight:800;color:#ef4444;text-transform:uppercase;letter-spacing:1px;"><i class="fas fa-fire"></i> SPECIAL OFFERS</span>
      <h2 class="section-title">Hot Deals &amp; Best Sellers</h2>
    </div>
    <a href="<?= url('shop.php?featured=1') ?>" style="font-size:0.88rem;font-weight:800;color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:6px;">
      See All Deals <i class="fas fa-arrow-right"></i>
    </a>
  </div>

  <div class="product-grid">
    <?php foreach ($featuredProducts as $prod): 
      $discountPercent = round((($prod['regular_price'] - $prod['sale_price']) / $prod['regular_price']) * 100);
    ?>
      <div class="product-card">
        <div class="product-img-wrap">
          <?php if ($discountPercent > 0): ?>
            <span class="badge-discount"><?= $discountPercent ?>% OFF</span>
          <?php endif; ?>
          <span class="badge-stock <?= $prod['stock_qty'] > 3 ? 'badge-in-stock' : 'badge-low-stock' ?>">
            <?= $prod['stock_qty'] > 0 ? ($prod['stock_qty'] > 3 ? 'In Stock' : 'Only ' . $prod['stock_qty'] . ' left') : 'Out of Stock' ?>
          </span>
          <a href="<?= url('product.php?id=' . $prod['id']) ?>" style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;">
            <img src="<?= product_image_url($prod['image_url']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy">
          </a>
        </div>

        <div class="product-body">
          <div class="product-meta-row">
            <span class="product-brand-tag"><?= htmlspecialchars($prod['brand'] ?: $prod['category_name']) ?></span>
            <?php if (!empty($prod['subcategory_name'])): ?>
              <span class="product-subcat-badge" title="<?= htmlspecialchars($prod['subcategory_name']) ?>"><?= htmlspecialchars($prod['subcategory_name']) ?></span>
            <?php endif; ?>
            <div class="product-rating">
              <i class="fas fa-star"></i>
              <span>4.9</span>
            </div>
          </div>

          <a href="<?= url('product.php?id=' . $prod['id']) ?>" class="product-title" title="<?= htmlspecialchars($prod['name']) ?>">
            <?= htmlspecialchars($prod['name']) ?>
          </a>

          <div class="product-pricing">
            <div class="price-main-row">
              <span class="sale-price">₹<?= number_format($prod['sale_price'], 2) ?></span>
              <?php if ($prod['regular_price'] > $prod['sale_price']): ?>
                <span class="regular-price">₹<?= number_format($prod['regular_price'], 2) ?></span>
              <?php endif; ?>
            </div>
            <span class="gst-note">Incl. <?= (int)$prod['gst_rate'] ?>% GST • Input Tax Credit</span>
          </div>

          <button type="button" class="btn-add-cart btn-ajax-add-cart" data-product-id="<?= $prod['id'] ?>">
            <i class="fas fa-cart-plus"></i> Add to Cart
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- 5. Computer Hardware Section -->
<div class="section-wrapper" style="background:linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);padding:32px 24px;border-radius:20px;margin:36px auto;border:1px solid #bfdbfe;">
  <div class="section-header" style="border-color:#bfdbfe;">
    <div>
      <span style="font-size:0.78rem;font-weight:800;color:var(--primary);text-transform:uppercase;"><i class="fas fa-laptop"></i> IT HARDWARE</span>
      <h2 class="section-title">Computers, Laptops &amp; Peripherals</h2>
    </div>
    <a href="<?= url('shop.php?type=computer') ?>" class="btn-primary-si" style="padding:8px 18px;font-size:0.85rem;">
      Explore All Computers <i class="fas fa-arrow-right"></i>
    </a>
  </div>

  <div class="product-grid">
    <?php foreach ($computerProducts as $prod): 
      $discount = round((($prod['regular_price'] - $prod['sale_price']) / $prod['regular_price']) * 100);
    ?>
      <div class="product-card">
        <div class="product-img-wrap">
          <?php if ($discount > 0): ?>
            <span class="badge-discount"><?= $discount ?>% OFF</span>
          <?php endif; ?>
          <span class="badge-stock <?= $prod['stock_qty'] > 3 ? 'badge-in-stock' : 'badge-low-stock' ?>">
            <?= $prod['stock_qty'] > 0 ? ($prod['stock_qty'] > 3 ? 'In Stock' : 'Only ' . $prod['stock_qty'] . ' left') : 'Out of Stock' ?>
          </span>
          <a href="<?= url('product.php?id=' . $prod['id']) ?>" style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;">
            <img src="<?= product_image_url($prod['image_url']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy">
          </a>
        </div>
        <div class="product-body">
          <div class="product-meta-row">
            <span class="product-brand-tag"><?= htmlspecialchars($prod['brand'] ?: 'Hardware') ?></span>
            <?php if (!empty($prod['subcategory_name'])): ?>
              <span class="product-subcat-badge" title="<?= htmlspecialchars($prod['subcategory_name']) ?>"><?= htmlspecialchars($prod['subcategory_name']) ?></span>
            <?php endif; ?>
            <div class="product-rating"><i class="fas fa-star"></i> <span>4.8</span></div>
          </div>
          <a href="<?= url('product.php?id=' . $prod['id']) ?>" class="product-title" title="<?= htmlspecialchars($prod['name']) ?>"><?= htmlspecialchars($prod['name']) ?></a>
          <div class="product-pricing">
            <div class="price-main-row">
              <span class="sale-price">₹<?= number_format($prod['sale_price'], 2) ?></span>
              <?php if ($prod['regular_price'] > $prod['sale_price']): ?>
                <span class="regular-price">₹<?= number_format($prod['regular_price'], 2) ?></span>
              <?php endif; ?>
            </div>
            <span class="gst-note">Incl. <?= (int)$prod['gst_rate'] ?>% GST • Input Tax Credit</span>
          </div>
          <button type="button" class="btn-add-cart btn-ajax-add-cart" data-product-id="<?= $prod['id'] ?>">
            <i class="fas fa-cart-plus"></i> Add to Cart
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- 6. Office Stationery & Toners Section -->
<div class="section-wrapper">
  <div class="section-header">
    <div>
      <span style="font-size:0.78rem;font-weight:800;color:#059669;text-transform:uppercase;"><i class="fas fa-print"></i> OFFICE SUPPLIES</span>
      <h2 class="section-title">Stationery, Copier Paper &amp; Toners</h2>
    </div>
    <a href="<?= url('shop.php?type=stationery') ?>" class="btn-outline-si" style="padding:8px 18px;font-size:0.85rem;">
      Explore Stationery <i class="fas fa-arrow-right"></i>
    </a>
  </div>

  <div class="product-grid">
    <?php foreach ($stationeryProducts as $prod): 
      $discount = round((($prod['regular_price'] - $prod['sale_price']) / $prod['regular_price']) * 100);
    ?>
      <div class="product-card">
        <div class="product-img-wrap">
          <?php if ($discount > 0): ?>
            <span class="badge-discount"><?= $discount ?>% OFF</span>
          <?php endif; ?>
          <span class="badge-stock <?= $prod['stock_qty'] > 3 ? 'badge-in-stock' : 'badge-low-stock' ?>">
            <?= $prod['stock_qty'] > 0 ? ($prod['stock_qty'] > 3 ? 'In Stock' : 'Only ' . $prod['stock_qty'] . ' left') : 'Out of Stock' ?>
          </span>
          <a href="<?= url('product.php?id=' . $prod['id']) ?>" style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;">
            <img src="<?= product_image_url($prod['image_url']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy">
          </a>
        </div>
        <div class="product-body">
          <div class="product-meta-row">
            <span class="product-brand-tag" style="background:#ecfdf5;color:#059669;"><?= htmlspecialchars($prod['brand'] ?: $prod['category_name']) ?></span>
            <?php if (!empty($prod['subcategory_name'])): ?>
              <span class="product-subcat-badge" style="background:#ecfdf5;color:#059669;" title="<?= htmlspecialchars($prod['subcategory_name']) ?>"><?= htmlspecialchars($prod['subcategory_name']) ?></span>
            <?php endif; ?>
            <div class="product-rating"><i class="fas fa-star"></i> <span>4.9</span></div>
          </div>
          <a href="<?= url('product.php?id=' . $prod['id']) ?>" class="product-title" title="<?= htmlspecialchars($prod['name']) ?>"><?= htmlspecialchars($prod['name']) ?></a>
          <div class="product-pricing">
            <div class="price-main-row">
              <span class="sale-price">₹<?= number_format($prod['sale_price'], 2) ?></span>
              <?php if ($prod['regular_price'] > $prod['sale_price']): ?>
                <span class="regular-price">₹<?= number_format($prod['regular_price'], 2) ?></span>
              <?php endif; ?>
            </div>
            <span class="gst-note">Incl. <?= (int)$prod['gst_rate'] ?>% GST • Input Tax Credit</span>
          </div>
          <button type="button" class="btn-add-cart btn-ajax-add-cart" data-product-id="<?= $prod['id'] ?>">
            <i class="fas fa-cart-plus"></i> Add to Cart
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- 7. Corporate & Bulk Purchase Inquiries Callout -->
<div class="section-wrapper" style="background:linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);color:#0f172a;padding:32px 28px;border-radius:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;border:1px solid #bfdbfe;box-shadow:var(--shadow-sm);margin-bottom:50px;">
  <div>
    <span style="color:var(--primary);font-size:0.8rem;font-weight:800;letter-spacing:1px;text-transform:uppercase;">
      <i class="fas fa-briefcase"></i> CORPORATE &amp; INSTITUTIONAL ORDERS
    </span>
    <h3 style="font-size:1.35rem;font-weight:900;margin-top:4px;color:#0f172a;">Need Bulk Hardware or Stationery for Office / School?</h3>
    <p style="color:#475569;font-size:0.88rem;margin-top:6px;max-width:620px;line-height:1.5;">
      Sakshi Infotech provides customized B2B GST quotations, wholesale volume discounts on JK paper cartons and computer hardware, with dedicated support.
    </p>
  </div>
  <a href="<?= url('shop.php') ?>" class="btn-primary-si">
    <i class="fas fa-file-invoice"></i> Request Bulk Quote
  </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
