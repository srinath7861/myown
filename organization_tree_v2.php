<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// Fetch CEO
$ceo_query = $db->query("
    SELECT u.u_id, u.name, u.employee_id, u.img, u.currect_status, u.email, p.name as post_name
    FROM user u
    LEFT JOIN post p ON u.post_id = p.p_id
    WHERE u.active = 1 AND (LOWER(p.name) LIKE '%ceo%' OR LOWER(p.name) LIKE '%chief executive%' OR p.p_id = 1)
    LIMIT 1
");
$ceo = $ceo_query ? $ceo_query->fetch_assoc() : null;

// Fetch all departments with categorized employees
$departments_query = $db->query("
    SELECT DISTINCT d.d_id, d.name as department_name
    FROM department d
    INNER JOIN user u ON u.dpar_id = d.d_id
    WHERE u.active = 1
    ORDER BY d.name
");

$departments = [];
if ($departments_query) {
    while ($dept = $departments_query->fetch_assoc()) {
        // Fetch manager
        $manager_query = $db->query("
            SELECT u.u_id, u.name, u.employee_id, u.img, u.currect_status, u.email, p.name as post_name
            FROM user u
            LEFT JOIN post p ON u.post_id = p.p_id
            WHERE u.active = 1 AND u.dpar_id = {$dept['d_id']} 
            AND (LOWER(p.name) LIKE '%manager%' OR LOWER(p.name) LIKE '%head%' OR LOWER(p.name) LIKE '%director%')
            ORDER BY p.p_id ASC
            LIMIT 1
        ");
        
        $manager = $manager_query ? $manager_query->fetch_assoc() : null;
        
        // Fetch senior associates
        $senior_query = $db->query("
            SELECT u.u_id, u.name, u.employee_id, u.img, u.currect_status, u.email, p.name as post_name
            FROM user u
            LEFT JOIN post p ON u.post_id = p.p_id
            WHERE u.active = 1 AND u.dpar_id = {$dept['d_id']}
            AND (LOWER(p.name) LIKE '%senior%' OR LOWER(p.name) LIKE '%lead%' OR LOWER(p.name) LIKE '%supervisor%')
            AND NOT (LOWER(p.name) LIKE '%manager%' OR LOWER(p.name) LIKE '%head%' OR LOWER(p.name) LIKE '%director%' OR LOWER(p.name) LIKE '%ceo%')
            ORDER BY u.name
            LIMIT 10
        ");
        
        $senior_associates = [];
        if ($senior_query) {
            while ($senior = $senior_query->fetch_assoc()) {
                $senior_associates[] = $senior;
            }
        }
        
        // Fetch regular associates
        $team_query = $db->query("
            SELECT u.u_id, u.name, u.employee_id, u.img, u.currect_status, u.email, p.name as post_name
            FROM user u
            LEFT JOIN post p ON u.post_id = p.p_id
            WHERE u.active = 1 AND u.dpar_id = {$dept['d_id']}
            AND NOT (LOWER(p.name) LIKE '%manager%' OR LOWER(p.name) LIKE '%head%' OR LOWER(p.name) LIKE '%director%' 
                OR LOWER(p.name) LIKE '%ceo%' OR LOWER(p.name) LIKE '%senior%' OR LOWER(p.name) LIKE '%lead%' 
                OR LOWER(p.name) LIKE '%supervisor%')
            ORDER BY u.name
            LIMIT 10
        ");
        
        $associates = [];
        if ($team_query) {
            while ($member = $team_query->fetch_assoc()) {
                $associates[] = $member;
            }
        }
        
        $departments[] = [
            'id' => $dept['d_id'],
            'name' => $dept['department_name'],
            'manager' => $manager,
            'senior_associates' => $senior_associates,
            'associates' => $associates,
            'total_members' => count($senior_associates) + count($associates) + ($manager ? 1 : 0)
        ];
    }
}

// Function to get status dot color
function getStatusDot($status) {
    if ($status === 'Available') return 'status-available';
    if (in_array($status, ['Break 1', 'Break 2', 'Lunch Break', 'Personal Time'])) return 'status-break';
    return 'status-offline';
}

// Calculate total employees
$totalEmployees = 1; // CEO
foreach ($departments as $dept) {
    $totalEmployees += $dept['total_members'];
}

// Count active employees
$activeCount = 0;
if ($ceo && $ceo['currect_status'] === 'Available') $activeCount++;
foreach ($departments as $dept) {
    if ($dept['manager'] && $dept['manager']['currect_status'] === 'Available') $activeCount++;
    foreach ($dept['senior_associates'] as $s) {
        if ($s['currect_status'] === 'Available') $activeCount++;
    }
    foreach ($dept['associates'] as $a) {
        if ($a['currect_status'] === 'Available') $activeCount++;
    }
}

// Define department colors
$departmentColors = [
    '#ff9800', '#2196f3', '#4caf50', '#9c27b0', '#f44336', '#00bcd4', '#ff5722', '#795548'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Interactive company organizational chart">
    <title>Organization Tree | Company Hierarchy</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                },
            },
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }

        /* Main content area adjustment for sidebar */
        .main-content {
            transition: all 0.3s ease;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        @media (min-width: 1024px) {
            .main-content {
                margin-left: 16rem;
            }
        }

        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0;
            }
        }

        /* Tree container */
        .tree-container {
            min-height: 100vh;
            padding: 80px 20px 40px;
            position: relative;
            overflow-x: auto;
        }

        /* Header Bar */
        .header-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }

        @media (min-width: 1024px) {
            .header-bar {
                left: 16rem;
            }
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-title h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.5px;
        }

        .header-title .icon-wrapper {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        /* Stats Section */
        .stats-section {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .stat-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 20px;
            border-right: 2px solid #e2e8f0;
        }

        .stat-card:last-child {
            border-right: none;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        /* Tree Wrapper */
        .tree-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 80px;
            padding: 40px 20px;
            min-width: max-content;
            margin: 0 auto;
        }

        /* Node Styles */
        .node {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 10;
        }

        .node-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            border: 4px solid #c2185b;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .node-circle:hover {
            transform: scale(1.05) translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        /* CEO Node */
        .node-circle.ceo {
            width: 140px;
            height: 140px;
            border-color: #c2185b;
            border-width: 5px;
            background: linear-gradient(135deg, #fff 0%, #fce4ec 100%);
        }

        /* Manager Nodes */
        .node-circle.manager {
            width: 110px;
            height: 110px;
            border-width: 4px;
        }

        /* Team Member Nodes */
        .node-circle.member {
            width: 90px;
            height: 90px;
            border-width: 3px;
        }

        /* Node Icon */
        .node-icon {
            width: 40px;
            height: 40px;
            background: #c2185b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .node-circle.ceo .node-icon {
            width: 48px;
            height: 48px;
            font-size: 22px;
            background: linear-gradient(135deg, #c2185b, #ad1457);
        }

        .node-circle.manager .node-icon {
            width: 36px;
            height: 36px;
            font-size: 16px;
        }

        .node-circle.member .node-icon {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }

        /* Node Content */
        .node-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            text-align: center;
            line-height: 1.2;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding: 0 8px;
        }

        .node-subtitle {
            font-size: 11px;
            color: #64748b;
            text-align: center;
            margin-top: 2px;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding: 0 8px;
        }

        .node-circle.ceo .node-title {
            font-size: 16px;
            max-width: 120px;
        }

        .node-circle.ceo .node-subtitle {
            font-size: 12px;
            max-width: 120px;
        }

        /* Status Indicator */
        .status-indicator {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 3px solid white;
            z-index: 2;
        }

        .status-available {
            background: #10b981;
            animation: pulse 2s infinite;
        }

        .status-break {
            background: #f59e0b;
        }

        .status-offline {
            background: #6b7280;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        /* User Image */
        .user-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            opacity: 0.1;
        }

        /* Level Containers */
        .level {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 100px;
            position: relative;
            width: 100%;
        }

        .department-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 60px;
            position: relative;
        }

        .team-level {
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 600px;
            position: relative;
        }

        /* Connector Lines */
        .connector-vertical {
            position: absolute;
            width: 2px;
            background: #cbd5e1;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1;
        }

        .connector-horizontal {
            position: absolute;
            height: 2px;
            background: #cbd5e1;
            z-index: 1;
        }

        /* Tooltip */
        .tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 50;
            margin-bottom: 15px;
            pointer-events: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #1e293b;
        }

        .node-circle:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }

        .tooltip-row {
            display: flex;
            gap: 8px;
            margin: 4px 0;
        }

        .tooltip-label {
            font-weight: 600;
            color: #94a3b8;
        }

        .tooltip-value {
            color: white;
        }

        /* Legend */
        .legend {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 90;
        }

        .legend-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .legend-text {
            font-size: 12px;
            color: #64748b;
        }

        /* Loading Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-bar {
                padding: 0 20px;
            }

            .header-title h1 {
                font-size: 20px;
            }

            .stats-section {
                display: none;
            }

            .node-circle {
                width: 80px;
                height: 80px;
            }

            .node-circle.ceo {
                width: 100px;
                height: 100px;
            }

            .node-circle.manager {
                width: 90px;
                height: 90px;
            }

            .node-circle.member {
                width: 70px;
                height: 70px;
            }

            .node-icon {
                width: 30px;
                height: 30px;
                font-size: 14px;
            }

            .node-title {
                font-size: 11px;
            }

            .node-subtitle {
                font-size: 9px;
            }

            .level {
                gap: 50px;
            }

            .team-level {
                gap: 20px;
            }

            .legend {
                bottom: 20px;
                right: 20px;
                padding: 15px;
            }
        }

        /* Print Styles */
        @media print {
            .header-bar {
                position: relative;
                box-shadow: none;
                border-bottom: 2px solid #e2e8f0;
            }

            .legend {
                position: relative;
                margin-top: 40px;
            }

            .node-circle:hover {
                transform: none;
            }
        }

        /* Zoom Controls */
        .zoom-controls {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 2px;
            overflow: hidden;
            z-index: 90;
        }

        @media (min-width: 1024px) {
            .zoom-controls {
                left: 286px;
            }
        }

        .zoom-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: none;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 18px;
        }

        .zoom-btn:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .zoom-btn:active {
            background: #e2e8f0;
        }

        /* Department Colors */
        .dept-color-0 { border-color: #ff9800 !important; }
        .dept-color-0 .node-icon { background: #ff9800 !important; }
        
        .dept-color-1 { border-color: #2196f3 !important; }
        .dept-color-1 .node-icon { background: #2196f3 !important; }
        
        .dept-color-2 { border-color: #4caf50 !important; }
        .dept-color-2 .node-icon { background: #4caf50 !important; }
        
        .dept-color-3 { border-color: #9c27b0 !important; }
        .dept-color-3 .node-icon { background: #9c27b0 !important; }
        
        .dept-color-4 { border-color: #f44336 !important; }
        .dept-color-4 .node-icon { background: #f44336 !important; }
        
        .dept-color-5 { border-color: #00bcd4 !important; }
        .dept-color-5 .node-icon { background: #00bcd4 !important; }
        
        .dept-color-6 { border-color: #ff5722 !important; }
        .dept-color-6 .node-icon { background: #ff5722 !important; }
        
        .dept-color-7 { border-color: #795548 !important; }
        .dept-color-7 .node-icon { background: #795548 !important; }
    </style>
</head>

<body class="antialiased" x-data="{ sidebarOpen: false, zoomLevel: 1 }">
    
    <?php include "header.php"; ?>
    <?php include "sidebar.php"; ?>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Header Bar -->
        <div class="header-bar">
            <div class="header-title">
                <div class="icon-wrapper">
                    <i class="fas fa-sitemap"></i>
                </div>
                <h1>Organization Structure</h1>
            </div>
            
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($departments); ?></div>
                    <div class="stat-label">Departments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalEmployees; ?></div>
                    <div class="stat-label">Total Staff</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $activeCount; ?></div>
                    <div class="stat-label">Active Now</div>
                </div>
            </div>
        </div>

        <!-- Tree Container -->
        <div class="tree-container" id="treeContainer">
            <div class="tree-wrapper" :style="`transform: scale(${zoomLevel})`">
                
                <!-- CEO Level -->
                <?php if ($ceo): ?>
                <div class="node fade-in-up">
                    <div class="node-circle ceo">
                        <?php if (!empty($ceo['img'])): ?>
                        <img src="<?php echo htmlspecialchars($ceo['img']); ?>" alt="<?php echo htmlspecialchars($ceo['name']); ?>" class="user-image">
                        <?php endif; ?>
                        <div class="node-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="node-title"><?php echo htmlspecialchars($ceo['name']); ?></div>
                        <div class="node-subtitle">Chief Executive Officer</div>
                        <div class="status-indicator <?php echo getStatusDot($ceo['currect_status']); ?>"></div>
                        
                        <div class="tooltip">
                            <div class="tooltip-row">
                                <span class="tooltip-label">Position:</span>
                                <span class="tooltip-value"><?php echo htmlspecialchars($ceo['post_name'] ?: 'CEO'); ?></span>
                            </div>
                            <div class="tooltip-row">
                                <span class="tooltip-label">Employee ID:</span>
                                <span class="tooltip-value"><?php echo htmlspecialchars($ceo['employee_id']); ?></span>
                            </div>
                            <?php if (!empty($ceo['email'])): ?>
                            <div class="tooltip-row">
                                <span class="tooltip-label">Email:</span>
                                <span class="tooltip-value"><?php echo htmlspecialchars($ceo['email']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="tooltip-row">
                                <span class="tooltip-label">Status:</span>
                                <span class="tooltip-value"><?php echo htmlspecialchars($ceo['currect_status']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (count($departments) > 0): ?>
                    <div class="connector-vertical" style="height: 80px; top: 100%;"></div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="node fade-in-up">
                    <div class="node-circle ceo">
                        <div class="node-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="node-title">Position Vacant</div>
                        <div class="node-subtitle">Chief Executive Officer</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Departments Level -->
                <?php if (count($departments) > 0): ?>
                <div class="level fade-in-up" style="animation-delay: 0.2s;">
                    <?php if (count($departments) > 1): ?>
                    <div class="connector-horizontal" 
                         style="width: <?php echo (count($departments) - 1) * 200; ?>px; 
                                top: -40px; 
                                left: 50%; 
                                transform: translateX(-50%);"></div>
                    <?php endif; ?>
                    
                    <?php foreach ($departments as $index => $dept): ?>
                    <div class="department-group">
                        <?php if (count($departments) > 1): ?>
                        <div class="connector-vertical" style="height: 40px; top: -40px;"></div>
                        <?php endif; ?>
                        
                        <!-- Department Manager -->
                        <div class="node">
                            <?php if ($dept['manager']): ?>
                            <div class="node-circle manager dept-color-<?php echo $index % 8; ?>">
                                <?php if (!empty($dept['manager']['img'])): ?>
                                <img src="<?php echo htmlspecialchars($dept['manager']['img']); ?>" alt="<?php echo htmlspecialchars($dept['manager']['name']); ?>" class="user-image">
                                <?php endif; ?>
                                <div class="node-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="node-title"><?php echo htmlspecialchars($dept['manager']['name']); ?></div>
                                <div class="node-subtitle"><?php echo htmlspecialchars($dept['name']); ?></div>
                                <div class="status-indicator <?php echo getStatusDot($dept['manager']['currect_status']); ?>"></div>
                                
                                <div class="tooltip">
                                    <div class="tooltip-row">
                                        <span class="tooltip-label">Department:</span>
                                        <span class="tooltip-value"><?php echo htmlspecialchars($dept['name']); ?></span>
                                    </div>
                                    <div class="tooltip-row">
                                        <span class="tooltip-label">Position:</span>
                                        <span class="tooltip-value"><?php echo htmlspecialchars($dept['manager']['post_name'] ?: 'Manager'); ?></span>
                                    </div>
                                    <div class="tooltip-row">
                                        <span class="tooltip-label">Employee ID:</span>
                                        <span class="tooltip-value"><?php echo htmlspecialchars($dept['manager']['employee_id']); ?></span>
                                    </div>
                                    <?php if (!empty($dept['manager']['email'])): ?>
                                    <div class="tooltip-row">
                                        <span class="tooltip-label">Email:</span>
                                        <span class="tooltip-value"><?php echo htmlspecialchars($dept['manager']['email']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="tooltip-row">
                                        <span class="tooltip-label">Team Size:</span>
                                        <span class="tooltip-value"><?php echo $dept['total_members'] - 1; ?> members</span>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="node-circle manager dept-color-<?php echo $index % 8; ?>">
                                <div class="node-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="node-title">Vacant Position</div>
                                <div class="node-subtitle"><?php echo htmlspecialchars($dept['name']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (count($dept['senior_associates']) > 0 || count($dept['associates']) > 0): ?>
                            <div class="connector-vertical" style="height: 60px; top: 100%;"></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Team Members -->
                        <?php if (count($dept['senior_associates']) > 0 || count($dept['associates']) > 0): ?>
                        <div class="team-level fade-in-up" style="animation-delay: <?php echo 0.3 + ($index * 0.1); ?>s;">
                            <?php 
                            $totalTeamMembers = count($dept['senior_associates']) + count($dept['associates']);
                            if ($totalTeamMembers > 1): 
                            ?>
                            <div class="connector-horizontal" 
                                 style="width: <?php echo ($totalTeamMembers - 1) * 130; ?>px; 
                                        top: -30px; 
                                        left: 50%; 
                                        transform: translateX(-50%);"></div>
                            <?php endif; ?>
                            
                            <!-- Senior Associates -->
                            <?php foreach ($dept['senior_associates'] as $senior): ?>
                            <div class="node">
                                <div class="connector-vertical" style="height: 30px; top: -30px;"></div>
                                <div class="node-circle member dept-color-<?php echo $index % 8; ?>">
                                    <?php if (!empty($senior['img'])): ?>
                                    <img src="<?php echo htmlspecialchars($senior['img']); ?>" alt="<?php echo htmlspecialchars($senior['name']); ?>" class="user-image">
                                    <?php endif; ?>
                                    <div class="node-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="node-title"><?php echo htmlspecialchars(explode(' ', $senior['name'])[0]); ?></div>
                                    <div class="node-subtitle">Senior</div>
                                    <div class="status-indicator <?php echo getStatusDot($senior['currect_status']); ?>"></div>
                                    
                                    <div class="tooltip">
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Name:</span>
                                            <span class="tooltip-value"><?php echo htmlspecialchars($senior['name']); ?></span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Position:</span>
                                            <span class="tooltip-value"><?php echo htmlspecialchars($senior['post_name'] ?: 'Senior Associate'); ?></span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Employee ID:</span>
                                            <span class="tooltip-value"><?php echo htmlspecialchars($senior['employee_id']); ?></span>
                                        </div>
                                        <?php if (!empty($senior['email'])): ?>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Email:</span>
                                            <span class="tooltip-value"><?php echo htmlspecialchars($senior['email']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- Regular Associates -->
                            <?php foreach ($dept['associates'] as $associate): ?>
                            <div class="node">
                                <div class="connector-vertical" style="height: 30px; top: -30px;"></div>
                                <div class="node-circle member dept-color-<?php echo $index % 8; ?>">
                                    <?php if (!empty($associate['img'])): ?>
                                    <img src="<?php echo htmlspecialchars($associate['img']); ?>" alt="<?php echo htmlspecialchars($associate['name']); ?>" class="user-image">
                                    <?php endif; ?>
                                    <div class="node-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="node-title"><?php echo htmlspecialchars(explode(' ', $associate['name'])[0]); ?></div>
                                    <div class="node-subtitle">Associate</div>
                                    <div class="status-indicator <?php echo getStatusDot($associate['currect_status']); ?>"></div>
                                    
                                    <div class="tooltip">
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Name:</span>
                                            <span class="tooltip-value"><?php echo htmlspecialchars($associate['name']); ?></span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Position:</span>
                                            <span class="tooltip-value"><?php echo htmlspecialchars($associate['post_name'] ?: 'Associate'); ?></span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Employee ID:</span>
                                            <span class="tooltip-value"><?php echo htmlspecialchars($associate['employee_id']); ?></span>
                                        </div>
                                        <?php if (!empty($associate['email'])): ?>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Email:</span>
                                            <span class="tooltip-value"><?php echo htmlspecialchars($associate['email']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Empty State -->
                <?php if (count($departments) == 0): ?>
                <div class="text-center mt-20">
                    <div class="inline-flex items-center justify-center w-32 h-32 bg-gray-100 rounded-full mb-6">
                        <i class="fas fa-users text-5xl text-gray-400"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-700 mb-2">No Departments Found</h3>
                    <p class="text-gray-500">Start by creating departments and assigning employees to build your organization structure.</p>
                </div>
                <?php endif; ?>
                
            </div>
        </div>

        <!-- Zoom Controls -->
        <div class="zoom-controls">
            <button class="zoom-btn" @click="zoomLevel = Math.min(zoomLevel + 0.1, 2)" title="Zoom In">
                <i class="fas fa-plus"></i>
            </button>
            <button class="zoom-btn" @click="zoomLevel = 1" title="Reset Zoom">
                <i class="fas fa-compress"></i>
            </button>
            <button class="zoom-btn" @click="zoomLevel = Math.max(zoomLevel - 0.1, 0.5)" title="Zoom Out">
                <i class="fas fa-minus"></i>
            </button>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-title">Status Legend</div>
            <div class="legend-item">
                <div class="legend-dot status-available"></div>
                <span class="legend-text">Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot status-break"></div>
                <span class="legend-text">On Break</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot status-offline"></div>
                <span class="legend-text">Offline</span>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const treeContainer = document.getElementById('treeContainer');
            const treeWrapper = document.querySelector('.tree-wrapper');
            
            // Pan functionality
            let isPanning = false;
            let startX = 0;
            let startY = 0;
            let scrollLeft = 0;
            let scrollTop = 0;

            // Mouse events for panning
            treeContainer.addEventListener('mousedown', (e) => {
                // Only start panning if clicking on empty space or with middle mouse button
                if (e.button === 1 || (e.button === 0 && !e.target.closest('.node-circle'))) {
                    isPanning = true;
                    treeContainer.style.cursor = 'grabbing';
                    startX = e.pageX - treeContainer.offsetLeft;
                    startY = e.pageY - treeContainer.offsetTop;
                    scrollLeft = treeContainer.scrollLeft;
                    scrollTop = treeContainer.scrollTop;
                    e.preventDefault();
                }
            });

            treeContainer.addEventListener('mouseleave', () => {
                isPanning = false;
                treeContainer.style.cursor = 'grab';
            });

            treeContainer.addEventListener('mouseup', () => {
                isPanning = false;
                treeContainer.style.cursor = 'grab';
            });

            treeContainer.addEventListener('mousemove', (e) => {
                if (!isPanning) return;
                e.preventDefault();
                const x = e.pageX - treeContainer.offsetLeft;
                const y = e.pageY - treeContainer.offsetTop;
                const walkX = (x - startX) * 1.5;
                const walkY = (y - startY) * 1.5;
                treeContainer.scrollLeft = scrollLeft - walkX;
                treeContainer.scrollTop = scrollTop - walkY;
            });

            // Touch events for mobile
            let touchStartX = 0;
            let touchStartY = 0;
            let touchScrollLeft = 0;
            let touchScrollTop = 0;

            treeContainer.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].pageX;
                touchStartY = e.touches[0].pageY;
                touchScrollLeft = treeContainer.scrollLeft;
                touchScrollTop = treeContainer.scrollTop;
            }, { passive: true });

            treeContainer.addEventListener('touchmove', (e) => {
                const touchX = e.touches[0].pageX;
                const touchY = e.touches[0].pageY;
                const walkX = touchStartX - touchX;
                const walkY = touchStartY - touchY;
                treeContainer.scrollLeft = touchScrollLeft + walkX;
                treeContainer.scrollTop = touchScrollTop + walkY;
            }, { passive: true });

            // Center the tree on load
            setTimeout(() => {
                const contentWidth = treeWrapper.scrollWidth;
                const containerWidth = treeContainer.clientWidth;
                if (contentWidth > containerWidth) {
                    treeContainer.scrollLeft = (contentWidth - containerWidth) / 2;
                }
            }, 100);

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    if (e.key === '=' || e.key === '+') {
                        e.preventDefault();
                        // Trigger zoom in
                        const event = new Event('click');
                        document.querySelector('.zoom-btn:first-child').dispatchEvent(event);
                    } else if (e.key === '-') {
                        e.preventDefault();
                        // Trigger zoom out
                        const event = new Event('click');
                        document.querySelector('.zoom-btn:last-child').dispatchEvent(event);
                    } else if (e.key === '0') {
                        e.preventDefault();
                        // Reset zoom
                        const event = new Event('click');
                        document.querySelector('.zoom-btn:nth-child(2)').dispatchEvent(event);
                    }
                }
            });

            // Add click handlers for nodes
            document.querySelectorAll('.node-circle').forEach(node => {
                node.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // You can add functionality here to show employee details
                    // For example, open a modal or navigate to employee profile
                    const nodeTitle = this.querySelector('.node-title')?.textContent;
                    if (nodeTitle && nodeTitle !== 'Position Vacant' && nodeTitle !== 'Vacant Position') {
                        console.log('Employee clicked:', nodeTitle);
                        // Add your employee detail view logic here
                    }
                });
            });

            // Print functionality
            window.addEventListener('beforeprint', () => {
                // Reset zoom for printing
                const alpineData = Alpine.$data(document.body);
                if (alpineData) {
                    alpineData.zoomLevel = 1;
                }
            });
        });
    </script>
</body>
</html>