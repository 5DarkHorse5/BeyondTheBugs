// This function shows or hides a password when you click the eye icon
function togglePassword(inputId) {
    // Find the password input box using its ID
    const input = document.getElementById(inputId);
    
    // Check if the input is currently set to 'password' type (hidden)
    if (input.type === 'password') {
        // Change it to 'text' so the password becomes visible
        input.type = 'text';
    } else {
        // Change it back to 'password' so it's hidden again
        input.type = 'password';
    }
}