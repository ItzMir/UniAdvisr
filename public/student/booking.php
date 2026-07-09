<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/includes/init.php';
require_once __DIR__ . '/../../app/templates/header_student.php';
require_role('student');

$studentId = $_SESSION['user_id'];

// Load assigned advisors
$stmt = $pdo->prepare("
    SELECT u.id, u.name, fp.designation, d.name AS dept_name
    FROM student_faculty_assignments sfa
    JOIN users u ON u.id = sfa.faculty_user_id
    LEFT JOIN faculty_profiles fp ON fp.user_id = u.id
    LEFT JOIN departments d ON d.id = fp.department_id
    WHERE sfa.student_user_id = ?
      AND (sfa.end_date IS NULL OR sfa.end_date >= CURDATE())
    ORDER BY sfa.is_primary DESC
");
$stmt->execute([$studentId]);
$advisors = $stmt->fetchAll();

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? '')) { // Basic validation of csrf token to ensure the form submission is legitimate
    $facultyId = (int)($_POST['faculty_id'] ?? 0);
    $date      = $_POST['appointment_date'] ?? '';
    $time      = $_POST['scheduled_start_at'] ?? '';
    $topic     = trim($_POST['topic'] ?? '');
    $note      = trim($_POST['student_note'] ?? '');

    if (empty($date) || empty($time) || empty($topic) || $facultyId === 0) { // Basic validation to ensure required fields are filled
        set_flash('error', 'All fields are required.');
    } else {
        // Check assignment
        $check = $pdo->prepare("SELECT 1 FROM student_faculty_assignments WHERE student_user_id = ? AND faculty_user_id = ? AND (end_date IS NULL OR end_date >= CURDATE())"); // Verify that the student is assigned to the selected faculty, and that the assignment is still active
        $check->execute([$studentId, $facultyId]);
        if (!$check->fetch()) {
            set_flash('error', 'You are not assigned to this faculty.');
        } else {
            $scheduledStart = $date . ' ' . $time . ':00'; // Combine date and time into a single datetime string for the scheduled start time of the appointment
            $scheduledEnd = date('Y-m-d H:i:s', strtotime($scheduledStart) + 15 * 60); // Calculate the scheduled end time by adding 15 minutes to the scheduled start time, since appointments are in 15-minute slots
            $errors = []; // Initialize an array to collect any validation errors that may occur during the booking process

            // Weekly availability rule
            $dayOfWeek = date('w', strtotime($date)); // Get the day of the week for the appointment date (0 for Sunday, 1 for Monday, etc.) to check against faculty availability rules
            // Check if the selected time falls within the faculty's availability rules for that day of the week, ensuring that the appointment is scheduled during times when the faculty is available
            $rule = $pdo->prepare("
                SELECT 1 FROM faculty_availability_rules
                WHERE faculty_user_id = ? AND day_of_week = ? AND is_active = 1
                  AND ? BETWEEN start_time AND end_time
                  AND ? BETWEEN start_time AND end_time
            "); 
            $rule->execute([$facultyId, $dayOfWeek, $time.':00', date('H:i:s', strtotime($scheduledEnd))]);
            if (!$rule->fetch()) $errors[] = 'Outside faculty hours.'; // If the time does not fall within the faculty's defined availability rules for that day, add an error message indicating that the selected time is outside of the faculty's working hours

            // Unavailability
            $unav = $pdo->prepare("
                SELECT 1 FROM faculty_unavailability
                WHERE faculty_user_id = ? AND unavailable_date = ?
                  AND ( ? BETWEEN start_time AND end_time
                    OR ? BETWEEN start_time AND end_time
                    OR start_time BETWEEN ? AND ? )
            ");
            $unav->execute([$facultyId, $date, $time.':00', date('H:i:s', strtotime($scheduledEnd)), $time.':00', date('H:i:s', strtotime($scheduledEnd))]);
            if ($unav->fetch()) $errors[] = 'Faculty unavailable.'; // If the faculty is unavailable on the selected date and time, add an error message

            // Overlap with ANY non‑cancelled/declined/completed appointment
            $overlap = $pdo->prepare("
                SELECT 1 FROM appointments
                WHERE faculty_user_id = ? AND appointment_date = ?
                  AND status NOT IN ('cancelled_by_student','cancelled_by_faculty','declined','completed','no_show')
                  AND scheduled_start_at < ? AND scheduled_end_at > ?
            ");
            $overlap->execute([$facultyId, $date, $scheduledEnd, $scheduledStart]); 
            if ($overlap->fetch()) $errors[] = 'Time slot already taken (including pending).'; // If there is any existing appointment for the same faculty and date that overlaps with the requested time slot (and is not in a cancelled, declined, completed, or no-show status), add an error message indicating that the time slot is already taken

            if (!empty($errors)) {
                set_flash('error', implode('<br>', $errors)); // If there are any validation errors collected, set a flash message with the combined error messages to inform the user of the issues that need to be addressed before successfully booking the appointment
            } else {
                // Token number
                $tn = $pdo->prepare("SELECT COALESCE(MAX(token_number),0)+1 FROM appointments WHERE faculty_user_id=? AND appointment_date=?"); // Calculate the next token number for the appointment by finding the maximum existing token number for the same faculty and date, and adding 1 to it. This ensures that each appointment receives a unique token number in sequential order for that faculty and date.
                $tn->execute([$facultyId, $date]);
                $tokenNumber = (int)$tn->fetchColumn();

                // Unique token code
                $tokenCode = 'T' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)); // Generate a unique token code for the appointment by creating a random 8-character hexadecimal string, converting it to uppercase, and prefixing it with 'T' to create a more user-friendly token code that can be easily referenced by students and faculty

                // Insert appointment into database
                $insert = $pdo->prepare("
                    INSERT INTO appointments
                    (student_user_id, faculty_user_id, created_by_user_id,
                     appointment_date, topic, student_note, status,
                     token_number, token_code, queue_position,
                     duration_minutes, scheduled_start_at, scheduled_end_at)
                    VALUES (?,?,?,?,?,?,'pending',?,?,NULL,15,?,?) 
                "); // Prepare an SQL statement to insert the new appointment into the database, including all relevant details such as student and faculty IDs, appointment date, topic, notes, status (initially set to 'pending'), token information, duration, and scheduled start and end times. The queue_position is set to NULL initially and will be calculated later based on the order of appointments for that faculty and date.
                $insert->execute([$studentId, $facultyId, $studentId, $date, $topic, $note, $tokenNumber, $tokenCode, $scheduledStart, $scheduledEnd]);

                // Recalculate queue positions for active appointments
                $pdo->exec("SET @pos = 0"); // Reset a user-defined variable in MySQL to 0, which will be used to calculate the new queue positions for active appointments for the same faculty and date. This is necessary to ensure that the queue positions are updated correctly after a new appointment is added, especially if there are any pending or approved appointments that need to be reordered based on their scheduled times.
                $pdo->prepare("
                    UPDATE appointments a
                    JOIN (
                        SELECT id, @pos := @pos + 1 AS new_pos
                        FROM appointments
                        WHERE faculty_user_id = ? AND appointment_date = ?
                          AND status IN ('pending','approved','in_progress')
                        ORDER BY scheduled_start_at, token_number
                    ) AS sorted ON a.id = sorted.id
                    SET a.queue_position = sorted.new_pos
                ")->execute([$facultyId, $date]); // Update the queue_position for all active appointments (pending, approved, in_progress) for the same faculty and date by joining the appointments table with a subquery that calculates the new positions based on the scheduled start time and token number. This ensures that the queue positions are always in the correct order after any changes to the appointments, such as adding a new appointment or changing the status of existing appointments.

                set_flash('success', 'Appointment requested! Token: ' . $tokenCode);
                header('Location: booking.php'); // After successfully booking the appointment and updating the queue positions, redirect to booking.php to refresh the page and display the updated list of appointments
                exit;
            }
        }
    }
}

$csrf_token = csrf_token(); //Generates a fresh security token for the form.

// Fetch student's appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS faculty_name
    FROM appointments a
    JOIN users u ON u.id = a.faculty_user_id
    WHERE a.student_user_id = ? AND a.status != 'cancelled_by_student'
    ORDER BY a.appointment_date, a.scheduled_start_at
");
$stmt->execute([$studentId]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Book Appointment – UniAdvisr</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="student-dashboard-shell">
  <main class="student-booking-container">
    <?php $flash = get_flash(); foreach ($flash as $f) echo '<div class="flash '.$f['type'].'">'.$f['message'].'</div>'; ?>
    <h1>Book a Counseling Session</h1>
    <form method="post" class="booking-form">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      <div class="form-group"><label>Select Advisor</label><select name="faculty_id" required><option value="">Choose</option><?php foreach ($advisors as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?> (<?= e($a['dept_name']) ?>)</option><?php endforeach; ?></select></div>
      <div class="form-group"><label>Date</label><input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>" required></div>
      <div class="form-group"><label>Time (15‑minute slots)</label><input type="time" name="scheduled_start_at" step="900" required></div>
      <div class="form-group"><label>Topic</label><input type="text" name="topic" required></div>
      <div class="form-group"><label>Additional Note</label><textarea name="student_note" rows="3"></textarea></div>
      <button type="submit" class="btn btn-primary">Request Appointment</button>
    </form>

    <section class="pending-appointments"><h2>My Appointments</h2>
    <?php if ($appointments): ?>
      <div class="table-scroll"><tr><thead><tr><th>Token</th><th>Advisor</th><th>Date</th><th>Time</th><th>Topic</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($appointments as $app): ?>
        <tr><td><?= e($app['token_code'] ?? '') ?></td><td><?= e($app['faculty_name']) ?></td><td><?= e($app['appointment_date']) ?></td><td><?= format_time_12h($app['scheduled_start_at']) ?></td><td><?= e($app['topic']) ?></td><td><span class="status-<?= str_replace('_','-',$app['status']) ?>"><?= ucwords(str_replace('_',' ',$app['status'])) ?></span></td>
        <td><?php if (in_array($app['status'], ['pending','approved','in_progress'])): ?><button class="btn btn-xs btn-cancel-appt" data-id="<?= $app['id'] ?>">Cancel</button><?php endif; ?></td></tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <?php else: ?><p>No appointments yet.</p><?php endif; ?>
    </section>
  </main>
  <footer class="site-footer"><!-- footer content --></footer>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>