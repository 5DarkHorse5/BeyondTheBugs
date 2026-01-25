<?php
// Bring in the database connection file
require_once 'config/database.php';

// Bring in the helper functions we need
require_once 'includes/functions.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // If they're already logged in, send them to the dashboard
    header("Location: dashboard.php");
    exit(); // Stop running the rest of the code
}

// Create a variable to store error messages (starts empty)
$error = '';

// Create a variable to store success messages (starts empty)
$success = '';

// Check if the registration form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get all the form inputs and clean them up
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $full_name = sanitize_input($_POST['full_name']);
    
    // Get the passwords (don't clean these, we need them exactly as typed)
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Check if any field is empty
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = "All fields are required";
    
    // Check if the email is in a valid format (like user@example.com)
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    
    // Check if password is at least 6 characters long
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    
    // Check if both passwords match
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    
    // If all checks passed, continue with registration
    } else {
        // Check if this username or email already exists in the database
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        // If we found a matching user, they already exist
        if ($stmt->fetch()) {
            $error = "Username or email already exists";
        
        // No duplicate found, we can create the new account
        } else {
            // Encrypt the password so it's secure in the database
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepare the query to insert the new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, full_name, password) VALUES (?, ?, ?, ?)");
            
            // Try to save the new user to the database
            if ($stmt->execute([$username, $email, $full_name, $hashed_password])) {
                // Success! Show a success message
                $success = "Registration successful! You can now login.";
            } else {
                // Something went wrong with saving
                $error = "Registration failed. Please try again.";
            }
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
    <title>Social Space - Register</title>
    
    <!-- Load the main stylesheet -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Canvas for the animated particle background effect -->
    <canvas id="particles-canvas"></canvas>
    
    <!-- Container that holds the registration box in the center -->
    <div class="auth-container">
        <!-- The actual registration box -->
        <div class="auth-box">
            <!-- Big "Social Space" logo at the top -->
            <h1 class="logo">Social Space</h1>
            
            <!-- Tagline text under the logo -->
            <p class="tagline">Join our community today</p>
            
            <!-- Only show error message if there is one -->
            <?php if ($error): ?>
                <!-- Red box showing the error message -->
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Only show success message if there is one -->
            <?php if ($success): ?>
                <!-- Green box showing the success message -->
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Registration form - posts to this same page when submitted -->
            <form method="POST" action="">
                <!-- Full name input field -->
                <div class="input-group">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                </div>
                
                <!-- Username input field -->
                <div class="input-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                
                <!-- Email input field -->
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                
                <!-- Password input field with eye icon -->
                <div class="input-group password-group">
                    <!-- Input box for password (hidden by default) -->
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    
                    <!-- Eye icon that lets you show/hide the password -->
                    <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                </div>
                
                <!-- Confirm password input field with eye icon -->
                <div class="input-group password-group">
                    <!-- Input box to type password again for confirmation -->
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    
                    <!-- Eye icon that lets you show/hide this password -->
                    <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
                
                <!-- Register button -->
                <button type="submit" class="btn-primary">Register</button>
            </form>
            
            <!-- Link to login page for users who already have an account -->
            <p class="switch-auth">Already have an account? <a href="index.php">Login</a></p>
        </div>
    </div>
    
    <!-- Load the JavaScript file that makes the particle animation work -->
    <script src="js/particles.js"></script>
    
    <!-- Load the JavaScript file that makes the password eye icon work -->
    <script src="js/auth.js"></script>
</body>
</html>