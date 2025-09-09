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
        $months[] = $selected_month;
    }
    $months_query->close();
} catch (Exception $e) {
    error_log("Error fetching months: " . $e->getMessage());
    $months = [$selected_month];
}

// Fetch all users for counting
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

// Calculate stats for each day in the selected month
$month_start = "$selected_month-01";
$month_end = date('Y-m-t', strtotime($month_start));
$days_in_month = (int) date('t', strtotime($month_start));
$calendar = [];
$monthly_stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0];

try {
    // Get present and late counts for each day
    $daily_stats_query = $db->prepare("
        SELECT 
            DATE(sc.start_time) AS date,
            u.u_id,
            u.work_start,
            MIN(sc.start_time) AS check_in,
            SUM(CASE WHEN sc.status = 'Available' THEN TIMESTAMPDIFF(SECOND, sc.start_time, COALESCE(sc.end_time, NOW())) ELSE 0 END) AS available_seconds
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE DATE(sc.start_time) BETWEEN ? AND ?
        AND sc.status IN ('Available', 'Break 1', 'Break 2', 'Lunch Break', 'Meeting', 'Personal Time')
        GROUP BY DATE(sc.start_time), u.u_id
    ");
    
    $daily_stats_query->bind_param('ss', $month_start, $month_end);
    $daily_stats_query->execute();
    $daily_result = $daily_stats_query->get_result();
    
    // Initialize calendar array
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = "$selected_month-" . sprintf("%02d", $day);
        $calendar[$date] = ['present_count' => 0, 'late_count' => 0];
    }
    
    // Process daily stats
    while ($row = $daily_result->fetch_assoc()) {
        $date = $row['date'];
        $available_seconds = (int)$row['available_seconds'];
        $check_in_time = substr($row['check_in'], 11, 8);
        $work_start = $row['work_start'] ?? '09:00:00';
        
        // Count as present if worked at least 4 hours
        if ($available_seconds >= 14400) {
            $calendar[$date]['present_count']++;
            $monthly_stats['present']++;
            
            // Check if late
            if ($check_in_time > $work_start) {
                $calendar[$date]['late_count']++;
                $monthly_stats['late']++;
            }
        }
        
        // Count half days (4-8 hours)
        if ($available_seconds >= 14400 && $available_seconds < 28800) {
            $monthly_stats['half_day']++;
        }
    }
    $daily_stats_query->close();
    
} catch (Exception $e) {
    error_log("Error calculating daily stats: " . $e->getMessage());
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
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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

        /* Calendar day hover */
        .calendar-day {
            transition: all 0.2s ease-in-out;
        }
        .calendar-day:hover {
            transform: scale(1.05);
            background-color: #f0f9ff;
            z-index: 10;
        }

        /* Mobile table responsiveness */
        @media (max-width: 640px) {
            .mobile-table tbody tr {
                display: block;
                border: 1px solid #e5e7eb;
                border-radius: 0.5rem;
                margin-bottom: 1rem;
                padding: 1rem;
                background: white;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            .mobile-table td {
                display: block;
                text-align: left !important;
                padding: 0.5rem 0 !important;
            }
            .mobile-table td:before {
                content: attr(data-label) ": ";
                font-weight: 600;
                color: #374151;
                display: inline-block;
                width: 120px;
            }
            .mobile-table thead {
                display: none;
            }
        }

        /* Loading spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body class="antialiased text-gray-800 bg-gray-50" x-data="{ sidebarOpen: false, ...allEmployees() }">
    <?php include "header.php"; ?>
    <?php include "sidebar.php"; ?>

    <!-- Main content -->
    <main id="main-content" class="main-content">
        <section id="admin-dashboard" class="page-section py-4 sm:py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Admin: All Employees Attendance</h2>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <select 
                            x-model="selectedMonth" 
                            @change="changeMonth($event.target.value)"
                            class="w-full sm:w-44 rounded-md border-gray-300 shadow-sm bg-white text-sm focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                            <?php foreach ($months as $m): ?>
                                <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $m === $selected_month ? 'selected' : ''; ?>>
                                    <?php echo date('F Y', strtotime($m . '-01')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="relative w-full sm:w-64" x-show="selectedDate">
                            <input 
                                type="text" 
                                x-model.debounce.300ms="searchQuery" 
                                @input="filterRecords()"
                                placeholder="Search by name or status..." 
                                class="w-full rounded-md border-gray-300 shadow-sm pl-10 pr-10 text-sm focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <button 
                                x-show="searchQuery" 
                                @click="searchQuery = ''; filterRecords()" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Monthly Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200 card-hover">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-2.5">
                                <i class="fas fa-check h-5 w-5 text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Present</p>
                                <p class="text-lg font-semibold text-gray-900"><?php echo $monthly_stats['present']; ?></p>
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
                                <p class="text-lg font-semibold text-gray-900"><?php echo $monthly_stats['late']; ?></p>
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
                                <p class="text-lg font-semibold text-gray-900"><?php echo $monthly_stats['half_day']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200 card-hover">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-md p-2.5">
                                <i class="fas fa-users h-5 w-5 text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Employees</p>
                                <p class="text-lg font-semibold text-gray-900"><?php echo count($users); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar View -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Monthly Attendance Calendar</h3>
                        <div class="flex space-x-2">
                            <button 
                                @click="changeMonth('<?php echo date('Y-m', strtotime($selected_month . '-01 -1 month')); ?>')"
                                class="inline-flex items-center px-3 py-1 bg-gray-200 rounded-md text-sm text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-chevron-left mr-2"></i>Prev
                            </button>
                            <button 
                                @click="changeMonth('<?php echo date('Y-m', strtotime($selected_month . '-01 +1 month')); ?>')"
                                class="inline-flex items-center px-3 py-1 bg-gray-200 rounded-md text-sm text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Next<i class="fas fa-chevron-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6">
                        <div class="grid grid-cols-7 text-center text-xs sm:text-sm font-medium text-gray-800 border-b mb-2">
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
                            // Add empty cells for days before the first day of the month
                            for ($i = 0; $i < $start_weekday; $i++):
                            ?>
                                <div class="bg-gray-50 p-1 sm:p-2 h-16 sm:h-24"></div>
                            <?php
                            endfor;
                            
                            // Add cells for each day of the month
                            for ($day = 1; $day <= $days_in_month; $day++):
                                $date_str = $selected_month . '-' . sprintf('%02d', $day);
                                $present_count = $calendar[$date_str]['present_count'] ?? 0;
                                $late_count = $calendar[$date_str]['late_count'] ?? 0;
                                $is_weekend = date('N', strtotime($date_str)) >= 6;
                                $day_bg = $is_weekend ? 'bg-gray-50' : 'bg-white';
                            ?>
                                <button 
                                    @click="selectDate('<?php echo $date_str; ?>')"
                                    class="calendar-day <?php echo $day_bg; ?> p-1 sm:p-2 h-16 sm:h-24 border border-gray-200 flex flex-col rounded-md text-left hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 relative"
                                    :class="{ 'ring-2 ring-blue-500': selectedDate === '<?php echo $date_str; ?>' }">
                                    <span class="font-bold text-xs sm:text-sm text-gray-700"><?php echo $day; ?></span>
                                    <?php if ($present_count > 0): ?>
                                        <div class="mt-auto space-y-0.5">
                                            <div class="text-xs flex items-center justify-between">
                                                <span class="text-green-600 font-medium"><?php echo $present_count; ?></span>
                                                <i class="fas fa-check text-green-500 text-xs"></i>
                                            </div>
                                            <?php if ($late_count > 0): ?>
                                            <div class="text-xs flex items-center justify-between">
                                                <span class="text-orange-600 font-medium"><?php echo $late_count; ?></span>
                                                <i class="fas fa-clock text-orange-500 text-xs"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </button>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Attendance Details Table -->
                <div x-show="selectedDate" class="bg-white rounded-lg shadow-sm border border-gray-200" x-cloak>
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">
                            Attendance Details for <span x-text="formatDate(selectedDate)"></span>
                        </h3>
                        <button 
                            @click="clearDate()" 
                            class="text-sm font-medium text-blue-600 hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-times mr-1"></i>Clear
                        </button>
                    </div>
                    
                    <!-- Loading Spinner -->
                    <div x-show="loading" class="p-8 flex justify-center">
                        <div class="spinner"></div>
                    </div>
                    
                    <!-- Table Content -->
                    <div x-show="!loading && filteredRecords.length > 0" class="p-4 sm:p-6">
                        <!-- Stats Summary -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4 pb-4 border-b">
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Present</p>
                                <p class="text-lg font-semibold text-green-600" x-text="dateStats.present"></p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Late</p>
                                <p class="text-lg font-semibold text-orange-600" x-text="dateStats.late"></p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Half Day</p>
                                <p class="text-lg font-semibold text-yellow-600" x-text="dateStats.half_day"></p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Absent</p>
                                <p class="text-lg font-semibold text-red-600" x-text="dateStats.absent"></p>
                            </div>
                        </div>
                        
                        <!-- Records Table -->
                        <div class="overflow-x-auto mobile-table">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check In</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Hours</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Break Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="record in paginatedRecords" :key="record.u_id">
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-gray-900" data-label="Employee" x-text="record.name"></td>
                                            <td class="px-4 py-4 text-sm text-gray-900" data-label="Check In" x-text="formatTime(record.check_in)"></td>
                                            <td class="px-4 py-4 text-sm text-gray-900" data-label="Total Hours" x-text="record.total_hours"></td>
                                            <td class="px-4 py-4 text-sm text-gray-900" data-label="Break Time" x-text="record.break_time"></td>
                                            <td class="px-4 py-4" data-label="Status">
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                                      :class="getStatusClass(record.status_text)">
                                                    <span x-text="record.status_text"></span>
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div x-show="totalPages > 1" class="mt-4 flex flex-col sm:flex-row items-center justify-between">
                            <p class="text-sm text-gray-700 mb-2 sm:mb-0">
                                Showing <span x-text="(currentPage - 1) * recordsPerPage + 1"></span> to 
                                <span x-text="Math.min(currentPage * recordsPerPage, totalRecords)"></span> of 
                                <span x-text="totalRecords"></span> results
                            </p>
                            <nav class="flex items-center space-x-1">
                                <button 
                                    @click="changePage(currentPage - 1)" 
                                    :disabled="currentPage <= 1"
                                    class="px-2 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <template x-for="page in paginationRange" :key="page">
                                    <button 
                                        @click="changePage(page)"
                                        x-text="page"
                                        class="px-3 py-2 rounded-md border text-sm font-medium"
                                        :class="page === currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'">
                                    </button>
                                </template>
                                <button 
                                    @click="changePage(currentPage + 1)" 
                                    :disabled="currentPage >= totalPages"
                                    class="px-2 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </nav>
                        </div>
                    </div>
                    
                    <!-- No Records Message -->
                    <div x-show="!loading && filteredRecords.length === 0" class="p-8 text-center text-gray-500">
                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
                        <p x-text="searchQuery ? `No records found for '${searchQuery}'` : 'No attendance records for this date'"></p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        function allEmployees() {
            return {
                selectedMonth: '<?php echo $selected_month; ?>',
                selectedDate: '',
                searchQuery: '',
                records: [],
                filteredRecords: [],
                paginatedRecords: [],
                recordsPerPage: 20,
                currentPage: 1,
                totalRecords: 0,
                totalPages: 1,
                loading: false,
                dateStats: {
                    present: 0,
                    late: 0,
                    half_day: 0,
                    absent: 0
                },

                init() {
                    // Check if there's a date in URL params
                    const urlParams = new URLSearchParams(window.location.search);
                    const dateParam = urlParams.get('date');
                    if (dateParam) {
                        this.selectDate(dateParam);
                    }
                },

                async selectDate(date) {
                    if (this.selectedDate === date) return;
                    
                    this.selectedDate = date;
                    this.searchQuery = '';
                    this.currentPage = 1;
                    this.loading = true;
                    
                    try {
                        const response = await fetch(`get_attendance_details.php?date=${date}`);
                        const data = await response.json();
                        
                        if (data.error) {
                            console.error(data.error);
                            this.records = [];
                        } else {
                            this.records = data.records || [];
                            this.dateStats = data.stats || {
                                present: 0,
                                late: 0,
                                half_day: 0,
                                absent: 0
                            };
                        }
                        
                        this.filterRecords();
                    } catch (error) {
                        console.error('Error fetching attendance details:', error);
                        this.records = [];
                    } finally {
                        this.loading = false;
                    }
                },

                clearDate() {
                    this.selectedDate = '';
                    this.records = [];
                    this.filteredRecords = [];
                    this.paginatedRecords = [];
                    this.searchQuery = '';
                    this.currentPage = 1;
                },

                filterRecords() {
                    if (this.searchQuery) {
                        this.filteredRecords = this.records.filter(record => 
                            record.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                            record.status_text.toLowerCase().includes(this.searchQuery.toLowerCase())
                        );
                    } else {
                        this.filteredRecords = [...this.records];
                    }
                    
                    this.totalRecords = this.filteredRecords.length;
                    this.totalPages = Math.ceil(this.totalRecords / this.recordsPerPage);
                    this.currentPage = Math.min(this.currentPage, Math.max(1, this.totalPages));
                    this.updatePagination();
                },

                updatePagination() {
                    const start = (this.currentPage - 1) * this.recordsPerPage;
                    const end = start + this.recordsPerPage;
                    this.paginatedRecords = this.filteredRecords.slice(start, end);
                },

                changePage(page) {
                    if (page >= 1 && page <= this.totalPages) {
                        this.currentPage = page;
                        this.updatePagination();
                    }
                },

                get paginationRange() {
                    const range = [];
                    const delta = 2;
                    const start = Math.max(1, this.currentPage - delta);
                    const end = Math.min(this.totalPages, this.currentPage + delta);
                    
                    for (let i = start; i <= end; i++) {
                        range.push(i);
                    }
                    return range;
                },

                changeMonth(month) {
                    window.location.href = `?month=${month}`;
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const [year, month, day] = dateStr.split('-');
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                                       'July', 'August', 'September', 'October', 'November', 'December'];
                    return `${monthNames[parseInt(month) - 1]} ${parseInt(day)}, ${year}`;
                },

                formatTime(time) {
                    if (!time) return '-';
                    const [hours, minutes] = time.split(':');
                    const hour = parseInt(hours);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const displayHour = hour % 12 || 12;
                    return `${displayHour}:${minutes} ${ampm}`;
                },

                getStatusClass(statusText) {
                    if (statusText.includes('Present')) return 'bg-green-100 text-green-800';
                    if (statusText.includes('Late')) return 'bg-orange-100 text-orange-800';
                    if (statusText.includes('Half Day')) return 'bg-yellow-100 text-yellow-800';
                    if (statusText.includes('Absent')) return 'bg-red-100 text-red-800';
                    return 'bg-gray-100 text-gray-800';
                }
            }
        }
    </script>
</body>
</html>