<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'config.php';
require_once 'time_calculations.php';

// Input sanitization and defaults
$selected_month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}-\d{2}$/']
]) ?? date('Y-m');
$selected_date = filter_input(INPUT_GET, 'date', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}-\d{2}-\d{2}$/']
]) ?? null;
$page = max(1, (int) filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => ['default' => 1]
]));
$search = filter_input(INPUT_GET, 'search') ?? '';
$records_per_page = 20;

// Initialize pagination variables
$offset = ($page - 1) * $records_per_page;
$total_records = 0;
$total_pages = 1;

// Reset pagination on search or new date selection
if ($search || ($selected_date && isset($_GET['date']))) {
    $page = 1;
    $offset = 0;
}

// Fetch distinct months from status_changes
try {
    $months_query = $db->prepare("SELECT DISTINCT DATE_FORMAT(start_time, '%Y-%m') AS month FROM status_changes ORDER BY month DESC");
    $months_query->execute();
    $months_result = $months_query->get_result();
    $months = [];
    while ($row = $months_result->fetch_assoc()) {
        $months[] = $row['month'];
    }
    if (empty($months)) {
        $months[] = $selected_month; // Allow current month even if no data
    }
    $months_query->close();
} catch (Exception $e) {
    error_log("Error fetching months: " . $e->getMessage());
    $months = [$selected_month];
}

// Fetch all users
try {
    $users_query = $db->prepare("SELECT u_id, name, work_start FROM user");
    $users_query->execute();
    $users_result = $users_query->get_result();
    $users = [];
    while ($row = $users_result->fetch_assoc()) {
        $users[$row['u_id']] = $row;
    }
    $users_query->close();
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
}

// Calculate present counts and stats for each day in the selected month
$month_start = "$selected_month-01";
$month_end = date('Y-m-t', strtotime($month_start));
$days_in_month = (int) date('t', strtotime($month_start));
$calendar = [];
$stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0];

try {
    $present_query = $db->prepare("
        SELECT DATE(sc.start_time) AS date, COUNT(DISTINCT sc.u_id) AS present_count
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE DATE(sc.start_time) BETWEEN ? AND ?
        AND sc.status IN ('Available', 'Break 1', 'Break 2', 'Lunch Break', 'Meeting', 'Personal Time')
        GROUP BY DATE(sc.start_time)
        HAVING SUM(CASE WHEN sc.status = 'Available' THEN TIMESTAMPDIFF(SECOND, sc.start_time, COALESCE(sc.end_time, NOW())) ELSE 0 END) >= 14400
    ");
    $present_query->bind_param('ss', $month_start, $month_end);
    $present_query->execute();
    $present_result = $present_query->get_result();

    // Initialize calendar array
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = "$selected_month-" . sprintf("%02d", $day);
        $calendar[$date] = ['present_count' => 0];
    }

    // Populate present counts
    $active_days = 0;
    while ($row = $present_result->fetch_assoc()) {
        $calendar[$row['date']]['present_count'] = (int) $row['present_count'];
        $stats['present'] += $row['present_count'];
        $active_days++;
    }
    $present_query->close();

    // Calculate absent count only for active days
    $stats['absent'] = $active_days * count($users) - $stats['present'];
} catch (Exception $e) {
    error_log("Error calculating present counts: " . $e->getMessage());
    $calendar = [];
}

// Fetch detailed records for the selected date
$filtered_records = [];
if ($selected_date) {
    try {
        $search_condition = $search ? "AND (u.name LIKE ? OR sc.status LIKE ?)" : '';
        $search_param = $search ? "%$search%" : '';
        $query = "
            SELECT u.u_id, u.name, DATE(sc.start_time) AS date, MIN(sc.start_time) AS check_in
            FROM status_changes sc
            JOIN user u ON sc.u_id = u.u_id
            WHERE DATE(sc.start_time) = ? $search_condition
            AND sc.status IN ('Available', 'Break 1', 'Break 2', 'Lunch Break', 'Meeting', 'Personal Time')
            GROUP BY sc.u_id
            ORDER BY u.name
            LIMIT ? OFFSET ?
        ";
        $table_stmt = $db->prepare($query);
        if ($search) {
            $table_stmt->bind_param('sssii', $selected_date, $search_param, $search_param, $records_per_page, $offset);
        } else {
            $table_stmt->bind_param('sii', $selected_date, $records_per_page, $offset);
        }
        $table_stmt->execute();
        $table_result = $table_stmt->get_result();

        $total_records_query = "
            SELECT COUNT(DISTINCT sc.u_id) AS total
            FROM status_changes sc
            JOIN user u ON sc.u_id = u.u_id
            WHERE DATE(sc.start_time) = ? $search_condition
        ";
        $total_records_stmt = $db->prepare($total_records_query);
        if ($search) {
            $total_records_stmt->bind_param('sss', $selected_date, $search_param, $search_param);
        } else {
            $total_records_stmt->bind_param('s', $selected_date);
        }
        $total_records_stmt->execute();
        $total_records = (int) $total_records_stmt->get_result()->fetch_assoc()['total'];
        $total_pages = ceil($total_records / $records_per_page);

        // Process detailed records
        while ($row = $table_result->fetch_assoc()) {
            $u_id = $row['u_id'];
            $date = $row['date'];
            $times = calculateTimes($db, $u_id, $date);
            $available_time = $times['Available'] ?? 0;
            $login_time = $row['check_in'] ? substr($row['check_in'], 11) : null;
            $work_start = $users[$u_id]['work_start'] ?? '09:00:00';

            $status = [];
            if (!$login_time || $available_time < 3600) {
                $status[] = 'Absent';
            } else {
                if ($available_time >= 28800) {
                    $status[] = 'Present';
                } elseif ($available_time >= 14400) {
                    $status[] = 'Half Day';
                    $status[] = 'Present';
                    $stats['half_day']++;
                }
                if ($login_time > $work_start) {
                    $status[] = 'Late';
                    $stats['late']++;
                }
            }
            $row['status'] = $status;
            $row['total_hours'] = formatSeconds($times['Available'] ?? 0);
            $row['break_time'] = formatSeconds(($times['Break 1'] ?? 0) + ($times['Break 2'] ?? 0) + ($times['Lunch Break'] ?? 0));
            $row['status_text'] = implode(' + ', $row['status']);
            $filtered_records[] = $row;
        }
        $table_stmt->close();
        $total_records_stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching detailed records: " . $e->getMessage());
        $filtered_records = [];
    }
}

// Calendar data for display
$first_day = new DateTime($month_start);
$start_weekday = (int) $first_day->format('w');
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="description" content="Admin Employee Management Dashboard for tracking all employees' attendance.">
    <meta name="theme-color" content="#ffffff">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Admin Employee Management Dashboard | All Employees</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Nunito:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
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

    <!-- Alpine.js and Icons -->
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Roboto', system-ui, sans-serif !important;
        }
        body, div, p, select, input, table {
            font-family: 'Nunito', system-ui, sans-serif !important;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Main content responsive layout */
        .main-content {
            transition: margin-left 0.3s ease-in-out;
        }
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 16rem;
                margin-top: 4rem;
                min-height: calc(100vh - 4rem);
                padding: 1rem;
            }
        }
        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0;
                margin-top: 4rem;
                min-height: calc(100vh - 4rem);
                padding: 1rem 0.5rem;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
        }

        /* Card hover effects */
        .card-hover {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Status badge hover */
        .status-badge {
            transition: background-color 0.2s ease-in-out;
        }
        .status-badge:hover {
            filter: brightness(90%);
        }

        /* Calendar day hover */
        .calendar-day {
            transition: transform 0.2s ease-in-out, background-color 0.2s ease-in-out;
        }
        .calendar-day:hover {
            transform: scale(1.05);
            background-color: #f0f9ff;
        }

        /* Mobile table responsiveness */
        @media (max-width: 640px) {
            .mobile-table .record-item {
                display: block;
                border: 1px solid #e5e7eb;
                border-radius: 0.5rem;
                margin-bottom: 1rem;
                padding: 1rem;
                background: white;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            .mobile-table .table-content {
                display: block;
                text-align: left !important;
                padding: 0.5rem 0 !important;
            }
            .mobile-table .table-content:before {
                content: attr(data-label) ": ";
                font-weight: 600;
                color: #374151;
                display: block;
                margin-bottom: 0.25rem;
            }
        }

        /* Modal animation */
        .modal-content {
            transform: scale(0.7);
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
        .modal-content.show {
            transform: scale(1);
            opacity: 1;
        }
    </style>
</head>

<body class="antialiased text-gray-800 bg-gray-50" x-data="{ sidebarOpen: false, ...allEmployees() }">
    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:p-4 focus:bg-white focus:text-black">
        Skip to main content
    </a>

    <?php include "header.php"; ?>
    <?php include "sidebar.php"; ?>

    <!-- Main content -->
    <main id="main-content" class="main-content">
        <section id="admin-dashboard" class="page-section py-4 sm:py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
                <!-- Header: Title, Month Filter, Search -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Admin: All Employees Attendance</h2>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <select 
                            x-model="selectedMonth" 
                            @change="changeMonth($event.target.value)"
                            class="w-full sm:w-44 rounded-md border-gray-300 shadow-sm bg-white text-sm focus:ring-blue-500 focus:border-blue-500">
                            <?php foreach ($months as $m): ?>
                                <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $m === $selected_month ? 'selected' : ''; ?>><?php echo date('F Y', strtotime($m . '-01')); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="relative w-full sm:w-64" x-show="selectedDate">
                            <input 
                                type="text" 
                                x-model.debounce.500ms="searchQuery" 
                                placeholder="Search by name or status..." 
                                class="w-full rounded-md border-gray-300 shadow-sm pl-10 pr-10 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <button 
                                x-show="searchQuery" 
                                @click="searchQuery = ''" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200 card-hover">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-2.5">
                                <i class="fas fa-check h-5 w-5 text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Present Days</p>
                                <p class="text-lg font-semibold text-gray-900"><?php echo $stats['present']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200 card-hover">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-100 rounded-md p-2.5">
                                <i class="fas fa-times h-5 w-5 text-red-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Absent Days</p>
                                <p class="text-lg font-semibold text-gray-900"><?php echo $stats['absent']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200 card-hover">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-orange-100 rounded-md p-2.5">
                                <i class="fas fa-clock h-5 w-5 text-orange-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Late Arrivals</p>
                                <p class="text-lg font-semibold text-gray-900"><?php echo $stats['late']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200 card-hover">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-md p-2.5">
                                <i class="fas fa-calendar-day h-5 w-5 text-yellow-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Half Days</p>
                                <p class="text-lg font-semibold text-gray-900"><?php echo $stats['half_day']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar View -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 card-hover mb-6">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Monthly Attendance Overview</h3>
                        <div class="flex space-x-2">
                            <button 
                                @click="changeMonth('<?php echo htmlspecialchars(date('Y-m', strtotime($selected_month . '-01 -1 month'))); ?>')"
                                class="inline-flex items-center px-3 py-1 bg-gray-200 rounded-md text-sm text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-chevron-left mr-2"></i>Prev
                            </button>
                            <button 
                                @click="changeMonth('<?php echo htmlspecialchars(date('Y-m', strtotime($selected_month . '-01 +1 month'))); ?>')"
                                class="inline-flex items-center px-3 py-1 bg-gray-200 rounded-md text-sm text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Next<i class="fas fa-chevron-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6">
                        <div class="grid grid-cols-7 text-center text-sm font-medium text-gray-800 border-b mb-2">
                            <div class="py-2">Sun</div>
                            <div class="py-2">Mon</div>
                            <div class="py-2">Tue</div>
                            <div class="py-2">Wed</div>
                            <div class="py-2">Thu</div>
                            <div class="py-2">Fri</div>
                            <div class="py-2">Sat</div>
                        </div>
                        <div class="grid grid-cols-7 gap-1">
                            <?php
                            // Add empty cells for days before the month starts
                            for ($i = 0; $i < $start_weekday; $i++):
                            ?>
                                <div class="p-2 h-20 sm:h-24"></div>
                            <?php endfor; ?>
                            
                            <?php
                            // Add days of the month
                            for ($day = 1; $day <= $days_in_month; $day++):
                                $date_str = $selected_month . '-' . sprintf('%02d', $day);
                                $present_count = $calendar[$date_str]['present_count'] ?? 0;
                                $bg_color = $present_count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500';
                                $is_selected = $date_str === $selected_date;
                            ?>
                                <button 
                                    @click="openDetails('<?php echo htmlspecialchars($date_str); ?>')"
                                    class="calendar-day bg-white p-2 h-20 sm:h-24 border border-gray-200 flex flex-col rounded-md text-left focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $is_selected ? 'border-blue-500' : ''; ?>">
                                    <span class="font-bold text-gray-700"><?php echo $day; ?></span>
                                    <div class="mt-1 text-xs text-center p-1 rounded-md status-badge <?php echo $bg_color; ?>">
                                        <?php echo $present_count; ?> Present
                                    </div>
                                </button>
                            <?php endfor; ?>
                            
                            <?php
                            // Add empty cells for remaining days
                            $remaining_days = 7 - (($start_weekday + $days_in_month) % 7);
                            if ($remaining_days < 7):
                                for ($i = 0; $i < $remaining_days; $i++):
                            ?>
                                <div class="p-2 h-20 sm:h-24"></div>
                            <?php 
                                endfor;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Detailed Records Table -->
                <div x-show="selectedDate" class="bg-white rounded-lg shadow-sm border border-gray-200 card-hover" x-cloak>
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900" x-text="`Attendance Details for ${formatDate(selectedDate)}`"></h3>
                        <button @click="clearDate" class="text-sm font-medium text-blue-600 hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Clear Date
                        </button>
                    </div>
                    <div class="p-4 sm:p-6" x-show="filteredRecords.length > 0" x-cloak>
                        <div class="overflow-x-auto mobile-table">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check In</th>
                                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break Time</th>
                                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="record in paginatedRecords" :key="record.u_id">
                                        <tr class="record-item">
                                            <td class="px-4 sm:px-6 py-4 table-content" data-label="Employee">
                                                <div class="text-sm text-gray-900" x-text="record.name"></div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 table-content" data-label="Check In">
                                                <div class="text-sm text-gray-900" x-text="record.check_in ? formatTime(record.check_in) : '-'"></div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 table-content" data-label="Total Hours">
                                                <div class="text-sm text-gray-900" x-text="record.total_hours"></div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 table-content" data-label="Break Time">
                                                <div class="text-sm text-gray-900" x-text="record.break_time"></div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 table-content" data-label="Status">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-badge" :class="getStatusClass(record.status)">
                                                    <span x-text="record.status_text"></span>
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <div class="px-4 sm:px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between">
                            <p class="text-sm text-gray-700 mb-2 sm:mb-0">
                                Showing <span class="font-medium" x-text="offset + 1"></span> to 
                                <span class="font-medium" x-text="Math.min(offset + recordsPerPage, totalRecords)"></span> of 
                                <span class="font-medium" x-text="totalRecords"></span> results
                            </p>
                            <nav class="flex items-center space-x-1" aria-label="Pagination">
                                <button 
                                    @click="changePage(currentPage - 1)" 
                                    :disabled="currentPage <= 1"
                                    class="inline-flex items-center px-2 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <template x-for="page in paginationRange" :key="page">
                                    <button 
                                        @click="changePage(page)"
                                        class="inline-flex items-center px-3 py-2 rounded-md border text-sm font-medium"
                                        :class="page === currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                                        x-text="page === '...' ? '...' : page">
                                    </button>
                                </template>
                                <button 
                                    @click="changePage(currentPage + 1)" 
                                    :disabled="currentPage >= totalPages"
                                    class="inline-flex items-center px-2 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                    <div x-show="filteredRecords.length === 0 && selectedDate" class="p-4 sm:p-6 text-center text-gray-500" x-cloak>
                        <p x-text="searchQuery ? `No records found for your search term '${searchQuery}'.` : 'No attendance records for this date.'"></p>
                    </div>
                </div>

                <!-- Details Modal -->
                <div x-show="detailsModalOpen" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50" id="detailsModal" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
                    <div class="bg-white rounded-lg p-4 sm:p-6 max-w-sm sm:max-w-md w-full modal-content" :class="{ 'show': detailsModalOpen }">
                        <h2 class="text-lg font-medium text-gray-900 mb-4" x-text="`Attendance Summary for ${formatDate(selectedDate)}`"></h2>
                        <template x-if="filteredRecords.length > 0">
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Total Employees</p>
                                        <p class="text-sm text-gray-900" x-text="filteredRecords.length"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Present</p>
                                        <p class="text-sm text-gray-900" x-text="filteredRecords.filter(r => r.status.includes('Present') || r.status.includes('Half Day')).length"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Absent</p>
                                        <p class="text-sm text-gray-900" x-text="filteredRecords.filter(r => r.status.includes('Absent')).length"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Late Arrivals</p>
                                        <p class="text-sm text-gray-900" x-text="filteredRecords.filter(r => r.status.includes('Late')).length"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Half Days</p>
                                        <p class="text-sm text-gray-900" x-text="filteredRecords.filter(r => r.status.includes('Half Day')).length"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="filteredRecords.length === 0">
                            <p class="text-sm text-gray-500">No attendance data for this date. It may be a non-working day.</p>
                        </template>
                        <div class="mt-6 flex justify-end">
                            <button 
                                @click="closeDetailsModal" 
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('allEmployees', () => ({
                selectedMonth: '<?php echo htmlspecialchars($selected_month); ?>',
                selectedDate: '<?php echo htmlspecialchars($selected_date ?? ''); ?>',
                searchQuery: '<?php echo htmlspecialchars($search); ?>',
                records: <?php echo json_encode($filtered_records); ?>,
                recordsPerPage: <?php echo $records_per_page; ?>,
                currentPage: <?php echo $page; ?>,
                filteredRecords: [],
                paginatedRecords: [],
                totalRecords: <?php echo $total_records; ?>,
                totalPages: <?php echo $total_pages; ?>,
                offset: <?php echo $offset; ?>,
                detailsModalOpen: false,

                init() {
                    this.updateRecords();
                    this.$watch('searchQuery', () => this.updateRecords());
                    this.$watch('currentPage', () => this.updateRecords());
                    this.$watch('detailsModalOpen', (value) => {
                        document.body.style.overflow = value ? 'hidden' : '';
                    });
                    this.$watch('sidebarOpen', (value) => {
                        document.body.style.overflow = value ? 'hidden' : '';
                    });
                },

                updateRecords() {
                    // Filter records
                    this.filteredRecords = this.records.filter(record => {
                        return this.searchQuery ? (
                            record.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                            record.status_text.toLowerCase().includes(this.searchQuery.toLowerCase())
                        ) : true;
                    });

                    // Update pagination
                    this.totalRecords = this.filteredRecords.length;
                    this.totalPages = Math.max(1, Math.ceil(this.totalRecords / this.recordsPerPage));
                    this.currentPage = Math.max(1, Math.min(this.currentPage, this.totalPages));
                    this.offset = (this.currentPage - 1) * this.recordsPerPage;
                    this.paginatedRecords = this.filteredRecords.slice(this.offset, this.offset + this.recordsPerPage);
                },

                get paginationRange() {
                    const range = [];
                    const delta = 2;
                    const start = Math.max(1, this.currentPage - delta);
                    const end = Math.min(this.totalPages, this.currentPage + delta);

                    if (start > 1) range.push(1);
                    if (start > 2) range.push('...');
                    for (let i = start; i <= end; i++) range.push(i);
                    if (end < this.totalPages - 1) range.push('...');
                    if (end < this.totalPages) range.push(this.totalPages);
                    return range;
                },

                changePage(page) {
                    if (page >= 1 && page <= this.totalPages) {
                        this.currentPage = page;
                        this.updateQueryString();
                    }
                },

                changeMonth(month) {
                    this.selectedMonth = month;
                    this.selectedDate = '';
                    this.currentPage = 1;
                    this.searchQuery = '';
                    this.updateQueryString();
                },

                openDetails(date) {
                    this.selectedDate = date;
                    this.currentPage = 1;
                    this.searchQuery = '';
                    this.updateQueryString();
                    this.detailsModalOpen = true;
                },

                clearDate() {
                    this.selectedDate = '';
                    this.searchQuery = '';
                    this.currentPage = 1;
                    this.updateQueryString();
                },

                closeDetailsModal() {
                    this.detailsModalOpen = false;
                },

                updateQueryString() {
                    const params = new URLSearchParams();
                    params.set('month', this.selectedMonth);
                    if (this.selectedDate) params.set('date', this.selectedDate);
                    if (this.currentPage > 1) params.set('page', this.currentPage);
                    if (this.searchQuery) params.set('search', this.searchQuery);
                    window.location.search = params.toString();
                },

                formatDate(date) {
                    return new Date(date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                },

                formatTime(time) {
                    return new Date(time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                },

                getStatusClass(status) {
                    if (status.includes('Present') || status.includes('Half Day')) return 'bg-green-100 text-green-800';
                    if (status.includes('Late')) return 'bg-orange-100 text-orange-800';
                    return 'bg-red-100 text-red-800';
                }
            }));
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const alpineData = document.querySelector('[x-data]');
                if (alpineData && alpineData.__x) {
                    if (alpineData.__x.$data.detailsModalOpen) {
                        alpineData.__x.$data.closeDetailsModal();
                    }
                    if (alpineData.__x.$data.sidebarOpen) {
                        alpineData.__x.$data.sidebarOpen = false;
                    }
                }
            }
        });
    </script>
</body>
</html>