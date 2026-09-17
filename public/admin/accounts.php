<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Order.php';

Auth::requireRole(['staff_accounts', 'admin']);
$user = Auth::user();

$msg = null;
$error = null;

// Handle Payment Verification Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = (int)$_POST['order_id'];
    $action = $_POST['action']; // 'verify' or 'reject'

    $status = ($action === 'verify') ? 'verified' : 'rejected';
    $res = Order::verifyPayment($orderId, $user['id'], $status, $_POST['notes'] ?? null);

    if ($res['success']) {
        if ($status === 'verified') {
            $msg = "Payment verified and cryptographically sealed with Payment Hash: " . $res['payment_hash'];
        } else {
            $msg = "Payment has been rejected.";
        }
    } else {
        $error = $res['message'];
    }
}

// Fetch orders needing payment verification or verified
$filter = $_GET['filter'] ?? 'pending';
$orders = Order::getAllOrders(['payment_status' => $filter]);

$pageTitle = "Accounts & Payment Verification - " . APP_NAME;
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
      <h1>💰 Accounts Department - Payment Verification</h1>
    </div>
    <div class="topbar-actions">
      <div style="display:flex;gap:10px;">
        <a href="<?= url('admin/accounts.php?filter=pending') ?>" class="btn-sm-action <?= $filter === 'pending' ? 'btn-action-primary' : '' ?>" style="background:<?= $filter === 'pending' ? 'var(--admin-primary)' : '#e2e8f0' ?>;color:<?= $filter === 'pending' ? '#fff' : '#0f172a' ?>;">
          Pending Review
        </a>
        <a href="<?= url('admin/accounts.php?filter=verified') ?>" class="btn-sm-action <?= $filter === 'verified' ? 'btn-action-primary' : '' ?>" style="background:<?= $filter === 'verified' ? 'var(--admin-primary)' : '#e2e8f0' ?>;color:<?= $filter === 'verified' ? '#fff' : '#0f172a' ?>;">
          Verified Payments
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
          <i class="fas fa-file-invoice-dollar" style="color:#b45309;"></i> 
          <?= $filter === 'pending' ? 'Orders Awaiting Payment Approval' : 'Approved Payment Records' ?> (<?= count($orders) ?>)
        </h3>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Order Number</th>
              <th>Customer</th>
              <th>Payable Amount</th>
              <th>Mode &amp; Reference (UTR)</th>
              <th>Payment Slip</th>
              <th>Verification Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($orders)): ?>
              <tr>
                <td colspan="6" style="text-align:center;padding:40px;color:var(--admin-text-muted);">
                  No orders found in this payment queue.
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
                    <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= htmlspecialchars($o['customer_phone']) ?></div>
                  </td>
                  <td style="font-size:1.1rem;font-weight:800;color:#0f172a;">
                    ₹<?= number_format($o['total_amount'], 2) ?>
                  </td>
                  <td>
                    <span class="badge" style="background:#f1f5f9;color:#0f172a;margin-bottom:4px;">
                      <?= strtoupper($o['payment_method']) ?>
                    </span>
                    <div style="font-family:monospace;font-size:0.85rem;font-weight:700;color:var(--admin-primary);">
                      <?= htmlspecialchars($o['payment_ref'] ?: 'No ref') ?>
                    </div>
                  </td>
                  <td>
                    <?php if (!empty($o['payment_proof_file'])): ?>
                      <a href="<?= asset(htmlspecialchars($o['payment_proof_file'])) ?>" target="_blank" class="btn-sm-action btn-action-primary" style="padding:4px 8px;font-size:0.75rem;">
                        <i class="fas fa-eye"></i> View Slip
                      </a>
                    <?php else: ?>
                      <span style="font-size:0.75rem;color:var(--admin-text-muted);">Direct Ref</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($o['payment_status'] === 'pending'): ?>
                      <div style="display:flex;gap:6px;">
                        <form action="<?= url('admin/accounts.php') ?>" method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="verify">
                          <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                          <button type="submit" class="btn-sm-action btn-approve" onclick="return confirm('Approve payment of ₹<?= $o['total_amount'] ?> for <?= $o['order_number'] ?>?');">
                            <i class="fas fa-check"></i> Approve
                          </button>
                        </form>
                        <form action="<?= url('admin/accounts.php') ?>" method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="reject">
                          <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                          <button type="submit" class="btn-sm-action btn-reject" onclick="return confirm('Reject this payment?');">
                            <i class="fas fa-times"></i> Reject
                          </button>
                        </form>
                      </div>
                    <?php else: ?>
                      <span class="badge badge-verified">Approved By <?= htmlspecialchars($o['accounts_staff_name'] ?? 'Staff') ?></span>
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
