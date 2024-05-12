document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    const content = document.getElementById('content');
    const inputs  = document.querySelectorAll('input');
    const textDarkToggle = document.querySelectorAll('.text-dark-toggle');

    // Check if dark mode preference is set
    const darkModePreference = window.matchMedia('(prefers-color-scheme: dark)').matches;

    // Function to toggle dark mode
    function toggleDarkMode() {
        content.classList.toggle('dark');
        inputs.forEach(input => input.classList.toggle('dark'));
        textDarkToggle.forEach(ele => ele.classList.toggle('dark'));
    }

    // Set initial dark mode state based on user preference
    if (darkModePreference) {
        toggleDarkMode();
    }

    // Event listener for dark mode toggle button
    darkModeToggle.addEventListener('click', function() {
        toggleDarkMode();
    });
});
