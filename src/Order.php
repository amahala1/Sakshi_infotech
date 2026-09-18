<?php
/**
 * Sakshi Infotech - Order Processing, Cart Lifecycle & Multi-Role Pipeline
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/HashEngine.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/Auth.php';

class Order {

    // -------------------------------------------------------------
    // Shopping Cart Operations
    // -------------------------------------------------------------

    public static function addToCart(int $productId, int $qty = 1): array {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $prod = Product::getProductById($productId);
        if (!$prod) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        $currentQty = $_SESSION['cart'][$productId] ?? 0;
        $newQty = $currentQty + $qty;

        if ($newQty > $prod['stock_qty']) {
            return ['success' => false, 'message' => "Only {$prod['stock_qty']} units available in stock."];
        }

        $_SESSION['cart'][$productId] = $newQty;
        return ['success' => true, 'cartCount' => self::getCartCount(), 'message' => "{$prod['name']} added to cart!"];
    }

    public static function updateCart(int $productId, int $qty): array {
        if ($qty <= 0) {
            return self::removeFromCart($productId);
        }

        $prod = Product::getProductById($productId);
        if (!$prod) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        if ($qty > $prod['stock_qty']) {
            return ['success' => false, 'message' => "Maximum available stock is {$prod['stock_qty']}."];
        }

        $_SESSION['cart'][$productId] = $qty;
        return ['success' => true, 'cart' => self::getCartDetails()];
    }

    public static function removeFromCart(int $productId): array {
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
        return ['success' => true, 'cart' => self::getCartDetails()];
    }

    public static function clearCart(): void {
        $_SESSION['cart'] = [];
    }

    public static function getCartCount(): int {
        if (empty($_SESSION['cart'])) return 0;
        return array_sum($_SESSION['cart']);
    }

    public static function getCartDetails(): array {
        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $subtotal = 0.0;
        $totalTax = 0.0;

        foreach ($cart as $productId => $qty) {
            $prod = Product::getProductById((int)$productId);
            if (!$prod) continue;

            $unitPrice = (float)$prod['sale_price'];
            $gstRate = (float)$prod['gst_rate'];
            $lineTotal = $unitPrice * $qty;

            // In Indian GST, sale_price can be treated as base or inclusive. Here base price calculation:
            $basePrice = $unitPrice / (1 + ($gstRate / 100));
            $taxPerUnit = $unitPrice - $basePrice;
            $lineTax = $taxPerUnit * $qty;
            $lineSubtotal = $basePrice * $qty;

            $subtotal += $lineSubtotal;
            $totalTax += $lineTax;

            $items[] = [
                'product' => $prod,
                'product_id' => $prod['id'],
                'name' => $prod['name'],
                'sku' => $prod['sku'],
                'brand' => $prod['brand'],
                'image_url' => $prod['image_url'],
                'unit_price' => $unitPrice,
                'gst_rate' => $gstRate,
                'quantity' => $qty,
                'line_total' => $lineTotal,
                'base_total' => $lineSubtotal,
                'tax_total' => $lineTax
            ];
        }

        $grandTotal = $subtotal + $totalTax;

        return [
            'items' => $items,
            'count' => count($items),
            'total_quantity' => array_sum($cart),
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($totalTax, 2),
            'total_amount' => round($grandTotal, 2)
        ];
    }

    // -------------------------------------------------------------
    // Order Placement with Cryptographic Hash Seal
    // -------------------------------------------------------------

    public static function createOrder(array $shipping, array $payment): array {
        $cart = self::getCartDetails();
        if (empty($cart['items'])) {
            return ['success' => false, 'message' => 'Your cart is empty.'];
        }

        $db = Database::getConnection();

        // 1. Resolve User (Logged in or auto-create guest customer)
        $userId = Auth::id();
        if (!$userId) {
            // Check if user exists by email
            $stmtUser = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmtUser->execute([trim($shipping['email'])]);
            $existingUser = $stmtUser->fetch();

            if ($existingUser) {
                $userId = (int)$existingUser['id'];
                // Register temporary customer account
                $guestUsername = 'cust_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $shipping['name'])) . '_' . rand(100, 999);
                $plainGuestPass = 'Sakshi@' . rand(1000, 9999);
                $guestPass = password_hash($plainGuestPass, PASSWORD_BCRYPT);
                $guestCustomerType = !empty($shipping['gstin']) ? 'company' : 'individual';
                $stmtNew = $db->prepare("
                    INSERT INTO users (
                        username, password_hash, full_name, email, phone,
                        customer_type, company_name, gst_number, email_verified,
                        address, city, state, pincode, role
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 'customer')
                ");
                $stmtNew->execute([
                    $guestUsername,
                    $guestPass,
                    trim($shipping['name']),
                    trim($shipping['email']),
                    trim($shipping['phone']),
                    $guestCustomerType,
                    $guestCustomerType === 'company' ? ($shipping['business_name'] ?? null) : null,
                    $guestCustomerType === 'company' ? ($shipping['gstin'] ?? null) : null,
                    trim($shipping['address']),
                    trim($shipping['city']),
                    trim($shipping['state']),
                    trim($shipping['pincode'])
                ]);
                $userId = (int)$db->lastInsertId();

                // Send welcome email with login ID & password
                require_once BASE_PATH . '/src/Mailer.php';
                Mailer::sendUserWelcome([
                    'id' => $userId,
                    'username' => $guestUsername,
                    'full_name' => trim($shipping['name']),
                    'email' => trim($shipping['email']),
                    'customer_type' => $guestCustomerType,
                    'company_name' => $shipping['business_name'] ?? null,
                    'gst_number' => $shipping['gstin'] ?? null
                ], $plainGuestPass);
            }
        }

        // 2. Generate Unique Order Number and Cryptographic Order Hash
        $dateStr = date('Ymd');
        $randomSeq = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $orderNumber = "SI-{$dateStr}-{$randomSeq}";
        $timestamp = date('Y-m-d H:i:s');

        $orderHash = HashEngine::generateOrderHash($orderNumber, $userId, $cart['total_amount'], $timestamp);

        // 3. Handle Payment Proof Upload (if provided)
        $proofPath = null;
        if (!empty($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf', 'webp'])) {
                $fileName = 'proof_' . $orderNumber . '_' . time() . '.' . $ext;
                $target = UPLOAD_PATH . '/' . $fileName;
                if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target)) {
                    $proofPath = 'uploads/' . $fileName;
                }
            }
        }

        // 4. Insert Master Order
        $stmtOrder = $db->prepare("
            INSERT INTO orders (
                order_number, order_hash, user_id, customer_name, customer_email, customer_phone,
                shipping_address, shipping_city, shipping_state, shipping_pincode, gstin, business_name,
                subtotal, tax_amount, total_amount, payment_method, payment_status, payment_ref,
                payment_proof_file, order_status, customer_notes, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, 'pending', ?,
                ?, 'placed', ?, ?
            )
        ");

        $stmtOrder->execute([
            $orderNumber,
            $orderHash,
            $userId,
            trim($shipping['name']),
            trim($shipping['email']),
            trim($shipping['phone']),
            trim($shipping['address']),
            trim($shipping['city']),
            trim($shipping['state']),
            trim($shipping['pincode']),
            trim($shipping['gstin'] ?? ''),
            trim($shipping['business_name'] ?? ''),
            $cart['subtotal'],
            $cart['tax_amount'],
            $cart['total_amount'],
            $payment['method'] ?? 'upi_qr',
            trim($payment['ref_no'] ?? ''),
            $proofPath,
            trim($shipping['notes'] ?? ''),
            $timestamp
        ]);

        $orderId = (int)$db->lastInsertId();

        // 5. Insert Items & compute Item Seal Hashes & update inventory stock
        $stmtItem = $db->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, sku, hsn_code, unit_price, gst_rate, quantity, total_price, item_seal_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtStock = $db->prepare("UPDATE products SET stock_qty = MAX(0, stock_qty - ?) WHERE id = ?");

        foreach ($cart['items'] as $item) {
            $itemSealHash = HashEngine::generateItemSealHash($item['sku'], $item['quantity'], $item['unit_price']);
            $stmtItem->execute([
                $orderId,
                $item['product_id'],
                $item['name'],
                $item['sku'],
                $item['product']['hsn_code'] ?? '8471',
                $item['unit_price'],
                $item['gst_rate'],
                $item['quantity'],
                $item['line_total'],
                $itemSealHash
            ]);

            // Deduct stock
            $stmtStock->execute([$item['quantity'], $item['product_id']]);
        }

        // 6. Record Audit Log
        Auth::logAudit('order', $orderId, 'ORDER_PLACED', $userId, $shipping['name'], 'customer', "Order {$orderNumber} placed for ₹{$cart['total_amount']}. Hash: {$orderHash}");

        // 7. Clear Cart
        self::clearCart();

        // 8. Dispatch Order Confirmation Transactional Email
        $createdOrder = self::getOrderById($orderId);
        if ($createdOrder) {
            require_once BASE_PATH . '/src/Mailer.php';
            Mailer::sendOrderPlaced($createdOrder, $cart['items']);
        }

        return [
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'order_hash' => $orderHash,
            'total_amount' => $cart['total_amount']
        ];
    }

    // -------------------------------------------------------------
    // Multi-Role Staff Pipeline Methods
    // -------------------------------------------------------------

    /**
     * ACCOUNTS ROLE: Verify Payment proof / UPI Ref and seal with Payment Hash
     */
    public static function verifyPayment(int $orderId, int $staffId, string $status, ?string $notes = null): array {
        $db = Database::getConnection();
        $order = self::getOrderById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $timestamp = date('Y-m-d H:i:s');
        $paymentHash = null;
        $orderStatus = $order['order_status'];

        if ($status === 'verified') {
            $paymentHash = HashEngine::generatePaymentHash(
                $order['order_number'],
                $order['payment_ref'] ?: 'UPI-DIRECT',
                (float)$order['total_amount'],
                $staffId,
                $timestamp
            );
            $orderStatus = 'payment_verified';
        } else {
            $status = 'rejected';
        }

        $stmt = $db->prepare("
            UPDATE orders SET
                payment_status = ?,
                payment_hash = ?,
                payment_verified_by = ?,
                payment_verified_at = ?,
                order_status = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $paymentHash, $staffId, $timestamp, $orderStatus, $orderId]);

        $staff = Auth::user();
        Auth::logAudit('order', $orderId, 'PAYMENT_' . strtoupper($status), $staffId, $staff['full_name'] ?? 'Staff', 'staff_accounts', "Payment marked as {$status}. Payment Seal Hash: {$paymentHash}");

        // Dispatch Payment Received Transactional Email
        if ($status === 'verified') {
            $updatedOrder = self::getOrderById($orderId);
            if ($updatedOrder) {
                require_once BASE_PATH . '/src/Mailer.php';
                Mailer::sendPaymentReceived($updatedOrder);
            }
        }

        return ['success' => true, 'payment_status' => $status, 'payment_hash' => $paymentHash];
    }

    /**
     * ORDER CHECKER ROLE: Verify all packed items against physical stock
     */
    public static function checkOrder(int $orderId, int $staffId, ?string $notes = null): array {
        $db = Database::getConnection();
        $order = self::getOrderById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $timestamp = date('Y-m-d H:i:s');
        $stmt = $db->prepare("
            UPDATE orders SET
                checked_by = ?,
                checked_at = ?,
                order_status = 'checked'
            WHERE id = ?
        ");
        $stmt->execute([$staffId, $timestamp, $orderId]);

        $staff = Auth::user();
        Auth::logAudit('order', $orderId, 'ORDER_CHECKED', $staffId, $staff['full_name'] ?? 'Staff', 'staff_checker', "Order {$order['order_number']} verified and packed by staff.");

        // Dispatch Order Packed Notification Email
        $packedOrder = self::getOrderById($orderId);
        if ($packedOrder) {
            require_once BASE_PATH . '/src/Mailer.php';
            Mailer::sendOrderPacked($packedOrder);
        }

        return ['success' => true, 'order_status' => 'checked'];
    }

    /**
     * DISPATCH ROLE: Attach Courier details, generate Dispatch Hash, mark Dispatched
     */
    public static function dispatchOrder(int $orderId, int $staffId, string $courier, string $trackingNo): array {
        $db = Database::getConnection();
        $order = self::getOrderById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $timestamp = date('Y-m-d H:i:s');
        $dispatchHash = HashEngine::generateDispatchHash($order['order_number'], $courier, $trackingNo, $staffId, $timestamp);

        $stmt = $db->prepare("
            UPDATE orders SET
                courier_name = ?,
                tracking_number = ?,
                dispatch_hash = ?,
                dispatched_by = ?,
                dispatched_at = ?,
                order_status = 'dispatched'
            WHERE id = ?
        ");
        $stmt->execute([trim($courier), trim($trackingNo), $dispatchHash, $staffId, $timestamp, $orderId]);

        $staff = Auth::user();
        Auth::logAudit('order', $orderId, 'ORDER_DISPATCHED', $staffId, $staff['full_name'] ?? 'Staff', 'staff_dispatch', "Dispatched via {$courier} (AWB: {$trackingNo}). Dispatch Hash: {$dispatchHash}");

        // Dispatch Order Dispatched Notification Email
        $dispatchedOrder = self::getOrderById($orderId);
        if ($dispatchedOrder) {
            require_once BASE_PATH . '/src/Mailer.php';
            Mailer::sendOrderDispatched($dispatchedOrder, trim($courier), trim($trackingNo));
        }

        return ['success' => true, 'dispatch_hash' => $dispatchHash, 'order_status' => 'dispatched'];
    }

    /**
     * Mark Order as Delivered
     */
    public static function markDelivered(int $orderId, int $staffId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE orders SET order_status = 'delivered' WHERE id = ?");
        $stmt->execute([$orderId]);

        $staff = Auth::user();
        Auth::logAudit('order', $orderId, 'ORDER_DELIVERED', $staffId, $staff['full_name'] ?? 'Staff', 'staff_dispatch', "Order marked as Delivered to customer.");

        // Dispatch Order Delivered Notification Email
        $deliveredOrder = self::getOrderById($orderId);
        if ($deliveredOrder) {
            require_once BASE_PATH . '/src/Mailer.php';
            Mailer::sendOrderDelivered($deliveredOrder);
        }

        return ['success' => true, 'order_status' => 'delivered'];
    }

    // -------------------------------------------------------------
    // Order Fetch Queries
    // -------------------------------------------------------------

    public static function getOrderById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT o.*, 
                   u.username as customer_username,
                   acc.full_name as accounts_staff_name,
                   chk.full_name as checker_staff_name,
                   dsp.full_name as dispatch_staff_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN users acc ON o.payment_verified_by = acc.id
            LEFT JOIN users chk ON o.checked_by = chk.id
            LEFT JOIN users dsp ON o.dispatched_by = dsp.id
            WHERE o.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) return null;

        // Fetch items
        $stmtItems = $db->prepare("
            SELECT oi.*, p.image_url
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmtItems->execute([$id]);
        $order['items'] = $stmtItems->fetchAll();

        return $order;
    }

    public static function getOrderByHash(string $hash): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM orders WHERE order_hash = ? OR payment_hash = ? OR dispatch_hash = ? LIMIT 1");
        $stmt->execute([$hash, $hash, $hash]);
        $res = $stmt->fetch();
        return $res ? self::getOrderById((int)$res['id']) : null;
    }

    public static function getOrdersByUserId(int $userId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM orders 
            WHERE user_id = ? 
            ORDER BY id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function getAllOrders(array $filters = []): array {
        $db = Database::getConnection();
        $sql = "
            SELECT o.*, u.username as customer_username,
                   acc.full_name as accounts_staff_name,
                   chk.full_name as checker_staff_name,
                   dsp.full_name as dispatch_staff_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN users acc ON o.payment_verified_by = acc.id
            LEFT JOIN users chk ON o.checked_by = chk.id
            LEFT JOIN users dsp ON o.dispatched_by = dsp.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND o.order_status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['payment_status'])) {
            $sql .= " AND o.payment_status = :payment_status";
            $params[':payment_status'] = $filters['payment_status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE :search OR o.customer_name LIKE :search OR o.customer_phone LIKE :search OR o.order_hash LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY o.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
