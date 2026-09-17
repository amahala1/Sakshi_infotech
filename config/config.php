<?php
/**
 * Sakshi Infotech - Global Configuration & Subfolder Resilient Path Helpers
 */

// Application Info
define('APP_NAME', 'Sakshi Infotech');
define('APP_TAGLINE', 'Complete Computer Hardware, Peripherals & Stationery Solutions');
define('APP_PHONE', '+91 97728 24888');
define('APP_EMAIL', 'info@sitindia.in');
define('APP_ADDRESS', '48, Near Prabhat Peradise, Harnathpura Jhotwara Jaipur-302012');
define('APP_GSTIN', '08APSPA4456M2ZC');
define('APP_CURRENCY', '₹');
define('APP_LOGO', 'images/logo.png');

// Security & Cryptographic Hash Secret
define('HASH_SECRET_KEY', 'SakshiInfotech_SecureHMAC_Key_2026_x89f#@!');

// Database Credentials (MySQL Primary)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'sakshi_infotech');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// Payment Options
define('UPI_ID', 'sakshiinfotech@icici');
define('UPI_NAME', 'Sakshi Infotech');
define('BANK_NAME', 'State Bank of India');
define('BANK_ACCOUNT', '389201948201');
define('BANK_IFSC', 'SBIN0004521');
define('BANK_BRANCH', 'Jhotwara Branch, Jaipur');

// Path helpers
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0777, true);
}

// -------------------------------------------------------------
// Dynamic Subfolder URL Resolver (Supports '/sales/' or root '/')
// -------------------------------------------------------------
function get_base_url(): string {
    if (!isset($_SERVER['SCRIPT_NAME'])) {
        return '/';
    }
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Normalize admin subfolder
    if (substr($scriptDir, -6) === '/admin') {
        $scriptDir = substr($scriptDir, 0, -6);
    }
    $base = rtrim($scriptDir, '/');
    return ($base === '' ? '' : $base) . '/';
}

function url(string $path = ''): string {
    $base = get_base_url();
    return $base . ltrim($path, '/');
}

function asset(string $path = ''): string {
    return url($path);
}

function product_image_url(?string $filename): string {
    if (empty($filename)) {
        return asset('assets/images/laptop-lenovo.webp');
    }
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    // Check if webp exists
    $webpPath = PUBLIC_PATH . '/assets/images/' . $baseName . '.webp';
    if (file_exists($webpPath)) {
        return asset('assets/images/' . $baseName . '.webp');
    }
    // Check if png exists
    $pngPath = PUBLIC_PATH . '/assets/images/' . $baseName . '.png';
    if (file_exists($pngPath)) {
        return asset('assets/images/' . $baseName . '.png');
    }
    return asset('assets/images/' . $filename);
}

// -------------------------------------------------------------
// Universal WebP Image Converter (Supports JPEG, PNG, GIF, BMP)
// -------------------------------------------------------------
function convertToWebP(string $sourcePath, string $targetPath, int $quality = 85): bool {
    try {
        if (!file_exists($sourcePath)) {
            return false;
        }

        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            // If GD is unavailable, copy file as fallback
            return @copy($sourcePath, $targetPath);
        }

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return @copy($sourcePath, $targetPath);
        }

        $mime = $info['mime'];
        $image = null;

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($sourcePath);
                if ($image) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
            case 'image/webp':
                // Already webp, just move/copy
                return @copy($sourcePath, $targetPath);
            case 'image/gif':
                $image = @imagecreatefromgif($sourcePath);
                break;
            case 'image/bmp':
                $image = @imagecreatefrombmp($sourcePath);
                break;
            default:
                return @copy($sourcePath, $targetPath);
        }

        if (!$image) {
            return @copy($sourcePath, $targetPath);
        }

        $res = @imagewebp($image, $targetPath, $quality);
        @imagedestroy($image);
        return $res;
    } catch (\Throwable $e) {
        error_log("convertToWebP error: " . $e->getMessage());
        return @copy($sourcePath, $targetPath);
    }
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
