<?php
// api/notifications.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$user  = requireAuth();
$pdo   = getDB();
$input = getInput();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user['id']]);
    $notifs = $stmt->fetchAll();
    $unread = count(array_filter($notifs, fn($n) => !$n['is_read']));
    jsonResponse(['notifications' => $notifs, 'unread_count' => $unread]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = $input['id'] ?? 'all';
    if ($id === 'all') {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user['id']]);
    } else {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$id, $user['id']]);
    }
    jsonResponse(['success' => true]);
}
