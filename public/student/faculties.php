<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_student.php';
require_role('student');

$studentId = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$deptFilter = $_GET['dept'] ?? '';

// 1. ALL ACTIVE FACULTIES (for main grid + filter)
$sql = "SELECT u.id, u.name, u.email, u.profile_photo,
               fp.designation, fp.specialization, fp.office_location,
               d.name AS department_name, d.id AS dept_id
        FROM users u
        JOIN roles r ON r.id = u.role_id AND r.code = 'faculty'
        LEFT JOIN faculty_profiles fp ON fp.user_id = u.id
        LEFT JOIN departments d ON d.id = fp.department_id
        WHERE u.is_active = 1";
$params = []; // dynamic params for search/filter, This creates an empty list for SQL values that will be inserted later
if ($search) {
    $sql .= " AND (u.name LIKE ? OR fp.specialization LIKE ? OR d.name LIKE ?)"; // .= joins/appends the search condition to the existing SQL query. previous sql + new condition
    $params = array_fill(0, 3, "%$search%");
}
if ($deptFilter) {
    $sql .= " AND d.id = ?";
    $params[] = $deptFilter;
}
$sql .= " ORDER BY u.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allFaculties = $stmt->fetchAll();

// Departments list for filter
$depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name")->fetchAll();

// ------------------------------------------------------------
// 2. ASSIGNED FACULTIES (for "My Advisors")
//    - fetch the full datetime of the next session, not separate date/time
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.profile_photo, fp.designation, d.name AS dept_name,
           (SELECT a.scheduled_start_at FROM appointments a
            WHERE a.student_user_id = ? AND a.faculty_user_id = u.id
              AND a.status IN ('approved','in_progress')
            ORDER BY a.appointment_date LIMIT 1) AS next_session_datetime,
           (SELECT a.token_code FROM appointments a
            WHERE a.student_user_id = ? AND a.faculty_user_id = u.id
              AND a.status IN ('approved','in_progress')
            ORDER BY a.appointment_date LIMIT 1) AS token_code
    FROM student_faculty_assignments sfa
    JOIN users u ON u.id = sfa.faculty_user_id
    LEFT JOIN faculty_profiles fp ON fp.user_id = u.id
    LEFT JOIN departments d ON d.id = fp.department_id
    WHERE sfa.student_user_id = ? AND (sfa.end_date IS NULL OR sfa.end_date >= CURDATE())
    ORDER BY sfa.is_primary DESC
");
$stmt->execute([$studentId, $studentId, $studentId]);
$assigned = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Faculties – UniAdvisr</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <!-- Popup & main JS will be included at the bottom -->
</head>
<body>
<div class="student-dashboard-shell">
  <!-- ===== MAIN CONTENT ===== -->
  <main class="student-faculty-container">

    <!-- Search / Filter Bar -->
    <div class="student-faculty-header">
      <h1>Explore Our Faculties</h1>
      <form method="get" class="filter-form">
        <select name="dept" class="filter-select">
          <option value="">All Departments</option>
          <?php foreach ($depts as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" name="search" class="filter-input" placeholder="Search by name or specialization…" value="<?= e($search) ?>">
        <button type="submit" class="btn btn-primary">Filter</button>
      </form>
    </div>

    <!-- ALL FACULTIES GRID -->
    <div class="student-faculty-grid">
      <?php if ($allFaculties): ?>
        <?php foreach ($allFaculties as $fac): ?>
          <div class="student-faculty-card">
            <img src="<?= $fac['profile_photo'] ? '../'.$fac['profile_photo'] : '../assets/images/profile.png' ?>" alt="" class="faculty-avatar">
            <h3><?= e($fac['name']) ?></h3>
            <p><strong><?= e($fac['designation'] ?? 'Faculty') ?></strong></p>
            <p><?= e($fac['department_name'] ?? 'General') ?></p>
            <?php if (!empty($fac['specialization'])): ?>
              <p class="specialization"><?= e($fac['specialization']) ?></p>
            <?php endif; ?>
            <button class="btn btn-outline btn-view-slots"
                    data-faculty="<?= $fac['id'] ?>"
                    data-name="<?= e($fac['name']) ?>">
              View Availability
            </button>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="grid-column:1/-1; text-align:center; color:var(--muted);">No faculties found.</p>
      <?php endif; ?>
    </div>

    <!-- ========== MY ASSIGNED ADVISORS ========== -->
    <?php if ($assigned): ?>
      <section style="margin-top:60px;">
        <h2 style="font-size:2rem; margin-bottom:24px; color:#fff;">My Assigned Advisors</h2>
        <div class="student-faculty-grid">
          <?php foreach ($assigned as $fac): ?>
            <div class="student-faculty-card" style="text-align:left;">
              <div style="display:flex; gap:16px; align-items:center;">
                <img src="<?= $fac['profile_photo'] ? '../'.$fac['profile_photo'] : '../assets/images/profile.png' ?>" alt="" class="faculty-avatar" style="width:60px; height:60px;">
                <div>
                  <h3 style="margin-bottom:0;"><?= e($fac['name']) ?></h3>
                  <p style="margin-bottom:0;">
                    <?= e($fac['designation'] ?? '') ?>
                    <?= $fac['dept_name'] ? ' – '.e($fac['dept_name']) : '' ?>
                  </p>
                </div>
              </div>

              <div style="margin-top:12px;">
                <?php if ($fac['next_session_datetime']): ?>
                  <p style="font-size:0.9rem;">
                    <strong>Next session:</strong>
                    <?= format_datetime($fac['next_session_datetime'], 'M j, Y g:i A') ?>
                  </p>
                  <p style="font-size:0.9rem;">
                    <strong>Token:</strong> <?= e($fac['token_code'] ?? 'N/A') ?>
                  </p>
                <?php else: ?>
                  <p style="font-size:0.9rem; color:var(--muted);">No upcoming sessions</p>
                <?php endif; ?>
              </div>

              <a href="booking.php?faculty_id=<?= $fac['id'] ?>" class="btn btn-ghost" style="margin-top:8px;">Book Counseling</a>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <!-- ===== FOOTER ===== -->
  <footer class="site-footer">
    <div class="footer-container">
      <div class="footer-grid">
        <div>
          <div class="footer-brand">UniAdvisr</div>
          <p>Your trusted university guidance platform.</p>
          <div class="social-links"><a href="#">Facebook</a><a href="#">Twitter</a><a href="#">LinkedIn</a></div>
        </div>
        <div>
          <h4>Quick Links</h4>
          <a href="dashboard.php">Home</a><a href="faculties.php">Faculties</a><a href="booking.php">Booking</a><a href="token.php">Token</a><a href="essay.php">Essay</a>
        </div>
        <div>
          <h4>Support</h4>
          <a href="#">Help Center</a><a href="#">Contact Us</a><a href="#">FAQs</a>
        </div>
        <div>
          <h4>Contact</h4>
          <p>furiousrashid02@gmail.com</p><p>+8801889143752</p><p>24/7 Available</p>
        </div>
      </div>
      <div class="footer-bottom">
        <p>© 2026 UniAdvisr. All rights reserved.</p>
      </div>
    </div>
  </footer>
</div>

<!-- ===== AVAILABILITY POPUP (self‑contained script) ===== -->
<div id="slot-popup" class="popup-overlay" style="display:none;">
  <div class="popup-content">
    <span class="popup-close">&times;</span>
    <h2 id="popup-faculty-name"></h2>
    <div id="slot-list">Loading…</div>
  </div>
</div>

<script>
// ---------- Popup logic (no dependency on main.js) ----------
document.addEventListener('DOMContentLoaded',function(){
    const popup = document.getElementById('slot-popup');
    if(!popup) return;

    popup.querySelector('.popup-close').addEventListener('click',function(){
        popup.style.display = 'none';
    });

    document.querySelectorAll('.btn-view-slots').forEach(function(btn){
        btn.addEventListener('click',async function(){
            const fid = this.dataset.faculty;
            const fname = this.dataset.name;
            document.getElementById('popup-faculty-name').textContent = fname + ' — Availability';
            const slotList = document.getElementById('slot-list');
            slotList.innerHTML = 'Loading…';
            popup.style.display = 'flex';

            try {
                const res = await fetch('../api/get_availability.php?faculty_id=' + fid);
                const data = await res.json();
                let html = '';
                if(data.slots && data.slots.length){
                    html += '<h3 style="margin-bottom:8px;">Weekly Schedule</h3>';
                    data.slots.forEach(s => {
                        html += `<p>${s.day} : ${s.start} – ${s.end}</p>`;
                    });
                } else {
                    html += '<p>No regular availability set.</p>';
                }
                if(data.unavailable && data.unavailable.length){
                    html += '<h3 style="margin-top:16px;margin-bottom:8px;">Blocked Times</h3>';
                    data.unavailable.forEach(u => {
                        html += `<p>${u.date} : ${u.start} – ${u.end} ${u.reason ? '('+u.reason+')' : ''}</p>`;
                    });
                }
                slotList.innerHTML = html;
            } catch(e){
                slotList.innerHTML = '<p style="color:#e74c3c;">Could not load availability.</p>';
            }
        });
    });
});
</script>

<!-- Optional: still load main.js for other features -->
<script src="../assets/js/main.js"></script>
</body>
</html>