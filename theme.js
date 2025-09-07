function initTheme() {
    const modeToggle = document.querySelector('.mode-tog');
    const darkMode = document.querySelector('.dark-mode');

    if (!modeToggle || !darkMode) {
        console.warn("Theme toggle elements not found.");
        return;
    }

    const applyTheme = (theme) => {
        const isDark = theme === 'dark';
        darkMode.classList.toggle('active', isDark);
        modeToggle.classList.toggle('active', isDark);
        document.body.classList.toggle('dark', isDark);
    };

    const toggleTheme = () => {
        const isDark = !document.body.classList.contains('dark');
        localStorage.setItem('mode', isDark ? 'dark' : 'light');
        applyTheme(isDark ? 'dark' : 'light');
    };

    // Apply saved theme on initialization, preventing a flash of incorrect theme
    const savedMode = localStorage.getItem('mode');
    if (savedMode) {
        darkMode.classList.add('no-transition');
        applyTheme(savedMode);
        // Use requestAnimationFrame to remove the transition override after the first paint
        requestAnimationFrame(() => {
            darkMode.classList.remove('no-transition');
        });
    }

    // Ensure the event listener is attached only once to prevent issues.
    if (!modeToggle.dataset.themeInitialized) {
        modeToggle.addEventListener('click', toggleTheme);
        modeToggle.dataset.themeInitialized = 'true';
    }
}

// Run the theme initializer as soon as the DOM is interactive or already complete.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
} else {
    initTheme();
}
