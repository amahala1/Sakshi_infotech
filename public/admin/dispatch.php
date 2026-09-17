<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Order.php';

Auth::requireRole(['staff_dispatch', 'admin']);
$user = Auth::user();

$msg = null;
$error = null;

// Handle Dispatch Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'dispatch') {
        $orderId = (int)$_POST['order_id'];
        $courier = $_POST['courier_name'] ?? 'DTDC Express';
        $trackingNo = $_POST['tracking_number'] ?? '';

        if (empty($trackingNo)) {
            $error = "Please enter the courier tracking / AWB number.";
        } else {
            $res = Order::dispatchOrder($orderId, $user['id'], $courier, $trackingNo);
            if ($res['success']) {
                $msg = "Parcel dispatched! Generated Dispatch Hash: " . $res['dispatch_hash'];
            } else {
                $error = $res['message'];
            }
        }
    } elseif ($_POST['action'] === 'mark_delivered') {
        $orderId = (int)$_POST['order_id'];
        Order::markDelivered($orderId, $user['id']);
        $msg = "Order successfully marked as Delivered.";
    }
}

// Fetch orders ready for dispatch or already dispatched
$filter = $_GET['filter'] ?? 'ready_dispatch';
$statusCondition = ($filter === 'ready_dispatch') ? 'checked' : 'dispatched';
$orders = Order::getAllOrders(['status' => $statusCondition]);

$pageTitle = "Dispatch & Logistics Department - " . APP_NAME;
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
      <h1>🚚 Dispatch &amp; Logistics Department</h1>
    </div>
    <div class="topbar-actions">
      <div style="display:flex;gap:10px;">
        <a href="<?= url('admin/dispatch.php?filter=ready_dispatch') ?>" class="btn-sm-action <?= $filter === 'ready_dispatch' ? 'btn-action-primary' : '' ?>" style="background:<?= $filter === 'ready_dispatch' ? 'var(--admin-primary)' : '#e2e8f0' ?>;color:<?= $filter === 'ready_dispatch' ? '#fff' : '#0f172a' ?>;">
          Ready to Dispatch
        </a>
        <a href="<?= url('admin/dispatch.php?filter=dispatched') ?>" class="btn-sm-action <?= $filter === 'dispatched' ? 'btn-action-primary' : '' ?>" style="background:<?= $filter === 'dispatched' ? 'var(--admin-primary)' : '#e2e8f0' ?>;color:<?= $filter === 'dispatched' ? '#fff' : '#0f172a' ?>;">
          In Transit / Dispatched
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
          <i class="fas fa-truck-fast" style="color:#0284c7;"></i> 
          <?= $filter === 'ready_dispatch' ? 'Orders Packed & Ready for Courier Handover' : 'Active Dispatches in Transit' ?> (<?= count($orders) ?>)
        </h3>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Order Number</th>
              <th>Consignee / Destination</th>
              <th>Packed Items</th>
              <th>Checker Approval</th>
              <th>Logistics Details</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($orders)): ?>
              <tr>
                <td colspan="6" style="text-align:center;padding:40px;color:var(--admin-text-muted);">
                  No orders found in this dispatch queue.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($orders as $o): 
                $fullOrder = Order::getOrderById($o['id']);
              ?>
                <tr>
                  <td>
                    <strong style="color:var(--admin-primary);"><?= htmlspecialchars($o['order_number']) ?></strong>
                    <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></div>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($o['customer_name']) ?></strong>
                    <div style="font-size:0.78rem;color:var(--admin-text-muted);line-height:1.3;margin-top:2px;">
                      <?= htmlspecialchars($o['shipping_address']) ?><br>
                      <?= htmlspecialchars($o['shipping_city']) ?>, <?= htmlspecialchars($o['shipping_state']) ?> - <?= htmlspecialchars($o['shipping_pincode']) ?>
                    </div>
                    <div style="font-size:0.75rem;color:var(--admin-primary);font-weight:700;">📞 <?= htmlspecialchars($o['customer_phone']) ?></div>
                  </td>
                  <td>
                    <div style="font-size:0.82rem;font-weight:700;">
                      <?= count($fullOrder['items']) ?> items packed
                    </div>
                    <div style="font-size:0.75rem;color:var(--admin-text-muted);">
                      ₹<?= number_format($o['total_amount'], 2) ?>
                    </div>
                  </td>
                  <td>
                    <span class="badge badge-checked"><i class="fas fa-check"></i> Packed</span>
                    <div style="font-size:0.72rem;color:var(--admin-text-muted);margin-top:2px;">By <?= htmlspecialchars($o['checker_staff_name'] ?? 'Checker') ?></div>
                  </td>

                  <?php if ($filter === 'ready_dispatch'): ?>
                    <td colspan="2">
                      <form action="<?= url('admin/dispatch.php') ?>" method="POST" style="display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="action" value="dispatch">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <select name="courier_name" class="form-control" style="width:140px;padding:6px 8px;font-size:0.82rem;">
                          <option value="BlueDart Express">BlueDart Express</option>
                          <option value="DTDC Express" selected>DTDC Express</option>
                          <option value="Delhivery Logistics">Delhivery</option>
                          <option value="Speed Post">Speed Post</option>
                          <option value="Local Van Delivery">Local Van Delivery</option>
                        </select>
                        <input type="text" name="tracking_number" class="form-control" placeholder="AWB / Tracking No." required style="width:160px;padding:6px 8px;font-size:0.82rem;">
                        <button type="submit" class="btn-sm-action btn-action-primary" style="padding:7px 12px;white-space:nowrap;">
                          <i class="fas fa-truck-ramp-box"></i> Mark Dispatched
                        </button>
                      </form>
                    </td>
                  <?php else: ?>
                    <td>
                      <strong><?= htmlspecialchars($o['courier_name']) ?></strong>
                      <div style="font-family:monospace;font-size:0.82rem;color:var(--admin-primary);font-weight:700;">
                        AWB: <?= htmlspecialchars($o['tracking_number']) ?>
                      </div>
                    </td>
                    <td>
                      <form action="<?= url('admin/dispatch.php') ?>" method="POST">
                        <input type="hidden" name="action" value="mark_delivered">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <button type="submit" class="btn-sm-action btn-approve" onclick="return confirm('Confirm parcel delivered to customer?');">
                          <i class="fas fa-check-double"></i> Mark Delivered
                        </button>
                      </form>
                    </td>
                  <?php endif; ?>

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
