<?php
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_faculty.php';
require_role('faculty');
$facultyId = $_SESSION['user_id'];

$students = $pdo->prepare("
    SELECT u.id, u.name, u.email, sp.student_code, sp.program_name, sp.class_section,
           COUNT(a.id) AS total_sessions,
           SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed_sessions
    FROM student_faculty_assignments sfa
    JOIN users u ON u.id = sfa.student_user_id
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    LEFT JOIN appointments a ON a.student_user_id = u.id AND a.faculty_user_id = ?
    WHERE sfa.faculty_user_id = ? AND (sfa.end_date IS NULL OR sfa.end_date >= CURDATE())
    GROUP BY u.id
    ORDER BY u.name
");
$students->execute([$facultyId, $facultyId]);
$studentList = $students->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><title>My Students - UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="faculty-dashboard-shell">
 
  <main class="faculty-students-container">
    <h1>Assigned Students</h1>
    <?php if ($studentList): ?>
      <div class="table-scroll">
        <table>
          <thead><tr><th>Code</th><th>Name</th><th>Program</th><th>Section</th><th>Sessions</th></tr></thead>
          <tbody>
            <?php foreach ($studentList as $s): ?>
              <tr>
                <td><?= e($s['student_code']) ?></td>
                <td><?= e($s['name']) ?></td>
                <td><?= e($s['program_name'] ?? 'N/A') ?></td>
                <td><?= e($s['class_section'] ?? 'N/A') ?></td>
                <td><?= $s['completed_sessions'] ?>/<?= $s['total_sessions'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p>No students assigned.</p>
    <?php endif; ?>
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