export function initializeMobileNavbar(document) {
    const button = document.querySelector('[data-mobile-nav-toggle]');
    const panel = document.querySelector('[data-mobile-nav-panel]');
    const navbar = document.querySelector('[data-app-navbar]');
    const header = document.querySelector('[data-nav-header]');
    if (!button || !panel || !navbar || !header) return;
    const window = document.defaultView;
    let animation;
    let expanded = false;

    const setExpanded = (next, immediate = false) => {
        const from = panel.hidden ? 0 : panel.getBoundingClientRect().height;
        animation?.cancel();
        expanded = next;
        button.setAttribute('aria-expanded', String(next));
        button.setAttribute('aria-label', next ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        panel.inert = !next;
        if (next) panel.hidden = false;
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (immediate || reduced || !panel.animate) {
            panel.hidden = !next;
            return;
        }
        const to = next ? Math.min(panel.scrollHeight, Math.max(0, window.innerHeight - header.getBoundingClientRect().height)) : 0;
        const current = panel.animate([
            { height: `${from}px`, opacity: next ? 0 : 1 },
            { height: `${to}px`, opacity: next ? 1 : 0 },
        ], { duration: 160, easing: 'ease-out' });
        animation = current;
        current.finished.then(() => {
            if (animation !== current) return;
            panel.hidden = !expanded;
            animation = null;
        }, () => {});
    };
    const updateHeight = () => {
        navbar.style.setProperty('--navbar-header-height', `${header.getBoundingClientRect().height}px`);
        if (window.matchMedia('(min-width: 1280px)').matches) setExpanded(false, true);
    };
    button.addEventListener('click', () => setExpanded(!expanded));
    document.addEventListener('click', (event) => {
        if (expanded && !navbar.contains(event.target)) setExpanded(false);
    });
    document.addEventListener('keydown', (event) => {
        if (expanded && event.key === 'Escape') {
            setExpanded(false);
            button.focus();
        }
    });
    window.addEventListener('resize', updateHeight, { passive: true });
    if (window.ResizeObserver) new window.ResizeObserver(updateHeight).observe(header);
    updateHeight();
}
