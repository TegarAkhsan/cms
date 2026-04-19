<?php
// api/stats.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$user = requireAuth();
$pdo  = getDB();
$role = $user['role'];

// Filter sesuai role
$baseWhere  = '';
$baseParams = [];
if ($role === 'stakeholder') { $baseWhere = 'WHERE owner_id = ?';    $baseParams = [$user['id']]; }
elseif ($role === 'operator') { $baseWhere = 'WHERE operator_id = ?'; $baseParams = [$user['id']]; }

// Total
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM containers $baseWhere");
$stmt->execute($baseParams);
$total = $stmt->fetch()['total'];

// By status
$stmt = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM containers $baseWhere GROUP BY status");
$stmt->execute($baseParams);
$byStatus = [];
foreach ($stmt->fetchAll() as $r) $byStatus[$r['status']] = $r['cnt'];

// Dok pending
$docWhere  = "d.status = 'pending'";
$docParams = [];
if ($role === 'stakeholder') { $docWhere .= " AND c.owner_id = ?"; $docParams[] = $user['id']; }
elseif ($role === 'operator') { $docWhere .= " AND c.operator_id = ?"; $docParams[] = $user['id']; }
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM documents d JOIN containers c ON d.container_id = c.id WHERE $docWhere");
$stmt->execute($docParams);
$pendingDocs = $stmt->fetch()['total'];

// Unread notif
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user['id']]);
$unread = $stmt->fetch()['total'];

// Monthly data (12 bulan terakhir) — admin only
$monthly = [];
if ($role === 'admin') {
    for ($i = 11; $i >= 0; $i--) {
        $ym   = date('Y-m', strtotime("-$i months"));
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM containers WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
        $stmt->execute([$ym]);
        $monthly[] = ['month' => $ym, 'total' => $stmt->fetch()['total']];
    }
}

// Vessel stats
$vessels = [];
if (in_array($role, ['admin','operator'])) {
    $vWhere  = $role === 'operator' ? 'WHERE operator_id = ?' : '';
    $vParams = $role === 'operator' ? [$user['id']] : [];
    $stmt = $pdo->prepare("SELECT vessel, COUNT(*) AS cnt FROM containers $vWhere GROUP BY vessel ORDER BY cnt DESC");
    $stmt->execute($vParams);
    $vessels = $stmt->fetchAll();
}

// Operator performance — admin only
$operators = [];
if ($role === 'admin') {
    $stmt = $pdo->query("SELECT u.name, COUNT(c.id) AS total, SUM(c.status='completed') AS completed FROM users u LEFT JOIN containers c ON u.id = c.operator_id WHERE u.role = 'operator' GROUP BY u.id");
    $operators = $stmt->fetchAll();
}

$inTransit = ($byStatus['gate_in']??0) + ($byStatus['on_vessel']??0) + ($byStatus['discharged']??0) + ($byStatus['clearance']??0) + ($byStatus['on_delivery']??0);

jsonResponse([
    'total'          => $total,
    'by_status'      => $byStatus,
    'in_transit'     => $inTransit,
    'completed'      => $byStatus['completed'] ?? 0,
    'pending_docs'   => $pendingDocs,
    'unread_notifs'  => $unread,
    'monthly_data'   => $monthly,
    'vessels'        => $vessels,
    'operators'      => $operators,
]);
