<?php
// Simple authentication (you should implement proper authentication in production)
session_start();

$ADMIN_PASSWORD = 'admin123'; // Change this!

if (isset($_POST['password'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        $error = "Invalid password";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_dashboard.php');
    exit;
}

$isAuthenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'];

// If authenticated, load data
$data = null;
$stats = null;
if ($isAuthenticated && file_exists('chatbot_data.json')) {
    $data = json_decode(file_get_contents('chatbot_data.json'), true);
    
    // Calculate statistics
    $stats = [
        'total_users' => count($data['users'] ?? []),
        'total_conversations' => count($data['conversations'] ?? []),
        'total_messages' => 0,
        'active_today' => 0,
        'active_this_week' => 0
    ];
    
    $today = date('Y-m-d');
    $weekAgo = date('Y-m-d', strtotime('-7 days'));
    
    foreach ($data['conversations'] ?? [] as $conv) {
        $stats['total_messages'] += count($conv['messages'] ?? []);
        
        if (isset($conv['date'])) {
            if ($conv['date'] === $today) {
                $stats['active_today']++;
            }
            if ($conv['date'] >= $weekAgo) {
                $stats['active_this_week']++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adriana Chatbot - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .login-container h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .error {
            color: #dc3545;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
        }
        
        .header .actions {
            display: flex;
            gap: 15px;
        }
        
        .header .btn-small {
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .header .btn-small:hover {
            background: #5a6268;
        }
        
        .header .btn-export {
            background: #28a745;
        }
        
        .header .btn-export:hover {
            background: #218838;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .stat-card .value {
            color: #333;
            font-size: 32px;
            font-weight: bold;
        }
        
        .data-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .data-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .users-table, .conversations-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th, .conversations-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #dee2e6;
        }
        
        .users-table td, .conversations-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .users-table tr:hover, .conversations-table tr:hover {
            background: #f8f9fa;
        }
        
        .conversation-messages {
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            background: white;
        }
        
        .message.user {
            background: linear-gradient(135deg, #d0b560, #ffeaa9);
            margin-left: 20%;
        }
        
        .message.bot {
            background: white;
            border: 1px solid #e9ecef;
            margin-right: 20%;
        }
        
        .message-header {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .message-content {
            color: #333;
            line-height: 1.4;
        }
        
        .no-data {
            text-align: center;
            color: #999;
            padding: 40px;
            font-size: 18px;
        }
        
        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .search-box button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .search-box button:hover {
            background: #0056b3;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 20px;
            }
            
            .header .actions {
                width: 100%;
                justify-content: center;
            }
            
            .users-table, .conversations-table {
                font-size: 14px;
            }
            
            .message.user {
                margin-left: 0;
            }
            
            .message.bot {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$isAuthenticated): ?>
            <div class="login-container">
                <h1>🔐 Admin Login</h1>
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="password">Admin Password</label>
                        <input type="password" id="password" name="password" required autofocus>
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>
            </div>
        <?php else: ?>
            <div class="header">
                <h1>📊 Adriana Chatbot Dashboard</h1>
                <div class="actions">
                    <button class="btn-small btn-export" onclick="exportData()">📥 Export Data</button>
                    <button class="btn-small" onclick="refreshData()">🔄 Refresh</button>
                    <a href="?logout=1" class="btn-small">🚪 Logout</a>
                </div>
            </div>
            
            <?php if ($data): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <div class="value"><?php echo $stats['total_users']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Conversations</h3>
                        <div class="value"><?php echo $stats['total_conversations']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Messages</h3>
                        <div class="value"><?php echo $stats['total_messages']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Active Today</h3>
                        <div class="value"><?php echo $stats['active_today']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Active This Week</h3>
                        <div class="value"><?php echo $stats['active_this_week']; ?></div>
                    </div>
                </div>
                
                <div class="data-section">
                    <h2>👥 Registered Users</h2>
                    <div class="search-box">
                        <input type="text" id="userSearch" placeholder="Search users by name or email...">
                        <button onclick="searchUsers()">Search</button>
                    </div>
                    <?php if (!empty($data['users'])): ?>
                        <table class="users-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Registered</th>
                                    <th>Last Login</th>
                                    <th>Login Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['users'] as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?></td>
                                        <td><?php echo $user['login_count'] ?? 1; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">No users registered yet</div>
                    <?php endif; ?>
                </div>
                
                <div class="data-section">
                    <h2>💬 Recent Conversations</h2>
                    <div class="search-box">
                        <input type="text" id="convSearch" placeholder="Search conversations...">
                        <button onclick="searchConversations()">Search</button>
                    </div>
                    <?php if (!empty($data['conversations'])): ?>
                        <?php 
                        // Sort conversations by date (most recent first)
                        usort($data['conversations'], function($a, $b) {
                            return strtotime($b['started_at'] ?? '0') - strtotime($a['started_at'] ?? '0');
                        });
                        
                        // Get user map for quick lookup
                        $userMap = [];
                        foreach ($data['users'] as $user) {
                            $userMap[$user['id']] = $user;
                        }
                        
                        // Show last 10 conversations
                        $recentConversations = array_slice($data['conversations'], 0, 10);
                        ?>
                        
                        <?php foreach ($recentConversations as $conv): ?>
                            <?php 
                            $user = $userMap[$conv['user_id']] ?? null;
                            if (!$user) continue;
                            ?>
                            <div class="conversation-item" style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <span style="color: #666; margin-left: 10px;"><?php echo htmlspecialchars($user['email']); ?></span>
                                    </div>
                                    <div style="color: #999; font-size: 14px;">
                                        <?php echo date('M d, Y H:i', strtotime($conv['started_at'])); ?>
                                        <?php if (isset($conv['ended'])): ?>
                                            <span style="color: #dc3545; margin-left: 10px;">• Ended</span>
                                        <?php else: ?>
                                            <span style="color: #28a745; margin-left: 10px;">• Active</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="conversation-messages">
                                    <?php foreach ($conv['messages'] as $msg): ?>
                                        <div class="message <?php echo $msg['sender']; ?>">
                                            <div class="message-header">
                                                <?php echo $msg['sender'] === 'user' ? 'User' : 'Adriana'; ?> • 
                                                <?php echo date('H:i', strtotime($msg['timestamp'])); ?>
                                            </div>
                                            <div class="message-content">
                                                <?php echo strip_tags($msg['message'], '<br><strong><em>'); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">No conversations yet</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="data-section">
                    <div class="no-data">No data available. The chatbot hasn't been used yet.</div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script>
        function exportData() {
            fetch('chatbot_backend.php?action=export_data')
                .then(response => response.json())
                .then(data => {
                    const dataStr = JSON.stringify(data.data, null, 2);
                    const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
                    const exportFileDefaultName = `chatbot-data-${new Date().toISOString().split('T')[0]}.json`;
                    
                    const linkElement = document.createElement('a');
                    linkElement.setAttribute('href', dataUri);
                    linkElement.setAttribute('download', exportFileDefaultName);
                    linkElement.click();
                })
                .catch(error => {
                    alert('Error exporting data: ' + error.message);
                });
        }
        
        function refreshData() {
            location.reload();
        }
        
        function searchUsers() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }
        
        function searchConversations() {
            const searchTerm = document.getElementById('convSearch').value.toLowerCase();
            const conversations = document.getElementsByClassName('conversation-item');
            
            for (let conv of conversations) {
                const text = conv.textContent.toLowerCase();
                conv.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        }
        
        // Auto-refresh every 30 seconds when on dashboard
        <?php if ($isAuthenticated): ?>
        setInterval(() => {
            // Only refresh if user is not actively searching
            if (!document.getElementById('userSearch').value && !document.getElementById('convSearch').value) {
                refreshData();
            }
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>