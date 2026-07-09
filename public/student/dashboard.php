<?php
declare(strict_types=1); // Enable strict types for better type safety

require_once __DIR__ . '/../../app/includes/init.php';// Initialize application and database connection
require_once __DIR__ . '/../../app/templates/header_student.php';

if (!isset($_SESSION['user_id'])/*Not Logged in?*/ || ($_SESSION['role'] ?? '') !== 'student'/*Not a student?*/) {
    header('Location: ../login.php');
    exit;// terminate the script after redirecting to prevent further execution
}

$studentId = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT sp.*, d.name AS department_name, d.code AS department_code,
           u.name, u.email, u.profile_photo
    FROM student_profiles sp
    INNER JOIN users u ON u.id = sp.user_id
    LEFT JOIN departments d ON d.id = sp.department_id
    WHERE sp.user_id = ?
    LIMIT 1
");// Fetch student profile along with department and user info
$stmt->execute([$studentId]);// Execute the prepared statement with the student ID as a parameter
$student = $stmt->fetch() ?: [];//fetch one row or return empty array if not found

$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.phone, u.profile_photo,
           fp.designation, fp.specialization, fp.office_location,
           fp.max_daily_sessions, d.name AS department_name
    FROM student_faculty_assignments sfa
    INNER JOIN users u ON u.id = sfa.faculty_user_id
    LEFT JOIN faculty_profiles fp ON fp.user_id = u.id
    LEFT JOIN departments d ON d.id = fp.department_id
    WHERE sfa.student_user_id = ?
      AND u.is_active = 1
      AND (sfa.end_date IS NULL OR sfa.end_date >= CURDATE())
    ORDER BY sfa.is_primary DESC, sfa.created_at ASC
");// Fetch assigned faculties for the student, prioritizing primary assignments and active faculties
$stmt->execute([$studentId]);
$assignedFaculties = $stmt->fetchAll();// Fetch all matching rows as an array of associative arrays

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_user_id = ? AND status <> 'cancelled_by_student'");// Count all non-cancelled appointments for the student, ? is a placeholder for the student ID parameter, <> means "not equal to"
$stmt->execute([$studentId]);//from this $studentID we get the placeholder value and execute the query
$bookedAppointments = (int) $stmt->fetchColumn();//only fetch the first column of the first row of the result set, which is the count of booked appointments

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_user_id = ? AND appointment_date = CURDATE() AND status IN ('approved','in_progress','completed')");
$stmt->execute([$studentId]);
$todaysAppointments = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT a.*, u.name AS faculty_name
    FROM appointments a
    INNER JOIN users u ON u.id = a.faculty_user_id
    WHERE a.student_user_id = ?
      AND a.status IN ('approved','in_progress')
      AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, COALESCE(a.scheduled_start_at, CONCAT(a.appointment_date, ' 23:59:59')) ASC, a.token_code ASC
    LIMIT 1
");// Fetch the next upcoming appointment for the student, prioritizing by date, then scheduled time (or end of day if not set), then token code
$stmt->execute([$studentId]);
$upcomingAppointment = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT a.token_code, a.appointment_date, a.scheduled_start_at, a.status, u.name AS faculty_name
    FROM appointments a
    INNER JOIN users u ON u.id = a.faculty_user_id
    WHERE a.student_user_id = ?
      AND a.token_code IS NOT NULL
    ORDER BY a.appointment_date DESC, a.token_code DESC
    LIMIT 3
");// Fetch the 3 most recent tokens for the student, along with faculty name and appointment details
$stmt->execute([$studentId]);
$tokens = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT title, status, submitted_at FROM essays WHERE student_user_id = ? ORDER BY submitted_at DESC LIMIT 1");// Fetch the most recent essay submitted by the student, along with its status and submission time
$stmt->execute([$studentId]);
$latestEssay = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM essays WHERE student_user_id = ? AND status IN ('pending_review','submitted')");
$stmt->execute([$studentId]);
$pendingEssays = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT message, type, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$studentId]);
$notifications = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_user_id = ?");
$stmt->execute([$studentId]);
$totalAppointments = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_user_id = ? AND appointment_date > CURDATE()");
$stmt->execute([$studentId]);
$upcomingAppointments = (int) $stmt->fetchColumn();

$pastAppointments = max(0, $totalAppointments - $upcomingAppointments);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_user_id = ? AND token_code IS NOT NULL");
$stmt->execute([$studentId]);
$totalTokens = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_user_id = ? AND status IN ('completed','in_progress') AND token_code IS NOT NULL");
$stmt->execute([$studentId]);
$usedTokens = (int) $stmt->fetchColumn();

$remainingTokens = max(0, $totalTokens - $usedTokens);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM essays WHERE student_user_id = ?");
$stmt->execute([$studentId]);
$totalEssays = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM essays WHERE student_user_id = ? AND status IN ('pending_review','submitted')");
$stmt->execute([$studentId]);
$pendingEssayCount = (int) $stmt->fetchColumn();

$reviewedEssays = max(0, $totalEssays - $pendingEssayCount);

$profileCompletion = (int) ($student['profile_completion'] ?? 0);// Get profile completion percentage, defaulting to 0 if not set
$profilePhoto = $student['profile_photo'] ?? $_SESSION['profile_photo'] ?? null;// Determine the profile photo to use, prioritizing the one from the student profile, then session, and defaulting to null if neither is set
if (!$profilePhoto) {
    $profilePhoto = '../assets/images/profile.png';
}

$formatToken = static fn ($token) => 'T' . str_pad((string) $token, 3, '0', STR_PAD_LEFT);// Helper function to format token codes as T001, T002, etc.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Dashboard - UniAdvisr</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>

<body>
<div class="student-dashboard-shell">
  <main>
    <section class="hero">
      <div class="hero-tabs">
        <div class="info-cards">
          <div class="info-card">
            <strong>Booked<br>Appointments</strong>
            <span><?= $bookedAppointments ?></span>
          </div>
          <div class="info-card">
            <strong>Today's<br>Appointments</strong>
            <span><?= $todaysAppointments ?></span>
          </div>
          <div class="info-card">
            <strong>Next Token At</strong>
            <span class="token-time">
              <?= $upcomingAppointment && !empty($upcomingAppointment['scheduled_start_at'])
                ? format_time_12h($upcomingAppointment['scheduled_start_at'])
                : '~~' ?> <!-- Display the next token time if available, otherwise show placeholder -->
            </span>
          </div>
          <div class="info-card">
            <strong>Your Faculties</strong>
            <div class="faculties">
              <?php if ($assignedFaculties): ?>
                <?php foreach ($assignedFaculties as $faculty): ?>
                  <span><?= e($faculty['name']) ?></span> <!-- Display the name of each assigned faculty, ensuring it's proper for HTML output -->
                <?php endforeach; ?>
              <?php else: ?>
                <span>None assigned</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="info-card">
            <strong>Pending Reviews</strong>
            <span><?= $pendingEssays ?></span>
          </div>
        </div>
        <div class="bar">
          <label for="progress-bar">Profile completion:</label>
          <progress id="progress-bar" value="<?= $profileCompletion ?>" max="100"><?= $profileCompletion ?>%</progress> <!-- Display a progress bar for profile completion, with the value set to the calculated percentage -->
        </div>
        <div class="boxes">
          <div class="box1">
            <h2>Upcoming Appointment</h2>
            <?php if ($upcomingAppointment): ?>
              <p><strong>Advisor:</strong> <?= e($upcomingAppointment['faculty_name']) ?></p> <!-- Display the name of the faculty advisor for the upcoming appointment -->
              <p><strong>Date:</strong> <?= e($upcomingAppointment['appointment_date']) ?></p> <!-- Display the date of the upcoming appointment -->
              <p><strong>Time:</strong> <?= $upcomingAppointment['scheduled_start_at'] ? format_time_12h($upcomingAppointment['scheduled_start_at']) : 'Pending' ?></p> <!-- Display the time of the upcoming appointment, or 'Pending' if not available -->
            <?php else: ?>
              <p>No upcoming appointments.</p>
            <?php endif; ?>
          </div>
          <div class="box2">
            <h2>My Tokens</h2>
            <?php if ($tokens): ?>
              <?php foreach ($tokens as $token): ?>
                <p>
                  <strong>Token <?= (int) $token['token_code'] ?>:</strong> <!-- Display the token code for each token, ensuring it's an integer for proper formatting -->
                  <?= e($formatToken((int) $token['token_code'])) ?> → <?= e($token['faculty_name']) ?>, <!-- Display the formatted token code and the associated faculty name -->
                  <?= date('M j', strtotime($token['appointment_date'])) ?>, <!-- Display the appointment date in a 'Jun 11' format -->
                  <?= $token['scheduled_start_at'] ? format_time_12h($token['scheduled_start_at']) : 'Pending' ?>
                </p>
              <?php endforeach; ?>
            <?php else: ?>
              <p>No active tokens right now.</p>
            <?php endif; ?>
          </div>
          <div class="box3">
            <h2>Essay Review</h2>
            <?php if ($latestEssay): ?>
              <p><strong>Essay:</strong> "<?= e($latestEssay['title']) ?>"</p>
              <p><strong>Status:</strong> <?= e(ucwords(str_replace('_', ' ', $latestEssay['status']))) ?></p>
            <?php else: ?>
              <p>No essay submitted.</p>
            <?php endif; ?>
          </div>
          <div class="box4">
            <h2>Notifications</h2>
            <?php if ($notifications): ?>
              <?php foreach ($notifications as $notification): ?>
                <p><strong>Notification:</strong> <?= e($notification['message']) ?></p>
              <?php endforeach; ?>
            <?php else: ?>
              <p>You're all caught up!</p>
            <?php endif; ?>
          </div>
        </div>
        <div class="hero-buttons">
          <a href="faculties.php" class="btn-dashboard">🧑‍🏫 All Faculties</a>
          <a href="booking.php" class="btn-dashboard">📅 All Appointments</a>
          <a href="token.php" class="btn-dashboard">🎫 All Tokens</a>
          <a href="essay.php" class="btn-dashboard">📝 All Essays</a>
        </div>
        <div class="reports">
          <div class="report-card">
            <h3>Appointment Summary</h3>
            <p><strong>Total:</strong> <?= $totalAppointments ?> <span>(Upcoming: <?= $upcomingAppointments ?>, Past: <?= $pastAppointments ?>)</span></p>
          </div>
          <div class="report-card">
            <h3>Token Summary</h3>
            <p><strong>Total:</strong> <?= $totalTokens ?> <span>(Used: <?= $usedTokens ?>, Remaining: <?= $remainingTokens ?>)</span></p>
          </div>
          <div class="report-card">
            <h3>Essay Review</h3>
            <p><strong>Total:</strong> <?= $totalEssays ?> <span>(Pending: <?= $pendingEssayCount ?>, Reviewed: <?= $reviewedEssays ?>)</span></p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-container">
      <div class="footer-grid">
        <div>
          <div class="footer-brand">UniAdvisr</div>
          <p>Your trusted university guidance platform for better academic decisions.</p>
          <div class="social-links">
            <a href="#">Facebook</a>
            <a href="#">Twitter</a>
            <a href="#">LinkedIn</a>
          </div>
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
  const menuBtn = document.getElementById("menuBtn");
  const navMenu = document.getElementById("navMenu");

  if (menuBtn && navMenu) {
    menuBtn.addEventListener("click", () => {
      navMenu.classList.toggle("active");
      const expanded = menuBtn.getAttribute("aria-expanded") === "true";
      menuBtn.setAttribute("aria-expanded", String(!expanded));
    });
  }
</script>
</body>
</html>