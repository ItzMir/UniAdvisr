<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_faculty.php';
require_role('faculty');

$facultyId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Profile info
$stmt = $pdo->prepare("SELECT fp.*, u.name, u.email, u.profile_photo FROM faculty_profiles fp JOIN users u ON u.id = fp.user_id WHERE fp.user_id = ?");
$stmt->execute([$facultyId]);
$faculty = $stmt->fetch();

// Counts
$pending = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE faculty_user_id = ? AND status = 'pending'");
$pending->execute([$facultyId]);
$pendingCount = $pending->fetchColumn();

$todayApproved = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE faculty_user_id = ? AND appointment_date = CURDATE() AND status IN ('approved','in_progress')");
$todayApproved->execute([$facultyId]);
$todayCount = $todayApproved->fetchColumn();

// Next 3 active appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS student_name, sp.student_code, sp.program_name
    FROM appointments a
    JOIN users u ON u.id = a.student_user_id
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    WHERE a.faculty_user_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ('approved','in_progress')
    ORDER BY a.appointment_date, a.scheduled_start_at, a.token_number
    LIMIT 3
");
$stmt->execute([$facultyId]);
$upcomingThree = $stmt->fetchAll();

// Today's schedule (all statuses except cancelled)
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS student_name
    FROM appointments a JOIN users u ON u.id = a.student_user_id
    WHERE a.faculty_user_id = ? AND a.appointment_date = CURDATE()
      AND a.status NOT IN ('cancelled_by_student','cancelled_by_faculty','declined')
    ORDER BY a.scheduled_start_at
");
$stmt->execute([$facultyId]);
$todaySessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Faculty Dashboard - UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="faculty-dashboard-shell">
  <!-- Hidden global CSRF token for JS -->
  <input type="hidden" id="global_csrf_token" value="<?= csrf_token() ?>">
  <main class="faculty-dashboard-container">
    <section class="stats-row">
      <div class="stat-card"><strong><?= $pendingCount ?></strong><span>Pending Requests</span></div>
      <div class="stat-card"><strong><?= $todayCount ?></strong><span>Today's Sessions</span></div>
      <div class="stat-card"><strong><?= $faculty['max_daily_sessions'] ?? 12 ?></strong><span>Max Daily</span></div>
    </section>

    <section class="upcoming-queue">
      <h2>Next 3 Students</h2>
      <div class="queue-cards" id="queue-container" data-faculty-id="<?= $facultyId ?>">
        <?php foreach ($upcomingThree as $s): ?>
          <div class="queue-card" data-appointment-id="<?= $s['id'] ?>">
            <div class="token-badge"><?= e($s['token_code'] ?? '') ?></div>
            <div class="student-info">
              <h3><?= e($s['student_name']) ?> (<?= e($s['student_code'] ?? 'N/A') ?>)</h3>
              <p><?= e($s['topic']) ?></p>
              <p><?= format_time_12h($s['scheduled_start_at']) ?> - <?= format_time_12h($s['scheduled_end_at']) ?></p>
              <span class="status-<?= str_replace('_','-',$s['status']) ?>"><?= ucwords(str_replace('_',' ',$s['status'])) ?></span>
            </div>
            <div class="actions">
              <?php if ($s['status'] == 'approved'): ?>
                <button class="btn-start-session" data-id="<?= $s['id'] ?>">Start</button>
              <?php elseif ($s['status'] == 'in_progress'): ?>
                <button class="btn-end-session" data-id="<?= $s['id'] ?>">Complete</button>
                <button class="btn-cancel-session" data-id="<?= $s['id'] ?>">Cancel</button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="today-schedule">
      <h2>Today's Full Schedule</h2>
      <?php if ($todaySessions): ?>
        <div class="table-scroll">
          <table><thead><tr><th>Token</th><th>Student</th><th>Time</th><th>Topic</th><th>Status</th></tr></thead><tbody>
          <?php foreach ($todaySessions as $ts): ?>
            <tr><td><?= e($ts['token_code'] ?? '') ?></td><td><?= e($ts['student_name']) ?></td><td><?= format_time_12h($ts['scheduled_start_at']) ?></td><td><?= e($ts['topic']) ?></td><td><span class="status-<?= str_replace('_','-',$ts['status']) ?>"><?= ucwords(str_replace('_',' ',$ts['status'])) ?></span></td></tr>
          <?php endforeach; ?>
          </tbody></table>
        </div>
      <?php else: ?><p>No sessions today.</p><?php endif; ?>
    </section>
  </main>
  <footer class="site-footer"><!-- footer content --></footer>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>