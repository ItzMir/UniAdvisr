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
      <a href="faculties.php" class="<?= basename($_SERVER['PHP_SELF']) == 'faculties.php' ? 'active' : '' ?>">Faculties</a>
      <a href="booking.php" class="<?= basename($_SERVER['PHP_SELF']) == 'booking.php' ? 'active' : '' ?>">Booking</a>
      <a href="token.php" class="<?= basename($_SERVER['PHP_SELF']) == 'token.php' ? 'active' : '' ?>">Token</a>
      <a href="essay.php" class="<?= basename($_SERVER['PHP_SELF']) == 'essay.php' ? 'active' : '' ?>">Essay</a>
    </nav>
    <div class="nav-actions">
      <div class="profile-dropdown">
        <a href="profile.php" class="profile-link">
          <div class="profile-img" style="background-image: url('<?= $profilePhoto ?>')"></div>
          <span class="profile-name"><?= e($_SESSION['user_name'] ?? 'Profile') ?></span>
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