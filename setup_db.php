<?php
try {
    // 1. Connect without selecting database
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS cms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database 'cms_db' verified/created.\n";

    // 3. Connect to the newly created database
    $pdo->exec("USE cms_db");

    // 4. Import the schema if it exists
    $sql_file = __DIR__ . '/backend/database/cms_db.sql';
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        $pdo->exec($sql);
        echo "Table schemas and dummy data imported successfully from cms_db.sql.\n";
    } else {
        echo "Warning: cms_db.sql not found at {$sql_file}\n";
    }

    // 5. Optionally run the seed program 
    $seed_file = __DIR__ . '/seed.php';
    if (file_exists($seed_file)) {
        include $seed_file;
    }

    echo "ALL DONE!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
