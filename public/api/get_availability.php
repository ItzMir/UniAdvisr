<?php
require_once __DIR__ . '/../../app/includes/init.php';
header('Content-Type: application/json');

// Enable error reporting for debugging (remove after testing)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

try {
    if (!isset($_GET['faculty_id']) || !is_numeric($_GET['faculty_id'])) {
        echo json_encode(['error' => 'Missing or invalid faculty_id']);
        exit;
    }

    $facultyId = (int)$_GET['faculty_id'];
    if ($facultyId <= 0) {
        echo json_encode(['error' => 'Invalid faculty_id']);
        exit;
    }

    // Ensure $pdo is defined
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }

    // Define format_time_12h if not exists (fallback)
    if (!function_exists('format_time_12h')) {
        function format_time_12h($time) {
            if (!$time) return '';
            return date('g:i A', strtotime($time));
        }
    }

    $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

    // Weekly rules
    $rules = $pdo->prepare("
        SELECT day_of_week, start_time, end_time
        FROM faculty_availability_rules
        WHERE faculty_user_id = ? AND is_active = 1
        ORDER BY day_of_week, start_time
    ");
    $rules->execute([$facultyId]);
    $weekly = [];
    while ($r = $rules->fetch()) {
        $weekly[] = [
            'day'   => $days[$r['day_of_week']] ?? 'Unknown',
            'start' => format_time_12h($r['start_time']),
            'end'   => format_time_12h($r['end_time'])
        ];
    }

    // Unavailability blocks
    $unavail = $pdo->prepare("
        SELECT unavailable_date, start_time, end_time, reason
        FROM faculty_unavailability
        WHERE faculty_user_id = ? AND unavailable_date >= CURDATE()
        ORDER BY unavailable_date, start_time
    ");
    $unavail->execute([$facultyId]);
    $blocks = [];
    while ($u = $unavail->fetch()) {
        $blocks[] = [
            'date'   => $u['unavailable_date'],
            'start'  => format_time_12h($u['start_time']),
            'end'    => format_time_12h($u['end_time']),
            'reason' => $u['reason'] ?? ''
        ];
    }

    echo json_encode([
        'slots'       => $weekly,
        'unavailable' => $blocks
    ]);
} catch (Exception $e) {
    // Return a JSON error message
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}