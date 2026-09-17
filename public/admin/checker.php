<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Order.php';

Auth::requireRole(['staff_checker', 'admin']);
$user = Auth::user();

$msg = null;
$error = null;

// Handle Order Check & Pack Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_order') {
    $orderId = (int)$_POST['order_id'];
    $res = Order::checkOrder($orderId, $user['id'], $_POST['notes'] ?? null);
    if ($res['success']) {
        $msg = "Order checked, verified with stock inventory, and marked ready for packing & dispatch.";
    } else {
        $error = $res['message'];
    }
}

// Fetch orders ready for checking or already checked
$filter = $_GET['filter'] ?? 'pending_check';
$statusCondition = ($filter === 'pending_check') ? 'payment_verified' : 'checked';
$orders = Order::getAllOrders(['status' => $statusCondition]);

$pageTitle = "Order Checker Department - " . APP_NAME;
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
      <h1>📦 Order Checker &amp; Packing Department</h1>
    </div>
    <div class="topbar-actions">
      <div style="display:flex;gap:10px;">
        <a href="<?= url('admin/checker.php?filter=pending_check') ?>" class="btn-sm-action <?= $filter === 'pending_check' ? 'btn-action-primary' : '' ?>" style="background:<?= $filter === 'pending_check' ? 'var(--admin-primary)' : '#e2e8f0' ?>;color:<?= $filter === 'pending_check' ? '#fff' : '#0f172a' ?>;">
          Pending Packing Inspection
        </a>
        <a href="<?= url('admin/checker.php?filter=checked') ?>" class="btn-sm-action <?= $filter === 'checked' ? 'btn-action-primary' : '' ?>" style="background:<?= $filter === 'checked' ? 'var(--admin-primary)' : '#e2e8f0' ?>;color:<?= $filter === 'checked' ? '#fff' : '#0f172a' ?>;">
          Inspected &amp; Packed
        </a>
      </div>
    </div>
  </header>

  <div class="admin-content">

    <?php if ($msg): ?>
      <div style="background:#d1fae5;border:1px solid #34d399;color:#065f46;padding:14px 20px;border-radius:10px;margin-bottom:20px;font-weight:700;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div style="background:#fee2e2;border:1px solid #f87171;color:#991b1b;padding:14px 20px;border-radius:10px;margin-bottom:20px;font-weight:700;">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <div class="card-table">
      <div class="card-header">
        <h3>
          <i class="fas fa-clipboard-check" style="color:#059669;"></i> 
          <?= $filter === 'pending_check' ? 'Orders Awaiting Item Inspection & Packing' : 'Packed Orders Ready for Dispatch' ?> (<?= count($orders) ?>)
        </h3>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Order Number</th>
              <th>Customer &amp; Shipping City</th>
              <th>Ordered Products (SKU &amp; Qty)</th>
              <th>Accounts Verification</th>
              <th>Check &amp; Pack Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($orders)): ?>
              <tr>
                <td colspan="5" style="text-align:center;padding:40px;color:var(--admin-text-muted);">
                  No orders waiting for physical inspection in this queue.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($orders as $o): 
                $fullOrder = Order::getOrderById($o['id']);
              ?>
                <tr>
                  <td>
                    <strong style="color:var(--admin-primary);"><?= htmlspecialchars($o['order_number']) ?></strong>
                    <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= date('d M, h:i A', strtotime($o['created_at'])) ?></div>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($o['customer_name']) ?></strong>
                    <div style="font-size:0.8rem;color:var(--admin-text-muted);"><?= htmlspecialchars($o['shipping_city']) ?>, <?= htmlspecialchars($o['shipping_state']) ?></div>
                    <?php if (!empty($o['customer_notes'])): ?>
                      <div style="font-size:0.75rem;color:#b45309;margin-top:4px;">Note: <?= htmlspecialchars($o['customer_notes']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display:flex;flex-direction:column;gap:6px;">
                      <?php foreach ($fullOrder['items'] as $item): ?>
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:6px 10px;border-radius:6px;font-size:0.82rem;">
                          <strong><?= htmlspecialchars($item['product_name']) ?></strong><br>
                          <span style="color:var(--admin-text-muted);font-size:0.75rem;">SKU: <?= htmlspecialchars($item['sku']) ?></span> | 
                          <span style="font-weight:800;color:var(--admin-primary);">Qty: <?= $item['quantity'] ?></span>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </td>
                  <td>
                    <span class="badge badge-verified"><i class="fas fa-check"></i> Paid &amp; Approved</span>
                    <div style="font-size:0.75rem;color:var(--admin-text-muted);margin-top:2px;">By <?= htmlspecialchars($o['accounts_staff_name'] ?? 'Accounts') ?></div>
                  </td>
                  <td>
                    <?php if ($filter === 'pending_check'): ?>
                      <form action="<?= url('admin/checker.php') ?>" method="POST">
                        <input type="hidden" name="action" value="check_order">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <button type="submit" class="btn-sm-action btn-approve" style="padding:9px 15px;" onclick="return confirm('Confirm all items have been inspected and packed correctly for <?= $o['order_number'] ?>?');">
                          <i class="fas fa-box-check"></i> Mark Inspected &amp; Packed
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="badge badge-checked"><i class="fas fa-check-double"></i> Checked By <?= htmlspecialchars($o['checker_staff_name'] ?? 'Checker') ?></span>
                    <?php endif; ?>
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
