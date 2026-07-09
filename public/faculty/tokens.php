<?php
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_faculty.php';
require_role('faculty');
$facultyId = $_SESSION['user_id'];

if (isset($_POST['cancel_token']) && csrf_verify($_POST['csrf_token'] ?? '')) {
    $apptId = (int)$_POST['appointment_id'];
    $pdo->prepare("UPDATE appointments SET status = 'cancelled_by_faculty', cancelled_by_user_id = ?, queue_position = NULL WHERE id = ? AND faculty_user_id = ?")
        ->execute([$facultyId, $apptId, $facultyId]);

    $stmt = $pdo->prepare("SELECT faculty_user_id, appointment_date FROM appointments WHERE id = ?");
    $stmt->execute([$apptId]);
    $appt = $stmt->fetch();
    if ($appt) {
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
    set_flash('success', 'Token cancelled and queue updated.');
    header('Location: tokens.php');
    exit;
}

$csrf_token = csrf_token();

$tokens = $pdo->prepare("
    SELECT a.*, u.name AS student_name, sp.student_code
    FROM appointments a
    JOIN users u ON u.id = a.student_user_id
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    WHERE a.faculty_user_id = ?
    ORDER BY a.appointment_date DESC, a.token_number DESC
");
$tokens->execute([$facultyId]);
$tokenList = $tokens->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Token Management - UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="faculty-dashboard-shell">
  <main class="faculty-tokens-container">
    <?php foreach (get_flash() as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>
    <h1>Token Queue</h1>
    <?php if ($tokenList): ?>
      <div class="table-scroll">
        <table>
          <thead><tr><th>Token Code</th><th>Token #</th><th>Student</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($tokenList as $t): ?>
              <tr>
                <td><?= e($t['token_code'] ?? '') ?></td>
                <td><?= $t['token_number'] ?></td>
                <td><?= e($t['student_name']) ?> (<?= e($t['student_code']) ?>)</td>
                <td><?= e($t['appointment_date']) ?></td>
                <td><?= format_time_12h($t['scheduled_start_at']) ?></td>
                <td><?= e($t['status']) ?></td>
                <td>
                  <?php if (!in_array($t['status'], ['cancelled_by_faculty','completed','declined'])): ?>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                      <input type="hidden" name="appointment_id" value="<?= $t['id'] ?>">
                      <button type="submit" name="cancel_token" class="btn btn-xs btn-danger">Cancel Token</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p>No active tokens.</p>
    <?php endif; ?>
  </main>
  <footer class="site-footer"><!-- footer content same as original --></footer>
</div>
</body>
</html>