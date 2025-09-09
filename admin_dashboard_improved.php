<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="description" content="A powerful employee management system with attendance tracking, break management, leave applications, and comprehensive admin controls for better workforce management.">
    <meta name="theme-color" content="#ffffff">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Employee Management Dashboard | Track Attendance, Breaks & Leave</title>

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
        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Roboto', system-ui, sans-serif !important;
        }

        body, div, p {
            font-family: 'Nunito', system-ui, sans-serif !important;
        }

        /* Layout Styles */
        .main-content {
            margin-left: 0;
            padding-top: 4rem;
            min-height: calc(100vh - 4rem);
            transition: margin-left 0.3s ease;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 4rem;
            left: -100%;
            width: 16rem;
            height: calc(100vh - 4rem);
            background: white;
            transition: left 0.3s ease;
            z-index: 40;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.open {
            left: 0;
        }

        /* Desktop Styles */
        @media (min-width: 768px) {
            .sidebar {
                left: 0;
            }

            .main-content {
                margin-left: 16rem;
            }

            .sidebar.closed {
                left: -16rem;
            }

            .main-content.expanded {
                margin-left: 0;
            }
        }

        /* Mobile Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 30;
        }

        .sidebar-overlay.show {
            display: block;
        }

        @media (max-width: 767px) {
            .sidebar-overlay.show {
                display: block;
            }
        }

        /* Details Row Styles */
        .details-row {
            display: none;
        }

        .details-row.show {
            display: table-row;
        }

        .rotate-180 {
            transform: rotate(180deg);
        }

        /* Tab Styles */
        .tab-button {
            position: relative;
            transition: all 0.3s ease;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: #3498db;
        }

        /* Modal Styles */
        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .modal.hidden {
            opacity: 0;
            transform: scale(0.95);
        }

        .modal:not(.hidden) {
            opacity: 1;
            transform: scale(1);
        }

        /* Team Structure Styles */
        .org-chart {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            overflow-x: auto;
        }

        .org-level {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin: 2rem 0;
            position: relative;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .org-node {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            transition: transform 0.3s ease;
        }

        .org-node:hover {
            transform: translateY(-5px);
        }

        .org-circle {
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .org-circle:hover {
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }

        .org-circle.ceo {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .org-circle.manager {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .org-circle.employee {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #48c774 0%, #3ec46d 100%);
            color: white;
        }

        .org-connector {
            position: absolute;
            background: #cbd5e0;
            z-index: -1;
        }

        .org-connector.vertical {
            width: 2px;
            height: 40px;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
        }

        .org-connector.horizontal {
            height: 2px;
            top: -40px;
        }

        .org-info {
            padding: 0.5rem;
        }

        .org-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .org-title {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        .org-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-bottom: 0.5rem;
            border: 2px solid white;
        }

        /* Responsive Team Structure */
        @media (max-width: 768px) {
            .org-circle.ceo {
                width: 120px;
                height: 120px;
            }

            .org-circle.manager {
                width: 100px;
                height: 100px;
            }

            .org-circle.employee {
                width: 80px;
                height: 80px;
            }

            .org-level {
                gap: 1rem;
            }
        }

        /* Loading Spinner */
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

<body class="antialiased text-gray-800 bg-gray-100">
    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:p-4 focus:bg-white focus:text-black">
        Skip to main content
    </a>

    <!-- Include Header -->
    <?php include "header.php" ?>
    
    <!-- Include Sidebar -->
    <?php include "sidebar.php" ?>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- Include PHP Dependencies -->
    <?php include_once 'time_calculations.php'; ?>
    
    <?php
    // Default target time (fallback)
    $default_target_arrival = '09:25:00';
    $target_break_minutes = 60;
    $target_availability_hours = 8;

    // Get selected period from GET parameter or default to 30 days
    $period = isset($_GET['period']) && in_array($_GET['period'], ['7', '15', '30']) ? (int)$_GET['period'] : 30;

    // Convert default target arrival to seconds for fallback
    $default_target_arrival_seconds = strtotime($default_target_arrival) - strtotime('00:00:00');

    // 1. Average Time of Arrival (comparing each employee against their own target)
    $arrival_query = $db->query("
    SELECT AVG(arrival_diff_seconds) as avg_arrival_diff_seconds
    FROM (
        SELECT 
            sc.u_id,
            DATE(sc.start_time) as work_date,
            MIN(TIME(sc.start_time)) as actual_arrival,
            COALESCE(u.work_start, '$default_target_arrival') as target_arrival,
            TIME_TO_SEC(MIN(TIME(sc.start_time))) - TIME_TO_SEC(COALESCE(u.work_start, '$default_target_arrival')) as arrival_diff_seconds
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE sc.status = 'Available'
        AND sc.start_time >= DATE_SUB(CURDATE(), INTERVAL $period DAY)
        AND u.active = 1
        GROUP BY sc.u_id, DATE(sc.start_time)
    ) as daily_arrivals
    ");
    $arrival_data = $arrival_query->fetch_assoc();
    $avg_arrival_diff_seconds = $arrival_data['avg_arrival_diff_seconds'] ? round($arrival_data['avg_arrival_diff_seconds']) : 0;
    $arrival_diff_minutes = round($avg_arrival_diff_seconds / 60);

    // Calculate actual average arrival time for display
    $actual_arrival_query = $db->query("
    SELECT AVG(TIME_TO_SEC(actual_arrival)) as avg_actual_arrival_seconds
    FROM (
        SELECT MIN(TIME(sc.start_time)) as actual_arrival
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE sc.status = 'Available'
        AND sc.start_time >= DATE_SUB(CURDATE(), INTERVAL $period DAY)
        AND u.active = 1
        GROUP BY sc.u_id, DATE(sc.start_time)
    ) as daily_actual_arrivals
    ");
    $actual_arrival_data = $actual_arrival_query->fetch_assoc();
    $avg_actual_arrival_seconds = $actual_arrival_data['avg_actual_arrival_seconds'] ? round($actual_arrival_data['avg_actual_arrival_seconds']) : 0;
    $avg_arrival = $avg_actual_arrival_seconds ? gmdate('H:i:s', $avg_actual_arrival_seconds) : '00:00:00';

    // Previous period comparison for arrival
    $prev_arrival_query = $db->query("
    SELECT AVG(arrival_diff_seconds) as avg_arrival_diff_seconds
    FROM (
        SELECT 
            sc.u_id,
            DATE(sc.start_time) as work_date,
            MIN(TIME(sc.start_time)) as actual_arrival,
            COALESCE(u.work_start, '$default_target_arrival') as target_arrival,
            TIME_TO_SEC(MIN(TIME(sc.start_time))) - TIME_TO_SEC(COALESCE(u.work_start, '$default_target_arrival')) as arrival_diff_seconds
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE sc.status = 'Available'
        AND sc.start_time >= DATE_SUB(CURDATE(), INTERVAL " . ($period * 2) . " DAY)
        AND sc.start_time < DATE_SUB(CURDATE(), INTERVAL $period DAY)
        AND u.active = 1
        GROUP BY sc.u_id, DATE(sc.start_time)
    ) as daily_arrivals
    ");
    $prev_arrival_data = $prev_arrival_query->fetch_assoc();
    $prev_avg_arrival_diff_seconds = $prev_arrival_data['avg_arrival_diff_seconds'] ? round($prev_arrival_data['avg_arrival_diff_seconds']) : $avg_arrival_diff_seconds;
    $arrival_change = $prev_avg_arrival_diff_seconds ? round((($avg_arrival_diff_seconds - $prev_avg_arrival_diff_seconds) / abs($prev_avg_arrival_diff_seconds)) * 100, 1) : 0;

    // 2. Average Break Duration (keeping same as original)
    $break_query = $db->query("
    SELECT AVG(total_break) as avg_break_minutes
    FROM (
        SELECT LEAST(SUM(TIMESTAMPDIFF(MINUTE, 
            GREATEST(start_time, DATE(sc.start_time)), 
            LEAST(COALESCE(end_time, NOW()), DATE(sc.start_time) + INTERVAL 1 DAY)
        )), 480) as total_break
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE sc.status IN ('Break 1', 'Break 2', 'Lunch Break')
        AND sc.start_time >= DATE_SUB(CURDATE(), INTERVAL $period DAY)
        AND u.active = 1
        GROUP BY sc.u_id, DATE(sc.start_time)
    ) as daily_breaks
    ");
    $break_data = $break_query->fetch_assoc();
    $avg_break = round($break_data['avg_break_minutes'] ?: 0);
    $break_diff_minutes = $avg_break - $target_break_minutes;

    $prev_break_query = $db->query("
    SELECT AVG(total_break) as avg_break_minutes
    FROM (
        SELECT LEAST(SUM(TIMESTAMPDIFF(MINUTE, 
            GREATEST(start_time, DATE(sc.start_time)), 
            LEAST(COALESCE(end_time, NOW()), DATE(sc.start_time) + INTERVAL 1 DAY)
        )), 480) as total_break
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE sc.status IN ('Break 1', 'Break 2', 'Lunch Break')
        AND sc.start_time >= DATE_SUB(CURDATE(), INTERVAL " . ($period * 2) . " DAY)
        AND sc.start_time < DATE_SUB(CURDATE(), INTERVAL $period DAY)
        AND u.active = 1
        GROUP BY sc.u_id, DATE(sc.start_time)
    ) as daily_breaks
    ");
    $prev_break_data = $prev_break_query->fetch_assoc();
    $prev_avg_break = $prev_break_data['avg_break_minutes'] ?: $avg_break;
    $break_change = $prev_avg_break ? round((($avg_break - $prev_avg_break) / $prev_avg_break) * 100, 1) : 0;

    // 3. Average Daily Availability (keeping same as original)
    $availability_query = $db->query("
    SELECT AVG(total_availability) as avg_availability_hours
    FROM (
        SELECT LEAST(SUM(TIMESTAMPDIFF(MINUTE, 
            GREATEST(start_time, DATE(sc.start_time)), 
            LEAST(COALESCE(end_time, NOW()), DATE(sc.start_time) + INTERVAL 1 DAY)
        )), 1440) / 60 as total_availability
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE sc.status = 'Available'
        AND sc.start_time >= DATE_SUB(CURDATE(), INTERVAL $period DAY)
        AND u.active = 1
        GROUP BY sc.u_id, DATE(sc.start_time)
    ) as daily_availability
    ");
    $availability_data = $availability_query->fetch_assoc();
    $avg_availability_hours = round($availability_data['avg_availability_hours'] ?: 0, 2);
    $avg_availability_formatted = floor($avg_availability_hours) . 'h ' . round(($avg_availability_hours - floor($avg_availability_hours)) * 60) . 'm';
    $availability_diff_minutes = round(($avg_availability_hours - $target_availability_hours) * 60);

    $prev_availability_query = $db->query("
    SELECT AVG(total_availability) as avg_availability_hours
    FROM (
        SELECT LEAST(SUM(TIMESTAMPDIFF(MINUTE, 
            GREATEST(start_time, DATE(sc.start_time)), 
            LEAST(COALESCE(end_time, NOW()), DATE(sc.start_time) + INTERVAL 1 DAY)
        )), 1440) / 60 as total_availability
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE sc.status = 'Available'
        AND sc.start_time >= DATE_SUB(CURDATE(), INTERVAL " . ($period * 2) . " DAY)
        AND sc.start_time < DATE_SUB(CURDATE(), INTERVAL $period DAY)
        AND u.active = 1
        GROUP BY sc.u_id, DATE(sc.start_time)
    ) as daily_availability
    ");
    $prev_availability_data = $prev_availability_query->fetch_assoc();
    $prev_avg_availability = $prev_availability_data['avg_availability_hours'] ?: $avg_availability_hours;
    $availability_change = $prev_avg_availability ? round((($avg_availability_hours - $prev_avg_availability) / $prev_avg_availability) * 100, 1) : 0;

    // 4. Top 5 Early Arrivers (compared against their own work_start time)
    $early_arrivers_query = $db->query("
    SELECT 
        u.name, 
        u.img, 
        u.email, 
        d.name as department,
        u.work_start as target_arrival,
        AVG(TIME_TO_SEC(actual_arrival)) as avg_arrival_seconds,
        AVG(TIME_TO_SEC(actual_arrival) - TIME_TO_SEC(COALESCE(u.work_start, '$default_target_arrival'))) as avg_diff_seconds,
        (SUM(CASE WHEN TIME_TO_SEC(actual_arrival) <= TIME_TO_SEC(COALESCE(u.work_start, '$default_target_arrival')) THEN 1 ELSE 0 END) / COUNT(*)) * 100 as punctuality_rate
    FROM (
        SELECT sc.u_id, MIN(TIME(sc.start_time)) as actual_arrival
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE sc.status = 'Available'
        AND sc.start_time >= DATE_SUB(CURDATE(), INTERVAL $period DAY)
        AND u.active = 1
        GROUP BY sc.u_id, DATE(sc.start_time)
    ) as daily_arrivals
    JOIN user u ON daily_arrivals.u_id = u.u_id
    JOIN department d ON u.dpar_id = d.d_id
    GROUP BY u.u_id, u.name, u.img, u.email, d.name, u.work_start
    ORDER BY avg_diff_seconds ASC
    LIMIT 5
    ");
    $early_arrivers = [];
    while ($row = $early_arrivers_query->fetch_assoc()) {
        $target_time = $row['target_arrival'] ?: $default_target_arrival;
        $early_arrivers[] = [
            'name' => $row['name'],
            'img' => $row['img'],
            'email' => $row['email'],
            'department' => $row['department'],
            'target_arrival' => $target_time,
            'avg_arrival' => gmdate('H:i:s', round($row['avg_arrival_seconds'])),
            'diff_minutes' => round($row['avg_diff_seconds'] / 60),
            'punctuality_rate' => round($row['punctuality_rate'], 1)
        ];
    }

    // 5. Top 5 Late Arrivers (compared against their own work_start time)
    $late_arrivers_query = $db->query("
    SELECT 
        u.name, 
        u.img, 
        u.email, 
        u.u_id,
        d.name as department,
        u.work_start as target_arrival,
        AVG(TIME_TO_SEC(actual_arrival)) as avg_arrival_seconds,
        AVG(TIME_TO_SEC(actual_arrival) - TIME_TO_SEC(COALESCE(u.work_start, '$default_target_arrival'))) as avg_diff_seconds,
        SUM(CASE WHEN TIME_TO_SEC(actual_arrival) > TIME_TO_SEC(COALESCE(u.work_start, '$default_target_arrival')) THEN 1 ELSE 0 END) as days_late
    FROM (
        SELECT sc.u_id, MIN(TIME(sc.start_time)) as actual_arrival
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE sc.status = 'Available'
        AND sc.start_time >= DATE_SUB(CURDATE(), INTERVAL $period DAY)
        AND u.active = 1
        GROUP BY sc.u_id, DATE(sc.start_time)
    ) as daily_arrivals
    JOIN user u ON daily_arrivals.u_id = u.u_id
    JOIN department d ON u.dpar_id = d.d_id
    GROUP BY u.u_id, u.name, u.img, u.email, d.name, u.work_start
    ORDER BY days_late DESC
    LIMIT 5
    ");

    $late_arrivers = [];
    while ($row = $late_arrivers_query->fetch_assoc()) {
        $target_time = $row['target_arrival'] ?: $default_target_arrival;

        // Get detailed late arrival dates for this employee
        $late_dates_query = $db->query("
        SELECT 
            DATE(sc.start_time) as late_date,
            MIN(TIME(sc.start_time)) as actual_arrival,
            COALESCE(u.work_start, '$default_target_arrival') as target_time,
            TIME_TO_SEC(MIN(TIME(sc.start_time))) - TIME_TO_SEC(COALESCE(u.work_start, '$default_target_arrival')) as diff_seconds
        FROM status_changes sc
        JOIN user u ON sc.u_id = u.u_id
        WHERE sc.status = 'Available'
        AND sc.u_id = {$row['u_id']}
        AND sc.start_time >= DATE_SUB(CURDATE(), INTERVAL $period DAY)
        AND u.active = 1
        GROUP BY sc.u_id, DATE(sc.start_time)
        HAVING diff_seconds > 0
        ORDER BY late_date DESC
        ");

        $late_dates = [];
        while ($date_row = $late_dates_query->fetch_assoc()) {
            $late_dates[] = [
                'date' => $date_row['late_date'],
                'actual_arrival' => $date_row['actual_arrival'],
                'target_time' => $date_row['target_time'],
                'diff_minutes' => round($date_row['diff_seconds'] / 60)
            ];
        }

        $late_arrivers[] = [
            'u_id' => $row['u_id'],
            'name' => $row['name'],
            'img' => $row['img'],
            'email' => $row['email'],
            'department' => $row['department'],
            'target_arrival' => $target_time,
            'avg_arrival' => gmdate('H:i:s', round($row['avg_arrival_seconds'])),
            'diff_minutes' => round($row['avg_diff_seconds'] / 60),
            'days_late' => (int)$row['days_late'],
            'late_dates' => $late_dates
        ];
    }

    // Fetch Team Structure Data
    $team_structure_query = $db->query("
        SELECT 
            u.u_id,
            u.name,
            u.email,
            u.img,
            u.employee_id,
            p.name as position,
            p.p_id as position_id,
            d.name as department,
            d.d_id as department_id,
            u.currect_status,
            CASE 
                WHEN p.name LIKE '%CEO%' OR p.name LIKE '%Chief%' THEN 'ceo'
                WHEN p.name LIKE '%Manager%' OR p.name LIKE '%Head%' OR p.name LIKE '%Director%' THEN 'manager'
                ELSE 'employee'
            END as level
        FROM user u
        LEFT JOIN post p ON u.post_id = p.p_id
        LEFT JOIN department d ON u.dpar_id = d.d_id
        WHERE u.active = 1
        ORDER BY 
            CASE 
                WHEN p.name LIKE '%CEO%' OR p.name LIKE '%Chief%' THEN 1
                WHEN p.name LIKE '%Manager%' OR p.name LIKE '%Head%' OR p.name LIKE '%Director%' THEN 2
                ELSE 3
            END,
            d.d_id,
            u.name
    ");

    $team_structure = [
        'ceo' => [],
        'managers' => [],
        'employees' => []
    ];

    while ($row = $team_structure_query->fetch_assoc()) {
        $member = [
            'id' => $row['u_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'img' => $row['img'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=random',
            'employee_id' => $row['employee_id'],
            'position' => $row['position'] ?: 'Team Member',
            'department' => $row['department'] ?: 'General',
            'department_id' => $row['department_id'],
            'status' => $row['currect_status'],
            'level' => $row['level']
        ];

        if ($row['level'] === 'ceo') {
            $team_structure['ceo'][] = $member;
        } elseif ($row['level'] === 'manager') {
            $team_structure['managers'][$row['department_id']][] = $member;
        } else {
            $team_structure['employees'][$row['department_id']][] = $member;
        }
    }
    ?>

    <!-- Main content -->
    <main id="main-content" class="main-content">
        <section id="admin-dashboard" class="page-section bg-neutral-50 min-h-screen">
            <div class="px-4 sm:px-6 lg:px-8 py-8">
                <!-- Admin Header with Stats -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-bold text-gray-900">Admin Dashboard</h2>
                        <!-- Mobile Menu Toggle -->
                        <button onclick="toggleSidebar()" class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg shadow-sm p-5 border border-neutral-200/30 hover:shadow-md transition-shadow">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Total Employees</p>
                                    <p class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($total_employees); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-5 border border-neutral-200/30 hover:shadow-md transition-shadow">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Available Now</p>
                                    <p class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($available_count); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-5 border border-neutral-200/30 hover:shadow-md transition-shadow">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">On Break</p>
                                    <p class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($break_count); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-5 border border-neutral-200/30 hover:shadow-md transition-shadow">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Offline</p>
                                    <p class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($offline_count); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Management Tabs -->
                <div class="bg-white rounded-lg shadow-sm border border-neutral-200/30 mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px overflow-x-auto">
                            <button class="tab-button active text-blue-600 whitespace-nowrap py-4 px-6 font-medium text-sm" data-tab="all-employees">
                                All Employees
                            </button>
                            <button class="tab-button text-gray-500 hover:text-gray-700 whitespace-nowrap py-4 px-6 font-medium text-sm" data-tab="attendance">
                                Attendance Insights
                            </button>
                            <button class="tab-button text-gray-500 hover:text-gray-700 whitespace-nowrap py-4 px-6 font-medium text-sm" data-tab="holiday-management">
                                Holiday Management
                            </button>
                            <button class="tab-button text-gray-500 hover:text-gray-700 whitespace-nowrap py-4 px-6 font-medium text-sm" data-tab="team-structure">
                                Team Structure
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Tab Contents Container -->
                <div class="tab-contents">
                    <!-- All Employees Tab Content -->
                    <div id="tab-content-all-employees" class="tab-content bg-white rounded-lg shadow-sm border border-neutral-200/30 mb-6">
                        <div class="px-6 py-4 border-b border-neutral-200/20 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                            <div class="flex items-center">
                                <h3 class="text-lg font-medium text-gray-900">Employee Directory</h3>
                                <span class="ml-4 inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($total_employees); ?>
                                </span>
                            </div>
                            <button type="button" onclick="window.location.href='admin_emp.php';" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add New Employee
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-neutral-200/20">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Active</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-neutral-200/10">
                                    <?php
                                    $limit = 10;
                                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                    $page = max(1, $page);
                                    $offset = ($page - 1) * $limit;

                                    $total_query = $db->query("SELECT COUNT(*) as total FROM user WHERE active = 1");
                                    $total_row = $total_query->fetch_object();
                                    $total_employees = $total_row->total;
                                    $total_pages = ceil($total_employees / $limit);

                                    $query = $db->query("
                                        SELECT u.u_id, u.name, u.employee_id, u.email, u.mobile, u.currect_status, u.img, u.date_birth, u.last_login, d.name as department_name, p.name as post_name
                                        FROM user u
                                        LEFT JOIN department d ON u.dpar_id = d.d_id
                                        LEFT JOIN post p ON u.post_id = p.p_id
                                        WHERE u.active = 1
                                        ORDER BY u.name ASC
                                        LIMIT $limit OFFSET $offset
                                    ");

                                    if ($query && $query->num_rows > 0) {
                                        while ($row = $query->fetch_object()) {
                                            $employee_name = htmlspecialchars($row->name);
                                            $employee_id = htmlspecialchars($row->employee_id);
                                            $email = htmlspecialchars($row->email);
                                            $img = htmlspecialchars($row->img);
                                            $department_name = htmlspecialchars($row->department_name ?: 'N/A');
                                            $post_name = htmlspecialchars($row->post_name ?: 'N/A');
                                            $current_status = htmlspecialchars($row->currect_status ?: 'N/A');
                                            $last_active = $row->last_login ? date('M d, Y', strtotime($row->last_login)) : 'N/A';
                                            $status_color = $current_status === 'Available' ? 'bg-green-100 text-green-800' : 
                                                           (in_array($current_status, ['Break 1', 'Break 2', 'Lunch Break', 'Personal Time']) ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                    ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded-full object-cover" src="<?php echo !empty($img) ? $img : 'https://ui-avatars.com/api/?name=' . urlencode($employee_name) . '&background=random'; ?>" alt="<?php echo $employee_name; ?>">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo $employee_name; ?> (<?php echo $employee_id; ?>)</div>
                                                        <div class="text-sm text-gray-500"><?php echo $email; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo $department_name; ?></div>
                                                <div class="text-sm text-gray-500"><?php echo $post_name; ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                                    <?php echo ucfirst($current_status); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $last_active; ?>
                                            </td>
                                        </tr>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                No employees found.
                                            </td>
                                        </tr>
                                    <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="px-6 py-4 border-t border-neutral-200/20 flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <a href="?page=<?php echo $page > 1 ? $page - 1 : 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                <a href="?page=<?php echo $page < $total_pages ? $page + 1 : $total_pages; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $total_employees); ?></span> of <span class="font-medium"><?php echo $total_employees; ?></span> results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <a href="?page=<?php echo $page > 1 ? $page - 1 : 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Previous</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        
                                        if ($start_page > 1) {
                                            echo '<a href="?page=1" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">1</a>';
                                            if ($start_page > 2) {
                                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                            }
                                        }
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            $active_class = $i == $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
                                            echo "<a href=\"?page=$i\" class=\"$active_class relative inline-flex items-center px-4 py-2 border text-sm font-medium\">$i</a>";
                                        }
                                        
                                        if ($end_page < $total_pages) {
                                            if ($end_page < $total_pages - 1) {
                                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                            }
                                            echo "<a href=\"?page=$total_pages\" class=\"bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium\">$total_pages</a>";
                                        }
                                        ?>
                                        <a href="?page=<?php echo $page < $total_pages ? $page + 1 : $total_pages; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Next</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Insights Tab Content -->
                    <div id="tab-content-attendance" class="tab-content hidden bg-white rounded-lg shadow-sm border border-neutral-200/30 mb-6">
                        <div class="px-6 py-4 border-b border-neutral-200/20 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 sm:mb-0">Attendance Insights</h3>
                            <div class="w-full sm:w-auto max-w-xs">
                                <select
                                    id="period-select"
                                    onchange="window.location.href='?period='+this.value"
                                    class="w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <option value="7" <?php echo $period == 7 ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="15" <?php echo $period == 15 ? 'selected' : ''; ?>>Last 15 Days</option>
                                    <option value="30" <?php echo $period == 30 ? 'selected' : ''; ?>>Last 30 Days</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <!-- Metrics Cards -->
                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-sm font-medium text-gray-700">Average Time of Arrival</h5>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Last <?php echo $period; ?> Days
                                            </span>
                                        </div>
                                        <p class="mt-2 text-2xl font-bold text-gray-900"><?php echo date('h:i A', strtotime($avg_arrival)); ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo abs($arrival_diff_minutes) . ' minutes ' . ($arrival_diff_minutes >= 0 ? 'after' : 'before') . ' target (09:25 AM)'; ?></p>
                                        <div class="mt-2">
                                            <span class="text-xs inline-flex items-center <?php echo $arrival_change <= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $arrival_change <= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3'; ?>" />
                                                </svg>
                                                <?php echo abs($arrival_change) . '% ' . ($arrival_change <= 0 ? 'improvement' : 'delay') . ' from previous period'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-sm font-medium text-gray-700">Average Break Duration</h5>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Last <?php echo $period; ?> Days
                                            </span>
                                        </div>
                                        <p class="mt-2 text-2xl font-bold text-gray-900"><?php echo $avg_break; ?> min</p>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo abs($break_diff_minutes) . ' minutes ' . ($break_diff_minutes >= 0 ? 'over' : 'under') . ' target (60 min)'; ?></p>
                                        <div class="mt-2">
                                            <span class="text-xs inline-flex items-center <?php echo $break_change >= 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $break_change >= 0 ? 'M19 14l-7 7m0 0l-7-7m7 7V3' : 'M5 10l7-7m0 0l7 7m-7-7v18'; ?>" />
                                                </svg>
                                                <?php echo abs($break_change) . '% ' . ($break_change >= 0 ? 'increase' : 'decrease') . ' from previous period'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-sm font-medium text-gray-700">Average Daily Availability</h5>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Last <?php echo $period; ?> Days
                                            </span>
                                        </div>
                                        <p class="mt-2 text-2xl font-bold text-gray-900"><?php echo $avg_availability_formatted; ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo abs($availability_diff_minutes) . ' minutes ' . ($availability_diff_minutes >= 0 ? 'over' : 'under') . ' target (8h)'; ?></p>
                                        <div class="mt-2">
                                            <span class="text-xs inline-flex items-center <?php echo $availability_change >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $availability_change >= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3'; ?>" />
                                                </svg>
                                                <?php echo abs($availability_change) . '% ' . ($availability_change >= 0 ? 'increase' : 'decrease') . ' from previous period'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Top 5 Early Arrivers -->
                            <div class="mb-6">
                                <h4 class="text-base font-medium text-gray-900 mb-4">Top 5 Earliest Arrivers</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-neutral-200/20">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Arrival Time</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Punctuality Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-neutral-200/10">
                                            <?php foreach ($early_arrivers as $arriver): ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 h-10 w-10">
                                                                <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars($arriver['img']); ?>" alt="Employee profile photo">
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($arriver['name']); ?></div>
                                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($arriver['email']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($arriver['department']); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900"><?php echo date('h:i A', strtotime($arriver['avg_arrival'])); ?></div>
                                                        <div class="text-xs text-green-600"><?php echo abs($arriver['diff_minutes']) . ' minutes ' . ($arriver['diff_minutes'] >= 0 ? 'after' : 'before'); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2 max-w-[150px]">
                                                                <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo $arriver['punctuality_rate']; ?>%"></div>
                                                            </div>
                                                            <span class="text-sm font-medium text-gray-900"><?php echo $arriver['punctuality_rate']; ?>%</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($early_arrivers)): ?>
                                                <tr>
                                                    <td colspan="4" class="px-6 py-4 text-sm text-gray-500 text-center">No data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Top 5 Late Arrivers -->
                            <div class="mb-6">
                                <h4 class="text-base font-medium text-gray-900 mb-4">Top 5 Latest Arrivers</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-neutral-200/20">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Arrival Time</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Late (<?php echo $period; ?> days)</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-neutral-200/10">
                                            <?php foreach ($late_arrivers as $arriver): ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 h-10 w-10">
                                                                <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars($arriver['img']); ?>" alt="Employee profile photo">
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($arriver['name']); ?></div>
                                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($arriver['email']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($arriver['department']); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900"><?php echo date('h:i A', strtotime($arriver['avg_arrival'])); ?></div>
                                                        <div class="text-xs text-red-600"><?php echo abs($arriver['diff_minutes']) . ' minutes ' . ($arriver['diff_minutes'] >= 0 ? 'after' : 'before'); ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2 max-w-[150px]">
                                                                <div class="bg-red-600 h-2.5 rounded-full" style="width: <?php echo min((int)$arriver['days_late'] / 30 * 100, 100); ?>%"></div>
                                                            </div>
                                                            <span class="text-sm font-medium text-gray-900"><?php echo $arriver['days_late']; ?> days</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <?php if (!empty($arriver['late_dates'])): ?>
                                                            <button onclick="toggleDetails('employee-<?php echo $arriver['u_id']; ?>')" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                                <span>View Dates</span>
                                                                <svg id="arrow-employee-<?php echo $arriver['u_id']; ?>" class="ml-1 h-3 w-3 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                                </svg>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-xs text-gray-400">No late dates</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php if (!empty($arriver['late_dates'])): ?>
                                                    <tr id="details-employee-<?php echo $arriver['u_id']; ?>" class="details-row bg-gray-50">
                                                        <td colspan="5" class="px-6 py-4">
                                                            <div class="bg-white rounded-md p-4 shadow-sm">
                                                                <h4 class="text-sm font-medium text-gray-900 mb-3">Late Arrival Dates for <?php echo htmlspecialchars($arriver['name']); ?></h4>
                                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                                    <?php foreach ($arriver['late_dates'] as $late_date): ?>
                                                                        <div class="border rounded-md p-3 bg-red-50">
                                                                            <div class="text-sm font-medium text-red-800"><?php echo date('M j, Y', strtotime($late_date['date'])); ?></div>
                                                                            <div class="text-xs text-red-600">
                                                                                Target: <?php echo date('h:i A', strtotime($late_date['target_time'])); ?><br>
                                                                                Arrived: <?php echo date('h:i A', strtotime($late_date['actual_arrival'])); ?>
                                                                                (<?php echo $late_date['diff_minutes']; ?>m late)
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <?php if (empty($late_arrivers)): ?>
                                                <tr>
                                                    <td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">No data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Holiday Management Tab Content -->
                    <div id="tab-content-holiday-management" class="tab-content hidden bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 sm:mb-0">Holiday Management</h3>
                            <button id="addHolidayBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Add Holiday
                            </button>
                        </div>
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="holidayTable" class="bg-white divide-y divide-gray-200">
                                        <!-- Holiday rows will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Team Structure Tab Content -->
                    <div id="tab-content-team-structure" class="tab-content hidden bg-white rounded-lg shadow-sm border border-neutral-200/30 mb-6">
                        <div class="px-6 py-4 border-b border-neutral-200/20">
                            <h3 class="text-lg font-medium text-gray-900">Team Structure</h3>
                            <p class="text-sm text-gray-500 mt-1">Visual representation of your organization's hierarchy</p>
                        </div>
                        
                        <div class="p-6 overflow-x-auto">
                            <div class="org-chart min-w-max">
                                <!-- CEO Level -->
                                <?php if (!empty($team_structure['ceo'])): ?>
                                    <div class="org-level">
                                        <?php foreach ($team_structure['ceo'] as $ceo): ?>
                                            <div class="org-node">
                                                <div class="org-circle ceo">
                                                    <img src="<?php echo htmlspecialchars($ceo['img']); ?>" alt="<?php echo htmlspecialchars($ceo['name']); ?>" class="org-avatar">
                                                    <div class="org-info">
                                                        <div class="org-name"><?php echo htmlspecialchars($ceo['name']); ?></div>
                                                        <div class="org-title"><?php echo htmlspecialchars($ceo['position']); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Connector Line -->
                                    <div class="flex justify-center">
                                        <div class="w-0.5 h-12 bg-gray-300"></div>
                                    </div>
                                <?php endif; ?>

                                <!-- Managers Level -->
                                <?php if (!empty($team_structure['managers'])): ?>
                                    <div class="org-level">
                                        <?php foreach ($team_structure['managers'] as $dept_id => $managers): ?>
                                            <?php foreach ($managers as $manager): ?>
                                                <div class="org-node mx-4">
                                                    <div class="org-connector vertical"></div>
                                                    <div class="org-circle manager">
                                                        <img src="<?php echo htmlspecialchars($manager['img']); ?>" alt="<?php echo htmlspecialchars($manager['name']); ?>" class="org-avatar">
                                                        <div class="org-info">
                                                            <div class="org-name text-sm"><?php echo htmlspecialchars($manager['name']); ?></div>
                                                            <div class="org-title text-xs"><?php echo htmlspecialchars($manager['position']); ?></div>
                                                            <div class="org-title text-xs opacity-75"><?php echo htmlspecialchars($manager['department']); ?></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Employees under this manager -->
                                                    <?php if (!empty($team_structure['employees'][$dept_id])): ?>
                                                        <div class="flex justify-center mt-4">
                                                            <div class="w-0.5 h-8 bg-gray-300"></div>
                                                        </div>
                                                        <div class="flex flex-wrap justify-center gap-2 mt-2">
                                                            <?php foreach ($team_structure['employees'][$dept_id] as $employee): ?>
                                                                <div class="org-node">
                                                                    <div class="org-circle employee">
                                                                        <img src="<?php echo htmlspecialchars($employee['img']); ?>" alt="<?php echo htmlspecialchars($employee['name']); ?>" class="org-avatar" style="width: 30px; height: 30px;">
                                                                        <div class="org-info">
                                                                            <div class="org-name text-xs"><?php echo htmlspecialchars(explode(' ', $employee['name'])[0]); ?></div>
                                                                            <div class="org-title" style="font-size: 0.65rem;"><?php echo htmlspecialchars($employee['position']); ?></div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Employees without managers (if any) -->
                                <?php 
                                $unmanagedEmployees = [];
                                foreach ($team_structure['employees'] as $dept_id => $employees) {
                                    if (empty($team_structure['managers'][$dept_id])) {
                                        $unmanagedEmployees = array_merge($unmanagedEmployees, $employees);
                                    }
                                }
                                ?>
                                
                                <?php if (!empty($unmanagedEmployees)): ?>
                                    <div class="mt-8 pt-8 border-t border-gray-200">
                                        <h4 class="text-center text-gray-600 mb-4">Other Team Members</h4>
                                        <div class="org-level">
                                            <?php foreach ($unmanagedEmployees as $employee): ?>
                                                <div class="org-node mx-2">
                                                    <div class="org-circle employee">
                                                        <img src="<?php echo htmlspecialchars($employee['img']); ?>" alt="<?php echo htmlspecialchars($employee['name']); ?>" class="org-avatar" style="width: 30px; height: 30px;">
                                                        <div class="org-info">
                                                            <div class="org-name text-xs"><?php echo htmlspecialchars($employee['name']); ?></div>
                                                            <div class="org-title" style="font-size: 0.65rem;"><?php echo htmlspecialchars($employee['position']); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal for Add/Edit Holiday -->
    <div id="holidayModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md transform transition-transform duration-300">
            <h2 id="modalTitle" class="text-xl font-medium text-gray-900 mb-4">Add Holiday</h2>
            <form id="holidayForm">
                <input type="hidden" id="holidayId" name="holidayId">
                <div class="mb-4">
                    <label for="holidayName" class="block text-sm font-medium text-gray-700">Holiday Name</label>
                    <input type="text" id="holidayName" name="holidayName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                <div class="mb-4">
                    <label for="holidayDate" class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" id="holidayDate" name="holidayDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="cancelBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="saveBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Sidebar Toggle Functions
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth < 768) {
                // Mobile behavior
                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
            } else {
                // Desktop behavior
                sidebar.classList.toggle('closed');
                mainContent.classList.toggle('expanded');
            }
        }

        function closeSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        }

        // Tab Switching
        document.addEventListener('DOMContentLoaded', () => {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Check URL for period parameter to determine active tab
            const urlParams = new URLSearchParams(window.location.search);
            const period = urlParams.get('period');
            
            // If period parameter exists, show attendance tab
            if (period && ['7', '15', '30'].includes(period)) {
                showTab('attendance');
            }
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabName = button.dataset.tab;
                    showTab(tabName);
                });
            });
            
            function showTab(tabName) {
                // Update buttons
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'text-blue-600');
                    btn.classList.add('text-gray-500');
                });
                
                // Update contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show selected tab
                const selectedButton = document.querySelector(`[data-tab="${tabName}"]`);
                const selectedContent = document.getElementById(`tab-content-${tabName}`);
                
                if (selectedButton && selectedContent) {
                    selectedButton.classList.add('active', 'text-blue-600');
                    selectedButton.classList.remove('text-gray-500');
                    selectedContent.classList.remove('hidden');
                }
            }
            
            // Holiday Management
            loadHolidays();
            
            // Show modal for adding new holiday
            document.getElementById('addHolidayBtn')?.addEventListener('click', () => {
                document.getElementById('modalTitle').textContent = 'Add Holiday';
                document.getElementById('holidayForm').reset();
                document.getElementById('holidayId').value = '';
                document.getElementById('holidayModal').classList.remove('hidden');
            });
            
            // Cancel button in modal
            document.getElementById('cancelBtn')?.addEventListener('click', () => {
                document.getElementById('holidayModal').classList.add('hidden');
                document.getElementById('holidayForm').reset();
                document.getElementById('holidayId').value = '';
            });
            
            // Handle form submission
            document.getElementById('holidayForm')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const id = document.getElementById('holidayId').value;
                const holiday = {
                    name: document.getElementById('holidayName').value,
                    date: document.getElementById('holidayDate').value
                };
                
                const method = id ? 'PUT' : 'POST';
                const url = id ? `holidays_api.php/${id}` : 'holidays_api.php';
                
                try {
                    const response = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(holiday)
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        loadHolidays();
                        document.getElementById('holidayModal').classList.add('hidden');
                        document.getElementById('holidayForm').reset();
                        document.getElementById('holidayId').value = '';
                    } else {
                        alert(`Operation failed: ${data.error || 'Unknown error'}`);
                    }
                } catch (err) {
                    console.error('Error performing operation:', err);
                    alert(`Failed to ${id ? 'update' : 'add'} holiday`);
                }
            });
        });

        // Load holidays
        async function loadHolidays() {
            try {
                const response = await fetch('holidays_api.php');
                const data = await response.json();
                const holidays = data.holidays || [];
                const holidayTable = document.getElementById('holidayTable');
                if (!holidayTable) return;
                
                holidayTable.innerHTML = '';
                
                holidays.forEach(holiday => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50 transition-colors';
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${holiday.name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${holiday.date}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="editBtn text-blue-600 hover:text-blue-800 mr-3" data-id="${holiday.id}" data-name="${holiday.name}" data-date="${holiday.date}">Edit</button>
                            <button class="deleteBtn text-red-600 hover:text-red-800" data-id="${holiday.id}">Delete</button>
                        </td>
                    `;
                    holidayTable.appendChild(row);
                });
                
                // Attach event listeners for edit and delete buttons
                document.querySelectorAll('.editBtn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.dataset.id;
                        const name = btn.dataset.name;
                        const date = btn.dataset.date;
                        
                        document.getElementById('modalTitle').textContent = 'Edit Holiday';
                        document.getElementById('holidayId').value = id;
                        document.getElementById('holidayName').value = name;
                        document.getElementById('holidayDate').value = date;
                        document.getElementById('holidayModal').classList.remove('hidden');
                    });
                });
                
                document.querySelectorAll('.deleteBtn').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (confirm('Are you sure you want to delete this holiday?')) {
                            const id = btn.dataset.id;
                            try {
                                const response = await fetch(`holidays_api.php/${id}`, { method: 'DELETE' });
                                const data = await response.json();
                                if (data.success) {
                                    loadHolidays();
                                } else {
                                    alert(`Failed to delete holiday: ${data.error || 'Unknown error'}`);
                                }
                            } catch (err) {
                                console.error('Error deleting holiday:', err);
                                alert('Failed to delete holiday');
                            }
                        }
                    });
                });
            } catch (err) {
                console.error('Error loading holidays:', err);
            }
        }

        // Toggle Details Function
        function toggleDetails(employeeId) {
            const detailsRow = document.getElementById('details-' + employeeId);
            const arrow = document.getElementById('arrow-' + employeeId);
            
            if (detailsRow.classList.contains('show')) {
                detailsRow.classList.remove('show');
                arrow.classList.remove('rotate-180');
            } else {
                detailsRow.classList.add('show');
                arrow.classList.add('rotate-180');
            }
        }

        // Close sidebar when clicking outside on mobile
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>