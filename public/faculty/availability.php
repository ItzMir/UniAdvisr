<?php
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_faculty.php';
require_role('faculty');
$facultyId = $_SESSION['user_id'];

// Add weekly rule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rule']) && csrf_verify($_POST['csrf_token'] ?? '')) {
    $day = (int)$_POST['day'];
    $start = $_POST['start_time'] . ':00';
    $end = $_POST['end_time'] . ':00';
    $stmt = $pdo->prepare("INSERT INTO faculty_availability_rules (faculty_user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
    $stmt->execute([$facultyId, $day, $start, $end]);
    set_flash('success', 'Availability rule added.');
    header('Location: availability.php');
    exit;
}

if (isset($_POST['add_unavailability']) && csrf_verify($_POST['csrf_token'] ?? '')) {
    $date = $_POST['unavailable_date'];
    $start = $_POST['start_time'] . ':00';
    $end = $_POST['end_time'] . ':00';
    $reason = $_POST['reason'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO faculty_unavailability (faculty_user_id, unavailable_date, start_time, end_time, reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$facultyId, $date, $start, $end, $reason]);
    set_flash('success', 'Unavailability block added.');
    header('Location: availability.php');
    exit;
}

// Delete rule
if (isset($_GET['delete_rule']) && csrf_verify($_GET['token'] ?? '')) {
    $ruleId = (int)$_GET['delete_rule'];
    $pdo->prepare("DELETE FROM faculty_availability_rules WHERE id = ? AND faculty_user_id = ?")->execute([$ruleId, $facultyId]);
    set_flash('success', 'Rule removed.');
    header('Location: availability.php');
    exit;
}

// Delete unavailability
if (isset($_GET['delete_unavail']) && csrf_verify($_GET['token'] ?? '')) {
    $unavId = (int)$_GET['delete_unavail'];
    $pdo->prepare("DELETE FROM faculty_unavailability WHERE id = ? AND faculty_user_id = ?")->execute([$unavId, $facultyId]);
    set_flash('success', 'Block removed.');
    header('Location: availability.php');
    exit;
}

$csrf = csrf_token();

// Fetch current rules
$rules = $pdo->prepare("SELECT * FROM faculty_availability_rules WHERE faculty_user_id = ? AND is_active = 1 ORDER BY day_of_week, start_time");
$rules->execute([$facultyId]);
$rulesList = $rules->fetchAll();

$unavails = $pdo->prepare("SELECT * FROM faculty_unavailability WHERE faculty_user_id = ? AND unavailable_date >= CURDATE() ORDER BY unavailable_date, start_time");
$unavails->execute([$facultyId]);
$unavailList = $unavails->fetchAll();

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Availability - UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="faculty-dashboard-shell">
  <main class="faculty-availability-container">
    <?php foreach (get_flash() as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>
    <section>
      <h2>Weekly Availability</h2>
      <form method="post" class="inline-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <select name="day">
          <?php foreach ($days as $i => $d): ?>
            <option value="<?= $i ?>"><?= $d ?></option>
          <?php endforeach; ?>
        </select>
        <input type="time" name="start_time" required>
        <input type="time" name="end_time" required>
        <button type="submit" name="add_rule" class="btn btn-primary">Add Rule</button>
      </form>
      <table>
        <thead><tr><th>Day</th><th>Start</th><th>End</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($rulesList as $r): ?>
            <tr>
              <td><?= $days[$r['day_of_week']] ?></td>
              <td><?= format_time_12h($r['start_time']) ?></td>
              <td><?= format_time_12h($r['end_time']) ?></td>
              <td><a href="?delete_rule=<?= $r['id'] ?>&token=<?= $csrf ?>" onclick="return confirm('Delete?')" class="btn btn-xs btn-danger">Delete</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <section style="margin-top:40px;">
      <h2>Unavailability Blocks</h2>
      <form method="post" class="inline-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="date" name="unavailable_date" min="<?= date('Y-m-d') ?>" required>
        <input type="time" name="start_time" required>
        <input type="time" name="end_time" required>
        <input type="text" name="reason" placeholder="Reason (optional)" style="width:200px;">
        <button type="submit" name="add_unavailability" class="btn btn-primary">Block Time</button>
      </form>
      <table>
        <thead><tr><th>Date</th><th>Start</th><th>End</th><th>Reason</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($unavailList as $u): ?>
            <tr>
              <td><?= e($u['unavailable_date']) ?></td>
              <td><?= format_time_12h($u['start_time']) ?></td>
              <td><?= format_time_12h($u['end_time']) ?></td>
              <td><?= e($u['reason'] ?? '') ?></td>
              <td><a href="?delete_unavail=<?= $u['id'] ?>&token=<?= $csrf ?>" onclick="return confirm('Delete?')" class="btn btn-xs btn-danger">Delete</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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