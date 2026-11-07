export function indicatorPosition(target, rail) {
    return { x: target.left - rail.left, y: target.top - rail.top, width: target.width, height: target.height };
}

export function initializeNavbar(document) {
    const rail = document.querySelector('[data-nav-rail]');
    const indicator = rail?.querySelector('[data-nav-indicator]');
    if (!rail || !indicator) return;

    const controls = [...rail.querySelectorAll(':scope > a, :scope > details > summary')];
    const active = controls.find((control) => control.getAttribute('aria-current') === 'page');
    const window = document.defaultView;
    const move = (target) => {
        if (!target || !rail.getBoundingClientRect().width) return;
        const box = indicatorPosition(target.getBoundingClientRect(), rail.getBoundingClientRect());
        indicator.style.width = `${box.width}px`;
        indicator.style.height = `${box.height}px`;
        indicator.style.transform = `translate(${box.x}px, ${box.y}px)`;
        controls.forEach((control) => control.toggleAttribute('data-nav-highlight', control === target));
        rail.setAttribute('data-indicator-ready', '');
    };
    const restore = () => {
        const opened = controls.find((control) => control.parentElement.matches('details[open]'));
        move(opened || active);
    };

    controls.forEach((control) => {
        control.addEventListener('pointerenter', () => move(control));
        control.addEventListener('focus', () => move(control));
        control.addEventListener('click', () => move(control));
    });
    rail.addEventListener('pointerleave', restore);
    rail.addEventListener('focusout', (event) => {
        if (!rail.contains(event.relatedTarget)) restore();
    });
    rail.addEventListener('toggle', restore, true);
    move(active || controls[0]);
    window.requestAnimationFrame(() => rail.setAttribute('data-indicator-animated', ''));
    window.addEventListener('resize', restore, { passive: true });
    document.fonts?.ready.then(restore);
}
