<?php
// Include the database connection file
require_once '../config/database.php';

// Include helper functions (like sanitize_input and check_login)
require_once '../includes/functions.php';

// Ensure the user is logged in before using this script
check_login();

// Set the content type to JSON since responses will be in JSON format
header('Content-Type: application/json');

// Determine what action to perform based on the request (POST or GET)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle different post-related actions
switch ($action) {

    // -------------------- CREATE A POST --------------------
    case 'create':
        // Sanitize the post content
        $content = sanitize_input($_POST['content']);
        // Initialize image variable as null
        $image = null;
        
        // Check if an image was uploaded and there are no upload errors
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Allowed image file types
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            // Get the original uploaded file name
            $filename = $_FILES['image']['name'];
            // Extract the file extension
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            // Validate the file extension
            if (in_array($ext, $allowed)) {
                // Generate a unique filename for the uploaded image
                $new_filename = uniqid() . '.' . $ext;
                // Define the upload path
                $upload_path = '../uploads/posts/' . $new_filename;
                
                // Move the uploaded file to the uploads directory
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Store the image name to save in the database
                    $image = $new_filename;
                }
            }
        }
        
        // Insert the new post into the database
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $content, $image])) {
            echo json_encode(['success' => true, 'message' => 'Post created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create post']);
        }
        break;
        
    // -------------------- DELETE A POST --------------------
    case 'delete':
        // Get the post ID to delete
        $post_id = intval($_POST['post_id']);
        
        // Fetch the post details to check ownership and get the image name
        $stmt = $conn->prepare("SELECT user_id, image FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        
        // Check if the post exists and belongs to the logged-in user
        if ($post && $post['user_id'] == $_SESSION['user_id']) {
            // Delete the post image from the server if it exists
            if ($post['image'] && file_exists('../uploads/posts/' . $post['image'])) {
                unlink('../uploads/posts/' . $post['image']);
            }
            
            // Delete the post record from the database
            $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
            if ($stmt->execute([$post_id])) {
                echo json_encode(['success' => true, 'message' => 'Post deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
            }
        } else {
            // Unauthorized attempt to delete a post
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
        break;
        
    // -------------------- LIKE OR UNLIKE A POST --------------------
    case 'like':
        // Get the post ID to like/unlike
        $post_id = intval($_POST['post_id']);
        
        // Check if the user already liked this post
        $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        
        // If already liked, remove the like (unlike)
        if ($stmt->fetch()) {
            $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
            $liked = false;
        } else {
            // Otherwise, add a new like
            $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
            $liked = true;
        }
        
        // Get the updated number of likes for the post
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $result = $stmt->fetch();
        
        // Return the like status and count
        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $result['count']]);
        break;
        
    // -------------------- ADD A COMMENT --------------------
    case 'comment':
        // Get the post ID and the comment text
        $post_id = intval($_POST['post_id']);
        $comment = sanitize_input($_POST['comment']);
        
        // Insert the comment into the comments table
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        if ($stmt->execute([$post_id, $_SESSION['user_id'], $comment])) {
            echo json_encode(['success' => true, 'message' => 'Comment added']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
        }
        break;
        
    // -------------------- FETCH COMMENTS FOR A POST --------------------
    case 'get_comments':
        // Get the post ID to fetch comments for
        $post_id = intval($_GET['post_id']);
        
        // Retrieve all comments for this post with user details
        $stmt = $conn->prepare("
            SELECT c.*, u.username, u.full_name, u.profile_pic
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll();
        
        // Add time and ownership details to each comment
        foreach ($comments as &$comment) {
            $comment['time_ago'] = time_elapsed($comment['created_at']); // Format time (e.g., "2h ago")
            $comment['can_delete'] = ($comment['user_id'] == $_SESSION['user_id']); // Check if user can delete it
        }
        
        // Return all comments as a JSON response
        echo json_encode(['success' => true, 'comments' => $comments]);
        break;
        
    // -------------------- DELETE A COMMENT --------------------
    case 'delete_comment':
        // Get the comment ID to delete
        $comment_id = intval($_POST['comment_id']);
        
        // Check who owns the comment
        $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();
        
        // Allow deletion only if the logged-in user is the comment owner
        if ($comment && $comment['user_id'] == $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
            if ($stmt->execute([$comment_id])) {
                echo json_encode(['success' => true, 'message' => 'Comment deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
            }
        } else {
            // Unauthorized attempt
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
        break;
        
    // -------------------- SHARE A POST --------------------
    case 'share':
        // Get the post ID to share
        $post_id = intval($_POST['post_id']);
        
        // Insert a new record in the shares table
        $stmt = $conn->prepare("INSERT INTO shares (post_id, user_id) VALUES (?, ?)");
        if ($stmt->execute([$post_id, $_SESSION['user_id']])) {
            echo json_encode(['success' => true, 'message' => 'Post shared']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to share post']);
        }
        break;
        
    // -------------------- INVALID ACTION HANDLER --------------------
    default:
        // Handle invalid or unknown action requests
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
