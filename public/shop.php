<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Product.php';
require_once BASE_PATH . '/src/Order.php';

// Parse filters
$filters = [
    'category_id' => $_GET['category_id'] ?? null,
    'category_slug' => $_GET['category_slug'] ?? null,
    'subcategory_id' => $_GET['subcategory_id'] ?? null,
    'subcategory_slug' => $_GET['subcategory_slug'] ?? null,
    'type' => $_GET['type'] ?? null,
    'search' => $_GET['search'] ?? null,
    'brand' => $_GET['brand'] ?? null,
    'min_price' => $_GET['min_price'] ?? null,
    'max_price' => $_GET['max_price'] ?? null,
    'sort' => $_GET['sort'] ?? 'newest',
    'featured' => $_GET['featured'] ?? null
];

$products = Product::getProducts($filters);
$categoriesWithSubs = Product::getCategoriesWithSubcategories($filters['type'] ?? null);

// Determine active subcategory or category details for breadcrumbs and title
$activeCategoryName = null;
$activeCategorySlug = $filters['category_slug'] ?? null;
$activeSubcategoryName = null;
$activeSubcategorySlug = $filters['subcategory_slug'] ?? null;

foreach ($categoriesWithSubs as $cat) {
    if ($activeCategorySlug && $cat['slug'] === $activeCategorySlug) {
        $activeCategoryName = $cat['name'];
    }
    if ($activeSubcategorySlug && !empty($cat['subcategories'])) {
        foreach ($cat['subcategories'] as $sc) {
            if ($sc['slug'] === $activeSubcategorySlug) {
                $activeSubcategoryName = $sc['name'];
                $activeCategoryName = $cat['name'];
                $activeCategorySlug = $cat['slug'];
                break 2;
            }
        }
    }
}

$pageTitle = ($activeSubcategoryName ? $activeSubcategoryName . " - " : ($activeCategoryName ? $activeCategoryName . " - " : "")) . "Shop Catalog - " . APP_NAME;

// Extract all unique brands across complete catalog
$allCatalog = Product::getProducts([]);
$brands = [];
foreach ($allCatalog as $p) {
    if (!empty($p['brand']) && !in_array($p['brand'], $brands)) {
        $brands[] = $p['brand'];
    }
}
sort($brands);

require_once __DIR__ . '/includes/header.php';
?>

<div class="section-wrapper" style="margin-top:25px;margin-bottom:60px;">
  <!-- Breadcrumb -->
  <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:20px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <a href="<?= url('sale.php') ?>" style="text-decoration:none;color:var(--text-muted);"><i class="fas fa-home"></i> Home</a>
    <span>/</span>
    <a href="<?= url('shop.php') ?>" style="text-decoration:none;color:var(--text-muted);">Catalog</a>
    <?php if (!empty($filters['type'])): ?>
      <span>/</span>
      <a href="<?= url('shop.php?type=' . urlencode($filters['type'])) ?>" style="text-decoration:none;color:var(--text-muted);text-transform:capitalize;font-weight:700;">
        <?= htmlspecialchars($filters['type'] === 'computer' ? 'Computer Hardware' : 'Office Stationery') ?>
      </a>
    <?php endif; ?>
    <?php if (!empty($activeCategoryName)): ?>
      <span>/</span>
      <a href="<?= url('shop.php?category_slug=' . urlencode($activeCategorySlug)) ?>" style="text-decoration:none;font-weight:700;color:<?= empty($activeSubcategoryName) ? 'var(--primary)' : 'var(--text-muted)' ?>;">
        <?= htmlspecialchars($activeCategoryName) ?>
      </a>
    <?php endif; ?>
    <?php if (!empty($activeSubcategoryName)): ?>
      <span>/</span>
      <span style="font-weight:800;color:var(--primary);"><?= htmlspecialchars($activeSubcategoryName) ?></span>
    <?php endif; ?>
    <?php if (!empty($filters['search'])): ?>
      <span>/</span>
      <span>Search: "<?= htmlspecialchars($filters['search']) ?>"</span>
    <?php endif; ?>
  </div>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:28px;align-items:start;">
    
    <!-- Left Sidebar Filters (MegaCompu 2-Tier Architecture) -->
    <aside style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:20px;box-shadow:var(--shadow-sm);">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;border-bottom:2px solid #f1f5f9;padding-bottom:12px;">
        <h3 style="font-size:1rem;font-weight:800;color:#0f172a;margin:0;display:flex;align-items:center;gap:8px;">
          <i class="fas fa-sitemap" style="color:var(--primary);"></i> MegaCompu Filter
        </h3>
        <a href="<?= url('shop.php') ?>" style="font-size:0.75rem;color:var(--danger);font-weight:700;text-decoration:none;">
          <i class="fas fa-redo"></i> Reset
        </a>
      </div>

      <form action="<?= url('shop.php') ?>" method="GET" id="shopFilterForm">
        <?php if (!empty($filters['search'])): ?>
          <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search']) ?>">
        <?php endif; ?>

        <!-- Department Selector -->
        <div style="margin-bottom:20px;border-bottom:1px solid var(--border-color);padding-bottom:16px;">
          <h4 style="font-size:0.82rem;font-weight:800;text-transform:uppercase;color:var(--text-main);margin-bottom:10px;letter-spacing:0.5px;">
            Department
          </h4>
          <div style="display:flex;flex-direction:column;gap:6px;font-size:0.86rem;">
            <label style="cursor:pointer;display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;<?= empty($filters['type']) && empty($filters['category_slug']) && empty($filters['subcategory_slug']) ? 'background:var(--primary-light);color:var(--primary);font-weight:700;' : '' ?>">
              <input type="radio" name="type" value="" <?= empty($filters['type']) && empty($filters['category_slug']) && empty($filters['subcategory_slug']) ? 'checked' : '' ?> onchange="document.getElementById('shopFilterForm').submit()">
              <span>All Products</span>
            </label>
            <label style="cursor:pointer;display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;<?= ($filters['type'] === 'computer') ? 'background:var(--primary-light);color:var(--primary);font-weight:700;' : '' ?>">
              <input type="radio" name="type" value="computer" <?= ($filters['type'] === 'computer') ? 'checked' : '' ?> onchange="document.getElementById('shopFilterForm').submit()">
              <span>💻 Computer Hardware</span>
            </label>
            <label style="cursor:pointer;display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;<?= ($filters['type'] === 'stationery') ? 'background:var(--primary-light);color:var(--primary);font-weight:700;' : '' ?>">
              <input type="radio" name="type" value="stationery" <?= ($filters['type'] === 'stationery') ? 'checked' : '' ?> onchange="document.getElementById('shopFilterForm').submit()">
              <span>📄 Office Stationery</span>
            </label>
          </div>
        </div>

        <!-- MegaCompu 2-Tier Hierarchy: Categories & Nested Sub-Categories -->
        <div style="margin-bottom:22px;border-bottom:1px solid var(--border-color);padding-bottom:18px;">
          <h4 style="font-size:0.82rem;font-weight:800;text-transform:uppercase;color:var(--text-main);margin-bottom:12px;letter-spacing:0.5px;display:flex;justify-content:space-between;align-items:center;">
            <span>Categories &amp; Sub-Cats</span>
            <span style="font-size:0.7rem;color:var(--primary);text-transform:none;font-weight:700;">2-Tier Tree</span>
          </h4>
          <div style="display:flex;flex-direction:column;gap:6px;">
            <?php foreach ($categoriesWithSubs as $c): 
              $isCatActive = ($activeCategorySlug === $c['slug']);
              $hasActiveSub = false;
              if (!empty($c['subcategories'])) {
                  foreach ($c['subcategories'] as $checkSub) {
                      if ($activeSubcategorySlug === $checkSub['slug']) {
                          $hasActiveSub = true;
                          break;
                      }
                  }
              }
              $shouldExpand = ($isCatActive || $hasActiveSub);
            ?>
              <div style="border:1px solid <?= $shouldExpand ? '#bfdbfe' : '#f1f5f9' ?>;border-radius:8px;overflow:hidden;background:<?= $shouldExpand ? '#f8fafc' : '#ffffff' ?>;">
                <!-- Parent Category Header -->
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;background:<?= ($isCatActive && empty($activeSubcategorySlug)) ? 'var(--primary)' : ($shouldExpand ? '#eff6ff' : 'transparent') ?>;">
                  <a href="<?= url('shop.php?category_slug=' . urlencode($c['slug'])) ?>" style="text-decoration:none;display:flex;align-items:center;gap:8px;font-size:0.84rem;font-weight:700;color:<?= ($isCatActive && empty($activeSubcategorySlug)) ? '#ffffff' : '#1e293b' ?>;flex:1;">
                    <i class="fas <?= htmlspecialchars($c['icon']) ?>" style="font-size:0.8rem;color:<?= ($isCatActive && empty($activeSubcategorySlug)) ? '#ffffff' : 'var(--primary)' ?>;"></i>
                    <span><?= htmlspecialchars($c['name']) ?></span>
                  </a>
                  <span style="font-size:0.7rem;font-weight:700;padding:2px 6px;border-radius:999px;<?= ($isCatActive && empty($activeSubcategorySlug)) ? 'background:rgba(255,255,255,0.25);color:#ffffff;' : 'background:#e2e8f0;color:#475569;' ?>">
                    <?= $c['product_count'] ?>
                  </span>
                </div>

                <!-- Sub-Categories Nested List -->
                <?php if (!empty($c['subcategories'])): ?>
                  <div style="padding:4px 8px 8px 14px;display:flex;flex-direction:column;gap:3px;background:#ffffff;border-top:1px solid #f1f5f9;">
                    <?php foreach ($c['subcategories'] as $sc): 
                      $isSubActive = ($activeSubcategorySlug === $sc['slug']);
                    ?>
                      <a href="<?= url('shop.php?subcategory_slug=' . urlencode($sc['slug'])) ?>" 
                         style="display:flex;justify-content:space-between;align-items:center;font-size:0.79rem;padding:4px 8px;border-radius:5px;text-decoration:none;transition:all 0.15s ease;<?= $isSubActive ? 'background:var(--primary);color:#ffffff;font-weight:800;' : 'color:#475569;' ?>"
                         onmouseover="if(!<?= $isSubActive ? 'true':'false' ?>) { this.style.background='#f1f5f9'; this.style.color='#0f172a'; }"
                         onmouseout="if(!<?= $isSubActive ? 'true':'false' ?>) { this.style.background='transparent'; this.style.color='#475569'; }">
                        <span style="display:flex;align-items:center;gap:6px;">
                          <i class="fas fa-angle-right" style="font-size:0.65rem;color:<?= $isSubActive ? '#ffffff' : 'var(--primary)' ?>;"></i>
                          <?= htmlspecialchars($sc['name']) ?>
                        </span>
                        <span style="font-size:0.68rem;font-weight:700;padding:1px 5px;border-radius:999px;<?= $isSubActive ? 'background:#ffffff;color:var(--primary);' : 'background:#f8fafc;color:#94a3b8;border:1px solid #e2e8f0;' ?>">
                          <?= $sc['product_count'] ?>
                        </span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Smart Context Filters: GSM Paper Selection & Hardware Specs -->
        <?php if (empty($filters['type']) || $filters['type'] === 'stationery'): ?>
          <div style="margin-bottom:20px;border-bottom:1px solid var(--border-color);padding-bottom:16px;">
            <h4 style="font-size:0.82rem;font-weight:800;text-transform:uppercase;color:var(--text-main);margin-bottom:10px;letter-spacing:0.5px;">
              📄 Paper Weight (GSM)
            </h4>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
              <a href="<?= url('shop.php?search=75+GSM') ?>" class="btn-sm-action" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:0.78rem;padding:4px 9px;">75 GSM</a>
              <a href="<?= url('shop.php?search=80+GSM') ?>" class="btn-sm-action" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:0.78rem;padding:4px 9px;">80 GSM</a>
              <a href="<?= url('shop.php?search=70+GSM') ?>" class="btn-sm-action" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:0.78rem;padding:4px 9px;">70 GSM</a>
              <a href="<?= url('shop.php?subcategory_slug=wholesale-paper-cartons') ?>" class="btn-sm-action" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:0.78rem;padding:4px 9px;">B2B Cartons</a>
            </div>
          </div>
        <?php endif; ?>

        <?php if (empty($filters['type']) || $filters['type'] === 'computer'): ?>
          <div style="margin-bottom:20px;border-bottom:1px solid var(--border-color);padding-bottom:16px;">
            <h4 style="font-size:0.82rem;font-weight:800;text-transform:uppercase;color:var(--text-main);margin-bottom:10px;letter-spacing:0.5px;">
              💻 IT Specifications
            </h4>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
              <a href="<?= url('shop.php?search=16GB') ?>" class="btn-sm-action" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:0.78rem;padding:4px 9px;">16GB RAM</a>
              <a href="<?= url('shop.php?search=SSD') ?>" class="btn-sm-action" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:0.78rem;padding:4px 9px;">NVMe SSD</a>
              <a href="<?= url('shop.php?search=Core+i5') ?>" class="btn-sm-action" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:0.78rem;padding:4px 9px;">Core i5</a>
              <a href="<?= url('shop.php?search=Wi-Fi+6') ?>" class="btn-sm-action" style="background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:0.78rem;padding:4px 9px;">Wi-Fi 6</a>
            </div>
          </div>
        <?php endif; ?>

        <!-- Brand Filter -->
        <div style="margin-bottom:22px;border-bottom:1px solid var(--border-color);padding-bottom:18px;">
          <h4 style="font-size:0.82rem;font-weight:800;text-transform:uppercase;color:var(--text-main);margin-bottom:10px;letter-spacing:0.5px;">
            Brands
          </h4>
          <div style="display:flex;flex-direction:column;gap:5px;max-height:180px;overflow-y:auto;padding-right:4px;">
            <?php foreach ($brands as $b): 
              $isBrandActive = ($filters['brand'] === $b);
            ?>
              <label style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;font-size:0.82rem;color:#334155;padding:4px 6px;border-radius:4px;<?= $isBrandActive ? 'background:#eff6ff;font-weight:700;color:var(--primary);' : '' ?>">
                <span style="display:flex;align-items:center;gap:8px;">
                  <input type="radio" name="brand" value="<?= htmlspecialchars($b) ?>" <?= $isBrandActive ? 'checked' : '' ?> onchange="document.getElementById('shopFilterForm').submit()">
                  <?= htmlspecialchars($b) ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Price Range Filter -->
        <div style="margin-bottom:18px;">
          <h4 style="font-size:0.82rem;font-weight:800;text-transform:uppercase;color:var(--text-main);margin-bottom:10px;letter-spacing:0.5px;">
            Price Range (₹)
          </h4>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
            <input type="number" name="min_price" placeholder="Min ₹" class="form-control" value="<?= htmlspecialchars($filters['min_price'] ?? '') ?>" style="padding:6px 8px;font-size:0.82rem;">
            <input type="number" name="max_price" placeholder="Max ₹" class="form-control" value="<?= htmlspecialchars($filters['max_price'] ?? '') ?>" style="padding:6px 8px;font-size:0.82rem;">
          </div>
          <button type="submit" class="btn-primary-si" style="width:100%;padding:7px;font-size:0.82rem;justify-content:center;">
            Apply Filter
          </button>
        </div>

        <!-- Quick Price Presets -->
        <div style="display:flex;flex-direction:column;gap:4px;font-size:0.76rem;">
          <a href="<?= url('shop.php?max_price=1000') ?>" style="color:var(--primary);text-decoration:none;">• Under ₹1,000</a>
          <a href="<?= url('shop.php?min_price=1000&max_price=5000') ?>" style="color:var(--primary);text-decoration:none;">• ₹1,000 to ₹5,000</a>
          <a href="<?= url('shop.php?min_price=5000&max_price=20000') ?>" style="color:var(--primary);text-decoration:none;">• ₹5,000 to ₹20,000</a>
          <a href="<?= url('shop.php?min_price=20000') ?>" style="color:var(--primary);text-decoration:none;">• Above ₹20,000</a>
        </div>
      </form>
    </aside>


    <!-- Main Products Catalog Area -->
    <div>
      <!-- Sort & Results Header Bar -->
      <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-md);padding:14px 20px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;box-shadow:var(--shadow-sm);">
        <div style="font-size:0.92rem;font-weight:700;color:var(--text-main);">
          Showing <span style="color:var(--primary);font-weight:900;"><?= count($products) ?></span> products found
        </div>

        <div style="display:flex;align-items:center;gap:12px;">
          <label style="font-size:0.84rem;font-weight:700;color:var(--text-muted);">Sort By:</label>
          <select class="form-control" style="padding:6px 12px;font-size:0.84rem;width:auto;" onchange="const url = new URL(window.location.href); url.searchParams.set('sort', this.value); window.location.href = url.toString();">
            <option value="newest" <?= ($filters['sort'] === 'newest') ? 'selected' : '' ?>>Newest Arrivals</option>
            <option value="price_asc" <?= ($filters['sort'] === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_desc" <?= ($filters['sort'] === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="name_asc" <?= ($filters['sort'] === 'name_asc') ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
            <option value="popular" <?= ($filters['sort'] === 'popular') ? 'selected' : '' ?>>Hot Best Sellers</option>
          </select>
        </div>
      </div>

      <!-- Products Grid -->
      <?php if (empty($products)): ?>
        <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:60px 20px;text-align:center;">
          <div style="width:70px;height:70px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;color:#94a3b8;font-size:1.8rem;">
            <i class="fas fa-search"></i>
          </div>
          <h3 style="font-size:1.3rem;font-weight:800;color:#0f172a;">No products found</h3>
          <p style="color:var(--text-muted);font-size:0.9rem;margin:8px 0 20px;">Try clearing some filters or searching for other items.</p>
          <a href="<?= url('shop.php') ?>" class="btn-primary-si" style="display:inline-flex;">View Complete Catalog</a>
        </div>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $prod): 
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
                  <span class="product-brand-tag"><?= htmlspecialchars($prod['brand'] ?: $prod['category_name']) ?></span>
                  <?php if (!empty($prod['subcategory_name'])): ?>
                    <span style="font-size:0.7rem;color:var(--primary);background:var(--primary-light);padding:2px 7px;border-radius:4px;font-weight:700;max-width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($prod['subcategory_name']) ?>">
                      <?= htmlspecialchars($prod['subcategory_name']) ?>
                    </span>
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
                  <span class="gst-note">Incl. <?= (int)$prod['gst_rate'] ?>% GST</span>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.74rem;color:var(--text-muted);margin-bottom:10px;">
                  <span>HSN: <?= htmlspecialchars($prod['hsn_code']) ?></span>
                  <span>SKU: <?= htmlspecialchars($prod['sku']) ?></span>
                </div>

                <button type="button" class="btn-add-cart btn-ajax-add-cart" data-product-id="<?= $prod['id'] ?>">
                  <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
