<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Order.php';

Auth::requireStaff();
$user = Auth::user();

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$filters = [];
if (!empty($search)) $filters['search'] = $search;
if (!empty($status)) $filters['status'] = $status;

$orders = Order::getAllOrders($filters);

$pageTitle = "Master Orders List - " . APP_NAME;
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
      <h1>📦 Master Orders Directory</h1>
    </div>
    <div class="topbar-actions">
      <form action="<?= url('admin/orders.php') ?>" method="GET" style="display:flex;gap:10px;">
        <select name="status" class="form-control" style="width:160px;padding:7px 10px;font-size:0.85rem;" onchange="this.form.submit()">
          <option value="">All Order Statuses</option>
          <option value="placed" <?= $status === 'placed' ? 'selected' : '' ?>>Placed (Pending Payment)</option>
          <option value="payment_verified" <?= $status === 'payment_verified' ? 'selected' : '' ?>>Payment Verified</option>
          <option value="checked" <?= $status === 'checked' ? 'selected' : '' ?>>Checked &amp; Packed</option>
          <option value="dispatched" <?= $status === 'dispatched' ? 'selected' : '' ?>>Dispatched</option>
          <option value="delivered" <?= $status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
        </select>
        <input type="text" name="search" class="form-control" placeholder="Search order no. or customer..." value="<?= htmlspecialchars($search) ?>" style="width:240px;padding:7px 10px;font-size:0.85rem;">
        <button type="submit" class="btn-sm-action btn-action-primary"><i class="fas fa-search"></i></button>
      </form>
    </div>
  </header>

  <div class="admin-content">

    <div class="card-table">
      <div class="card-header">
        <h3>Orders Record List (<?= count($orders) ?>)</h3>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Order Number</th>
              <th>Customer</th>
              <th>Total Amount</th>
              <th>Payment Status</th>
              <th>Order Progress</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($orders)): ?>
              <tr>
                <td colspan="6" style="text-align:center;padding:40px;color:var(--admin-text-muted);">
                  No orders match the current criteria.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td>
                    <strong style="color:var(--admin-primary);"><?= htmlspecialchars($o['order_number']) ?></strong>
                    <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></div>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($o['customer_name']) ?></strong>
                    <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= htmlspecialchars($o['shipping_city']) ?> • <?= htmlspecialchars($o['customer_phone']) ?></div>
                  </td>
                  <td style="font-size:1.05rem;font-weight:800;color:#0f172a;">
                    ₹<?= number_format($o['total_amount'], 2) ?>
                  </td>
                  <td>
                    <span class="badge <?= $o['payment_status'] === 'verified' ? 'badge-verified' : ($o['payment_status'] === 'rejected' ? 'badge-rejected' : 'badge-pending') ?>">
                      <?= strtoupper($o['payment_status']) ?>
                    </span>
                    <?php if (!empty($o['accounts_staff_name'])): ?>
                      <div style="font-size:0.7rem;color:var(--admin-text-muted);margin-top:2px;">By <?= htmlspecialchars($o['accounts_staff_name']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= in_array($o['order_status'], ['delivered']) ? 'badge-delivered' : (in_array($o['order_status'], ['dispatched']) ? 'badge-dispatched' : 'badge-checked') ?>">
                      <?= strtoupper($o['order_status']) ?>
                    </span>
                    <?php if (!empty($o['tracking_number'])): ?>
                      <div style="font-size:0.72rem;color:var(--admin-text-muted);margin-top:2px;"><?= htmlspecialchars($o['courier_name']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display:flex;gap:6px;">
                      <a href="<?= url('invoice.php?hash=' . urlencode($o['order_hash'])) ?>" target="_blank" class="btn-sm-action" style="background:#f1f5f9;color:#334155;">
                        <i class="fas fa-print"></i> Invoice
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<script src="<?= asset('assets/js/admin.js') ?>"></script>
</body>
</html>
