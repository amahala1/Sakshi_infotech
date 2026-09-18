<?php
/**
 * Sakshi Infotech - Database Connection Layer (MySQL Primary + SQLite Fallback)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // 1. Try MySQL Connection if pdo_mysql extension is available
        if (extension_loaded('pdo_mysql')) {
            $host = DB_HOST;
            $port = DB_PORT;
            $dbname = DB_NAME;
            $user = DB_USER;
            $pass = DB_PASS;
            $charset = DB_CHARSET;

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 1,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
                self::$driver = 'mysql';
                self::initSchema(self::$instance, 'mysql');
                return self::$instance;
            } catch (PDOException $e) {
                // Try creating database if it doesn't exist
                try {
                    $rootDsn = "mysql:host={$host};port={$port};charset={$charset}";
                    $rootPdo = new PDO($rootDsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    self::$instance = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]);
                    self::$driver = 'mysql';
                    self::initSchema(self::$instance, 'mysql');
                    self::seedInitialData(self::$instance, 'mysql');
                    return self::$instance;
                } catch (Exception $ex) {
                    // MySQL server is not running or credentials failed -> Fallback to SQLite
                    error_log("MySQL connection failed: " . $ex->getMessage() . " - switching to SQLite.");
                }
            }
        }

        // 2. Local zero-config fallback to SQLite for instant plug & play
        $dataDir = BASE_PATH . '/database';
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0777, true);
        }
        $dbPath = $dataDir . '/sakshi_infotech.sqlite';
        $isNew = !file_exists($dbPath);

        self::$instance = new PDO('sqlite:' . $dbPath);
        self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$driver = 'sqlite';

        self::initSchema(self::$instance, 'sqlite');
        if ($isNew) {
            self::seedInitialData(self::$instance, 'sqlite');
        }

        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    private static function initSchema(PDO $db, string $driver): void {
        if ($driver === 'mysql') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(100) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    full_name VARCHAR(150) NOT NULL,
                    email VARCHAR(150) UNIQUE NOT NULL,
                    phone VARCHAR(30) DEFAULT NULL,
                    address TEXT DEFAULT NULL,
                    city VARCHAR(100) DEFAULT NULL,
                    state VARCHAR(100) DEFAULT NULL,
                    pincode VARCHAR(10) DEFAULT NULL,
                    role ENUM('admin', 'staff_accounts', 'staff_checker', 'staff_dispatch', 'customer') DEFAULT 'customer',
                    customer_type VARCHAR(20) DEFAULT 'individual',
                    company_name VARCHAR(150) DEFAULT NULL,
                    gst_number VARCHAR(20) DEFAULT NULL,
                    email_verified TINYINT(1) DEFAULT 0,
                    status VARCHAR(30) DEFAULT 'active',
                    auth_token_hash VARCHAR(64) DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    slug VARCHAR(120) UNIQUE NOT NULL,
                    type ENUM('computer', 'stationery') DEFAULT 'computer',
                    description TEXT DEFAULT NULL,
                    icon VARCHAR(50) DEFAULT 'fa-folder',
                    status TINYINT(1) DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    category_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    sku VARCHAR(100) UNIQUE NOT NULL,
                    hsn_code VARCHAR(20) DEFAULT '8471',
                    brand VARCHAR(100) DEFAULT NULL,
                    regular_price DECIMAL(10,2) NOT NULL,
                    sale_price DECIMAL(10,2) NOT NULL,
                    gst_rate DECIMAL(5,2) DEFAULT 18.00,
                    stock_qty INT DEFAULT 10,
                    min_stock_alert INT DEFAULT 3,
                    description TEXT DEFAULT NULL,
                    specifications TEXT DEFAULT NULL,
                    image_url VARCHAR(255) DEFAULT NULL,
                    gallery_images TEXT DEFAULT NULL,
                    is_featured TINYINT(1) DEFAULT 0,
                    data_integrity_hash VARCHAR(64) DEFAULT NULL,
                    status TINYINT(1) DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_number VARCHAR(50) UNIQUE NOT NULL,
                    order_hash VARCHAR(64) UNIQUE NOT NULL,
                    user_id INT NOT NULL,
                    customer_name VARCHAR(150) NOT NULL,
                    customer_email VARCHAR(150) NOT NULL,
                    customer_phone VARCHAR(30) NOT NULL,
                    shipping_address TEXT NOT NULL,
                    shipping_city VARCHAR(100) NOT NULL,
                    shipping_state VARCHAR(100) NOT NULL,
                    shipping_pincode VARCHAR(10) NOT NULL,
                    gstin VARCHAR(20) DEFAULT NULL,
                    business_name VARCHAR(200) DEFAULT NULL,
                    subtotal DECIMAL(10,2) NOT NULL,
                    tax_amount DECIMAL(10,2) NOT NULL,
                    discount_amount DECIMAL(10,2) DEFAULT 0.00,
                    total_amount DECIMAL(10,2) NOT NULL,
                    payment_method VARCHAR(50) DEFAULT 'upi_qr',
                    payment_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
                    payment_ref VARCHAR(100) DEFAULT NULL,
                    payment_proof_file VARCHAR(255) DEFAULT NULL,
                    payment_hash VARCHAR(64) DEFAULT NULL,
                    payment_verified_by INT DEFAULT NULL,
                    payment_verified_at DATETIME DEFAULT NULL,
                    order_status ENUM('placed', 'payment_verified', 'checked', 'dispatched', 'delivered', 'cancelled') DEFAULT 'placed',
                    checked_by INT DEFAULT NULL,
                    checked_at DATETIME DEFAULT NULL,
                    courier_name VARCHAR(100) DEFAULT NULL,
                    tracking_number VARCHAR(100) DEFAULT NULL,
                    dispatch_hash VARCHAR(64) DEFAULT NULL,
                    dispatched_by INT DEFAULT NULL,
                    dispatched_at DATETIME DEFAULT NULL,
                    customer_notes TEXT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS order_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    product_id INT NOT NULL,
                    product_name VARCHAR(255) NOT NULL,
                    sku VARCHAR(100) NOT NULL,
                    hsn_code VARCHAR(20) DEFAULT NULL,
                    unit_price DECIMAL(10,2) NOT NULL,
                    gst_rate DECIMAL(5,2) DEFAULT 18.00,
                    quantity INT NOT NULL,
                    total_price DECIMAL(10,2) NOT NULL,
                    item_seal_hash VARCHAR(64) DEFAULT NULL,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS audit_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id INT NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    performed_by_id INT DEFAULT NULL,
                    performed_by_name VARCHAR(100) DEFAULT NULL,
                    role VARCHAR(50) DEFAULT NULL,
                    record_hash VARCHAR(64) NOT NULL,
                    details TEXT DEFAULT NULL,
                    ip_address VARCHAR(45) DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS system_settings (
                    setting_key VARCHAR(60) PRIMARY KEY,
                    setting_value TEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS email_otps (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(150) NOT NULL,
                    otp_code VARCHAR(10) NOT NULL,
                    action_type VARCHAR(50) DEFAULT 'registration',
                    expires_at DATETIME NOT NULL,
                    is_used TINYINT(1) DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email_otp (email, otp_code)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS email_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    recipient_email VARCHAR(150) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    event_type VARCHAR(50) NOT NULL,
                    status VARCHAR(20) NOT NULL,
                    error_message TEXT NULL,
                    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email_logs (recipient_email, event_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            // SQLite Compatible Syntax
            $db->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    full_name TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    phone TEXT,
                    address TEXT,
                    city TEXT,
                    state TEXT,
                    pincode TEXT,
                    role TEXT DEFAULT 'customer',
                    customer_type TEXT DEFAULT 'individual',
                    company_name TEXT DEFAULT NULL,
                    gst_number TEXT DEFAULT NULL,
                    email_verified INTEGER DEFAULT 0,
                    status TEXT DEFAULT 'active',
                    auth_token_hash TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    slug TEXT UNIQUE NOT NULL,
                    type TEXT DEFAULT 'computer',
                    description TEXT,
                    icon TEXT DEFAULT 'fa-folder',
                    status INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS products (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    category_id INTEGER NOT NULL,
                    name TEXT NOT NULL,
                    sku TEXT UNIQUE NOT NULL,
                    hsn_code TEXT DEFAULT '8471',
                    brand TEXT,
                    regular_price REAL NOT NULL,
                    sale_price REAL NOT NULL,
                    gst_rate REAL DEFAULT 18.00,
                    stock_qty INTEGER DEFAULT 10,
                    min_stock_alert INTEGER DEFAULT 3,
                    description TEXT,
                    specifications TEXT,
                    image_url TEXT,
                    gallery_images TEXT,
                    is_featured INTEGER DEFAULT 0,
                    data_integrity_hash TEXT,
                    status INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS orders (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_number TEXT UNIQUE NOT NULL,
                    order_hash TEXT UNIQUE NOT NULL,
                    user_id INTEGER NOT NULL,
                    customer_name TEXT NOT NULL,
                    customer_email TEXT NOT NULL,
                    customer_phone TEXT NOT NULL,
                    shipping_address TEXT NOT NULL,
                    shipping_city TEXT NOT NULL,
                    shipping_state TEXT NOT NULL,
                    shipping_pincode TEXT NOT NULL,
                    gstin TEXT,
                    business_name TEXT,
                    subtotal REAL NOT NULL,
                    tax_amount REAL NOT NULL,
                    discount_amount REAL DEFAULT 0.00,
                    total_amount REAL NOT NULL,
                    payment_method TEXT DEFAULT 'upi_qr',
                    payment_status TEXT DEFAULT 'pending',
                    payment_ref TEXT,
                    payment_proof_file TEXT,
                    payment_hash TEXT,
                    payment_verified_by INTEGER,
                    payment_verified_at DATETIME,
                    order_status TEXT DEFAULT 'placed',
                    checked_by INTEGER,
                    checked_at DATETIME,
                    courier_name TEXT,
                    tracking_number TEXT,
                    dispatch_hash TEXT,
                    dispatched_by INTEGER,
                    dispatched_at DATETIME,
                    customer_notes TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS order_items (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    product_name TEXT NOT NULL,
                    sku TEXT NOT NULL,
                    hsn_code TEXT,
                    unit_price REAL NOT NULL,
                    gst_rate REAL DEFAULT 18.00,
                    quantity INTEGER NOT NULL,
                    total_price REAL NOT NULL,
                    item_seal_hash TEXT,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS audit_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    entity_type TEXT NOT NULL,
                    entity_id INTEGER NOT NULL,
                    action TEXT NOT NULL,
                    performed_by_id INTEGER,
                    performed_by_name TEXT,
                    role TEXT,
                    record_hash TEXT NOT NULL,
                    details TEXT,
                    ip_address TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS system_settings (
                    setting_key TEXT PRIMARY KEY,
                    setting_value TEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS email_otps (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email TEXT NOT NULL,
                    otp_code TEXT NOT NULL,
                    action_type TEXT DEFAULT 'registration',
                    expires_at DATETIME NOT NULL,
                    is_used INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS email_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    recipient_email TEXT NOT NULL,
                    subject TEXT NOT NULL,
                    event_type TEXT NOT NULL,
                    status TEXT NOT NULL,
                    error_message TEXT,
                    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ");
        }
    }

    public static function seedInitialData(PDO $db, string $driver): void {
        require_once BASE_PATH . '/src/HashEngine.php';

        // 1. Create Default Users (Admin + Staff Roles + Customer)
        $adminPassHash = password_hash('admin123', PASSWORD_BCRYPT);
        $accountsPassHash = password_hash('accounts123', PASSWORD_BCRYPT);
        $checkerPassHash = password_hash('checker123', PASSWORD_BCRYPT);
        $dispatchPassHash = password_hash('dispatch123', PASSWORD_BCRYPT);
        $customerPassHash = password_hash('customer123', PASSWORD_BCRYPT);

        $users = [
            ['admin', $adminPassHash, 'Sakshi Admin', 'admin@sakshiinfotech.com', '9829012345', 'admin'],
            ['staff_acc', $accountsPassHash, 'Ramesh Sharma (Accounts)', 'accounts@sakshiinfotech.com', '9829011111', 'staff_accounts'],
            ['staff_chk', $checkerPassHash, 'Sunil Meena (Order Checker)', 'checker@sakshiinfotech.com', '9829022222', 'staff_checker'],
            ['staff_dsp', $dispatchPassHash, 'Vikas Verma (Dispatch)', 'dispatch@sakshiinfotech.com', '9829033333', 'staff_dispatch'],
            ['customer1', $customerPassHash, 'Gaurav Joshi', 'gaurav@example.com', '9829044444', 'customer']
        ];

        $stmtUser = $db->prepare("INSERT OR IGNORE INTO users (username, password_hash, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
        if ($driver === 'mysql') {
            $stmtUser = $db->prepare("INSERT IGNORE INTO users (username, password_hash, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
        }

        foreach ($users as $u) {
            $stmtUser->execute($u);
        }

        // 2. Create Categories for Computer & Stationery items
        $categories = [
            ['Laptops & Desktops', 'laptops-desktops', 'computer', 'High performance Business & Gaming laptops, customized desktops', 'fa-laptop'],
            ['Keyboards & Mice', 'keyboards-mice', 'computer', 'Mechanical keyboards, wireless combos, ergonomic mice', 'fa-keyboard'],
            ['Printers & Scanners', 'printers-scanners', 'computer', 'Laser printers, all-in-one inkjet, barcode scanners', 'fa-print'],
            ['Storage & Memory (SSD/RAM)', 'storage-memory', 'computer', 'NVMe M.2 SSDs, external hard drives, DDR4/DDR5 RAM', 'fa-hdd'],
            ['Networking & Cables', 'networking-cables', 'computer', 'Wi-Fi 6 Routers, CAT6 LAN cables, HDMI & Type-C adapters', 'fa-network-wired'],
            ['Printing & Copier Paper', 'paper-supplies', 'stationery', 'JK Copier A4/Legal 75/80 GSM, photo glossy sheets', 'fa-copy'],
            ['Toners & Cartridges', 'toners-cartridges', 'stationery', 'LaserJet toners, ink bottles, cartridge refills', 'fa-fill-drip'],
            ['Office Registers & Files', 'registers-files', 'stationery', 'Account registers, lever arch files, cobra files, spiral pads', 'fa-book'],
            ['Pens, Markers & Desktops', 'pens-markers', 'stationery', 'Gel pens, whiteboard markers, staplers, calculators, punches', 'fa-pen-fancy']
        ];

        $stmtCat = $db->prepare("INSERT OR IGNORE INTO categories (name, slug, type, description, icon) VALUES (?, ?, ?, ?, ?)");
        if ($driver === 'mysql') {
            $stmtCat = $db->prepare("INSERT IGNORE INTO categories (name, slug, type, description, icon) VALUES (?, ?, ?, ?, ?)");
        }
        foreach ($categories as $c) {
            $stmtCat->execute($c);
        }

        // 3. Seed Products (Computer & Stationery Items)
        $products = [
            // Computer Items
            [1, 'Lenovo ThinkPad E14 Intel Core i5 13th Gen', 'COMP-LTP-001', '8471', 'Lenovo', 68900.00, 59990.00, 18.00, 8, 2, 'Reliable business laptop with 16GB DDR5 RAM, 512GB NVMe SSD, backlit keyboard, FHD display, Fingerprint reader.', '{"RAM":"16GB DDR5","Storage":"512GB NVMe","Processor":"Intel Core i5-1335U","Display":"14-inch IPS FHD","OS":"Windows 11 Pro"}', 'laptop-lenovo.png', 1],
            [1, 'HP ProDesk 400 G9 Mini Desktop PC', 'COMP-DSK-002', '8471', 'HP', 52500.00, 46990.00, 18.00, 6, 2, 'Compact commercial desktop PC with Intel Core i5 12th Gen, 16GB RAM, 512GB SSD, Wi-Fi 6, Dual DisplayPort.', '{"Form Factor":"Mini PC","RAM":"16GB","Storage":"512GB SSD","OS":"Windows 11 Home"}', 'hp-mini-pc.png', 1],
            [2, 'Logitech MK295 Silent Wireless Keyboard & Mouse Combo', 'COMP-KB-003', '8471', 'Logitech', 2495.00, 1999.00, 18.00, 25, 5, 'SilentTouch technology reduces 90% typing noise. Full-size keyboard with 8 shortcut keys and contoured wireless mouse.', '{"Connectivity":"2.4GHz Wireless Nano Receiver","Battery Life":"36 Months (Keyboard) / 18 Months (Mouse)","Color":"Graphite Black"}', 'logitech-mk295.png', 1],
            [2, 'Zebronics Transformer RGB Gaming Keyboard & Mouse', 'COMP-KB-004', '8471', 'Zebronics', 1599.00, 1149.00, 18.00, 30, 5, 'Aluminium body, multi-color LED backlit effects, braided cable, high precision optical gaming mouse up to 3200 DPI.', '{"Interface":"USB Gold Plated","Keys":"104 Keys","Cable":"Braided 1.8M"}', 'zebronics-rgb.png', 0],
            [3, 'HP Laser 1008w Single Function Wireless Laser Printer', 'COMP-PRN-005', '8443', 'HP', 14500.00, 12890.00, 18.00, 12, 3, 'High speed crisp black-and-white printing up to 21 ppm. Built-in Wi-Fi & HP Smart App mobile printing support.', '{"Print Speed":"21 ppm","Duty Cycle":"Up to 10,000 pages","Connectivity":"Wi-Fi, USB 2.0"}', 'hp-laser-printer.png', 1],
            [3, 'Canon PIXMA G3010 All-in-One Wireless Ink Tank Printer', 'COMP-PRN-006', '8443', 'Canon', 16999.00, 14490.00, 18.00, 10, 2, 'High-volume colour printing, scanning, and copying. Cost-effective ink bottles yielding up to 7000 colour pages.', '{"Type":"Ink Tank All-in-One","Resolution":"4800x1200 dpi","Wi-Fi Direct":"Yes"}', 'canon-g3010.png', 1],
            [4, 'Crucial P3 Plus 1TB PCIe 4.0 3D NAND NVMe M.2 SSD', 'COMP-SSD-007', '8471', 'Crucial', 7500.00, 5690.00, 18.00, 40, 5, 'Blazing fast sequential read speeds up to 5000 MB/s. Perfect for high performance laptops, workstations & gaming.', '{"Capacity":"1TB","Interface":"PCIe Gen4 x4 NVMe","Read Speed":"5000 MB/s","Form Factor":"M.2 2280"}', 'crucial-nvme-1tb.png', 1],
            [4, 'Kingston Fury Beast 16GB DDR4 3200MHz Desktop RAM', 'COMP-RAM-008', '8471', 'Kingston', 3800.00, 2890.00, 18.00, 35, 5, 'Cost-effective high-performance upgrade with stylish low-profile heat spreader. Intel XMP and AMD Ryzen ready.', '{"Capacity":"16GB (1x16GB)","Frequency":"3200MHz","Latency":"CL16"}', 'kingston-fury-ram.png', 0],
            [5, 'TP-Link Archer AX12 AX1500 Dual Band Wi-Fi 6 Router', 'COMP-NET-009', '8517', 'TP-Link', 3999.00, 2799.00, 18.00, 20, 4, 'Next-gen Wi-Fi 6 speeds up to 1.5 Gbps (1201 Mbps on 5 GHz and 300 Mbps on 2.4 GHz). 4 Gigabit Ethernet ports.', '{"Standards":"Wi-Fi 6 IEEE 802.11ax","Antennas":"4 High-Gain","Gigabit Ports":"4"}', 'tplink-ax12.png', 1],

            // Stationery & Office Supplies Items
            [6, 'JK Copier A4 Paper 75 GSM - Ream of 500 Sheets', 'STAT-PPR-010', '4802', 'JK Paper', 380.00, 310.00, 12.00, 150, 20, 'Premium quality copier paper with ColorLok technology. Smear-resistant, high brightness 90%, smooth surface for jam-free printing.', '{"Size":"A4 (210x297 mm)","GSM":"75 GSM","Sheets":"500 Sheets","Brightness":"90%"}', 'jk-copier-a4.png', 1],
            [6, 'B2B Carton - JK Copier A4 (Box of 5 Reams / 2500 Sheets)', 'STAT-PPR-011', '4802', 'JK Paper', 1900.00, 1499.00, 12.00, 50, 10, 'Wholesale bulk pack of 5 reams. Best for offices, schools, cyber cafes and computer centers.', '{"Pack Type":"Carton Box","Total Sheets":"2500 Sheets","Weight":"11.5 Kg"}', 'jk-copier-box.png', 1],
            [7, 'ProDot 12A / Q2612A Compatible Laser Toner Cartridge', 'STAT-TNR-012', '8443', 'ProDot', 1200.00, 690.00, 18.00, 45, 10, 'High yield replacement toner for HP LaserJet 1010, 1020, M1005, Canon LBP 2900B. Crystal dark prints up to 2000 pages.', '{"Page Yield":"Up to 2000 Pages","Warranty":"1 Year","Color":"Monochrome Black"}', 'prodot-12a.png', 1],
            [7, 'Canon GI-790 Black & CMYK Original Ink Bottle Set', 'STAT-TNR-013', '3215', 'Canon', 2250.00, 1890.00, 18.00, 25, 5, 'Complete set of 4 bottles (Black 135ml, Cyan, Magenta, Yellow 70ml each) for Canon G1010, G2010, G3010 printers.', '{"Included":"Black 135ml, Cyan 70ml, Magenta 70ml, Yellow 70ml","Page Yield":"Black 6000 pgs, Color 7000 pgs"}', 'canon-gi790-set.png', 1],
            [8, 'Executive Hard Bound Account Register (200 Pages, 70 GSM)', 'STAT-REG-014', '4820', 'Sakshi Print', 260.00, 195.00, 12.00, 60, 10, 'Sturdy cloth-bound spine with ledger ruling. Acid-free bright paper suitable for accounts, ledger, and cash book entry.', '{"Pages":"200 Pages","Binding":"Hard Bound Cloth Spine","Ruling":"Ledger / Account Rule"}', 'account-register.png', 0],
            [8, 'Kangaro Heavy Duty Lever Arch File (Pack of 5)', 'STAT-FIL-015', '3926', 'Kangaro', 750.00, 549.00, 18.00, 40, 8, 'Durable polypropylene laminated lever arch files with stainless steel ring binder and metal finger ring.', '{"Capacity":"500 Sheets","Spine Width":"75mm","Pack":"5 Files"}', 'lever-arch-file.png', 1],
            [9, 'Casio MJ-120D Plus Desktop Basic Financial Calculator', 'STAT-CAL-016', '8470', 'Casio', 645.00, 520.00, 18.00, 30, 5, '12 Digits display with 300 steps check & correct, Indian comma marker, tax calculation, dual power (solar & battery).', '{"Digits":"12 Digits","Keys":"Plastic Keys","Check Function":"300 Steps"}', 'casio-mj120d.png', 1],
            [9, 'Reynolds 045 Fine Carbure Ball Pen (Box of 50 Blue)', 'STAT-PEN-017', '9608', 'Reynolds', 500.00, 399.00, 18.00, 50, 10, 'India\'s most loved pen with laser tip technology for smooth, uninterrupted writing. Non-smudge ink.', '{"Tip":"0.7mm Fine","Ink Color":"Blue","Pack":"50 Pens Box"}', 'reynolds-045.png', 1]
        ];

        $stmtProd = $db->prepare("INSERT OR IGNORE INTO products (category_id, name, sku, hsn_code, brand, regular_price, sale_price, gst_rate, stock_qty, min_stock_alert, description, specifications, image_url, is_featured, data_integrity_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($driver === 'mysql') {
            $stmtProd = $db->prepare("INSERT IGNORE INTO products (category_id, name, sku, hsn_code, brand, regular_price, sale_price, gst_rate, stock_qty, min_stock_alert, description, specifications, image_url, is_featured, data_integrity_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        }

        foreach ($products as $p) {
            $dataString = $p[1] . '|' . $p[2] . '|' . $p[5] . '|' . $p[6];
            $integrityHash = HashEngine::generateIntegrityHash($dataString);
            $p[] = $integrityHash;
            $stmtProd->execute($p);
        }
    }
}
