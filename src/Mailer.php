<?php
/**
 * Sakshi Infotech - Enterprise Transactional Mailer & SMTP Engine
 * 
 * Supports configurable SMTP via DB (system_settings), pure PHP socket client with
 * TLS/SSL, fallback to mail(), audit logging to email_logs, and responsive branded HTML templates.
 */

if (!defined('BASE_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once BASE_PATH . '/config/database.php';

class Mailer {

    /**
     * Retrieve a setting from system_settings with optional default
     */
    public static function getSetting(string $key, string $default = ''): string {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $res = $stmt->fetchColumn();
            return ($res !== false && $res !== null) ? (string)$res : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Retrieve all email settings as an associative array
     */
    public static function getEmailSettings(): array {
        return [
            'smtp_from_email' => self::getSetting('smtp_from_email', 'noreply@sitindia.in'),
            'smtp_from_name'  => self::getSetting('smtp_from_name', APP_NAME),
            'smtp_host'       => self::getSetting('smtp_host', 'mail.sitindia.in'),
            'smtp_port'       => self::getSetting('smtp_port', '587'),
            'smtp_secure'     => self::getSetting('smtp_secure', 'tls'),
            'smtp_user'       => self::getSetting('smtp_user', 'noreply@sitindia.in'),
            'smtp_pass'       => self::getSetting('smtp_pass', ''),
            'mail_driver'     => self::getSetting('mail_driver', 'smtp')
        ];
    }

    /**
     * Update settings in system_settings table
     */
    public static function updateSettings(array $settings): bool {
        try {
            $db = Database::getConnection();
            $stmtCheck = $db->prepare("SELECT setting_key FROM system_settings WHERE setting_key = ?");
            $stmtUp = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_at = ? WHERE setting_key = ?");
            $stmtIn = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)");

            $now = date('Y-m-d H:i:s');
            foreach ($settings as $k => $v) {
                $stmtCheck->execute([$k]);
                if ($stmtCheck->fetch()) {
                    $stmtUp->execute([(string)$v, $now, $k]);
                } else {
                    $stmtIn->execute([$k, (string)$v, $now]);
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Failed updating system_settings: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Primary Send Mail Dispatcher
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $eventType = 'general'): array {
        $settings = self::getEmailSettings();
        $fromEmail = !empty($settings['smtp_from_email']) ? $settings['smtp_from_email'] : 'noreply@sitindia.in';
        $fromName  = !empty($settings['smtp_from_name']) ? $settings['smtp_from_name'] : APP_NAME;

        $wrappedHtml = self::wrapBrandedTemplate($subject, $htmlBody, $toName);

        $success = false;
        $errorMessage = null;

        // 1. If SMTP configured with valid host and password, attempt direct socket SMTP
        if ($settings['mail_driver'] === 'smtp' && !empty($settings['smtp_host']) && !empty($settings['smtp_user'])) {
            $smtpRes = self::sendViaSocketSmtp($settings, $toEmail, $toName, $subject, $wrappedHtml);
            if ($smtpRes['success']) {
                $success = true;
            } else {
                $errorMessage = "SMTP Error: " . $smtpRes['message'];
                // Attempt fallback to standard PHP mail()
                $mailRes = self::sendViaPhpMail($fromEmail, $fromName, $toEmail, $subject, $wrappedHtml);
                if ($mailRes['success']) {
                    $success = true;
                    $errorMessage .= " (Delivered via PHP mail fallback)";
                } else {
                    $errorMessage .= " | Mail() Fallback Error: " . $mailRes['message'];
                }
            }
        } else {
            // Standard PHP mail()
            $mailRes = self::sendViaPhpMail($fromEmail, $fromName, $toEmail, $subject, $wrappedHtml);
            $success = $mailRes['success'];
            if (!$success) {
                $errorMessage = $mailRes['message'];
            }
        }

        // Log to database
        self::logEmail($toEmail, $subject, $eventType, $success ? 'sent' : 'failed', $errorMessage);

        return [
            'success' => $success,
            'message' => $success ? 'Email sent successfully.' : ($errorMessage ?? 'Failed to send email.')
        ];
    }

    /**
     * Native Socket-based SMTP Client (Pure PHP - No external dependencies)
     */
    private static function sendViaSocketSmtp(array $cfg, string $to, string $toName, string $subject, string $html): array {
        $host = trim($cfg['smtp_host']);
        $port = (int)$cfg['smtp_port'];
        $secure = strtolower($cfg['smtp_secure']);
        $user = trim($cfg['smtp_user']);
        $pass = (string)$cfg['smtp_pass'];
        $fromEmail = trim($cfg['smtp_from_email']);
        $fromName = trim($cfg['smtp_from_name']);

        $timeout = 10;
        $errno = 0;
        $errstr = '';

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        $targetUri = ($secure === 'ssl') ? 'ssl://' . $host . ':' . $port : 'tcp://' . $host . ':' . $port;
        $socket = @stream_socket_client($targetUri, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            return ['success' => false, 'message' => "Connection failed to {$targetUri} ($errno: $errstr)"];
        }

        stream_set_timeout($socket, $timeout);

        $read = function() use ($socket) {
            $data = '';
            while ($line = fgets($socket, 515)) {
                $data .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $data;
        };

        $write = function(string $cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $banner = $read();
        if (substr($banner, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'message' => "Invalid server banner: $banner"];
        }

        // EHLO
        $write("EHLO " . gethostname());
        $ehloResp = $read();

        // STARTTLS
        if ($secure === 'tls' && strpos($ehloResp, 'STARTTLS') !== false) {
            $write("STARTTLS");
            $tlsResp = $read();
            if (substr($tlsResp, 0, 3) === '220') {
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    return ['success' => false, 'message' => "Failed to start TLS encryption"];
                }
                // Resend EHLO after TLS established
                $write("EHLO " . gethostname());
                $ehloResp = $read();
            }
        }

        // AUTH LOGIN
        if (!empty($user) && !empty($pass)) {
            $write("AUTH LOGIN");
            $authResp = $read();
            if (substr($authResp, 0, 3) !== '334') {
                fclose($socket);
                return ['success' => false, 'message' => "AUTH LOGIN rejected: $authResp"];
            }

            $write(base64_encode($user));
            $userResp = $read();
            if (substr($userResp, 0, 3) !== '334') {
                fclose($socket);
                return ['success' => false, 'message' => "Username rejected: $userResp"];
            }

            $write(base64_encode($pass));
            $passResp = $read();
            if (substr($passResp, 0, 3) !== '235') {
                fclose($socket);
                return ['success' => false, 'message' => "Password authentication failed: $passResp"];
            }
        }

        // MAIL FROM
        $write("MAIL FROM: <{$fromEmail}>");
        $fromResp = $read();
        if (substr($fromResp, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'message' => "MAIL FROM rejected: $fromResp"];
        }

        // RCPT TO
        $write("RCPT TO: <{$to}>");
        $rcptResp = $read();
        if (substr($rcptResp, 0, 3) !== '250' && substr($rcptResp, 0, 3) !== '251') {
            fclose($socket);
            return ['success' => false, 'message' => "RCPT TO rejected for {$to}: $rcptResp"];
        }

        // DATA
        $write("DATA");
        $dataResp = $read();
        if (substr($dataResp, 0, 3) !== '354') {
            fclose($socket);
            return ['success' => false, 'message' => "DATA command rejected: $dataResp"];
        }

        // MIME Headers & Message
        $headers = [];
        $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
        $headers[] = "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$to}>";
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "Date: " . date('r');
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        $headers[] = "X-Mailer: SakshiInfotech-Enterprise-Mailer";

        $messageData = implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.";
        $write($messageData);

        $sendResp = $read();
        $write("QUIT");
        fclose($socket);

        if (substr($sendResp, 0, 3) !== '250') {
            return ['success' => false, 'message' => "Server rejected message body: $sendResp"];
        }

        return ['success' => true, 'message' => 'Sent via SMTP'];
    }

    /**
     * Fallback via PHP mail()
     */
    private static function sendViaPhpMail(string $fromEmail, string $fromName, string $to, string $subject, string $html): array {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];

        $sent = @mail($to, $subject, $html, implode("\r\n", $headers));
        return [
            'success' => (bool)$sent,
            'message' => $sent ? 'Sent via mail()' : 'PHP mail() function returned false'
        ];
    }

    /**
     * Log email transmission into email_logs table
     */
    private static function logEmail(string $to, string $subject, string $event, string $status, ?string $err): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO email_logs (recipient_email, subject, event_type, status, error_message, sent_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$to, $subject, $event, $status, $err, date('Y-m-d H:i:s')]);
        } catch (Exception $e) {
            error_log("Failed writing email log: " . $e->getMessage());
        }
    }

    /**
     * Branded HTML Email Template Wrapper
     */
    public static function wrapBrandedTemplate(string $title, string $contentHtml, string $recipientName = ''): string {
        $appName = htmlspecialchars(APP_NAME);
        $appPhone = htmlspecialchars(APP_PHONE);
        $appEmail = htmlspecialchars(APP_EMAIL);
        $appAddress = htmlspecialchars(APP_ADDRESS);
        $appGstin = htmlspecialchars(APP_GSTIN);
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <style>
    body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; color: #1e293b; }
    .wrapper { width: 100%; max-width: 620px; margin: 20px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0; }
    .header { background: linear-gradient(135deg, #0A192F 0%, #1e3a8a 100%); padding: 24px 30px; text-align: left; }
    .header h1 { margin: 0; color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: 0.5px; }
    .header p { margin: 4px 0 0; color: #94a3b8; font-size: 12px; }
    .badge-bar { background: #38bdf8; height: 3px; width: 100%; }
    .body { padding: 30px; line-height: 1.6; font-size: 14.5px; }
    .btn { display: inline-block; background-color: #2563eb; color: #ffffff !important; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; margin: 15px 0; }
    .card-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin: 16px 0; }
    .otp-code { font-size: 32px; font-weight: 900; letter-spacing: 6px; color: #2563eb; background: #eff6ff; padding: 12px 24px; border-radius: 8px; display: inline-block; border: 2px dashed #93c5fd; margin: 15px 0; }
    .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 30px; text-align: center; font-size: 12px; color: #64748b; line-height: 1.5; }
    .footer strong { color: #0f172a; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>{$appName}</h1>
      <p>Authorized IT Hardware &amp; Commercial Stationery Hub • GST: {$appGstin}</p>
    </div>
    <div class="badge-bar"></div>
    <div class="body">
      {$contentHtml}
    </div>
    <div class="footer">
      <p><strong>{$appName}</strong><br>{$appAddress}<br>Support: {$appPhone} | Email: {$appEmail}</p>
      <p style="margin-top:10px;font-size:11px;color:#94a3b8;">This is an automated system notification from noreply@sitindia.in. Please do not reply directly to this email.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    // =============================================================
    // TRANSACTIONAL PROCESS METHODS
    // =============================================================

    /**
     * 1. Send Registration OTP
     */
    public static function sendOtp(string $toEmail, string $toName, string $otpCode): array {
        $subject = "Your Verification OTP: {$otpCode} - " . APP_NAME;
        $html = <<<HTML
<h2 style="color:#0f172a;margin-top:0;">Verify Your Email Address</h2>
<p>Hello <strong>{$toName}</strong>,</p>
<p>Thank you for registering with <strong>Sakshi Infotech</strong>. Please use the One-Time Password (OTP) below to complete your email verification and account setup:</p>
<div style="text-align:center;">
  <div class="otp-code">{$otpCode}</div>
</div>
<p style="color:#64748b;font-size:13px;">This OTP is valid for <strong>15 minutes</strong>. If you did not initiate this request, please disregard this email.</p>
HTML;
        return self::send($toEmail, $toName, $subject, $html, 'registration_otp');
    }

    /**
     * 2. Send User Creation & Credentials Welcome Email
     */
    public static function sendUserWelcome(array $userData, ?string $plainPassword = null): array {
        $toEmail = $userData['email'];
        $toName = $userData['full_name'];
        $username = $userData['username'];
        $customerType = strtoupper($userData['customer_type'] ?? 'INDIVIDUAL');
        $companyName = !empty($userData['company_name']) ? $userData['company_name'] : 'N/A';
        $gstin = !empty($userData['gst_number']) ? $userData['gst_number'] : 'N/A';
        $loginUrl = function_exists('url') ? url('login.php') : '/login.php';

        $credBlock = "";
        if ($plainPassword) {
            $credBlock = "<p><strong>Password:</strong> <code style='background:#f1f5f9;padding:3px 6px;border-radius:4px;'>{$plainPassword}</code></p>";
        }

        $subject = "Welcome to " . APP_NAME . " - Account Created Successfully";
        $html = <<<HTML
<h2 style="color:#0f172a;margin-top:0;">Welcome to Sakshi Infotech!</h2>
<p>Hello <strong>{$toName}</strong>,</p>
<p>Your customer account has been registered and verified successfully. You can now place orders, access GST tax invoices, and track shipments in real-time.</p>

<div class="card-box">
  <h3 style="margin-top:0;font-size:15px;color:#0f172a;">Your Account Details:</h3>
  <p style="margin:5px 0;"><strong>Customer Type:</strong> <span style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:4px;font-weight:700;font-size:12px;">{$customerType}</span></p>
  <p style="margin:5px 0;"><strong>Company Name:</strong> {$companyName}</p>
  <p style="margin:5px 0;"><strong>GSTIN:</strong> {$gstin}</p>
  <p style="margin:5px 0;"><strong>Username / Login ID:</strong> <strong style="color:#2563eb;">{$username}</strong></p>
  <p style="margin:5px 0;"><strong>Email Address:</strong> {$toEmail}</p>
  {$credBlock}
</div>

<div style="text-align:center;margin:25px 0;">
  <a href="{$loginUrl}" class="btn" style="color:#ffffff;">Sign In to Your Account &rarr;</a>
</div>

<p style="font-size:13px;color:#64748b;">Keep your credentials confidential. You can update your profile or change your password anytime in your dashboard.</p>
HTML;
        return self::send($toEmail, $toName, $subject, $html, 'user_creation');
    }

    /**
     * 3. Send Order Placed / Done Confirmation
     */
    public static function sendOrderPlaced(array $order, array $items = []): array {
        $toEmail = $order['customer_email'];
        $toName = $order['customer_name'];
        $orderNumber = $order['order_number'];
        $totalAmount = number_format($order['total_amount'], 2);
        $paymentMethod = strtoupper(str_replace('_', ' ', $order['payment_method'] ?? 'UPI'));
        $invoiceUrl = function_exists('url') ? url('invoice.php?order_number=' . urlencode($orderNumber)) : '/invoice.php';

        $itemsRows = '';
        foreach ($items as $it) {
            $pName = htmlspecialchars($it['product_name'] ?? $it['name'] ?? 'Product');
            $pQty = (int)($it['quantity'] ?? 1);
            $pPrice = number_format($it['unit_price'] ?? $it['price'] ?? 0, 2);
            $pTotal = number_format(($it['quantity'] ?? 1) * ($it['unit_price'] ?? $it['price'] ?? 0), 2);
            $itemsRows .= "<tr><td style='padding:8px;border-bottom:1px solid #e2e8f0;'>{$pName}</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;text-align:center;'>{$pQty}</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;text-align:right;'>₹{$pPrice}</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:700;'>₹{$pTotal}</td></tr>";
        }

        $subject = "Order Confirmed: #{$orderNumber} - " . APP_NAME;
        $html = <<<HTML
<h2 style="color:#0f172a;margin-top:0;">Thank You for Your Order!</h2>
<p>Hello <strong>{$toName}</strong>,</p>
<p>Your order <strong>#{$orderNumber}</strong> has been received successfully and is being queued for verification by our accounts team.</p>

<div class="card-box">
  <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
    <span><strong>Order Number:</strong> {$orderNumber}</span>
    <span><strong>Date:</strong> {$order['created_at']}</span>
  </div>
  <p style="margin:4px 0;"><strong>Payment Method:</strong> {$paymentMethod}</p>
  <p style="margin:4px 0;"><strong>Delivery Address:</strong> {$order['shipping_address']}, {$order['shipping_city']}, {$order['shipping_state']} - {$order['shipping_pincode']}</p>
</div>

<h3 style="font-size:15px;color:#0f172a;margin-top:20px;">Order Summary:</h3>
<table style="width:100%;border-collapse:collapse;font-size:13.5px;">
  <thead>
    <tr style="background:#f1f5f9;color:#475569;">
      <th style="padding:8px;text-align:left;">Item</th>
      <th style="padding:8px;text-align:center;">Qty</th>
      <th style="padding:8px;text-align:right;">Price</th>
      <th style="padding:8px;text-align:right;">Total</th>
    </tr>
  </thead>
  <tbody>
    {$itemsRows}
  </tbody>
  <tfoot>
    <tr>
      <td colspan="3" style="padding:10px;text-align:right;font-weight:800;font-size:15px;">Grand Total (Incl. GST):</td>
      <td style="padding:10px;text-align:right;font-weight:900;font-size:16px;color:#2563eb;">₹{$totalAmount}</td>
    </tr>
  </tfoot>
</table>

<div style="text-align:center;margin:25px 0;">
  <a href="{$invoiceUrl}" class="btn" style="color:#ffffff;">View &amp; Download GST Tax Invoice &rarr;</a>
</div>
HTML;
        return self::send($toEmail, $toName, $subject, $html, 'order_done');
    }

    /**
     * 4. Send Payment Received Confirmation
     */
    public static function sendPaymentReceived(array $order): array {
        $toEmail = $order['customer_email'];
        $toName = $order['customer_name'];
        $orderNumber = $order['order_number'];
        $totalAmount = number_format($order['total_amount'], 2);
        $paymentHash = $order['payment_hash'] ?? 'VERIFIED_ON_' . date('Ymd');
        $invoiceUrl = function_exists('url') ? url('invoice.php?order_number=' . urlencode($orderNumber)) : '/invoice.php';

        $subject = "Payment Verified & Received: Order #{$orderNumber} - " . APP_NAME;
        $html = <<<HTML
<div style="text-align:center;margin-bottom:15px;">
  <div style="width:50px;height:50px;background:#dcfce7;color:#16a34a;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:24px;font-weight:bold;">&#10003;</div>
  <h2 style="color:#0f172a;margin:10px 0 5px;">Payment Received &amp; Verified</h2>
</div>
<p>Hello <strong>{$toName}</strong>,</p>
<p>We are pleased to inform you that payment of <strong>₹{$totalAmount}</strong> for your order <strong>#{$orderNumber}</strong> has been audited and verified by our Accounts Department.</p>

<div class="card-box" style="background:#f0fdf4;border-color:#bbf7d0;">
  <p style="margin:4px 0;"><strong>Payment Status:</strong> <span style="color:#16a34a;font-weight:bold;">VERIFIED &amp; COMPLETED</span></p>
  <p style="margin:4px 0;"><strong>Payment Reference:</strong> {$order['payment_ref']}</p>
  <p style="margin:4px 0;"><strong>Payment Seal Hash:</strong> <code style="font-size:11px;color:#166534;">{$paymentHash}</code></p>
  <p style="margin:4px 0;"><strong>Input Tax Credit (ITC):</strong> Fully Claimable on Tax Invoice</p>
</div>

<p>Your order is now being queued for physical stock check and tamper-proof packing.</p>

<div style="text-align:center;margin:20px 0;">
  <a href="{$invoiceUrl}" class="btn" style="background-color:#16a34a;color:#ffffff;">Download Receipt &amp; Invoice</a>
</div>
HTML;
        return self::send($toEmail, $toName, $subject, $html, 'payment_received');
    }

    /**
     * 5. Send Order Packed & Checked Notification
     */
    public static function sendOrderPacked(array $order): array {
        $toEmail = $order['customer_email'];
        $toName = $order['customer_name'];
        $orderNumber = $order['order_number'];

        $subject = "Order Packed & Verified: #{$orderNumber} - " . APP_NAME;
        $html = <<<HTML
<h2 style="color:#0f172a;margin-top:0;">Your Order is Packed &amp; Ready!</h2>
<p>Hello <strong>{$toName}</strong>,</p>
<p>Great news! All items in your order <strong>#{$orderNumber}</strong> have been physically inspected, serial numbers verified, and securely packed in our Jaipur warehouse.</p>

<div class="card-box">
  <p style="margin:4px 0;"><strong>Order Number:</strong> {$orderNumber}</p>
  <p style="margin:4px 0;"><strong>Packing Status:</strong> <span style="color:#0284c7;font-weight:700;">CHECKED &amp; SEALED</span></p>
  <p style="margin:4px 0;"><strong>Next Stage:</strong> Handover to Dispatch Logistics for carrier pickup.</p>
</div>

<p>You will receive your carrier tracking link as soon as the shipment is dispatched.</p>
HTML;
        return self::send($toEmail, $toName, $subject, $html, 'order_packed');
    }

    /**
     * 6. Send Order Dispatched Notification
     */
    public static function sendOrderDispatched(array $order, string $courier, string $trackingNo): array {
        $toEmail = $order['customer_email'];
        $toName = $order['customer_name'];
        $orderNumber = $order['order_number'];
        $dispatchHash = $order['dispatch_hash'] ?? 'DISPATCH_VERIFIED';

        $subject = "Dispatched: Order #{$orderNumber} is on the way! - " . APP_NAME;
        $html = <<<HTML
<h2 style="color:#0f172a;margin-top:0;">Your Order Has Been Dispatched!</h2>
<p>Hello <strong>{$toName}</strong>,</p>
<p>Your package for order <strong>#{$orderNumber}</strong> has been handed over to our shipping partner and is currently in transit to your address.</p>

<div class="card-box" style="background:#eff6ff;border-color:#bfdbfe;">
  <p style="margin:5px 0;"><strong>Courier Partner:</strong> <strong style="color:#1e3a8a;">{$courier}</strong></p>
  <p style="margin:5px 0;"><strong>AWB / Tracking Number:</strong> <code style="font-size:14px;font-weight:bold;color:#2563eb;">{$trackingNo}</code></p>
  <p style="margin:5px 0;"><strong>Dispatch Seal Hash:</strong> <code style="font-size:11px;color:#64748b;">{$dispatchHash}</code></p>
  <p style="margin:5px 0;"><strong>Shipping Destination:</strong> {$order['shipping_city']}, {$order['shipping_state']} - {$order['shipping_pincode']}</p>
</div>

<p>You can track the progress of your shipment directly with <strong>{$courier}</strong> using your tracking number <strong>{$trackingNo}</strong>.</p>
HTML;
        return self::send($toEmail, $toName, $subject, $html, 'order_dispatched');
    }

    /**
     * 7. Send Order Delivered Notification
     */
    public static function sendOrderDelivered(array $order): array {
        $toEmail = $order['customer_email'];
        $toName = $order['customer_name'];
        $orderNumber = $order['order_number'];

        $subject = "Delivered: Order #{$orderNumber} - " . APP_NAME;
        $html = <<<HTML
<h2 style="color:#0f172a;margin-top:0;">Order Delivered Successfully!</h2>
<p>Hello <strong>{$toName}</strong>,</p>
<p>Your order <strong>#{$orderNumber}</strong> has been marked as delivered. We hope everything arrived in perfect condition.</p>

<p>If you have any questions or require manufacturer warranty support, our customer care team is here to assist you at <strong>APP_PHONE</strong> or <strong>APP_EMAIL</strong>.</p>

<p style="margin-top:20px;">Thank you for choosing <strong>Sakshi Infotech</strong>!</p>
HTML;
        return self::send($toEmail, $toName, $subject, $html, 'order_delivered');
    }

    /**
     * Test SMTP Connection
     */
    public static function testConnection(string $toEmail): array {
        $testSubject = "SMTP Test Connection Successful - " . APP_NAME;
        $testContent = <<<HTML
<h2 style="color:#16a34a;margin-top:0;">SMTP Connection Verified!</h2>
<p>This is a test email sent from the Sakshi Infotech Administration Portal.</p>
<p>Your email settings (Host, Port, User, and Password) are configured properly and active.</p>
<p>Timestamp: <strong>" . date('Y-m-d H:i:s') . "</strong></p>
HTML;
        return self::send($toEmail, 'Administrator', $testSubject, $testContent, 'smtp_test');
    }
}
