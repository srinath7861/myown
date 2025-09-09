<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Check user role for menu permissions
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$is_admin = ($user_role === 'admin' || $user_role === 'Admin');
$is_manager = ($user_role === 'manager' || $user_role === 'Manager');
?>

<aside class="sidebar bg-white">
    <div class="h-full flex flex-col">
        <!-- Mobile Header -->
        <div class="md:hidden flex items-center justify-between p-4 border-b">
            <div class="flex items-center">
                <i class="fas fa-users text-primary text-xl mr-2"></i>
                <span class="font-bold text-gray-900">Menu</span>
            </div>
            <button onclick="closeSidebar()" 
                    class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
            <!-- Dashboard -->
            <a href="index.php" 
               class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'index.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <i class="fas fa-home mr-3 w-5 text-center"></i>
                <span>Dashboard</span>
            </a>

            <!-- Employee Section -->
            <div class="pt-4">
                <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</h3>
                <div class="mt-2 space-y-1">
                    <a href="attendance.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'attendance.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-clock mr-3 w-5 text-center"></i>
                        <span>Attendance</span>
                    </a>
                    
                    <a href="breaks.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'breaks.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-coffee mr-3 w-5 text-center"></i>
                        <span>Break Management</span>
                    </a>
                    
                    <a href="leave.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'leave.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-calendar-alt mr-3 w-5 text-center"></i>
                        <span>Leave Requests</span>
                    </a>
                    
                    <a href="schedule.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'schedule.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-calendar mr-3 w-5 text-center"></i>
                        <span>Schedule</span>
                    </a>
                </div>
            </div>

            <?php if ($is_manager || $is_admin): ?>
            <!-- Management Section -->
            <div class="pt-4">
                <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Management</h3>
                <div class="mt-2 space-y-1">
                    <a href="team.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'team.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-users mr-3 w-5 text-center"></i>
                        <span>Team Overview</span>
                    </a>
                    
                    <a href="reports.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'reports.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
                        <span>Reports</span>
                    </a>
                    
                    <a href="approvals.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'approvals.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-check-circle mr-3 w-5 text-center"></i>
                        <span>Approvals</span>
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">3</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_admin): ?>
            <!-- Admin Section -->
            <div class="pt-4">
                <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Administration</h3>
                <div class="mt-2 space-y-1">
                    <a href="users.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'users.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-user-cog mr-3 w-5 text-center"></i>
                        <span>User Management</span>
                    </a>
                    
                    <a href="departments.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'departments.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-building mr-3 w-5 text-center"></i>
                        <span>Departments</span>
                    </a>
                    
                    <a href="settings.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'settings.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-cog mr-3 w-5 text-center"></i>
                        <span>System Settings</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Support Section -->
            <div class="pt-4">
                <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Support</h3>
                <div class="mt-2 space-y-1">
                    <a href="help.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'help.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-question-circle mr-3 w-5 text-center"></i>
                        <span>Help & Support</span>
                    </a>
                    
                    <a href="profile.php" 
                       class="sidebar-link flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $current_page === 'profile.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-user mr-3 w-5 text-center"></i>
                        <span>My Profile</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Mobile Footer with Logout -->
        <div class="md:hidden border-t p-4">
            <a href="logout.php" 
               class="flex items-center px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md transition-colors">
                <i class="fas fa-sign-out-alt mr-3 w-5 text-center"></i>
                <span>Logout</span>
            </a>
        </div>

        <!-- Desktop Footer -->
        <div class="hidden md:block border-t p-4">
            <div class="flex items-center text-xs text-gray-500">
                <i class="fas fa-info-circle mr-2"></i>
                <span>Version 2.0.1</span>
            </div>
        </div>
    </div>
</aside>

<style>
/* Sidebar link hover effects */
.sidebar-link {
    position: relative;
    overflow: hidden;
}

.sidebar-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.sidebar-link:hover::before {
    left: 100%;
}

/* Mobile-specific styles */
@media (max-width: 767px) {
    .sidebar {
        width: 280px;
        max-width: 85vw;
    }
}

/* Smooth scrolling for sidebar */
.sidebar nav {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
}

.sidebar nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar nav::-webkit-scrollbar-track {
    background: #f7fafc;
}

.sidebar nav::-webkit-scrollbar-thumb {
    background-color: #cbd5e0;
    border-radius: 3px;
}

.sidebar nav::-webkit-scrollbar-thumb:hover {
    background-color: #a0aec0;
}
</style>