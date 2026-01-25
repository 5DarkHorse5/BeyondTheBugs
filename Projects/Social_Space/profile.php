<?php
// Bring in the database connection file
require_once 'config/database.php';

// Bring in the helper functions we need
require_once 'includes/functions.php';

// Make sure the user is logged in - if not, send them to login page
check_login();

// Get the user ID from the URL, or use the logged-in user's ID if none provided
$user_id = $_GET['id'] ?? $_SESSION['user_id'];

// Check if this is the logged-in user's own profile
$is_own_profile = ($user_id == $_SESSION['user_id']);

// Get the profile user's information from the database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile_user = $stmt->fetch();

// If we couldn't find this user, send them back to the dashboard
if (!$profile_user) {
    header("Location: dashboard.php");
    exit();
}

// Get all posts made by this user
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.full_name, u.profile_pic,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");

// Run the query with the logged-in user's ID and the profile user's ID
$stmt->execute([$_SESSION['user_id'], $user_id]);

// Get all the posts
$posts = $stmt->fetchAll();

// Count how many posts this user has made
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
$stmt->execute([$user_id]);
$post_count = $stmt->fetch()['count'];

// Count how many friends this user has
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM friendships 
    WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'
");
$stmt->execute([$user_id, $user_id]);
$friends_count = $stmt->fetch()['count'];

// Set the default friendship status to 'none'
$friendship_status = 'none';

// Only check friendship status if viewing someone else's profile
if (!$is_own_profile) {
    // Look up the friendship between you and this user
    $stmt = $conn->prepare("
        SELECT status, user_id FROM friendships 
        WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $user_id, $user_id, $_SESSION['user_id']]);
    $friendship = $stmt->fetch();
    
    // Check if there's a friendship record
    if ($friendship) {
        // If status is 'accepted', you're friends
        if ($friendship['status'] == 'accepted') {
            $friendship_status = 'friends';
        // If you sent the request, mark it as 'pending_sent'
        } elseif ($friendship['user_id'] == $_SESSION['user_id']) {
            $friendship_status = 'pending_sent';
        // If they sent you the request, mark it as 'pending_received'
        } else {
            $friendship_status = 'pending_received';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Tell the browser this page uses UTF-8 characters -->
    <meta charset="UTF-8">
    
    <!-- Make the page look good on phones and tablets -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Set the page title to the user's name -->
    <title><?php echo htmlspecialchars($profile_user['full_name']); ?> - Social Space</title>
    
    <!-- Load the main stylesheet -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Load the dashboard stylesheet -->
    <link rel="stylesheet" href="css/dashboard.css">
    
    <!-- Load the profile-specific stylesheet -->
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <!-- Navigation bar at the top -->
    <nav class="navbar">
        <!-- Container for navbar content -->
        <div class="nav-container">
            <!-- Logo - clicking it takes you to the dashboard -->
            <h1 class="nav-logo" onclick="location.href='dashboard.php'">Social Space</h1>
            
            <!-- Search bar section -->
            <div class="nav-search">
                <!-- Input box to search for friends -->
                <input type="text" id="search-users" placeholder="Search friends..." autocomplete="off">
                
                <!-- This div will show search results -->
                <div id="search-results" class="search-results"></div>
            </div>
            
            <!-- Navigation menu buttons -->
            <div class="nav-menu">
                <!-- Home button - goes to dashboard -->
                <button onclick="location.href='dashboard.php'" class="nav-btn">🏠 Home</button>
                
                <!-- Button to create a new post -->
                <button onclick="showCreatePost()" class="nav-btn">➕ Post</button>
                
                <!-- Button to view friend requests -->
                <button onclick="showFriendRequests()" class="nav-btn">👥 Friends</button>
                
                <!-- Button to open messages -->
                <button onclick="showMessages()" class="nav-btn">💬 Messages</button>
                
                <!-- Button to view your own profile -->
                <button onclick="location.href='profile.php'" class="nav-btn">👤 Profile</button>
                
                <!-- Logout link -->
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main container for page content -->
    <div class="main-container">
        <!-- Profile header section with cover photo and profile info -->
        <div class="profile-header">
            <!-- Cover photo area (green gradient background) -->
            <div class="profile-cover"></div>
            
            <!-- Section containing profile picture and user details -->
            <div class="profile-info-section">
                <!-- Profile picture container -->
                <div class="profile-picture-container">
                    <!-- Show the user's profile picture -->
                    <img src="uploads/profiles/<?php echo htmlspecialchars($profile_user['profile_pic']); ?>" alt="Profile" class="profile-picture">
                    
                    <!-- If this is your own profile, show the edit button -->
                    <?php if ($is_own_profile): ?>
                        <button class="edit-profile-pic" onclick="showEditProfile()">📷</button>
                    <?php endif; ?>
                </div>
                
                <!-- Profile details section -->
                <div class="profile-details">
                    <!-- Show user's full name -->
                    <h1><?php echo htmlspecialchars($profile_user['full_name']); ?></h1>
                    
                    <!-- Show username with @ symbol -->
                    <p class="username-text">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
                    
                    <!-- If user has a bio, show it -->
                    <?php if ($profile_user['bio']): ?>
                        <p class="bio-text"><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></p>
                    <!-- If it's your profile and you don't have a bio yet, show a message -->
                    <?php elseif ($is_own_profile): ?>
                        <p class="bio-text" style="color: #94a3b8; font-style: italic;">No bio yet. Click edit to add one!</p>
                    <?php endif; ?>
                    
                    <!-- Stats showing posts and friends count -->
                    <div class="profile-stats">
                        <!-- Number of posts -->
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $post_count; ?></span>
                            <span class="stat-label">Posts</span>
                        </div>
                        
                        <!-- Number of friends -->
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $friends_count; ?></span>
                            <span class="stat-label">Friends</span>
                        </div>
                    </div>
                    
                    <!-- If this is your own profile, show edit button -->
                    <?php if ($is_own_profile): ?>
                        <button class="btn-edit-profile" onclick="showEditProfile()">✏️ Edit Profile</button>
                    
                    <!-- If viewing someone else's profile, show friend action buttons -->
                    <?php else: ?>
                        <div class="profile-actions">
                            <!-- If you're already friends, show message and unfriend buttons -->
                            <?php if ($friendship_status == 'friends'): ?>
                                <button class="btn-primary" onclick="location.href='dashboard.php'">Message</button>
                                <button class="btn-secondary" onclick="unfriendUser(<?php echo $user_id; ?>)">Unfriend</button>
                            
                            <!-- If you sent them a friend request, show disabled button -->
                            <?php elseif ($friendship_status == 'pending_sent'): ?>
                                <button class="btn-secondary" disabled>Request Sent</button>
                            
                            <!-- If they sent you a request, show respond button -->
                            <?php elseif ($friendship_status == 'pending_received'): ?>
                                <button class="btn-primary" onclick="showFriendRequests()">Respond to Request</button>
                            
                            <!-- If you're not friends, show add friend button -->
                            <?php else: ?>
                                <button class="btn-primary" onclick="sendFriendRequest(<?php echo $user_id; ?>)">Add Friend</button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section showing user's posts -->
        <div class="profile-content">
            <!-- Posts section title -->
            <h2 class="section-title">Posts</h2>
            
            <!-- If user has no posts, show a message -->
            <?php if (count($posts) == 0): ?>
                <div class="no-posts">
                    <p>No posts yet</p>
                </div>
            
            <!-- If user has posts, show them -->
            <?php else: ?>
                <div id="posts-feed">
                    <!-- Loop through each post -->
                    <?php foreach ($posts as $post): ?>
                        <!-- Each post card -->
                        <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                            <!-- Post header with user info -->
                            <div class="post-header">
                                <div class="post-user">
                                    <!-- User's profile picture -->
                                    <img src="uploads/profiles/<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile" class="post-avatar">
                                    <div>
                                        <!-- User's name -->
                                        <h4><?php echo htmlspecialchars($post['full_name']); ?></h4>
                                        
                                        <!-- Time since post was created -->
                                        <span class="post-time"><?php echo time_elapsed($post['created_at']); ?></span>
                                    </div>
                                </div>
                                
                                <!-- If this is your own profile, show delete button -->
                                <?php if ($is_own_profile): ?>
                                    <button class="delete-btn" onclick="deletePost(<?php echo $post['id']; ?>)">🗑️</button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Post content (text and image) -->
                            <div class="post-content">
                                <!-- Post text -->
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                
                                <!-- If post has an image, show it -->
                                <?php if ($post['image']): ?>
                                    <img src="uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" alt="Post" class="post-image">
                                <?php endif; ?>
                            </div>
                            
                            <!-- Stats showing likes and comments -->
                            <div class="post-stats">
                                <span><?php echo $post['likes_count']; ?> likes</span>
                                <span><?php echo $post['comments_count']; ?> comments</span>
                            </div>
                            
                            <!-- Action buttons -->
                            <div class="post-actions">
                                <!-- Like button - add 'liked' class if user already liked it -->
                                <button onclick="likePost(<?php echo $post['id']; ?>)" class="action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>">
                                    ❤️ Like
                                </button>
                                
                                <!-- Comment button -->
                                <button onclick="toggleComments(<?php echo $post['id']; ?>)" class="action-btn">💬 Comment</button>
                                
                                <!-- Share button -->
                                <button onclick="sharePost(<?php echo $post['id']; ?>)" class="action-btn">🔗 Share</button>
                            </div>
                            
                            <!-- Comments section (hidden by default) -->
                            <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display:none;">
                                <!-- This div will be filled with comments by JavaScript -->
                                <div class="comments-list"></div>
                                
                                <!-- Input area to write a comment -->
                                <div class="comment-input">
                                    <!-- Text box to type comment -->
                                    <input type="text" placeholder="Write a comment..." id="comment-input-<?php echo $post['id']; ?>">
                                    
                                    <!-- Send button -->
                                    <button onclick="addComment(<?php echo $post['id']; ?>)">Send</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal/popup for editing profile -->
    <div id="edit-profile-modal" class="modal">
        <div class="modal-content">
            <!-- X button to close the modal -->
            <span class="close" onclick="closeModal('edit-profile-modal')">&times;</span>
            
            <!-- Modal title -->
            <h2>Edit Profile</h2>
            
            <!-- Form to update profile information -->
            <form id="edit-profile-form" enctype="multipart/form-data">
                <!-- Profile picture upload field -->
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_pic" accept="image/*">
                    <small>Leave empty to keep current picture</small>
                </div>
                
                <!-- Full name field -->
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($profile_user['full_name']); ?>" required>
                </div>
                
                <!-- Bio text area -->
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profile_user['bio'] ?? ''); ?></textarea>
                </div>
                
                <!-- Save button -->
                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Modal/popup for creating a new post -->
    <div id="create-post-modal" class="modal">
        <div class="modal-content">
            <!-- X button to close -->
            <span class="close" onclick="closeModal('create-post-modal')">&times;</span>
            
            <!-- Modal title -->
            <h2>Create Post</h2>
            
            <!-- Form to create a post -->
            <form id="create-post-form" enctype="multipart/form-data">
                <!-- Text area for post content -->
                <textarea name="content" placeholder="What's on your mind?" required></textarea>
                
                <!-- Image upload field -->
                <input type="file" name="image" accept="image/*">
                
                <!-- Post button -->
                <button type="submit" class="btn-primary">Post</button>
            </form>
        </div>
    </div>

    <!-- Modal/popup for friend requests -->
    <div id="friend-requests-modal" class="modal">
        <div class="modal-content">
            <!-- X button to close -->
            <span class="close" onclick="closeModal('friend-requests-modal')">&times;</span>
            
            <!-- Modal title -->
            <h2>Friend Requests</h2>
            
            <!-- This div will be filled with friend requests by JavaScript -->
            <div id="friend-requests-list"></div>
        </div>
    </div>

    <!-- Modal/popup for messages -->
    <div id="messages-modal" class="modal">
        <div class="modal-content messages-modal-content">
            <!-- X button to close -->
            <span class="close" onclick="closeModal('messages-modal')">&times;</span>
            
            <!-- Container for messaging interface -->
            <div class="messages-container">
                <!-- Left side: List of friends -->
                <div class="friends-list">
                    <h3>Friends</h3>
                    
                    <!-- This div will be filled with friends list by JavaScript -->
                    <div id="friends-list"></div>
                </div>
                
                <!-- Right side: Chat area -->
                <div class="chat-area">
                    <!-- Top bar showing who you're chatting with -->
                    <div class="chat-header">
                        <!-- Friend's name -->
                        <h3 id="chat-username">Select a friend to chat</h3>
                        
                        <!-- Theme button (hidden until you select a friend) -->
                        <button id="theme-btn" onclick="showThemeSelector()" style="display:none;">🎨</button>
                        
                        <!-- Delete chat button (hidden until you select a friend) -->
                        <button id="delete-chat-btn" onclick="deleteChat()" style="display:none;">🗑️</button>
                    </div>
                    
                    <!-- Area where messages appear -->
                    <div id="chat-messages" class="chat-messages"></div>
                    
                    <!-- Input area to type and send messages -->
                    <div class="chat-input">
                        <!-- Emoji button -->
                        <button onclick="showEmojiPicker()">😊</button>
                        
                        <!-- Message input box -->
                        <input type="text" id="message-input" placeholder="Type a message..." disabled>
                        
                        <!-- Send button -->
                        <button onclick="sendMessage()" id="send-btn" disabled>Send</button>
                    </div>
                    
                    <!-- Emoji picker (hidden by default) -->
                    <div id="emoji-picker" class="emoji-picker" style="display:none;">
                        <!-- Each emoji is clickable -->
                        <span onclick="insertEmoji('😊')">😊</span>
                        <span onclick="insertEmoji('😂')">😂</span>
                        <span onclick="insertEmoji('❤️')">❤️</span>
                        <span onclick="insertEmoji('👍')">👍</span>
                        <span onclick="insertEmoji('🎉')">🎉</span>
                        <span onclick="insertEmoji('🔥')">🔥</span>
                        <span onclick="insertEmoji('😎')">😎</span>
                        <span onclick="insertEmoji('🤗')">🤗</span>
                    </div>
                </div>
            </div>
            
            <!-- Theme selector (hidden by default) -->
            <div id="theme-selector" class="theme-selector" style="display:none;">
                <h4>Select Theme</h4>
                
                <!-- Buttons to choose different chat backgrounds -->
                <button onclick="setTheme('default')">Default</button>
                <button onclick="setTheme('doodle')">Doodle</button>
                <button onclick="setTheme('gradient')">Gradient</button>
                <button onclick="setTheme('matrix')">Matrix</button>
                <button onclick="setTheme('sunset')">Sunset</button>
                <button onclick="setTheme('ocean')">Ocean</button>
            </div>
        </div>
    </div>

    <!-- Load the dashboard JavaScript file -->
    <script src="js/dashboard.js"></script>
    
    <!-- Load the profile-specific JavaScript file -->
    <script src="js/profile.js"></script>
</body>
</html>