<?php
// api/auth.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../database/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = getInput();

switch ($action) {

    case 'login':
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        if (!$username || !$password) {
            jsonResponse(['error' => 'Username dan password wajib diisi'], 400);
        }

        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['error' => 'Username atau password salah'], 401);
        }

        unset($user['password']);
        $_SESSION['user'] = $user;
        session_regenerate_id(true);

        jsonResponse([
            'success'  => true,
            'user'     => $user,
            'redirect' => $user['role'] . '.html'
        ]);
        break;

    case 'logout':
        $_SESSION = [];
        session_destroy();
        jsonResponse(['success' => true]);
        break;

    case 'check':
        if (!empty($_SESSION['user'])) {
            jsonResponse(['authenticated' => true, 'user' => $_SESSION['user']]);
        } else {
            jsonResponse(['authenticated' => false], 401);
        }
        break;

    default:
        jsonResponse(['error' => 'Action tidak dikenal'], 400);
}
