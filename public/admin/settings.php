<?php
/**
 * Sakshi Infotech - Enterprise Admin Settings & SMTP Configuration
 */
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Mailer.php';

Auth::requireAdmin();

$msg = null;
$error = null;

// Handle Form Submission: Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_email_settings') {
    $newSettings = [
        'smtp_from_email' => trim($_POST['smtp_from_email'] ?? ''),
        'smtp_from_name'  => trim($_POST['smtp_from_name'] ?? APP_NAME),
        'smtp_host'       => trim($_POST['smtp_host'] ?? ''),
        'smtp_port'       => trim($_POST['smtp_port'] ?? '587'),
        'smtp_secure'     => trim($_POST['smtp_secure'] ?? 'tls'),
        'smtp_user'       => trim($_POST['smtp_user'] ?? ''),
        'mail_driver'     => trim($_POST['mail_driver'] ?? 'smtp')
    ];

    // Only update password if provided
    if (!empty($_POST['smtp_pass'])) {
        $newSettings['smtp_pass'] = trim($_POST['smtp_pass']);
    }

    if (Mailer::updateSettings($newSettings)) {
        $msg = "Email and SMTP credentials updated successfully in database.";
    } else {
        $error = "Failed to update email settings. Please check database permissions.";
    }
}

// Handle Test Email Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_email') {
    $testTo = trim($_POST['test_recipient'] ?? '');
    if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid test recipient email address.";
    } else {
        $testRes = Mailer::testConnection($testTo);
        if ($testRes['success']) {
            $msg = "Test email dispatched successfully to {$testTo}!";
        } else {
            $error = "Test email failed: " . $testRes['message'];
        }
    }
}

$settings = Mailer::getEmailSettings();

// Fetch Recent Email Transmission Logs
$db = Database::getConnection();
$stmtLogs = $db->query("SELECT * FROM email_logs ORDER BY id DESC LIMIT 20");
$recentLogs = $stmtLogs->fetchAll();

$pageTitle = "Email & SMTP Settings - " . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <h1 style="font-size:1.4rem;font-weight:900;color:var(--text-main);margin:0;">
          <i class="fas fa-envelope-circle-check" style="color:var(--primary);"></i> Email &amp; SMTP Configuration
        </h1>
        <p style="color:var(--text-muted);font-size:0.84rem;margin:2px 0 0;">
          Configure outgoing email credentials, sender identity (noreply@sitindia.in), and audit transactional notifications
        </p>
      </div>
      <div>
        <span class="badge badge-verified" style="font-size:0.8rem;padding:6px 12px;">
          <i class="fas fa-server"></i> Active DB Settings
        </span>
      </div>
    </div>

    <?php if ($msg): ?>
      <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:0.88rem;font-weight:700;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div style="background:#fee2e2;border:1px solid #f87171;color:#991b1b;padding:12px 16px;border-radius:var(--radius-md);margin-bottom:20px;font-size:0.88rem;font-weight:700;">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px;align-items:start;">
      
      <!-- Left Column: Primary SMTP Configuration Form -->
      <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:25px;box-shadow:var(--shadow-sm);">
        <h3 style="font-size:1.1rem;font-weight:800;color:var(--text-main);margin-bottom:18px;border-bottom:1px solid #f1f5f9;padding-bottom:10px;display:flex;align-items:center;gap:8px;">
          <i class="fas fa-gear" style="color:var(--primary);"></i> Outgoing Mail Server Credentials
        </h3>

        <form action="<?= url('admin/settings.php') ?>" method="POST">
          <input type="hidden" name="action" value="save_email_settings">

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Sender Email ID (From Email) *</label>
              <input type="email" name="smtp_from_email" class="form-control" required value="<?= htmlspecialchars($settings['smtp_from_email']) ?>" placeholder="noreply@sitindia.in">
              <span style="font-size:0.75rem;color:var(--text-muted);margin-top:3px;display:block;">All transactional emails and OTPs originate from this ID.</span>
            </div>
            <div class="form-group">
              <label class="form-label">Sender Display Name *</label>
              <input type="text" name="smtp_from_name" class="form-control" required value="<?= htmlspecialchars($settings['smtp_from_name']) ?>" placeholder="Sakshi Infotech">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">SMTP Host Server *</label>
              <input type="text" name="smtp_host" class="form-control" required value="<?= htmlspecialchars($settings['smtp_host']) ?>" placeholder="mail.sitindia.in or smtp.gmail.com">
            </div>
            <div class="form-group">
              <label class="form-label">SMTP Port *</label>
              <input type="number" name="smtp_port" class="form-control" required value="<?= htmlspecialchars($settings['smtp_port']) ?>" placeholder="587">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Encryption Protocol *</label>
              <select name="smtp_secure" class="form-control">
                <option value="tls" <?= ($settings['smtp_secure'] === 'tls') ? 'selected' : '' ?>>STARTTLS (Port 587 - Recommended)</option>
                <option value="ssl" <?= ($settings['smtp_secure'] === 'ssl') ? 'selected' : '' ?>>SSL / SMTPS (Port 465)</option>
                <option value="none" <?= ($settings['smtp_secure'] === 'none') ? 'selected' : '' ?>>Plain / None (Port 25)</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Mail Dispatch Engine</label>
              <select name="mail_driver" class="form-control">
                <option value="smtp" <?= ($settings['mail_driver'] === 'smtp') ? 'selected' : '' ?>>Direct Socket SMTP Client</option>
                <option value="mail" <?= ($settings['mail_driver'] === 'mail') ? 'selected' : '' ?>>Server PHP mail()</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">SMTP Username / Login Account *</label>
              <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($settings['smtp_user']) ?>" placeholder="noreply@sitindia.in">
            </div>
            <div class="form-group">
              <label class="form-label">
                <span>SMTP Password</span>
                <?php if (!empty($settings['smtp_pass'])): ?>
                  <span style="font-size:0.75rem;color:var(--success);font-weight:700;">(Password is configured)</span>
                <?php endif; ?>
              </label>
              <input type="password" name="smtp_pass" class="form-control" placeholder="<?= !empty($settings['smtp_pass']) ? '•••••••••••• (Leave blank to keep current)' : 'Enter email account password' ?>">
              <span style="font-size:0.75rem;color:var(--text-muted);margin-top:3px;display:block;">Stored securely in system database.</span>
            </div>
          </div>

          <button type="submit" class="btn-primary-si" style="padding:11px 24px;font-size:0.92rem;margin-top:10px;">
            <i class="fas fa-floppy-disk"></i> Save Email Configuration
          </button>
        </form>
      </div>

      <!-- Right Column: Instant Live Connection Test -->
      <div>
        <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px;">
          <h3 style="font-size:1.05rem;font-weight:800;color:var(--text-main);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-paper-plane" style="color:var(--primary);"></i> Send Live Test Email
          </h3>
          <p style="font-size:0.84rem;color:var(--text-muted);line-height:1.5;margin-bottom:16px;">
            Verify your SMTP connection, encryption handshake, and authentic delivery straight to your inbox.
          </p>

          <form action="<?= url('admin/settings.php') ?>" method="POST">
            <input type="hidden" name="action" value="test_email">
            <div class="form-group" style="margin-bottom:14px;">
              <label class="form-label">Recipient Test Email</label>
              <input type="email" name="test_recipient" class="form-control" required placeholder="admin@sitindia.in" value="<?= htmlspecialchars(APP_EMAIL) ?>">
            </div>
            <button type="submit" class="btn-outline-si" style="width:100%;justify-content:center;padding:10px;font-size:0.88rem;font-weight:800;">
              <i class="fas fa-bolt"></i> Test Outgoing SMTP Now
            </button>
          </form>
        </div>

        <!-- Information & Lifecycle Overview -->
        <div style="background:linear-gradient(135deg, #0A192F 0%, #1e3a8a 100%);color:#ffffff;border-radius:var(--radius-lg);padding:22px;box-shadow:var(--shadow-sm);">
          <h4 style="font-size:0.95rem;font-weight:800;margin:0 0 10px;color:#38bdf8;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-shield-halved"></i> Automated Email Triggers
          </h4>
          <ul style="padding-left:18px;margin:0;font-size:0.82rem;line-height:1.7;color:#cbd5e1;">
            <li><strong>Email OTP:</strong> Instant 6-digit code for new registrations.</li>
            <li><strong>User Creation:</strong> Welcome email with Login ID &amp; GST details.</li>
            <li><strong>Order Placed:</strong> Customer order confirmation with invoice link.</li>
            <li><strong>Payment Received:</strong> Accounts payment seal confirmation.</li>
            <li><strong>Order Packed:</strong> Warehouse stock check &amp; packing alert.</li>
            <li><strong>Order Dispatched:</strong> Courier partner &amp; AWB tracking link.</li>
          </ul>
        </div>
      </div>

    </div>

    <!-- Bottom Section: Email Transmission Audit Logs -->
    <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow-sm);margin-top:28px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="font-size:1.1rem;font-weight:800;color:var(--text-main);margin:0;display:flex;align-items:center;gap:8px;">
          <i class="fas fa-list-check" style="color:var(--primary);"></i> Transactional Email Transmission Audit Logs
        </h3>
        <span style="font-size:0.8rem;color:var(--text-muted);">Last 20 System Dispatches</span>
      </div>

      <?php if (empty($recentLogs)): ?>
        <p style="text-align:center;color:var(--text-muted);font-size:0.88rem;padding:30px 0;">
          No email transmissions recorded yet. Dispatches will appear here automatically.
        </p>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Recipient</th>
                <th>Subject</th>
                <th>Event Type</th>
                <th>Status</th>
                <th>Dispatched At</th>
                <th>Diagnostic Info</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentLogs as $log): ?>
                <tr>
                  <td><code>#<?= $log['id'] ?></code></td>
                  <td><strong><?= htmlspecialchars($log['recipient_email']) ?></strong></td>
                  <td style="max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($log['subject']) ?>">
                    <?= htmlspecialchars($log['subject']) ?>
                  </td>
                  <td>
                    <span style="background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:4px;font-size:0.75rem;font-weight:700;">
                      <?= htmlspecialchars($log['event_type']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($log['status'] === 'sent'): ?>
                      <span class="badge badge-verified" style="font-size:0.75rem;"><i class="fas fa-check"></i> Sent</span>
                    <?php else: ?>
                      <span class="badge badge-pending" style="background:#fee2e2;color:#991b1b;font-size:0.75rem;"><i class="fas fa-triangle-exclamation"></i> Failed</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:0.8rem;color:var(--text-muted);white-space:nowrap;"><?= $log['sent_at'] ?></td>
                  <td style="font-size:0.76rem;color:#64748b;max-width:200px;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($log['error_message'] ?? 'OK') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
