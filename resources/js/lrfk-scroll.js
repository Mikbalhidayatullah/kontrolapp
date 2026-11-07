export function optimizeFrozenRows(viewport, window) {
    if (!window.IntersectionObserver) return () => {};
    const rows = [...viewport.querySelectorAll('tbody > tr')];
    if (rows.length < 80) return () => {};

    // Keep a generous buffer so fast scrolling reaches already-frozen rows.
    const observer = new window.IntersectionObserver((records) => {
        records.forEach(({ target, isIntersecting }) => {
            target.toggleAttribute('data-frozen-visible', isIntersecting);
        });
        viewport.setAttribute('data-frozen-optimized', '');
    }, { root: viewport, rootMargin: '800px 0px', threshold: 0 });
    rows.forEach((row) => observer.observe(row));

    return () => {
        observer.disconnect();
        viewport.removeAttribute('data-frozen-optimized');
        rows.forEach((row) => row.removeAttribute('data-frozen-visible'));
    };
}

export function initializeLrfkScroll(document) {
    const window = document.defaultView;
    const host = document.querySelector('[data-page-content]');
    if (!host?.querySelector('[data-lrfk-report]') || !window.IntersectionObserver) return;
    const viewports = new Map();
    const refresh = () => {
        viewports.forEach((cleanup, viewport) => {
            if (!viewport.isConnected) {
                cleanup();
                viewports.delete(viewport);
            }
        });
        host.querySelectorAll('.lrfk-table-scroll').forEach((viewport) => {
            if (!viewports.has(viewport)) {
                viewports.set(viewport, optimizeFrozenRows(viewport, window));
            }
        });
    };
    refresh();
    // Filter results replace the table without reloading the page.
    if (window.MutationObserver) {
        let scheduled = false;
        new window.MutationObserver((records) => {
            if (scheduled || !records.some((record) => record.addedNodes.length || record.removedNodes.length)) return;
            scheduled = true;
            window.requestAnimationFrame(() => {
                scheduled = false;
                refresh();
            });
        }).observe(host, { childList: true, subtree: true });
    }
}
