<?php
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_faculty.php';
require_role('faculty');
$facultyId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? '')) {
    $essayId = (int)$_POST['essay_id'];
    $status = $_POST['status'];
    $comments = $_POST['review_comments'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO essay_reviews (essay_id, reviewed_by_user_id, status, comments) VALUES (?, ?, ?, ?)");
    $stmt->execute([$essayId, $facultyId, $status, $comments]);
    $pdo->prepare("UPDATE essays SET status = ?, reviewed_by_user_id = ?, reviewed_at = NOW() WHERE id = ?")
        ->execute([$status, $facultyId, $essayId]);
    set_flash('success', 'Review submitted.');
    header('Location: essay_review.php');
    exit;
}

$csrf = csrf_token();
$essays = $pdo->query("SELECT e.*, u.name AS student_name FROM essays e JOIN users u ON u.id = e.student_user_id WHERE e.status IN ('submitted','pending_review') ORDER BY e.submitted_at ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Essay Review - UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="faculty-dashboard-shell">
  <main class="faculty-essay-container">
    <?php foreach (get_flash() as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>
    <h1>Essays Pending Review</h1>
    <?php if ($essays): ?>
      <div class="essay-review-list">
        <?php foreach ($essays as $essay): ?>
          <div class="essay-review-card">
            <h3><?= e($essay['title']) ?></h3>
            <p><strong>Student:</strong> <?= e($essay['student_name']) ?> | <strong>Submitted:</strong> <?= format_datetime($essay['submitted_at']) ?></p>
            <a href="../download_file.php?type=essay&id=<?= $essay['id'] ?>" class="btn btn-outline">Download Essay</a>
            <form method="post" class="review-form">
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <input type="hidden" name="essay_id" value="<?= $essay['id'] ?>">
              <select name="status" required>
                <option value="reviewed">✅ Mark as Reviewed</option>
                <option value="revision_requested">🔄 Request Revision</option>
              </select>
              <textarea name="review_comments" placeholder="Write your feedback here..." rows="4"></textarea>
              <button type="submit" class="btn btn-primary">Submit Review</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="glass-card" style="padding:30px; text-align:center;">
        <p>No essays pending review. Great job!</p>
      </div>
    <?php endif; ?>
  </main>
  <footer class="site-footer">...</footer>
</div>
</body>
</html>