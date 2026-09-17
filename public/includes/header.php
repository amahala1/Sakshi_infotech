<?php
if (!defined('BASE_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/config/config.php';
}
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Product.php';
require_once BASE_PATH . '/src/Order.php';

$cartData = Order::getCartDetails();
$cartCount = $cartData['count'];
$allCategoriesWithSubs = Product::getCategoriesWithSubcategories();
$computerCats = array_filter($allCategoriesWithSubs, fn($c) => $c['type'] === 'computer');
$stationeryCats = array_filter($allCategoriesWithSubs, fn($c) => $c['type'] === 'stationery');
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME . ' - Computer & Stationery Hub') ?></title>
  <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<!-- 1. Top Announcement Bar (Fresh Light Ribbon) -->
<div class="top-announcement">
  <div class="top-bar-inner">
    <div>
      <i class="fas fa-shield-halved" style="color:var(--primary);margin-right:6px;"></i>
      100% Genuine IT Hardware &amp; Office Stationery with GST Invoicing
    </div>
    <div class="top-announcement-links">
      <span><i class="fas fa-phone-alt"></i> <?= APP_PHONE ?></span>
      <span><i class="fas fa-envelope"></i> <?= APP_EMAIL ?></span>
      <span><i class="fas fa-truck-fast" style="color:var(--primary);"></i> Express Dispatch</span>
      <?php if (Auth::isLoggedIn()): ?>
        <?php if (Auth::isStaff()): ?>
          <a href="<?= url('admin/') ?>" style="color:var(--primary);font-weight:800;"><i class="fas fa-solar-panel"></i> Staff / Admin Portal</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- 2. Main Storefront Header (Crisp Light Theme) -->
<header class="main-header">
  <div class="header-container">
    <!-- Brand Logo (Requested exact path: images/logo.png) -->
    <a href="<?= url('sale.php') ?>" class="brand-logo-wrap">
      <img src="<?= asset('images/logo.png') ?>" alt="<?= APP_NAME ?> Logo" style="height:48px;width:auto;">
    </a>

    <!-- Live Search Box with Category Selector -->
    <form action="<?= url('shop.php') ?>" method="GET" class="search-box-form">
      <select name="category_id" class="search-category-select">
        <option value="">All Categories</option>
        <optgroup label="Computer Hardware">
          <?php foreach ($computerCats as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= (isset($_GET['category_id']) && $_GET['category_id'] == $cat['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </optgroup>
        <optgroup label="Office Stationery">
          <?php foreach ($stationeryCats as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= (isset($_GET['category_id']) && $_GET['category_id'] == $cat['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </optgroup>
      </select>
      <input type="text" name="search" class="search-input" placeholder="Search laptops, SSD, toner, JK paper, keyboards..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      <button type="submit" class="search-btn">
        <i class="fas fa-search"></i> Search
      </button>
    </form>

    <!-- User & Cart Header Actions -->
    <div class="header-actions">
      <?php if (Auth::isLoggedIn()): ?>
        <div class="action-pill">
          <i class="fas fa-user-check" style="color:var(--primary);"></i>
          <span>Hello, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></span>
          <a href="<?= url('my_orders.php') ?>" style="margin-left:8px;color:var(--primary);"><i class="fas fa-box"></i> Orders</a>
          <a href="<?= url('logout.php') ?>" style="margin-left:8px;color:var(--danger);"><i class="fas fa-sign-out-alt"></i></a>
        </div>
      <?php else: ?>
        <a href="<?= url('login.php') ?>" class="action-pill">
          <i class="fas fa-user" style="color:var(--primary);"></i>
          <span>Login / Register</span>
        </a>
      <?php endif; ?>

      <!-- Cart Button with Live Counter Badge -->
      <a href="<?= url('cart.php') ?>" class="action-pill cart-pill trigger-cart-drawer">
        <i class="fas fa-shopping-cart"></i>
        <span>Cart</span>
        <span class="cart-badge cart-count-badge"><?= $cartCount ?></span>
      </a>
    </div>
  </div>
</header>

<!-- 3. Navigation Bar (Clean Light Aesthetic with Mega Categories Dropdown) -->
<nav class="nav-menu-bar">
  <div class="nav-container">
    <div class="categories-dropdown-wrapper">
      <button class="categories-trigger" id="categoriesDropdownBtn" type="button" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-bars"></i>
        <span>ALL CATEGORIES</span>
        <i class="fas fa-chevron-down dropdown-arrow-icon"></i>
      </button>
      <div class="categories-dropdown-menu" id="categoriesDropdownMenu" style="width:740px;">
        <div class="dropdown-category-col">
          <div class="dropdown-col-title" style="color:#1e3a8a;background:#eff6ff;padding:8px 12px;border-radius:8px;font-weight:900;">
            <i class="fas fa-laptop" style="color:var(--primary);"></i> IT Solutions &amp; Gadgets
          </div>
          <?php foreach ($computerCats as $c): ?>
            <div style="margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;">
              <a href="<?= url('shop.php?category_slug=' . urlencode($c['slug'])) ?>" class="dropdown-cat-item" style="font-weight:800;color:#0f172a;padding:4px 0;">
                <i class="fas <?= htmlspecialchars($c['icon']) ?>"></i>
                <span><?= htmlspecialchars($c['name']) ?></span>
                <span class="cat-count-pill"><?= $c['product_count'] ?></span>
              </a>
              <?php if (!empty($c['subcategories'])): ?>
                <div style="display:flex;flex-wrap:wrap;gap:4px;padding-left:24px;margin-top:4px;">
                  <?php foreach ($c['subcategories'] as $sc): ?>
                    <a href="<?= url('shop.php?subcategory_slug=' . urlencode($sc['slug'])) ?>" style="font-size:0.72rem;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;padding:2px 7px;border-radius:4px;text-decoration:none;">
                      <?= htmlspecialchars($sc['name']) ?> (<?= $sc['product_count'] ?>)
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="dropdown-category-col">
          <div class="dropdown-col-title" style="color:#065f46;background:#ecfdf5;padding:8px 12px;border-radius:8px;font-weight:900;">
            <i class="fas fa-print" style="color:#059669;"></i> Office &amp; Creative Stationery
          </div>
          <?php foreach ($stationeryCats as $c): ?>
            <div style="margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;">
              <a href="<?= url('shop.php?category_slug=' . urlencode($c['slug'])) ?>" class="dropdown-cat-item" style="font-weight:800;color:#0f172a;padding:4px 0;">
                <i class="fas <?= htmlspecialchars($c['icon']) ?>"></i>
                <span><?= htmlspecialchars($c['name']) ?></span>
                <span class="cat-count-pill"><?= $c['product_count'] ?></span>
              </a>
              <?php if (!empty($c['subcategories'])): ?>
                <div style="display:flex;flex-wrap:wrap;gap:4px;padding-left:24px;margin-top:4px;">
                  <?php foreach ($c['subcategories'] as $sc): ?>
                    <a href="<?= url('shop.php?subcategory_slug=' . urlencode($sc['slug'])) ?>" style="font-size:0.72rem;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;padding:2px 7px;border-radius:4px;text-decoration:none;">
                      <?= htmlspecialchars($sc['name']) ?> (<?= $sc['product_count'] ?>)
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="dropdown-footer-bar">
          <a href="<?= url('shop.php') ?>"><i class="fas fa-th-large"></i> Browse Complete Catalog (All Categories &amp; Sub-Categories) &rarr;</a>
        </div>
      </div>
    </div>

    <ul class="main-nav-links">
      <li><a href="<?= url('sale.php') ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'sale.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a></li>
      <li><a href="<?= url('shop.php?type=computer') ?>" class="<?= (isset($_GET['type']) && $_GET['type'] === 'computer') ? 'active' : '' ?>"><i class="fas fa-laptop" style="color:#2563eb;"></i> Computer Hardware</a></li>
      <li><a href="<?= url('shop.php?type=stationery') ?>" class="<?= (isset($_GET['type']) && $_GET['type'] === 'stationery') ? 'active' : '' ?>"><i class="fas fa-print" style="color:#059669;"></i> Stationery &amp; Toners</a></li>
      <li><a href="<?= url('shop.php?featured=1') ?>"><i class="fas fa-bolt" style="color:#f59e0b;"></i> Hot Deals</a></li>
      <li><a href="<?= url('shop.php') ?>"><i class="fas fa-certificate" style="color:#0284c7;"></i> Brand Catalog</a></li>
    </ul>

    <div class="help-hotline">
      <i class="fas fa-headset"></i>
      <span>Need Help? <?= APP_PHONE ?></span>
    </div>
  </div>
</nav>

<!-- 4. Interactive Cart Drawer Overlay & Slide-in Container -->
<div class="cart-drawer-overlay" id="cartDrawerOverlay"></div>
<aside class="cart-drawer" id="cartDrawer">
  <div class="cart-drawer-header">
    <h3>
      <i class="fas fa-shopping-bag" style="color:var(--primary);"></i>
      Shopping Cart (<span id="cartDrawerCountHeader"><?= $cartData['count'] ?></span>)
    </h3>
    <button class="btn-close-drawer" aria-label="Close cart">&times;</button>
  </div>
  
  <div class="cart-drawer-items" id="cartDrawerItemsContainer">
    <?php if (empty($cartData['items'])): ?>
      <div style="text-align:center;padding:45px 20px;color:var(--text-muted);">
        <div style="width:70px;height:70px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;color:#94a3b8;font-size:1.8rem;">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <h4 style="font-size:1.1rem;font-weight:800;color:var(--text-main);">Your cart is empty</h4>
        <p style="font-size:0.85rem;margin-top:6px;">Explore our computers and stationery collection to add items.</p>
        <a href="<?= url('shop.php') ?>" class="btn-primary-si" style="margin-top:20px;display:inline-flex;padding:10px 22px;font-size:0.88rem;">Start Shopping</a>
      </div>
    <?php else: ?>
      <?php foreach ($cartData['items'] as $item): ?>
        <div class="cart-item-row" id="drawerItem-<?= $item['product_id'] ?>">
          <div class="cart-item-thumb">
            <img src="<?= product_image_url($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
          </div>
          <div class="cart-item-details">
            <h4 class="cart-item-title"><?= htmlspecialchars($item['name']) ?></h4>
            <div class="cart-item-price">₹<?= number_format($item['unit_price'], 2) ?></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
              <div class="qty-control">
                <button class="qty-btn" type="button" onclick="updateCartItemQty(<?= $item['product_id'] ?>, <?= $item['quantity'] - 1 ?>)">-</button>
                <span class="qty-val"><?= $item['quantity'] ?></span>
                <button class="qty-btn" type="button" onclick="updateCartItemQty(<?= $item['product_id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
              </div>
              <button type="button" onclick="updateCartItemQty(<?= $item['product_id'] ?>, 0)" style="background:none;border:none;color:var(--danger);font-size:0.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:4px;">
                <i class="fas fa-trash-alt"></i> Remove
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="cart-drawer-footer" id="cartDrawerFooter" style="display: <?= empty($cartData['items']) ? 'none' : 'flex' ?>; flex-direction: column;">
    <div class="drawer-subtotal">
      <span>GST Taxable Subtotal:</span>
      <span id="cartDrawerSubtotal" style="font-weight:700;color:var(--text-main);">₹<?= number_format($cartData['subtotal'], 2) ?></span>
    </div>
    <div class="drawer-subtotal">
      <span>Estimated GST Tax:</span>
      <span id="cartDrawerTax" style="font-weight:700;color:var(--text-main);">₹<?= number_format($cartData['tax_amount'], 2) ?></span>
    </div>
    <div class="drawer-total">
      <span>Grand Total:</span>
      <span id="cartDrawerTotal">₹<?= number_format($cartData['total_amount'], 2) ?></span>
    </div>
    <div style="font-size:0.75rem;color:#059669;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:5px;">
      <i class="fas fa-truck-fast"></i> Eligible for Free Express Dispatch
    </div>
    <div style="display:flex;gap:10px;">
      <a href="<?= url('cart.php') ?>" class="btn-outline-si" style="flex:1;text-align:center;justify-content:center;padding:11px 14px;font-size:0.88rem;">View Full Cart</a>
      <a href="<?= url('checkout.php') ?>" class="btn-primary-si" style="flex:1;text-align:center;justify-content:center;padding:11px 14px;font-size:0.88rem;">Checkout <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</aside>
