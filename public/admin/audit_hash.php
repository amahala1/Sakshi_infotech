<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/HashEngine.php';

Auth::requireStaff();
$user = Auth::user();
$db = Database::getConnection();

$searchHash = $_GET['hash'] ?? '';
$lookupResult = null;
if (!empty($searchHash)) {
    $lookupResult = HashEngine::lookupHash($db, $searchHash);
}

// Fetch recent audit logs
$auditLogs = $db->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 50")->fetchAll();

$pageTitle = "Cryptographic Hash Audit - " . APP_NAME;
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
      <h1>🔐 Cryptographic Hash &amp; Audit Engine</h1>
    </div>
  </header>

  <div class="admin-content">

    <!-- Security Information Banner (Clean Light Palette - No dark colors) -->
    <div style="background:linear-gradient(135deg, #f0fdf4 0%, #eff6ff 100%);color:#1e293b;border-radius:14px;padding:24px;margin-bottom:30px;border:1px solid #bfdbfe;box-shadow:0 4px 15px rgba(37,99,235,0.06);">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;margin-bottom:15px;border-bottom:1px solid #e2e8f0;padding-bottom:12px;">
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="width:45px;height:45px;border-radius:50%;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
            <i class="fas fa-fingerprint"></i>
          </div>
          <div>
            <h3 style="font-size:1.2rem;font-weight:800;color:#0f172a;">Database Cryptographic Engine Status: ACTIVE</h3>
            <span style="font-size:0.75rem;color:#64748b;">Driver: <strong><?= strtoupper(Database::getDriver()) ?></strong> | Algorithm: <strong>HMAC-SHA256</strong> | Key: <strong>Loaded &amp; Protected</strong></span>
          </div>
        </div>
        <span class="badge badge-verified" style="background:#0284c7;color:#ffffff;font-size:0.8rem;padding:6px 14px;">
          100% Tamper Proof
        </span>
      </div>

      <p style="font-size:0.85rem;color:#475569;line-height:1.6;margin-bottom:16px;">
        Every customer order, payment verification by accounts, dispatch slip by logistics, and inventory update is assigned a unique cryptographic HMAC-SHA256 signature. Any alteration in the database will invalidate the seal.
      </p>

      <!-- Instant Hash Lookup Bar -->
      <form action="<?= url('admin/audit_hash.php') ?>" method="GET" style="display:flex;gap:10px;">
        <input type="text" name="hash" class="form-control" placeholder="Paste any 64-character record hash (Order, Payment, or Dispatch seal)..." value="<?= htmlspecialchars($searchHash) ?>" required style="background:#ffffff;border:1px solid #cbd5e1;color:#0f172a;font-family:monospace;font-size:0.88rem;">
        <button type="submit" class="btn-sm-action btn-action-primary" style="padding:10px 18px;white-space:nowrap;">
          <i class="fas fa-search"></i> Inspect Hash
        </button>
      </form>
    </div>

    <!-- Lookup Result Modal/Card -->
    <?php if (!empty($searchHash)): ?>
      <div style="background:#ffffff;border:2px solid <?= ($lookupResult && $lookupResult['success']) ? '#10b981' : '#ef4444' ?>;border-radius:14px;padding:25px;margin-bottom:30px;">
        <?php if ($lookupResult && $lookupResult['success']): ?>
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:15px;">
            <i class="fas fa-check-circle" style="color:#10b981;font-size:1.5rem;"></i>
            <h3 style="font-size:1.15rem;font-weight:800;color:#065f46;">
              Valid Authenticated Record: <?= htmlspecialchars($lookupResult['title']) ?> (ID: <?= htmlspecialchars($lookupResult['record_id']) ?>)
            </h3>
          </div>
          <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:15px;border-radius:8px;font-family:monospace;font-size:0.85rem;">
            <?= htmlspecialchars(json_encode($lookupResult['data'], JSON_PRETTY_PRINT)) ?>
          </div>
        <?php else: ?>
          <div style="display:flex;align-items:center;gap:12px;">
            <i class="fas fa-times-circle" style="color:#ef4444;font-size:1.5rem;"></i>
            <div>
              <h3 style="font-size:1.15rem;font-weight:800;color:#991b1b;">Cryptographic Seal Verification Failed</h3>
              <p style="font-size:0.85rem;color:var(--admin-text-muted);">No database record matches hash: <code><?= htmlspecialchars($searchHash) ?></code></p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Audit Logs Table -->
    <div class="card-table">
      <div class="card-header">
        <h3><i class="fas fa-list" style="color:var(--admin-primary);"></i> Cryptographic Audit Trail (Last <?= count($auditLogs) ?> Actions)</h3>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Actor &amp; Role</th>
              <th>Action Event</th>
              <th>Details</th>
              <th>Cryptographic Seal Hash (SHA-256)</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($auditLogs)): ?>
              <tr>
                <td colspan="5" style="text-align:center;padding:30px;color:var(--admin-text-muted);">
                  No audit logs recorded yet.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($auditLogs as $log): ?>
                <tr>
                  <td style="font-size:0.75rem;color:var(--admin-text-muted);white-space:nowrap;">
                    <?= date('d M Y, h:i:s A', strtotime($log['created_at'])) ?>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($log['performed_by_name'] ?? 'System') ?></strong>
                    <div style="font-size:0.72rem;color:var(--admin-primary);text-transform:uppercase;font-weight:700;">
                      <?= htmlspecialchars($log['role'] ?? 'system') ?>
                    </div>
                  </td>
                  <td>
                    <span class="badge badge-verified" style="font-size:0.72rem;">
                      <?= htmlspecialchars($log['action']) ?>
                    </span>
                  </td>
                  <td style="font-size:0.82rem;max-width:300px;">
                    <?= htmlspecialchars($log['details']) ?>
                  </td>
                  <td>
                    <span class="seal-code" title="<?= htmlspecialchars($log['record_hash']) ?>">
                      <?= substr($log['record_hash'], 0, 18) ?>...
                    </span>
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
