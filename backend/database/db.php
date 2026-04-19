<?php
// ============================================================
// CMS — Database Layer: MySQL via PDO
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'cms_db');
define('DB_USER', 'root');
define('DB_PASS', '');       // Kosong = default XAMPP tanpa password
define('DB_CHARSET', 'utf8mb4');

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

function requireAuth(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
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
