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

// Fetch CEO (assuming post_id 1 is CEO or highest position)
$ceo_query = $db->query("
    SELECT u.u_id, u.name, u.employee_id, u.img, u.currect_status, p.name as post_name
    FROM user u
    LEFT JOIN post p ON u.post_id = p.p_id
    WHERE u.active = 1 AND (LOWER(p.name) LIKE '%ceo%' OR LOWER(p.name) LIKE '%chief%' OR p.p_id = 1)
    LIMIT 1
");
$ceo = $ceo_query ? $ceo_query->fetch_assoc() : null;

// Fetch all departments with managers and team members
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
        // Fetch manager for this department (assuming manager has specific post_id or name contains 'manager')
        $manager_query = $db->query("
            SELECT u.u_id, u.name, u.employee_id, u.img, u.currect_status, u.email, p.name as post_name
            FROM user u
            LEFT JOIN post p ON u.post_id = p.p_id
            WHERE u.active = 1 AND u.dpar_id = {$dept['d_id']} 
            AND (LOWER(p.name) LIKE '%manager%' OR LOWER(p.name) LIKE '%head%' OR LOWER(p.name) LIKE '%lead%')
            LIMIT 1
        ");
        
        $manager = $manager_query ? $manager_query->fetch_assoc() : null;
        
        // Fetch team members for this department (excluding manager and CEO)
        $team_query = $db->query("
            SELECT u.u_id, u.name, u.employee_id, u.img, u.currect_status, u.email, p.name as post_name
            FROM user u
            LEFT JOIN post p ON u.post_id = p.p_id
            WHERE u.active = 1 AND u.dpar_id = {$dept['d_id']}
            AND NOT (LOWER(p.name) LIKE '%manager%' OR LOWER(p.name) LIKE '%head%' OR LOWER(p.name) LIKE '%lead%' OR LOWER(p.name) LIKE '%ceo%' OR LOWER(p.name) LIKE '%chief%')
            ORDER BY u.name
        ");
        
        $team_members = [];
        if ($team_query) {
            while ($member = $team_query->fetch_assoc()) {
                $team_members[] = $member;
            }
        }
        
        $departments[] = [
            'id' => $dept['d_id'],
            'name' => $dept['department_name'],
            'manager' => $manager,
            'team' => $team_members
        ];
    }
}

// Function to get status color
function getStatusColor($status) {
    if ($status === 'Available') return 'border-green-400 shadow-green-200';
    if (in_array($status, ['Break 1', 'Break 2', 'Lunch Break', 'Personal Time'])) return 'border-yellow-400 shadow-yellow-200';
    return 'border-gray-400 shadow-gray-200';
}

// Function to get status badge color
function getStatusBadgeColor($status) {
    if ($status === 'Available') return 'bg-green-100 text-green-800';
    if (in_array($status, ['Break 1', 'Break 2', 'Lunch Break', 'Personal Time'])) return 'bg-yellow-100 text-yellow-800';
    return 'bg-gray-100 text-gray-800';
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="description" content="Company organizational chart showing team structure and hierarchy">
    <meta name="theme-color" content="#ffffff">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Organization Chart | Company Employees Tree</title>

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

    <!-- Alpine.js and Icons -->
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Roboto', system-ui, sans-serif !important;
        }

        body, div, p {
            font-family: 'Nunito', system-ui, sans-serif !important;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
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

        /* Desktop layout */
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 16rem;
                margin-top: 4rem;
                min-height: calc(100vh - 4rem);
                padding: 1rem;
            }
        }

        /* Tablet and mobile layout */
        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0;
                margin-top: 4rem;
                min-height: calc(100vh - 4rem);
                padding: 1rem 0.5rem;
            }
        }

        /* Org chart specific styles */
        .org-tree {
            position: relative;
            padding: 20px 0;
        }

        /* Vertical lines from CEO to managers */
        .vertical-line {
            position: absolute;
            width: 2px;
            background: linear-gradient(180deg, #3498db 0%, #2c3e50 100%);
            left: 50%;
            transform: translateX(-50%);
        }

        /* Horizontal line connecting managers */
        .horizontal-line {
            position: absolute;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #3498db 20%, #3498db 80%, transparent 100%);
            top: 0;
        }

        /* Branch lines for team members */
        .branch-line {
            position: relative;
        }

        .branch-line::before {
            content: '';
            position: absolute;
            width: 2px;
            height: 40px;
            background: #94a3b8;
            left: 50%;
            top: -40px;
            transform: translateX(-50%);
        }

        /* Employee card animations */
        .employee-card {
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
        }

        .employee-card:hover {
            transform: translateY(-5px) scale(1.05);
            z-index: 10;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* CEO card special styling */
        .ceo-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .ceo-card .text-gray-500 {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        /* Manager card special styling */
        .manager-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .manager-card .text-gray-500,
        .manager-card .text-gray-600 {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        /* Pulse animation for available status */
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
            }
        }

        .status-available {
            animation: pulse 2s infinite;
        }

        /* Department section styling */
        .department-section {
            position: relative;
            margin-bottom: 4rem;
        }

        .department-section::before {
            content: '';
            position: absolute;
            width: 2px;
            height: 60px;
            background: #3498db;
            left: 50%;
            top: -60px;
            transform: translateX(-50%);
        }

        /* Team grid responsive */
        .team-grid {
            display: grid;
            gap: 1.5rem;
            padding: 2rem 1rem;
        }

        @media (min-width: 640px) {
            .team-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 768px) {
            .team-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .team-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .team-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        /* Connection lines for team members */
        .team-connector {
            position: relative;
        }

        .team-connector::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #cbd5e1 50%, transparent 100%);
            top: -30px;
            left: 0;
        }

        /* Hover effects for profile images */
        .profile-img {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .profile-img:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        /* Department header styling */
        .dept-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            margin-bottom: 2rem;
        }

        /* Empty state styling */
        .empty-state {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 1rem;
            padding: 3rem;
            text-align: center;
        }

        /* Mobile responsiveness */
        @media (max-width: 640px) {
            .org-tree {
                overflow-x: auto;
                padding-bottom: 2rem;
            }

            .employee-card {
                min-width: 200px;
            }
        }
    </style>
</head>

<body class="antialiased text-gray-800 bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen" x-data="{ sidebarOpen: false, selectedEmployee: null }">
    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:p-4 focus:bg-white focus:text-black">
        Skip to main content
    </a>

    <?php include "header.php"; ?>
    <?php include "sidebar.php"; ?>

    <!-- Main content -->
    <main id="main-content" class="main-content">
        <section class="py-6 sm:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="text-center mb-12">
                    <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-4">
                        Organization Chart
                    </h1>
                    <p class="text-gray-600 text-lg">Company hierarchy and team structure</p>
                </div>

                <!-- CEO Section -->
                <div class="flex justify-center mb-20">
                    <div class="relative">
                        <div class="employee-card ceo-card rounded-2xl shadow-2xl p-6 text-center max-w-xs mx-auto transform hover:scale-105 transition-all duration-300">
                            <div class="relative inline-block mb-4">
                                <img src="<?php echo $ceo && !empty($ceo['img']) ? htmlspecialchars($ceo['img']) : 'https://ui-avatars.com/api/?name=CEO&background=ffffff&color=764ba2&size=150'; ?>" 
                                     alt="CEO" 
                                     class="profile-img w-32 h-32 rounded-full mx-auto border-4 border-white shadow-lg object-cover">
                                <?php if ($ceo && $ceo['currect_status'] === 'Available'): ?>
                                    <span class="absolute bottom-2 right-2 w-6 h-6 bg-green-400 border-2 border-white rounded-full status-available"></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-xl font-bold mb-1"><?php echo $ceo ? htmlspecialchars($ceo['name']) : 'CEO Position'; ?></h3>
                            <p class="text-sm opacity-90 mb-2"><?php echo $ceo ? htmlspecialchars($ceo['post_name'] ?: 'Chief Executive Officer') : 'Chief Executive Officer'; ?></p>
                            <?php if ($ceo): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur">
                                    <?php echo htmlspecialchars($ceo['currect_status'] ?: 'Offline'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Vertical line from CEO to departments -->
                        <?php if (count($departments) > 0): ?>
                            <div class="vertical-line" style="height: 100px; top: 100%;"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Departments Section -->
                <?php if (count($departments) > 0): ?>
                    <!-- Horizontal line connecting all departments -->
                    <div class="relative mb-20">
                        <div class="horizontal-line" style="width: 80%; left: 10%;"></div>
                        
                        <!-- Department Managers Row -->
                        <div class="flex flex-wrap justify-center gap-8 relative">
                            <?php foreach ($departments as $index => $dept): ?>
                                <div class="department-section" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <!-- Manager Card -->
                                    <div class="flex flex-col items-center">
                                        <?php if ($dept['manager']): ?>
                                            <div class="employee-card manager-card rounded-2xl shadow-xl p-5 text-center max-w-xs transform hover:scale-105 transition-all duration-300">
                                                <div class="relative inline-block mb-3">
                                                    <img src="<?php echo !empty($dept['manager']['img']) ? htmlspecialchars($dept['manager']['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($dept['manager']['name']) . '&background=f093fb&color=ffffff&size=120'; ?>" 
                                                         alt="<?php echo htmlspecialchars($dept['manager']['name']); ?>" 
                                                         class="profile-img w-24 h-24 rounded-full mx-auto border-4 border-white shadow-lg object-cover">
                                                    <?php if ($dept['manager']['currect_status'] === 'Available'): ?>
                                                        <span class="absolute bottom-1 right-1 w-5 h-5 bg-green-400 border-2 border-white rounded-full status-available"></span>
                                                    <?php endif; ?>
                                                </div>
                                                <h4 class="text-lg font-bold mb-1"><?php echo htmlspecialchars($dept['manager']['name']); ?></h4>
                                                <p class="text-xs opacity-90 mb-1"><?php echo htmlspecialchars($dept['manager']['post_name'] ?: 'Department Manager'); ?></p>
                                                <p class="text-xs font-semibold mb-2"><?php echo htmlspecialchars($dept['name']); ?></p>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur">
                                                    <?php echo htmlspecialchars($dept['manager']['currect_status'] ?: 'Offline'); ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div class="employee-card bg-gray-100 rounded-2xl shadow-xl p-5 text-center max-w-xs border-2 border-dashed border-gray-300">
                                                <div class="w-24 h-24 rounded-full mx-auto bg-gray-200 flex items-center justify-center mb-3">
                                                    <i class="fas fa-user-tie text-3xl text-gray-400"></i>
                                                </div>
                                                <h4 class="text-lg font-bold text-gray-600 mb-1">Manager Needed</h4>
                                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($dept['name']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Department Name Badge -->
                                        <div class="dept-header mt-8 mb-4">
                                            <h3 class="text-lg font-bold text-center">
                                                <?php echo htmlspecialchars($dept['name']); ?> Team
                                                <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white/20">
                                                    <?php echo count($dept['team']); ?> members
                                                </span>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Team Members Section -->
                    <?php foreach ($departments as $dept): ?>
                        <?php if (count($dept['team']) > 0): ?>
                            <div class="mb-16">
                                <h3 class="text-2xl font-bold text-center mb-8 text-gray-800">
                                    <?php echo htmlspecialchars($dept['name']); ?> Department Team
                                </h3>
                                
                                <div class="team-grid">
                                    <?php foreach ($dept['team'] as $member): ?>
                                        <div class="branch-line">
                                            <div class="employee-card bg-white rounded-xl shadow-lg p-4 text-center transform hover:scale-105 transition-all duration-300 border-2 <?php echo getStatusColor($member['currect_status']); ?>">
                                                <div class="relative inline-block mb-3">
                                                    <img src="<?php echo !empty($member['img']) ? htmlspecialchars($member['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($member['name']) . '&background=3498db&color=ffffff&size=100'; ?>" 
                                                         alt="<?php echo htmlspecialchars($member['name']); ?>" 
                                                         class="profile-img w-20 h-20 rounded-full mx-auto border-3 border-gray-200 shadow-md object-cover">
                                                    <?php if ($member['currect_status'] === 'Available'): ?>
                                                        <span class="absolute bottom-0 right-0 w-4 h-4 bg-green-400 border-2 border-white rounded-full"></span>
                                                    <?php endif; ?>
                                                </div>
                                                <h5 class="text-sm font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($member['name']); ?></h5>
                                                <p class="text-xs text-gray-600 mb-1"><?php echo htmlspecialchars($member['post_name'] ?: 'Team Member'); ?></p>
                                                <p class="text-xs text-gray-500 mb-2">ID: <?php echo htmlspecialchars($member['employee_id']); ?></p>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo getStatusBadgeColor($member['currect_status']); ?>">
                                                    <?php echo htmlspecialchars($member['currect_status'] ?: 'Offline'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-16">
                                <h3 class="text-2xl font-bold text-center mb-8 text-gray-800">
                                    <?php echo htmlspecialchars($dept['name']); ?> Department
                                </h3>
                                <div class="empty-state max-w-md mx-auto">
                                    <i class="fas fa-users text-5xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-600">No team members yet</p>
                                    <p class="text-sm text-gray-500 mt-2">Team members will appear here once assigned</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state max-w-lg mx-auto">
                        <i class="fas fa-sitemap text-6xl text-gray-400 mb-6"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Organization Structure Found</h3>
                        <p class="text-gray-500">Start by adding departments and assigning employees to build your organization chart.</p>
                    </div>
                <?php endif; ?>

                <!-- Legend -->
                <div class="mt-16 bg-white rounded-2xl shadow-lg p-6 max-w-2xl mx-auto">
                    <h4 class="text-lg font-bold text-gray-800 mb-4">Status Legend</h4>
                    <div class="flex flex-wrap gap-4 justify-center">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-green-400 rounded-full"></span>
                            <span class="text-sm text-gray-600">Available</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-yellow-400 rounded-full"></span>
                            <span class="text-sm text-gray-600">On Break</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 bg-gray-400 rounded-full"></span>
                            <span class="text-sm text-gray-600">Offline</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Add interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scroll for better UX
            document.querySelectorAll('.employee-card').forEach((card, index) => {
                card.style.animationDelay = `${index * 0.05}s`;
            });

            // Add click to zoom on profile images
            document.querySelectorAll('.profile-img').forEach(img => {
                img.addEventListener('click', function() {
                    // You can implement a modal here to show larger image
                    console.log('Profile clicked:', this.alt);
                });
            });

            // Mobile responsiveness handler
            function handleResize() {
                if (window.innerWidth >= 1024) {
                    const sidebarData = document.querySelector('[x-data]');
                    if (sidebarData && sidebarData.__x && sidebarData.__x.$data.sidebarOpen) {
                        sidebarData.__x.$data.sidebarOpen = false;
                        document.body.style.overflow = '';
                    }
                }
            }

            window.addEventListener('resize', handleResize);
        });
    </script>
</body>
</html>