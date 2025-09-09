<?php
// AJAX endpoint to fetch attendance details for a specific date
require_once 'config.php';
require_once 'time_calculations.php';

header('Content-Type: application/json');

$selected_date = filter_input(INPUT_GET, 'date', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}-\d{2}-\d{2}$/']
]);

if (!$selected_date) {
    echo json_encode(['error' => 'Invalid date']);
    exit;
}

// Fetch all users
$users_query = $db->prepare("SELECT u_id, name, work_start FROM user");
$users_query->execute();
$users_result = $users_query->get_result();
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[$row['u_id']] = $row;
}
$users_query->close();

// Fetch attendance records for the selected date
$records = [];
$stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0];

try {
    $query = "
        SELECT u.u_id, u.name, DATE(sc.start_time) AS date, MIN(sc.start_time) AS check_in
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE DATE(sc.start_time) = ?
        AND sc.status IN ('Available', 'Break 1', 'Break 2', 'Lunch Break', 'Meeting', 'Personal Time')
        GROUP BY sc.u_id
        ORDER BY u.name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('s', $selected_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $u_id = $row['u_id'];
        $date = $row['date'];
        $times = calculateTimes($db, $u_id, $date);
        $available_time = $times['Available'] ?? 0;
        $login_time = $row['check_in'] ? substr($row['check_in'], 11) : null;
        $work_start = $users[$u_id]['work_start'] ?? '09:00:00';
        
        $status = [];
        if (!$login_time || $available_time < 3600) {
            $status[] = 'Absent';
            $stats['absent']++;
        } else {
            if ($available_time >= 28800) {
                $status[] = 'Present';
                $stats['present']++;
            } elseif ($available_time >= 14400) {
                $status[] = 'Half Day';
                $stats['half_day']++;
            }
            if ($login_time > $work_start) {
                $status[] = 'Late';
                $stats['late']++;
            }
        }
        
        $row['status'] = $status;
        $row['total_hours'] = formatSeconds($available_time);
        $row['break_time'] = formatSeconds(($times['Break 1'] ?? 0) + ($times['Break 2'] ?? 0) + ($times['Lunch Break'] ?? 0));
        $row['status_text'] = implode(' + ', $status);
        $row['check_in'] = $login_time;
        $records[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

echo json_encode([
    'records' => $records,
    'stats' => $stats,
    'total' => count($records)
]);
?>