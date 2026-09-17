<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Staff.php';

Auth::requireAdmin(); // Only Super Admin can manage Staff

$msg = null;
$error = null;

// Handle Staff Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $res = Staff::createStaff([
            'full_name' => $_POST['full_name'] ?? '',
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'password' => $_POST['password'] ?? '',
            'role' => $_POST['role'] ?? ''
        ]);

        if ($res['success']) {
            $msg = $res['message'];
        } else {
            $error = $res['message'];
        }
    } elseif ($_POST['action'] === 'toggle') {
        Staff::toggleStatus((int)$_POST['staff_id']);
        $msg = "Staff status updated successfully.";
    } elseif ($_POST['action'] === 'delete') {
        Staff::deleteStaff((int)$_POST['staff_id']);
        $msg = "Staff member deleted.";
    }
}

$staffList = Staff::getAllStaff();
$availableRoles = Staff::getRoles();

$pageTitle = "Staff Management - " . APP_NAME;
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
      <h1>Staff &amp; Role Management</h1>
    </div>
  </header>

  <div class="admin-content">
    
    <?php if ($msg): ?>
      <div style="background:#d1fae5;border:1px solid #34d399;color:#065f46;padding:12px 18px;border-radius:10px;margin-bottom:20px;font-weight:700;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div style="background:#fee2e2;border:1px solid #f87171;color:#991b1b;padding:12px 18px;border-radius:10px;margin-bottom:20px;font-weight:700;">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1.8fr;gap:30px;align-items:start;">
      
      <!-- Create Staff Form -->
      <div style="background:#ffffff;border:1px solid var(--admin-border);border-radius:14px;padding:26px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h3 style="font-size:1.15rem;font-weight:800;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
          <i class="fas fa-user-plus" style="color:var(--admin-primary);"></i> Create New Staff Member
        </h3>

        <form action="<?= url('admin/staff.php') ?>" method="POST">
          <input type="hidden" name="action" value="create">

          <div style="margin-bottom:14px;">
            <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:5px;">Staff Member Role *</label>
            <select name="role" class="form-control" required style="font-weight:700;">
              <option value="">-- Select Functional Role --</option>
              <option value="staff_accounts">💰 Accounts (Payment Verification)</option>
              <option value="staff_checker">📦 Order Checker (Verify Items &amp; Packing)</option>
              <option value="staff_dispatch">🚚 Dispatch (Logistics &amp; Courier Tracking)</option>
            </select>
          </div>

          <div style="margin-bottom:14px;">
            <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:5px;">Full Name *</label>
            <input type="text" name="full_name" class="form-control" required placeholder="e.g. Ramesh Kumar Sharma">
          </div>

          <div style="margin-bottom:14px;">
            <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:5px;">Login Username *</label>
            <input type="text" name="username" class="form-control" required placeholder="e.g. ramesh_accounts">
          </div>

          <div style="margin-bottom:14px;">
            <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:5px;">Official Email *</label>
            <input type="email" name="email" class="form-control" required placeholder="e.g. ramesh@sakshiinfotech.com">
          </div>

          <div style="margin-bottom:14px;">
            <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:5px;">Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="e.g. 9829011111">
          </div>

          <div style="margin-bottom:20px;">
            <label style="display:block;font-size:0.82rem;font-weight:700;margin-bottom:5px;">Account Password *</label>
            <input type="password" name="password" class="form-control" required placeholder="Min 6 characters">
          </div>

          <button type="submit" class="btn-sm-action btn-action-primary" style="width:100%;padding:12px;font-size:0.92rem;justify-content:center;">
            <i class="fas fa-check"></i> Register Staff Account
          </button>
        </form>
      </div>

      <!-- Staff List Table -->
      <div class="card-table">
        <div class="card-header">
          <h3><i class="fas fa-users" style="color:var(--admin-primary);"></i> Active Staff Directory (<?= count($staffList) ?>)</h3>
        </div>
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Staff Name</th>
                <th>Role / Department</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($staffList)): ?>
                <tr>
                  <td colspan="5" style="text-align:center;padding:30px;color:var(--admin-text-muted);">
                    No staff members created yet. Use the form to add accounts, checker, or dispatch staff.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($staffList as $s): 
                  $rMeta = $availableRoles[$s['role']] ?? null;
                  $roleClass = 'role-accounts';
                  if ($s['role'] === 'staff_checker') $roleClass = 'role-checker';
                  if ($s['role'] === 'staff_dispatch') $roleClass = 'role-dispatch';
                ?>
                  <tr>
                    <td>
                      <strong style="color:var(--admin-text);"><?= htmlspecialchars($s['full_name']) ?></strong>
                      <div style="font-size:0.75rem;color:var(--admin-text-muted);">@<?= htmlspecialchars($s['username']) ?></div>
                    </td>
                    <td>
                      <span class="role-pill <?= $roleClass ?>">
                        <i class="fas <?= $rMeta['icon'] ?? 'fa-user' ?>"></i>
                        <?= htmlspecialchars($rMeta['name'] ?? $s['role']) ?>
                      </span>
                    </td>
                    <td>
                      <div style="font-size:0.85rem;"><?= htmlspecialchars($s['email']) ?></div>
                      <div style="font-size:0.75rem;color:var(--admin-text-muted);"><?= htmlspecialchars($s['phone'] ?: 'No phone') ?></div>
                    </td>
                    <td>
                      <span class="badge <?= $s['status'] === 'active' ? 'badge-verified' : 'badge-rejected' ?>">
                        <?= strtoupper($s['status']) ?>
                      </span>
                    </td>
                    <td>
                      <div style="display:flex;gap:6px;">
                        <form action="<?= url('admin/staff.php') ?>" method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="toggle">
                          <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                          <button type="submit" class="btn-sm-action" style="background:#f1f5f9;color:#334155;" title="Toggle Active/Suspended">
                            <i class="fas <?= $s['status'] === 'active' ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                          </button>
                        </form>
                        <form action="<?= url('admin/staff.php') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                          <button type="submit" class="btn-sm-action btn-reject" title="Delete Staff">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
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

  </div>
</main>

<script src="<?= asset('assets/js/admin.js') ?>"></script>
</body>
</html>
