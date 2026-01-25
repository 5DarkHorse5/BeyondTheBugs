<?php
// Bring in the database connection file so we can talk to the database
require_once 'config/database.php';

// Bring in the helper functions file that has useful tools we'll need
require_once 'includes/functions.php';

// Check if the user is logged in - if not, send them to the login page
check_login();

// Get the current logged-in user's information from the database
$user = get_user_info($conn, $_SESSION['user_id']);

// Prepare a query to get all posts from the database
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.full_name, u.profile_pic,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");

// Run the query and pass in the current user's ID to check if they liked each post
$stmt->execute([$_SESSION['user_id']]);

// Get all the posts and save them in a variable called $posts
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Tell the browser this page uses UTF-8 characters -->
    <meta charset="UTF-8">
    
    <!-- Make the page look good on phones and tablets -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Set the title that shows in the browser tab -->
    <title>Social Space - Dashboard</title>
    
    <!-- Load the main style sheet for the whole site -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Load the dashboard-specific style sheet -->
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <!-- This is the navigation bar at the top of the page -->
    <nav class="navbar">
        <!-- Container to hold everything in the navbar -->
        <div class="nav-container">
            <!-- The Social Space logo/title -->
            <h1 class="nav-logo">Social Space</h1>
            
            <!-- Search bar section -->
            <div class="nav-search">
                <!-- Input box where users can type to search for friends -->
                <input type="text" id="search-users" placeholder="Search friends..." autocomplete="off">
                
                <!-- This div will show the search results when user types -->
                <div id="search-results" class="search-results"></div>
            </div>
            
            <!-- Navigation menu with all the buttons -->
            <div class="nav-menu">
                <!-- Button to create a new post -->
                <button onclick="showCreatePost()" class="nav-btn">➕ Post</button>
                
                <!-- Button to see friend requests -->
                <button onclick="showFriendRequests()" class="nav-btn">👥 Friends</button>
                
                <!-- Button to open messages -->
                <button onclick="showMessages()" class="nav-btn">💬 Messages</button>
                
                <!-- Button to go to user's profile page -->
                <button onclick="location.href='profile.php'" class="nav-btn">👤 Profile</button>
                
                <!-- Link to log out of the account -->
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main container that holds all the content on the page -->
    <div class="main-container">
        <!-- Container specifically for the posts feed -->
        <div class="feed-container">
            <!-- This div will contain all the posts -->
            <div id="posts-feed">
                <!-- Loop through each post and display it -->
                <?php foreach ($posts as $post): ?>
                    <!-- Each post is wrapped in this card -->
                    <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                        <!-- Header section of the post (shows who posted it) -->
                        <div class="post-header">
                            <!-- User information section -->
                            <div class="post-user">
                                <!-- Show the user's profile picture -->
                                <img src="uploads/profiles/<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile" class="post-avatar">
                                
                                <!-- User's name and time posted -->
                                <div>
                                    <!-- Display the user's full name -->
                                    <h4><?php echo htmlspecialchars($post['full_name']); ?></h4>
                                    
                                    <!-- Show how long ago the post was created (like "2 hours ago") -->
                                    <span class="post-time"><?php echo time_elapsed($post['created_at']); ?></span>
                                </div>
                            </div>
                            
                            <!-- If this is the current user's post, show delete button -->
                            <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                <!-- Delete button (trash can icon) -->
                                <button class="delete-btn" onclick="deletePost(<?php echo $post['id']; ?>)">🗑️</button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- The actual content of the post -->
                        <div class="post-content">
                            <!-- Display the post text (nl2br makes line breaks work) -->
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
                            <!-- If the post has an image, show it -->
                            <?php if ($post['image']): ?>
                                <img src="uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" alt="Post" class="post-image">
                            <?php endif; ?>
                        </div>
                        
                        <!-- Stats showing number of likes and comments -->
                        <div class="post-stats">
                            <!-- Show how many likes this post has -->
                            <span><?php echo $post['likes_count']; ?> likes</span>
                            
                            <!-- Show how many comments this post has -->
                            <span><?php echo $post['comments_count']; ?> comments</span>
                        </div>
                        
                        <!-- Action buttons (Like, Comment, Share) -->
                        <div class="post-actions">
                            <!-- Like button - add 'liked' class if user already liked it -->
                            <button onclick="likePost(<?php echo $post['id']; ?>)" class="action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>">
                                ❤️ Like
                            </button>
                            
                            <!-- Comment button - opens the comments section -->
                            <button onclick="toggleComments(<?php echo $post['id']; ?>)" class="action-btn">💬 Comment</button>
                            
                            <!-- Share button - lets users share the post -->
                            <button onclick="sharePost(<?php echo $post['id']; ?>)" class="action-btn">🔗 Share</button>
                        </div>
                        
                        <!-- Comments section (hidden by default) -->
                        <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display:none;">
                            <!-- This div will be filled with comments using JavaScript -->
                            <div class="comments-list"></div>
                            
                            <!-- Input area to write a new comment -->
                            <div class="comment-input">
                                <!-- Text box to type the comment -->
                                <input type="text" placeholder="Write a comment..." id="comment-input-<?php echo $post['id']; ?>">
                                
                                <!-- Button to send the comment -->
                                <button onclick="addComment(<?php echo $post['id']; ?>)">Send</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal/popup for creating a new post -->
    <div id="create-post-modal" class="modal">
        <!-- Content inside the modal -->
        <div class="modal-content">
            <!-- X button to close the modal -->
            <span class="close" onclick="closeModal('create-post-modal')">&times;</span>
            
            <!-- Title of the modal -->
            <h2>Create Post</h2>
            
            <!-- Form to submit a new post -->
            <form id="create-post-form" enctype="multipart/form-data">
                <!-- Text area where user types their post -->
                <textarea name="content" placeholder="What's on your mind?" required></textarea>
                
                <!-- File input to upload an image with the post -->
                <input type="file" name="image" accept="image/*">
                
                <!-- Submit button to post -->
                <button type="submit" class="btn-primary">Post</button>
            </form>
        </div>
    </div>

    <!-- Modal/popup for viewing friend requests -->
    <div id="friend-requests-modal" class="modal">
        <!-- Content inside the modal -->
        <div class="modal-content">
            <!-- X button to close the modal -->
            <span class="close" onclick="closeModal('friend-requests-modal')">&times;</span>
            
            <!-- Title of the modal -->
            <h2>Friend Requests</h2>
            
            <!-- This div will be filled with friend requests using JavaScript -->
            <div id="friend-requests-list"></div>
        </div>
    </div>

    <!-- Modal/popup for messaging friends -->
    <div id="messages-modal" class="modal">
        <!-- Content inside the modal (wider for chat layout) -->
        <div class="modal-content messages-modal-content">
            <!-- X button to close the modal -->
            <span class="close" onclick="closeModal('messages-modal')">&times;</span>
            
            <!-- Container for the messaging interface -->
            <div class="messages-container">
                <!-- Left side: List of friends to chat with -->
                <div class="friends-list">
                    <!-- Title -->
                    <h3>Friends</h3>
                    
                    <!-- This div will be filled with friend list using JavaScript -->
                    <div id="friends-list"></div>
                </div>
                
                <!-- Right side: Chat area -->
                <div class="chat-area">
                    <!-- Top of chat showing who you're talking to -->
                    <div class="chat-header">
                        <!-- Name of the friend you're chatting with -->
                        <h3 id="chat-username">Select a friend to chat</h3>
                        
                        <!-- Button to change chat theme (hidden until you select a friend) -->
                        <button id="theme-btn" onclick="showThemeSelector()" style="display:none;">🎨</button>
                        
                        <!-- Button to delete the entire chat history (hidden until you select a friend) -->
                        <button id="delete-chat-btn" onclick="deleteChat()" style="display:none;">🗑️</button>
                    </div>
                    
                    <!-- Area where all the messages will appear -->
                    <div id="chat-messages" class="chat-messages"></div>
                    
                    <!-- Bottom section where you type and send messages -->
                    <div class="chat-input">
                        <!-- Button to open emoji picker -->
                        <button onclick="showEmojiPicker()">😊</button>
                        
                        <!-- Input box to type your message -->
                        <input type="text" id="message-input" placeholder="Type a message..." disabled>
                        
                        <!-- Button to send the message -->
                        <button onclick="sendMessage()" id="send-btn" disabled>Send</button>
                    </div>
                    
                    <!-- Emoji picker popup (hidden by default) -->
                    <div id="emoji-picker" class="emoji-picker" style="display:none;">
                        <!-- Each emoji is clickable to insert it into the message -->
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
            
            <!-- Theme selector popup (hidden by default) -->
            <div id="theme-selector" class="theme-selector" style="display:none;">
                <!-- Title -->
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

    <!-- Load the JavaScript file that makes everything interactive -->
    <script src="js/dashboard.js"></script>
</body>
</html>