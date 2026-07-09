<?php
require_once __DIR__ . '/../../app/includes/init.php';
header('Content-Type: application/json');  // This API endpoint handles actions on appointments (start, complete, cancel) like JSON not html.

if (!is_logged_in()) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }  // Must be logged in to perform any actions

$action = $_POST['action'] ?? ''; //gets the action from the POST request, which indicates what operation to perform on the appointment (e.g., start, complete, cancel_student, cancel_faculty)
$apptId = (int)($_POST['appointment_id'] ?? 0); //gets the appointment ID from the POST request
if (!csrf_verify($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false,'message'=>'Invalid token']); exit; } //csrf verification

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
// Fetch the appointment to check permissions and details
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
$stmt->execute([$apptId]);
$appt = $stmt->fetch();
if (!$appt) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
// Permission checks: students can only cancel their own appointments, faculty can start/complete/cancel their own appointments. No one else can perform actions on appointments they are not involved in.
$allowed = false;
if ($role === 'student' && $appt['student_user_id'] == $userId && $action === 'cancel_student') $allowed = true;
if ($role === 'faculty' && $appt['faculty_user_id'] == $userId && in_array($action, ['start','complete','cancel_faculty'])) $allowed = true;
if (!$allowed) { echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

try {
    switch ($action) {
        // updates the appointment for cancelled by student
        case 'cancel_student':
            $pdo->prepare("UPDATE appointments SET status='cancelled_by_student', cancelled_by_user_id=?, queue_position=NULL WHERE id=?")
                ->execute([$userId, $apptId]);
            break;
        // updates the appointment for in progress and sets the actual start time
        case 'start':
            $pdo->prepare("UPDATE appointments SET status='in_progress', actual_start_at=NOW() WHERE id=?")->execute([$apptId]);
            echo json_encode(['success'=>true]);
            exit;

        case 'complete':
        // Fetch original scheduled times to calculate time saved if completed early
            $orig = $pdo->prepare("SELECT scheduled_start_at, scheduled_end_at FROM appointments WHERE id = ?");
            $orig->execute([$apptId]);
            $origData = $orig->fetch();
    
            if (!$origData) {
            throw new Exception("Appointment not found");
            }   
            // Calculate time saved if completed early
            $actualEnd = new DateTime();
            $scheduledEnd = new DateTime($origData['scheduled_end_at']);
            $timeSaved = $scheduledEnd->getTimestamp() - $actualEnd->getTimestamp(); // positive = early finish
    
            // Mark as completed and remove from queue
            $pdo->prepare("UPDATE appointments SET status='completed', actual_end_at=NOW(), queue_position=NULL, completed_early = ? WHERE id=?")
                ->execute([($timeSaved > 0 ? 1 : 0), $apptId]); // if timeSaved > 0, mark completed_early as true, else false

            // If completed early, shift all future appointments earlier
            if ($timeSaved > 0) {
                // Get all future active appointments for the same faculty/date
                $future = $pdo->prepare("
                    SELECT id, scheduled_start_at, scheduled_end_at
                    FROM appointments
                    WHERE faculty_user_id = ? 
                      AND appointment_date = ?
                      AND status IN ('pending','approved','in_progress')
                      AND scheduled_start_at > ?
                    ORDER BY scheduled_start_at ASC
                ");
                $future->execute([
                    $appt['faculty_user_id'],
                    $appt['appointment_date'],
                    $origData['scheduled_start_at']
                ]);
                $futureApps = $future->fetchAll();
                
                foreach ($futureApps as $fa) {
                    //old start and end times of the future appointment
                    $oldStart = new DateTime($fa['scheduled_start_at']);
                    $oldEnd = new DateTime($fa['scheduled_end_at']);

                    // make a copy of the start and end time and move it earlier by the time saved
                    $newStart = clone $oldStart;
                    $newStart->modify("-{$timeSaved} seconds");
                    $newEnd = clone $oldEnd;
                    $newEnd->modify("-{$timeSaved} seconds");

                    // Update the appointment
                    $upd = $pdo->prepare("
                        UPDATE appointments 
                        SET scheduled_start_at = ?, scheduled_end_at = ? 
                        WHERE id = ?
                    ");
                    $upd->execute([
                        $newStart->format('Y-m-d H:i:s'),
                        $newEnd->format('Y-m-d H:i:s'),
                        $fa['id']
                    ]);
                }
            }
            break;

        case 'cancel_faculty':
            $pdo->prepare("UPDATE appointments SET status='cancelled_by_faculty', cancelled_by_user_id=?, queue_position=NULL WHERE id=?")
                ->execute([$userId, $apptId]);
            break;
    }
    // Reorder queue for remaining active appointments (pending, approved, in_progress)
    //if the appointment was cancelled or completed, rebuild the queue
    if (in_array($action, ['cancel_student', 'complete', 'cancel_faculty'])) {
        $facultyId = $appt['faculty_user_id'];
        $date = $appt['appointment_date'];
        $pdo->exec("SET @pos = 0"); //create a counter starting at zero
        //update the queue_position for all active appointments of the same faculty and date by joining with a subquery that calculates the new positions based on the scheduled start time and token number
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
        ")->execute([$facultyId, $date]);
    }

    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'DB error: ' . $e->getMessage()]);
}