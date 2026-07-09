<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_student.php';
require_role('student');

$userId = $_SESSION['user_id'];

// Fetch current profile data
$stmt = $pdo->prepare("
    SELECT u.name, u.email, u.phone, u.profile_photo,
           sp.*, d.name AS dept_name
    FROM users u
    JOIN student_profiles sp ON sp.user_id = u.id
    LEFT JOIN departments d ON d.id = sp.department_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$student = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? '')) {
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $program = $_POST['program_name'] ?? '';
    $batch = $_POST['batch_year'] ?? '';
    $section = $_POST['class_section'] ?? '';
    $cgpa = $_POST['cgpa'] ?? '';

    // Update users table
    $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
    $stmt->execute([$phone, $userId]);

    // Update student_profiles
    $stmt = $pdo->prepare("UPDATE student_profiles SET phone = ?, address = ?, program_name = ?, batch_year = ?, class_section = ?, cgpa = ?, profile_completion = 85 WHERE user_id = ?");
    $stmt->execute([$phone, $address, $program, $batch ?: null, $section, $cgpa ?: null, $userId]);

    // Photo upload
    if (!empty($_FILES['photo']['tmp_name'])) {
        $photoPath = handle_upload($_FILES['photo'], 'profile_photos');
        if ($photoPath) {
            $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->execute([$photoPath, $userId]);
            $_SESSION['profile_photo'] = $photoPath; // update session
        }
    }

    set_flash('success', 'Profile updated successfully!');
    header('Location: profile.php');
    exit;
}

$csrf_token = csrf_token();
$profilePhoto = $student['profile_photo'] ?? null;
if (!$profilePhoto) {
    $profilePhoto = '../assets/images/profile.png';
} else {
    $profilePhoto = '../' . $profilePhoto;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>My Profile - UniAdvisr</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
<div class="student-dashboard-shell">

  <main class="student-profile-container">
    <?php $flash = get_flash(); foreach ($flash as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>
    <h1>My Profile</h1>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

      <!-- Photo section -->
      <div class="profile-photo-section">
        <img src="<?= $profilePhoto ?>" alt="Profile Photo" class="profile-avatar-large" id="profile-preview">
        <div class="photo-upload-btn">
          <input type="file" name="photo" id="photo-input" accept="image/*" onchange="previewPhoto(this)">
          <label for="photo-input" class="btn btn-outline">Change Photo</label>
        </div>
      </div>

      <!-- Non-editable fields -->
      <div class="form-group">
        <label>Student Code</label>
        <input type="text" value="<?= e($student['student_code']) ?>" disabled>
      </div>
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" value="<?= e($student['name']) ?>" disabled>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" value="<?= e($student['email']) ?>" disabled>
      </div>
      <div class="form-group">
        <label>Department</label>
        <input type="text" value="<?= e($student['dept_name'] ?? 'Not assigned') ?>" disabled>
      </div>

      <!-- Editable fields -->
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= e($student['phone']) ?>" placeholder="Enter phone number">
      </div>
      <div class="form-group">
        <label>Program Name</label>
        <input type="text" name="program_name" value="<?= e($student['program_name']) ?>" placeholder="e.g., B.Sc. in CSE">
      </div>
      <div class="form-group">
        <label>Batch Year</label>
        <input type="number" name="batch_year" value="<?= e($student['batch_year']) ?>" placeholder="e.g., 2026" min="2010" max="2030">
      </div>
      <div class="form-group">
        <label>Class Section</label>
        <input type="text" name="class_section" value="<?= e($student['class_section']) ?>" placeholder="e.g., A">
      </div>
      <div class="form-group">
        <label>CGPA</label>
        <input type="number" step="0.01" name="cgpa" value="<?= e($student['cgpa']) ?>" placeholder="e.g., 3.75" min="0" max="4">
      </div>
      <div class="form-group">
        <label>Address</label>
        <textarea name="address" rows="3" placeholder="Your address"><?= e($student['address']) ?></textarea>
      </div>

      <div class="form-group">
        <label>Profile Completion</label>
        <progress value="<?= (int)($student['profile_completion'] ?? 60) ?>" max="100" style="width:100%;"><?= $student['profile_completion'] ?? 60 ?>%</progress>
      </div>

      <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
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

<script>
  // Photo preview function
  function previewPhoto(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('profile-preview').src = e.target.result;
      };
      reader.readAsDataURL(input.files[0]);
    }
  }
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>