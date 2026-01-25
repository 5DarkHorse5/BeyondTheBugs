<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Ensure the user is logged in before proceeding
check_login();

// Return data as JSON
header('Content-Type: application/json');

// Get the action from POST request
$action = $_POST['action'] ?? '';

// Handle profile update action
if ($action == 'update') {
    try {
        // Sanitize user input for security
        $full_name = sanitize_input($_POST['full_name']);
        $bio = sanitize_input($_POST['bio']);
        $profile_pic = null;
        
        // ------------------------------
        // PROFILE PICTURE UPLOAD SECTION
        // ------------------------------
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif']; // Allowed file types
            $filename = $_FILES['profile_pic']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION)); // Get file extension
            
            // Check if file type is valid
            if (in_array($ext, $allowed)) {
                // Create the upload directory if it doesn't exist
                if (!file_exists('../uploads/profiles/')) {
                    mkdir('../uploads/profiles/', 0777, true);
                }
                
                // Generate a unique filename to avoid duplicates
                $new_filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $upload_path = '../uploads/profiles/' . $new_filename;
                
                // Move uploaded file to the destination folder
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                    
                    // Fetch current profile picture to delete the old one (if not default)
                    $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $old_pic = $stmt->fetch()['profile_pic'];
                    
                    // Delete old image file if it exists and isn’t default
                    if ($old_pic != 'default.jpg' && file_exists('../uploads/profiles/' . $old_pic)) {
                        unlink('../uploads/profiles/' . $old_pic);
                    }
                    
                    // Store new profile image filename
                    $profile_pic = $new_filename;
                } else {
                    // File upload failed (permissions or path issue)
                    echo json_encode(['success' => false, 'message' => 'Failed to upload image. Check folder permissions.']);
                    exit;
                }
            } else {
                // Invalid file extension
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF allowed.']);
                exit;
            }
        }
        
        // --------------------------
        // UPDATE USER PROFILE INFO
        // --------------------------
        if ($profile_pic) {
            // Update including profile picture
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, bio = ?, profile_pic = ? WHERE id = ?");
            $result = $stmt->execute([$full_name, $bio, $profile_pic, $_SESSION['user_id']]);
        } else {
            // Update without profile picture
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, bio = ? WHERE id = ?");
            $result = $stmt->execute([$full_name, $bio, $_SESSION['user_id']]);
        }
        
        // --------------------------
        // SEND JSON RESPONSE
        // --------------------------
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
        
    } catch (Exception $e) {
        // Catch any PHP or database error
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    // Invalid action was passed
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
