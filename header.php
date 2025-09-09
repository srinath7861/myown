<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get user information if logged in
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>

<header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-md">
    <nav class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Mobile menu button -->
            <button onclick="toggleSidebar()" 
                    class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-primary hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary transition-colors"
                    aria-label="Toggle menu">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Logo and Title -->
            <div class="flex items-center flex-1 md:flex-none">
                <div class="flex items-center">
                    <i class="fas fa-users text-primary text-2xl mr-3"></i>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">Employee Management</h1>
                        <p class="text-xs text-gray-500 hidden sm:block">Dashboard</p>
                    </div>
                </div>
            </div>

            <!-- Desktop Navigation Items -->
            <div class="hidden md:flex items-center space-x-4">
                <!-- Clock Display -->
                <div id="clock" class="text-sm font-medium text-gray-600 bg-gray-100 px-3 py-1 rounded-md">
                    Loading...
                </div>

                <!-- Notifications -->
                <button class="relative p-2 text-gray-600 hover:text-primary transition-colors" aria-label="Notifications">
                    <i class="fas fa-bell text-lg"></i>
                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                </button>

                <!-- User Menu -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="flex items-center space-x-2 text-sm font-medium text-gray-700 hover:text-primary transition-colors">
                        <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                        <span class="hidden lg:block"><?php echo htmlspecialchars($user_name); ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5"
                         style="display: none;">
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i> Profile
                        </a>
                        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-cog mr-2"></i> Settings
                        </a>
                        <hr class="my-1">
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mobile User Menu -->
            <div class="flex md:hidden items-center space-x-2">
                <!-- Mobile Clock -->
                <div id="clock-mobile" class="text-xs font-medium text-gray-600">
                    00:00
                </div>
                
                <!-- Mobile User Avatar -->
                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
            </div>
        </div>
    </nav>
</header>

<script>
// Update mobile clock
function updateMobileClock() {
    const now = new Date();
    const hours = now.getHours() % 12 || 12;
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const ampm = now.getHours() >= 12 ? 'PM' : 'AM';
    const clockElement = document.getElementById('clock-mobile');
    if (clockElement) {
        clockElement.textContent = `${hours}:${minutes} ${ampm}`;
    }
}

// Initialize mobile clock
if (document.getElementById('clock-mobile')) {
    setInterval(updateMobileClock, 1000);
    updateMobileClock();
}
</script>