<?php
// ============================================================
// CMS — Database Layer: MySQL via PDO
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'cms_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Ensure clean JSON output by hiding errors in production-like environments
// ini_set('display_errors', 0); 
// error_reporting(E_ALL);

/**
 * Sends unified JSON and CORS headers.
 */
function sendCommonHeaders(): void {
    if (headers_sent()) return;
    
    header('Content-Type: application/json; charset=utf-8');
    
    // Handle CORS with credentials support
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if ($origin) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    } else {
        header("Access-Control-Allow-Origin: *");
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'error' => 'Koneksi database gagal: ' . $e->getMessage(),
                'hint'  => 'Pastikan MySQL berjalan dan database cms_db sudah dibuat.'
            ]));
        }
    }
    return $pdo;
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

    // Determine the cookie path: strip /backend/api down to the project root
    // e.g. /cms/backend/api → cookie path = /cms/
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Walk up two directories (api/ → backend/ → project root)
    $cookiePath = dirname(dirname($scriptPath)) . '/';
    // Normalize to at least /
    if ($cookiePath === '//') $cookiePath = '/';

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $cookiePath,
        'domain'   => '',
        'secure'   => false, // set true if serving HTTPS
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
