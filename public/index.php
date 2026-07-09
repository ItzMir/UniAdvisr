<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/includes/init.php';

$today = date('Y-m-d');

// Dynamic stats
$totalAdvisors      = (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code = 'faculty' AND u.is_active = 1")->fetchColumn();
$totalDepartments   = (int) $pdo->query("SELECT COUNT(*) FROM departments WHERE is_active = 1")->fetchColumn();
$totalStudents      = (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code = 'student' AND u.is_active = 1")->fetchColumn();
$totalTodaySessions = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status IN ('approved','in_progress','completed')")->fetchColumn();

$loggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;

// Determine dashboard link
if ($loggedIn) {
    $dashboardUrl = match ($userRole) {
        'student' => 'student/dashboard.php',
        'faculty' => 'faculty/dashboard.php',
        'admin'   => 'admin/dashboard.php',
        default   => 'login.php',
    };
} else {
    $dashboardUrl = 'login.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UniAdvisr — Smart University Guidance</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>

<body>
<div class="homepage-shell">
  <header class="site-header">
    <div class="nav-wrap">
      <a href="index.php" class="brand">
        <span class="brand-mark">U</span>
        <span class="brand-text">
          <strong>UniAdvisr</strong>
          <small>University Guidance Platform</small>
        </span>
      </a>

      <nav class="nav-menu" id="navMenu">
        <a class="active" href="index.php">Home</a>
        <a href="login.php">Faculties</a>
        <a href="login.php">Booking</a>
        <a href="login.php">Token</a>
        <a href="login.php">Essay</a>
      </nav>

      <div class="nav-actions">
        <?php if ($loggedIn): ?>
          <a href="<?= e($dashboardUrl) ?>" class="btn btn-primary">Dashboard</a>
          <a href="logout.php" class="btn btn-secondary">Logout</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-primary">Login</a>
          <a href="register.php" class="btn btn-primary">Register</a>
        <?php endif; ?>
      </div>

      <button class="hamburger" id="menuBtn" aria-label="Menu" aria-expanded="false">☰</button>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="hero-content">
        <span class="eyebrow">Smart admissions support</span>
        <h1>Plan your future with <span>confidence</span></h1>
        <p class="subtitle">
          Book advisors, explore faculties, manage tokens, and get the right guidance for your university journey.
        </p>

        <div class="hero-buttons">
          <a href="register.php" class="btn btn-solid">Get Started</a>
          <a href="<?= $loggedIn ? 'student/booking.php' : 'login.php' ?>" class="btn btn-solid">Book Advisor</a>
        </div>

        <div class="stats">
          <div class="stat-card stat-card--glow">
            <span class="stat-number"><?= $totalAdvisors ?>+</span>
            <span class="stat-label">Advisors</span>
          </div>
          <div class="stat-card stat-card--glow">
            <span class="stat-number"><?= $totalDepartments ?>+</span>
            <span class="stat-label">Departments</span>
          </div>
          <div class="stat-card stat-card--glow">
            <span class="stat-number"><?= $totalTodaySessions ?>+</span>
            <span class="stat-label">Today's Sessions</span>
          </div>
          <div class="stat-card stat-card--glow">
            <span class="stat-number"><?= $totalStudents ?>+</span>
            <span class="stat-label">Students</span>
          </div>
        </div>
      </div>

      <aside class="hero-panel">
        <div class="panel-header">
          <span class="status-dot"></span>
          <span>Quick Access</span>
        </div>

        <div class="panel-list">
          <div class="panel-item">
            <div class="panel-index">01</div>
            <div>
              <h4>Explore faculties</h4>
              <p>Compare programs, departments, and advisor availability easily.</p>
            </div>
          </div>
          <div class="panel-item">
            <div class="panel-index">02</div>
            <div>
              <h4>Book sessions</h4>
              <p>Reserve an advisor meeting in just a few clicks.</p>
            </div>
          </div>
          <div class="panel-item">
            <div class="panel-index">03</div>
            <div>
              <h4>Manage submissions</h4>
              <p>Track tokens and submit essays without confusion.</p>
            </div>
          </div>
        </div>

        <div class="panel-footer">
          <div>
            <strong>15 min</strong>
            <span>Per session</span>
          </div>
          <div>
            <strong>Live</strong>
            <span>Queue updates</span>
          </div>
        </div>
      </aside>
    </section>

    <section class="features">
      <div class="section-heading">
        <span class="eyebrow">Why UniAdvisr</span>
        <h2>Everything you need for smooth university planning</h2>
        <p>A modern platform that makes counseling faster, easier, and better organized.</p>
      </div>

      <div class="feature-grid">
        <article class="feature-card">
          <div class="feature-icon">🎓</div>
          <h4>Faculty Discovery</h4>
          <p>Browse departments and advisors with a clean, structured layout.</p>
        </article>
        <article class="feature-card">
          <div class="feature-icon">📅</div>
          <h4>Advisor Booking</h4>
          <p>Schedule sessions and keep the guidance process streamlined.</p>
        </article>
        <article class="feature-card">
          <div class="feature-icon">🎫</div>
          <h4>Token System</h4>
          <p>Keep token management simple, visible, and easy to follow.</p>
        </article>
        <article class="feature-card">
          <div class="feature-icon">📝</div>
          <h4>Essay Support</h4>
          <p>Submit and review essays in a guided and user-friendly flow.</p>
        </article>
      </div>
    </section>

    <section class="cta">
      <div class="cta-inner">
        <h2>Ready to Start Your Journey?</h2>
        <p>Join students and faculty who keep counseling organized with UniAdvisr.</p>
        <a href="<?= $loggedIn ? e($dashboardUrl) : 'register.php' ?>" class="btn btn-primary">Get Started Now →</a>
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
          <a href="#">Home</a>
          <a href="#">Faculties</a>
          <a href="#">Booking</a>
          <a href="#">Token</a>
          <a href="#">Essay</a>
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