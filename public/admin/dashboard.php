<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_admin.php';
require_role('admin');

$adminId = $_SESSION['user_id'];
$csrf = csrf_token();

// Soft delete (deactivate) user if action triggered
//gets the user ID to deactivate and token from url of a query string
if (isset($_GET['deactivate_user']) && csrf_verify($_GET['token'] ?? '')) {
    $userId = (int)$_GET['deactivate_user'];
    // Prevent self-deactivation
    if ($userId === $adminId) {
        set_flash('error', 'You cannot deactivate your own account.');
    } else {
        $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?")->execute([$userId]);
        set_flash('success', 'User deactivated successfully.');
    }
    header('Location: dashboard.php');
    exit;
}

// Fetch users by role (including is_active status)
$students = $pdo->query("
    SELECT u.id, u.name, u.email, u.is_active, sp.student_code, d.name AS dept
    FROM users u
    JOIN roles r ON r.id = u.role_id AND r.code = 'student'
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    LEFT JOIN departments d ON d.id = sp.department_id
    ORDER BY u.name
")->fetchAll();

$faculties = $pdo->query("
    SELECT u.id, u.name, u.email, u.is_active, fp.employee_code, d.name AS dept
    FROM users u
    JOIN roles r ON r.id = u.role_id AND r.code = 'faculty'
    LEFT JOIN faculty_profiles fp ON fp.user_id = u.id
    LEFT JOIN departments d ON d.id = fp.department_id
    ORDER BY u.name
")->fetchAll();

$admins = $pdo->query("
    SELECT u.id, u.name, u.email, u.is_active
    FROM users u
    JOIN roles r ON r.id = u.role_id AND r.code = 'admin'
    ORDER BY u.name
")->fetchAll();

// Stats
$totalStudents = count($students);
$totalFaculties = count($faculties);
$totalAppts = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard - UniAdvisr</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
<div class="admin-dashboard-shell">

  <main class="admin-dashboard-container">
    <?php foreach (get_flash() as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>
    <section class="admin-stats">
      <div class="stat-card"><strong><?= $totalStudents ?></strong><span>Total Students</span></div>
      <div class="stat-card"><strong><?= $totalFaculties ?></strong><span>Total Faculties</span></div>
      <div class="stat-card"><strong><?= $totalAppts ?></strong><span>Total Appointments</span></div>
    </section>

    <div class="admin-tabs">
      <button class="tab-btn active" data-tab="students-tab">Students</button>
      <button class="tab-btn" data-tab="faculties-tab">Faculties</button>
      <button class="tab-btn" data-tab="admins-tab">Admins</button>
    </div>

    <div id="students-tab" class="tab-content active">
      <h2>Student Accounts</h2>
      <div class="table-scroll">
        <table>
          <thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Department</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($students as $s): ?>
              <tr>
                <td><?= e($s['student_code']) ?></td>
                <td><?= e($s['name']) ?></td>
                <td><?= e($s['email']) ?></td>
                <td><?= e($s['dept'] ?? 'N/A') ?></td>
                <td><span class="status-<?= $s['is_active'] ? 'active' : 'inactive' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                  <?php if ($s['is_active'] && $s['id'] !== $adminId): ?>
                    <a href="?deactivate_user=<?= $s['id'] ?>&token=<?= $csrf ?>" onclick="return confirm('Deactivate this student?')" class="btn btn-xs btn-danger">Deactivate</a>
                  <?php else: ?>
                    <span class="muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="faculties-tab" class="tab-content">
      <h2>Faculty Accounts</h2>
      <div class="table-scroll">
        <table>
          <thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Department</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($faculties as $f): ?>
              <tr>
                <td><?= e($f['employee_code']) ?></td>
                <td><?= e($f['name']) ?></td>
                <td><?= e($f['email']) ?></td>
                <td><?= e($f['dept'] ?? 'N/A') ?></td>
                <td><span class="status-<?= $f['is_active'] ? 'active' : 'inactive' ?>"><?= $f['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                  <?php if ($f['is_active'] && $f['id'] !== $adminId): ?>
                    <a href="?deactivate_user=<?= $f['id'] ?>&token=<?= $csrf ?>" onclick="return confirm('Deactivate this faculty?')" class="btn btn-xs btn-danger">Deactivate</a>
                  <?php else: ?>
                    <span class="muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="admins-tab" class="tab-content">
      <h2>Admin Accounts</h2>
      <div class="table-scroll">
        <table>
          <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($admins as $a): ?>
              <tr>
                <td><?= e($a['name']) ?></td>
                <td><?= e($a['email']) ?></td>
                <td><span class="status-<?= $a['is_active'] ? 'active' : 'inactive' ?>"><?= $a['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                  <?php if ($a['is_active'] && $a['id'] !== $adminId): ?>
                    <a href="?deactivate_user=<?= $a['id'] ?>&token=<?= $csrf ?>" onclick="return confirm('Deactivate this admin?')" class="btn btn-xs btn-danger">Deactivate</a>
                  <?php else: ?>
                    <span class="muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
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
<script src="../assets/js/main.js"></script>
</body>
</html>