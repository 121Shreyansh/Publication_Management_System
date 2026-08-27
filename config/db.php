<?php
$host = 'localhost';
$db   = 'pub'; // Your confirmed database name
$user = 'root';
$pass = 'Sreenjoy@2005'; // Leave empty for default WSL MySQL unless you set a password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Exception $e) {
    // Helpful for debugging WSL connection issues
    die("Database Connection Failed: " . $e->getMessage());
}
?>