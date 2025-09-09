<?php
include 'config.php';
include 'time_calculations.php';

$current_date = date('Y-m-d');
$current_time = date('Y-m-d H:i:s');
$status_update_message = '';

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
            $_SESSION['status_update_message'] = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md mb-4" role="alert">
                Status updated successfully!
            </div>';
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['status_update_message'] = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md mb-4" role="alert">
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
    die("Error: " . htmlspecialchars($e->getMessage()));
}

// Fetch ongoing status for active employees
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
$stmt->bind_param('s', $current_date);
$stmt->execute();
$ongoing_result = $stmt->get_result();

$ongoing_durations = [];
while ($status = $ongoing_result->fetch_assoc()) {
    $start = new DateTime($status['start_time']);
    $end = new DateTime();
    $duration = $start->diff($end);
    $seconds = $duration->h * 3600 + $duration->i * 60 + $duration->s;
    $ongoing_durations[$status['name']][$status['status']] = $seconds;
}

// Helper function for status color
function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'available': return 'bg-green-500';
        case 'break 1':
        case 'break 2': return 'bg-yellow-500';
        case 'lunch break': return 'bg-blue-500';
        case 'offline': return 'bg-red-500';
        case 'personal time': return 'bg-orange-500';
        case 'meeting': return 'bg-purple-500';
        default: return 'bg-gray-500';
    }
}

?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="A powerful employee management system with attendance tracking, break management, leave applications, and comprehensive admin controls for better workforce management.">
    <meta name="theme-color" content="#3498db">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Employee Management Dashboard | Track Attendance, Breaks & Leave</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Nunito:wght@400;500;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3498db',
                        secondary: '#2c3e50',
                    },
                    fontFamily: {
                        sans: ['Nunito', 'system-ui', 'sans-serif'],
                        heading: ['Roboto', 'system-ui', 'sans-serif'],
                    },
                    animation: {
                        'slide-in': 'slideIn 0.3s ease-out',
                        'slide-out': 'slideOut 0.3s ease-in',
                    },
                    keyframes: {
                        slideIn: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(0)' },
                        },
                        slideOut: {
                            '0%': { transform: 'translateX(0)' },
                            '100%': { transform: 'translateX(-100%)' },
                        }
                    }
                },
            },
            variants: {
                extend: {
                    backgroundColor: ['active', 'disabled'],
                    textColor: ['active', 'disabled'],
                },
            },
        }
    </script>

    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        h1, h2, h3, h4, h5, h6 { font-family: 'Roboto', system-ui, sans-serif !important; }
        body, div, p { font-family: 'Nunito', system-ui, sans-serif !important; }
        
        /* Main content responsive layout */
        .main-content {
            margin-left: 0;
            margin-top: 4rem;
            min-height: calc(100vh - 4rem);
            transition: margin-left 0.3s ease;
        }
        
        @media (min-width: 768px) {
            .main-content {
                margin-left: 16rem;
            }
            .main-content.sidebar-collapsed {
                margin-left: 0;
            }
        }
        
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 4rem;
            left: 0;
            width: 16rem;
            height: calc(100vh - 4rem);
            background: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 40;
            overflow-y: auto;
        }
        
        @media (min-width: 768px) {
            .sidebar {
                transform: translateX(0);
            }
            .sidebar.collapsed {
                transform: translateX(-100%);
            }
        }
        
        .sidebar.open {
            transform: translateX(0);
        }
        
        /* Mobile overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 30;
            display: none;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Status badge styles */
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            color: white;
            transition: all 0.3s ease;
            display: inline-block;
            white-space: nowrap;
        }
        
        @media (min-width: 640px) {
            .status-badge {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
        }
        
        /* Table responsive styles */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -1rem;
            padding: 0 1rem;
        }
        
        @media (min-width: 640px) {
            .table-responsive {
                margin: 0;
                padding: 0;
            }
        }
        
        .table-responsive::-webkit-scrollbar { 
            height: 8px; 
        }
        .table-responsive::-webkit-scrollbar-track { 
            background: #f5f6fa; 
            border-radius: 4px; 
        }
        .table-responsive::-webkit-scrollbar-thumb { 
            background: #3498db; 
            border-radius: 4px; 
        }
        .table-responsive::-webkit-scrollbar-thumb:hover { 
            background: #2c3e50; 
        }
        
        /* Mobile table styles */
        @media (max-width: 768px) {
            .mobile-card {
                display: block;
                background: white;
                border-radius: 0.5rem;
                padding: 1rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .desktop-table {
                display: none;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-card {
                display: none;
            }
            
            .desktop-table {
                display: table;
            }
        }
        
        /* Touch-friendly button styles */
        .touch-button {
            min-height: 44px;
            min-width: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Improved form controls for mobile */
        select, input, button {
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        @media (max-width: 640px) {
            select, input[type="text"] {
                width: 100%;
                padding: 0.5rem;
            }
        }
        
        /* Loading animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .loading {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="antialiased text-gray-800 bg-gray-100">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:p-4 focus:bg-white focus:text-black">
        Skip to main content
    </a>

    <!-- Mobile sidebar overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebar()"></div>

    <?php include "header.php"; ?>
    <?php include "sidebar.php";
    
    // Check if user is logged in
    if (!isset($_SESSION['u_id'])) {
        header("Location: login.php");
        exit();
    }
    $status_update_message = isset($_SESSION['status_update_message']) ? $_SESSION['status_update_message'] : '';
    unset($_SESSION['status_update_message']);
    ?>

    <main id="main-content" class="main-content">
        <section class="bg-gray-100 min-h-full py-4 px-4 sm:py-6 sm:px-6 lg:px-8">
            <?php echo $status_update_message; ?>
            
            <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                    <div class="flex flex-col space-y-3 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                        <h4 class="text-base sm:text-lg font-bold text-gray-900">Active Employee Overview</h4>
                        <div class="relative w-full sm:w-auto sm:ml-4">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="employeeSearch" 
                                   class="w-full sm:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" 
                                   placeholder="Search employees..." 
                                   aria-label="Search active employees">
                        </div>
                    </div>
                </div>
                
                <!-- Mobile View Cards -->
                <div class="block md:hidden p-4" id="mobileEmployeeCards">
                    <?php foreach ($employees as $employee): 
                        $last_login_date = date('Y-m-d', strtotime($employee['last_login']));
                        $current_date = date('Y-m-d');
                        $display_status = ($last_login_date === $current_date) ? $employee['current_status'] : 'Offline';
                        
                        $name = $employee['name'];
                        $available_total = $employee['times']['Available'] + ($ongoing_durations[$name]['Available'] ?? 0);
                        $break1_total = $employee['times']['Break 1'] + ($ongoing_durations[$name]['Break 1'] ?? 0);
                        $break2_total = $employee['times']['Break 2'] + ($ongoing_durations[$name]['Break 2'] ?? 0);
                        $lunch_total = $employee['times']['Lunch Break'] + ($ongoing_durations[$name]['Lunch Break'] ?? 0);
                        $offline_total = $employee['times']['Offline'] + ($ongoing_durations[$name]['Offline'] ?? 0);
                    ?>
                    <div class="mobile-card" data-employee-name="<?php echo strtolower(htmlspecialchars($name)); ?>">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h5 class="font-semibold text-gray-900"><?php echo htmlspecialchars($name); ?></h5>
                                <span class="text-xs text-gray-500"><?php echo htmlspecialchars($employee['employee_id']); ?></span>
                            </div>
                            <span class="status-badge <?php echo getStatusColor($display_status); ?>">
                                <?php echo htmlspecialchars($display_status); ?>
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                            <div class="bg-gray-50 p-2 rounded">
                                <div class="text-xs text-gray-600 mb-1">Available</div>
                                <div class="font-semibold" data-total-time="<?php echo $available_total; ?>">
                                    <?php echo formatSeconds($available_total); ?>
                                </div>
                                <?php if (isset($ongoing_durations[$name]['Available'])): ?>
                                    <div class="text-xs text-green-600 counter" data-seconds="<?php echo $ongoing_durations[$name]['Available']; ?>">
                                        <?php echo formatSeconds($ongoing_durations[$name]['Available']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="bg-gray-50 p-2 rounded">
                                <div class="text-xs text-gray-600 mb-1">Break 1</div>
                                <div class="font-semibold" data-total-time="<?php echo $break1_total; ?>">
                                    <?php echo formatSeconds($break1_total); ?>
                                </div>
                                <?php if (isset($ongoing_durations[$name]['Break 1'])): ?>
                                    <div class="text-xs text-green-600 counter" data-seconds="<?php echo $ongoing_durations[$name]['Break 1']; ?>">
                                        <?php echo formatSeconds($ongoing_durations[$name]['Break 1']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="bg-gray-50 p-2 rounded">
                                <div class="text-xs text-gray-600 mb-1">Break 2</div>
                                <div class="font-semibold" data-total-time="<?php echo $break2_total; ?>">
                                    <?php echo formatSeconds($break2_total); ?>
                                </div>
                                <?php if (isset($ongoing_durations[$name]['Break 2'])): ?>
                                    <div class="text-xs text-green-600 counter" data-seconds="<?php echo $ongoing_durations[$name]['Break 2']; ?>">
                                        <?php echo formatSeconds($ongoing_durations[$name]['Break 2']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="bg-gray-50 p-2 rounded">
                                <div class="text-xs text-gray-600 mb-1">Lunch</div>
                                <div class="font-semibold" data-total-time="<?php echo $lunch_total; ?>">
                                    <?php echo formatSeconds($lunch_total); ?>
                                </div>
                                <?php if (isset($ongoing_durations[$name]['Lunch Break'])): ?>
                                    <div class="text-xs text-green-600 counter" data-seconds="<?php echo $ongoing_durations[$name]['Lunch Break']; ?>">
                                        <?php echo formatSeconds($ongoing_durations[$name]['Lunch Break']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="text-xs text-gray-500 mb-3">
                            Last updated: <?php echo htmlspecialchars($employee['last_login']); ?>
                        </div>
                        
                        <form method="post" class="flex flex-col space-y-2" onsubmit="return validateForm(this)">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                            <select name="new_status" 
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-sm" 
                                    <?php echo ($last_login_date !== $current_date) ? 'disabled aria-disabled="true"' : ''; ?>>
                                <?php
                                $statuses = ['Available', 'Break 1', 'Break 2', 'Lunch Break', 'Offline', 'Personal Time', 'Meeting'];
                                foreach ($statuses as $status) {
                                    $selected = ($display_status === $status) ? 'selected' : '';
                                    echo "<option value=\"$status\" $selected>$status</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" 
                                    class="w-full touch-button px-4 py-2 bg-primary text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                                    <?php echo ($last_login_date !== $current_date) ? 'disabled aria-disabled="true"' : ''; ?>>
                                <i class="fas fa-save mr-2"></i> Update Status
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Desktop View Table -->
                <div class="table-responsive hidden md:block">
                    <table class="desktop-table w-full divide-y divide-gray-200" aria-label="Active employee status overview">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break 1</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break 2</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lunch</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Offline</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="employeeTable">
                            <?php foreach ($employees as $employee): 
                                $last_login_date = date('Y-m-d', strtotime($employee['last_login']));
                                $current_date = date('Y-m-d');
                                $display_status = ($last_login_date === $current_date) ? $employee['current_status'] : 'Offline';
                                
                                $name = $employee['name'];
                                $available_total = $employee['times']['Available'] + ($ongoing_durations[$name]['Available'] ?? 0);
                                $break1_total = $employee['times']['Break 1'] + ($ongoing_durations[$name]['Break 1'] ?? 0);
                                $break2_total = $employee['times']['Break 2'] + ($ongoing_durations[$name]['Break 2'] ?? 0);
                                $lunch_total = $employee['times']['Lunch Break'] + ($ongoing_durations[$name]['Lunch Break'] ?? 0);
                                $offline_total = $employee['times']['Offline'] + ($ongoing_durations[$name]['Offline'] ?? 0);
                            ?>
                            <tr data-employee-id="<?php echo htmlspecialchars($employee['id']); ?>">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($name); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="status-badge <?php echo getStatusColor($display_status); ?>">
                                        <?php echo htmlspecialchars($display_status); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-semibold" data-total-time="<?php echo $available_total; ?>">
                                        <?php echo formatSeconds($available_total); ?>
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Done: <?php echo formatSeconds($employee['times']['Available']); ?>
                                        <?php if (isset($ongoing_durations[$name]['Available'])): ?>
                                            <br>Now: <span class="text-green-600 font-medium counter" data-seconds="<?php echo $ongoing_durations[$name]['Available']; ?>">
                                                <?php echo formatSeconds($ongoing_durations[$name]['Available']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-semibold" data-total-time="<?php echo $break1_total; ?>">
                                        <?php echo formatSeconds($break1_total); ?>
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Done: <?php echo formatSeconds($employee['times']['Break 1']); ?>
                                        <?php if (isset($ongoing_durations[$name]['Break 1'])): ?>
                                            <br>Now: <span class="text-green-600 font-medium counter" data-seconds="<?php echo $ongoing_durations[$name]['Break 1']; ?>">
                                                <?php echo formatSeconds($ongoing_durations[$name]['Break 1']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-semibold" data-total-time="<?php echo $break2_total; ?>">
                                        <?php echo formatSeconds($break2_total); ?>
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Done: <?php echo formatSeconds($employee['times']['Break 2']); ?>
                                        <?php if (isset($ongoing_durations[$name]['Break 2'])): ?>
                                            <br>Now: <span class="text-green-600 font-medium counter" data-seconds="<?php echo $ongoing_durations[$name]['Break 2']; ?>">
                                                <?php echo formatSeconds($ongoing_durations[$name]['Break 2']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-semibold" data-total-time="<?php echo $lunch_total; ?>">
                                        <?php echo formatSeconds($lunch_total); ?>
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Done: <?php echo formatSeconds($employee['times']['Lunch Break']); ?>
                                        <?php if (isset($ongoing_durations[$name]['Lunch Break'])): ?>
                                            <br>Now: <span class="text-green-600 font-medium counter" data-seconds="<?php echo ($ongoing_durations[$name]['Lunch Break']); ?>">
                                                <?php echo formatSeconds($ongoing_durations[$name]['Lunch Break']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-semibold" data-total-time="<?php echo $offline_total; ?>">
                                        <?php echo formatSeconds($offline_total); ?>
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Done: <?php echo formatSeconds($employee['times']['Offline']); ?>
                                        <?php if (isset($ongoing_durations[$name]['Offline'])): ?>
                                            <br>Now: <span class="text-green-600 font-medium counter" data-seconds="<?php echo ($ongoing_durations[$name]['Offline']); ?>">
                                                <?php echo formatSeconds($ongoing_durations[$name]['Offline']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($employee['last_login']); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <form method="post" class="flex items-center space-x-2" onsubmit="return validateForm(this)">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                        <select name="new_status" 
                                                class="border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-primary text-sm" 
                                                <?php echo ($last_login_date !== $current_date) ? 'disabled aria-disabled="true"' : ''; ?>>
                                            <?php
                                            $statuses = ['Available', 'Break 1', 'Break 2', 'Lunch Break', 'Offline', 'Personal Time', 'Meeting'];
                                            foreach ($statuses as $status) {
                                                $selected = ($display_status === $status) ? 'selected' : '';
                                                echo "<option value=\"$status\" $selected>$status</option>";
                                            }
                                            ?>
                                        </select>
                                        <button type="submit" 
                                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                                                <?php echo ($last_login_date !== $current_date) ? 'disabled aria-disabled="true"' : ''; ?>>
                                            <i class="fas fa-save mr-1"></i> Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Mobile menu toggle functions
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }
        
        function openSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuButton = document.querySelector('[onclick*="toggleSidebar"]');
            
            if (window.innerWidth < 768 && 
                sidebar && 
                sidebar.classList.contains('open') && 
                !sidebar.contains(event.target) && 
                !menuButton.contains(event.target)) {
                closeSidebar();
            }
        });
        
        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 768) {
                    closeSidebar();
                }
            }, 250);
        });

        function showSection(sectionId) {
            console.log(`Navigating to section: ${sectionId}`);
            // Close sidebar on mobile after navigation
            if (window.innerWidth < 768) {
                closeSidebar();
            }
        }

        function updateClock() {
            const now = new Date().toLocaleString("en-US", { timeZone: "America/New_York" });
            const date = new Date(now);
            const hours = date.getHours() % 12 || 12;
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            const ampm = date.getHours() >= 12 ? 'PM' : 'AM';
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const currentTime = `${hours}:${minutes}:${seconds} ${ampm}`;
            const currentDate = `${day}/${month}/${year}`;
            const clockElement = document.getElementById('clock');
            if (clockElement) clockElement.innerHTML = `<span class="hidden sm:inline">${currentDate} | </span>${currentTime}`;
        }

        // Search functionality with debounce for both desktop and mobile
        const searchInput = document.getElementById('employeeSearch');
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchText = this.value.toLowerCase();
                
                // Search in desktop table
                const rows = document.querySelectorAll('#employeeTable tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchText) ? '' : 'none';
                });
                
                // Search in mobile cards
                const cards = document.querySelectorAll('.mobile-card');
                cards.forEach(card => {
                    const name = card.getAttribute('data-employee-name');
                    const text = card.textContent.toLowerCase();
                    card.style.display = (name.includes(searchText) || text.includes(searchText)) ? '' : 'none';
                });
            }, 300);
        });

        // Auto-dismiss alerts
        document.querySelectorAll('[role="alert"]').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });

        // Timer for ongoing statuses
        function updateTimers() {
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                let seconds = parseInt(counter.getAttribute('data-seconds') || 0);
                seconds++;
                counter.setAttribute('data-seconds', seconds);
                counter.textContent = formatSeconds(seconds);

                // Update total time if it exists
                const totalTimeElement = counter.closest('td')?.querySelector('[data-total-time]') || 
                                       counter.closest('div')?.querySelector('[data-total-time]');
                if (totalTimeElement) {
                    const baseTime = parseInt(totalTimeElement.getAttribute('data-total-time') || 0) - seconds + 1;
                    const totalSeconds = baseTime + seconds;
                    totalTimeElement.textContent = formatSeconds(totalSeconds);
                }
            });
        }

        function formatSeconds(seconds) {
            if (!Number.isInteger(seconds) || seconds < 0) return '00:00:00';
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }

        // Client-side form validation
        function validateForm(form) {
            const select = form.querySelector('select[name="new_status"]');
            if (!select.value) {
                alert('Please select a valid status.');
                return false;
            }
            return true;
        }

        // Initialize
        setInterval(updateClock, 1000);
        updateClock();
        setInterval(updateTimers, 1000);
        
        // Prevent double tap zoom on iOS
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</body>
</html>