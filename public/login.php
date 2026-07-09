<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/includes/init.php';

if (is_logged_in()) {
    $redirects = [
        'student' => 'student/dashboard.php',
        'faculty' => 'faculty/dashboard.php',
        'admin'   => 'admin/dashboard.php'
    ];
    header('Location: ' . $redirects[$_SESSION['role']] ?? 'index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';
    
    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.password_hash, u.profile_photo, r.code AS role_code
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.email = ? AND r.code = ? AND u.is_active = 1
        ");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            do_login($user);
            $redirects = [
                'student' => 'student/dashboard.php',
                'faculty' => 'faculty/dashboard.php',
                'admin'   => 'admin/dashboard.php'
            ];
            header('Location: ' . ($redirects[$role] ?? 'index.php'));
            exit;
        } else {
            $error = 'Invalid credentials or role.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniAdvisr — Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-background">
    <div class="bigcontainer">    
        <div class="container login-container">
            <div class="nev">Login</div>
            <?php if ($error): ?>
                <div class="flash error"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="p">
                    <div class="name">Role:</div>
                    <div class="box">
                        <select name="role" id="login-role" required>
                            <option hidden value="">Select Your Role</option>
                            <option value="student">Student</option>
                            <option value="faculty">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="p">
                    <div class="name">Email:</div>
                    <div class="box"><input type="email" name="email" placeholder="Enter your email" required></div>
                </div>
                <div class="p">
                    <div class="name">Password:</div>
                    <div class="box"><input type="password" name="password" placeholder="Enter your password" required></div>
                </div>
                <div class="fr">
                    <div class="ck"><input type="checkbox">Remember me</div>
                    <div class="for"><a href="#">Forgot Password?</a></div>  
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            <div class="register">Don't have an account? <a href="register.php">Register</a></div>
        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>