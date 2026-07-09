<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_faculty.php';
require_role('faculty');

$facultyId = $_SESSION['user_id'];

if (isset($_GET['action'], $_GET['id']) && csrf_verify($_GET['token'] ?? '')) {
    $action = $_GET['action'];
    $apptId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND faculty_user_id = ?");
    $stmt->execute([$apptId, $facultyId]);
    $appt = $stmt->fetch();
    if ($appt && in_array($action, ['approve','decline'])) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE appointments SET status = 'approved', approved_by_user_id = ?, approved_at = NOW() WHERE id = ?")
                ->execute([$facultyId, $apptId]);
        } else {
            // Decline: remove from active queue
            $pdo->prepare("UPDATE appointments SET status = 'declined', cancelled_by_user_id = ?, cancelled_reason = 'Declined by faculty', queue_position = NULL WHERE id = ?")
                ->execute([$facultyId, $apptId]);

            // Reorder queue for this faculty/date
            $pdo->exec("SET @pos = 0");
            $pdo->prepare("
                UPDATE appointments a
                JOIN (
                    SELECT id, @pos := @pos + 1 AS new_pos
                    FROM appointments
                    WHERE faculty_user_id = ?
                      AND appointment_date = ?
                      AND status IN ('pending','approved','in_progress')
                    ORDER BY scheduled_start_at, token_number
                ) AS sorted ON a.id = sorted.id
                SET a.queue_position = sorted.new_pos
            ")->execute([$appt['faculty_user_id'], $appt['appointment_date']]);
        }
        set_flash('success', "Booking $action" . "d.");
    }
    header('Location: bookings.php');
    exit;
}

$csrf_token = csrf_token();

$stmt = $pdo->prepare("
    SELECT a.*, u.name AS student_name, sp.student_code
    FROM appointments a
    JOIN users u ON u.id = a.student_user_id
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    WHERE a.faculty_user_id = ?
    ORDER BY a.appointment_date, a.scheduled_start_at
");
$stmt->execute([$facultyId]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Manage Bookings - UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="faculty-dashboard-shell">
  <main class="faculty-bookings-container">
    <?php foreach (get_flash() as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>
    <h1>Booking Requests</h1>
    <?php if ($bookings): ?>
      <div class="table-scroll">
        <table>
          <thead>
            <tr><th>Token</th><th>Student</th><th>Date</th><th>Time</th><th>Topic</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td><?= e($b['token_code'] ?? '') ?></td>
              <td><?= e($b['student_name']) ?> (<?= e($b['student_code']) ?>)</td>
              <td><?= e($b['appointment_date']) ?></td>
              <td><?= format_time_12h($b['scheduled_start_at']) ?></td>
              <td><?= e($b['topic']) ?></td>
              <td><span class="status-<?= str_replace('_','-',$b['status']) ?>"><?= ucwords(str_replace('_',' ',$b['status'])) ?></span></td>
              <td>
                <?php if ($b['status'] === 'pending'): ?>
                  <a href="?action=approve&id=<?= $b['id'] ?>&token=<?= $csrf_token ?>" class="btn btn-xs btn-primary">Approve</a>
                  <a href="?action=decline&id=<?= $b['id'] ?>&token=<?= $csrf_token ?>" class="btn btn-xs btn-danger">Decline</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p>No booking requests.</p>
    <?php endif; ?>
  </main>
  <footer class="site-footer"><!-- footer same as original --></footer>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>