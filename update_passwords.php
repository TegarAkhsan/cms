<?php
require_once __DIR__ . '/backend/database/db.php';
$pdo = getDB();

$new_password = 'password';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "Updating all user passwords to '$new_password' (Hash: $hash)...\n";

$stmt = $pdo->prepare("UPDATE users SET password = ?");
if ($stmt->execute([$hash])) {
    echo "Success! All user passwords have been updated.\n";
} else {
    echo "Error updating passwords.\n";
}
