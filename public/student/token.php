<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_student.php';
require_role('student');

$studentId = $_SESSION['user_id'];

if (isset($_GET['cancel'], $_GET['id']) && csrf_verify($_GET['token'] ?? '')) {
    $apptId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND student_user_id = ?");
    $stmt->execute([$apptId, $studentId]);
    $appt = $stmt->fetch();
    if ($appt && in_array($appt['status'], ['pending','approved','in_progress'])) {
        $pdo->prepare("UPDATE appointments SET status = 'cancelled_by_student', cancelled_by_user_id = ?, cancelled_reason = 'Cancelled by student', queue_position = NULL WHERE id = ?")
            ->execute([$studentId, $apptId]);

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

        set_flash('success', 'Token cancelled.');
    } else {
        set_flash('error', 'Cannot cancel this token.');
    }
    header('Location: token.php');
    exit;
}

$csrf_token = csrf_token();

$stmt = $pdo->prepare("
    SELECT a.*, u.name AS faculty_name
    FROM appointments a
    JOIN users u ON u.id = a.faculty_user_id
    WHERE a.student_user_id = ? AND a.token_code IS NOT NULL
    ORDER BY a.appointment_date DESC, a.token_number DESC
");
$stmt->execute([$studentId]);
$tokens = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>My Tokens – UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="student-dashboard-shell">
  <main class="student-token-container">
    <?php $flash = get_flash(); foreach ($flash as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>
    <h1>My Token Queue</h1>
    <p class="subtitle">Unique token codes for your appointments.</p>
    <?php if ($tokens): ?>
      <div class="student-token-list">
        <?php foreach ($tokens as $token): ?>
          <div class="student-token-card">
            <div class="token-number"><?= e($token['token_code']) ?></div>
            <div class="token-details">
              <p><strong>Advisor:</strong> <?= e($token['faculty_name']) ?></p>
              <p><strong>Date:</strong> <?= e($token['appointment_date']) ?> &nbsp; <?= format_time_12h($token['scheduled_start_at']) ?></p>
              <p><strong>Topic:</strong> <?= e($token['topic']) ?></p>
              <p><strong>Status:</strong> <span class="status-<?= str_replace('_','-',$token['status']) ?>"><?= ucwords(str_replace('_',' ',$token['status'])) ?></span></p>
            </div>
            <div class="token-actions">
              <?php if (in_array($token['status'], ['pending','approved','in_progress'])): ?>
                <a href="?cancel=1&id=<?= $token['id'] ?>&token=<?= $csrf_token ?>" class="btn btn-danger btn-xs" onclick="return confirm('Cancel this token?')">Cancel</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No tokens yet. <a href="booking.php" class="btn btn-primary">Book an appointment</a></p>
    <?php endif; ?>
  </main>
  <footer class="site-footer"><!-- footer --></footer>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>