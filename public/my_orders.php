<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Order.php';

Auth::requireLogin();
$user = Auth::user();
$orders = Order::getOrdersByUserId($user['id']);

$pageTitle = "My Orders - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="section-wrapper" style="margin-top:25px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
    <div>
      <h1 style="font-size:1.8rem;font-weight:800;display:flex;align-items:center;gap:12px;">
        <i class="fas fa-boxes-stacked" style="color:var(--primary);"></i> My Order History
      </h1>
      <p style="color:var(--text-muted);font-size:0.9rem;margin-top:4px;">
        Track real-time accounts verification, packing, and courier dispatch status for your purchases.
      </p>
    </div>
    <a href="<?= url('shop.php') ?>" class="btn-primary-si" style="padding:10px 20px;font-size:0.88rem;">
      <i class="fas fa-plus"></i> Shop More Items
    </a>
  </div>

  <?php if (empty($orders)): ?>
    <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:60px 20px;text-align:center;box-shadow:var(--shadow-sm);">
      <i class="fas fa-box-open" style="font-size:3.5rem;color:#cbd5e1;margin-bottom:15px;"></i>
      <h3>No orders placed yet</h3>
      <p style="color:var(--text-muted);font-size:0.9rem;margin:8px 0 20px;">You haven't ordered any computer hardware or stationery supplies yet.</p>
      <a href="<?= url('shop.php') ?>" class="btn-primary-si" style="display:inline-flex;">Explore Catalog</a>
    </div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:25px;">
      <?php foreach ($orders as $o): 
        $orderDetails = Order::getOrderById($o['id']);
      ?>
        <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:25px;box-shadow:var(--shadow-sm);">
          
          <!-- Top bar of order card -->
          <div style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:16px;border-bottom:1px solid var(--border-color);flex-wrap:wrap;gap:12px;">
            <div>
              <div style="display:flex;align-items:center;gap:12px;">
                <h3 style="font-size:1.15rem;font-weight:800;color:var(--primary);"><?= htmlspecialchars($o['order_number']) ?></h3>
                <span class="badge <?= in_array($o['order_status'], ['delivered']) ? 'badge-delivered' : (in_array($o['order_status'], ['dispatched']) ? 'badge-dispatched' : 'badge-pending') ?>">
                  <?= strtoupper($o['order_status']) ?>
                </span>
                <span class="badge <?= $o['payment_status'] === 'verified' ? 'badge-verified' : 'badge-pending' ?>">
                  PAYMENT: <?= strtoupper($o['payment_status']) ?>
                </span>
              </div>
              <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;">
                Placed on: <strong><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></strong> | Mode: <strong><?= strtoupper(str_replace('_', ' ', $o['payment_method'])) ?></strong>
              </div>
            </div>

            <div style="text-align:right;">
              <div style="font-size:1.3rem;font-weight:900;color:#0f172a;">₹<?= number_format($o['total_amount'], 2) ?></div>
              <div style="font-size:0.75rem;color:var(--text-muted);">Total with GST</div>
            </div>
          </div>

          <!-- Items Row -->
          <div style="padding:16px 0;display:flex;flex-wrap:wrap;gap:16px;">
            <?php if (!empty($orderDetails['items'])): ?>
              <?php foreach ($orderDetails['items'] as $item): ?>
                <div style="display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid var(--border-color);border-radius:var(--radius-md);padding:8px 12px;font-size:0.85rem;">
                  <img src="<?= asset('assets/images/' . htmlspecialchars($item['image_url'] ?: 'laptop-lenovo.svg')) ?>" style="width:36px;height:36px;object-fit:contain;">
                  <div>
                    <strong style="color:var(--text-main);"><?= htmlspecialchars($item['product_name']) ?></strong>
                    <div style="color:var(--text-muted);font-size:0.75rem;">Qty: <?= $item['quantity'] ?> × ₹<?= number_format($item['unit_price'], 2) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- Live Tracking Timeline -->
          <div class="tracking-timeline" style="margin:20px 0 15px;">
            <div class="timeline-step completed">
              <div class="timeline-icon"><i class="fas fa-shopping-cart"></i></div>
              <div class="timeline-label">Placed</div>
            </div>
            <div class="timeline-step <?= $o['payment_status'] === 'verified' ? 'completed' : ($o['order_status'] === 'placed' ? 'active' : '') ?>">
              <div class="timeline-icon"><i class="fas fa-file-invoice-dollar"></i></div>
              <div class="timeline-label">Accounts</div>
            </div>
            <div class="timeline-step <?= in_array($o['order_status'], ['checked', 'dispatched', 'delivered']) ? 'completed' : ($o['payment_status'] === 'verified' ? 'active' : '') ?>">
              <div class="timeline-icon"><i class="fas fa-clipboard-check"></i></div>
              <div class="timeline-label">Packed</div>
            </div>
            <div class="timeline-step <?= in_array($o['order_status'], ['dispatched', 'delivered']) ? 'completed' : '' ?>">
              <div class="timeline-icon"><i class="fas fa-truck-fast"></i></div>
              <div class="timeline-label">Dispatched</div>
            </div>
            <div class="timeline-step <?= $o['order_status'] === 'delivered' ? 'completed' : '' ?>">
              <div class="timeline-icon"><i class="fas fa-house-circle-check"></i></div>
              <div class="timeline-label">Delivered</div>
            </div>
          </div>

          <!-- Footer Actions & Hash -->
          <div style="border-top:1px solid var(--border-color);padding-top:15px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div style="font-size:0.8rem;color:#059669;font-weight:700;display:flex;align-items:center;gap:6px;">
              <i class="fas fa-check-circle"></i> Verified Official Order
            </div>

            <div style="display:flex;gap:10px;">
              <?php if (!empty($o['tracking_number'])): ?>
                <span class="badge badge-dispatched" style="padding:6px 12px;font-size:0.78rem;">
                  <i class="fas fa-truck"></i> <?= htmlspecialchars($o['courier_name']) ?> (AWB: <?= htmlspecialchars($o['tracking_number']) ?>)
                </span>
              <?php endif; ?>
              <a href="<?= url('invoice.php?hash=' . urlencode($o['order_hash'])) ?>" target="_blank" class="btn-sm-action btn-action-primary">
                <i class="fas fa-print"></i> Tax Invoice
              </a>
            </div>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
