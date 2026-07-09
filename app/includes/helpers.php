<?php
declare(strict_types=1);

// Escape output for HTML safety
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Format a time string (HH:MM:SS) to 12‑hour format
function format_time_12h(?string $time): string {
    if (!$time) return '~~';
    $ts = strtotime($time); // Convert to timestamp
    return date('g:i A', $ts); // Format to 12‑hour with AM/PM
}

// Format a datetime string to human‑readable
function format_datetime(?string $dt, string $format = 'M j, Y g:i A'): string {
    if (!$dt) return '';
    return date($format, strtotime($dt)); // Format to something like: Jan 1, 2024 5:00 PM
}

// Generate CSRF token
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a secure random token of 64 characters
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function csrf_verify(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token); // Use hash_equals to check if the token are same, preventing time attacks
}

// Set flash message
function set_flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; // Store flash messages in session as an array of ['type' => 'success|error', 'message' => '...']
}

// Get flash messages and clear them
function get_flash(): array {
    $messages = $_SESSION['flash'] ?? []; // Retrieve flash messages, or return empty array if none
    unset($_SESSION['flash']); // Clear flash messages after retrieving
    return $messages;
}