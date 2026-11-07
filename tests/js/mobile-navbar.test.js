import { test } from 'node:test';
import assert from 'node:assert/strict';
import { initializeMobileNavbar } from '../../resources/js/mobile-navbar.js';

function setup(reduced = true) {
    const events = {};
    const attributes = {};
    let click;
    let resize;
    let desktop = false;
    const button = { setAttribute: (key, value) => { attributes[key] = value; }, addEventListener: (_, fn) => { click = fn; }, focus() {} };
    const panel = { hidden: true, inert: true, scrollHeight: 800, getBoundingClientRect: () => ({ height: 400 }) };
    const navbar = { style: { setProperty() {} }, contains: (target) => target === button };
    const header = { getBoundingClientRect: () => ({ height: 80 }) };
    const elements = { '[data-mobile-nav-toggle]': button, '[data-mobile-nav-panel]': panel, '[data-app-navbar]': navbar, '[data-nav-header]': header };
    initializeMobileNavbar({
        querySelector: (selector) => elements[selector],
        addEventListener: (name, fn) => { events[name] = fn; },
        defaultView: { innerHeight: 500, matchMedia: (query) => ({ matches: query.includes('reduced') ? reduced : desktop }), addEventListener: (_, fn) => { resize = fn; } },
    });
    return { panel, attributes, events, click: () => click(), resize: () => { desktop = true; resize(); } };
}

test('hamburger opens and closes all menu content with correct accessibility state', () => {
    const state = setup();
    state.click();
    assert.equal(state.panel.hidden, false);
    assert.equal(state.panel.inert, false);
    assert.equal(state.attributes['aria-expanded'], 'true');
    state.click();
    assert.equal(state.panel.hidden, true);
    assert.equal(state.panel.inert, true);
});

test('outside click and Escape dismiss the mobile menu', () => {
    const state = setup();
    state.click();
    state.events.click({ target: {} });
    assert.equal(state.panel.hidden, true);
    state.click();
    state.events.keydown({ key: 'Escape' });
    assert.equal(state.panel.hidden, true);
});

test('switching to desktop resets the mobile menu', () => {
    const state = setup();
    state.click();
    state.resize();
    assert.equal(state.panel.hidden, true);
    assert.equal(state.attributes['aria-expanded'], 'false');
});

test('exit animation keeps the panel visible until it finishes', async () => {
    const state = setup(false);
    let finish;
    state.panel.animate = () => ({ cancel() {}, finished: new Promise((resolve) => { finish = resolve; }) });
    state.click();
    finish();
    await Promise.resolve();
    state.click();
    assert.equal(state.panel.hidden, false);
    assert.equal(state.panel.inert, true);
    finish();
    await Promise.resolve();
    assert.equal(state.panel.hidden, true);
});
