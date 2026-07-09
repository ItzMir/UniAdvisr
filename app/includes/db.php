<?php
declare(strict_types=1);

// Database connection using PDO with environment variables for configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'uniadvisr';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

// PDO options for better error handling and security
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    //  Set MySQL session timezone to Bangladesh (UTC+6)
    $pdo->exec("SET time_zone = '+06:00'");

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}