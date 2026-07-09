<?php
require_once __DIR__ . '/../../app/includes/init.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode([]);
    exit;
}
// Get faculty ID from query string, default to 0 if not set
$facultyId = (int)($_GET['faculty_id'] ?? 0);
if (!$facultyId) {
    echo json_encode([]);
    exit;
}
// Fetch next 3 upcoming appointments for the faculty member
$stmt = $pdo->prepare("
    SELECT a.id, a.token_code, a.token_number, a.queue_position,
           a.scheduled_start_at, a.scheduled_end_at,
           a.status, a.topic,
           u.name AS student_name, sp.student_code
    FROM appointments a
    JOIN users u ON u.id = a.student_user_id
    LEFT JOIN student_profiles sp ON sp.user_id = u.id
    WHERE a.faculty_user_id = ?
      AND a.appointment_date >= CURDATE()
      AND a.status IN ('approved','in_progress')
    ORDER BY a.appointment_date, a.scheduled_start_at, a.token_number
    LIMIT 3
");
$stmt->execute([$facultyId]);
$queue = $stmt->fetchAll();

echo json_encode($queue); // Return the queue data as JSON