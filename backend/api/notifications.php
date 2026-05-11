<?php
require_once __DIR__ . '/../database/db.php';
sendCommonHeaders();
startSecureSession();
$user  = requireAuth();
$pdo   = getDB();
$input = getInput();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $where = ["n.user_id = ?"];
    $params = [$user['id']];

    $type = $input['type'] ?? '';
    if ($type) {
        if ($type === 'Kontainer') {
            $where[] = "n.message LIKE '%Kontainer%'";
        } elseif ($type === 'Dokumen') {
            $where[] = "n.message LIKE '%Dokumen%'";
        }
    }

    $search = $input['search'] ?? '';
    if ($search) {
        $where[] = "(n.container_id LIKE ? OR c.vessel LIKE ? OR c.commodity LIKE ? OR n.message LIKE ?)";
        $s = "%$search%";
        array_push($params, $s, $s, $s, $s);
    }

    $sql = "SELECT n.*, c.vessel, c.commodity
            FROM notifications n 
            LEFT JOIN containers c ON n.container_id = c.id 
            WHERE " . implode(" AND ", $where) . "
            ORDER BY n.created_at DESC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifs = $stmt->fetchAll();

    // Get unread count for the whole set (for badge)
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtCount->execute([$user['id']]);
    $unreadTotal = $stmtCount->fetchColumn();

    jsonResponse(['notifications' => $notifs, 'unread_count' => (int)$unreadTotal]);
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
