export function initializeDropdowns(document) {
    const openDropdowns = () => document.querySelectorAll('details[data-dropdown][open]');
    const closing = new WeakMap();

    const cancelClose = (dropdown) => {
        const pending = closing.get(dropdown);
        if (pending) {
            closing.delete(dropdown);
            pending.animation.cancel();
            pending.panel.style.pointerEvents = pending.pointerEvents;
        }
    };

    const closeDropdown = (dropdown) => {
        if (!dropdown.open || closing.has(dropdown)) {
            return;
        }
        const panel = dropdown.querySelector(':scope > :not(summary)');
        if (!panel?.animate || document.defaultView?.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            dropdown.open = false;
            return;
        }

        const currentStyle = document.defaultView?.getComputedStyle?.(panel);
        const animation = panel.animate([
            { opacity: currentStyle?.opacity || '1', transform: currentStyle?.transform || 'translateY(0)' },
            { opacity: 0, transform: 'translateY(-3px)' },
        ], { duration: 120, easing: 'ease-in', fill: 'forwards' });
        const pending = { animation, panel, pointerEvents: panel.style.pointerEvents };
        closing.set(dropdown, pending);
        panel.style.pointerEvents = 'none';

        const finishClose = () => {
            if (closing.get(dropdown) === pending) {
                dropdown.open = false;
                cancelClose(dropdown);
            }
        };
        animation.finished.then(finishClose, finishClose);
    };

    document.addEventListener('click', (event) => {
        const summary = event.target.closest?.('summary');
        const selected = summary?.parentElement;
        if (selected?.matches('details[data-dropdown]') && selected.open) {
            event.preventDefault();
            if (closing.has(selected)) {
                cancelClose(selected);
            } else {
                closeDropdown(selected);
            }
        }
        openDropdowns().forEach((dropdown) => {
            if (!dropdown.contains(event.target)) {
                closeDropdown(dropdown);
            }
        });
    });

    // Capture toggle events so menus inserted by page filters work as well.
    document.addEventListener('toggle', (event) => {
        const current = event.target;
        if (!current.matches?.('details[data-dropdown]') || !current.open) {
            return;
        }
        openDropdowns().forEach((dropdown) => {
            if (dropdown !== current && !dropdown.contains(current)) {
                closeDropdown(dropdown);
            }
        });
    }, true);

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        openDropdowns().forEach((dropdown) => {
            closeDropdown(dropdown);
            dropdown.querySelector('summary')?.focus();
        });
    });
}
