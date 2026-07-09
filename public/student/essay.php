<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_student.php';
require_role('student');

$studentId = $_SESSION['user_id'];

// Handle essay upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? '')) {
    $title = trim($_POST['title'] ?? '');
    if (empty($title)) {
        set_flash('error', 'Essay title is required.');
    } elseif (isset($_FILES['essay']) && $_FILES['essay']['error'] === UPLOAD_ERR_OK) {
        $filePath = handle_upload($_FILES['essay'], 'essays');
        if ($filePath) {
            $stmt = $pdo->prepare("INSERT INTO essays (student_user_id, title, description, file_path, status) VALUES (?, ?, ?, ?, 'submitted')");
            $stmt->execute([$studentId, $title, $_POST['description'] ?? '', $filePath]);
            set_flash('success', 'Essay submitted for review!');
        } else {
            set_flash('error', 'File upload failed. Please try again.');
        }
    } else {
        set_flash('error', 'Please select a valid file (PDF, DOC, or TXT).');
    }
    header('Location: essay.php');
    exit;
}

$csrf_token = csrf_token();

// Fetch all essays for this student
$stmt = $pdo->prepare("SELECT * FROM essays WHERE student_user_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$studentId]);
$essays = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Essay Support - UniAdvisr</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
<div class="student-dashboard-shell">

  <main class="student-essay-container">
    <?php $flash = get_flash(); foreach ($flash as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>

    <!-- Upload Section -->
    <section class="essay-upload">
      <h1>Submit an Essay</h1>
      <form method="post" enctype="multipart/form-data" class="essay-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="5242880" /> <!-- 5MB -->
        <div class="form-group">
          <label>Essay Title</label>
          <input type="text" name="title" placeholder="e.g., Statement of Purpose" required>
        </div>
        <div class="form-group">
          <label>Description / Notes (optional)</label>
          <textarea name="description" rows="3" placeholder="Any additional info..."></textarea>
        </div>
        <div class="form-group">
          <label>File Attachment (PDF, DOC, TXT)</label>
          <input type="file" name="essay" required>
          <small class="muted">Max file size: 5MB</small>
        </div>
        <button type="submit" class="btn btn-primary">Upload for Review</button>
      </form>
    </section>

    <!-- Essay History -->
    <section class="essay-list">
      <h2>My Submitted Essays</h2>
      <?php if ($essays): ?>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Feedback</th>
                <th>Download</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($essays as $essay): ?>
                <tr>
                  <td><?= e($essay['title']) ?></td>
                  <td>
                    <span class="status-<?= str_replace('_','-',$essay['status']) ?>">
                      <?= e(ucwords(str_replace('_',' ', $essay['status']))) ?>
                    </span>
                  </td>
                  <td><?= format_datetime($essay['submitted_at']) ?></td>
                  <td>
                    <?php if ($essay['review_comments']): ?>
                      <?= e($essay['review_comments']) ?>
                    <?php else: ?>
                      <span class="muted">No feedback yet</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="../download_file.php?type=essay&id=<?= $essay['id'] ?>" class="btn btn-outline btn-xs">Download</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p>You haven't submitted any essays yet. Upload one above.</p>
      <?php endif; ?>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-container">
      <div class="footer-grid">
        <div>
          <div class="footer-brand">UniAdvisr</div>
          <p>Your trusted university guidance platform.</p>
        </div>
        <div>
          <h4>Quick Links</h4>
          <a href="dashboard.php">Home</a>
          <a href="faculties.php">Faculties</a>
          <a href="booking.php">Booking</a>
          <a href="token.php">Token</a>
          <a href="essay.php">Essay</a>
        </div>
        <div>
          <h4>Support</h4>
          <a href="#">Help Center</a>
          <a href="#">Contact Us</a>
          <a href="#">FAQs</a>
        </div>
        <div>
          <h4>Contact</h4>
          <p>furiousrashid02@gmail.com</p>
          <p>+8801889143752</p>
          <p>24/7 Available</p>
        </div>
      </div>
      <div class="footer-bottom">
        <p>© 2026 UniAdvisr. All rights reserved.</p>
      </div>
    </div>
  </footer>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>