<?php
// team_structure_api.php - API for fetching team structure data
header('Content-Type: application/json');

// Include database connection
require_once 'db_connection.php'; // Adjust path as needed

// Function to get team hierarchy
function getTeamStructure($db) {
    $structure = [
        'ceo' => [],
        'managers' => [],
        'employees' => [],
        'departments' => []
    ];
    
    // Get all departments
    $dept_query = $db->query("SELECT d_id, name FROM department WHERE active = 1 ORDER BY name");
    while ($dept = $dept_query->fetch_assoc()) {
        $structure['departments'][$dept['d_id']] = $dept['name'];
    }
    
    // Get all active users with their positions and departments
    $query = $db->query("
        SELECT 
            u.u_id,
            u.name,
            u.email,
            u.img,
            u.employee_id,
            u.mobile,
            u.currect_status,
            u.last_login,
            p.name as position,
            p.p_id as position_id,
            d.name as department,
            d.d_id as department_id,
            CASE 
                WHEN LOWER(p.name) LIKE '%ceo%' OR LOWER(p.name) LIKE '%chief executive%' THEN 1
                WHEN LOWER(p.name) LIKE '%cto%' OR LOWER(p.name) LIKE '%chief technology%' THEN 2
                WHEN LOWER(p.name) LIKE '%cfo%' OR LOWER(p.name) LIKE '%chief financial%' THEN 2
                WHEN LOWER(p.name) LIKE '%coo%' OR LOWER(p.name) LIKE '%chief operating%' THEN 2
                WHEN LOWER(p.name) LIKE '%director%' THEN 3
                WHEN LOWER(p.name) LIKE '%manager%' OR LOWER(p.name) LIKE '%head%' OR LOWER(p.name) LIKE '%lead%' THEN 4
                WHEN LOWER(p.name) LIKE '%supervisor%' OR LOWER(p.name) LIKE '%coordinator%' THEN 5
                ELSE 6
            END as hierarchy_level,
            CASE 
                WHEN LOWER(p.name) LIKE '%ceo%' OR LOWER(p.name) LIKE '%chief%' THEN 'executive'
                WHEN LOWER(p.name) LIKE '%director%' OR LOWER(p.name) LIKE '%manager%' OR LOWER(p.name) LIKE '%head%' OR LOWER(p.name) LIKE '%lead%' THEN 'manager'
                WHEN LOWER(p.name) LIKE '%supervisor%' OR LOWER(p.name) LIKE '%coordinator%' THEN 'supervisor'
                ELSE 'employee'
            END as role_type
        FROM user u
        LEFT JOIN post p ON u.post_id = p.p_id
        LEFT JOIN department d ON u.dpar_id = d.d_id
        WHERE u.active = 1
        ORDER BY hierarchy_level, d.d_id, u.name
    ");
    
    while ($row = $query->fetch_assoc()) {
        // Create member object
        $member = [
            'id' => $row['u_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'img' => $row['img'] ?: generateAvatar($row['name']),
            'employee_id' => $row['employee_id'],
            'mobile' => $row['mobile'],
            'position' => $row['position'] ?: 'Team Member',
            'position_id' => $row['position_id'],
            'department' => $row['department'] ?: 'General',
            'department_id' => $row['department_id'] ?: 0,
            'status' => $row['currect_status'] ?: 'Offline',
            'last_login' => $row['last_login'],
            'hierarchy_level' => $row['hierarchy_level'],
            'role_type' => $row['role_type']
        ];
        
        // Categorize based on role type
        switch ($row['role_type']) {
            case 'executive':
                if ($row['hierarchy_level'] == 1) {
                    $structure['ceo'][] = $member;
                } else {
                    // C-level executives but not CEO
                    if (!isset($structure['managers'][$row['department_id']])) {
                        $structure['managers'][$row['department_id']] = [];
                    }
                    $structure['managers'][$row['department_id']][] = $member;
                }
                break;
                
            case 'manager':
            case 'supervisor':
                if (!isset($structure['managers'][$row['department_id']])) {
                    $structure['managers'][$row['department_id']] = [];
                }
                $structure['managers'][$row['department_id']][] = $member;
                break;
                
            default:
                if (!isset($structure['employees'][$row['department_id']])) {
                    $structure['employees'][$row['department_id']] = [];
                }
                $structure['employees'][$row['department_id']][] = $member;
                break;
        }
    }
    
    // Get reporting relationships if available
    $structure['reporting'] = getReportingRelationships($db);
    
    // Get department statistics
    $structure['stats'] = getDepartmentStats($db);
    
    return $structure;
}

// Function to generate avatar URL
function generateAvatar($name) {
    $colors = ['3498db', '2ecc71', 'e74c3c', 'f39c12', '9b59b6', '1abc9c', '34495e'];
    $color = $colors[array_rand($colors)];
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=' . $color . '&color=fff&size=200';
}

// Function to get reporting relationships (if you have a reporting structure table)
function getReportingRelationships($db) {
    $relationships = [];
    
    // This assumes you might have a table that tracks who reports to whom
    // Adjust based on your actual database structure
    $query = "
        SELECT 
            u1.u_id as employee_id,
            u1.name as employee_name,
            u2.u_id as manager_id,
            u2.name as manager_name
        FROM user u1
        LEFT JOIN user u2 ON u1.manager_id = u2.u_id
        WHERE u1.active = 1 AND u1.manager_id IS NOT NULL
    ";
    
    // Check if manager_id column exists
    $columns = $db->query("SHOW COLUMNS FROM user LIKE 'manager_id'");
    if ($columns && $columns->num_rows > 0) {
        $result = $db->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $relationships[] = [
                    'employee_id' => $row['employee_id'],
                    'employee_name' => $row['employee_name'],
                    'manager_id' => $row['manager_id'],
                    'manager_name' => $row['manager_name']
                ];
            }
        }
    }
    
    return $relationships;
}

// Function to get department statistics
function getDepartmentStats($db) {
    $stats = [];
    
    $query = $db->query("
        SELECT 
            d.d_id,
            d.name as department_name,
            COUNT(u.u_id) as total_employees,
            SUM(CASE WHEN u.currect_status = 'Available' THEN 1 ELSE 0 END) as available_count,
            SUM(CASE WHEN u.currect_status IN ('Break 1', 'Break 2', 'Lunch Break') THEN 1 ELSE 0 END) as on_break_count,
            SUM(CASE WHEN u.currect_status = 'Offline' OR u.currect_status IS NULL THEN 1 ELSE 0 END) as offline_count
        FROM department d
        LEFT JOIN user u ON d.d_id = u.dpar_id AND u.active = 1
        WHERE d.active = 1
        GROUP BY d.d_id, d.name
        ORDER BY d.name
    ");
    
    while ($row = $query->fetch_assoc()) {
        $stats[$row['d_id']] = [
            'department_name' => $row['department_name'],
            'total_employees' => (int)$row['total_employees'],
            'available_count' => (int)$row['available_count'],
            'on_break_count' => (int)$row['on_break_count'],
            'offline_count' => (int)$row['offline_count'],
            'availability_rate' => $row['total_employees'] > 0 ? 
                round(($row['available_count'] / $row['total_employees']) * 100, 1) : 0
        ];
    }
    
    return $stats;
}

// Main API handler
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $teamStructure = getTeamStructure($db);
        
        // Add summary statistics
        $totalEmployees = 0;
        $totalManagers = 0;
        $totalDepartments = count($teamStructure['departments']);
        
        foreach ($teamStructure['managers'] as $dept_managers) {
            $totalManagers += count($dept_managers);
        }
        
        foreach ($teamStructure['employees'] as $dept_employees) {
            $totalEmployees += count($dept_employees);
        }
        
        $teamStructure['summary'] = [
            'total_executives' => count($teamStructure['ceo']),
            'total_managers' => $totalManagers,
            'total_employees' => $totalEmployees,
            'total_departments' => $totalDepartments,
            'total_staff' => count($teamStructure['ceo']) + $totalManagers + $totalEmployees
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $teamStructure
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch team structure: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}
?>