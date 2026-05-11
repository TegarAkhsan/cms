<?php
require_once __DIR__ . '/../database/db.php';
sendCommonHeaders();

startSecureSession();
$user   = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$pdo    = getDB();

// Ensure table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ships (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        flag VARCHAR(100) NOT NULL,
        type VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    // Table already exists or creation failed, proceed anyway
}

switch ($method) {
    case 'GET':
        $search = $input['search'] ?? '';
        $query = "SELECT * FROM ships WHERE user_id = ?";
        $params = [$user['id']];

        if ($search) {
            $query .= " AND (name LIKE ? OR flag LIKE ? OR type LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        jsonResponse(['ships' => $stmt->fetchAll()]);
        break;

    case 'POST':
        if ($user['role'] !== 'stakeholder') jsonResponse(['error' => 'Akses ditolak'], 403);
        
        $name = $input['name'] ?? null;
        $flag = $input['flag'] ?? null;
        $type = $input['type'] ?? null;

        if (!$name || !$flag || !$type) {
            jsonResponse(['error' => 'Data kapal tidak lengkap'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO ships (user_id, name, flag, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], $name, $flag, $type]);

        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'DELETE':
        $id = $input['id'] ?? null;
        if (!$id) jsonResponse(['error' => 'ID diperlukan'], 400);

        $stmt = $pdo->prepare("DELETE FROM ships WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user['id']]);

        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
