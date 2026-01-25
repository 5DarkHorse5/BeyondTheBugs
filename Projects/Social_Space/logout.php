<?php
// Start the session so we can access the user's login information
session_start();

// Destroy the session - this logs the user out by deleting all their login info
session_destroy();

// Send the user back to the login page
header("Location: index.php");

// Stopping the code here - don't run anything else
exit();
?>