<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Order.php';

$cart = Order::getCartDetails();
if (empty($cart['items'])) {
    header("Location: " . (function_exists('url') ? url('cart.php') : '/cart.php'));
    exit;
}

$user = Auth::user();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping = [
        'name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['shipping_address'] ?? '',
        'city' => $_POST['shipping_city'] ?? '',
        'state' => $_POST['shipping_state'] ?? 'Rajasthan',
        'pincode' => $_POST['shipping_pincode'] ?? '',
        'gstin' => $_POST['gstin'] ?? '',
        'business_name' => $_POST['business_name'] ?? '',
        'notes' => $_POST['customer_notes'] ?? ''
    ];

    $payment = [
        'method' => $_POST['payment_method'] ?? 'upi_qr',
        'ref_no' => $_POST['payment_ref'] ?? ''
    ];

    if (empty($shipping['name']) || empty($shipping['email']) || empty($shipping['phone']) || empty($shipping['address']) || empty($shipping['pincode'])) {
        $error = "Please fill in all mandatory shipping address fields.";
    } else {
        $res = Order::createOrder($shipping, $payment);
        if ($res['success']) {
            header("Location: " . (function_exists('url') ? url('order_success.php?hash=' . urlencode($res['order_hash'])) : '/order_success.php?hash=' . urlencode($res['order_hash'])));
            exit;
        } else {
            $error = $res['message'] ?? "An error occurred while creating order.";
        }
    }
}

$pageTitle = "Secure Checkout - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="section-wrapper" style="margin-top:25px;">
  <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:25px;display:flex;align-items:center;gap:12px;">
    <i class="fas fa-lock" style="color:var(--primary);"></i> Secure Checkout &amp; Payment
  </h1>

  <?php if ($error): ?>
    <div style="background:#fee2e2;border:1px solid #f87171;color:#991b1b;padding:14px 20px;border-radius:var(--radius-md);margin-bottom:25px;font-weight:700;">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form action="<?= url('checkout.php') ?>" method="POST" enctype="multipart/form-data" class="checkout-container">
    
    <!-- Left: Shipping & Billing Form -->
    <div>
      <div class="form-card" style="margin-bottom:25px;">
        <h3 class="form-title"><i class="fas fa-truck-ramp-box" style="color:var(--primary);"></i> Shipping &amp; Delivery Details</h3>
        
        <div class="form-group">
          <label class="form-label">Full Customer Name *</label>
          <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? ($user['full_name'] ?? '')) ?>" placeholder="e.g. Ramesh Kumar">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? ($user['email'] ?? '')) ?>" placeholder="e.g. ramesh@example.com">
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number *</label>
            <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($_POST['phone'] ?? ($user['phone'] ?? '')) ?>" placeholder="e.g. 9829012345">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Full Street Address *</label>
          <textarea name="shipping_address" rows="3" class="form-control" required placeholder="House/Shop no., Building, Street, Area"><?= htmlspecialchars($_POST['shipping_address'] ?? ($user['address'] ?? '')) ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">City *</label>
            <input type="text" name="shipping_city" class="form-control" required value="<?= htmlspecialchars($_POST['shipping_city'] ?? ($user['city'] ?? 'Jaipur')) ?>" placeholder="City">
          </div>
          <div class="form-group">
            <label class="form-label">State *</label>
            <input type="text" name="shipping_state" class="form-control" required value="<?= htmlspecialchars($_POST['shipping_state'] ?? ($user['state'] ?? 'Rajasthan')) ?>" placeholder="State">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Postal Pincode *</label>
          <input type="text" name="shipping_pincode" class="form-control" required value="<?= htmlspecialchars($_POST['shipping_pincode'] ?? ($user['pincode'] ?? '')) ?>" placeholder="e.g. 302001">
        </div>

        <!-- Optional GST B2B details -->
        <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:var(--radius-md);padding:16px;margin-top:10px;">
          <h4 style="font-size:0.88rem;font-weight:800;color:var(--text-main);margin-bottom:10px;">
            <i class="fas fa-file-invoice" style="color:var(--primary);"></i> GST Input Tax Credit (Optional for B2B Billing)
          </h4>
          <div class="form-row">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Business / Firm Name</label>
              <input type="text" name="business_name" class="form-control" placeholder="Company Name" value="<?= htmlspecialchars($_POST['business_name'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">GSTIN (15 Digits)</label>
              <input type="text" name="gstin" class="form-control" placeholder="e.g. 08APSPA4456M2ZC" value="<?= htmlspecialchars($_POST['gstin'] ?? '') ?>">
            </div>
          </div>
        </div>

        <div class="form-group" style="margin-top:16px;">
          <label class="form-label">Order Notes / Delivery Instructions</label>
          <input type="text" name="customer_notes" class="form-control" placeholder="Optional notes for packing or dispatch" value="<?= htmlspecialchars($_POST['customer_notes'] ?? '') ?>">
        </div>
      </div>

      <!-- Payment Method Selection -->
      <div class="form-card">
        <h3 class="form-title"><i class="fas fa-wallet" style="color:var(--primary);"></i> Select Payment Method</h3>
        
        <!-- UPI QR Scan Option -->
        <label class="payment-option-card selected">
          <input type="radio" name="payment_method" value="upi_qr" checked style="accent-color:var(--primary);">
          <div style="flex:1;">
            <div style="font-weight:800;font-size:0.95rem;display:flex;align-items:center;gap:8px;">
              <span>Instant UPI QR Scan (GPay, PhonePe, Paytm)</span>
              <span class="badge badge-verified" style="font-size:0.7rem;">RECOMMENDED</span>
            </div>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-top:2px;">
              Scan dynamic QR code, pay directly, and submit your UTR reference for instant Accounts verification.
            </p>
          </div>
        </label>

        <!-- UPI Details Box -->
        <div id="upiPaymentDetails" class="upi-qr-display">
          <div style="font-size:0.88rem;font-weight:800;color:var(--text-main);margin-bottom:8px;">
            SCAN &amp; PAY ₹<?= number_format($cart['total_amount'], 2) ?>
          </div>
          <img src="<?= asset('assets/images/upi-qr.svg') ?>" alt="Sakshi Infotech UPI QR">
          <div style="font-family:monospace;font-size:0.9rem;font-weight:800;color:var(--primary);margin-bottom:12px;">
            UPI ID: <?= UPI_ID ?>
          </div>
          <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:14px;">
            After payment, please enter the 12-digit UTR / Transaction ID below:
          </p>
          <div class="form-row" style="max-width:550px;margin:0 auto;text-align:left;">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">UPI Transaction / UTR No. *</label>
              <input type="text" name="payment_ref" class="form-control" placeholder="e.g. 423981290312">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Payment Screenshot (Optional)</label>
              <input type="file" name="payment_proof" class="form-control" accept="image/*,.pdf">
            </div>
          </div>
        </div>

        <!-- Bank Transfer Option -->
        <label class="payment-option-card">
          <input type="radio" name="payment_method" value="bank_transfer" style="accent-color:var(--primary);">
          <div style="flex:1;">
            <div style="font-weight:800;font-size:0.95rem;">Bank NEFT / RTGS Transfer</div>
            <p style="font-size:0.8rem;color:var(--text-muted);">Transfer directly to Sakshi Infotech Current Account.</p>
          </div>
        </label>

        <div id="bankPaymentDetails" style="display:none;background:#f8fafc;border:1px solid var(--border-color);border-radius:var(--radius-md);padding:18px;margin:15px 0;font-size:0.85rem;">
          <h4 style="font-weight:800;margin-bottom:8px;">Beneficiary Account Details:</h4>
          <p>Bank Name: <strong><?= BANK_NAME ?></strong></p>
          <p>Account Number: <strong><?= BANK_ACCOUNT ?></strong></p>
          <p>IFSC Code: <strong><?= BANK_IFSC ?></strong></p>
          <p>Branch: <?= BANK_BRANCH ?></p>
        </div>

        <!-- Cash on Delivery Option -->
        <label class="payment-option-card">
          <input type="radio" name="payment_method" value="cod" style="accent-color:var(--primary);">
          <div style="flex:1;">
            <div style="font-weight:800;font-size:0.95rem;">Cash on Delivery (COD)</div>
            <p style="font-size:0.8rem;color:var(--text-muted);">Pay upon delivery at your door (Available in Jaipur region).</p>
          </div>
        </label>
      </div>
    </div>

    <!-- Right: Order Review & Confirmation -->
    <div>
      <div class="form-card" style="position:sticky;top:90px;">
        <h3 class="form-title" style="font-size:1.15rem;margin-bottom:15px;padding-bottom:10px;border-bottom:1px solid var(--border-color);">
          <i class="fas fa-receipt" style="color:var(--primary);"></i> Order Summary (<?= $cart['count'] ?> Items)
        </h3>

        <div style="max-height:220px;overflow-y:auto;margin-bottom:15px;padding-right:5px;">
          <?php foreach ($cart['items'] as $item): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:0.85rem;">
              <div style="line-height:1.3;max-width:190px;">
                <strong style="color:var(--text-main);"><?= htmlspecialchars($item['name']) ?></strong>
                <div style="color:var(--text-muted);font-size:0.75rem;">Qty: <?= $item['quantity'] ?> × ₹<?= number_format($item['unit_price'], 2) ?></div>
              </div>
              <span style="font-weight:800;color:var(--text-main);">₹<?= number_format($item['line_total'], 2) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="display:flex;justify-content:space-between;font-size:0.88rem;color:var(--text-muted);margin-bottom:8px;">
          <span>Subtotal:</span>
          <span style="font-weight:700;color:var(--text-main);">₹<?= number_format($cart['subtotal'], 2) ?></span>
        </div>

        <div style="display:flex;justify-content:space-between;font-size:0.88rem;color:var(--text-muted);margin-bottom:8px;">
          <span>Total GST (18% / 12%):</span>
          <span style="font-weight:700;color:var(--text-main);">₹<?= number_format($cart['tax_amount'], 2) ?></span>
        </div>

        <div style="display:flex;justify-content:space-between;font-size:0.88rem;color:var(--text-muted);margin-bottom:16px;">
          <span>Delivery Charge:</span>
          <span style="font-weight:700;color:var(--success);">FREE</span>
        </div>

        <div style="border-top:2px solid var(--border-color);padding-top:14px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:baseline;">
          <span style="font-size:1.1rem;font-weight:800;">Total Payable:</span>
          <span style="font-size:1.6rem;font-weight:900;color:var(--primary);">₹<?= number_format($cart['total_amount'], 2) ?></span>
        </div>

        <button type="submit" class="btn-primary-si" style="width:100%;padding:14px;font-size:1.05rem;justify-content:center;box-shadow:var(--shadow-glow);">
          <i class="fas fa-check-circle"></i> Place Order Now
        </button>

        <div style="margin-top:18px;font-size:0.75rem;color:var(--text-muted);text-align:center;">
          By placing an order, an immutable cryptographic SHA-256 hash will be generated for tamper-proof verification by Sakshi Infotech.
        </div>
      </div>
    </div>

  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
