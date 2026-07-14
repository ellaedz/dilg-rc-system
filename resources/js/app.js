import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('[data-app-shell]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const closeTargets = document.querySelectorAll('[data-sidebar-close]');

    if (!shell || !toggle) return;

    const setOpen = (open) => {
        shell.classList.toggle('sidebar-open', open);
        document.body.classList.toggle('sidebar-locked', open);
        toggle.setAttribute('aria-expanded', String(open));
    };

    toggle.addEventListener('click', () => setOpen(!shell.classList.contains('sidebar-open')));
    closeTargets.forEach((target) => target.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) setOpen(false);
    });
});
