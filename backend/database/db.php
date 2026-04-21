<?php
// ============================================================
// CMS — Database Layer: MySQL via PDO with Auto-Init
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'cms_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Sends unified JSON and CORS headers.
 */
function sendCommonHeaders(): void {
    if (headers_sent()) return;
    header('Content-Type: application/json; charset=utf-8');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if ($origin) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    } else {
        header("Access-Control-Allow-Origin: *");
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // First, connect without DB name to ensure DB exists
        try {
            $tmpPdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
            $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Koneksi MySQL gagal: ' . $e->getMessage()]));
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            
            // Auto-Initialize tables if missing
            checkAndInitDB($pdo);
            
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'error' => 'Koneksi database gagal: ' . $e->getMessage(),
                'hint'  => 'Pastikan MySQL berjalan.'
            ]));
        }
    }
    return $pdo;
}

function checkAndInitDB(PDO $pdo): void {
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        // Table doesn't exist, run initialization
        $sql = "
            SET FOREIGN_KEY_CHECKS = 0;
            
            CREATE TABLE IF NOT EXISTS users (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                username   VARCHAR(50) UNIQUE NOT NULL,
                password   VARCHAR(255) NOT NULL,
                role       ENUM('admin','operator','stakeholder') NOT NULL,
                name       VARCHAR(100) NOT NULL,
                email      VARCHAR(100),
                port       VARCHAR(100),
                status     ENUM('pending','verified','rejected') DEFAULT 'verified',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS containers (
                id             VARCHAR(20) PRIMARY KEY,
                booking_no     VARCHAR(50),
                vessel         VARCHAR(100),
                voyage         VARCHAR(50),
                type           VARCHAR(30),
                weight         INT DEFAULT 0,
                commodity      VARCHAR(100),
                origin         VARCHAR(100),
                destination    VARCHAR(100),
                eta            DATE,
                status         ENUM('booking','gate_in','ship_arrival','discharge','yard_map','clearance','loading','ship_departure','delivery','completed') DEFAULT 'booking',
                booking_status ENUM('Ekspor','Impor') DEFAULT 'Ekspor',
                owner_id       INT,
                operator_id    INT,
                position_lat   DECIMAL(10,6) DEFAULT -7.257500,
                position_lng   DECIMAL(10,6) DEFAULT 112.752100,
                position_desc  VARCHAR(200),
                created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id)    REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS documents (
                id           VARCHAR(20) PRIMARY KEY,
                container_id VARCHAR(20),
                type         VARCHAR(100) NOT NULL,
                filename     VARCHAR(200),
                filepath     VARCHAR(300),
                status       ENUM('pending','approved','revision') DEFAULT 'pending',
                uploaded_by  INT,
                notes        TEXT,
                created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (container_id) REFERENCES containers(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by)  REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS events (
                id           VARCHAR(20) PRIMARY KEY,
                container_id VARCHAR(20),
                event        VARCHAR(100) NOT NULL,
                actor        VARCHAR(100),
                note         TEXT,
                timestamp    DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (container_id) REFERENCES containers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS notifications (
                id           VARCHAR(20) PRIMARY KEY,
                user_id      INT,
                container_id VARCHAR(20),
                message      TEXT NOT NULL,
                type         ENUM('info','success','warning','danger') DEFAULT 'info',
                is_read      TINYINT(1) DEFAULT 0,
                created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            SET FOREIGN_KEY_CHECKS = 1;

            -- SEED DATA
            INSERT IGNORE INTO users (id, username, password, role, name, email, port, status) VALUES
            (1, 'admin',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',       'Administrator',       'admin@cms.id',           NULL,             'verified'),
            (2, 'operator1',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator',    'Budi Santoso',        'budi@pelabuhan.id',      'Tanjung Perak',  'verified'),
            (3, 'operator2',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator',    'Sari Dewi',           'sari@pelabuhan.id',      'Pelabuhan Merak','verified'),
            (4, 'stakeholder1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'stakeholder', 'PT. Maju Sejahtera',  'cs@majusejahtera.id',    NULL,             'verified'),
            (5, 'stakeholder2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'stakeholder', 'CV. Nusantara Cargo', 'info@nusantaracargo.id', NULL,             'verified');

            INSERT IGNORE INTO containers (id, booking_no, vessel, voyage, type, weight, commodity, origin, destination, eta, status, booking_status, owner_id, operator_id, position_lat, position_lng, position_desc, created_at) VALUES
            ('CTR001','BK-2026-0312','KM. Nusantara Jaya','NJ-2026-03','20ft Dry',18500,'Elektronik','Jakarta','Surabaya','2026-03-05','gate_in','Ekspor',4,2,-7.257500,112.752100,'Yard A-12, Tanjung Perak','2026-03-01 08:00:00'),
            ('CTR002','BK-2026-0287','MV. Samudra Biru','SB-2026-02','40ft HC',24000,'Tekstil','Surabaya','Makassar','2026-03-08','ship_departure','Ekspor',4,2,-7.180000,112.720000,'On Board MV. Samudra Biru','2026-02-28 10:00:00'),
            ('CTR003','BK-2026-0301','KM. Garuda Mas','GM-2026-04','20ft Reefer',12000,'Produk Segar','Makassar','Jakarta','2026-03-10','clearance','Impor',5,3,-6.105000,106.830000,'Clearance Bea Cukai - Tanjung Priok','2026-03-02 07:30:00');
        ";
        $pdo->exec($sql);
    }
}

// ── HELPERS ───────────────────────────────────────────────
function genId(string $prefix): string {
    return $prefix . strtoupper(substr(uniqid(), -6));
}

function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getInput(): array {
    $body = file_get_contents('php://input');
    $json = json_decode($body, true) ?? [];
    return array_merge($_GET, $_POST, $json);
}

function startSecureSession(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $cookiePath = dirname(dirname($scriptPath)) . '/';
    if ($cookiePath === '//') $cookiePath = '/';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $cookiePath,
        'domain'   => '',
        'secure'   => false, 
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function requireAuth(): array {
    startSecureSession();
    if (empty($_SESSION['user'])) {
        jsonResponse(['error' => 'Unauthorized - silakan login kembali'], 401);
    }
    return $_SESSION['user'];
}

function requireRole(string $role): array {
    $user = requireAuth();
    if ($user['role'] !== $role) {
        jsonResponse(['error' => 'Akses ditolak'], 403);
    }
    return $user;
}

// Jika diakses langsung via browser, jalankan inisialisasi
if (basename($_SERVER['PHP_SELF']) === 'db.php') {
    try {
        getDB();
        echo json_encode(['success' => true, 'message' => 'Database initialized/updated successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
