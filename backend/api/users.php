<?php
require_once __DIR__ . '/../database/db.php';
sendCommonHeaders();
startSecureSession();
$user   = requireRole('admin');
$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$pdo    = getDB();

switch ($method) {
    case 'GET':
        $role = $input['role'] ?? null;
        if ($role) {
            $stmt = $pdo->prepare("SELECT id, username, role, name, email, port, created_at FROM users WHERE role = ? ORDER BY name");
            $stmt->execute([$role]);
        } else {
            $stmt = $pdo->query("SELECT id, username, role, name, email, port, created_at FROM users ORDER BY role, name");
        }
        jsonResponse($stmt->fetchAll());
        break;

    case 'POST':
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $role     = trim($input['role']     ?? '');
        $name     = trim($input['name']     ?? '');
        if (!$username || !$password || !$role || !$name) jsonResponse(['error' => 'Semua field wajib diisi'], 400);
        if (!in_array($role, ['admin','operator','stakeholder'])) jsonResponse(['error' => 'Role tidak valid'], 400);

        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) jsonResponse(['error' => 'Username sudah digunakan'], 409);

        $pdo->prepare("INSERT INTO users (username,password,role,name,email,port) VALUES (?,?,?,?,?,?)")
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $name, $input['email'] ?? '', $input['port'] ?? '']);

        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        $id = intval($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID wajib diisi'], 400);

        if (!empty($input['password'])) {
            $pdo->prepare("UPDATE users SET name=?,email=?,port=?,role=?,password=? WHERE id=?")
                ->execute([$input['name']??'', $input['email']??'', $input['port']??'', $input['role']??'stakeholder', password_hash($input['password'], PASSWORD_DEFAULT), $id]);
        } else {
            $pdo->prepare("UPDATE users SET name=?,email=?,port=?,role=? WHERE id=?")
                ->execute([$input['name']??'', $input['email']??'', $input['port']??'', $input['role']??'stakeholder', $id]);
        }
        jsonResponse(['success' => true]);
        break;

    case 'DELETE':
        $id = intval($input['id'] ?? 0);
        if (!$id || $id === intval($user['id'])) jsonResponse(['error' => 'Tidak bisa hapus akun sendiri'], 400);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;
}
