<?php
// db_config.php - Database configuration for Technodome

define('DB_HOST', 'localhost');
define('DB_NAME', 'techno_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default is empty

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
