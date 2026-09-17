<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Order.php';

$hash = $_GET['hash'] ?? '';
$order = Order::getOrderByHash($hash);

if (!$order) {
    die("Invoice not found or invalid link.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tax Invoice - <?= htmlspecialchars($order['order_number']) ?> - <?= APP_NAME ?></title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 30px;
      color: #1e293b;
      background: #f8fafc;
      font-size: 13px;
    }
    .invoice-card {
      max-width: 850px;
      margin: 0 auto;
      background: #ffffff;
      padding: 40px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      border-radius: 8px;
    }
    .header-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #0f172a;
      padding-bottom: 20px;
      margin-bottom: 25px;
    }
    .brand-title {
      font-size: 24px;
      font-weight: 900;
      color: #1d4ed8;
      letter-spacing: -0.5px;
    }
    .tagline {
      font-size: 11px;
      color: #64748b;
      font-weight: 700;
      margin-top: 2px;
    }
    .invoice-label {
      text-align: right;
    }
    .invoice-label h2 {
      margin: 0;
      font-size: 22px;
      font-weight: 800;
      color: #0f172a;
      text-transform: uppercase;
    }
    .details-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin-bottom: 25px;
    }
    .info-box h4 {
      margin: 0 0 8px;
      font-size: 12px;
      text-transform: uppercase;
      color: #1d4ed8;
      letter-spacing: 0.5px;
    }
    .info-box p {
      margin: 3px 0;
      line-height: 1.4;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 25px;
    }
    th {
      background: #0f172a;
      color: #ffffff;
      text-align: left;
      padding: 10px 12px;
      font-size: 11px;
      text-transform: uppercase;
    }
    td {
      padding: 10px 12px;
      border-bottom: 1px solid #e2e8f0;
      vertical-align: top;
    }
    .totals-area {
      display: flex;
      justify-content: space-between;
      margin-bottom: 30px;
    }
    .totals-table {
      width: 320px;
      border-collapse: collapse;
    }
    .totals-table td {
      padding: 6px 12px;
      border-bottom: 1px solid #f1f5f9;
    }
    .seal-box {
      background: #f1f5f9;
      border: 1px dashed #cbd5e1;
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 25px;
      font-family: monospace;
      font-size: 11px;
    }
    .print-bar {
      max-width: 850px;
      margin: 0 auto 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .btn-print {
      background: #1d4ed8;
      color: #ffffff;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 700;
      cursor: pointer;
    }
    @media print {
      body { padding: 0; background: #ffffff; }
      .print-bar { display: none; }
      .invoice-card { box-shadow: none; border: none; padding: 0; }
    }
  </style>
</head>
<body>

<div class="print-bar">
  <a href="<?= url('my_orders.php') ?>" style="color:#1d4ed8;text-decoration:none;font-weight:700;">&larr; Back to Orders</a>
  <button onclick="window.print()" class="btn-print">Print Tax Invoice</button>
</div>

<div class="invoice-card">
  
  <!-- Header Row -->
  <div class="header-row">
    <div>
      <div class="brand-title"><?= APP_NAME ?></div>
      <div class="tagline"><?= APP_TAGLINE ?></div>
      <p style="margin:6px 0 0;font-size:11.5px;color:#475569;">
        <?= APP_ADDRESS ?><br>
        GSTIN: <strong><?= APP_GSTIN ?></strong> | State Code: <strong>08 (Rajasthan)</strong><br>
        Phone: <?= APP_PHONE ?> | Email: <?= APP_EMAIL ?>
      </p>
    </div>
    <div class="invoice-label">
      <h2>TAX INVOICE</h2>
      <p style="margin:4px 0;">Invoice No: <strong><?= htmlspecialchars($order['order_number']) ?></strong></p>
      <p style="margin:2px 0;">Date: <strong><?= date('d-m-Y', strtotime($order['created_at'])) ?></strong></p>
      <p style="margin:2px 0;">Place of Supply: <strong><?= htmlspecialchars($order['shipping_state']) ?></strong></p>
    </div>
  </div>

  <!-- Buyer & Consignee Details -->
  <div class="details-grid">
    <div class="info-box">
      <h4>Billed To (Customer):</h4>
      <p><strong><?= htmlspecialchars($order['customer_name']) ?></strong></p>
      <?php if (!empty($order['business_name'])): ?>
        <p>Firm: <strong><?= htmlspecialchars($order['business_name']) ?></strong></p>
      <?php endif; ?>
      <?php if (!empty($order['gstin'])): ?>
        <p>GSTIN: <strong><?= htmlspecialchars($order['gstin']) ?></strong></p>
      <?php endif; ?>
      <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
      <p><?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?> - <?= htmlspecialchars($order['shipping_pincode']) ?></p>
      <p>Phone: <?= htmlspecialchars($order['customer_phone']) ?> | Email: <?= htmlspecialchars($order['customer_email']) ?></p>
    </div>

    <div class="info-box">
      <h4>Payment &amp; Logistics Details:</h4>
      <p>Payment Mode: <strong><?= strtoupper(str_replace('_', ' ', $order['payment_method'])) ?></strong></p>
      <p>Payment Status: <strong><?= strtoupper($order['payment_status']) ?></strong></p>
      <?php if (!empty($order['payment_ref'])): ?>
        <p>Transaction / UTR: <strong><?= htmlspecialchars($order['payment_ref']) ?></strong></p>
      <?php endif; ?>
      <?php if (!empty($order['accounts_staff_name'])): ?>
        <p>Verified By Accounts: <strong><?= htmlspecialchars($order['accounts_staff_name']) ?></strong></p>
      <?php endif; ?>
      <?php if (!empty($order['courier_name'])): ?>
        <p>Courier Partner: <strong><?= htmlspecialchars($order['courier_name']) ?></strong> (AWB: <?= htmlspecialchars($order['tracking_number']) ?>)</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Items Table -->
  <table>
    <thead>
      <tr>
        <th style="width:40px;">#</th>
        <th>Description of Goods</th>
        <th>HSN/SAC</th>
        <th>Qty</th>
        <th>Unit Rate</th>
        <th>Taxable Value</th>
        <th>GST %</th>
        <th style="text-align:right;">Amount (₹)</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = 1; foreach ($order['items'] as $item): 
        $unitPrice = (float)$item['unit_price'];
        $gstRate = (float)$item['gst_rate'];
        $baseRate = $unitPrice / (1 + ($gstRate / 100));
        $taxableVal = $baseRate * $item['quantity'];
      ?>
        <tr>
          <td><?= $i++ ?></td>
          <td>
            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
            <div style="font-size:11px;color:#64748b;">SKU: <?= htmlspecialchars($item['sku']) ?></div>
          </td>
          <td><?= htmlspecialchars($item['hsn_code']) ?></td>
          <td><?= $item['quantity'] ?></td>
          <td>₹<?= number_format($baseRate, 2) ?></td>
          <td>₹<?= number_format($taxableVal, 2) ?></td>
          <td><?= (int)$gstRate ?>%</td>
          <td style="text-align:right;font-weight:700;">₹<?= number_format($item['total_price'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Totals Section -->
  <div class="totals-area">
    <div style="max-width:400px;font-size:11.5px;color:#475569;">
      <strong>Terms &amp; Conditions:</strong>
      <ol style="margin:6px 0 0 16px;padding:0;line-height:1.5;">
        <li>Goods once sold are covered under respective manufacturer brand warranty.</li>
        <li>For warranty claims on printers, laptops and toners, please retain this original tax invoice.</li>
        <li>Subject to Jaipur Jurisdiction only.</li>
      </ol>
    </div>

    <div>
      <table class="totals-table">
        <tr>
          <td>Taxable Subtotal:</td>
          <td style="text-align:right;font-weight:700;">₹<?= number_format($order['subtotal'], 2) ?></td>
        </tr>
        <tr>
          <td>CGST (<?= $order['shipping_state'] === 'Rajasthan' ? '9%' : '0%' ?>):</td>
          <td style="text-align:right;">₹<?= number_format($order['shipping_state'] === 'Rajasthan' ? $order['tax_amount'] / 2 : 0, 2) ?></td>
        </tr>
        <tr>
          <td>SGST (<?= $order['shipping_state'] === 'Rajasthan' ? '9%' : '0%' ?>):</td>
          <td style="text-align:right;">₹<?= number_format($order['shipping_state'] === 'Rajasthan' ? $order['tax_amount'] / 2 : 0, 2) ?></td>
        </tr>
        <?php if ($order['shipping_state'] !== 'Rajasthan'): ?>
          <tr>
            <td>IGST (18%):</td>
            <td style="text-align:right;">₹<?= number_format($order['tax_amount'], 2) ?></td>
          </tr>
        <?php endif; ?>
        <tr>
          <td>Delivery &amp; Freight:</td>
          <td style="text-align:right;color:#059669;font-weight:700;">₹0.00 (FREE)</td>
        </tr>
        <tr style="border-top:2px solid #0f172a;font-size:15px;font-weight:900;">
          <td>Total Amount:</td>
          <td style="text-align:right;color:#1d4ed8;">₹<?= number_format($order['total_amount'], 2) ?></td>
        </tr>
      </table>
    </div>
  </div>

  <!-- Official Authenticity Seal -->
  <div class="seal-box" style="background:#f8fafc;border:1px solid #cbd5e1;padding:12px;border-radius:6px;font-size:11px;color:#475569;">
    <div style="color:#059669;font-weight:800;margin-bottom:2px;">
      [OFFICIAL TAX INVOICE - 100% GENUINE &amp; VERIFIED]
    </div>
    <div>System Invoice No: <?= htmlspecialchars($order['order_number']) ?> | GSTIN: <?= APP_GSTIN ?></div>
    <div>This is an electronically generated and certified tax invoice under Indian GST Rules.</div>
  </div>

  <!-- Signature -->
  <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:20px;">
    <div style="font-size:11px;color:#64748b;">
      Electronic Computer Generated Tax Invoice. All items verified with official warranty.
    </div>
    <div style="text-align:center;">
      <div style="font-weight:800;margin-bottom:40px;">For <?= APP_NAME ?></div>
      <div style="border-top:1px solid #94a3b8;padding-top:4px;font-size:11px;font-weight:700;">Authorized Signatory</div>
    </div>
  </div>

</div>

</body>
</html>
