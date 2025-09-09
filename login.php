<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['u_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error_message = 'Your session has expired. Please login again.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'config.php';
    
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        // Query to check user credentials
        $stmt = $db->prepare("SELECT u_id, name, email, password, role, active FROM user WHERE email = ? AND active = 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Verify password (assuming passwords are hashed)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                // Set session variables
                $_SESSION['u_id'] = $user['u_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Update last login
                $update_stmt = $db->prepare("UPDATE user SET last_login = NOW() WHERE u_id = ?");
                $update_stmt->bind_param('i', $user['u_id']);
                $update_stmt->execute();
                
                // Redirect to dashboard
                header("Location: index.php");
                exit();
            } else {
                $error_message = 'Invalid email or password.';
            }
        } else {
            $error_message = 'Invalid email or password.';
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Login to Employee Management System">
    <meta name="theme-color" content="#3498db">
    <title>Login - Employee Management System</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Nunito:wght@400;500;700&display=swap" rel="stylesheet">
    
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
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        h1, h2, h3, h4, h5, h6 { font-family: 'Roboto', system-ui, sans-serif !important; }
        body, div, p { font-family: 'Nunito', system-ui, sans-serif !important; }
        
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        input {
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        .floating-label {
            transition: all 0.3s ease;
        }
        
        .input-group:focus-within .floating-label,
        .input-group input:not(:placeholder-shown) + .floating-label {
            transform: translateY(-1.5rem) scale(0.85);
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="login-container flex items-center justify-center p-4">
        <div class="login-card w-full max-w-md rounded-2xl shadow-2xl p-8 sm:p-10">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-primary rounded-full mb-4">
                    <i class="fas fa-users text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Welcome Back</h1>
                <p class="text-gray-600">Login to Employee Management System</p>
            </div>
            
            <!-- Error/Success Messages -->
            <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md mb-6" role="alert">
                <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md mb-6" role="alert">
                <p class="text-sm"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-6">
                <!-- Email Field -->
                <div class="input-group relative">
                    <input type="email" 
                           name="email" 
                           id="email" 
                           placeholder=" "
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                    <label for="email" class="floating-label absolute left-4 top-3 text-gray-500 pointer-events-none">
                        Email Address
                    </label>
                </div>
                
                <!-- Password Field -->
                <div class="input-group relative">
                    <input type="password" 
                           name="password" 
                           id="password" 
                           placeholder=" "
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                    <label for="password" class="floating-label absolute left-4 top-3 text-gray-500 pointer-events-none">
                        Password
                    </label>
                    <button type="button" 
                            onclick="togglePassword()" 
                            class="absolute right-4 top-3.5 text-gray-500 hover:text-gray-700 focus:outline-none">
                        <i id="passwordIcon" class="fas fa-eye"></i>
                    </button>
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="forgot-password.php" class="text-sm text-primary hover:underline">
                        Forgot password?
                    </a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full py-3 px-4 bg-primary text-white font-semibold rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </button>
            </form>
            
            <!-- Demo Credentials (Remove in production) -->
            <div class="mt-8 p-4 bg-gray-100 rounded-lg">
                <p class="text-xs text-gray-600 mb-2 font-semibold">Demo Credentials:</p>
                <p class="text-xs text-gray-600">Email: admin@example.com | Password: admin123</p>
                <p class="text-xs text-gray-600">Email: user@example.com | Password: user123</p>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
        
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>