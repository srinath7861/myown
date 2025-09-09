<?php
/**
 * Time calculation functions for employee management system
 */

/**
 * Calculate time spent in different statuses for a user on a specific date
 * 
 * @param mysqli $db Database connection
 * @param int $user_id User ID
 * @param string $date Date in Y-m-d format
 * @return array Array of status times in seconds
 */
function calculateTimes($db, $user_id, $date) {
    $times = [
        'Available' => 0,
        'Break 1' => 0,
        'Break 2' => 0,
        'Lunch Break' => 0,
        'Offline' => 0,
        'Personal Time' => 0,
        'Meeting' => 0
    ];
    
    // Query to get all status changes for the user on the specified date
    $query = "SELECT status, start_time, end_time 
              FROM status_changes 
              WHERE u_id = ? 
              AND DATE(start_time) = ?
              AND end_time IS NOT NULL
              ORDER BY start_time ASC";
    
    $stmt = $db->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $db->error);
        return $times;
    }
    
    $stmt->bind_param('is', $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'];
        $start = new DateTime($row['start_time']);
        $end = new DateTime($row['end_time']);
        $diff = $start->diff($end);
        $seconds = $diff->h * 3600 + $diff->i * 60 + $diff->s;
        
        if (isset($times[$status])) {
            $times[$status] += $seconds;
        }
    }
    
    $stmt->close();
    return $times;
}

/**
 * Format seconds to HH:MM:SS format
 * 
 * @param int $seconds Number of seconds
 * @return string Formatted time string
 */
function formatSeconds($seconds) {
    if (!is_numeric($seconds) || $seconds < 0) {
        return '00:00:00';
    }
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

/**
 * Get total working hours for a user on a specific date
 * 
 * @param mysqli $db Database connection
 * @param int $user_id User ID
 * @param string $date Date in Y-m-d format
 * @return int Total working seconds
 */
function getTotalWorkingHours($db, $user_id, $date) {
    $times = calculateTimes($db, $user_id, $date);
    return $times['Available'] + $times['Meeting'];
}

/**
 * Get total break time for a user on a specific date
 * 
 * @param mysqli $db Database connection
 * @param int $user_id User ID
 * @param string $date Date in Y-m-d format
 * @return int Total break seconds
 */
function getTotalBreakTime($db, $user_id, $date) {
    $times = calculateTimes($db, $user_id, $date);
    return $times['Break 1'] + $times['Break 2'] + $times['Lunch Break'] + $times['Personal Time'];
}

/**
 * Check if user has exceeded break limits
 * 
 * @param mysqli $db Database connection
 * @param int $user_id User ID
 * @param string $date Date in Y-m-d format
 * @return array Array with status and message
 */
function checkBreakLimits($db, $user_id, $date) {
    $times = calculateTimes($db, $user_id, $date);
    $warnings = [];
    
    // Break limits in seconds
    $limits = [
        'Break 1' => 900, // 15 minutes
        'Break 2' => 900, // 15 minutes
        'Lunch Break' => 3600, // 60 minutes
        'Personal Time' => 1800 // 30 minutes
    ];
    
    foreach ($limits as $break_type => $limit) {
        if ($times[$break_type] > $limit) {
            $exceeded = $times[$break_type] - $limit;
            $warnings[] = [
                'type' => $break_type,
                'exceeded_by' => formatSeconds($exceeded),
                'total_taken' => formatSeconds($times[$break_type]),
                'allowed' => formatSeconds($limit)
            ];
        }
    }
    
    return [
        'has_warnings' => !empty($warnings),
        'warnings' => $warnings
    ];
}

/**
 * Get current status duration for a user
 * 
 * @param mysqli $db Database connection
 * @param int $user_id User ID
 * @return array Status information with duration
 */
function getCurrentStatusDuration($db, $user_id) {
    $query = "SELECT status, start_time 
              FROM status_changes 
              WHERE u_id = ? 
              AND end_time IS NULL 
              ORDER BY start_time DESC 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $start = new DateTime($row['start_time']);
        $now = new DateTime();
        $diff = $start->diff($now);
        $seconds = $diff->h * 3600 + $diff->i * 60 + $diff->s;
        
        return [
            'status' => $row['status'],
            'start_time' => $row['start_time'],
            'duration_seconds' => $seconds,
            'duration_formatted' => formatSeconds($seconds)
        ];
    }
    
    $stmt->close();
    return null;
}

/**
 * Get weekly summary for a user
 * 
 * @param mysqli $db Database connection
 * @param int $user_id User ID
 * @param string $week_start Start date of the week (Y-m-d format)
 * @return array Weekly summary data
 */
function getWeeklySummary($db, $user_id, $week_start = null) {
    if ($week_start === null) {
        $week_start = date('Y-m-d', strtotime('monday this week'));
    }
    
    $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
    $summary = [];
    
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime($week_start . " +$i days"));
        $times = calculateTimes($db, $user_id, $date);
        
        $summary[$date] = [
            'day' => date('l', strtotime($date)),
            'date' => $date,
            'working_hours' => getTotalWorkingHours($db, $user_id, $date),
            'break_time' => getTotalBreakTime($db, $user_id, $date),
            'times' => $times
        ];
    }
    
    return $summary;
}
?>