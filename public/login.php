<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';

$error = $_GET['error'] ?? null;
$msg = $_GET['msg'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $res = Auth::login($username, $password);
    if ($res['success']) {
        $role = $res['user']['role'];
        if (in_array($role, ['admin', 'staff_accounts', 'staff_checker', 'staff_dispatch'])) {
            header("Location: " . (function_exists('url') ? url('admin/index.php') : '/admin/index.php'));
        } else {
            $redirect = $_GET['redirect'] ?? 'my_orders.php';
            header("Location: " . (function_exists('url') ? url(ltrim($redirect, '/')) : '/' . ltrim($redirect, '/')));
        }
        exit;
    } else {
        $error = $res['message'];
    }
}

$pageTitle = "Login - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="section-wrapper" style="max-width:480px;margin:40px auto;">
  <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:35px;box-shadow:var(--shadow-md);">
    
    <div style="text-align:center;margin-bottom:25px;">
      <h1 style="font-size:1.6rem;font-weight:900;color:var(--text-main);">Welcome Back</h1>
      <p style="color:var(--text-muted);font-size:0.88rem;margin-top:4px;">Sign in to your customer or staff account</p>
    </div>

    <?php if ($error): ?>
      <div style="background:#fee2e2;border:1px solid #f87171;color:#991b1b;padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:0.88rem;font-weight:700;">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($msg): ?>
      <div style="background:#d1fae5;border:1px solid #34d399;color:#065f46;padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:0.88rem;font-weight:700;">
        <i class="fas fa-info-circle"></i> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <form action="<?= url('login.php') ?>" method="POST">
      <div class="form-group">
        <label class="form-label">Username or Email Address</label>
        <input type="text" name="username" class="form-control" required placeholder="Enter username or email" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>

      <div class="form-group" style="margin-bottom:24px;">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required placeholder="••••••••">
      </div>

      <button type="submit" class="btn-primary-si" style="width:100%;justify-content:center;padding:12px;font-size:0.95rem;">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div style="text-align:center;margin-top:20px;font-size:0.88rem;color:var(--text-muted);">
      Don't have a customer account? <a href="<?= url('register.php') ?>" style="color:var(--primary);font-weight:700;">Register Now</a>
    </div>

    <!-- Quick Role Switcher Credentials Helper for pair-programming & evaluation -->
    <div style="margin-top:30px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:var(--radius-md);padding:14px;font-size:0.78rem;">
      <strong style="color:var(--text-main);display:block;margin-bottom:6px;">Demo Quick Logins:</strong>
      <div style="display:flex;flex-direction:column;gap:4px;color:var(--text-muted);">
        <div>👑 <strong>Admin</strong>: <code>admin</code> / <code>admin123</code></div>
        <div>💰 <strong>Accounts Staff</strong>: <code>staff_acc</code> / <code>accounts123</code></div>
        <div>📦 <strong>Order Checker Staff</strong>: <code>staff_chk</code> / <code>checker123</code></div>
        <div>🚚 <strong>Dispatch Staff</strong>: <code>staff_dsp</code> / <code>dispatch123</code></div>
        <div>👤 <strong>Customer</strong>: <code>customer1</code> / <code>customer123</code></div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
