<?php
require 'backend/database/db.php';
$pdo = getDB();
$pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('pending','verified','rejected') DEFAULT 'verified'");
echo "Done";
