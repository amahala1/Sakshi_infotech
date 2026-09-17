<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Product.php';
require_once BASE_PATH . '/src/Order.php';

// Handle clear cart before headers are sent
if (isset($_GET['clear'])) {
    Order::clearCart();
    header("Location: " . url('cart.php'));
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 1);

    if ($action === 'add') {
        $res = Order::addToCart($productId, $qty);
        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
    }

    if ($action === 'update') {
        $res = Order::updateCart($productId, $qty);
        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
    }

    if ($action === 'remove') {
        $res = Order::removeFromCart($productId);
        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
    }
}

// Handle AJAX drawer contents
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_drawer') {
    $cart = Order::getCartDetails();
    ob_start();
    if (empty($cart['items'])):
    ?>
      <div style="text-align:center;padding:45px 20px;color:var(--text-muted);">
        <div style="width:70px;height:70px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;color:#94a3b8;font-size:1.8rem;">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <h4 style="font-size:1.1rem;font-weight:800;color:var(--text-main);">Your cart is empty</h4>
        <p style="font-size:0.85rem;margin-top:6px;">Explore our computers and stationery collection to add items.</p>
        <a href="<?= url('shop.php') ?>" class="btn-primary-si" style="margin-top:20px;display:inline-flex;padding:10px 22px;font-size:0.88rem;">Start Shopping</a>
      </div>
    <?php else: ?>
      <?php foreach ($cart['items'] as $item): ?>
        <div class="cart-item-row" id="drawerItem-<?= $item['product_id'] ?>">
          <div class="cart-item-thumb">
            <img src="<?= product_image_url($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
          </div>
          <div class="cart-item-details">
            <h4 class="cart-item-title"><?= htmlspecialchars($item['name']) ?></h4>
            <div class="cart-item-price">₹<?= number_format($item['unit_price'], 2) ?></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
              <div class="qty-control">
                <button class="qty-btn" type="button" onclick="updateCartItemQty(<?= $item['product_id'] ?>, <?= $item['quantity'] - 1 ?>)">-</button>
                <span class="qty-val"><?= $item['quantity'] ?></span>
                <button class="qty-btn" type="button" onclick="updateCartItemQty(<?= $item['product_id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
              </div>
              <button type="button" onclick="updateCartItemQty(<?= $item['product_id'] ?>, 0)" style="background:none;border:none;color:var(--danger);font-size:0.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:4px;">
                <i class="fas fa-trash-alt"></i> Remove
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php
    endif;
    $itemsHtml = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => $cart['count'],
        'has_items' => !empty($cart['items']),
        'subtotal' => number_format($cart['subtotal'], 2),
        'tax_amount' => number_format($cart['tax_amount'], 2),
        'total_amount' => number_format($cart['total_amount'], 2),
        'html' => $itemsHtml
    ]);
    exit;
}

$pageTitle = "Shopping Cart - " . APP_NAME;
$cart = Order::getCartDetails();

require_once __DIR__ . '/includes/header.php';
?>

<div class="section-wrapper" style="margin-top:30px;margin-bottom:60px;">
  <!-- Breadcrumb -->
  <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:20px;display:flex;align-items:center;gap:8px;">
    <a href="<?= url('sale.php') ?>"><i class="fas fa-home"></i> Home</a>
    <span>/</span>
    <a href="<?= url('shop.php') ?>">Shop Catalog</a>
    <span>/</span>
    <span style="color:var(--text-main);font-weight:700;">Shopping Cart</span>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;border-bottom:2px solid #e2e8f0;padding-bottom:14px;">
    <h1 style="font-size:1.75rem;font-weight:900;display:flex;align-items:center;gap:12px;color:#0f172a;margin:0;">
      <i class="fas fa-shopping-cart" style="color:var(--primary);"></i>
      Shopping Cart
      <span style="font-size:1rem;font-weight:700;background:#eff6ff;color:var(--primary);padding:4px 12px;border-radius:999px;border:1px solid #bfdbfe;">
        <?= $cart['count'] ?> <?= $cart['count'] === 1 ? 'item' : 'items' ?>
      </span>
    </h1>
    <?php if (!empty($cart['items'])): ?>
      <a href="<?= url('cart.php?clear=1') ?>" onclick="return confirm('Clear all items from your cart?');" style="color:var(--danger);font-size:0.85rem;font-weight:700;display:flex;align-items:center;gap:6px;text-decoration:none;">
        <i class="fas fa-trash-alt"></i> Empty Cart
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($cart['items'])): ?>
    <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:70px 20px;text-align:center;box-shadow:var(--shadow-sm);max-width:700px;margin:30px auto;">
      <div style="width:90px;height:90px;border-radius:50%;background:#f8fafc;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:#cbd5e1;font-size:2.6rem;border:1px solid #e2e8f0;">
        <i class="fas fa-cart-arrow-down"></i>
      </div>
      <h2 style="font-size:1.5rem;font-weight:800;color:#0f172a;">Your Cart is Currently Empty</h2>
      <p style="color:var(--text-muted);font-size:0.95rem;margin:10px auto 25px;max-width:440px;line-height:1.6;">
        You haven't added any products to your shopping cart yet. Browse our commercial computers and office stationery supplies to find what you need.
      </p>
      <div style="display:flex;justify-content:center;gap:14px;flex-wrap:wrap;">
        <a href="<?= url('shop.php') ?>" class="btn-primary-si">
          <i class="fas fa-th-large"></i> Browse All Products
        </a>
        <a href="<?= url('shop.php?type=computer') ?>" class="btn-outline-si">
          <i class="fas fa-laptop" style="color:var(--primary);"></i> IT Hardware
        </a>
        <a href="<?= url('shop.php?type=stationery') ?>" class="btn-outline-si">
          <i class="fas fa-print" style="color:#059669;"></i> Stationery &amp; Toners
        </a>
      </div>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:1.75fr 1fr;gap:30px;align-items:start;">
      
      <!-- Cart Table Card -->
      <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);overflow:hidden;box-shadow:var(--shadow-sm);">
        <table class="admin-table" style="margin:0;">
          <thead>
            <tr>
              <th style="padding:16px 20px;">Product Description</th>
              <th style="width:130px;">Unit Price</th>
              <th style="width:140px;text-align:center;">Quantity</th>
              <th style="width:130px;text-align:right;">Subtotal</th>
              <th style="width:60px;text-align:center;"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cart['items'] as $item): ?>
              <tr>
                <td style="padding:16px 20px;">
                  <div style="display:flex;align-items:center;gap:16px;">
                    <div style="width:72px;height:72px;background:#f8fafc;padding:6px;border-radius:10px;border:1px solid var(--border-color);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                      <img src="<?= product_image_url($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="max-height:100%;max-width:100%;object-fit:contain;">
                    </div>
                    <div>
                      <a href="<?= url('product.php?id=' . $item['product_id']) ?>" style="font-weight:800;color:var(--text-main);font-size:0.95rem;line-height:1.35;display:block;margin-bottom:4px;">
                        <?= htmlspecialchars($item['name']) ?>
                      </a>
                      <div style="display:flex;gap:10px;font-size:0.76rem;color:var(--text-muted);font-weight:600;">
                        <span>SKU: <strong style="color:#334155;"><?= htmlspecialchars($item['sku']) ?></strong></span>
                        <span>•</span>
                        <span>HSN: <?= htmlspecialchars($item['hsn_code']) ?></span>
                        <span>•</span>
                        <span style="color:#059669;"><?= (int)$item['gst_rate'] ?>% GST Included</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td style="font-weight:700;color:#0f172a;font-size:0.95rem;">
                  ₹<?= number_format($item['unit_price'], 2) ?>
                </td>
                <td style="text-align:center;">
                  <div class="qty-control" style="margin:0 auto;">
                    <button class="qty-btn" type="button" onclick="updateCartItemQty(<?= $item['product_id'] ?>, <?= $item['quantity'] - 1 ?>)">-</button>
                    <span class="qty-val"><?= $item['quantity'] ?></span>
                    <button class="qty-btn" type="button" onclick="updateCartItemQty(<?= $item['product_id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
                  </div>
                </td>
                <td style="font-weight:900;color:var(--primary);font-size:1.05rem;text-align:right;">
                  ₹<?= number_format($item['line_total'], 2) ?>
                </td>
                <td style="text-align:center;">
                  <button type="button" onclick="updateCartItemQty(<?= $item['product_id'] ?>, 0)" class="btn-sm-action btn-reject" title="Remove item from cart" style="cursor:pointer;">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Table Bottom Action Strip -->
        <div style="padding:18px 24px;background:#f8fafc;border-top:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
          <a href="<?= url('shop.php') ?>" class="btn-outline-si" style="padding:9px 18px;font-size:0.86rem;">
            <i class="fas fa-arrow-left"></i> Continue Shopping
          </a>
          <div style="font-size:0.82rem;color:var(--text-muted);display:flex;align-items:center;gap:6px;">
            <i class="fas fa-shield-alt" style="color:var(--success);"></i>
            <span>All cart values calculated with precise GST Input Tax Credit rules</span>
          </div>
        </div>
      </div>

      <!-- Order Summary Card -->
      <div style="background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:28px;box-shadow:var(--shadow-sm);position:sticky;top:90px;">
        <h3 style="font-size:1.25rem;font-weight:900;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border-color);color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
          <span>Order Summary</span>
          <span style="font-size:0.8rem;font-weight:700;color:var(--primary);"><i class="fas fa-file-invoice"></i> Tax Invoice</span>
        </h3>

        <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:0.92rem;color:var(--text-muted);">
          <span>Taxable Subtotal:</span>
          <span style="font-weight:700;color:var(--text-main);">₹<?= number_format($cart['subtotal'], 2) ?></span>
        </div>

        <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:0.92rem;color:var(--text-muted);">
          <span>Estimated GST (CGST + SGST):</span>
          <span style="font-weight:700;color:var(--text-main);">₹<?= number_format($cart['tax_amount'], 2) ?></span>
        </div>

        <div style="display:flex;justify-content:space-between;margin-bottom:16px;font-size:0.92rem;color:var(--text-muted);">
          <span>Shipping &amp; Delivery:</span>
          <span class="badge badge-verified" style="font-size:0.75rem;padding:4px 10px;">FREE EXPRESS</span>
        </div>

        <div style="border-top:2px dashed var(--border-color);padding-top:16px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:baseline;">
          <div>
            <span style="font-size:1.1rem;font-weight:900;color:#0f172a;display:block;">Grand Total:</span>
            <span style="font-size:0.75rem;color:var(--text-muted);">(Inclusive of all taxes)</span>
          </div>
          <span style="font-size:1.75rem;font-weight:900;color:var(--primary);">
            ₹<?= number_format($cart['total_amount'], 2) ?>
          </span>
        </div>

        <a href="<?= url('checkout.php') ?>" class="btn-primary-si" style="width:100%;padding:14px;font-size:1.05rem;justify-content:center;box-shadow:var(--shadow-glow);">
          Proceed to Checkout <i class="fas fa-arrow-right"></i>
        </a>

        <!-- Security & Trust Badges -->
        <div style="margin-top:22px;background:#f8fafc;border:1px solid var(--border-color);border-radius:var(--radius-md);padding:14px;font-size:0.8rem;color:var(--text-muted);display:flex;align-items:center;gap:12px;">
          <i class="fas fa-shield-alt" style="color:var(--success);font-size:1.8rem;flex-shrink:0;"></i>
          <div>
            <strong style="color:var(--text-main);">100% Buyer Protection</strong><br>
            Official brand warranty, genuine GST billing, and safe door delivery guaranteed.
          </div>
        </div>

        <div style="margin-top:10px;display:flex;justify-content:center;gap:16px;font-size:0.78rem;color:var(--text-muted);font-weight:600;">
          <span><i class="fas fa-truck" style="color:#0284c7;"></i> Same-Day Dispatch</span>
          <span>•</span>
          <span><i class="fas fa-certificate" style="color:#059669;"></i> 100% Genuine</span>
        </div>
      </div>

    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
