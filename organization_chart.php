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
    if ($status === 'Available') return 'bg-green-500';
    if (in_array($status, ['Break 1', 'Break 2', 'Lunch Break', 'Personal Time'])) return 'bg-yellow-500';
    return 'bg-gray-400';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Company organizational chart">
    <title>Organization Tree | Company Hierarchy</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

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
            overflow-x: auto;
        }

        /* Main container that takes exactly 100vh */
        .tree-container {
            height: 100vh;
            width: 100%;
            min-width: 100vw;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: auto;
        }

        /* Tree wrapper with scrolling if needed */
        .tree-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            position: relative;
            min-width: max-content;
            margin: 0 auto;
        }

        /* Profile circles */
        .profile-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            cursor: pointer;
            transition: transform 0.3s ease;
            background-size: cover;
            background-position: center;
        }

        .profile-circle:hover {
            transform: scale(1.1);
            z-index: 10;
        }

        .profile-circle.ceo {
            width: 80px;
            height: 80px;
            border: 4px solid #fbbf24;
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
        }

        .profile-circle.manager {
            width: 70px;
            height: 70px;
            border: 3px solid #60a5fa;
        }

        /* Status indicator */
        .status-dot {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid white;
        }

        /* Name labels */
        .name-label {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 5px;
            background: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            color: #1f2937;
            z-index: 5;
        }

        .role-label {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: 600;
            white-space: nowrap;
            color: #6366f1;
        }

        /* Tree lines using SVG */
        .tree-lines {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .tree-lines svg {
            width: 100%;
            height: 100%;
        }

        .tree-line {
            stroke: rgba(255, 255, 255, 0.4);
            stroke-width: 2;
            fill: none;
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
            margin-bottom: 60px;
        }

        .managers-level {
            margin-bottom: 60px;
            gap: 80px;
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
            gap: 30px;
            margin-top: 60px;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 300px;
        }

        .member-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Department label */
        .dept-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
            color: #4f46e5;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-circle {
                width: 45px;
                height: 45px;
            }

            .profile-circle.ceo {
                width: 60px;
                height: 60px;
            }

            .profile-circle.manager {
                width: 50px;
                height: 50px;
            }

            .name-label {
                font-size: 9px;
                padding: 2px 6px;
            }

            .managers-level {
                gap: 40px;
            }

            .team-members {
                gap: 20px;
            }

            .tree-wrapper {
                padding: 10px;
            }
        }

        /* Tooltip on hover */
        .tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 20;
            margin-bottom: 8px;
        }

        .profile-circle:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }

        /* Header bar */
        .header-bar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-title {
            color: white;
            font-size: 18px;
            font-weight: 600;
        }

        .legend {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: white;
            font-size: 12px;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        /* Connecting lines styles */
        .vertical-line {
            position: absolute;
            width: 2px;
            background: rgba(255, 255, 255, 0.3);
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
        }

        .horizontal-line {
            position: absolute;
            height: 2px;
            background: rgba(255, 255, 255, 0.3);
            z-index: 0;
        }

        /* Branch indicator for seniors vs associates */
        .senior-indicator {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #10b981;
            color: white;
            padding: 1px 6px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 600;
        }

        .associate-indicator {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #6b7280;
            color: white;
            padding: 1px 6px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 600;
        }
    </style>
</head>

<body x-data="{ showTooltips: false }">
    <div class="tree-container">
        <!-- Header -->
        <div class="header-bar">
            <h1 class="header-title">
                <i class="fas fa-sitemap mr-2"></i>Organization Tree
            </h1>
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
            <div class="level ceo-level">
                <div class="member-wrapper">
                    <div class="role-label">CEO</div>
                    <div class="profile-circle ceo" 
                         style="background-image: url('<?php echo !empty($ceo['img']) ? htmlspecialchars($ceo['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($ceo['name']) . '&background=fbbf24&color=ffffff&size=80'; ?>')">
                        <div class="status-dot <?php echo getStatusDot($ceo['currect_status']); ?>"></div>
                        <div class="tooltip">
                            <?php echo htmlspecialchars($ceo['post_name'] ?: 'Chief Executive Officer'); ?><br>
                            ID: <?php echo htmlspecialchars($ceo['employee_id']); ?>
                        </div>
                    </div>
                    <div class="name-label"><?php echo htmlspecialchars($ceo['name']); ?></div>
                    
                    <!-- Vertical line to managers -->
                    <?php if (count($departments) > 0): ?>
                    <div class="vertical-line" style="height: 60px; top: 100%;"></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Managers Level with Departments -->
            <?php if (count($departments) > 0): ?>
            <div class="level managers-level">
                <!-- Horizontal line connecting all managers -->
                <?php if (count($departments) > 1): ?>
                <div class="horizontal-line" style="width: calc(100% - 100px); top: -30px; left: 50px;"></div>
                <?php endif; ?>
                
                <?php foreach ($departments as $index => $dept): ?>
                <div class="department-group">
                    <!-- Manager -->
                    <?php if ($dept['manager']): ?>
                    <div class="member-wrapper">
                        <!-- Vertical line from horizontal connector -->
                        <div class="vertical-line" style="height: 30px; top: -30px;"></div>
                        
                        <div class="profile-circle manager" 
                             style="background-image: url('<?php echo !empty($dept['manager']['img']) ? htmlspecialchars($dept['manager']['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($dept['manager']['name']) . '&background=60a5fa&color=ffffff&size=70'; ?>')">
                            <div class="status-dot <?php echo getStatusDot($dept['manager']['currect_status']); ?>"></div>
                            <div class="tooltip">
                                <?php echo htmlspecialchars($dept['manager']['post_name'] ?: 'Manager'); ?><br>
                                ID: <?php echo htmlspecialchars($dept['manager']['employee_id']); ?>
                            </div>
                        </div>
                        <div class="name-label"><?php echo htmlspecialchars($dept['manager']['name']); ?></div>
                        <div class="dept-label"><?php echo htmlspecialchars($dept['name']); ?></div>
                        
                        <!-- Vertical line to team members -->
                        <?php if (count($dept['senior_associates']) > 0 || count($dept['associates']) > 0): ?>
                        <div class="vertical-line" style="height: 60px; top: 100%;"></div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="member-wrapper">
                        <div class="vertical-line" style="height: 30px; top: -30px;"></div>
                        <div class="profile-circle manager" style="background: #e5e7eb;">
                            <i class="fas fa-user-plus" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #9ca3af;"></i>
                        </div>
                        <div class="name-label">Vacant</div>
                        <div class="dept-label"><?php echo htmlspecialchars($dept['name']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Team Members (Seniors and Associates) -->
                    <?php if (count($dept['senior_associates']) > 0 || count($dept['associates']) > 0): ?>
                    <div class="team-members">
                        <!-- Horizontal line for team members -->
                        <?php 
                        $totalMembers = count($dept['senior_associates']) + count($dept['associates']);
                        if ($totalMembers > 1): 
                        ?>
                        <div class="horizontal-line" style="width: calc(100% - 30px); top: -30px; left: 15px;"></div>
                        <?php endif; ?>
                        
                        <!-- Senior Associates -->
                        <?php foreach ($dept['senior_associates'] as $senior): ?>
                        <div class="member-wrapper">
                            <div class="vertical-line" style="height: 30px; top: -30px;"></div>
                            <div class="senior-indicator">SR</div>
                            <div class="profile-circle" 
                                 style="background-image: url('<?php echo !empty($senior['img']) ? htmlspecialchars($senior['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($senior['name']) . '&background=10b981&color=ffffff&size=60'; ?>')">
                                <div class="status-dot <?php echo getStatusDot($senior['currect_status']); ?>"></div>
                                <div class="tooltip">
                                    <?php echo htmlspecialchars($senior['post_name'] ?: 'Senior Associate'); ?><br>
                                    ID: <?php echo htmlspecialchars($senior['employee_id']); ?>
                                </div>
                            </div>
                            <div class="name-label"><?php echo htmlspecialchars(explode(' ', $senior['name'])[0]); ?></div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Associates -->
                        <?php foreach ($dept['associates'] as $associate): ?>
                        <div class="member-wrapper">
                            <div class="vertical-line" style="height: 30px; top: -30px;"></div>
                            <div class="profile-circle" 
                                 style="background-image: url('<?php echo !empty($associate['img']) ? htmlspecialchars($associate['img']) : 'https://ui-avatars.com/api/?name=' . urlencode($associate['name']) . '&background=6b7280&color=ffffff&size=60'; ?>')">
                                <div class="status-dot <?php echo getStatusDot($associate['currect_status']); ?>"></div>
                                <div class="tooltip">
                                    <?php echo htmlspecialchars($associate['post_name'] ?: 'Associate'); ?><br>
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
        </div>
    </div>

    <script>
        // Add interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add click to view details
            document.querySelectorAll('.profile-circle').forEach(circle => {
                circle.addEventListener('click', function() {
                    // Could open a modal or side panel with employee details
                    const name = this.nextElementSibling?.textContent || 'Unknown';
                    console.log('Clicked on:', name);
                });
            });

            // Smooth scroll for overflow
            const treeWrapper = document.querySelector('.tree-wrapper');
            let isDown = false;
            let startX;
            let scrollLeft;

            treeWrapper.addEventListener('mousedown', (e) => {
                // Only activate if clicking on empty space, not on profile circles
                if (e.target === treeWrapper || e.target.classList.contains('level')) {
                    isDown = true;
                    treeWrapper.style.cursor = 'grabbing';
                    startX = e.pageX - treeWrapper.offsetLeft;
                    scrollLeft = treeWrapper.scrollLeft;
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
                const walk = (x - startX) * 2;
                treeWrapper.scrollLeft = scrollLeft - walk;
            });

            // Touch support for mobile
            let touchStartX = 0;
            let touchScrollLeft = 0;

            treeWrapper.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].pageX;
                touchScrollLeft = treeWrapper.scrollLeft;
            });

            treeWrapper.addEventListener('touchmove', (e) => {
                const touchX = e.touches[0].pageX;
                const walk = (touchStartX - touchX) * 2;
                treeWrapper.scrollLeft = touchScrollLeft + walk;
            });
        });
    </script>
</body>
</html>