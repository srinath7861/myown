<?php
include 'config.php';
include 'time_calculations.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$current_date = date('Y-m-d');
$current_time = date('Y-m-d H:i:s');
$status_update_message = isset($_SESSION['status_update_message']) ? $_SESSION['status_update_message'] : '';
unset($_SESSION['status_update_message']);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (isset($_POST['employee_id']) && isset($_POST['new_status'])) {
        $employee_id = $db->real_escape_string($_POST['employee_id']);
        $new_status = $db->real_escape_string($_POST['new_status']);
        
        $db->begin_transaction();
        try {
            $update_query = $db->query("UPDATE user SET currect_status = '$new_status' WHERE u_id = '$employee_id'");
            if (!$update_query) {
                throw new Exception("Failed to update user status: " . $db->error);
            }
            
            $close_previous = $db->query("UPDATE `status_changes` SET end_time = '$current_time' 
                                        WHERE u_id = '$employee_id' AND end_time IS NULL");
            if (!$close_previous) {
                throw new Exception("Failed to close previous status: " . $db->error);
            }
            
            $insert_new = $db->query("INSERT INTO `status_changes` (u_id, status, start_time) 
                                    VALUES ('$employee_id', '$new_status', '$current_time')");
            if (!$insert_new) {
                throw new Exception("Failed to insert new status: " . $db->error);
            }
            
            $db->commit();
            $_SESSION['status_update_message'] = '<div class="alert alert-success" role="alert">
                Status updated successfully!
            </div>';
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['status_update_message'] = '<div class="alert alert-error" role="alert">
                Error updating status: ' . htmlspecialchars($e->getMessage()) . '
            </div>';
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch active employee data
try {
    $employees_query = $db->query("
        SELECT 
            u_id,
            employee_id,
            name,
            currect_status,
            last_login
        FROM user 
        WHERE active = 1
        ORDER BY currect_status DESC, name ASC
    ");
    
    if (!$employees_query) {
        throw new Exception("Database query failed: " . $db->error);
    }
    
    $employees = [];
    while ($employee = $employees_query->fetch_assoc()) {
        $times = calculateTimes($db, $employee['u_id'], $current_date);
        $employees[] = [
            'id' => $employee['u_id'],
            'employee_id' => $employee['employee_id'],
            'name' => $employee['name'],
            'current_status' => $employee['currect_status'],
            'last_login' => $employee['last_login'],
            'times' => $times
        ];
    }
} catch (Exception $e) {
    $employees = [];
    $error_message = "Error loading employee data: " . htmlspecialchars($e->getMessage());
}

// Fetch ongoing status for active employees
$ongoing_durations = [];
try {
    $ongoing_status_query = "SELECT 
        u.name,
        sc.status,
        sc.start_time,
        CASE WHEN sc.end_time IS NULL THEN 'Ongoing' ELSE sc.end_time END as end_time
    FROM status_changes sc
    JOIN user u ON sc.u_id = u.u_id
    WHERE DATE(sc.start_time) = ?
    AND sc.end_time IS NULL
    AND u.active = 1
    ORDER BY sc.start_time DESC";

    $stmt = $db->prepare($ongoing_status_query);
    if ($stmt) {
        $stmt->bind_param('s', $current_date);
        $stmt->execute();
        $ongoing_result = $stmt->get_result();

        while ($status = $ongoing_result->fetch_assoc()) {
            $start = new DateTime($status['start_time']);
            $end = new DateTime();
            $duration = $start->diff($end);
            $seconds = $duration->h * 3600 + $duration->i * 60 + $duration->s;
            $ongoing_durations[$status['name']][$status['status']] = $seconds;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Continue with empty ongoing durations
}

// Helper function for status color
function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'available': return 'status-available';
        case 'break 1':
        case 'break 2': return 'status-break';
        case 'lunch break': return 'status-lunch';
        case 'offline': return 'status-offline';
        case 'personal time': return 'status-personal';
        case 'meeting': return 'status-meeting';
        default: return 'status-default';
    }
}

// Helper function to format seconds
function formatSeconds($seconds) {
    if (!is_numeric($seconds) || $seconds < 0) return '00:00:00';
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="Employee management dashboard with real-time status tracking">
    <meta name="theme-color" content="#3498db">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title>Employee Dashboard - Status Management</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
            --light: #ecf0f1;
            --dark: #34495e;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Skip to content link */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--primary);
            color: white;
            padding: 8px;
            text-decoration: none;
            z-index: 100;
        }
        
        .skip-link:focus {
            top: 0;
        }
        
        /* Main Layout */
        .main-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
            padding: 1rem;
            padding-top: 5rem;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Container */
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: var(--success);
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border-color: var(--danger);
            color: #721c24;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Card Component */
        .card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            background-color: var(--white);
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }
        
        /* Search Bar */
        .search-container {
            position: relative;
            width: 100%;
            max-width: 300px;
            margin-top: 1rem;
        }
        
        .search-input {
            width: 100%;
            padding: 0.625rem 2.5rem 0.625rem 2.5rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
        }
        
        /* Mobile Cards for Employee Data */
        .employee-cards {
            display: none;
        }
        
        .employee-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .employee-card:active {
            transform: scale(0.98);
        }
        
        .employee-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .employee-info h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }
        
        .employee-id {
            font-size: 0.75rem;
            color: var(--gray-600);
            margin-top: 0.125rem;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            color: white;
            white-space: nowrap;
        }
        
        .status-available { background-color: var(--success); }
        .status-break { background-color: var(--warning); }
        .status-lunch { background-color: var(--info); }
        .status-offline { background-color: var(--danger); }
        .status-personal { background-color: #e67e22; }
        .status-meeting { background-color: #9b59b6; }
        .status-default { background-color: var(--gray-600); }
        
        /* Time Grid */
        .time-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin: 1rem 0;
        }
        
        .time-item {
            background: var(--gray-100);
            padding: 0.75rem;
            border-radius: 0.5rem;
        }
        
        .time-label {
            font-size: 0.75rem;
            color: var(--gray-600);
            margin-bottom: 0.25rem;
        }
        
        .time-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .time-details {
            font-size: 0.625rem;
            color: var(--gray-600);
            margin-top: 0.25rem;
        }
        
        .counter {
            color: var(--success);
            font-weight: 500;
        }
        
        /* Action Form */
        .action-form {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .status-select {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background-color: white;
        }
        
        .status-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .status-select:disabled {
            background-color: var(--gray-100);
            color: var(--gray-500);
            cursor: not-allowed;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            background-color: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Desktop Table */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -1rem;
            padding: 0 1rem;
        }
        
        .data-table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background-color: var(--gray-100);
        }
        
        .data-table th {
            padding: 0.75rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .data-table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.875rem;
        }
        
        .data-table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .data-table tbody tr:hover {
            background-color: var(--gray-50);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 0.5rem;
                padding-top: 4.5rem;
            }
            
            .card-header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-container {
                max-width: 100%;
                margin-top: 0;
            }
            
            .table-container {
                display: none;
            }
            
            .employee-cards {
                display: block;
            }
            
            .time-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (min-width: 769px) {
            .card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .search-container {
                margin-top: 0;
            }
            
            .employee-cards {
                display: none;
            }
            
            .table-container {
                display: block;
            }
        }
        
        @media (min-width: 1024px) {
            .main-content {
                padding: 2rem;
                padding-top: 6rem;
            }
        }
        
        /* Sidebar adjustments for desktop */
        @media (min-width: 1024px) {
            .with-sidebar .main-content {
                margin-left: 16rem;
            }
        }
        
        /* Loading State */
        .loading {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--gray-300);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Accessibility */
        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Focus styles */
        *:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--gray-400);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500);
        }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <?php include "header.php"; ?>
    <?php include "sidebar.php"; ?>
    
    <main id="main-content" class="main-content">
        <div class="container">
            <?php if ($status_update_message): ?>
                <?php echo $status_update_message; ?>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h1 class="card-title">Active Employee Overview</h1>
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input 
                            type="text" 
                            id="employeeSearch" 
                            class="search-input" 
                            placeholder="Search employees..."
                            aria-label="Search active employees"
                        >
                    </div>
                </div>
                
                <!-- Mobile View: Cards -->
                <div class="employee-cards" id="employeeCards">
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $employee): 
                            $last_login_date = date('Y-m-d', strtotime($employee['last_login']));
                            $display_status = ($last_login_date === $current_date) ? $employee['current_status'] : 'Offline';
                            $name = $employee['name'];
                            
                            // Calculate totals including ongoing time
                            $available_total = $employee['times']['Available'] + ($ongoing_durations[$name]['Available'] ?? 0);
                            $break1_total = $employee['times']['Break 1'] + ($ongoing_durations[$name]['Break 1'] ?? 0);
                            $break2_total = $employee['times']['Break 2'] + ($ongoing_durations[$name]['Break 2'] ?? 0);
                            $lunch_total = $employee['times']['Lunch Break'] + ($ongoing_durations[$name]['Lunch Break'] ?? 0);
                            $offline_total = $employee['times']['Offline'] + ($ongoing_durations[$name]['Offline'] ?? 0);
                        ?>
                        <div class="employee-card" data-employee-name="<?php echo strtolower(htmlspecialchars($name)); ?>">
                            <div class="employee-header">
                                <div class="employee-info">
                                    <h3><?php echo htmlspecialchars($name); ?></h3>
                                    <div class="employee-id"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                                </div>
                                <span class="status-badge <?php echo getStatusColor($display_status); ?>">
                                    <?php echo htmlspecialchars($display_status); ?>
                                </span>
                            </div>
                            
                            <div class="time-grid">
                                <div class="time-item">
                                    <div class="time-label">Available</div>
                                    <div class="time-value" data-total-time="<?php echo $available_total; ?>">
                                        <?php echo formatSeconds($available_total); ?>
                                    </div>
                                    <?php if (isset($ongoing_durations[$name]['Available'])): ?>
                                        <div class="time-details">
                                            Ongoing: <span class="counter" data-seconds="<?php echo $ongoing_durations[$name]['Available']; ?>">
                                                <?php echo formatSeconds($ongoing_durations[$name]['Available']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="time-item">
                                    <div class="time-label">Break 1</div>
                                    <div class="time-value" data-total-time="<?php echo $break1_total; ?>">
                                        <?php echo formatSeconds($break1_total); ?>
                                    </div>
                                    <?php if (isset($ongoing_durations[$name]['Break 1'])): ?>
                                        <div class="time-details">
                                            Ongoing: <span class="counter" data-seconds="<?php echo $ongoing_durations[$name]['Break 1']; ?>">
                                                <?php echo formatSeconds($ongoing_durations[$name]['Break 1']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="time-item">
                                    <div class="time-label">Break 2</div>
                                    <div class="time-value" data-total-time="<?php echo $break2_total; ?>">
                                        <?php echo formatSeconds($break2_total); ?>
                                    </div>
                                    <?php if (isset($ongoing_durations[$name]['Break 2'])): ?>
                                        <div class="time-details">
                                            Ongoing: <span class="counter" data-seconds="<?php echo $ongoing_durations[$name]['Break 2']; ?>">
                                                <?php echo formatSeconds($ongoing_durations[$name]['Break 2']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="time-item">
                                    <div class="time-label">Lunch Break</div>
                                    <div class="time-value" data-total-time="<?php echo $lunch_total; ?>">
                                        <?php echo formatSeconds($lunch_total); ?>
                                    </div>
                                    <?php if (isset($ongoing_durations[$name]['Lunch Break'])): ?>
                                        <div class="time-details">
                                            Ongoing: <span class="counter" data-seconds="<?php echo $ongoing_durations[$name]['Lunch Break']; ?>">
                                                <?php echo formatSeconds($ongoing_durations[$name]['Lunch Break']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="time-item">
                                    <div class="time-label">Offline</div>
                                    <div class="time-value" data-total-time="<?php echo $offline_total; ?>">
                                        <?php echo formatSeconds($offline_total); ?>
                                    </div>
                                    <?php if (isset($ongoing_durations[$name]['Offline'])): ?>
                                        <div class="time-details">
                                            Ongoing: <span class="counter" data-seconds="<?php echo $ongoing_durations[$name]['Offline']; ?>">
                                                <?php echo formatSeconds($ongoing_durations[$name]['Offline']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="time-item">
                                    <div class="time-label">Last Updated</div>
                                    <div class="time-value">
                                        <?php echo date('h:i A', strtotime($employee['last_login'])); ?>
                                    </div>
                                    <div class="time-details">
                                        <?php echo date('M d, Y', strtotime($employee['last_login'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <form method="post" class="action-form" onsubmit="return validateForm(this)">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                <select 
                                    name="new_status" 
                                    class="status-select"
                                    <?php echo ($last_login_date !== $current_date) ? 'disabled' : ''; ?>
                                    aria-label="Select status for <?php echo htmlspecialchars($name); ?>"
                                >
                                    <?php
                                    $statuses = ['Available', 'Break 1', 'Break 2', 'Lunch Break', 'Offline', 'Personal Time', 'Meeting'];
                                    foreach ($statuses as $status) {
                                        $selected = ($display_status === $status) ? 'selected' : '';
                                        echo "<option value=\"$status\" $selected>$status</option>";
                                    }
                                    ?>
                                </select>
                                <button 
                                    type="submit" 
                                    class="btn btn-primary"
                                    <?php echo ($last_login_date !== $current_date) ? 'disabled' : ''; ?>
                                >
                                    <i class="fas fa-save"></i>
                                    <span>Update</span>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: var(--gray-600);">
                            No active employees found.
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Desktop View: Table -->
                <div class="table-container">
                    <table class="data-table" id="employeeTable">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Available</th>
                                <th scope="col">Break 1</th>
                                <th scope="col">Break 2</th>
                                <th scope="col">Lunch</th>
                                <th scope="col">Offline</th>
                                <th scope="col">Last Updated</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($employees)): ?>
                                <?php foreach ($employees as $employee): 
                                    $last_login_date = date('Y-m-d', strtotime($employee['last_login']));
                                    $display_status = ($last_login_date === $current_date) ? $employee['current_status'] : 'Offline';
                                    $name = $employee['name'];
                                    
                                    $available_total = $employee['times']['Available'] + ($ongoing_durations[$name]['Available'] ?? 0);
                                    $break1_total = $employee['times']['Break 1'] + ($ongoing_durations[$name]['Break 1'] ?? 0);
                                    $break2_total = $employee['times']['Break 2'] + ($ongoing_durations[$name]['Break 2'] ?? 0);
                                    $lunch_total = $employee['times']['Lunch Break'] + ($ongoing_durations[$name]['Lunch Break'] ?? 0);
                                    $offline_total = $employee['times']['Offline'] + ($ongoing_durations[$name]['Offline'] ?? 0);
                                ?>
                                <tr data-employee-name="<?php echo strtolower(htmlspecialchars($name)); ?>">
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($name); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--gray-600);">
                                            <?php echo htmlspecialchars($employee['employee_id']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusColor($display_status); ?>">
                                            <?php echo htmlspecialchars($display_status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;" data-total-time="<?php echo $available_total; ?>">
                                            <?php echo formatSeconds($available_total); ?>
                                        </div>
                                        <?php if (isset($ongoing_durations[$name]['Available'])): ?>
                                            <div style="font-size: 0.75rem; color: var(--gray-600);">
                                                Ongoing: <span class="counter" data-seconds="<?php echo $ongoing_durations[$name]['Available']; ?>">
                                                    <?php echo formatSeconds($ongoing_durations[$name]['Available']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;" data-total-time="<?php echo $break1_total; ?>">
                                            <?php echo formatSeconds($break1_total); ?>
                                        </div>
                                        <?php if (isset($ongoing_durations[$name]['Break 1'])): ?>
                                            <div style="font-size: 0.75rem; color: var(--gray-600);">
                                                Ongoing: <span class="counter" data-seconds="<?php echo $ongoing_durations[$name]['Break 1']; ?>">
                                                    <?php echo formatSeconds($ongoing_durations[$name]['Break 1']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;" data-total-time="<?php echo $break2_total; ?>">
                                            <?php echo formatSeconds($break2_total); ?>
                                        </div>
                                        <?php if (isset($ongoing_durations[$name]['Break 2'])): ?>
                                            <div style="font-size: 0.75rem; color: var(--gray-600);">
                                                Ongoing: <span class="counter" data-seconds="<?php echo $ongoing_durations[$name]['Break 2']; ?>">
                                                    <?php echo formatSeconds($ongoing_durations[$name]['Break 2']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;" data-total-time="<?php echo $lunch_total; ?>">
                                            <?php echo formatSeconds($lunch_total); ?>
                                        </div>
                                        <?php if (isset($ongoing_durations[$name]['Lunch Break'])): ?>
                                            <div style="font-size: 0.75rem; color: var(--gray-600);">
                                                Ongoing: <span class="counter" data-seconds="<?php echo $ongoing_durations[$name]['Lunch Break']; ?>">
                                                    <?php echo formatSeconds($ongoing_durations[$name]['Lunch Break']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;" data-total-time="<?php echo $offline_total; ?>">
                                            <?php echo formatSeconds($offline_total); ?>
                                        </div>
                                        <?php if (isset($ongoing_durations[$name]['Offline'])): ?>
                                            <div style="font-size: 0.75rem; color: var(--gray-600);">
                                                Ongoing: <span class="counter" data-seconds="<?php echo $ongoing_durations[$name]['Offline']; ?>">
                                                    <?php echo formatSeconds($ongoing_durations[$name]['Offline']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['last_login']); ?></td>
                                    <td>
                                        <form method="post" style="display: flex; gap: 0.5rem;" onsubmit="return validateForm(this)">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                            <select 
                                                name="new_status" 
                                                class="status-select"
                                                style="min-width: 120px;"
                                                <?php echo ($last_login_date !== $current_date) ? 'disabled' : ''; ?>
                                            >
                                                <?php
                                                $statuses = ['Available', 'Break 1', 'Break 2', 'Lunch Break', 'Offline', 'Personal Time', 'Meeting'];
                                                foreach ($statuses as $status) {
                                                    $selected = ($display_status === $status) ? 'selected' : '';
                                                    echo "<option value=\"$status\" $selected>$status</option>";
                                                }
                                                ?>
                                            </select>
                                            <button 
                                                type="submit" 
                                                class="btn btn-primary"
                                                style="white-space: nowrap;"
                                                <?php echo ($last_login_date !== $current_date) ? 'disabled' : ''; ?>
                                            >
                                                <i class="fas fa-save"></i> Update
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 2rem; color: var(--gray-600);">
                                        No active employees found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Prevent multiple form submissions
        let isSubmitting = false;
        
        function validateForm(form) {
            if (isSubmitting) {
                return false;
            }
            
            const select = form.querySelector('select[name="new_status"]');
            if (!select || !select.value) {
                alert('Please select a valid status.');
                return false;
            }
            
            isSubmitting = true;
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.innerHTML = '<span class="loading"></span> Updating...';
                button.disabled = true;
            }
            
            return true;
        }
        
        // Search functionality with debounce
        const searchInput = document.getElementById('employeeSearch');
        let searchTimeout;
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const searchText = this.value.toLowerCase().trim();
                    
                    // Search in table rows (desktop)
                    const tableRows = document.querySelectorAll('#employeeTable tbody tr');
                    tableRows.forEach(row => {
                        const name = row.getAttribute('data-employee-name') || '';
                        const text = row.textContent.toLowerCase();
                        row.style.display = (name.includes(searchText) || text.includes(searchText)) ? '' : 'none';
                    });
                    
                    // Search in cards (mobile)
                    const cards = document.querySelectorAll('.employee-card');
                    cards.forEach(card => {
                        const name = card.getAttribute('data-employee-name') || '';
                        const text = card.textContent.toLowerCase();
                        card.style.display = (name.includes(searchText) || text.includes(searchText)) ? '' : 'none';
                    });
                }, 300);
            });
        }
        
        // Auto-dismiss alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
        
        // Timer for ongoing statuses
        function updateTimers() {
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                let seconds = parseInt(counter.getAttribute('data-seconds') || 0);
                seconds++;
                counter.setAttribute('data-seconds', seconds);
                
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                counter.textContent = 
                    `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            });
        }
        
        // Start timer updates
        setInterval(updateTimers, 1000);
        
        // Handle visibility change to pause/resume timers
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden, could pause timers if needed
            } else {
                // Page is visible again, could refresh data if needed
            }
        });
        
        // Touch feedback for mobile
        if ('ontouchstart' in window) {
            document.querySelectorAll('.btn, .employee-card').forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.opacity = '0.8';
                });
                element.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                });
            });
        }
        
        // Responsive sidebar toggle (if needed)
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('open');
            }
        }
        
        // Add keyboard navigation support
        document.addEventListener('keydown', function(e) {
            // Escape key to close sidebar on mobile
            if (e.key === 'Escape') {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                }
            }
        });
        
        // Prevent zoom on double tap for iOS
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(e) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        
        // Handle offline/online status
        window.addEventListener('online', function() {
            console.log('Connection restored');
            // Could show a notification or refresh data
        });
        
        window.addEventListener('offline', function() {
            console.log('Connection lost');
            // Could show a notification
        });
    </script>
</body>
</html>