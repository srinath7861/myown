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
$dataFile = 'chatbot_data.json';

// Utility function to read data from JSON file
function readDataFile($file) {
    if (!file_exists($file)) {
        return ['users' => [], 'conversations' => []];
    }
    
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If JSON is corrupted, create new structure
        return ['users' => [], 'conversations' => []];
    }
    
    return $data;
}

// Utility function to write data to JSON file
function writeDataFile($file, $data) {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($file, $jsonData, LOCK_EX) !== false;
}

// Utility function to find user by email and name
function findUser($users, $email, $name) {
    foreach ($users as $index => $user) {
        if (strtolower($user['email']) === strtolower($email) && 
            strtolower($user['name']) === strtolower($name)) {
            return ['user' => $user, 'index' => $index];
        }
    }
    return null;
}

// Main request handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    $data = readDataFile($dataFile);
    $action = $input['action'];
    
    switch ($action) {
        case 'save_user':
            if (!isset($input['name']) || !isset($input['email']) || !isset($input['mobile'])) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            $name = trim($input['name']);
            $email = trim($input['email']);
            $mobile = trim($input['mobile']);
            
            // Check if user already exists
            $existingUser = findUser($data['users'], $email, $name);
            
            if ($existingUser) {
                // Update existing user
                $userId = $existingUser['user']['id'];
                $data['users'][$existingUser['index']]['mobile'] = $mobile;
                $data['users'][$existingUser['index']]['last_login'] = date('Y-m-d H:i:s');
            } else {
                // Create new user
                $userId = uniqid('user_', true);
                $newUser = [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'mobile' => $mobile,
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_login' => date('Y-m-d H:i:s')
                ];
                $data['users'][] = $newUser;
            }
            
            if (writeDataFile($dataFile, $data)) {
                echo json_encode(['success' => true, 'user_id' => $userId, 'is_returning' => $existingUser !== null]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save user data']);
            }
            break;
            
        case 'save_message':
            if (!isset($input['user_id']) || !isset($input['message']) || !isset($input['sender'])) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            $userId = $input['user_id'];
            $message = $input['message'];
            $sender = $input['sender']; // 'user' or 'bot'
            
            // Find user's conversation or create new one
            $conversationIndex = -1;
            foreach ($data['conversations'] as $index => $conversation) {
                if ($conversation['user_id'] === $userId) {
                    $conversationIndex = $index;
                    break;
                }
            }
            
            if ($conversationIndex === -1) {
                // Create new conversation
                $data['conversations'][] = [
                    'user_id' => $userId,
                    'messages' => [],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $conversationIndex = count($data['conversations']) - 1;
            }
            
            // Add message to conversation
            $data['conversations'][$conversationIndex]['messages'][] = [
                'id' => uniqid('msg_', true),
                'sender' => $sender,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $data['conversations'][$conversationIndex]['updated_at'] = date('Y-m-d H:i:s');
            
            if (writeDataFile($dataFile, $data)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save message']);
            }
            break;
            
        case 'get_conversation':
            if (!isset($input['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }
            
            $userId = $input['user_id'];
            
            // Find user's conversation
            foreach ($data['conversations'] as $conversation) {
                if ($conversation['user_id'] === $userId) {
                    echo json_encode(['success' => true, 'conversation' => $conversation]);
                    exit;
                }
            }
            
            // No conversation found
            echo json_encode(['success' => true, 'conversation' => null]);
            break;
            
        case 'check_returning_user':
            if (!isset($input['email']) || !isset($input['name'])) {
                echo json_encode(['success' => false, 'message' => 'Email and name required']);
                exit;
            }
            
            $email = trim($input['email']);
            $name = trim($input['name']);
            
            $existingUser = findUser($data['users'], $email, $name);
            
            if ($existingUser) {
                // Get user's conversation
                $conversation = null;
                foreach ($data['conversations'] as $conv) {
                    if ($conv['user_id'] === $existingUser['user']['id']) {
                        $conversation = $conv;
                        break;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'is_returning' => true,
                    'user' => $existingUser['user'],
                    'conversation' => $conversation
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'is_returning' => false
                ]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
}
?>