<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => $_POST['full_name'] ?? '',
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'password' => $_POST['password'] ?? '',
        'address' => $_POST['address'] ?? '',
        'city' => $_POST['city'] ?? 'Jaipur',
        'state' => $_POST['state'] ?? 'Rajasthan',
        'pincode' => $_POST['pincode'] ?? ''
    ];

    $res = Auth::register($data);
    if ($res['success']) {
        header("Location: " . (function_exists('url') ? url('my_orders.php') : '/my_orders.php'));
        exit;
    } else {
        $error = $res['message'];
    }
}

$pageTitle = "Register Customer Account - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="section-wrapper" style="max-width:550px;margin:35px auto;">
  <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:35px;box-shadow:var(--shadow-md);">
    
    <div style="text-align:center;margin-bottom:25px;">
      <h1 style="font-size:1.6rem;font-weight:900;color:var(--text-main);">Create Customer Account</h1>
      <p style="color:var(--text-muted);font-size:0.88rem;margin-top:4px;">Join Sakshi Infotech for easy tracking &amp; GST tax invoices</p>
    </div>

    <?php if ($error): ?>
      <div style="background:#fee2e2;border:1px solid #f87171;color:#991b1b;padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:0.88rem;font-weight:700;">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="<?= url('register.php') ?>" method="POST">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="full_name" class="form-control" required placeholder="e.g. Alok Sharma" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Username *</label>
          <input type="text" name="username" class="form-control" required placeholder="alok26" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required placeholder="alok@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control" placeholder="9829011122" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Password (Min 6 chars) *</label>
          <input type="password" name="password" class="form-control" required placeholder="••••••••">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Delivery Address</label>
        <input type="text" name="address" class="form-control" placeholder="Office / Home Address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">City</label>
          <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($_POST['city'] ?? 'Jaipur') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Pincode</label>
          <input type="text" name="pincode" class="form-control" placeholder="302001" value="<?= htmlspecialchars($_POST['pincode'] ?? '') ?>">
        </div>
      </div>

      <button type="submit" class="btn-primary-si" style="width:100%;justify-content:center;padding:12px;font-size:0.95rem;margin-top:10px;">
        <i class="fas fa-user-plus"></i> Create Account
      </button>
    </form>

    <div style="text-align:center;margin-top:20px;font-size:0.88rem;color:var(--text-muted);">
      Already have an account? <a href="<?= url('login.php') ?>" style="color:var(--primary);font-weight:700;">Sign In</a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
