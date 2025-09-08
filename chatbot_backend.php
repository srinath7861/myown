<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuration
define('DATA_FILE', __DIR__ . '/chatbot_data.json');
define('BACKUP_DIR', __DIR__ . '/backups/');

// Create backup directory if it doesn't exist
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

// Initialize data file if it doesn't exist
if (!file_exists(DATA_FILE)) {
    $initialData = [
        'users' => [],
        'conversations' => [],
        'metadata' => [
            'created_at' => date('Y-m-d H:i:s'),
            'last_updated' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ]
    ];
    file_put_contents(DATA_FILE, json_encode($initialData, JSON_PRETTY_PRINT));
}

// Function to read data
function readData() {
    $data = file_get_contents(DATA_FILE);
    return json_decode($data, true);
}

// Function to write data
function writeData($data) {
    // Create backup before writing
    createBackup();
    
    $data['metadata']['last_updated'] = date('Y-m-d H:i:s');
    return file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// Function to create backup
function createBackup() {
    if (file_exists(DATA_FILE)) {
        $backupFile = BACKUP_DIR . 'backup_' . date('Y-m-d_H-i-s') . '.json';
        copy(DATA_FILE, $backupFile);
        
        // Keep only last 10 backups
        $backups = glob(BACKUP_DIR . 'backup_*.json');
        if (count($backups) > 10) {
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            $toDelete = array_slice($backups, 0, count($backups) - 10);
            foreach ($toDelete as $file) {
                unlink($file);
            }
        }
    }
}

// Function to generate unique ID
function generateId() {
    return uniqid('user_', true);
}

// Function to generate conversation ID
function generateConversationId() {
    return uniqid('conv_', true);
}

// Function to find user by email and name
function findUser($email, $name = null) {
    $data = readData();
    foreach ($data['users'] as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            // If name is provided, check if it matches
            if ($name !== null) {
                // Check if names are similar (case-insensitive)
                if (strtolower($user['name']) === strtolower($name)) {
                    return $user;
                }
            } else {
                return $user;
            }
        }
    }
    return null;
}

// Function to create or update user
function saveUser($name, $email, $mobile) {
    $data = readData();
    
    // Check if user exists
    $existingUser = findUser($email, $name);
    
    if ($existingUser) {
        // Update existing user
        $userId = $existingUser['id'];
        foreach ($data['users'] as &$user) {
            if ($user['id'] === $userId) {
                $user['mobile'] = $mobile;
                $user['last_login'] = date('Y-m-d H:i:s');
                $user['login_count'] = ($user['login_count'] ?? 0) + 1;
                break;
            }
        }
    } else {
        // Create new user
        $userId = generateId();
        $newUser = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => date('Y-m-d H:i:s'),
            'login_count' => 1
        ];
        $data['users'][] = $newUser;
    }
    
    writeData($data);
    return $userId;
}

// Function to save message
function saveMessage($userId, $message, $sender) {
    $data = readData();
    
    // Find or create conversation for user
    $conversationId = null;
    $today = date('Y-m-d');
    
    // Look for today's conversation for this user
    foreach ($data['conversations'] as &$conv) {
        if ($conv['user_id'] === $userId && 
            isset($conv['date']) && 
            $conv['date'] === $today &&
            !isset($conv['ended'])) {
            $conversationId = $conv['id'];
            break;
        }
    }
    
    // Create new conversation if needed
    if (!$conversationId) {
        $conversationId = generateConversationId();
        $newConversation = [
            'id' => $conversationId,
            'user_id' => $userId,
            'date' => $today,
            'started_at' => date('Y-m-d H:i:s'),
            'messages' => []
        ];
        $data['conversations'][] = $newConversation;
    }
    
    // Add message to conversation
    $newMessage = [
        'id' => uniqid('msg_', true),
        'sender' => $sender,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    foreach ($data['conversations'] as &$conv) {
        if ($conv['id'] === $conversationId) {
            $conv['messages'][] = $newMessage;
            $conv['last_message_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    writeData($data);
    return true;
}

// Function to get user conversations
function getUserConversations($userId, $limit = null) {
    $data = readData();
    $userConversations = [];
    
    foreach ($data['conversations'] as $conv) {
        if ($conv['user_id'] === $userId) {
            $userConversations[] = $conv;
        }
    }
    
    // Sort by date (most recent first)
    usort($userConversations, function($a, $b) {
        return strtotime($b['started_at']) - strtotime($a['started_at']);
    });
    
    if ($limit !== null) {
        $userConversations = array_slice($userConversations, 0, $limit);
    }
    
    return $userConversations;
}

// Function to get conversation history
function getConversationHistory($userId) {
    $conversations = getUserConversations($userId);
    $allMessages = [];
    
    foreach ($conversations as $conv) {
        foreach ($conv['messages'] as $msg) {
            $allMessages[] = [
                'sender' => $msg['sender'],
                'message' => $msg['message'],
                'timestamp' => $msg['timestamp'],
                'conversation_date' => $conv['date']
            ];
        }
    }
    
    // Sort by timestamp
    usort($allMessages, function($a, $b) {
        return strtotime($a['timestamp']) - strtotime($b['timestamp']);
    });
    
    return $allMessages;
}

// Function to end conversation
function endConversation($userId) {
    $data = readData();
    $today = date('Y-m-d');
    
    foreach ($data['conversations'] as &$conv) {
        if ($conv['user_id'] === $userId && 
            isset($conv['date']) && 
            $conv['date'] === $today &&
            !isset($conv['ended'])) {
            $conv['ended'] = true;
            $conv['ended_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    writeData($data);
    return true;
}

// Function to get statistics
function getStatistics() {
    $data = readData();
    
    $stats = [
        'total_users' => count($data['users']),
        'total_conversations' => count($data['conversations']),
        'total_messages' => 0,
        'active_today' => 0,
        'active_this_week' => 0,
        'active_this_month' => 0
    ];
    
    $today = date('Y-m-d');
    $weekAgo = date('Y-m-d', strtotime('-7 days'));
    $monthAgo = date('Y-m-d', strtotime('-30 days'));
    
    foreach ($data['conversations'] as $conv) {
        $stats['total_messages'] += count($conv['messages']);
        
        $convDate = $conv['date'];
        if ($convDate === $today) {
            $stats['active_today']++;
        }
        if ($convDate >= $weekAgo) {
            $stats['active_this_week']++;
        }
        if ($convDate >= $monthAgo) {
            $stats['active_this_month']++;
        }
    }
    
    return $stats;
}

// Main request handler
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'save_user':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $name = $input['name'] ?? '';
            $email = $input['email'] ?? '';
            $mobile = $input['mobile'] ?? '';
            
            if (empty($name) || empty($email) || empty($mobile)) {
                throw new Exception('Missing required fields');
            }
            
            $userId = saveUser($name, $email, $mobile);
            $response = [
                'success' => true,
                'user_id' => $userId,
                'message' => 'User saved successfully'
            ];
            break;
            
        case 'check_user':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $email = $input['email'] ?? '';
            $name = $input['name'] ?? null;
            
            if (empty($email)) {
                throw new Exception('Email is required');
            }
            
            $user = findUser($email, $name);
            if ($user) {
                $response = [
                    'success' => true,
                    'exists' => true,
                    'user' => $user
                ];
            } else {
                $response = [
                    'success' => true,
                    'exists' => false
                ];
            }
            break;
            
        case 'save_message':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $userId = $input['user_id'] ?? '';
            $message = $input['message'] ?? '';
            $sender = $input['sender'] ?? '';
            
            if (empty($userId) || empty($message) || empty($sender)) {
                throw new Exception('Missing required fields');
            }
            
            saveMessage($userId, $message, $sender);
            $response = [
                'success' => true,
                'message' => 'Message saved successfully'
            ];
            break;
            
        case 'get_history':
            $userId = $input['user_id'] ?? $_GET['user_id'] ?? '';
            
            if (empty($userId)) {
                throw new Exception('User ID is required');
            }
            
            $history = getConversationHistory($userId);
            $response = [
                'success' => true,
                'history' => $history,
                'count' => count($history)
            ];
            break;
            
        case 'get_conversations':
            $userId = $input['user_id'] ?? $_GET['user_id'] ?? '';
            $limit = $input['limit'] ?? $_GET['limit'] ?? null;
            
            if (empty($userId)) {
                throw new Exception('User ID is required');
            }
            
            $conversations = getUserConversations($userId, $limit);
            $response = [
                'success' => true,
                'conversations' => $conversations,
                'count' => count($conversations)
            ];
            break;
            
        case 'end_conversation':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $userId = $input['user_id'] ?? '';
            
            if (empty($userId)) {
                throw new Exception('User ID is required');
            }
            
            endConversation($userId);
            $response = [
                'success' => true,
                'message' => 'Conversation ended successfully'
            ];
            break;
            
        case 'get_statistics':
            $stats = getStatistics();
            $response = [
                'success' => true,
                'statistics' => $stats
            ];
            break;
            
        case 'export_data':
            $data = readData();
            $response = [
                'success' => true,
                'data' => $data,
                'export_date' => date('Y-m-d H:i:s')
            ];
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>