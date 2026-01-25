<?php
// Clean up user input to prevent harmful code
function sanitize_input($data) {
    // Remove extra spaces from beginning and end
    $data = trim($data);
    // Remove backslashes
    $data = stripslashes($data);
    // Convert special characters to safe HTML (prevents hacking)
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // Return the cleaned data
    return $data;
}

// Check if user is logged in, if not send them to login page
function check_login() {
    // If user_id is not in the session (not logged in)
    if (!isset($_SESSION['user_id'])) {
        // Send them to the login page
        header("Location: index.php");
        // Stop running any more code
        exit();
    }
}

// Convert a timestamp to "time ago" format (like "2h ago")
function time_elapsed($datetime) {
    // Get the current time
    $now = new DateTime;
    // Get the time when something happened
    $ago = new DateTime($datetime);
    // Calculate the difference between now and then
    $diff = $now->diff($ago);

    // If more than a year ago, show years
    if ($diff->y > 0) return $diff->y . 'y ago';
    // If more than a month ago, show months
    if ($diff->m > 0) return $diff->m . 'mo ago';
    // If more than a day ago, show days
    if ($diff->d > 0) return $diff->d . 'd ago';
    // If more than an hour ago, show hours
    if ($diff->h > 0) return $diff->h . 'h ago';
    // If more than a minute ago, show minutes
    if ($diff->i > 0) return $diff->i . 'm ago';
    // If less than a minute, show "Just now"
    return 'Just now';
}

// Get a user's basic information from the database
function get_user_info($conn, $user_id) {
    // Prepare a query to get user info
    $stmt = $conn->prepare("SELECT id, username, full_name, profile_pic FROM users WHERE id = ?");
    // Run the query with the user's ID
    $stmt->execute([$user_id]);
    // Return the user's information
    return $stmt->fetch();
}
?>