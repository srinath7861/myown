<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'employee_management');

// Create database connection
$db = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Set charset to UTF-8
$db->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('America/New_York');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application settings
define('APP_NAME', 'Employee Management System');
define('APP_VERSION', '2.0.1');
define('APP_URL', 'http://localhost/employee-management/');

// Session timeout (30 minutes)
$session_timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// CSRF Token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['u_id']) && !empty($_SESSION['u_id']);
}

// Helper function to check user role
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}
?>