<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Product.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = Product::getProductById($id);

if (!$product) {
    header("Location: " . (function_exists('url') ? url('shop.php') : '/shop.php'));
    exit;
}

$pageTitle = $product['name'] . " - " . APP_NAME;
$specs = !empty($product['specifications']) ? json_decode($product['specifications'], true) : [];
$discount = round((($product['regular_price'] - $product['sale_price']) / $product['regular_price']) * 100);
$galleryImages = Product::getGalleryImages($product);

// Fetch related items from same subcategory (or category if no subcategory)
$relatedFilter = !empty($product['subcategory_id']) 
    ? ['subcategory_id' => $product['subcategory_id']] 
    : ['category_id' => $product['category_id']];
$relatedProductsRaw = Product::getProducts($relatedFilter);
$relatedProducts = [];
foreach ($relatedProductsRaw as $rp) {
    if ($rp['id'] != $product['id']) {
        $relatedProducts[] = $rp;
    }
    if (count($relatedProducts) >= 4) break;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="section-wrapper" style="margin-top:25px;margin-bottom:60px;">
  <!-- Breadcrumbs (MegaCompu 2-Tier Hierarchy) -->
  <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:20px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <a href="<?= url('sale.php') ?>" style="text-decoration:none;color:var(--text-muted);"><i class="fas fa-home"></i> Home</a>
    <span>/</span>
    <a href="<?= url('shop.php') ?>" style="text-decoration:none;color:var(--text-muted);">Catalog</a>
    <span>/</span>
    <a href="<?= url('shop.php?category_slug=' . urlencode($product['category_slug'])) ?>" style="text-decoration:none;color:var(--text-muted);"><?= htmlspecialchars($product['category_name']) ?></a>
    <?php if (!empty($product['subcategory_name'])): ?>
      <span>/</span>
      <a href="<?= url('shop.php?subcategory_slug=' . urlencode($product['subcategory_slug'])) ?>" style="text-decoration:none;font-weight:700;color:var(--primary);">
        <?= htmlspecialchars($product['subcategory_name']) ?>
      </a>
    <?php endif; ?>
    <span>/</span>
    <span style="color:var(--text-main);font-weight:700;"><?= htmlspecialchars($product['name']) ?></span>
  </div>

  <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:35px;display:grid;grid-template-columns:1fr 1.2fr;gap:40px;box-shadow:var(--shadow-sm);margin-bottom:30px;">
    
    <!-- Image Gallery Section -->
    <div>
      <div style="background:#f8fafc;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:28px;display:flex;align-items:center;justify-content:center;position:relative;min-height:360px;">
        <?php if ($discount > 0): ?>
          <span class="badge-discount" style="top:16px;left:16px;font-size:0.85rem;padding:5px 12px;"><?= $discount ?>% OFF SPECIAL</span>
        <?php endif; ?>
        <img id="mainProductImage" src="<?= product_image_url($galleryImages[0] ?? $product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="max-height:340px;max-width:100%;object-fit:contain;transition:opacity 0.2s ease, transform 0.2s ease;">
      </div>

      <!-- Multiple Gallery Thumbnails Strip -->
      <?php if (count($galleryImages) > 1): ?>
        <div class="product-thumbnails-container" style="display:flex;gap:12px;margin-top:16px;overflow-x:auto;padding:4px 2px;">
          <?php foreach ($galleryImages as $idx => $imgFile): ?>
            <button type="button" class="gallery-thumb-btn <?= $idx === 0 ? 'active' : '' ?>" 
                    onclick="switchProductPhoto(this, '<?= product_image_url($imgFile) ?>')"
                    style="border:2px solid <?= $idx === 0 ? 'var(--primary)' : 'var(--border-color)' ?>;background:#ffffff;border-radius:10px;padding:4px;cursor:pointer;width:72px;height:72px;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease;flex-shrink:0;box-shadow:var(--shadow-sm);">
              <img src="<?= product_image_url($imgFile) ?>" alt="Photo <?= $idx + 1 ?>" style="max-width:100%;max-height:100%;object-fit:contain;">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      
      <!-- Trust badges -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:20px;">
        <div style="background:#f8fafc;padding:12px;border-radius:var(--radius-md);text-align:center;border:1px solid var(--border-color);">
          <i class="fas fa-check-circle" style="color:var(--success);font-size:1.2rem;margin-bottom:4px;"></i>
          <div style="font-size:0.82rem;font-weight:800;">100% Original</div>
          <div style="font-size:0.75rem;color:var(--text-muted);">Brand Verified</div>
        </div>
        <div style="background:#f8fafc;padding:12px;border-radius:var(--radius-md);text-align:center;border:1px solid var(--border-color);">
          <i class="fas fa-truck" style="color:var(--primary);font-size:1.2rem;margin-bottom:4px;"></i>
          <div style="font-size:0.82rem;font-weight:800;">Ready Dispatch</div>
          <div style="font-size:0.75rem;color:var(--text-muted);">From Jaipur Hub</div>
        </div>
      </div>
    </div>

      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
        <a href="<?= url('shop.php?category_slug=' . urlencode($product['category_slug'])) ?>" class="product-category-tag" style="font-size:0.8rem;text-decoration:none;">
          <?= htmlspecialchars($product['category_name']) ?>
        </a>
        <?php if (!empty($product['subcategory_name'])): ?>
          <span style="color:#94a3b8;font-size:0.75rem;">&gt;</span>
          <a href="<?= url('shop.php?subcategory_slug=' . urlencode($product['subcategory_slug'])) ?>" style="background:var(--primary-light);color:var(--primary);font-size:0.8rem;font-weight:700;padding:4px 10px;border-radius:6px;text-decoration:none;">
            <?= htmlspecialchars($product['subcategory_name']) ?>
          </a>
        <?php endif; ?>
      </div>
      <h1 style="font-size:1.8rem;font-weight:800;color:var(--text-main);line-height:1.25;margin:8px 0 14px;">
        <?= htmlspecialchars($product['name']) ?>
      </h1>

      <div style="display:flex;align-items:center;gap:15px;margin-bottom:18px;font-size:0.85rem;color:var(--text-muted);">
        <span>Brand: <strong style="color:var(--text-main);"><?= htmlspecialchars($product['brand'] ?: 'Sakshi Infotech') ?></strong></span>
        <span>•</span>
        <span>SKU: <code style="color:var(--primary);font-weight:700;"><?= htmlspecialchars($product['sku']) ?></code></span>
        <span>•</span>
        <span>HSN: <strong><?= htmlspecialchars($product['hsn_code']) ?></strong></span>
      </div>

      <!-- Pricing Box -->
      <div style="background:#f8fafc;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:20px;margin-bottom:24px;">
        <div style="display:flex;align-items:baseline;gap:15px;margin-bottom:6px;">
          <span style="font-size:2.2rem;font-weight:900;color:#0f172a;">₹<?= number_format($product['sale_price'], 2) ?></span>
          <?php if ($product['regular_price'] > $product['sale_price']): ?>
            <span style="font-size:1.1rem;color:#94a3b8;text-decoration:line-through;">₹<?= number_format($product['regular_price'], 2) ?></span>
          <?php endif; ?>
          <span class="badge badge-verified" style="font-size:0.8rem;">Save ₹<?= number_format($product['regular_price'] - $product['sale_price'], 2) ?></span>
        </div>
        <p style="font-size:0.82rem;color:var(--text-muted);font-weight:600;">
          Price includes <?= (int)$product['gst_rate'] ?>% Indian GST. Eligible for full Input Tax Credit (ITC).
        </p>
      </div>

      <!-- Stock & Add to Cart Action -->
      <div style="margin-bottom:25px;">
        <div style="display:flex;align-items:center;gap:15px;margin-bottom:15px;">
          <label style="font-weight:800;font-size:0.9rem;">Quantity:</label>
          <div class="qty-control" style="border-radius:var(--radius-md);height:42px;">
            <button class="qty-btn" type="button" style="width:38px;height:40px;font-size:1.1rem;" onclick="let q = document.getElementById('itemDetailQty'); if(parseInt(q.value) > 1) q.value = parseInt(q.value)-1;">-</button>
            <input type="text" id="itemDetailQty" value="1" readonly style="width:45px;text-align:center;border:none;font-weight:800;font-size:1rem;outline:none;">
            <button class="qty-btn" type="button" style="width:38px;height:40px;font-size:1.1rem;" onclick="let q = document.getElementById('itemDetailQty'); if(parseInt(q.value) < <?= $product['stock_qty'] ?>) q.value = parseInt(q.value)+1;">+</button>
          </div>
          <span class="badge <?= $product['stock_qty'] > 3 ? 'badge-verified' : 'badge-pending' ?>" style="font-size:0.85rem;padding:8px 14px;">
            <i class="fas fa-box"></i> <?= $product['stock_qty'] > 0 ? "{$product['stock_qty']} Units In Stock" : "Out of Stock" ?>
          </span>
        </div>

        <button type="button" class="btn-primary-si" id="btnProductDetailAdd" data-product-id="<?= $product['id'] ?>" style="width:100%;padding:14px;font-size:1.05rem;justify-content:center;box-shadow:var(--shadow-glow);">
          <i class="fas fa-cart-plus"></i> Add to Cart Now
        </button>
      </div>

      <!-- Description -->
      <div style="border-top:1px solid var(--border-color);padding-top:20px;margin-bottom:20px;">
        <h3 style="font-size:1rem;font-weight:800;margin-bottom:10px;">Product Description</h3>
        <p style="font-size:0.92rem;color:var(--text-muted);line-height:1.7;">
          <?= nl2br(htmlspecialchars($product['description'])) ?>
        </p>
      </div>

      <!-- Official Product Warranty & Assurance -->
      <div style="background:linear-gradient(135deg, #f0fdf4 0%, #eff6ff 100%);color:#0f172a;border:1px solid #bfdbfe;border-radius:var(--radius-md);padding:14px 18px;display:flex;align-items:center;gap:14px;">
        <div style="width:40px;height:40px;border-radius:50%;background:#e0f2fe;color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">
          <i class="fas fa-shield-halved"></i>
        </div>
        <div>
          <div style="font-size:0.86rem;color:var(--text-main);font-weight:800;">Sakshi Infotech Quality Assurance</div>
          <div style="font-size:0.78rem;color:#64748b;margin-top:2px;">100% genuine brand-sealed product backed by official manufacturer warranty and original GST tax invoice.</div>
        </div>
      </div>

    </div>
  </div>

  <!-- Specifications Table -->
  <?php if (!empty($specs)): ?>
    <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:30px;box-shadow:var(--shadow-sm);margin-bottom:40px;">
      <h3 style="font-size:1.2rem;font-weight:800;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-list-check" style="color:var(--primary);"></i> Technical Specifications
      </h3>
      <table class="admin-table" style="max-width:800px;">
        <tbody>
          <?php foreach ($specs as $label => $val): ?>
            <tr>
              <td style="width:250px;font-weight:800;color:var(--text-main);background:#f8fafc;"><?= htmlspecialchars($label) ?></td>
              <td><?= htmlspecialchars($val) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- Related Products in this Sub-Category / Category -->
  <?php if (!empty($relatedProducts)): ?>
    <div style="margin-top:40px;margin-bottom:20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
          <span style="font-size:0.75rem;font-weight:800;color:var(--primary);text-transform:uppercase;letter-spacing:1px;">Related Products</span>
          <h3 style="font-size:1.3rem;font-weight:900;color:#0f172a;margin-top:2px;">
            More in <?= htmlspecialchars($product['subcategory_name'] ?? $product['category_name']) ?>
          </h3>
        </div>
        <a href="<?= url('shop.php?' . (!empty($product['subcategory_slug']) ? 'subcategory_slug=' . urlencode($product['subcategory_slug']) : 'category_slug=' . urlencode($product['category_slug']))) ?>" style="color:var(--primary);font-weight:700;font-size:0.88rem;text-decoration:none;">
          View All &rarr;
        </a>
      </div>

      <div class="product-grid">
        <?php foreach ($relatedProducts as $rp): 
          $rpDisc = round((($rp['regular_price'] - $rp['sale_price']) / $rp['regular_price']) * 100);
        ?>
          <div class="product-card">
            <div class="product-img-wrap">
              <?php if ($rpDisc > 0): ?>
                <span class="badge-discount"><?= $rpDisc ?>% OFF</span>
              <?php endif; ?>
              <a href="<?= url('product.php?id=' . $rp['id']) ?>" style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;">
                <img src="<?= product_image_url($rp['image_url']) ?>" alt="<?= htmlspecialchars($rp['name']) ?>" loading="lazy">
              </a>
            </div>
            <div class="product-body">
              <div class="product-meta-row">
                <span class="product-brand-tag"><?= htmlspecialchars($rp['brand'] ?: $rp['category_name']) ?></span>
                <div class="product-rating"><i class="fas fa-star"></i> <span>4.9</span></div>
              </div>
              <a href="<?= url('product.php?id=' . $rp['id']) ?>" class="product-title" title="<?= htmlspecialchars($rp['name']) ?>">
                <?= htmlspecialchars($rp['name']) ?>
              </a>
              <div class="product-pricing">
                <div class="price-main-row">
                  <span class="sale-price">₹<?= number_format($rp['sale_price'], 2) ?></span>
                  <?php if ($rp['regular_price'] > $rp['sale_price']): ?>
                    <span class="regular-price">₹<?= number_format($rp['regular_price'], 2) ?></span>
                  <?php endif; ?>
                </div>
                <span class="gst-note">Incl. <?= (int)$rp['gst_rate'] ?>% GST</span>
              </div>
              <button type="button" class="btn-add-cart btn-ajax-add-cart" data-product-id="<?= $rp['id'] ?>">
                <i class="fas fa-cart-plus"></i> Add to Cart
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function switchProductPhoto(btn, url) {
    const mainImg = document.getElementById('mainProductImage');
    if (!mainImg) return;
    mainImg.style.opacity = '0.3';
    mainImg.style.transform = 'scale(0.97)';
    setTimeout(() => {
        mainImg.src = url;
        mainImg.style.opacity = '1';
        mainImg.style.transform = 'scale(1)';
    }, 150);
    document.querySelectorAll('.gallery-thumb-btn').forEach(b => {
        b.style.borderColor = 'var(--border-color)';
        b.classList.remove('active');
    });
    btn.style.borderColor = 'var(--primary)';
    btn.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
