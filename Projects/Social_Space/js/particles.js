// Get the canvas element where we'll draw the particles
const canvas = document.getElementById('particles-canvas');
// Get the drawing context (like a paintbrush) for the canvas
const ctx = canvas.getContext('2d');

// Make the canvas fill the whole browser window
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

// Create an empty array to store all our particles
const particles = [];
// Decide how many particles we want (100 floating dots)
const particleCount = 100;

// This is a blueprint for creating particles
class Particle {
    // When we create a new particle, set up its properties
    constructor() {
        // Put it at a random position on the screen (X coordinate)
        this.x = Math.random() * canvas.width;
        // Put it at a random position on the screen (Y coordinate)
        this.y = Math.random() * canvas.height;
        // Give it a random size between 1 and 4 pixels
        this.size = Math.random() * 3 + 1;
        // Give it a random horizontal speed (can move left or right)
        this.speedX = Math.random() * 2 - 1;
        // Give it a random vertical speed (can move up or down)
        this.speedY = Math.random() * 2 - 1;
        // Give it a green color with random transparency
        this.color = `rgba(16, 185, 129, ${Math.random() * 0.5 + 0.2})`;
    }

    // Move the particle
    update() {
        // Move it horizontally based on its speed
        this.x += this.speedX;
        // Move it vertically based on its speed
        this.y += this.speedY;

        // If particle goes off the right edge, bring it back on the left
        if (this.x > canvas.width) this.x = 0;
        // If particle goes off the left edge, bring it back on the right
        if (this.x < 0) this.x = canvas.width;
        // If particle goes off the bottom, bring it back on the top
        if (this.y > canvas.height) this.y = 0;
        // If particle goes off the top, bring it back on the bottom
        if (this.y < 0) this.y = canvas.height;
    }

    // Draw the particle on the canvas
    draw() {
        // Set the color for this particle
        ctx.fillStyle = this.color;
        // Start drawing a shape
        ctx.beginPath();
        // Draw a circle at the particle's position
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        // Fill the circle with color
        ctx.fill();
    }
}

// Create all the particles when the page loads
function init() {
    // Loop 100 times
    for (let i = 0; i < particleCount; i++) {
        // Create a new particle and add it to our array
        particles.push(new Particle());
    }
}

// Draw lines between particles that are close to each other
function connectParticles() {
    // Loop through each particle
    for (let i = 0; i < particles.length; i++) {
        // Compare it with every other particle
        for (let j = i + 1; j < particles.length; j++) {
            // Calculate horizontal distance between the two particles
            const dx = particles[i].x - particles[j].x;
            // Calculate vertical distance between the two particles
            const dy = particles[i].y - particles[j].y;
            // Calculate the actual distance using Pythagorean theorem
            const distance = Math.sqrt(dx * dx + dy * dy);

            // If particles are close enough (less than 120 pixels apart)
            if (distance < 120) {
                // Set line color - gets more transparent as distance increases
                ctx.strokeStyle = `rgba(16, 185, 129, ${0.2 - distance / 600})`;
                // Set line thickness to 1 pixel
                ctx.lineWidth = 1;
                // Start drawing a line
                ctx.beginPath();
                // Start the line at the first particle
                ctx.moveTo(particles[i].x, particles[i].y);
                // Draw the line to the second particle
                ctx.lineTo(particles[j].x, particles[j].y);
                // Actually draw the line on the canvas
                ctx.stroke();
            }
        }
    }
}

// This function runs over and over to create animation
function animate() {
    // Clear the entire canvas (erase everything)
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Loop through all particles
    for (let i = 0; i < particles.length; i++) {
        // Move this particle
        particles[i].update();
        // Draw this particle
        particles[i].draw();
    }
    
    // Draw lines connecting nearby particles
    connectParticles();
    // Call this function again on the next frame (creates smooth animation)
    requestAnimationFrame(animate);
}

// When the browser window is resized
window.addEventListener('resize', () => {
    // Update canvas width to match new window size
    canvas.width = window.innerWidth;
    // Update canvas height to match new window size
    canvas.height = window.innerHeight;
});

// Create all the particles
init();
// Start the animation loop
animate();