<?php
// Include the database connection file
require_once '../config/database.php';

// Include helper functions (like login checking)
require_once '../includes/functions.php';

// Verify that the user is logged in before proceeding
check_login();

// Set the response content type to JSON (for JavaScript handling)
header('Content-Type: application/json');

// Get the requested action from either POST or GET (default to empty string if none)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle different friend-related actions based on the action type
switch ($action) {
    // -------------------- SEND FRIEND REQUEST --------------------
    case 'send_request':
        // Get the friend's user ID from the form and convert it to an integer
        $friend_id = intval($_POST['friend_id']);
        
        // Prevent users from sending a request to themselves
        if ($friend_id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Cannot send request to yourself']);
            break;
        }
        
        // Check if a friend request already exists between the two users
        $stmt = $conn->prepare("SELECT id FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->execute([$_SESSION['user_id'], $friend_id, $friend_id, $_SESSION['user_id']]);
        
        // If a record is found, a request already exists
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Request already exists']);
        } else {
            // If not found, insert a new friend request into the friendships table
            $stmt = $conn->prepare("INSERT INTO friendships (user_id, friend_id) VALUES (?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $friend_id])) {
                echo json_encode(['success' => true, 'message' => 'Friend request sent']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send request']);
            }
        }
        break;
        
    // -------------------- ACCEPT FRIEND REQUEST --------------------
    case 'accept_request':
        // Get the ID of the friend request to accept
        $request_id = intval($_POST['request_id']);
        
        // Update the friendship status to "accepted" for this request
        $stmt = $conn->prepare("UPDATE friendships SET status = 'accepted' WHERE id = ? AND friend_id = ?");
        if ($stmt->execute([$request_id, $_SESSION['user_id']])) {
            echo json_encode(['success' => true, 'message' => 'Friend request accepted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to accept request']);
        }
        break;
        
    // -------------------- REJECT FRIEND REQUEST --------------------
    case 'reject_request':
        // Get the ID of the request to reject
        $request_id = intval($_POST['request_id']);
        
        // Delete the pending request from the database
        $stmt = $conn->prepare("DELETE FROM friendships WHERE id = ? AND friend_id = ?");
        if ($stmt->execute([$request_id, $_SESSION['user_id']])) {
            echo json_encode(['success' => true, 'message' => 'Friend request rejected']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject request']);
        }
        break;
        
    // -------------------- UNFRIEND --------------------
    case 'unfriend':
        // Get the friend's user ID to remove the connection
        $friend_id = intval($_POST['friend_id']);
        
        // Delete the friendship record regardless of who initiated it
        $stmt = $conn->prepare("DELETE FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        if ($stmt->execute([$_SESSION['user_id'], $friend_id, $friend_id, $_SESSION['user_id']])) {
            echo json_encode(['success' => true, 'message' => 'Friend removed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove friend']);
        }
        break;
        
    // -------------------- GET PENDING FRIEND REQUESTS --------------------
    case 'get_requests':
        // Select all pending requests sent to the logged-in user
        $stmt = $conn->prepare("
            SELECT f.id, u.id as user_id, u.username, u.full_name, u.profile_pic
            FROM friendships f
            JOIN users u ON f.user_id = u.id
            WHERE f.friend_id = ? AND f.status = 'pending'
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Fetch all pending requests as an array
        $requests = $stmt->fetchAll();
        
        // Return the pending requests as a JSON response
        echo json_encode(['success' => true, 'requests' => $requests]);
        break;
        
    // -------------------- GET FRIEND LIST --------------------
    case 'get_friends':
        // Select all accepted friendships involving the logged-in user
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.full_name, u.profile_pic
            FROM users u
            INNER JOIN friendships f ON (
                (f.user_id = ? AND f.friend_id = u.id) OR 
                (f.friend_id = ? AND f.user_id = u.id)
            )
            WHERE f.status = 'accepted'
            ORDER BY u.full_name ASC
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        
        // Fetch all friends
        $friends = $stmt->fetchAll();
        
        // Return the list of friends as a JSON response
        echo json_encode(['success' => true, 'friends' => $friends]);
        break;
        
    // -------------------- INVALID ACTION HANDLER --------------------
    default:
        // If no valid action is provided, return an error message
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
