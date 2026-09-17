<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Product.php';

Auth::requireAdmin();

$msg = null;
$error = null;

// Handle Product Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $editId = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;

        // Image upload handling with Automatic WebP Conversion
        $imgName = $_POST['existing_image'] ?? 'laptop-lenovo.svg';
        if (!empty($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            if (in_array($ext, $allowed)) {
                $tempSource = $_FILES['product_image']['tmp_name'];
                $webpName = 'prod_' . time() . '_' . rand(100, 999) . '.webp';
                $targetFile = BASE_PATH . '/public/assets/images/' . $webpName;
                
                // Convert to WebP format
                if (convertToWebP($tempSource, $targetFile, 85)) {
                    $imgName = $webpName;
                    @copy($targetFile, BASE_PATH . '/public/images/' . $webpName);
                } else {
                    $fallbackName = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    if (move_uploaded_file($tempSource, BASE_PATH . '/public/assets/images/' . $fallbackName)) {
                        $imgName = $fallbackName;
                        @copy(BASE_PATH . '/public/assets/images/' . $fallbackName, BASE_PATH . '/public/images/' . $fallbackName);
                    }
                }
            }
        }

        // Handle Gallery Images (Multiple Photos)
        $galleryList = [];
        if (!empty($_POST['existing_gallery']) && is_array($_POST['existing_gallery'])) {
            foreach ($_POST['existing_gallery'] as $eg) {
                $cleanEg = basename($eg);
                if (!empty($cleanEg) && !in_array($cleanEg, $galleryList)) {
                    $galleryList[] = $cleanEg;
                }
            }
        }

        // Process multi-image upload
        if (!empty($_FILES['gallery_images']['name']) && is_array($_FILES['gallery_images']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            $totalFiles = count($_FILES['gallery_images']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $origName = $_FILES['gallery_images']['name'][$i];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $tmpSource = $_FILES['gallery_images']['tmp_name'][$i];
                        $webpName = 'prod_gal_' . time() . '_' . rand(100, 999) . '_' . $i . '.webp';
                        $targetFile = BASE_PATH . '/public/assets/images/' . $webpName;
                        if (convertToWebP($tmpSource, $targetFile, 85)) {
                            $galleryList[] = $webpName;
                            @copy($targetFile, BASE_PATH . '/public/images/' . $webpName);
                        } else {
                            $fallbackName = 'prod_gal_' . time() . '_' . rand(100, 999) . '_' . $i . '.' . $ext;
                            if (move_uploaded_file($tmpSource, BASE_PATH . '/public/assets/images/' . $fallbackName)) {
                                $galleryList[] = $fallbackName;
                                @copy(BASE_PATH . '/public/assets/images/' . $fallbackName, BASE_PATH . '/public/images/' . $fallbackName);
                            }
                        }
                    }
                }
            }
        }

        $data = [
            'category_id' => (int)$_POST['category_id'],
            'subcategory_id' => !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null,
            'name' => $_POST['name'] ?? '',
            'sku' => $_POST['sku'] ?? '',
            'hsn_code' => $_POST['hsn_code'] ?? '8471',
            'brand' => $_POST['brand'] ?? '',
            'regular_price' => (float)$_POST['regular_price'],
            'sale_price' => (float)$_POST['sale_price'],
            'gst_rate' => (float)$_POST['gst_rate'],
            'stock_qty' => (int)$_POST['stock_qty'],
            'min_stock_alert' => (int)$_POST['min_stock_alert'],
            'description' => $_POST['description'] ?? '',
            'specifications' => $_POST['specifications'] ?? '',
            'image_url' => $imgName,
            'gallery_images' => $galleryList,
            'is_featured' => isset($_POST['is_featured']),
            'status' => 1
        ];

        Product::saveProduct($data, $editId);
        $msg = $editId ? "Product updated successfully!" : "New product added successfully!";
    } elseif ($_POST['action'] === 'delete') {
        Product::deleteProduct((int)$_POST['product_id']);
        $msg = "Product deleted.";
    }
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $editProduct = Product::getProductById((int)$_GET['edit']);
}

$products = Product::getProducts();
$categories = Product::getAllCategories();
$allSubcats = Product::getAllSubcategories();

$pageTitle = "Products & Inventory - " . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    /* Bento Box Form Layout */
    .bento-form-container {
      display: grid;
      grid-template-columns: 1.35fr 1fr;
      gap: 22px;
      align-items: start;
    }
    @media (max-width: 992px) {
      .bento-form-container {
        grid-template-columns: 1fr;
      }
    }
    .bento-card {
      background: #ffffff;
      border: 1px solid var(--admin-border);
      border-radius: 14px;
      padding: 22px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
      margin-bottom: 22px;
      transition: all 0.2s ease;
    }
    .bento-card:hover {
      border-color: #cbd5e1;
    }
    .bento-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
      padding-bottom: 10px;
      border-bottom: 1.5px solid #f1f5f9;
    }
    .bento-title {
      font-size: 0.96rem;
      font-weight: 800;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 8px;
    }
  </style>
</head>
<body>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="admin-main">
  <header class="admin-topbar">
    <div class="topbar-title">
      <h1>💻 Products &amp; Inventory Management</h1>
    </div>
    <div class="topbar-actions">
      <a href="#productFormCard" class="btn-sm-action btn-action-primary" style="padding:8px 16px;">
        <i class="fas fa-plus"></i> Add New Product
      </a>
    </div>
  </header>

  <div class="admin-content">

    <?php if ($msg): ?>
      <div style="background:#d1fae5;border:1px solid #34d399;color:#065f46;padding:12px 18px;border-radius:10px;margin-bottom:20px;font-weight:700;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <!-- Add / Edit Product Bento Form Card -->
    <div id="productFormCard" style="background:#f8fafc;border:1px solid var(--admin-border);border-radius:18px;padding:26px;box-shadow:0 4px 14px rgba(15,23,42,0.04);margin-bottom:34px;">
      
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:2px solid #e2e8f0;">
        <div>
          <h2 style="font-size:1.25rem;font-weight:900;color:#0f172a;display:flex;align-items:center;gap:10px;">
            <i class="fas <?= $editProduct ? 'fa-pen-to-square' : 'fa-box-plus' ?>" style="color:var(--admin-primary);"></i>
            <?= $editProduct ? 'Edit Product: ' . htmlspecialchars($editProduct['name']) : 'Add New Computer or Stationery Product' ?>
          </h2>
          <p style="font-size:0.82rem;color:var(--admin-text-muted);margin-top:2px;">
            MegaCompu 2-Tier Architecture: Select Category, then Sub-Category, followed by product details.
          </p>
        </div>
        <a href="<?= url('admin/categories.php') ?>" target="_blank" class="btn-sm-action" style="background:#ffffff;border:1px solid #cbd5e1;color:#334155;padding:6px 12px;font-size:0.8rem;">
          <i class="fas fa-folder-plus"></i> Manage Categories
        </a>
      </div>

      <form action="<?= url('admin/products.php') ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <?php if ($editProduct): ?>
          <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
          <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editProduct['image_url']) ?>">
        <?php endif; ?>

        <div class="bento-form-container">
          
          <!-- LEFT COLUMN: Classification, Identity & Overview -->
          <div>
            
            <!-- Bento Box 1: MegaCompu 2-Tier Category & Sub-Category Selection -->
            <div class="bento-card" style="border-top:3px solid var(--admin-primary);">
              <div class="bento-header">
                <div class="bento-title">
                  <i class="fas fa-sitemap" style="color:var(--admin-primary);"></i>
                  1. Classification (Category &rarr; Sub-Category)
                </div>
                <span style="font-size:0.75rem;color:var(--admin-primary);font-weight:700;">Required</span>
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                <div>
                  <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">
                    Main Category *
                  </label>
                  <select name="category_id" id="catSelector" class="form-control" required style="font-weight:700;">
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($categories as $c): ?>
                      <option value="<?= $c['id'] ?>" data-type="<?= $c['type'] ?>" <?= ($editProduct && $editProduct['category_id'] == $c['id']) ? 'selected' : '' ?>>
                        [<?= strtoupper($c['type']) ?>] <?= htmlspecialchars($c['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div>
                  <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">
                    Sub-Category (MegaCompu Tier 2) *
                  </label>
                  <select name="subcategory_id" id="subcatSelector" class="form-control" required style="font-weight:700;">
                    <option value="">-- Select Sub-Category --</option>
                  </select>
                </div>
              </div>

              <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">Brand / Manufacturer</label>
                <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($editProduct['brand'] ?? '') ?>" placeholder="e.g. Lenovo, HP, JK Paper, Casio, Kangaro">
              </div>
            </div>

            <!-- Bento Box 2: Product Name, SKU & HSN -->
            <div class="bento-card">
              <div class="bento-header">
                <div class="bento-title">
                  <i class="fas fa-tag" style="color:#0284c7;"></i>
                  2. Product Identity &amp; Tax Codes
                </div>
              </div>

              <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">Product Name / Model *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>" placeholder="e.g. Lenovo ThinkPad E14 Intel Core i5 13th Gen">
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                  <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">SKU Code *</label>
                  <input type="text" name="sku" class="form-control" required value="<?= htmlspecialchars($editProduct['sku'] ?? '') ?>" placeholder="e.g. COMP-LTP-001">
                </div>
                <div>
                  <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">HSN / SAC Code</label>
                  <input type="text" name="hsn_code" class="form-control" value="<?= htmlspecialchars($editProduct['hsn_code'] ?? '8471') ?>" placeholder="8471 (Computers) or 4802 (Paper)">
                </div>
              </div>
            </div>

            <!-- Bento Box 3: Description & Technical Specifications -->
            <div class="bento-card">
              <div class="bento-header">
                <div class="bento-title">
                  <i class="fas fa-file-lines" style="color:#6366f1;"></i>
                  3. Description &amp; Specifications
                </div>
              </div>

              <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">Product Description &amp; Features</label>
                <textarea name="description" rows="3" class="form-control" placeholder="Key highlights, warranty info, and commercial usage overview"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
              </div>

              <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">Specifications (JSON or Key-Value pairs)</label>
                <input type="text" name="specifications" class="form-control" value="<?= htmlspecialchars($editProduct['specifications'] ?? '') ?>" placeholder='{"Processor":"Intel i5","RAM":"16GB DDR5","GSM":"75 GSM"}'>
                <span style="font-size:0.72rem;color:var(--admin-text-muted);display:block;margin-top:4px;">Used to power the technical specifications table on the product page.</span>
              </div>
            </div>

          </div>

          <!-- RIGHT COLUMN: Pricing, Inventory & Multi-Photo Gallery -->
          <div>
            
            <!-- Bento Box 4: Commercial Pricing & Indian GST -->
            <div class="bento-card" style="border-top:3px solid #059669;">
              <div class="bento-header">
                <div class="bento-title">
                  <i class="fas fa-indian-rupee-sign" style="color:#059669;"></i>
                  4. Commercial Pricing &amp; GST
                </div>
                <span style="background:#ecfdf5;color:#059669;font-size:0.72rem;font-weight:800;padding:2px 8px;border-radius:6px;">
                  ITC Eligible
                </span>
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                  <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">Regular Price / MRP (₹)</label>
                  <input type="number" step="0.01" name="regular_price" class="form-control" required value="<?= htmlspecialchars($editProduct['regular_price'] ?? '') ?>" placeholder="e.g. 68900.00">
                </div>
                <div>
                  <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">Sale Price (₹) *</label>
                  <input type="number" step="0.01" name="sale_price" class="form-control" required value="<?= htmlspecialchars($editProduct['sale_price'] ?? '') ?>" placeholder="e.g. 59990.00">
                </div>
              </div>

              <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">GST Tax Rate (%)</label>
                <select name="gst_rate" class="form-control" style="font-weight:700;">
                  <option value="18" <?= ($editProduct && $editProduct['gst_rate'] == 18) ? 'selected' : '' ?>>18% (Computers, Electronics &amp; Hardware)</option>
                  <option value="12" <?= ($editProduct && $editProduct['gst_rate'] == 12) ? 'selected' : '' ?>>12% (Paper, Stationery &amp; Registers)</option>
                  <option value="5" <?= ($editProduct && $editProduct['gst_rate'] == 5) ? 'selected' : '' ?>>5%</option>
                  <option value="28" <?= ($editProduct && $editProduct['gst_rate'] == 28) ? 'selected' : '' ?>>28%</option>
                </select>
              </div>
            </div>

            <!-- Bento Box 5: Inventory & Homepage Promotion -->
            <div class="bento-card">
              <div class="bento-header">
                <div class="bento-title">
                  <i class="fas fa-warehouse" style="color:#d97706;"></i>
                  5. Stock &amp; Inventory Management
                </div>
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                  <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">Current Stock Qty *</label>
                  <input type="number" name="stock_qty" class="form-control" required value="<?= htmlspecialchars($editProduct['stock_qty'] ?? '10') ?>">
                </div>
                <div>
                  <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">Min Stock Alert</label>
                  <input type="number" name="min_stock_alert" class="form-control" value="<?= htmlspecialchars($editProduct['min_stock_alert'] ?? '3') ?>">
                </div>
              </div>

              <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:700;font-size:0.86rem;padding:10px;background:#f8fafc;border-radius:8px;border:1px solid var(--admin-border);">
                <input type="checkbox" name="is_featured" value="1" <?= (!empty($editProduct['is_featured'])) ? 'checked' : '' ?> style="accent-color:var(--admin-primary);width:18px;height:18px;">
                <span>Show in Homepage "Weekly Hot Deals" Showcase</span>
              </label>
            </div>

            <!-- Bento Box 6: Media & Auto-WebP Multi-Gallery -->
            <div class="bento-card" style="border-top:3px solid #3b82f6;">
              <div class="bento-header">
                <div class="bento-title">
                  <i class="fas fa-images" style="color:#3b82f6;"></i>
                  6. Media &amp; Multi-Photo WebP Gallery
                </div>
                <span style="background:#eff6ff;color:#1d4ed8;font-size:0.72rem;font-weight:800;padding:2px 8px;border-radius:6px;">
                  Auto WebP (85%)
                </span>
              </div>

              <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">Primary Cover Photo</label>
                <input type="file" name="product_image" class="form-control" accept="image/*">
                <?php if ($editProduct && !empty($editProduct['image_url'])): ?>
                  <div style="margin-top:8px;display:flex;align-items:center;gap:10px;font-size:0.78rem;background:#f8fafc;padding:6px 10px;border-radius:6px;border:1px solid #e2e8f0;">
                    <img src="<?= asset('assets/images/' . htmlspecialchars($editProduct['image_url'])) ?>" style="width:36px;height:36px;object-fit:contain;background:#ffffff;border:1px solid var(--admin-border);border-radius:4px;">
                    <span style="color:#334155;font-weight:600;"><?= htmlspecialchars($editProduct['image_url']) ?></span>
                  </div>
                <?php endif; ?>
              </div>

              <div>
                <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:6px;color:#1e293b;">
                  Upload Multiple Gallery Photos (Hold Ctrl/Shift)
                </label>
                <input type="file" name="gallery_images[]" multiple accept="image/*" class="form-control">
                <span style="font-size:0.72rem;color:var(--admin-text-muted);display:block;margin-top:4px;">
                  All uploaded photos are automatically converted into optimized WebP format.
                </span>

                <?php 
                if ($editProduct) {
                    $currentGallery = [];
                    if (!empty($editProduct['gallery_images'])) {
                        $decoded = json_decode($editProduct['gallery_images'], true);
                        if (is_array($decoded)) {
                            $currentGallery = $decoded;
                        }
                    }
                    if (!empty($currentGallery)):
                ?>
                  <div style="margin-top:14px;padding-top:10px;border-top:1px dashed #cbd5e1;">
                    <div style="font-size:0.78rem;font-weight:800;color:#0f172a;margin-bottom:8px;">Current Gallery Photos (Uncheck to remove):</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                      <?php foreach ($currentGallery as $gImg): ?>
                        <label style="display:flex;flex-direction:column;align-items:center;background:#f8fafc;border:1px solid var(--admin-border);border-radius:6px;padding:6px;cursor:pointer;width:80px;">
                          <img src="<?= asset('assets/images/' . htmlspecialchars($gImg)) ?>" style="width:65px;height:50px;object-fit:contain;background:#ffffff;border-radius:4px;margin-bottom:4px;">
                          <div style="display:flex;align-items:center;gap:4px;font-size:0.72rem;font-weight:700;color:#059669;">
                            <input type="checkbox" name="existing_gallery[]" value="<?= htmlspecialchars($gImg) ?>" checked style="accent-color:var(--admin-primary);">
                            Keep
                          </div>
                        </label>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php 
                    endif;
                } 
                ?>
              </div>
            </div>

          </div>

        </div>

        <!-- Sticky Action Bar -->
        <div style="display:flex;gap:14px;align-items:center;margin-top:10px;padding-top:16px;border-top:2px solid #e2e8f0;">
          <button type="submit" class="btn-sm-action btn-action-primary" style="padding:12px 28px;font-size:1rem;font-weight:900;box-shadow:0 4px 14px rgba(30,58,138,0.25);">
            <i class="fas fa-check"></i> <?= $editProduct ? 'Update Product' : 'Save Product' ?>
          </button>
          <?php if ($editProduct): ?>
            <a href="<?= url('admin/products.php') ?>" class="btn-sm-action" style="background:#ffffff;border:1px solid #cbd5e1;color:#0f172a;padding:12px 22px;font-size:0.92rem;font-weight:700;">Cancel Edit</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Products List Table -->
    <div class="card-table">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
          <h3>Products Catalog Directory (<?= count($products) ?> Items)</h3>
          <p style="font-size:0.75rem;color:var(--admin-text-muted);margin-top:2px;">Structured with MegaCompu Category &amp; Sub-Category hierarchy</p>
        </div>
        <span style="background:#eff6ff;color:var(--admin-primary);padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:800;">
          All Verified
        </span>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Image</th>
              <th>Product Details</th>
              <th>Category &amp; Sub-Category</th>
              <th>Sale Price</th>
              <th>Stock Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p): ?>
              <tr>
                <td style="width:60px;">
                  <img src="<?= asset('assets/images/' . htmlspecialchars($p['image_url'])) ?>" style="width:50px;height:50px;object-fit:contain;background:#f8fafc;border-radius:6px;border:1px solid var(--admin-border);">
                </td>
                <td>
                  <strong><?= htmlspecialchars($p['name']) ?></strong>
                  <div style="font-size:0.75rem;color:var(--admin-text-muted);margin-top:2px;">
                    SKU: <code><?= htmlspecialchars($p['sku']) ?></code> | HSN: <?= htmlspecialchars($p['hsn_code']) ?>
                    <?php if (!empty($p['brand'])): ?> | Brand: <strong><?= htmlspecialchars($p['brand']) ?></strong><?php endif; ?>
                  </div>
                </td>
                <td>
                  <span class="badge" style="background:<?= $p['category_type'] === 'computer' ? '#dbeafe' : '#dcfce7' ?>;color:<?= $p['category_type'] === 'computer' ? '#1d4ed8' : '#15803d' ?>;margin-bottom:3px;display:inline-block;">
                    <?= htmlspecialchars($p['category_name']) ?>
                  </span>
                  <?php if (!empty($p['subcategory_name'])): ?>
                    <div style="font-size:0.74rem;color:#475569;font-weight:600;">
                      <i class="fas fa-turn-up fa-rotate-90" style="color:var(--admin-primary);font-size:0.65rem;"></i> <?= htmlspecialchars($p['subcategory_name']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td style="font-weight:800;font-size:0.95rem;">
                  ₹<?= number_format($p['sale_price'], 2) ?>
                  <div style="font-size:0.72rem;color:var(--admin-text-muted);font-weight:normal;"><?= (int)$p['gst_rate'] ?>% GST</div>
                </td>
                <td>
                  <span class="badge <?= $p['stock_qty'] > $p['min_stock_alert'] ? 'badge-verified' : 'badge-rejected' ?>">
                    <?= $p['stock_qty'] ?> Units
                  </span>
                </td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a href="<?= url('admin/products.php?edit=' . $p['id']) ?>#productFormCard" class="btn-sm-action btn-action-primary" title="Edit">
                      <i class="fas fa-pen"></i>
                    </a>
                    <form action="<?= url('admin/products.php') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                      <button type="submit" class="btn-sm-action btn-reject" title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- Dynamic Sub-Category Filtering Script -->
<script>
  const subcategoriesData = <?= json_encode($allSubcats) ?>;
  const currentSubcatId = <?= json_encode($editProduct['subcategory_id'] ?? null) ?>;
  const catSelect = document.getElementById('catSelector');
  const subcatSelect = document.getElementById('subcatSelector');

  function updateSubcategories(catId, selectedSubId = null) {
    subcatSelect.innerHTML = '<option value="">-- Select Sub-Category --</option>';
    if (!catId) return;

    const filtered = subcategoriesData.filter(s => parseInt(s.category_id) === parseInt(catId));
    filtered.forEach(sub => {
      const opt = document.createElement('option');
      opt.value = sub.id;
      opt.textContent = sub.name;
      if (selectedSubId && parseInt(sub.id) === parseInt(selectedSubId)) {
        opt.selected = true;
      }
      subcatSelect.appendChild(opt);
    });
  }

  catSelect.addEventListener('change', function() {
    updateSubcategories(this.value);
  });

  // Initial populate on load
  if (catSelect.value) {
    updateSubcategories(catSelect.value, currentSubcatId);
  }
</script>
<script src="<?= asset('assets/js/admin.js') ?>"></script>
</body>
</html>
