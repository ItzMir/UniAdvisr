<?php
require_once __DIR__ . '/../../app/includes/init.php';
$facultyId = (int)($_GET['faculty_id'] ?? 0); //gets faculty id from query string, defaults to 0 if not set
if (!$facultyId || !is_logged_in()) exit;
// Fetch next 3 upcoming appointments for the faculty member
$stmt = $pdo->prepare("SELECT a.*, u.name AS student_name, sp.student_code FROM appointments a JOIN users u ON u.id = a.student_user_id LEFT JOIN student_profiles sp ON sp.user_id = u.id WHERE a.faculty_user_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ('approved','in_progress') ORDER BY a.appointment_date, COALESCE(a.scheduled_start_at, CONCAT(a.appointment_date,' 23:59:59')), a.queue_position LIMIT 3");
$stmt->execute([$facultyId]);
$queue = $stmt->fetchAll();
foreach ($queue as $s):
?>
<div class="queue-card" data-appointment-id="<?= $s['id'] ?>">
  <div class="token-badge">T<?= str_pad((string)$s['token_code'],3,'0',STR_PAD_LEFT) ?></div>
  <div class="student-info">
    <h3><?= e($s['student_name']) ?> (<?= e($s['student_code'] ?? 'N/A') ?>)</h3>
    <p><?= e($s['topic']) ?></p>
    <p><?= format_time_12h($s['scheduled_start_at']) ?> - <?= format_time_12h($s['scheduled_end_at']) ?></p>
    <span class="status-<?= str_replace('_','-',$s['status']) ?>"><?= e(ucwords(str_replace('_',' ',$s['status']))) ?></span>
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