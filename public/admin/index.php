<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Product.php';
require_once BASE_PATH . '/src/Order.php';

Auth::requireStaff();
$user = Auth::user();

$db = Database::getConnection();

// Summary stats
$totalRev = $db->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'verified'")->fetchColumn() ?: 0.0;
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
$pendingPayments = $db->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn() ?: 0;
$readyToCheck = $db->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'verified' AND order_status = 'payment_verified'")->fetchColumn() ?: 0;
$readyToDispatch = $db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'checked'")->fetchColumn() ?: 0;
$lowStockCount = count(Product::getLowStockProducts());
$totalStaff = $db->query("SELECT COUNT(*) FROM users WHERE role LIKE 'staff_%'")->fetchColumn() ?: 0;

$recentOrders = Order::getAllOrders(['limit' => 6]);

$pageTitle = "Control Panel - " . APP_NAME;
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
  <!-- Topbar -->
  <header class="admin-topbar">
    <div class="topbar-title">
      <h1>Operational Dashboard</h1>
    </div>
    <div class="topbar-actions">
      <a href="<?= url('sale.php') ?>" target="_blank" class="btn-sm-action btn-action-primary" style="padding:8px 14px;">
        <i class="fas fa-external-link-alt"></i> View Storefront
      </a>
    </div>
  </header>

  <div class="admin-content">
    
    <!-- Welcome Greeting (Fresh Light Palette - No dark blocks) -->
    <div style="background:linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);border:1px solid #bfdbfe;color:#1e3a8a;padding:24px 28px;border-radius:14px;margin-bottom:30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;box-shadow:0 4px 12px rgba(37,99,235,0.06);">
      <div>
        <span style="font-size:0.75rem;color:#2563eb;font-weight:800;letter-spacing:1px;text-transform:uppercase;">SAKSHI INFOTECH OPERATING SYSTEM</span>
        <h2 style="font-size:1.55rem;font-weight:900;margin-top:4px;color:#1e293b;">
          Welcome, <?= htmlspecialchars($user['full_name']) ?>!
        </h2>
        <p style="color:#475569;font-size:0.9rem;margin-top:4px;">
          Logged in as <strong style="color:#0284c7;"><?= Staff::getRoleName($user['role']) ?></strong>. Commercial IT hardware &amp; stationery inventory system.
        </p>
      </div>

      <!-- Quick Role Shortcut Button -->
      <div>
        <?php if ($user['role'] === 'staff_accounts' || $user['role'] === 'admin'): ?>
          <a href="<?= url('admin/accounts.php') ?>" class="btn-sm-action" style="background:#f59e0b;color:#ffffff;padding:10px 18px;font-size:0.85rem;font-weight:800;">
            <i class="fas fa-file-invoice-dollar"></i> Verify Pending Payments (<?= $pendingPayments ?>)
          </a>
        <?php elseif ($user['role'] === 'staff_checker'): ?>
          <a href="<?= url('admin/checker.php') ?>" class="btn-sm-action" style="background:#059669;color:#ffffff;padding:10px 18px;font-size:0.85rem;font-weight:800;">
            <i class="fas fa-clipboard-check"></i> Check &amp; Pack Orders (<?= $readyToCheck ?>)
          </a>
        <?php elseif ($user['role'] === 'staff_dispatch'): ?>
          <a href="<?= url('admin/dispatch.php') ?>" class="btn-sm-action" style="background:#0284c7;color:#ffffff;padding:10px 18px;font-size:0.85rem;font-weight:800;">
            <i class="fas fa-truck-fast"></i> Dispatch Ready Parcels (<?= $readyToDispatch ?>)
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-indian-rupee-sign"></i></div>
        <div>
          <div class="stat-val">₹<?= number_format($totalRev, 2) ?></div>
          <div class="stat-lbl">Verified Revenue</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background:#e0e7ff;color:#4338ca;"><i class="fas fa-boxes-stacked"></i></div>
        <div>
          <div class="stat-val"><?= $totalOrders ?></div>
          <div class="stat-lbl">Total Orders Placed</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#b45309;"><i class="fas fa-file-invoice-dollar"></i></div>
        <div>
          <div class="stat-val"><?= $pendingPayments ?></div>
          <div class="stat-lbl">Pending Payment Review</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background:#cffafe;color:#0e7490;"><i class="fas fa-truck-ramp-box"></i></div>
        <div>
          <div class="stat-val"><?= $readyToDispatch ?></div>
          <div class="stat-lbl">Awaiting Dispatch</div>
        </div>
      </div>

      <?php if (Auth::isAdmin()): ?>
        <div class="stat-card">
          <div class="stat-icon" style="background:#fee2e2;color:#b91c1c;"><i class="fas fa-triangle-exclamation"></i></div>
          <div>
            <div class="stat-val"><?= $lowStockCount ?></div>
            <div class="stat-lbl">Low Stock Alerts</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background:#fce7f3;color:#be185d;"><i class="fas fa-users-gear"></i></div>
          <div>
            <div class="stat-val"><?= $totalStaff ?></div>
            <div class="stat-lbl">Active Staff Members</div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Recent Orders Table -->
    <div class="card-table">
      <div class="card-header">
        <h3><i class="fas fa-clock-rotate-left" style="color:var(--admin-primary);"></i> Recent Orders</h3>
        <a href="<?= url('admin/orders.php') ?>" style="font-size:0.85rem;font-weight:700;color:var(--admin-primary);">View All Orders &rarr;</a>
      </div>
      
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Order No.</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Order Status</th>
              <th>Action Required</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $o): ?>
              <tr>
                <td>
                  <strong style="color:var(--admin-primary);"><?= htmlspecialchars($o['order_number']) ?></strong>
                  <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= date('d M, h:i A', strtotime($o['created_at'])) ?></div>
                </td>
                <td>
                  <strong><?= htmlspecialchars($o['customer_name']) ?></strong>
                  <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= htmlspecialchars($o['customer_phone']) ?></div>
                </td>
                <td style="font-weight:800;">₹<?= number_format($o['total_amount'], 2) ?></td>
                <td>
                  <span class="badge <?= $o['payment_status'] === 'verified' ? 'badge-verified' : ($o['payment_status'] === 'rejected' ? 'badge-rejected' : 'badge-pending') ?>">
                    <?= strtoupper($o['payment_status']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= in_array($o['order_status'], ['delivered']) ? 'badge-delivered' : (in_array($o['order_status'], ['dispatched']) ? 'badge-dispatched' : 'badge-checked') ?>">
                    <?= strtoupper($o['order_status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($o['payment_status'] === 'pending' && Auth::isAccountsStaff()): ?>
                    <a href="<?= url('admin/accounts.php') ?>" class="btn-sm-action btn-approve"><i class="fas fa-check"></i> Verify Payment</a>
                  <?php elseif ($o['order_status'] === 'payment_verified' && Auth::isCheckerStaff()): ?>
                    <a href="<?= url('admin/checker.php') ?>" class="btn-sm-action btn-action-primary"><i class="fas fa-clipboard-check"></i> Check Items</a>
                  <?php elseif ($o['order_status'] === 'checked' && Auth::isDispatchStaff()): ?>
                    <a href="<?= url('admin/dispatch.php') ?>" class="btn-sm-action btn-action-primary" style="background:#0284c7;"><i class="fas fa-truck"></i> Dispatch</a>
                  <?php else: ?>
                    <a href="<?= url('invoice.php?hash=' . urlencode($o['order_hash'])) ?>" target="_blank" class="btn-sm-action" style="background:#f1f5f9;color:#334155;"><i class="fas fa-print"></i> Invoice</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<script src="<?= asset('assets/js/admin.js') ?>"></script>
</body>
</html>
