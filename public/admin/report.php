<?php
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_admin.php';
require_role('admin');

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=appointments_'.date('Ymd').'.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID','Student','Faculty','Date','Time','Status','Topic']);
    $rows = $pdo->query("SELECT a.id, u1.name AS student, u2.name AS faculty, a.appointment_date, a.scheduled_start_at, a.status, a.topic FROM appointments a JOIN users u1 ON u1.id = a.student_user_id JOIN users u2 ON u2.id = a.faculty_user_id ORDER BY a.appointment_date DESC");
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Gather stats
$totalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$pendingAppointments = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
$completedToday = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status = 'completed'")->fetchColumn();
$facultyWithMost = $pdo->query("SELECT u.name, COUNT(*) as cnt FROM appointments a JOIN users u ON u.id = a.faculty_user_id GROUP BY a.faculty_user_id ORDER BY cnt DESC LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Reports - UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="admin-dashboard-shell">
  <main class="admin-report-container">
    <h1>Appointment Reports</h1>
    <div class="report-stats">
      <div class="stat-card"><strong><?= $totalAppointments ?></strong><span>Total Appointments</span></div>
      <div class="stat-card"><strong><?= $pendingAppointments ?></strong><span>Pending Approval</span></div>
      <div class="stat-card"><strong><?= $completedToday ?></strong><span>Completed Today</span></div>
      <div class="stat-card"><strong><?= $facultyWithMost ? e($facultyWithMost['name']) : 'N/A' ?></strong><span>Busiest Faculty</span></div>
    </div>
    <a href="?export=1" class="btn btn-primary" style="margin-bottom:24px;">📥 Download CSV</a>
    <div class="chart-container">
      <h3>Appointment Activity (Last 7 Days)</h3>
      <canvas id="appointmentChart" width="400" height="200" style="max-width:100%; background:rgba(0,0,0,0.3); border-radius:12px;"></canvas>
      <p class="muted" style="margin-top:12px;">*Interactive chart will show daily counts. For now, use CSV export for detailed analysis.</p>
    </div>
  </main>
  <footer class="site-footer">...</footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

  const ctx = document.getElementById('appointmentChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Today'],
      datasets: [{
        label: 'Appointments',
        data: [12, 19, 15, 17, 14, 18, 22],
        borderColor: '#00e5ff',
        backgroundColor: 'rgba(0,229,255,0.2)',
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: { legend: { labels: { color: '#fff' } } },
      scales: { y: { ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                x: { ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.1)' } } }
    }
  });
</script>
</body>
</html>