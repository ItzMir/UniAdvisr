<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/includes/init.php';

if (is_logged_in()) {
    $redirects = [
        'student' => 'student/dashboard.php',
        'faculty' => 'faculty/dashboard.php',
        'admin'   => 'admin/dashboard.php'
    ];
    header('Location: ' . ($redirects[$_SESSION['role']] ?? 'index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email) || empty($role) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE code = ?");
            $stmt->execute([$role]);
            $roleRow = $stmt->fetch();
            if (!$roleRow) {
                $error = 'Invalid role selected.';
            } else {
                $roleId = $roleRow['id'];
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$roleId, $name, $email, $hash]);
                    $userId = $pdo->lastInsertId();
                    
                    if ($role === 'student') {
                        $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, student_code) VALUES (?, ?)");
                        $stmt->execute([$userId, 'STU' . str_pad((string)$userId, 5, '0', STR_PAD_LEFT)]);
                    } elseif ($role === 'faculty') {
                        $stmt = $pdo->prepare("INSERT INTO faculty_profiles (user_id, employee_code) VALUES (?, ?)");
                        $stmt->execute([$userId, 'FAC' . str_pad((string)$userId, 5, '0', STR_PAD_LEFT)]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO admin_profiles (user_id) VALUES (?)");
                        $stmt->execute([$userId]);
                    }
                    
                    $pdo->commit();
                    
                    // --- AUTO LOGIN & REDIRECT ---
                    // Fetch the newly created user with role code
                    $stmt = $pdo->prepare("
                        SELECT u.*, r.code AS role_code 
                        FROM users u 
                        JOIN roles r ON r.id = u.role_id 
                        WHERE u.id = ?
                    ");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        do_login($user); // sets session variables
                        // Redirect to role-specific dashboard
                        $redirects = [
                            'student' => 'student/dashboard.php',
                            'faculty' => 'faculty/dashboard.php',
                            'admin'   => 'admin/dashboard.php'
                        ];
                        $target = $redirects[$user['role_code']] ?? 'index.php';
                        header('Location: ' . $target);
                        exit;
                    } else {
                        // Fallback: should never happen, but just in case
                        $error = 'Registration succeeded but login failed. Please login manually.';
                    }
                } catch (\Exception $ex) {
                    $pdo->rollBack();
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniAdvisr — Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="bigcontainer">
    <div class="container login-container">
        <div class="nev">Register</div>
        <?php if ($error): ?>
            <div class="flash error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="p">
                <div class="name">Username</div>
                <div class="box"><input type="text" name="name" placeholder="Enter your username" required></div>
            </div>
            <div class="p">
                <div class="name">Email Address</div>
                <div class="box"><input type="email" name="email" placeholder="Enter your email" required></div>
            </div>
            <div class="p">
                <div class="name">Account Type</div>
                <div class="box">
                    <select name="role" required>
                        <option value="" hidden>Select Role</option>
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="p">
                <div class="name">Password</div>
                <div class="box"><input type="password" name="password" placeholder="Enter your password" required></div>
            </div>
            <div class="p">
                <div class="name">Confirm Password</div>
                <div class="box"><input type="password" name="confirm_password" placeholder="Re-enter your password" required></div>
            </div>
            <div class="bottom">
                <button type="submit" class="btn btn-primary register-btn">Create Account</button>
            </div>
        </form>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>