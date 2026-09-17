<?php
/**
 * Sakshi Infotech - Robust, Crash-Proof Cryptographic Hash & Verification Engine
 * 100% resilient with comprehensive try-catch wrappers, type coercion, and safe fallbacks.
 */

require_once dirname(__DIR__) . '/config/config.php';

class HashEngine {

    /**
     * Safe secret key getter
     */
    private static function getSecretKey(): string {
        return defined('HASH_SECRET_KEY') && !empty(HASH_SECRET_KEY) 
            ? HASH_SECRET_KEY 
            : 'SakshiInfotech_Default_SecureKey_2026_!@#';
    }

    /**
     * Generate an HMAC-SHA256 signature for any raw string (Crash-Proof)
     */
    public static function generateIntegrityHash($data): string {
        try {
            $str = is_scalar($data) ? trim((string)$data) : json_encode($data);
            if (empty($str)) {
                $str = 'SI_EMPTY_PAYLOAD_' . microtime(true);
            }
            return hash_hmac('sha256', $str, self::getSecretKey());
        } catch (\Throwable $e) {
            error_log("HashEngine::generateIntegrityHash warning: " . $e->getMessage());
            return hash('sha256', 'FALLBACK_' . microtime(true) . '_' . rand(1000, 9999));
        }
    }

    /**
     * Generate unique Order Verification Hash (Crash-Proof)
     */
    public static function generateOrderHash($orderNumber, $userId, $totalAmount, $timestamp = null): string {
        try {
            $ord = !empty($orderNumber) ? trim((string)$orderNumber) : ('SI-' . date('Ymd') . '-' . rand(1000, 9999));
            $uid = (int)($userId ?? 0);
            $amt = is_numeric($totalAmount) ? (float)$totalAmount : 0.0;
            $ts = !empty($timestamp) ? (string)$timestamp : date('Y-m-d H:i:s');

            $payload = "ORDER|{$ord}|USER:{$uid}|AMT:" . number_format($amt, 2, '.', '') . "|TS:{$ts}";
            return hash_hmac('sha256', $payload, self::getSecretKey());
        } catch (\Throwable $e) {
            error_log("HashEngine::generateOrderHash warning: " . $e->getMessage());
            return hash('sha256', 'ORDER_FAILOVER_' . microtime(true));
        }
    }

    /**
     * Generate Payment Verification Hash - Sealed by Accounts Staff (Crash-Proof)
     */
    public static function generatePaymentHash($orderNumber, $paymentRef, $amount, $verifiedByStaffId, $timestamp = null): string {
        try {
            $ord = !empty($orderNumber) ? trim((string)$orderNumber) : 'UNKNOWN_ORDER';
            $ref = !empty($paymentRef) ? trim((string)$paymentRef) : 'DIRECT_VERIFIED';
            $amt = is_numeric($amount) ? (float)$amount : 0.0;
            $staff = (int)($verifiedByStaffId ?? 0);
            $ts = !empty($timestamp) ? (string)$timestamp : date('Y-m-d H:i:s');

            $payload = "PAYMENT|{$ord}|REF:{$ref}|AMT:" . number_format($amt, 2, '.', '') . "|VERIFIED_BY:{$staff}|TS:{$ts}";
            return hash_hmac('sha256', $payload, self::getSecretKey());
        } catch (\Throwable $e) {
            error_log("HashEngine::generatePaymentHash warning: " . $e->getMessage());
            return hash('sha256', 'PAYMENT_FAILOVER_' . microtime(true));
        }
    }

    /**
     * Generate Dispatch & Shipment Hash - Sealed by Dispatch Staff (Crash-Proof)
     */
    public static function generateDispatchHash($orderNumber, $courier, $trackingNo, $dispatchedByStaffId, $timestamp = null): string {
        try {
            $ord = !empty($orderNumber) ? trim((string)$orderNumber) : 'UNKNOWN_ORDER';
            $c = !empty($courier) ? trim((string)$courier) : 'STANDARD_LOGISTICS';
            $t = !empty($trackingNo) ? trim((string)$trackingNo) : 'AWB_PENDING';
            $staff = (int)($dispatchedByStaffId ?? 0);
            $ts = !empty($timestamp) ? (string)$timestamp : date('Y-m-d H:i:s');

            $payload = "DISPATCH|{$ord}|COURIER:{$c}|AWB:{$t}|DISPATCHED_BY:{$staff}|TS:{$ts}";
            return hash_hmac('sha256', $payload, self::getSecretKey());
        } catch (\Throwable $e) {
            error_log("HashEngine::generateDispatchHash warning: " . $e->getMessage());
            return hash('sha256', 'DISPATCH_FAILOVER_' . microtime(true));
        }
    }

    /**
     * Generate Item Seal Hash (Crash-Proof)
     */
    public static function generateItemSealHash($sku, $qty, $unitPrice): string {
        try {
            $s = !empty($sku) ? trim((string)$sku) : 'ITEM_SKU';
            $q = (int)($qty ?? 1);
            $p = is_numeric($unitPrice) ? (float)$unitPrice : 0.0;

            $payload = "ITEM|{$s}|QTY:{$q}|PRICE:" . number_format($p, 2, '.', '');
            return hash_hmac('sha256', $payload, self::getSecretKey());
        } catch (\Throwable $e) {
            error_log("HashEngine::generateItemSealHash warning: " . $e->getMessage());
            return hash('sha256', 'ITEM_FAILOVER_' . microtime(true));
        }
    }

    /**
     * Lookup any record across the system by its cryptographic Hash (100% Crash-Proof)
     */
    public static function lookupHash(?PDO $db, $searchHash): array {
        try {
            if (!$db) {
                return ['success' => false, 'message' => 'Database connection unavailable.'];
            }

            $cleanHash = trim((string)$searchHash);
            if (empty($cleanHash) || strlen($cleanHash) < 8) {
                return ['success' => false, 'message' => 'Invalid or empty hash signature provided.'];
            }

            // 1. Check Orders
            try {
                $stmtOrder = $db->prepare("
                    SELECT o.*, u.full_name as customer_real_name, u.email as user_email
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    WHERE o.order_hash = :h1 OR o.payment_hash = :h2 OR o.dispatch_hash = :h3
                    LIMIT 1
                ");
                $stmtOrder->execute([':h1' => $cleanHash, ':h2' => $cleanHash, ':h3' => $cleanHash]);
                $order = $stmtOrder->fetch();

                if ($order) {
                    $matchType = 'Order Certificate';
                    if (isset($order['payment_hash']) && $order['payment_hash'] === $cleanHash) {
                        $matchType = 'Accounts Payment Seal';
                    } elseif (isset($order['dispatch_hash']) && $order['dispatch_hash'] === $cleanHash) {
                        $matchType = 'Dispatch & Tracking Seal';
                    }

                    return [
                        'success' => true,
                        'type' => 'order',
                        'title' => $matchType,
                        'hash' => $cleanHash,
                        'record_id' => $order['order_number'] ?? ('Order #' . ($order['id'] ?? '')),
                        'verified' => true,
                        'data' => $order
                    ];
                }
            } catch (\Throwable $exOrd) {
                error_log("HashEngine order lookup warning: " . $exOrd->getMessage());
            }

            // 2. Check Products
            try {
                $stmtProd = $db->prepare("
                    SELECT p.*, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.data_integrity_hash = ?
                    LIMIT 1
                ");
                $stmtProd->execute([$cleanHash]);
                $prod = $stmtProd->fetch();
                if ($prod) {
                    return [
                        'success' => true,
                        'type' => 'product',
                        'title' => 'Product Authenticity Seal',
                        'hash' => $cleanHash,
                        'record_id' => $prod['sku'] ?? ('Product #' . ($prod['id'] ?? '')),
                        'verified' => true,
                        'data' => $prod
                    ];
                }
            } catch (\Throwable $exProd) {
                error_log("HashEngine product lookup warning: " . $exProd->getMessage());
            }

            // 3. Check Audit Logs
            try {
                $stmtAudit = $db->prepare("SELECT * FROM audit_logs WHERE record_hash = ? LIMIT 1");
                $stmtAudit->execute([$cleanHash]);
                $log = $stmtAudit->fetch();
                if ($log) {
                    return [
                        'success' => true,
                        'type' => 'audit',
                        'title' => 'System Audit Record Seal',
                        'hash' => $cleanHash,
                        'record_id' => 'LOG-' . ($log['id'] ?? ''),
                        'verified' => true,
                        'data' => $log
                    ];
                }
            } catch (\Throwable $exAudit) {
                error_log("HashEngine audit lookup warning: " . $exAudit->getMessage());
            }

            return [
                'success' => false,
                'message' => 'No database record matches this cryptographic hash signature.'
            ];

        } catch (\Throwable $e) {
            error_log("HashEngine::lookupHash general failure: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Verification engine encountered a handled exception. The signature could not be verified at this time.'
            ];
        }
    }

    /**
     * Convenience auto-connecting record verifier (Crash-Proof)
     */
    public static function verifyRecordByHash($searchHash): array {
        try {
            require_once dirname(__DIR__) . '/config/database.php';
            $db = Database::getConnection();
            return self::lookupHash($db, $searchHash);
        } catch (\Throwable $e) {
            error_log("HashEngine::verifyRecordByHash error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Verification failed safely: ' . $e->getMessage()];
        }
    }
}
