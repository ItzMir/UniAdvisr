<?php
declare(strict_types=1);

//Check if user is logged in
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

// Require a specific role; redirect if not met
function require_role(string $role): void {
    if (!is_logged_in() || ($_SESSION['role'] ?? '') !== $role) {
        header('Location: /login.php');
        exit;
    }
}

// Perform login by setting session variables and updating last login time
function do_login(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role_code'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['profile_photo'] = $user['profile_photo'] ?? null;
    
    // Update last login
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
}