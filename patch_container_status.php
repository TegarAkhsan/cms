<?php
require 'backend/database/db.php';
$pdo = getDB();

// 1. Temporarily change to VARCHAR to allow any status
$pdo->exec("ALTER TABLE containers MODIFY COLUMN status VARCHAR(50)");

// 2. Map existing data to new statuses
$pdo->exec("UPDATE containers SET status = 'discharge' WHERE status = 'discharged'");
$pdo->exec("UPDATE containers SET status = 'delivery' WHERE status = 'on_delivery'");
$pdo->exec("UPDATE containers SET status = 'completed' WHERE status = 'gate_in_depo'");
$pdo->exec("UPDATE containers SET status = 'ship_arrival' WHERE status = 'on_vessel'"); 
$pdo->exec("UPDATE containers SET status = 'booking' WHERE status = 'delay'");

// 3. Change back to ENUM with new values
$pdo->exec("ALTER TABLE containers MODIFY COLUMN status ENUM(
    'booking', 
    'gate_in', 
    'ship_arrival', 
    'discharge', 
    'yard_map', 
    'clearance', 
    'loading', 
    'ship_departure', 
    'delivery', 
    'completed'
) DEFAULT 'booking'");

echo "Containers status enum updated successfully.\n";
