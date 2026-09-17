<?php
if (!defined('BASE_PATH')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/config/config.php';
}
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Staff.php';

Auth::requireStaff();
$user = Auth::user();
$currentFile = basename($_SERVER['PHP_SELF']);

$roleTitle = 'Staff Member';
$roleClass = 'role-admin';

if ($user['role'] === 'admin') {
    $roleTitle = 'System Administrator';
    $roleClass = 'role-admin';
} elseif ($user['role'] === 'staff_accounts') {
    $roleTitle = 'Accounts Officer';
    $roleClass = 'role-accounts';
} elseif ($user['role'] === 'staff_checker') {
    $roleTitle = 'Order Checker';
    $roleClass = 'role-checker';
} elseif ($user['role'] === 'staff_dispatch') {
    $roleTitle = 'Dispatch Logistics';
    $roleClass = 'role-dispatch';
}
?>
<aside class="admin-sidebar">
  <div class="sidebar-brand">
    <div style="width:38px;height:38px;border-radius:8px;background:var(--admin-primary);color:#ffffff;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:900;">
      SI
    </div>
    <div>
      <h2><?= APP_NAME ?></h2>
      <span>CONTROL PANEL</span>
    </div>
  </div>

  <div class="sidebar-user-card">
    <div class="user-avatar">
      <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
    </div>
    <div class="user-meta" style="flex:1;overflow:hidden;">
      <h4 style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($user['full_name']) ?></h4>
      <span class="role-pill <?= $roleClass ?>"><?= $roleTitle ?></span>
    </div>
  </div>

  <ul class="sidebar-nav">
    <div class="nav-section-title">General</div>
    <li>
      <a href="<?= url('admin/index.php') ?>" class="<?= $currentFile === 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-chart-pie"></i> Dashboard Overview
      </a>
    </li>

    <!-- Role-based staff portals -->
    <div class="nav-section-title">Operations Pipeline</div>
    
    <?php if (Auth::isAccountsStaff()): ?>
      <li>
        <a href="<?= url('admin/accounts.php') ?>" class="<?= $currentFile === 'accounts.php' ? 'active' : '' ?>" style="<?= $user['role'] === 'staff_accounts' ? 'font-weight:800;' : '' ?>">
          <i class="fas fa-file-invoice-dollar" style="color:#d97706;"></i> Accounts &amp; Payments
        </a>
      </li>
    <?php endif; ?>

    <?php if (Auth::isCheckerStaff()): ?>
      <li>
        <a href="<?= url('admin/checker.php') ?>" class="<?= $currentFile === 'checker.php' ? 'active' : '' ?>" style="<?= $user['role'] === 'staff_checker' ? 'font-weight:800;' : '' ?>">
          <i class="fas fa-clipboard-check" style="color:#059669;"></i> Order Checker &amp; Pack
        </a>
      </li>
    <?php endif; ?>

    <?php if (Auth::isDispatchStaff()): ?>
      <li>
        <a href="<?= url('admin/dispatch.php') ?>" class="<?= $currentFile === 'dispatch.php' ? 'active' : '' ?>" style="<?= $user['role'] === 'staff_dispatch' ? 'font-weight:800;' : '' ?>">
          <i class="fas fa-truck-fast" style="color:#0284c7;"></i> Dispatch &amp; Shipping
        </a>
      </li>
    <?php endif; ?>

    <li>
      <a href="<?= url('admin/orders.php') ?>" class="<?= $currentFile === 'orders.php' ? 'active' : '' ?>">
        <i class="fas fa-boxes-stacked"></i> Master Orders List
      </a>
    </li>

    <!-- Admin Master Controls -->
    <?php if (Auth::isAdmin()): ?>
      <div class="nav-section-title">Admin Management</div>
      <li>
        <a href="<?= url('admin/staff.php') ?>" class="<?= $currentFile === 'staff.php' ? 'active' : '' ?>">
          <i class="fas fa-users-gear" style="color:#e11d48;"></i> Staff Management
        </a>
      </li>
      <li>
        <a href="<?= url('admin/products.php') ?>" class="<?= $currentFile === 'products.php' ? 'active' : '' ?>">
          <i class="fas fa-box-open"></i> Products &amp; Stock
        </a>
      </li>
      <li>
        <a href="<?= url('admin/categories.php') ?>" class="<?= $currentFile === 'categories.php' ? 'active' : '' ?>">
          <i class="fas fa-folder-tree"></i> Categories
        </a>
      </li>
    <?php endif; ?>

    <li>
      <a href="<?= url('sale.php') ?>" target="_blank">
        <i class="fas fa-external-link-alt"></i> View Storefront
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <a href="<?= url('logout.php') ?>" class="btn-sidebar-logout">
      <i class="fas fa-arrow-right-from-bracket"></i> Sign Out
    </a>
  </div>
</aside>
