<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Order.php';

$hash = $_GET['hash'] ?? '';
$order = Order::getOrderByHash($hash);

if (!$order) {
    header("Location: " . (function_exists('url') ? url('sale.php') : '/sale.php'));
    exit;
}

$pageTitle = "Order Confirmed - " . $order['order_number'] . " | " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="section-wrapper" style="margin-top:35px;max-width:850px;">
  <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:40px;box-shadow:var(--shadow-md);text-align:center;">
    
    <div style="width:75px;height:75px;border-radius:50%;background:var(--success-light);color:var(--success);display:flex;align-items:center;justify-content:center;font-size:2.2rem;margin:0 auto 20px;">
      <i class="fas fa-check"></i>
    </div>

    <h1 style="font-size:2rem;font-weight:900;color:var(--text-main);margin-bottom:8px;">
      Order Successfully Placed!
    </h1>
    <p style="color:var(--text-muted);font-size:1rem;margin-bottom:25px;">
      Thank you, <strong><?= htmlspecialchars($order['customer_name']) ?></strong>! Your order has been recorded in the Sakshi Infotech database.
    </p>

    <!-- Order Details Strip -->
    <div style="background:#f8fafc;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:20px;margin-bottom:30px;display:grid;grid-template-columns:repeat(3, 1fr);gap:15px;text-align:left;">
      <div>
        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Order Number</div>
        <div style="font-size:1.1rem;font-weight:800;color:var(--primary);margin-top:4px;"><?= htmlspecialchars($order['order_number']) ?></div>
      </div>
      <div>
        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Total Amount (GST Incl.)</div>
        <div style="font-size:1.1rem;font-weight:800;color:#0f172a;margin-top:4px;">₹<?= number_format($order['total_amount'], 2) ?></div>
      </div>
      <div>
        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Payment Status</div>
        <div style="margin-top:4px;">
          <span class="badge <?= $order['payment_status'] === 'verified' ? 'badge-verified' : 'badge-pending' ?>">
            <?= strtoupper($order['payment_status']) ?>
          </span>
        </div>
      </div>
    </div>

    <!-- Order Dispatch & GST Invoicing Notice -->
    <div style="background:linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);color:#1e293b;border-radius:var(--radius-lg);padding:20px;margin-bottom:30px;text-align:left;border:1px solid #bfdbfe;box-shadow:0 4px 12px rgba(37,99,235,0.06);">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
        <i class="fas fa-file-invoice-dollar" style="color:var(--primary);font-size:1.4rem;"></i>
        <h4 style="margin:0;font-size:1rem;font-weight:800;color:#0f172a;">Order Placed Successfully with GST Invoicing</h4>
      </div>
      <p style="font-size:0.85rem;color:#475569;line-height:1.5;margin:4px 0 0;">
        Your order <strong><?= htmlspecialchars($order['order_number']) ?></strong> has been recorded in the system. Our accounts team will verify your payment receipt and prepare dispatch.
      </p>
    </div>

    <!-- Order Workflow Timeline -->
    <div style="text-align:left;margin-bottom:35px;">
      <h3 style="font-size:1.05rem;font-weight:800;margin-bottom:15px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-route" style="color:var(--primary);"></i> Multi-Role Processing Timeline
      </h3>

      <div class="tracking-timeline">
        <!-- 1. Placed -->
        <div class="timeline-step completed">
          <div class="timeline-icon"><i class="fas fa-shopping-cart"></i></div>
          <div class="timeline-label">Order Placed</div>
          <div class="timeline-time"><?= date('d M, H:i', strtotime($order['created_at'])) ?></div>
        </div>

        <!-- 2. Accounts -->
        <div class="timeline-step <?= $order['payment_status'] === 'verified' ? 'completed' : ($order['order_status'] === 'placed' ? 'active' : '') ?>">
          <div class="timeline-icon"><i class="fas fa-file-invoice-dollar"></i></div>
          <div class="timeline-label">Accounts Check</div>
          <div class="timeline-time"><?= $order['payment_status'] === 'verified' ? 'Verified' : 'In Review' ?></div>
        </div>

        <!-- 3. Checker -->
        <div class="timeline-step <?= in_array($order['order_status'], ['checked', 'dispatched', 'delivered']) ? 'completed' : ($order['payment_status'] === 'verified' ? 'active' : '') ?>">
          <div class="timeline-icon"><i class="fas fa-clipboard-check"></i></div>
          <div class="timeline-label">Order Checked</div>
          <div class="timeline-time"><?= in_array($order['order_status'], ['checked', 'dispatched', 'delivered']) ? 'Packed' : 'Pending' ?></div>
        </div>

        <!-- 4. Dispatch -->
        <div class="timeline-step <?= in_array($order['order_status'], ['dispatched', 'delivered']) ? 'completed' : '' ?>">
          <div class="timeline-icon"><i class="fas fa-truck-fast"></i></div>
          <div class="timeline-label">Dispatched</div>
          <div class="timeline-time"><?= !empty($order['courier_name']) ? htmlspecialchars($order['courier_name']) : 'Logistics' ?></div>
        </div>

        <!-- 5. Delivered -->
        <div class="timeline-step <?= $order['order_status'] === 'delivered' ? 'completed' : '' ?>">
          <div class="timeline-icon"><i class="fas fa-house-circle-check"></i></div>
          <div class="timeline-label">Delivered</div>
          <div class="timeline-time"><?= $order['order_status'] === 'delivered' ? 'Received' : 'Estimated 2-3 Days' ?></div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div style="display:flex;justify-content:center;gap:15px;flex-wrap:wrap;">
      <a href="<?= url('invoice.php?hash=' . urlencode($order['order_hash'])) ?>" target="_blank" class="btn-primary-si">
        <i class="fas fa-print"></i> Download / Print GST Invoice
      </a>
      <a href="<?= url('my_orders.php') ?>" class="btn-outline-si" style="color:var(--text-main);border-color:var(--border-color);">
        <i class="fas fa-boxes-stacked"></i> My Orders History
      </a>
      <a href="<?= url('shop.php') ?>" class="btn-outline-si" style="color:var(--text-main);border-color:var(--border-color);">
        <i class="fas fa-shopping-bag"></i> Continue Shopping
      </a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
