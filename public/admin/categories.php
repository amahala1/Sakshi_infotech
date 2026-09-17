<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Product.php';

Auth::requireAdmin();
$db = Database::getConnection();

$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_category') {
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']) ?: strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $type = $_POST['type'] ?? 'computer';
        $desc = $_POST['description'] ?? '';
        $icon = $_POST['icon'] ?? 'fa-folder';

        $stmt = $db->prepare("INSERT INTO categories (name, slug, type, description, icon, status) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$name, $slug, $type, $desc, $icon]);
        $msg = "Main Category '{$name}' created.";
    } elseif ($_POST['action'] === 'save_subcategory') {
        $catId = (int)$_POST['category_id'];
        $name = trim($_POST['sub_name']);
        $slug = trim($_POST['sub_slug']) ?: strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $desc = $_POST['sub_description'] ?? '';

        $stmt = $db->prepare("INSERT INTO subcategories (category_id, name, slug, description, status) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$catId, $name, $slug, $desc]);
        $msg = "Sub-category '{$name}' created successfully.";
    } elseif ($_POST['action'] === 'delete_category') {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([(int)$_POST['category_id']]);
        $msg = "Category and its nested sub-categories deleted.";
    } elseif ($_POST['action'] === 'delete_subcategory') {
        $stmt = $db->prepare("DELETE FROM subcategories WHERE id = ?");
        $stmt->execute([(int)$_POST['subcategory_id']]);
        $msg = "Sub-category deleted.";
    }
}

$categoriesWithSubs = Product::getCategoriesWithSubcategories();
$allCategories = Product::getAllCategories();

$pageTitle = "Categories & Sub-Categories (MegaCompu Architecture) - " . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="admin-main">
  <header class="admin-topbar">
    <div class="topbar-title">
      <h1>📁 Categories &amp; Sub-Categories Directory</h1>
    </div>
  </header>

  <div class="admin-content">

    <?php if ($msg): ?>
      <div style="background:#d1fae5;border:1px solid #34d399;color:#065f46;padding:12px 18px;border-radius:10px;margin-bottom:20px;font-weight:700;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1.8fr;gap:25px;align-items:start;">
      
      <!-- Left Column: Forms -->
      <div style="display:flex;flex-direction:column;gap:20px;">
        
        <!-- 1. Add Sub-Category Form (MegaCompu Hierarchy) -->
        <div style="background:#ffffff;border:1px solid var(--admin-border);border-radius:14px;padding:22px;box-shadow:0 1px 3px rgba(0,0,0,0.05);border-top:3px solid var(--admin-primary);">
          <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:14px;color:#0f172a;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-network-wired" style="color:var(--admin-primary);"></i> Add New Sub-Category
          </h3>
          <p style="font-size:0.78rem;color:var(--admin-text-muted);margin-bottom:14px;">
            Create sub-categories under any parent category (e.g. Laptops & Desktops &rarr; Business Laptops).
          </p>

          <form action="<?= url('admin/categories.php') ?>" method="POST">
            <input type="hidden" name="action" value="save_subcategory">

            <div style="margin-bottom:12px;">
              <label style="display:block;font-size:0.8rem;font-weight:700;margin-bottom:4px;">Parent Category *</label>
              <select name="category_id" class="form-control" required style="font-weight:700;">
                <?php foreach ($allCategories as $c): ?>
                  <option value="<?= $c['id'] ?>">
                    [<?= strtoupper($c['type']) ?>] <?= htmlspecialchars($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div style="margin-bottom:12px;">
              <label style="display:block;font-size:0.8rem;font-weight:700;margin-bottom:4px;">Sub-Category Name *</label>
              <input type="text" name="sub_name" class="form-control" required placeholder="e.g. Business Laptops, A4 75 GSM">
            </div>

            <div style="margin-bottom:12px;">
              <label style="display:block;font-size:0.8rem;font-weight:700;margin-bottom:4px;">URL Slug (Optional)</label>
              <input type="text" name="sub_slug" class="form-control" placeholder="e.g. business-laptops">
            </div>

            <div style="margin-bottom:16px;">
              <label style="display:block;font-size:0.8rem;font-weight:700;margin-bottom:4px;">Description</label>
              <textarea name="sub_description" rows="2" class="form-control" placeholder="Brief overview of items in this sub-category"></textarea>
            </div>

            <button type="submit" class="btn-sm-action btn-action-primary" style="width:100%;padding:11px;font-size:0.9rem;justify-content:center;">
              <i class="fas fa-plus"></i> Save Sub-Category
            </button>
          </form>
        </div>

        <!-- 2. Add Main Category Form -->
        <div style="background:#ffffff;border:1px solid var(--admin-border);border-radius:14px;padding:22px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
          <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:14px;color:#0f172a;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-folder-plus" style="color:#059669;"></i> Add New Main Category
          </h3>

          <form action="<?= url('admin/categories.php') ?>" method="POST">
            <input type="hidden" name="action" value="save_category">

            <div style="margin-bottom:12px;">
              <label style="display:block;font-size:0.8rem;font-weight:700;margin-bottom:4px;">Department / Track *</label>
              <select name="type" class="form-control" required style="font-weight:700;">
                <option value="computer">💻 Computer Hardware &amp; IT Gadgets</option>
                <option value="stationery">📄 Office Stationery &amp; Printing Supplies</option>
              </select>
            </div>

            <div style="margin-bottom:12px;">
              <label style="display:block;font-size:0.8rem;font-weight:700;margin-bottom:4px;">Category Name *</label>
              <input type="text" name="name" class="form-control" required placeholder="e.g. Audio &amp; Video">
            </div>

            <div style="margin-bottom:12px;">
              <label style="display:block;font-size:0.8rem;font-weight:700;margin-bottom:4px;">FontAwesome Icon Class</label>
              <input type="text" name="icon" class="form-control" value="fa-folder" placeholder="fa-headphones, fa-camera">
            </div>

            <button type="submit" class="btn-sm-action" style="background:#059669;color:#ffffff;width:100%;padding:11px;font-size:0.9rem;justify-content:center;">
              <i class="fas fa-plus"></i> Save Main Category
            </button>
          </form>
        </div>

      </div>

      <!-- Right Column: Hierarchical Categories & Subcategories Tree -->
      <div class="card-table">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
          <div>
            <h3>Category &amp; Sub-Category Tree (MegaCompu Style)</h3>
            <p style="font-size:0.75rem;color:var(--admin-text-muted);margin-top:2px;">Showing all parent categories with nested subcategories and product counts.</p>
          </div>
          <span style="background:#eff6ff;color:var(--admin-primary);padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:800;">
            <?= count($categoriesWithSubs) ?> Main Categories
          </span>
        </div>

        <div style="padding:15px;">
          <?php foreach ($categoriesWithSubs as $cat): ?>
            <div style="background:#f8fafc;border:1px solid var(--admin-border);border-radius:10px;padding:16px;margin-bottom:16px;">
              
              <!-- Parent Category Bar -->
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;padding-bottom:10px;border-bottom:1.5px solid #e2e8f0;">
                <div style="display:flex;align-items:center;gap:10px;">
                  <span style="width:36px;height:36px;border-radius:8px;background:#ffffff;border:1px solid var(--admin-border);display:flex;align-items:center;justify-content:center;color:var(--admin-primary);font-size:1.1rem;">
                    <i class="fas <?= htmlspecialchars($cat['icon']) ?>"></i>
                  </span>
                  <div>
                    <strong style="font-size:1rem;color:#0f172a;"><?= htmlspecialchars($cat['name']) ?></strong>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:2px;">
                      <span class="badge" style="background:<?= $cat['type'] === 'computer' ? '#dbeafe' : '#dcfce7' ?>;color:<?= $cat['type'] === 'computer' ? '#1d4ed8' : '#15803d' ?>;font-size:0.68rem;">
                        <?= strtoupper($cat['type']) ?>
                      </span>
                      <span style="font-size:0.72rem;color:var(--admin-text-muted);">
                        Total Products: <strong><?= $cat['product_count'] ?></strong>
                      </span>
                    </div>
                  </div>
                </div>

                <form action="<?= url('admin/categories.php') ?>" method="POST" onsubmit="return confirm('Delete this entire category and its sub-categories?');">
                  <input type="hidden" name="action" value="delete_category">
                  <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                  <button type="submit" class="btn-sm-action btn-reject" title="Delete Parent Category" style="padding:5px 9px;">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>

              <!-- Nested Sub-Categories Chips / List -->
              <div style="padding-left:10px;">
                <div style="font-size:0.75rem;font-weight:800;color:var(--admin-text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">
                  Sub-Categories (<?= count($cat['subcategories']) ?>):
                </div>
                
                <?php if (empty($cat['subcategories'])): ?>
                  <div style="font-size:0.8rem;color:#94a3b8;font-style:italic;">No sub-categories yet. Use the form on the left to add one.</div>
                <?php else: ?>
                  <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:10px;">
                    <?php foreach ($cat['subcategories'] as $sub): ?>
                      <div style="background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;padding:8px 12px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 2px rgba(0,0,0,0.03);">
                        <div>
                          <div style="font-size:0.84rem;font-weight:700;color:#1e293b;"><?= htmlspecialchars($sub['name']) ?></div>
                          <div style="font-size:0.7rem;color:var(--admin-primary);font-weight:600;">
                            <?= $sub['product_count'] ?> Products
                          </div>
                        </div>
                        <form action="<?= url('admin/categories.php') ?>" method="POST" onsubmit="return confirm('Delete subcategory <?= htmlspecialchars($sub['name']) ?>?');">
                          <input type="hidden" name="action" value="delete_subcategory">
                          <input type="hidden" name="subcategory_id" value="<?= $sub['id'] ?>">
                          <button type="submit" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:0.75rem;padding:4px;" title="Delete Subcategory">
                            <i class="fas fa-times"></i>
                          </button>
                        </form>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

  </div>
</main>

<script src="<?= asset('assets/js/admin.js') ?>"></script>
</body>
</html>
