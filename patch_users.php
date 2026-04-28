<?php
require 'backend/database/db.php';
$pdo = getDB();
try { $pdo->exec("ALTER TABLE users ADD COLUMN nik VARCHAR(50)"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN npwp VARCHAR(50)"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN telepon VARCHAR(50)"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN alamat TEXT"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN kota VARCHAR(100)"); } catch(Exception $e) {}
echo "Done";
