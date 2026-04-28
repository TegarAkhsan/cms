<?php
require_once __DIR__ . '/../database/db.php';
sendCommonHeaders();

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = getInput();
$name = trim($input['name'] ?? '');
$nik = trim($input['nik'] ?? '');
$npwp = trim($input['npwp'] ?? '');
$email = trim($input['email'] ?? '');
$telepon = trim($input['telepon'] ?? '');
$password = trim($input['password'] ?? '');
$alamat = trim($input['alamat'] ?? '');
$port = trim($input['port'] ?? '');

if (!$name || !$email || !$password) {
    jsonResponse(['error' => 'Nama, email, dan password wajib diisi'], 400);
}

$pdo = getDB();

// Check if email/username already exists
$check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$check->execute([$email]);
if ($check->fetch()) {
    jsonResponse(['error' => 'Email sudah terdaftar'], 409);
}

// Default role to stakeholder for public registration, and status to pending
$role = 'stakeholder';
$status = 'pending';
$username = $email;

$stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, nik, npwp, email, telepon, alamat, port, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $name, $nik, $npwp, $email, $telepon, $alamat, $port, $status]);

// Create a notification for the admin about the new user
$userId = $pdo->lastInsertId();
$adminCheck = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$admin = $adminCheck->fetch();
if ($admin) {
    $notifMsg = "Pengguna baru mendaftar: $name ($email). Menunggu verifikasi.";
    $notifId = 'NTF' . time() . rand(100, 999);
    $pdo->prepare("INSERT INTO notifications (id, user_id, message, type) VALUES (?, ?, ?, 'warning')")
        ->execute([$notifId, $admin['id'], $notifMsg]);
}

jsonResponse(['success' => true]);
