// Show the edit profile popup
function showEditProfile() {
    // Find the edit profile modal and add 'active' class to show it
    document.getElementById('edit-profile-modal').classList.add('active');
}

// When the edit profile form is submitted
document.getElementById('edit-profile-form').addEventListener('submit', async (e) => {
    // Stop the form from refreshing the page
    e.preventDefault();
    
    // Get all the form data (profile picture, name, bio)
    const formData = new FormData(e.target);
    // Add an action to tell the server we want to update the profile
    formData.append('action', 'update');
    
    try {
        // Send the profile data to the server
        const response = await fetch('api/profile.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the response from the server
        const data = await response.json();
        
        // Check if the profile was updated successfully
        if (data.success) {
            alert('Profile updated successfully!');
            // Refresh the page to show the updated profile
            location.reload();
        } else {
            // Show error message from server
            alert(data.message);
        }
    } catch (error) {
        // If something went wrong, log it and show an error
        console.error('Error:', error);
        alert('Failed to update profile');
    }
});