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
    SELECT u.u_id, u.name, u.employee_id, u.img, u.currect_status, u.email, p.name as post_name
    FROM user u
    LEFT JOIN post p ON u.post_id = p.p_id
    WHERE u.active = 1 AND (LOWER(p.name) LIKE '%ceo%' OR LOWER(p.name) LIKE '%chief executive%' OR p.p_id = 1)
    LIMIT 1
");
$ceo = $ceo_query ? $ceo_query->fetch_assoc() : null;

// Fetch all departments with managers and team members categorized by role
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
        // Fetch manager for this department
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
        
        // Fetch senior associates for this department
        $senior_query = $db->query("
            SELECT u.u_id, u.name, u.employee_id, u.img, u.currect_status, u.email, p.name as post_name
            FROM user u
            LEFT JOIN post p ON u.post_id = p.p_id
            WHERE u.active = 1 AND u.dpar_id = {$dept['d_id']}
            AND (LOWER(p.name) LIKE '%senior%' OR LOWER(p.name) LIKE '%lead%' OR LOWER(p.name) LIKE '%supervisor%')
            AND NOT (LOWER(p.name) LIKE '%manager%' OR LOWER(p.name) LIKE '%head%' OR LOWER(p.name) LIKE '%director%' OR LOWER(p.name) LIKE '%ceo%')
            ORDER BY u.name
        ");
        
        $senior_associates = [];
        if ($senior_query) {
            while ($senior = $senior_query->fetch_assoc()) {
                $senior_associates[] = $senior;
            }
        }
        
        // Fetch regular associates/team members for this department
        $team_query = $db->query("
            SELECT u.u_id, u.name, u.employee_id, u.img, u.currect_status, u.email, p.name as post_name
            FROM user u
            LEFT JOIN post p ON u.post_id = p.p_id
            WHERE u.active = 1 AND u.dpar_id = {$dept['d_id']}
            AND NOT (LOWER(p.name) LIKE '%manager%' OR LOWER(p.name) LIKE '%head%' OR LOWER(p.name) LIKE '%director%' 
                OR LOWER(p.name) LIKE '%ceo%' OR LOWER(p.name) LIKE '%senior%' OR LOWER(p.name) LIKE '%lead%' 
                OR LOWER(p.name) LIKE '%supervisor%')
            ORDER BY u.name
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

// Function to get status color
function getStatusColor($status) {
    if ($status === 'Available') return 'ring-green-500 ring-2';
    if (in_array($status, ['Break 1', 'Break 2', 'Lunch Break', 'Personal Time'])) return 'ring-yellow-500 ring-2';
    return 'ring-gray-300 ring-1';
}

// Function to get status indicator
function getStatusIndicator($status) {
    if ($status === 'Available') return '<div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white animate-pulse"></div>';
    if (in_array($status, ['Break 1', 'Break 2', 'Lunch Break', 'Personal Time'])) return '<div class="absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-500 rounded-full border-2 border-white"></div>';
    return '<div class="absolute -bottom-1 -right-1 w-4 h-4 bg-gray-400 rounded-full border-2 border-white"></div>';
}

// Function to get role badge style
function getRoleBadgeStyle($role) {
    if (stripos($role, 'ceo') !== false || stripos($role, 'chief') !== false) {
        return 'bg-gradient-to-r from-purple-600 to-pink-600 text-white';
    }
    if (stripos($role, 'manager') !== false || stripos($role, 'head') !== false || stripos($role, 'director') !== false) {
        return 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white';
    }
    if (stripos($role, 'senior') !== false || stripos($role, 'lead') !== false) {
        return 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white';
    }
    return 'bg-gradient-to-r from-gray-600 to-gray-700 text-white';
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="description" content="Interactive company organizational chart showing complete team hierarchy">
    <meta name="theme-color" content="#6366f1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Company Hierarchy | Interactive Organization Tree</title>

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
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'fade-in': 'fadeIn 0.5s ease-out',
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
        /* Custom animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Gradient backgrounds */
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .gradient-bg-light {
            background: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Hierarchy lines */
        .hierarchy-line {
            position: relative;
        }

        .hierarchy-line::before {
            content: '';
            position: absolute;
            width: 2px;
            background: linear-gradient(180deg, #6366f1 0%, #8b5cf6 100%);
            left: 50%;
            transform: translateX(-50%);
        }

        .horizontal-connector {
            position: relative;
        }

        .horizontal-connector::before {
            content: '';
            position: absolute;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #6366f1 20%, #6366f1 80%, transparent 100%);
            top: -20px;
            left: 0;
            right: 0;
        }

        /* Card hover effects */
        .employee-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .employee-card:hover {
            transform: translateY(-8px) scale(1.02);
        }

        /* CEO special card */
        .ceo-card {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            position: relative;
            overflow: hidden;
        }

        .ceo-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 8s ease-in-out infinite;
        }

        /* Manager card */
        .manager-card {
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
        }

        /* Senior card */
        .senior-card {
            background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
        }

        /* Mobile menu */
        .mobile-dept-menu {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-dept-menu {
                display: block;
            }

            .desktop-view {
                display: none;
            }
        }

        @media (min-width: 769px) {
            .mobile-view {
                display: none;
            }
        }

        /* Department section animations */
        .dept-section {
            animation: slideUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .dept-section:nth-child(1) { animation-delay: 0.1s; }
        .dept-section:nth-child(2) { animation-delay: 0.2s; }
        .dept-section:nth-child(3) { animation-delay: 0.3s; }
        .dept-section:nth-child(4) { animation-delay: 0.4s; }
        .dept-section:nth-child(5) { animation-delay: 0.5s; }

        /* Profile image styles */
        .profile-ring {
            position: relative;
            display: inline-block;
        }

        .profile-ring::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
        }

        /* Responsive grid */
        .org-grid {
            display: grid;
            gap: 1.5rem;
        }

        @media (min-width: 640px) {
            .org-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .org-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .org-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Main content responsive */
        .main-content {
            transition: all 0.3s ease;
        }

        @media (min-width: 1024px) {
            .main-content {
                margin-left: 16rem;
                margin-top: 4rem;
            }
        }

        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0;
                margin-top: 4rem;
            }
        }

        /* Tooltip */
        .tooltip {
            visibility: hidden;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .employee-card:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }

        /* Status pulse animation */
        @keyframes pulse-green {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            }
            70% {
                box-shadow: 0 0 0 8px rgba(34, 197, 94, 0);
            }
        }

        .status-pulse {
            animation: pulse-green 2s infinite;
        }
    </style>
</head>

<body class="antialiased bg-gradient-to-br from-slate-50 via-white to-indigo-50 min-h-screen" 
      x-data="{ 
          sidebarOpen: false, 
          selectedDept: null,
          mobileMenuOpen: false,
          viewMode: 'hierarchy',
          searchQuery: '',
          expandedDepts: []
      }">
    
    <?php include "header.php"; ?>
    <?php include "sidebar.php"; ?>

    <!-- Main Content -->
    <main class="main-content min-h-screen">
        <div class="px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Page Header -->
            <div class="text-center mb-12 animate-fade-in">
                <h1 class="text-4xl sm:text-5xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent mb-4">
                    Company Hierarchy
                </h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Explore our organizational structure and meet the team
                </p>
                
                <!-- View Mode Toggle -->
                <div class="mt-6 flex justify-center gap-2">
                    <button @click="viewMode = 'hierarchy'" 
                            :class="viewMode === 'hierarchy' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'"
                            class="px-4 py-2 rounded-lg font-medium transition-all duration-200 shadow-sm hover:shadow-md">
                        <i class="fas fa-sitemap mr-2"></i>Hierarchy View
                    </button>
                    <button @click="viewMode = 'grid'" 
                            :class="viewMode === 'grid' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'"
                            class="px-4 py-2 rounded-lg font-medium transition-all duration-200 shadow-sm hover:shadow-md">
                        <i class="fas fa-th mr-2"></i>Grid View
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="mt-6 max-w-md mx-auto">
                    <div class="relative">
                        <input type="text" 
                               x-model="searchQuery"
                               placeholder="Search employees..." 
                               class="w-full px-4 py-3 pl-12 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- CEO Section -->
            <?php if ($ceo): ?>
            <div class="flex justify-center mb-16" x-show="viewMode === 'hierarchy'">
                <div class="relative">
                    <div class="employee-card ceo-card rounded-2xl shadow-2xl p-8 text-white max-w-sm mx-auto transform hover:scale-105 transition-all duration-300">
                        <div class="relative z-10">
                            <!-- Crown Icon -->
                            <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                                <div class="bg-yellow-400 rounded-full p-2 shadow-lg">
                                    <i class="fas fa-crown text-white text-xl"></i>
                                </div>
                            </div>
                            
                            <!-- Profile Image -->
                            <div class="relative inline-block mb-4 mt-4">
                                <div class="profile-ring">
                                    <img src="<?php echo !empty($ceo['img']) ? htmlspecialchars($ceo['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($ceo['name']) . '&background=8b5cf6&color=ffffff&size=150'; ?>" 
                                         alt="<?php echo htmlspecialchars($ceo['name']); ?>" 
                                         class="w-32 h-32 rounded-full border-4 border-white/30 shadow-xl object-cover">
                                    <?php echo getStatusIndicator($ceo['currect_status']); ?>
                                </div>
                            </div>
                            
                            <!-- CEO Info -->
                            <h2 class="text-2xl font-bold mb-1"><?php echo htmlspecialchars($ceo['name']); ?></h2>
                            <p class="text-white/90 font-medium mb-3">Chief Executive Officer</p>
                            
                            <!-- Contact Info -->
                            <div class="flex justify-center gap-4 mb-4">
                                <span class="text-sm text-white/80">
                                    <i class="fas fa-id-badge mr-1"></i><?php echo htmlspecialchars($ceo['employee_id']); ?>
                                </span>
                                <?php if (!empty($ceo['email'])): ?>
                                <span class="text-sm text-white/80">
                                    <i class="fas fa-envelope mr-1"></i>Email
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Status Badge -->
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-white/20 backdrop-blur-sm">
                                <span class="w-2 h-2 rounded-full bg-green-400 mr-2 animate-pulse"></span>
                                <?php echo htmlspecialchars($ceo['currect_status'] ?: 'Offline'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Vertical Line to Departments -->
                    <?php if (count($departments) > 0): ?>
                    <div class="hierarchy-line" style="height: 80px; top: 100%;">
                        <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-3 h-3 bg-indigo-600 rounded-full"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Departments Section - Hierarchy View -->
            <div x-show="viewMode === 'hierarchy'" class="space-y-12">
                <?php foreach ($departments as $index => $dept): ?>
                <div class="dept-section" x-show="searchQuery === '' || '<?php echo strtolower($dept['name']); ?>'.includes(searchQuery.toLowerCase())">
                    
                    <!-- Department Header -->
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 mb-8 shadow-xl">
                        <div class="flex flex-col sm:flex-row items-center justify-between">
                            <div class="text-center sm:text-left mb-4 sm:mb-0">
                                <h3 class="text-2xl font-bold text-white mb-2">
                                    <?php echo htmlspecialchars($dept['name']); ?> Department
                                </h3>
                                <div class="flex flex-wrap gap-3 justify-center sm:justify-start">
                                    <span class="bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm">
                                        <i class="fas fa-users mr-1"></i><?php echo $dept['total_members']; ?> Members
                                    </span>
                                    <?php if ($dept['manager']): ?>
                                    <span class="bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm">
                                        <i class="fas fa-user-tie mr-1"></i>Led by <?php echo htmlspecialchars(explode(' ', $dept['manager']['name'])[0]); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Expand/Collapse Button for Mobile -->
                            <button @click="expandedDepts.includes(<?php echo $dept['id']; ?>) ? expandedDepts = expandedDepts.filter(id => id !== <?php echo $dept['id']; ?>) : expandedDepts.push(<?php echo $dept['id']; ?>)"
                                    class="sm:hidden bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg">
                                <i class="fas" :class="expandedDepts.includes(<?php echo $dept['id']; ?>) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Department Content -->
                    <div :class="{'hidden sm:block': !expandedDepts.includes(<?php echo $dept['id']; ?>)}" class="sm:block">
                        
                        <!-- Manager -->
                        <?php if ($dept['manager']): ?>
                        <div class="flex justify-center mb-8">
                            <div class="employee-card manager-card rounded-xl shadow-xl p-6 text-white max-w-xs transform hover:scale-105 transition-all duration-300">
                                <div class="relative inline-block mb-3">
                                    <img src="<?php echo !empty($dept['manager']['img']) ? htmlspecialchars($dept['manager']['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($dept['manager']['name']) . '&background=3b82f6&color=ffffff&size=120'; ?>" 
                                         alt="<?php echo htmlspecialchars($dept['manager']['name']); ?>" 
                                         class="w-24 h-24 rounded-full border-3 border-white/30 shadow-lg object-cover">
                                    <?php echo getStatusIndicator($dept['manager']['currect_status']); ?>
                                </div>
                                <h4 class="text-lg font-bold mb-1"><?php echo htmlspecialchars($dept['manager']['name']); ?></h4>
                                <p class="text-white/90 text-sm mb-2"><?php echo htmlspecialchars($dept['manager']['post_name'] ?: 'Department Manager'); ?></p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur-sm">
                                    <?php echo htmlspecialchars($dept['manager']['currect_status'] ?: 'Offline'); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Senior Associates -->
                        <?php if (count($dept['senior_associates']) > 0): ?>
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 text-center">
                                <i class="fas fa-star text-emerald-500 mr-2"></i>Senior Associates
                            </h4>
                            <div class="org-grid">
                                <?php foreach ($dept['senior_associates'] as $senior): ?>
                                <div class="employee-card senior-card rounded-xl shadow-lg p-5 text-white transform hover:scale-105 transition-all duration-300">
                                    <div class="relative inline-block mb-3">
                                        <img src="<?php echo !empty($senior['img']) ? htmlspecialchars($senior['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($senior['name']) . '&background=10b981&color=ffffff&size=100'; ?>" 
                                             alt="<?php echo htmlspecialchars($senior['name']); ?>" 
                                             class="w-20 h-20 rounded-full border-3 border-white/30 shadow-md object-cover mx-auto">
                                        <?php echo getStatusIndicator($senior['currect_status']); ?>
                                    </div>
                                    <h5 class="text-sm font-bold mb-1 text-center"><?php echo htmlspecialchars($senior['name']); ?></h5>
                                    <p class="text-xs text-white/90 mb-2 text-center"><?php echo htmlspecialchars($senior['post_name'] ?: 'Senior Associate'); ?></p>
                                    <div class="text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur-sm">
                                            <?php echo htmlspecialchars($senior['currect_status'] ?: 'Offline'); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Associates -->
                        <?php if (count($dept['associates']) > 0): ?>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-700 mb-4 text-center">
                                <i class="fas fa-users text-blue-500 mr-2"></i>Associates
                            </h4>
                            <div class="org-grid">
                                <?php foreach ($dept['associates'] as $associate): ?>
                                <div class="employee-card bg-white rounded-xl shadow-lg p-4 transform hover:scale-105 transition-all duration-300 <?php echo getStatusColor($associate['currect_status']); ?>">
                                    <div class="relative inline-block mb-3">
                                        <img src="<?php echo !empty($associate['img']) ? htmlspecialchars($associate['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($associate['name']) . '&background=6366f1&color=ffffff&size=80'; ?>" 
                                             alt="<?php echo htmlspecialchars($associate['name']); ?>" 
                                             class="w-16 h-16 rounded-full shadow-md object-cover mx-auto">
                                        <?php echo getStatusIndicator($associate['currect_status']); ?>
                                    </div>
                                    <h5 class="text-sm font-semibold text-gray-800 mb-1 text-center"><?php echo htmlspecialchars($associate['name']); ?></h5>
                                    <p class="text-xs text-gray-600 mb-2 text-center"><?php echo htmlspecialchars($associate['post_name'] ?: 'Associate'); ?></p>
                                    <div class="text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo getRoleBadgeStyle($associate['post_name']); ?>">
                                            <?php echo htmlspecialchars($associate['currect_status'] ?: 'Offline'); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Empty State -->
                        <?php if (count($dept['senior_associates']) == 0 && count($dept['associates']) == 0): ?>
                        <div class="text-center py-12 bg-gray-50 rounded-xl">
                            <i class="fas fa-user-plus text-5xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No team members assigned yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Grid View -->
            <div x-show="viewMode === 'grid'" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <!-- CEO Card in Grid -->
                <?php if ($ceo): ?>
                <div class="employee-card bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
                    <div class="flex items-center space-x-4">
                        <img src="<?php echo !empty($ceo['img']) ? htmlspecialchars($ceo['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($ceo['name']) . '&background=8b5cf6&color=ffffff&size=80'; ?>" 
                             alt="<?php echo htmlspecialchars($ceo['name']); ?>" 
                             class="w-20 h-20 rounded-full border-3 border-white/30 shadow-lg object-cover">
                        <div class="flex-1">
                            <h3 class="font-bold text-lg"><?php echo htmlspecialchars($ceo['name']); ?></h3>
                            <p class="text-white/90 text-sm">CEO</p>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur-sm mt-2">
                                <?php echo htmlspecialchars($ceo['currect_status'] ?: 'Offline'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- All Employees in Grid -->
                <?php foreach ($departments as $dept): ?>
                    <?php if ($dept['manager']): ?>
                    <div class="employee-card bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <img src="<?php echo !empty($dept['manager']['img']) ? htmlspecialchars($dept['manager']['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($dept['manager']['name']) . '&background=3b82f6&color=ffffff&size=80'; ?>" 
                                 alt="<?php echo htmlspecialchars($dept['manager']['name']); ?>" 
                                 class="w-20 h-20 rounded-full border-3 border-white/30 shadow-lg object-cover">
                            <div class="flex-1">
                                <h3 class="font-bold"><?php echo htmlspecialchars($dept['manager']['name']); ?></h3>
                                <p class="text-white/90 text-sm"><?php echo htmlspecialchars($dept['manager']['post_name']); ?></p>
                                <p class="text-white/70 text-xs"><?php echo htmlspecialchars($dept['name']); ?></p>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur-sm mt-2">
                                    <?php echo htmlspecialchars($dept['manager']['currect_status'] ?: 'Offline'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php foreach ($dept['senior_associates'] as $senior): ?>
                    <div class="employee-card bg-gradient-to-br from-emerald-600 to-teal-600 rounded-xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <img src="<?php echo !empty($senior['img']) ? htmlspecialchars($senior['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($senior['name']) . '&background=10b981&color=ffffff&size=80'; ?>" 
                                 alt="<?php echo htmlspecialchars($senior['name']); ?>" 
                                 class="w-20 h-20 rounded-full border-3 border-white/30 shadow-lg object-cover">
                            <div class="flex-1">
                                <h3 class="font-bold"><?php echo htmlspecialchars($senior['name']); ?></h3>
                                <p class="text-white/90 text-sm"><?php echo htmlspecialchars($senior['post_name']); ?></p>
                                <p class="text-white/70 text-xs"><?php echo htmlspecialchars($dept['name']); ?></p>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur-sm mt-2">
                                    <?php echo htmlspecialchars($senior['currect_status'] ?: 'Offline'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php foreach ($dept['associates'] as $associate): ?>
                    <div class="employee-card bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-all duration-300 <?php echo getStatusColor($associate['currect_status']); ?>">
                        <div class="flex items-center space-x-4">
                            <img src="<?php echo !empty($associate['img']) ? htmlspecialchars($associate['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($associate['name']) . '&background=6366f1&color=ffffff&size=80'; ?>" 
                                 alt="<?php echo htmlspecialchars($associate['name']); ?>" 
                                 class="w-20 h-20 rounded-full shadow-lg object-cover">
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($associate['name']); ?></h3>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($associate['post_name']); ?></p>
                                <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($dept['name']); ?></p>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mt-2 <?php echo getRoleBadgeStyle($associate['post_name']); ?>">
                                    <?php echo htmlspecialchars($associate['currect_status'] ?: 'Offline'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <!-- Statistics Section -->
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <div class="text-4xl font-bold text-indigo-600 mb-2">
                        <?php echo count($departments); ?>
                    </div>
                    <p class="text-gray-600">Departments</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <div class="text-4xl font-bold text-emerald-600 mb-2">
                        <?php 
                        $total = 1; // CEO
                        foreach ($departments as $dept) {
                            $total += $dept['total_members'];
                        }
                        echo $total;
                        ?>
                    </div>
                    <p class="text-gray-600">Total Employees</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">
                        <?php 
                        $active = 0;
                        if ($ceo && $ceo['currect_status'] === 'Available') $active++;
                        foreach ($departments as $dept) {
                            if ($dept['manager'] && $dept['manager']['currect_status'] === 'Available') $active++;
                            foreach ($dept['senior_associates'] as $s) {
                                if ($s['currect_status'] === 'Available') $active++;
                            }
                            foreach ($dept['associates'] as $a) {
                                if ($a['currect_status'] === 'Available') $active++;
                            }
                        }
                        echo $active;
                        ?>
                    </div>
                    <p class="text-gray-600">Currently Active</p>
                </div>
            </div>

            <!-- Legend -->
            <div class="mt-12 bg-white rounded-xl shadow-lg p-6">
                <h4 class="text-lg font-bold text-gray-800 mb-4 text-center">Quick Reference</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 mb-2">
                            <i class="fas fa-crown text-white"></i>
                        </div>
                        <p class="text-sm text-gray-600">CEO</p>
                    </div>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 mb-2">
                            <i class="fas fa-user-tie text-white"></i>
                        </div>
                        <p class="text-sm text-gray-600">Managers</p>
                    </div>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 mb-2">
                            <i class="fas fa-star text-white"></i>
                        </div>
                        <p class="text-sm text-gray-600">Senior Associates</p>
                    </div>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-r from-gray-600 to-gray-700 mb-2">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <p class="text-sm text-gray-600">Associates</p>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-center text-sm font-semibold text-gray-700 mb-3">Status Indicators</p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-sm text-gray-600">Available</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-yellow-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">On Break</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-gray-400 rounded-full"></div>
                            <span class="text-sm text-gray-600">Offline</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-2 z-40 md:hidden">
        <div class="flex justify-around">
            <button @click="viewMode = 'hierarchy'" 
                    :class="viewMode === 'hierarchy' ? 'text-indigo-600' : 'text-gray-400'"
                    class="flex flex-col items-center py-2">
                <i class="fas fa-sitemap text-xl"></i>
                <span class="text-xs mt-1">Hierarchy</span>
            </button>
            <button @click="viewMode = 'grid'" 
                    :class="viewMode === 'grid' ? 'text-indigo-600' : 'text-gray-400'"
                    class="flex flex-col items-center py-2">
                <i class="fas fa-th text-xl"></i>
                <span class="text-xs mt-1">Grid</span>
            </button>
            <button @click="mobileMenuOpen = !mobileMenuOpen" 
                    class="flex flex-col items-center py-2 text-gray-400">
                <i class="fas fa-filter text-xl"></i>
                <span class="text-xs mt-1">Filter</span>
            </button>
        </div>
    </div>

    <script>
        // Enhanced interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth animations on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                    }
                });
            }, {
                threshold: 0.1
            });

            document.querySelectorAll('.dept-section').forEach(section => {
                observer.observe(section);
            });

            // Touch gestures for mobile
            let touchStartX = 0;
            let touchEndX = 0;

            document.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            });

            document.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            });

            function handleSwipe() {
                if (touchEndX < touchStartX - 50) {
                    // Swipe left - could trigger next department
                    console.log('Swipe left');
                }
                if (touchEndX > touchStartX + 50) {
                    // Swipe right - could trigger previous department
                    console.log('Swipe right');
                }
            }

            // Preload images for better performance
            document.querySelectorAll('img').forEach(img => {
                const src = img.src;
                const preloadImg = new Image();
                preloadImg.src = src;
            });
        });
    </script>
</body>
</html>