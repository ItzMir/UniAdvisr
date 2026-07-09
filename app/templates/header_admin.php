<?php
if (!isset($_SESSION)) session_start();
?>
<header class="site-header">
  <div class="nav-wrap">
    <a href="../index.php" class="brand">
      <span class="brand-mark">U</span>
      <span class="brand-text"><strong>UniAdvisr</strong><small>Admin Panel</small></span>
    </a>
    <nav class="nav-menu" id="navMenu">
      <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
      <a href="manage_students.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_students.php' ? 'active' : '' ?>">Assignments</a>
      <a href="add_faculties.php" class="<?= basename($_SERVER['PHP_SELF']) == 'add_faculties.php' ? 'active' : '' ?>">Add Faculty</a>
      <a href="report.php" class="<?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : '' ?>">Reports</a>
    </nav>
    <div class="nav-actions">
      <div class="profile-dropdown">
        <a href="profile.php" class="profile-link">
          <div class="profile-img" style="background-image: url('../assets/images/profile.png')"></div>
          <span class="profile-name">Admin</span>
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