<?php
declare(strict_types=1);

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//  Set PHP timezone to Bangladesh (UTC+6)
date_default_timezone_set('Asia/Dhaka');

// Load environment variables (simple .env parser)
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) == 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $_ENV[$key] = $value;
        }
    }
}

// Database connection
require_once __DIR__ . '/db.php';

// Helper functions
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

//  Upload helper (for file uploads)
require_once __DIR__ . '/upload_helper.php';