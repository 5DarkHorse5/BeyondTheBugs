<?php
// Include the database connection file
require_once '../config/database.php';

// Include helper functions (like sanitize_input and login checks)
require_once '../includes/functions.php';

// Ensure that the user is logged in before continuing
check_login();

// Tell the browser that this script will return JSON data
header('Content-Type: application/json');

// Determine the action to perform (either from POST or GET)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle the different actions based on what the client requested
switch ($action) {
    // -------------------- SEND MESSAGE --------------------
    case 'send':
        // Get the receiver's user ID as an integer
        $receiver_id = intval($_POST['receiver_id']);
        // Sanitize the message input to prevent security issues
        $message = sanitize_input($_POST['message']);
        
        // Check if the message field is empty
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
            break;
        }
        
        // Insert the message into the messages table
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $receiver_id, $message])) {
            echo json_encode(['success' => true, 'message' => 'Message sent']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        }
        break;
        
    // -------------------- GET CHAT MESSAGES --------------------
    case 'get':
        // Get the friend's user ID whose chat messages are being requested
        $friend_id = intval($_GET['friend_id']);
        
        // Select all messages between the logged-in user and this friend
        $stmt = $conn->prepare("
            SELECT m.*, u.username, u.full_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$_SESSION['user_id'], $friend_id, $friend_id, $_SESSION['user_id']]);
        
        // Fetch all messages in order from oldest to newest
        $messages = $stmt->fetchAll();
        
        // Mark messages from the friend as "read"
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->execute([$friend_id, $_SESSION['user_id']]);
        
        // Add extra info for each message (e.g. whether it’s sent by me, and time since sent)
        foreach ($messages as &$msg) {
            // Check if this message belongs to the current user
            $msg['is_mine'] = ($msg['sender_id'] == $_SESSION['user_id']);
            // Convert the timestamp to a "time ago" format (like "5 minutes ago")
            $msg['time_ago'] = time_elapsed($msg['created_at']);
        }
        
        // Return the chat messages as JSON
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;
        
    // -------------------- DELETE A SINGLE MESSAGE --------------------
    case 'delete':
        // Get the message ID to be deleted
        $message_id = intval($_POST['message_id']);
        
        // Only delete messages sent by the logged-in user
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
        if ($stmt->execute([$message_id, $_SESSION['user_id']])) {
            echo json_encode(['success' => true, 'message' => 'Message deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
        }
        break;
        
    // -------------------- DELETE ENTIRE CHAT --------------------
    case 'delete_chat':
        // Get the friend's user ID whose chat should be deleted
        $friend_id = intval($_POST['friend_id']);
        
        // Delete all messages between the logged-in user and this friend
        $stmt = $conn->prepare("DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
        if ($stmt->execute([$_SESSION['user_id'], $friend_id, $friend_id, $_SESSION['user_id']])) {
            echo json_encode(['success' => true, 'message' => 'Chat history deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete chat']);
        }
        break;
        
    // -------------------- SET CHAT THEME --------------------
    case 'set_theme':
        // Get the friend's ID and the chosen theme name
        $friend_id = intval($_POST['friend_id']);
        $theme = sanitize_input($_POST['theme']);
        
        // Insert or update the chat theme for this user and friend
        $stmt = $conn->prepare("INSERT INTO message_themes (user_id, friend_id, theme) VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE theme = ?");
        if ($stmt->execute([$_SESSION['user_id'], $friend_id, $theme, $theme])) {
            echo json_encode(['success' => true, 'message' => 'Theme updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update theme']);
        }
        break;
        
    // -------------------- GET CHAT THEME --------------------
    case 'get_theme':
        // Get the friend's ID whose theme we want to load
        $friend_id = intval($_GET['friend_id']);
        
        // Retrieve the saved theme for this chat
        $stmt = $conn->prepare("SELECT theme FROM message_themes WHERE user_id = ? AND friend_id = ?");
        $stmt->execute([$_SESSION['user_id'], $friend_id]);
        
        // Fetch the result
        $result = $stmt->fetch();
        
        // If no theme is found, default to 'default'
        $theme = $result ? $result['theme'] : 'default';
        
        // Return the theme in JSON format
        echo json_encode(['success' => true, 'theme' => $theme]);
        break;
        
    // -------------------- INVALID ACTION HANDLER --------------------
    default:
        // Handle any invalid or missing action request
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
