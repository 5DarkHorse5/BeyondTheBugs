<?php
// Bring in the database connection file so we can talk to the database
require_once 'config/database.php';

// Bring in the helper functions we need for this page
require_once 'includes/functions.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // If they're logged in, send them to the dashboard instead
    header("Location: dashboard.php");
    exit(); // Stop the rest of the code from running
}

// Create a variable to store any error messages (starts empty)
$error = '';

// Check if the form was submitted (user clicked the Login button)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the username from the form and clean it up
    $username = sanitize_input($_POST['username']);
    
    // Get the password from the form (don't clean this one, we need it as-is)
    $password = $_POST['password'];
    
    // Check if either field is empty
    if (empty($username) || empty($password)) {
        // Show an error message if they forgot to fill something in
        $error = "All fields are required";
    } else {
        // Prepare a query to look for this user in the database
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        
        // Run the query - check if username OR email matches what they typed
        $stmt->execute([$username, $username]);
        
        // Get the user's information from the database
        $user = $stmt->fetch();
        
        // Check if we found a user AND if the password is correct
        if ($user && password_verify($password, $user['password'])) {
            // Password is correct! Save their user ID in the session
            $_SESSION['user_id'] = $user['id'];
            
            // Also save their username in the session
            $_SESSION['username'] = $user['username'];
            
            // Send them to the dashboard
            header("Location: dashboard.php");
            exit(); // Stop the code here
        } else {
            // Username or password is wrong - show error message
            $error = "Invalid username or password";
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
    
    <!-- Set the title that shows in the browser tab -->
    <title>Social Space - Login</title>
    
    <!-- Load the main stylesheet for styling this page -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Canvas for the animated particle background effect -->
    <canvas id="particles-canvas"></canvas>
    
    <!-- Container that holds the login box in the center of the page -->
    <div class="auth-container">
        <!-- The actual login box -->
        <div class="auth-box">
            <!-- Big "Social Space" logo at the top -->
            <h1 class="logo">Social Space</h1>
            
            <!-- Tagline text under the logo -->
            <p class="tagline">Connect with friends around the world</p>
            
            <!-- Only show error message if there is one -->
            <?php if ($error): ?>
                <!-- Red box showing the error message -->
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- The login form - when submitted, it posts to this same page -->
            <form method="POST" action="">
                <!-- Username/Email input field -->
                <div class="input-group">
                    <!-- Input box where user types their username or email -->
                    <input type="text" name="username" placeholder="Username or Email" required>
                </div>
                
                <!-- Password input field with eye icon -->
                <div class="input-group password-group">
                    <!-- Input box where user types their password (hidden by default) -->
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    
                    <!-- Eye icon that lets you show/hide the password -->
                    <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                </div>
                
                <!-- Login button -->
                <button type="submit" class="btn-primary">Login</button>
            </form>
            
            <!-- Link to registration page for new users -->
            <p class="switch-auth">Don't have an account? <a href="register.php">Sign up</a></p>
        </div>
    </div>
    
    <!-- Load the JavaScript file that makes the particle animation work -->
    <script src="js/particles.js"></script>
    
    <!-- Load the JavaScript file that makes the password eye icon work -->
    <script src="js/auth.js"></script>
</body>
</html>