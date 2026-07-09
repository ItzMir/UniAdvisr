<?php
if (!isset($_SESSION)) session_start();
$profilePhoto = $_SESSION['profile_photo'] ?? null;
$profilePhoto = $profilePhoto ? '../'.$profilePhoto : '../assets/images/profile.png';
?>
<header class="site-header">
  <div class="nav-wrap">
    <a href="../index.php" class="brand">
      <span class="brand-mark">U</span>
      <span class="brand-text"><strong>UniAdvisr</strong><small>Guidance Platform</small></span>
    </a>
    <nav class="nav-menu" id="navMenu">
      <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
      <a href="bookings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : '' ?>">Bookings</a>
      <a href="students.php" class="<?= basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : '' ?>">My Students</a>
      <a href="tokens.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tokens.php' ? 'active' : '' ?>">Tokens</a>
      <a href="availability.php" class="<?= basename($_SERVER['PHP_SELF']) == 'availability.php' ? 'active' : '' ?>">Availability</a>
      <a href="essay_review.php" class="<?= basename($_SERVER['PHP_SELF']) == 'essay_review.php' ? 'active' : '' ?>">Essays</a>
    </nav>
    <div class="nav-actions">
      <div class="profile-dropdown">
        <a href="profile.php" class="profile-link">
          <div class="profile-img" style="background-image: url('<?= $profilePhoto ?>')"></div>
          <span class="profile-name"><?= e($_SESSION['user_name'] ?? 'Faculty') ?></span>
        </a>
      </div>
        <div class="logout">
          <a href="../logout.php" class="btn-logout" >
            <img src="../assets/images/logout.png" alt="Logout" />
          </a>
        </div>
    </div>
    <button class="hamburger" id="menuBtn" aria-label="Menu">☰</button>
  </div>
</header>