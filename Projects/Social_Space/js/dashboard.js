// Store which friend we're currently chatting with (starts as null)
let currentFriend = null;

// Store the timer that refreshes messages (starts as null)
let messageInterval = null;

// ========================================
// MODAL FUNCTIONS (Popups)
// ========================================

// Show the create post popup
function showCreatePost() {
    document.getElementById('create-post-modal').classList.add('active');
}

// Show the friend requests popup
function showFriendRequests() {
    document.getElementById('friend-requests-modal').classList.add('active');
    // Load friend requests when popup opens
    loadFriendRequests();
}

// Show the messages popup
function showMessages() {
    document.getElementById('messages-modal').classList.add('active');
    // Load friends list when popup opens
    loadFriends();
}

// Close any popup/modal
function closeModal(modalId) {
    // Remove the 'active' class to hide the modal
    document.getElementById(modalId).classList.remove('active');
    
    // If we're closing the messages modal and have an active message refresh timer
    if (modalId === 'messages-modal' && messageInterval) {
        // Stop the timer from refreshing messages
        clearInterval(messageInterval);
        // Reset the timer to null
        messageInterval = null;
    }
}

// ========================================
// CREATE POST
// ========================================

// When the create post form is submitted
document.getElementById('create-post-form').addEventListener('submit', async (e) => {
    // Stop the form from refreshing the page
    e.preventDefault();
    
    // Get all the form data (text and image)
    const formData = new FormData(e.target);
    // Add an action to tell the server we want to create a post
    formData.append('action', 'create');
    
    try {
        // Send the post data to the server
        const response = await fetch('api/posts.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response from the server
        const data = await response.json();
        
        // Check if the post was created successfully
        if (data.success) {
            alert('Post created successfully!');
            // Close the create post popup
            closeModal('create-post-modal');
            // Refresh the page to show the new post
            location.reload();
        } else {
            // Show error message from server
            alert(data.message);
        }
    } catch (error) {
        // If something went wrong, log it and show an error
        console.error('Error:', error);
        alert('Failed to create post');
    }
});

// ========================================
// DELETE POST
// ========================================

// Delete a post
async function deletePost(postId) {
    // Ask the user if they're sure they want to delete
    if (!confirm('Are you sure you want to delete this post?')) return;
    
    // Create form data with the post ID
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('post_id', postId);
    
    try {
        // Send delete request to the server
        const response = await fetch('api/posts.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If deleted successfully
        if (data.success) {
            // Remove the post from the page
            document.querySelector(`[data-post-id="${postId}"]`).remove();
        } else {
            // Show error message
            alert(data.message);
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// ========================================
// LIKE POST
// ========================================

// Like or unlike a post
async function likePost(postId) {
    // Create form data with the post ID
    const formData = new FormData();
    formData.append('action', 'like');
    formData.append('post_id', postId);
    
    try {
        // Send like request to the server
        const response = await fetch('api/posts.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If the like/unlike was successful
        if (data.success) {
            // Find the post on the page
            const postCard = document.querySelector(`[data-post-id="${postId}"]`);
            // Find the like button
            const likeBtn = postCard.querySelector('.action-btn');
            // Find the likes count text
            const likesCount = postCard.querySelector('.post-stats span:first-child');
            
            // If the user just liked the post
            if (data.liked) {
                // Add the 'liked' class to make the button look different
                likeBtn.classList.add('liked');
            } else {
                // Remove the 'liked' class (user unliked)
                likeBtn.classList.remove('liked');
            }
            
            // Update the likes count on the page
            likesCount.textContent = `${data.count} likes`;
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// ========================================
// COMMENTS
// ========================================

// Show or hide the comments section
async function toggleComments(postId) {
    // Find the comments section for this post
    const commentsSection = document.getElementById(`comments-${postId}`);
    
    // If comments are currently hidden
    if (commentsSection.style.display === 'none') {
        // Show the comments
        commentsSection.style.display = 'block';
        // Load the comments from the server
        await loadComments(postId);
    } else {
        // Hide the comments
        commentsSection.style.display = 'none';
    }
}

// Load all comments for a post from the server
async function loadComments(postId) {
    try {
        // Ask the server for the comments
        const response = await fetch(`api/posts.php?action=get_comments&post_id=${postId}`);
        // Get the response
        const data = await response.json();
        
        // If we got the comments successfully
        if (data.success) {
            // Find where we want to put the comments
            const commentsList = document.querySelector(`#comments-${postId} .comments-list`);
            // Clear out any old comments
            commentsList.innerHTML = '';
            
            // Loop through each comment
            data.comments.forEach(comment => {
                // Create a new div for this comment
                const commentDiv = document.createElement('div');
                commentDiv.className = 'comment-item';
                // Fill it with the comment HTML
                commentDiv.innerHTML = `
                    <img src="uploads/profiles/${comment.profile_pic}" alt="Profile" class="comment-avatar">
                    <div class="comment-content">
                        <div class="comment-header">
                            <span class="comment-author">${comment.full_name}</span>
                            <span class="comment-time">${comment.time_ago}</span>
                        </div>
                        <p class="comment-text">${comment.comment}</p>
                        ${comment.can_delete ? `<button class="comment-delete" onclick="deleteComment(${comment.id}, ${postId})">Delete</button>` : ''}
                    </div>
                `;
                // Add this comment to the list
                commentsList.appendChild(commentDiv);
            });
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// Add a new comment to a post
async function addComment(postId) {
    // Get the comment input box for this post
    const input = document.getElementById(`comment-input-${postId}`);
    // Get what the user typed and remove extra spaces
    const comment = input.value.trim();
    
    // If the comment is empty, don't do anything
    if (!comment) return;
    
    // Create form data with the comment
    const formData = new FormData();
    formData.append('action', 'comment');
    formData.append('post_id', postId);
    formData.append('comment', comment);
    
    try {
        // Send the comment to the server
        const response = await fetch('api/posts.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If comment was added successfully
        if (data.success) {
            // Clear the input box
            input.value = '';
            // Reload all comments to show the new one
            await loadComments(postId);
            
            // Find the post and update the comment count
            const postCard = document.querySelector(`[data-post-id="${postId}"]`);
            const commentsCount = postCard.querySelector('.post-stats span:last-child');
            const currentCount = parseInt(commentsCount.textContent);
            commentsCount.textContent = `${currentCount + 1} comments`;
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// Delete a comment
async function deleteComment(commentId, postId) {
    // Ask user if they're sure
    if (!confirm('Are you sure you want to delete this comment?')) return;
    
    // Create form data with the comment ID
    const formData = new FormData();
    formData.append('action', 'delete_comment');
    formData.append('comment_id', commentId);
    
    try {
        // Send delete request to server
        const response = await fetch('api/posts.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If deleted successfully
        if (data.success) {
            // Reload comments to remove the deleted one
            await loadComments(postId);
            
            // Update the comments count (subtract 1)
            const postCard = document.querySelector(`[data-post-id="${postId}"]`);
            const commentsCount = postCard.querySelector('.post-stats span:last-child');
            const currentCount = parseInt(commentsCount.textContent);
            // Make sure count doesn't go below 0
            commentsCount.textContent = `${Math.max(0, currentCount - 1)} comments`;
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// ========================================
// SHARE POST
// ========================================

// Share a post
async function sharePost(postId) {
    // Create form data with the post ID
    const formData = new FormData();
    formData.append('action', 'share');
    formData.append('post_id', postId);
    
    try {
        // Send share request to server
        const response = await fetch('api/posts.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // Show success or error message
        if (data.success) {
            alert('Post shared successfully!');
        } else {
            alert(data.message);
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// ========================================
// SEARCH USERS
// ========================================

// Store the search delay timer
let searchTimeout = null;
// Get the search input box
const searchInput = document.getElementById('search-users');
// Get the search results container
const searchResults = document.getElementById('search-results');

// When user types in the search box
searchInput.addEventListener('input', (e) => {
    // Cancel any previous search timer
    clearTimeout(searchTimeout);
    
    // Get what the user typed
    const query = e.target.value.trim();
    
    // If they typed less than 2 characters
    if (query.length < 2) {
        // Hide the search results
        searchResults.classList.remove('active');
        return; // Stop here
    }
    
    // Wait 300 milliseconds before searching (so we don't search on every keystroke)
    searchTimeout = setTimeout(async () => {
        try {
            // Search for users
            const response = await fetch(`api/search.php?query=${encodeURIComponent(query)}`);
            // Get the results
            const data = await response.json();
            
            // If search was successful
            if (data.success) {
                // Display the users we found
                displaySearchResults(data.users);
            }
        } catch (error) {
            // Log any errors
            console.error('Error:', error);
        }
    }, 300);
});

// Display search results on the page
function displaySearchResults(users) {
    // If no users were found
    if (users.length === 0) {
        searchResults.innerHTML = '<div style="padding: 20px; text-align: center; color: #cbd5e1;">No users found</div>';
        searchResults.classList.add('active');
        return; // Stop here
    }
    
    // Clear previous results
    searchResults.innerHTML = '';
    
    // Loop through each user
    users.forEach(user => {
        // Create a div for this user
        const div = document.createElement('div');
        div.className = 'search-result-item';
        
        // Decide what button to show based on friendship status
        let buttonHTML = '';
        if (user.friendship_status === 'friend') {
            // Already friends - show unfriend button
            buttonHTML = '<button class="search-result-btn" onclick="unfriendUser(' + user.id + ')">Unfriend</button>';
        } else if (user.friendship_status === 'pending_sent') {
            // You sent them a request - show pending
            buttonHTML = '<button class="search-result-btn pending">Pending</button>';
        } else if (user.friendship_status === 'pending_received') {
            // They sent you a request - show respond button
            buttonHTML = '<button class="search-result-btn">Respond</button>';
        } else {
            // Not friends - show add friend button
            buttonHTML = '<button class="search-result-btn" onclick="sendFriendRequest(' + user.id + ')">Add Friend</button>';
        }
        
        // Fill the div with user info
        div.innerHTML = `
            <img src="uploads/profiles/${user.profile_pic}" alt="Profile">
            <div class="search-result-info">
                <h4>${user.full_name}</h4>
                <p>@${user.username}</p>
            </div>
            ${buttonHTML}
        `;
        
        // Add this user to the results
        searchResults.appendChild(div);
    });
    
    // Show the search results
    searchResults.classList.add('active');
}

// When user clicks anywhere on the page
document.addEventListener('click', (e) => {
    // If they didn't click on the search box or results
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        // Hide the search results
        searchResults.classList.remove('active');
    }
});

// ========================================
// FRIEND REQUESTS
// ========================================

// Send a friend request to someone
async function sendFriendRequest(friendId) {
    // Create form data with their user ID
    const formData = new FormData();
    formData.append('action', 'send_request');
    formData.append('friend_id', friendId);
    
    try {
        // Send the request to the server
        const response = await fetch('api/friends.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If request was sent successfully
        if (data.success) {
            alert('Friend request sent!');
            // Clear the search box
            searchInput.value = '';
            // Hide the search results
            searchResults.classList.remove('active');
        } else {
            // Show error message
            alert(data.message);
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// Remove someone from your friends list
async function unfriendUser(friendId) {
    // Ask if they're sure
    if (!confirm('Are you sure you want to remove this friend?')) return;
    
    // Create form data with their user ID
    const formData = new FormData();
    formData.append('action', 'unfriend');
    formData.append('friend_id', friendId);
    
    try {
        // Send unfriend request to server
        const response = await fetch('api/friends.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If unfriended successfully
        if (data.success) {
            alert('Friend removed');
            // Clear the search box
            searchInput.value = '';
            // Hide the search results
            searchResults.classList.remove('active');
        } else {
            // Show error message
            alert(data.message);
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// Load all pending friend requests
async function loadFriendRequests() {
    try {
        // Ask server for friend requests
        const response = await fetch('api/friends.php?action=get_requests');
        // Get the response
        const data = await response.json();
        
        // If we got the requests successfully
        if (data.success) {
            // Find where to put the requests
            const list = document.getElementById('friend-requests-list');
            
            // If there are no pending requests
            if (data.requests.length === 0) {
                list.innerHTML = '<p style="text-align: center; color: #cbd5e1;">No pending requests</p>';
                return; // Stop here
            }
            
            // Clear the list
            list.innerHTML = '';
            
            // Loop through each request
            data.requests.forEach(request => {
                // Create a div for this request
                const div = document.createElement('div');
                div.className = 'friend-request-item';
                // Fill it with the request info
                div.innerHTML = `
                    <img src="uploads/profiles/${request.profile_pic}" alt="Profile">
                    <div class="friend-request-info">
                        <h4>${request.full_name}</h4>
                        <p>@${request.username}</p>
                    </div>
                    <div class="friend-request-actions">
                        <button class="accept-btn" onclick="acceptRequest(${request.id})">Accept</button>
                        <button class="reject-btn" onclick="rejectRequest(${request.id})">Reject</button>
                    </div>
                `;
                // Add this request to the list
                list.appendChild(div);
            });
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// Accept a friend request
async function acceptRequest(requestId) {
    // Create form data with the request ID
    const formData = new FormData();
    formData.append('action', 'accept_request');
    formData.append('request_id', requestId);
    
    try {
        // Send accept request to server
        const response = await fetch('api/friends.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If accepted successfully
        if (data.success) {
            // Reload the friend requests list
            loadFriendRequests();
        } else {
            // Show error message
            alert(data.message);
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// Reject a friend request
async function rejectRequest(requestId) {
    // Create form data with the request ID
    const formData = new FormData();
    formData.append('action', 'reject_request');
    formData.append('request_id', requestId);
    
    try {
        // Send reject request to server
        const response = await fetch('api/friends.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If rejected successfully
        if (data.success) {
            // Reload the friend requests list
            loadFriendRequests();
        } else {
            // Show error message
            alert(data.message);
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// ========================================
// MESSAGING
// ========================================

// Load all your friends for messaging
async function loadFriends() {
    try {
        // Ask server for friends list
        const response = await fetch('api/friends.php?action=get_friends');
        // Get the response
        const data = await response.json();
        
        // If we got the friends successfully
        if (data.success) {
            // Find where to put the friends
            const list = document.getElementById('friends-list');
            
            // If you have no friends yet
            if (data.friends.length === 0) {
                list.innerHTML = '<p style="text-align: center; color: #cbd5e1; padding: 20px;">No friends yet</p>';
                return; // Stop here
            }
            
            // Clear the list
            list.innerHTML = '';
            
            // Loop through each friend
            data.friends.forEach(friend => {
                // Create a div for this friend
                const div = document.createElement('div');
                div.className = 'friend-item';
                // When clicked, select this friend to chat with
                div.onclick = () => selectFriend(friend);
                // Fill it with friend info
                div.innerHTML = `
                    <img src="uploads/profiles/${friend.profile_pic}" alt="Profile">
                    <div>
                        <h4>${friend.full_name}</h4>
                        <p style="font-size: 12px; color: #cbd5e1;">@${friend.username}</p>
                    </div>
                `;
                // Add this friend to the list
                list.appendChild(div);
            });
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// When user clicks on a friend to chat with them
async function selectFriend(friend) {
    // Save this friend as the current chat friend
    currentFriend = friend;
    
    // Remove 'active' class from all friend items
    document.querySelectorAll('.friend-item').forEach(item => {
        item.classList.remove('active');
    });
    // Add 'active' class to the clicked friend
    event.target.closest('.friend-item').classList.add('active');
    
    // Show friend's name at top of chat
    document.getElementById('chat-username').textContent = friend.full_name;
    // Enable the message input box
    document.getElementById('message-input').disabled = false;
    // Enable the send button
    document.getElementById('send-btn').disabled = false;
    // Show the theme button
    document.getElementById('theme-btn').style.display = 'inline-block';
    // Show the delete chat button
    document.getElementById('delete-chat-btn').style.display = 'inline-block';
    
    // Load the messages with this friend
    await loadMessages();
    // Load the chat theme
    await loadTheme();
    
    // If there's already a message refresh timer, stop it
    if (messageInterval) clearInterval(messageInterval);
    // Start a new timer to refresh messages every 3 seconds
    messageInterval = setInterval(loadMessages, 3000);
}

// Load all messages with the current friend
async function loadMessages() {
    // If no friend is selected, do nothing
    if (!currentFriend) return;
    
    try {
        // Ask server for messages
        const response = await fetch(`api/messages.php?action=get&friend_id=${currentFriend.id}`);
        // Get the response
        const data = await response.json();
        
        // If we got the messages successfully
        if (data.success) {
            // Find the chat messages area
            const chatMessages = document.getElementById('chat-messages');
            // Clear old messages
            chatMessages.innerHTML = '';
            
            // Loop through each message
            data.messages.forEach(msg => {
                // Create a div for this message
                const div = document.createElement('div');
                // Add 'mine' class if this is your message
                div.className = `message-item ${msg.is_mine ? 'mine' : ''}`;
                // Fill it with message content
                div.innerHTML = `
                    <div class="message-bubble">
                        <div class="message-text">${msg.message}</div>
                        <div class="message-time">
                            ${msg.time_ago}
                            ${msg.is_mine ? `<button class="message-delete" onclick="deleteMessage(${msg.id})">Delete</button>` : ''}
                        </div>
                    </div>
                `;
                // Add this message to the chat
                chatMessages.appendChild(div);
            });
            
            // Scroll to the bottom to show newest messages
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// Send a message to the current friend
async function sendMessage() {
    // If no friend is selected, do nothing
    if (!currentFriend) return;
    
    // Get the message input box
    const input = document.getElementById('message-input');
    // Get what the user typed
    const message = input.value.trim();
    
    // If message is empty, do nothing
    if (!message) return;
    
    // Create form data with the message
    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('receiver_id', currentFriend.id);
    formData.append('message', message);
    
    try {
        // Send the message to the server
        const response = await fetch('api/messages.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If message was sent successfully
        if (data.success) {
            // Clear the input box
            input.value = '';
            // Reload messages to show the new one
            await loadMessages();
        } else {
            // Show error message
            alert(data.message);
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// When user presses Enter in the message box
document.getElementById('message-input').addEventListener('keypress', (e) => {
    // If they pressed Enter
    if (e.key === 'Enter') {
        // Send the message
        sendMessage();
    }
});

// Delete a single message
async function deleteMessage(messageId) {
    // Create form data with the message ID
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('message_id', messageId);
    
    try {
        // Send delete request to server
        const response = await fetch('api/messages.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If deleted successfully
        if (data.success) {
            // Reload messages to remove the deleted one
            await loadMessages();
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// Delete entire chat history with a friend
async function deleteChat() {
    // If no friend is selected, do nothing
    if (!currentFriend) return;
    // Ask user if they're sure
    if (!confirm('Are you sure you want to delete this entire chat history?')) return;
    
    // Create form data with friend ID
    const formData = new FormData();
    formData.append('action', 'delete_chat');
    formData.append('friend_id', currentFriend.id);
    
    try {
        // Send delete request to server
        const response = await fetch('api/messages.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If deleted successfully
        if (data.success) {
            // Reload messages (will be empty now)
            await loadMessages();
            alert('Chat history deleted');
        } else {
            // Show error message
            alert(data.message);
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// ========================================
// EMOJI PICKER
// ========================================

// Show or hide the emoji picker
function showEmojiPicker() {
    // Get the emoji picker
    const picker = document.getElementById('emoji-picker');
    // If it's hidden, show it. If it's showing, hide it
    picker.style.display = picker.style.display === 'none' ? 'flex' : 'none';
}

// Add an emoji to the message input
function insertEmoji(emoji) {
    // Get the message input box
    const input = document.getElementById('message-input');
    // Add the emoji to whatever is already typed
    input.value += emoji;
    // Put cursor back in the input box
    input.focus();
    // Hide the emoji picker
    document.getElementById('emoji-picker').style.display = 'none';
}

// ========================================
// CHAT THEMES
// ========================================

// Show or hide the theme selector
function showThemeSelector() {
    // Get the theme selector
    const selector = document.getElementById('theme-selector');
    // If it's hidden, show it. If it's showing, hide it
    selector.style.display = selector.style.display === 'none' ? 'block' : 'none';
}

// Set a chat theme
async function setTheme(theme) {
    // If no friend is selected, do nothing
    if (!currentFriend) return;
    
    // Create form data with the theme
    const formData = new FormData();
    formData.append('action', 'set_theme');
    formData.append('friend_id', currentFriend.id);
    formData.append('theme', theme);
    
    try {
        // Send theme to server
        const response = await fetch('api/messages.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response
        const data = await response.json();
        
        // If theme was set successfully
        if (data.success) {
            // Apply the theme to the chat
            applyTheme(theme);
            // Hide the theme selector
            document.getElementById('theme-selector').style.display = 'none';
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// Load the saved theme for current chat
async function loadTheme() {
    // If no friend is selected, do nothing
    if (!currentFriend) return;
    
    try {
        // Ask server for the theme
        const response = await fetch(`api/messages.php?action=get_theme&friend_id=${currentFriend.id}`);
        // Get the response
        const data = await response.json();
        
        // If we got the theme successfully
        if (data.success) {
            // Apply the theme
            applyTheme(data.theme);
        }
    } catch (error) {
        // Log any errors
        console.error('Error:', error);
    }
}

// Apply a theme to the chat background
function applyTheme(theme) {
    // Get the chat messages area
    const chatMessages = document.getElementById('chat-messages');
    // Reset to default styling
    chatMessages.className = 'chat-messages';
    // If theme is not default
    if (theme !== 'default') {
        // Add the theme class (like 'theme-doodle' or 'theme-gradient')
        chatMessages
        chatMessages.classList.add(`theme-${theme}`);
    }
}