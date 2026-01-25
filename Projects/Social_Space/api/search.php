<?php
// Include the database connection file
require_once '../config/database.php';

// Include helper functions
require_once '../includes/functions.php';

// Check if the user is logged in
check_login();

// Set response type to JSON (for AJAX calls)
header('Content-Type: application/json');

// Get the search query from the URL and sanitize it
$query = sanitize_input($_GET['query'] ?? '');

// If the query is shorter than 2 characters, return an empty result
if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'users' => []]);
    exit();
}

// Add wildcard symbols (%) for SQL LIKE search
$search = "%{$query}%";

// Prepare SQL statement to search users and check friendship status
$stmt = $conn->prepare("
    SELECT 
        u.id, 
        u.username, 
        u.full_name, 
        u.profile_pic,
        CASE 
            WHEN f.status = 'accepted' THEN 'friend'                      -- Already friends
            WHEN f.status = 'pending' AND f.user_id = ? THEN 'pending_sent' -- Request sent by you
            WHEN f.status = 'pending' AND f.friend_id = ? THEN 'pending_received' -- Request received from them
            ELSE 'none'                                                   -- No friendship yet
        END as friendship_status
    FROM users u
    LEFT JOIN friendships f ON (
        (f.user_id = ? AND f.friend_id = u.id) OR 
        (f.friend_id = ? AND f.user_id = u.id)
    )
    WHERE u.id != ? AND (u.username LIKE ? OR u.full_name LIKE ?)
    LIMIT 10
");

// Execute query with session user ID and search term
$stmt->execute([
    $_SESSION['user_id'], $_SESSION['user_id'], 
    $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'], $search, $search
]);

// Fetch all matching users
$users = $stmt->fetchAll();

// Return search results as JSON
echo json_encode(['success' => true, 'users' => $users]);
?>
