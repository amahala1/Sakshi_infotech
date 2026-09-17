-- Sakshi Infotech - Full MySQL Database Schema & Seed Data
-- Designed for Computer Hardware, Peripherals & Stationery Store E-Commerce
-- Features: Cryptographic HMAC SHA-256 Hash Verification & Role Based Staff Operations

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `sakshi_infotech` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sakshi_infotech`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `pincode` VARCHAR(10) DEFAULT NULL,
  `role` ENUM('admin', 'staff_accounts', 'staff_checker', 'staff_dispatch', 'customer') DEFAULT 'customer',
  `status` VARCHAR(30) DEFAULT 'active',
  `auth_token_hash` VARCHAR(64) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping default users (Pass: admin123, accounts123, checker123, dispatch123, customer123)
-- --------------------------------------------------------
INSERT INTO `users` (`username`, `password_hash`, `full_name`, `email`, `phone`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sakshi Admin', 'admin@sakshiinfotech.com', '9829012345', 'admin'),
('staff_acc', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ramesh Sharma (Accounts)', 'accounts@sakshiinfotech.com', '9829011111', 'staff_accounts'),
('staff_chk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sunil Meena (Order Checker)', 'checker@sakshiinfotech.com', '9829022222', 'staff_checker'),
('staff_dsp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Vikas Verma (Dispatch)', 'dispatch@sakshiinfotech.com', '9829033333', 'staff_dispatch'),
('customer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gaurav Joshi', 'gaurav@example.com', '9829044444', 'customer');

-- --------------------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) UNIQUE NOT NULL,
  `type` ENUM('computer', 'stationery') DEFAULT 'computer',
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'fa-folder',
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `type`, `description`, `icon`) VALUES
(1, 'Laptops & Desktops', 'laptops-desktops', 'computer', 'High performance Business & Gaming laptops, customized desktops', 'fa-laptop'),
(2, 'Keyboards & Mice', 'keyboards-mice', 'computer', 'Mechanical keyboards, wireless combos, ergonomic mice', 'fa-keyboard'),
(3, 'Printers & Scanners', 'printers-scanners', 'computer', 'Laser printers, all-in-one inkjet, barcode scanners', 'fa-print'),
(4, 'Storage & Memory (SSD/RAM)', 'storage-memory', 'computer', 'NVMe M.2 SSDs, external hard drives, DDR4/DDR5 RAM', 'fa-hdd'),
(5, 'Networking & Cables', 'networking-cables', 'computer', 'Wi-Fi 6 Routers, CAT6 LAN cables, HDMI & Type-C adapters', 'fa-network-wired'),
(6, 'Printing & Copier Paper', 'paper-supplies', 'stationery', 'JK Copier A4/Legal 75/80 GSM, photo glossy sheets', 'fa-copy'),
(7, 'Toners & Cartridges', 'toners-cartridges', 'stationery', 'LaserJet toners, ink bottles, cartridge refills', 'fa-fill-drip'),
(8, 'Office Registers & Files', 'registers-files', 'stationery', 'Account registers, lever arch files, cobra files, spiral pads', 'fa-book'),
(9, 'Pens, Markers & Desktops', 'pens-markers', 'stationery', 'Gel pens, whiteboard markers, staplers, calculators, punches', 'fa-pen-fancy');

-- --------------------------------------------------------
-- Table structure for table `subcategories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `subcategories`;
CREATE TABLE `subcategories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) UNIQUE NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subcategories` (`id`, `category_id`, `name`, `slug`, `description`) VALUES
(1, 1, 'Business & Commercial Laptops', 'business-laptops', 'Lenovo ThinkPad, HP ProBook, Dell Latitude laptops'),
(2, 1, 'Mini & Desktop PCs', 'mini-desktop-pcs', 'Compact desktop mini PCs, commercial workstations'),
(3, 1, 'Gaming & Creator Laptops', 'gaming-laptops', 'High performance dedicated GPU laptops'),
(4, 2, 'Wireless Keyboard & Mouse Combos', 'wireless-combos', 'Silent wireless office keyboard and optical mouse sets'),
(5, 2, 'Gaming Keyboards & RGB Mice', 'gaming-keyboards-mice', 'Mechanical switches, backlit LED gaming combos'),
(6, 2, 'Ergonomic & Wired Accessories', 'ergonomic-wired', 'Standard USB keyboards, trackballs, numeric pads'),
(7, 3, 'Single-Function Laser Printers', 'laser-printers', 'Monochrome high-speed Wi-Fi laser printers for office bills'),
(8, 3, 'All-in-One Ink Tank Printers', 'ink-tank-printers', 'Colour print, scan, copy with high yield ink bottles'),
(9, 3, 'Barcode & POS Scanners', 'barcode-pos-scanners', '1D/2D QR code retail barcode handheld scanners'),
(10, 4, 'NVMe M.2 Solid State Drives', 'nvme-m2-ssd', 'PCIe Gen4 Gen3 high-speed SSD storage'),
(11, 4, 'Desktop & Laptop RAM', 'ddr4-ddr5-ram', 'DDR4 & DDR5 performance RAM memory modules'),
(12, 4, 'External Portable Hard Drives', 'external-hdd', 'USB 3.0 backup hard disks and flash drives'),
(13, 5, 'Wi-Fi 6 Routers & Access Points', 'wifi-6-routers', 'Gigabit dual band wireless routers'),
(14, 5, 'Ethernet LAN Cables (CAT6)', 'ethernet-lan-cables', 'High speed RJ45 networking patch cords & rolls'),
(15, 5, 'Display & Power Adapters', 'display-power-adapters', 'HDMI, VGA, Type-C hubs and laptop chargers'),
(16, 6, 'A4 Copier Paper (75/80 GSM)', 'a4-copier-paper', 'JK Copier 75 GSM & 80 GSM reams of 500 sheets'),
(17, 6, 'Wholesale B2B Paper Cartons', 'wholesale-paper-cartons', 'Bulk cartons of 5 reams / 2500 sheets for institutions'),
(18, 6, 'Legal & Photo Glossy Paper', 'legal-photo-paper', 'FS Legal court fee paper and photo paper'),
(19, 7, 'LaserJet Compatible Toners', 'laserjet-compatible-toners', '12A, 88A, 78A, 05A black laser toner cartridges'),
(20, 7, 'Original Ink Bottles & Refills', 'ink-bottles-refills', 'Canon GI-790, HP GT52/GT53, Epson 003 ink bottles'),
(21, 7, 'Refill Toner Powder & OPC Drums', 'toner-powder-drums', 'Bulk toner powder bottles and printer spare drums'),
(22, 8, 'Hard Bound Account Registers', 'account-registers', '200/400 page ledger, cash book, attendance registers'),
(23, 8, 'Lever Arch Box Files', 'lever-arch-files', 'Heavy duty Kangaro & Solo 75mm box files with ring binder'),
(24, 8, 'Cobra & Document Folders', 'cobra-document-folders', 'Spring cobra files, button folders, conference files'),
(25, 9, 'Commercial Desk Calculators', 'desk-calculators', 'Casio MJ-120D, 12-digit check & correct GST calculators'),
(26, 9, 'Ball & Gel Pen Boxes', 'ball-gel-pens', 'Cello Butterflow, Reynolds, Pentonic pen packs'),
(27, 9, 'Whiteboard Markers & Highlighters', 'markers-highlighters', 'Camlin whiteboard markers, Faber Castell highlighters');

-- --------------------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `subcategory_id` INT DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) UNIQUE NOT NULL,
  `hsn_code` VARCHAR(20) DEFAULT '8471',
  `brand` VARCHAR(100) DEFAULT NULL,
  `regular_price` DECIMAL(10,2) NOT NULL,
  `sale_price` DECIMAL(10,2) NOT NULL,
  `gst_rate` DECIMAL(5,2) DEFAULT 18.00,
  `stock_qty` INT DEFAULT 10,
  `min_stock_alert` INT DEFAULT 3,
  `description` TEXT DEFAULT NULL,
  `specifications` TEXT DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `gallery_images` TEXT DEFAULT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `data_integrity_hash` VARCHAR(64) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `category_id`, `name`, `sku`, `hsn_code`, `brand`, `regular_price`, `sale_price`, `gst_rate`, `stock_qty`, `min_stock_alert`, `description`, `specifications`, `image_url`, `is_featured`, `data_integrity_hash`) VALUES
(1, 1, 'Lenovo ThinkPad E14 Intel Core i5 13th Gen', 'COMP-LTP-001', '8471', 'Lenovo', 68900.00, 59990.00, 18.00, 8, 2, 'Reliable business laptop with 16GB DDR5 RAM, 512GB NVMe SSD, backlit keyboard, FHD display, Fingerprint reader.', '{\"RAM\":\"16GB DDR5\",\"Storage\":\"512GB NVMe\",\"Processor\":\"Intel Core i5-1335U\",\"Display\":\"14-inch IPS FHD\",\"OS\":\"Windows 11 Pro\"}', 'laptop-lenovo.png', 1, '24fbc908c6f784efb9a6747b9e0787e914df5944111be17ebfcbb7ea8e5399ba'),
(2, 1, 'HP ProDesk 400 G9 Mini Desktop PC', 'COMP-DSK-002', '8471', 'HP', 52500.00, 46990.00, 18.00, 6, 2, 'Compact commercial desktop PC with Intel Core i5 12th Gen, 16GB RAM, 512GB SSD, Wi-Fi 6, Dual DisplayPort.', '{\"Form Factor\":\"Mini PC\",\"RAM\":\"16GB\",\"Storage\":\"512GB SSD\",\"OS\":\"Windows 11 Home\"}', 'hp-mini-pc.png', 1, '9cba75d5ff240c5f242aa155c0a0c98f80cb52be6ff342faee1d149022648719'),
(3, 2, 'Logitech MK295 Silent Wireless Keyboard & Mouse Combo', 'COMP-KB-003', '8471', 'Logitech', 2495.00, 1999.00, 18.00, 25, 5, 'SilentTouch technology reduces 90% typing noise. Full-size keyboard with 8 shortcut keys and contoured wireless mouse.', '{\"Connectivity\":\"2.4GHz Wireless Nano Receiver\",\"Battery Life\":\"36 Months (Keyboard) / 18 Months (Mouse)\",\"Color\":\"Graphite Black\"}', 'logitech-mk295.png', 1, '57e793ba64155b46d0370d65b750131109a96e6a12b7f04ef7fdb0e3e571c4c1'),
(4, 2, 'Zebronics Transformer RGB Gaming Keyboard & Mouse', 'COMP-KB-004', '8471', 'Zebronics', 1599.00, 1149.00, 18.00, 30, 5, 'Aluminium body, multi-color LED backlit effects, braided cable, high precision optical gaming mouse up to 3200 DPI.', '{\"Interface\":\"USB Gold Plated\",\"Keys\":\"104 Keys\",\"Cable\":\"Braided 1.8M\"}', 'zebronics-rgb.png', 0, '1d62c2f216da197825bcf8e0c8b030b429d4791244585c54c330dfd7f6ef63b6'),
(5, 3, 'HP Laser 1008w Single Function Wireless Laser Printer', 'COMP-PRN-005', '8443', 'HP', 14500.00, 12890.00, 18.00, 12, 3, 'High speed crisp black-and-white printing up to 21 ppm. Built-in Wi-Fi & HP Smart App mobile printing support.', '{\"Print Speed\":\"21 ppm\",\"Duty Cycle\":\"Up to 10,000 pages\",\"Connectivity\":\"Wi-Fi, USB 2.0\"}', 'hp-laser-printer.png', 1, 'f5d1ba1f94d93510526eec1f0cf8516d2b67f1b204e9c704f434dfad80d68bb4'),
(6, 3, 'Canon PIXMA G3010 All-in-One Wireless Ink Tank Printer', 'COMP-PRN-006', '8443', 'Canon', 16999.00, 14490.00, 18.00, 10, 2, 'High-volume colour printing, scanning, and copying. Cost-effective ink bottles yielding up to 7000 colour pages.', '{\"Type\":\"Ink Tank All-in-One\",\"Resolution\":\"4800x1200 dpi\",\"Wi-Fi Direct\":\"Yes\"}', 'canon-g3010.png', 1, 'aa89cf32a45053cf258d4aef99f4d1eefd79e6ca6fbf5eeb2b77e2ff12781b0a'),
(7, 4, 'Crucial P3 Plus 1TB PCIe 4.0 3D NAND NVMe M.2 SSD', 'COMP-SSD-007', '8471', 'Crucial', 7500.00, 5690.00, 18.00, 40, 5, 'Blazing fast sequential read speeds up to 5000 MB/s. Perfect for high performance laptops, workstations & gaming.', '{\"Capacity\":\"1TB\",\"Interface\":\"PCIe Gen4 x4 NVMe\",\"Read Speed\":\"5000 MB/s\",\"Form Factor\":\"M.2 2280\"}', 'crucial-nvme-1tb.png', 1, '33de7aa265c0ecb39170e17c0c16922df6ee9c3d4c721b0339d67b2d5a3ea7f4'),
(8, 4, 'Kingston Fury Beast 16GB DDR4 3200MHz Desktop RAM', 'COMP-RAM-008', '8471', 'Kingston', 3800.00, 2890.00, 18.00, 35, 5, 'Cost-effective high-performance upgrade with stylish low-profile heat spreader. Intel XMP and AMD Ryzen ready.', '{\"Capacity\":\"16GB (1x16GB)\",\"Frequency\":\"3200MHz\",\"Latency\":\"CL16\"}', 'kingston-fury-ram.png', 0, '67a7ba416ee979b986eeef939fa9cb601445778848db29ee7bb033bc6f030cc4'),
(9, 5, 'TP-Link Archer AX12 AX1500 Dual Band Wi-Fi 6 Router', 'COMP-NET-009', '8517', 'TP-Link', 3999.00, 2799.00, 18.00, 20, 4, 'Next-gen Wi-Fi 6 speeds up to 1.5 Gbps (1201 Mbps on 5 GHz and 300 Mbps on 2.4 GHz). 4 Gigabit Ethernet ports.', '{\"Standards\":\"Wi-Fi 6 IEEE 802.11ax\",\"Antennas\":\"4 High-Gain\",\"Gigabit Ports\":\"4\"}', 'tplink-ax12.png', 1, '76b5d0bf93ffce7b2ba07f7be95bb4fba99052d9a69ee730bb39b71a28a31818'),
(10, 6, 'JK Copier A4 Paper 75 GSM - Ream of 500 Sheets', 'STAT-PPR-010', '4802', 'JK Paper', 380.00, 310.00, 12.00, 150, 20, 'Premium quality copier paper with ColorLok technology. Smear-resistant, high brightness 90%, smooth surface for jam-free printing.', '{\"Size\":\"A4 (210x297 mm)\",\"GSM\":\"75 GSM\",\"Sheets\":\"500 Sheets\",\"Brightness\":\"90%\"}', 'jk-copier-a4.png', 1, '119a0f44ec74a985d8aa61d8ea8f35212ec828abfe4a11f2679ad3061da2d01e'),
(11, 6, 'B2B Carton - JK Copier A4 (Box of 5 Reams / 2500 Sheets)', 'STAT-PPR-011', '4802', 'JK Paper', 1900.00, 1499.00, 12.00, 50, 10, 'Wholesale bulk pack of 5 reams. Best for offices, schools, cyber cafes and computer centers.', '{\"Pack Type\":\"Carton Box\",\"Total Sheets\":\"2500 Sheets\",\"Weight\":\"11.5 Kg\"}', 'jk-copier-box.png', 1, '70b20efc1966a3371a3962e74ec8ce4d59a8c148bb66c40e0be21f37ff7b068a'),
(12, 7, 'ProDot 12A / Q2612A Compatible Laser Toner Cartridge', 'STAT-TNR-012', '8443', 'ProDot', 1200.00, 690.00, 18.00, 45, 10, 'High yield replacement toner for HP LaserJet 1010, 1020, M1005, Canon LBP 2900B. Crystal dark prints up to 2000 pages.', '{\"Page Yield\":\"Up to 2000 Pages\",\"Warranty\":\"1 Year\",\"Color\":\"Monochrome Black\"}', 'prodot-12a.png', 1, '4318c50fafeec33b86552a466dcbcf53ba0f58537c35d1f89bbd0a793a55e1ad'),
(13, 7, 'Canon GI-790 Black & CMYK Original Ink Bottle Set', 'STAT-TNR-013', '3215', 'Canon', 2250.00, 1890.00, 18.00, 25, 5, 'Complete set of 4 bottles (Black 135ml, Cyan, Magenta, Yellow 70ml each) for Canon G1010, G2010, G3010 printers.', '{\"Included\":\"Black 135ml, Cyan 70ml, Magenta 70ml, Yellow 70ml\",\"Page Yield\":\"Black 6000 pgs, Color 7000 pgs\"}', 'canon-gi790-set.png', 1, '9ee3d1bf9ee0f16fbc8a2be5f311c1df7c49397635db15777a760814983b6b15'),
(14, 8, 'Executive Hard Bound Account Register (200 Pages, 70 GSM)', 'STAT-REG-014', '4820', 'Sakshi Print', 260.00, 195.00, 12.00, 60, 10, 'Sturdy cloth-bound spine with ledger ruling. Acid-free bright paper suitable for accounts, ledger, and cash book entry.', '{\"Pages\":\"200 Pages\",\"Binding\":\"Hard Bound Cloth Spine\",\"Ruling\":\"Ledger / Account Rule\"}', 'account-register.png', 0, 'f16f0bcf84ecbb7c8cfbc76de29048a1491763717df30291fbc521abfe524bca'),
(15, 8, 'Kangaro Heavy Duty Lever Arch File (Pack of 5)', 'STAT-FIL-015', '3926', 'Kangaro', 750.00, 549.00, 18.00, 40, 8, 'Durable polypropylene laminated lever arch files with stainless steel ring binder and metal finger ring.', '{\"Capacity\":\"500 Sheets\",\"Spine Width\":\"75mm\",\"Pack\":\"5 Files\"}', 'lever-arch-file.png', 1, '09b30c1e8fae83f218f0c57c4f3aa9e1c14d9b4b0eec86ad334e9cb72b1588be'),
(16, 9, 'Casio MJ-120D Plus Desktop Basic Financial Calculator', 'STAT-CAL-016', '8470', 'Casio', 645.00, 520.00, 18.00, 30, 5, '12 Digits display with 300 steps check & correct, Indian comma marker, tax calculation, dual power (solar & battery).', '{\"Digits\":\"12 Digits\",\"Keys\":\"Plastic Keys\",\"Check Function\":\"300 Steps\"}', 'casio-mj120d.png', 1, '1bf92083cb8f16bc477c7c3b9b47e5b15809778e7bbef0f6e5ef5bfe3ccda262'),
(17, 9, 'Reynolds 045 Fine Carbure Ball Pen (Box of 50 Blue)', 'STAT-PEN-017', '9608', 'Reynolds', 500.00, 399.00, 18.00, 50, 10, 'India\'s most loved pen with laser tip technology for smooth, uninterrupted writing. Non-smudge ink.', '{\"Tip\":\"0.7mm Fine\",\"Ink Color\":\"Blue\",\"Pack\":\"50 Pens Box\"}', 'reynolds-045.png', 1, 'e176b6aa99e9dc0199e469aaef80baec09f6e804f3299719ea2b5a1bb7e937d5');

-- --------------------------------------------------------
-- Table structure for table `orders`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) UNIQUE NOT NULL,
  `order_hash` VARCHAR(64) UNIQUE NOT NULL,
  `user_id` INT NOT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `shipping_city` VARCHAR(100) NOT NULL,
  `shipping_state` VARCHAR(100) NOT NULL,
  `shipping_pincode` VARCHAR(10) NOT NULL,
  `gstin` VARCHAR(20) DEFAULT NULL,
  `business_name` VARCHAR(200) DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `tax_amount` DECIMAL(10,2) NOT NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'upi_qr',
  `payment_status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
  `payment_ref` VARCHAR(100) DEFAULT NULL,
  `payment_proof_file` VARCHAR(255) DEFAULT NULL,
  `payment_hash` VARCHAR(64) DEFAULT NULL,
  `payment_verified_by` INT DEFAULT NULL,
  `payment_verified_at` DATETIME DEFAULT NULL,
  `order_status` ENUM('placed', 'payment_verified', 'checked', 'dispatched', 'delivered', 'cancelled') DEFAULT 'placed',
  `checked_by` INT DEFAULT NULL,
  `checked_at` DATETIME DEFAULT NULL,
  `courier_name` VARCHAR(100) DEFAULT NULL,
  `tracking_number` VARCHAR(100) DEFAULT NULL,
  `dispatch_hash` VARCHAR(64) DEFAULT NULL,
  `dispatched_by` INT DEFAULT NULL,
  `dispatched_at` DATETIME DEFAULT NULL,
  `customer_notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `order_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) NOT NULL,
  `hsn_code` VARCHAR(20) DEFAULT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `gst_rate` DECIMAL(5,2) DEFAULT 18.00,
  `quantity` INT NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `item_seal_hash` VARCHAR(64) DEFAULT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `audit_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `performed_by_id` INT DEFAULT NULL,
  `performed_by_name` VARCHAR(100) DEFAULT NULL,
  `role` VARCHAR(50) DEFAULT NULL,
  `record_hash` VARCHAR(64) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
