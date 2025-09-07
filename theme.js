let modeToggle = document.querySelector('.mode-tog');
let darkMode = document.querySelector('.dark-mode');

let savedMode = localStorage.getItem('mode');
if (savedMode === 'dark') {
    darkMode.classList.add('no-transition');
    darkMode.classList.add('active');
    modeToggle.classList.add('active');
    document.body.classList.add('dark');

    setTimeout(() => {
        darkMode.classList.remove('no-transition');
    }, 0);
}

modeToggle.addEventListener('click', () => {
    darkMode.classList.toggle('active');
    modeToggle.classList.toggle('active');
    document.body.classList.toggle('dark');

    if (darkMode.classList.contains('active')) {
        localStorage.setItem('mode', 'dark');
    } else {
        localStorage.setItem('mode', 'light');
    }
});
