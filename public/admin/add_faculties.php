<?php
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_admin.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? '')) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $employeeCode = $_POST['employee_code'];
    $deptId = $_POST['department_id'] ?? null;
    $designation = $_POST['designation'] ?? '';

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $roleId = $pdo->query("SELECT id FROM roles WHERE code = 'faculty'")->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)");
    $stmt->execute([$roleId, $name, $email, $hash]);
    $userId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO faculty_profiles (user_id, employee_code, department_id, designation) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $employeeCode, $deptId, $designation]);

    set_flash('success', 'Faculty added successfully.');
    header('Location: add_faculties.php');
    exit;
}

$csrf = csrf_token();
$departments = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Add Faculty - UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="admin-dashboard-shell">
  <main class="admin-add-faculty-container">
    <?php foreach (get_flash() as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>
    <h1>Register New Faculty</h1>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="form-group"><label>Full Name</label><input type="text" name="name" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
      <div class="form-group"><label>Employee Code</label><input type="text" name="employee_code" required></div>
      <div class="form-group"><label>Department</label>
        <select name="department_id">
          <option value="">None</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Designation</label><input type="text" name="designation"></div>
      <button type="submit" class="btn btn-primary">Create Faculty</button>
    </form>
  </main>
  <footer class="site-footer">
  <div class="footer-container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">UniAdvisr</div>
        <p>Your trusted university guidance platform for better academic decisions.</p>
        <div class="social-links">
          <a href="#">Facebook</a><a href="#">Twitter</a><a href="#">LinkedIn</a>
        </div>
      </div>
      <div>
        <h4>Quick Links</h4>
        <a href="../index.php">Home</a>
        <a href="#">Help Center</a><a href="#">Contact Us</a>
      </div>
      <div>
        <h4>Support</h4>
        <a href="#">Help Center</a><a href="#">Contact Us</a><a href="#">FAQs</a>
      </div>
      <div>
        <h4>Contact</h4>
        <p>furiousrashid02@gmail.com</p>
        <p>+8801889143752</p>
        <p>24/7 Available</p>
      </div>
    </div>
    <div class="footer-bottom"><p>© 2026 UniAdvisr. All rights reserved.</p></div>
  </div>
</footer>
</div>
</body>
</html>