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
    if ($status === 'Available') return 'bg-green-500 animate-pulse';
    if (in_array($status, ['Break 1', 'Break 2', 'Lunch Break', 'Personal Time'])) return 'bg-yellow-500';
    return 'bg-gray-400';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        @media (min-width: 1024px) {
            .main-content {
                margin-left: 16rem; /* 256px sidebar width */
            }
        }

        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0;
            }
        }

        /* Tree container that accounts for header */
        .tree-container {
            height: calc(100vh - 4rem); /* Subtract header height */
            margin-top: 4rem; /* Header height */
            width: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        /* Tree wrapper with scrolling */
        .tree-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: auto;
            min-width: 100%;
        }

        /* Profile circles */
        .profile-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            background-size: cover;
            background-position: center;
            background-color: white;
        }

        .profile-circle:hover {
            transform: scale(1.15) translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            z-index: 20;
        }

        /* CEO special styling */
        .profile-circle.ceo {
            width: 100px;
            height: 100px;
            border: 4px solid #fbbf24;
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.6);
            background: white;
        }

        .ceo-title {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(251, 191, 36, 0.4);
        }

        /* Manager styling */
        .profile-circle.manager {
            width: 60px;
            height: 60px;
            border: 3px solid #60a5fa;
            box-shadow: 0 4px 15px rgba(96, 165, 250, 0.4);
        }

        /* Status indicator */
        .status-dot {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid white;
            z-index: 2;
        }

        /* Name labels */
        .name-label {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 8px;
            background: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            color: #1f2937;
            z-index: 5;
        }

        .name-label.ceo-name {
            font-size: 13px;
            padding: 4px 14px;
            background: linear-gradient(135deg, #fff, #fef3c7);
        }

        /* Department label */
        .dept-label {
            position: absolute;
            bottom: -28px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            white-space: nowrap;
            color: #6366f1;
            box-shadow: 0 2px 6px rgba(99, 102, 241, 0.2);
        }

        /* Role badges */
        .role-badge {
            position: absolute;
            top: -18px;
            left: 50%;
            transform: translateX(-50%);
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: 700;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .senior-badge {
            background: #10b981;
            color: white;
        }

        /* Level containers */
        .level {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            position: relative;
            width: 100%;
            z-index: 1;
        }

        .ceo-level {
            margin-bottom: 80px;
            padding-top: 20px;
        }

        .managers-level {
            margin-bottom: 70px;
            gap: 100px;
            flex-wrap: wrap;
        }

        .department-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .team-members {
            display: flex;
            gap: 25px;
            margin-top: 70px;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 350px;
        }

        .member-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Connecting lines */
        .vertical-line {
            position: absolute;
            width: 2px;
            background: rgba(255, 255, 255, 0.4);
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
        }

        .horizontal-line {
            position: absolute;
            height: 2px;
            background: rgba(255, 255, 255, 0.4);
            z-index: 0;
        }

        /* Info bar */
        .info-bar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .info-title {
            color: white;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: white;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
        }

        .stat-label {
            font-size: 11px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .legend {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: white;
            font-size: 12px;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        /* Tooltip */
        .tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 30;
            margin-bottom: 10px;
            pointer-events: none;
        }

        .tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #1f2937;
        }

        .profile-circle:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .tree-container {
                height: calc(100vh - 3.5rem);
                margin-top: 3.5rem;
            }

            .profile-circle {
                width: 40px;
                height: 40px;
            }

            .profile-circle.ceo {
                width: 70px;
                height: 70px;
            }

            .profile-circle.manager {
                width: 45px;
                height: 45px;
            }

            .name-label {
                font-size: 9px;
                padding: 2px 6px;
            }

            .name-label.ceo-name {
                font-size: 11px;
            }

            .managers-level {
                gap: 50px;
            }

            .team-members {
                gap: 15px;
            }

            .info-bar {
                padding: 10px 16px;
            }

            .info-title {
                font-size: 16px;
            }

            .stats {
                gap: 15px;
            }

            .stat-value {
                font-size: 16px;
            }

            .stat-label {
                font-size: 9px;
            }

            .legend {
                display: none;
            }
        }

        /* Mobile legend */
        @media (max-width: 640px) {
            .stats {
                display: none;
            }

            .legend {
                display: flex;
                gap: 10px;
            }

            .legend-item {
                font-size: 10px;
            }
        }

        /* Scrollbar styling */
        .tree-wrapper::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .tree-wrapper::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .tree-wrapper::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .tree-wrapper::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Loading animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        /* Pulse animation for available status */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>

<body class="antialiased" x-data="{ sidebarOpen: false }">
    
    <?php include "header.php"; ?>
    <?php include "sidebar.php"; ?>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="tree-container">
            <!-- Info Bar -->
            <div class="info-bar">
                <h1 class="info-title">
                    <i class="fas fa-sitemap"></i>
                    Organization Tree
                </h1>
                
                <!-- Desktop Stats -->
                <div class="stats hidden sm:flex">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($departments); ?></div>
                        <div class="stat-label">Departments</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $totalEmployees; ?></div>
                        <div class="stat-label">Employees</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $activeCount; ?></div>
                        <div class="stat-label">Active Now</div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-dot bg-green-500"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot bg-yellow-500"></div>
                        <span>Break</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot bg-gray-400"></div>
                        <span>Offline</span>
                    </div>
                </div>
            </div>

            <!-- Tree Wrapper -->
            <div class="tree-wrapper">
                <!-- CEO Level -->
                <?php if ($ceo): ?>
                <div class="level ceo-level fade-in">
                    <div class="member-wrapper">
                        <div class="ceo-title">
                            <i class="fas fa-crown mr-1"></i>CEO
                        </div>
                        <div class="profile-circle ceo" 
                             style="background-image: url('https://ui-avatars.com/api/?name=CEO&background=fbbf24&color=ffffff&size=100&bold=true')">
                            <div class="status-dot <?php echo getStatusDot($ceo['currect_status']); ?>"></div>
                            <div class="tooltip">
                                <strong><?php echo htmlspecialchars($ceo['post_name'] ?: 'Chief Executive Officer'); ?></strong><br>
                                ID: <?php echo htmlspecialchars($ceo['employee_id']); ?><br>
                                <?php if (!empty($ceo['email'])): ?>
                                Email: <?php echo htmlspecialchars($ceo['email']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="name-label ceo-name"><?php echo htmlspecialchars($ceo['name']); ?></div>
                        
                        <!-- Vertical line to managers -->
                        <?php if (count($departments) > 0): ?>
                        <div class="vertical-line" style="height: 80px; top: 100%;"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="level ceo-level fade-in">
                    <div class="member-wrapper">
                        <div class="ceo-title">
                            <i class="fas fa-crown mr-1"></i>CEO
                        </div>
                        <div class="profile-circle ceo" style="background: #f3f4f6;">
                            <i class="fas fa-user-tie" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 40px; color: #9ca3af;"></i>
                        </div>
                        <div class="name-label ceo-name">Position Vacant</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Managers Level -->
                <?php if (count($departments) > 0): ?>
                <div class="level managers-level fade-in" style="animation-delay: 0.2s;">
                    <!-- Horizontal line connecting managers -->
                    <?php if (count($departments) > 1): ?>
                    <div class="horizontal-line" style="width: calc(100% - 150px); top: -40px; left: 75px;"></div>
                    <?php endif; ?>
                    
                    <?php foreach ($departments as $index => $dept): ?>
                    <div class="department-group">
                        <!-- Manager -->
                        <?php if ($dept['manager']): ?>
                        <div class="member-wrapper">
                            <!-- Vertical line from horizontal connector -->
                            <div class="vertical-line" style="height: 40px; top: -40px;"></div>
                            
                            <div class="profile-circle manager" 
                                 style="background-image: url('<?php echo !empty($dept['manager']['img']) ? htmlspecialchars($dept['manager']['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($dept['manager']['name']) . '&background=60a5fa&color=ffffff&size=60'; ?>')">
                                <div class="status-dot <?php echo getStatusDot($dept['manager']['currect_status']); ?>"></div>
                                <div class="tooltip">
                                    <strong><?php echo htmlspecialchars($dept['manager']['post_name'] ?: 'Department Manager'); ?></strong><br>
                                    ID: <?php echo htmlspecialchars($dept['manager']['employee_id']); ?><br>
                                    Department: <?php echo htmlspecialchars($dept['name']); ?>
                                </div>
                            </div>
                            <div class="name-label"><?php echo htmlspecialchars($dept['manager']['name']); ?></div>
                            <div class="dept-label"><?php echo htmlspecialchars($dept['name']); ?></div>
                            
                            <!-- Vertical line to team -->
                            <?php if (count($dept['senior_associates']) > 0 || count($dept['associates']) > 0): ?>
                            <div class="vertical-line" style="height: 70px; top: 100%;"></div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="member-wrapper">
                            <div class="vertical-line" style="height: 40px; top: -40px;"></div>
                            <div class="profile-circle manager" style="background: #e5e7eb;">
                                <i class="fas fa-user-plus" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #9ca3af; font-size: 20px;"></i>
                            </div>
                            <div class="name-label">Vacant Position</div>
                            <div class="dept-label"><?php echo htmlspecialchars($dept['name']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Team Members -->
                        <?php if (count($dept['senior_associates']) > 0 || count($dept['associates']) > 0): ?>
                        <div class="team-members fade-in" style="animation-delay: <?php echo 0.3 + ($index * 0.1); ?>s;">
                            <!-- Horizontal line for team -->
                            <?php 
                            $totalMembers = count($dept['senior_associates']) + count($dept['associates']);
                            if ($totalMembers > 1): 
                            ?>
                            <div class="horizontal-line" style="width: calc(100% - 20px); top: -35px; left: 10px;"></div>
                            <?php endif; ?>
                            
                            <!-- Senior Associates -->
                            <?php foreach ($dept['senior_associates'] as $senior): ?>
                            <div class="member-wrapper">
                                <div class="vertical-line" style="height: 35px; top: -35px;"></div>
                                <div class="role-badge senior-badge">SR</div>
                                <div class="profile-circle" 
                                     style="background-image: url('<?php echo !empty($senior['img']) ? htmlspecialchars($senior['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($senior['name']) . '&background=10b981&color=ffffff&size=50'; ?>')">
                                    <div class="status-dot <?php echo getStatusDot($senior['currect_status']); ?>"></div>
                                    <div class="tooltip">
                                        <strong><?php echo htmlspecialchars($senior['post_name'] ?: 'Senior Associate'); ?></strong><br>
                                        ID: <?php echo htmlspecialchars($senior['employee_id']); ?>
                                    </div>
                                </div>
                                <div class="name-label"><?php echo htmlspecialchars(explode(' ', $senior['name'])[0]); ?></div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- Associates -->
                            <?php foreach ($dept['associates'] as $associate): ?>
                            <div class="member-wrapper">
                                <div class="vertical-line" style="height: 35px; top: -35px;"></div>
                                <div class="profile-circle" 
                                     style="background-image: url('<?php echo !empty($associate['img']) ? htmlspecialchars($associate['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($associate['name']) . '&background=6b7280&color=ffffff&size=50'; ?>')">
                                    <div class="status-dot <?php echo getStatusDot($associate['currect_status']); ?>"></div>
                                    <div class="tooltip">
                                        <strong><?php echo htmlspecialchars($associate['post_name'] ?: 'Associate'); ?></strong><br>
                                        ID: <?php echo htmlspecialchars($associate['employee_id']); ?>
                                    </div>
                                </div>
                                <div class="name-label"><?php echo htmlspecialchars(explode(' ', $associate['name'])[0]); ?></div>
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
                <div class="text-center text-white mt-20">
                    <i class="fas fa-users text-6xl opacity-50 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2">No Departments Found</h3>
                    <p class="opacity-80">Start by creating departments and assigning employees</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle sidebar toggle for responsive view
            const mainContent = document.querySelector('.main-content');
            
            // Add smooth scroll for tree wrapper
            const treeWrapper = document.querySelector('.tree-wrapper');
            let isDown = false;
            let startX;
            let scrollLeft;
            let startY;
            let scrollTop;

            if (treeWrapper) {
                // Mouse drag to scroll
                treeWrapper.addEventListener('mousedown', (e) => {
                    // Only activate on empty space or with shift key
                    if (e.shiftKey || (!e.target.classList.contains('profile-circle') && !e.target.closest('.profile-circle'))) {
                        isDown = true;
                        treeWrapper.style.cursor = 'grabbing';
                        startX = e.pageX - treeWrapper.offsetLeft;
                        startY = e.pageY - treeWrapper.offsetTop;
                        scrollLeft = treeWrapper.scrollLeft;
                        scrollTop = treeWrapper.scrollTop;
                        e.preventDefault();
                    }
                });

                treeWrapper.addEventListener('mouseleave', () => {
                    isDown = false;
                    treeWrapper.style.cursor = 'grab';
                });

                treeWrapper.addEventListener('mouseup', () => {
                    isDown = false;
                    treeWrapper.style.cursor = 'grab';
                });

                treeWrapper.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const x = e.pageX - treeWrapper.offsetLeft;
                    const y = e.pageY - treeWrapper.offsetTop;
                    const walkX = (x - startX) * 1.5;
                    const walkY = (y - startY) * 1.5;
                    treeWrapper.scrollLeft = scrollLeft - walkX;
                    treeWrapper.scrollTop = scrollTop - walkY;
                });

                // Touch support for mobile
                let touchStartX = 0;
                let touchStartY = 0;
                let touchScrollLeft = 0;
                let touchScrollTop = 0;

                treeWrapper.addEventListener('touchstart', (e) => {
                    touchStartX = e.touches[0].pageX;
                    touchStartY = e.touches[0].pageY;
                    touchScrollLeft = treeWrapper.scrollLeft;
                    touchScrollTop = treeWrapper.scrollTop;
                }, { passive: true });

                treeWrapper.addEventListener('touchmove', (e) => {
                    const touchX = e.touches[0].pageX;
                    const touchY = e.touches[0].pageY;
                    const walkX = (touchStartX - touchX);
                    const walkY = (touchStartY - touchY);
                    treeWrapper.scrollLeft = touchScrollLeft + walkX;
                    treeWrapper.scrollTop = touchScrollTop + walkY;
                }, { passive: true });
            }

            // Add click handlers for profile circles
            document.querySelectorAll('.profile-circle').forEach(circle => {
                circle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // Could open employee details modal here
                    const tooltip = this.querySelector('.tooltip');
                    if (tooltip) {
                        const name = this.nextElementSibling?.textContent || 'Unknown';
                        console.log('Employee clicked:', name);
                    }
                });
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (!treeWrapper) return;
                
                const scrollAmount = 50;
                switch(e.key) {
                    case 'ArrowLeft':
                        treeWrapper.scrollLeft -= scrollAmount;
                        break;
                    case 'ArrowRight':
                        treeWrapper.scrollLeft += scrollAmount;
                        break;
                    case 'ArrowUp':
                        treeWrapper.scrollTop -= scrollAmount;
                        break;
                    case 'ArrowDown':
                        treeWrapper.scrollTop += scrollAmount;
                        break;
                }
            });

            // Center the tree on load
            if (treeWrapper) {
                setTimeout(() => {
                    const treeContent = treeWrapper.firstElementChild;
                    if (treeContent) {
                        const contentWidth = treeContent.offsetWidth;
                        const wrapperWidth = treeWrapper.offsetWidth;
                        if (contentWidth > wrapperWidth) {
                            treeWrapper.scrollLeft = (contentWidth - wrapperWidth) / 2;
                        }
                    }
                }, 100);
            }
        });
    </script>
</body>
</html>