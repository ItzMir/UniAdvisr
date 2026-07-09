<?php
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_admin.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? '')) {
    $studentId = (int)$_POST['student_id'];
    $facultyId = (int)$_POST['faculty_id'];
    $type = $_POST['assignment_type'];
    $primary = isset($_POST['is_primary']) ? 1 : 0;
    $startDate = date('Y-m-d');

    // Check for existing assignment with same student, faculty, and start_date
    $stmt = $pdo->prepare("SELECT id FROM student_faculty_assignments WHERE student_user_id = ? AND faculty_user_id = ? AND start_date = ?");
    $stmt->execute([$studentId, $facultyId, $startDate]);
    if ($stmt->fetch()) {
        set_flash('error', 'An assignment for this student and faculty already exists for today. Please choose a different date or modify the existing assignment.');
        header('Location: manage_students.php');
        exit;
    }

    // If primary, set all other primary assignments to false for this student
    if ($primary) {
        $pdo->prepare("UPDATE student_faculty_assignments SET is_primary = 0 WHERE student_user_id = ?")->execute([$studentId]);
    }

    $stmt = $pdo->prepare("INSERT INTO student_faculty_assignments (student_user_id, faculty_user_id, assigned_by_user_id, assignment_type, is_primary, start_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$studentId, $facultyId, $_SESSION['user_id'], $type, $primary, $startDate]);
    set_flash('success', 'Assignment created.');
    header('Location: manage_students.php');
    exit;
}

$csrf = csrf_token();
$students = $pdo->query("SELECT u.id, u.name, sp.student_code FROM users u JOIN roles r ON r.id = u.role_id AND r.code = 'student' LEFT JOIN student_profiles sp ON sp.user_id = u.id WHERE u.is_active = 1 ORDER BY u.name")->fetchAll();
$faculties = $pdo->query("SELECT u.id, u.name, fp.employee_code, d.name AS dept FROM users u JOIN roles r ON r.id = u.role_id AND r.code = 'faculty' LEFT JOIN faculty_profiles fp ON fp.user_id = u.id LEFT JOIN departments d ON d.id = fp.department_id WHERE u.is_active = 1 ORDER BY u.name")->fetchAll();
$assignments = $pdo->query("SELECT sfa.*, u1.name AS student_name, u2.name AS faculty_name FROM student_faculty_assignments sfa JOIN users u1 ON u1.id = sfa.student_user_id JOIN users u2 ON u2.id = sfa.faculty_user_id ORDER BY sfa.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Manage Assignments - UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="admin-dashboard-shell">
  <main class="admin-assign-container">
    <?php foreach (get_flash() as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>
    <section>
      <h2>Create New Assignment</h2>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="form-group">
          <label>Student</label>
          <select name="student_id" required>
            <option value="">Select student</option>
            <?php foreach ($students as $s): ?>
              <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['student_code']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Faculty</label>
          <select name="faculty_id" required>
            <option value="">Select faculty</option>
            <?php foreach ($faculties as $f): ?>
              <option value="<?= $f['id'] ?>"><?= e($f['name']) ?> (<?= e($f['employee_code']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Type</label>
          <select name="assignment_type">
            <option value="advisor">Advisor</option>
            <option value="class_teacher">Class Teacher</option>
            <option value="temporary">Temporary</option>
          </select>
        </div>
        <div class="form-group">
          <label><input type="checkbox" name="is_primary"> Primary Advisor</label>
        </div>
        <button type="submit" class="btn btn-primary">Assign</button>
      </form>
    </section>

    <section style="margin-top:40px;">
      <h2>Current Assignments</h2>
      <div class="table-scroll">
        <table>
          <thead><tr><th>Student</th><th>Faculty</th><th>Type</th><th>Primary</th></tr></thead>
          <tbody>
            <?php foreach ($assignments as $a): ?>
              <tr>
                <td><?= e($a['student_name']) ?></td>
                <td><?= e($a['faculty_name']) ?></td>
                <td><?= e($a['assignment_type']) ?></td>
                <td><?= $a['is_primary'] ? 'Yes' : 'No' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
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