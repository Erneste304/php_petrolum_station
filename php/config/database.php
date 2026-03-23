<?php
$host = 'localhost';
$dbname = 'petroleum_station_db';
$username = 'Erneste304tech';
$password = 'Password123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set timezone for MySQL
    $pdo->exec("SET time_zone = '+02:00'");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>