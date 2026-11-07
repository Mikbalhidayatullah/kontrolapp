import { test } from 'node:test';
import assert from 'node:assert/strict';
import { initializeDropdowns } from '../../resources/js/dropdowns.js';

function fixture(reducedMotion = false) {
    const handlers = {};
    const dropdowns = [];
    const document = {
        defaultView: { matchMedia: () => ({ matches: reducedMotion }) },
        addEventListener(type, callback) { handlers[type] = callback; },
        querySelectorAll() { return dropdowns.filter((dropdown) => dropdown.open); },
    };
    initializeDropdowns(document);
    const add = () => {
        const dropdown = {
            open: true, focused: false,
            matches(selector) { return selector === 'details[data-dropdown]'; },
            contains(target) { return target === this || target.parent === this; },
            querySelector() { return { focus: () => { dropdown.focused = true; } }; },
        };
        dropdowns.push(dropdown);
        return dropdown;
    };
    return { handlers, add };
}

test('outside clicks close open dropdowns, including dynamically added ones', () => {
    const { handlers, add } = fixture();
    const dropdown = add();
    handlers.click({ target: {} });
    assert.equal(dropdown.open, false);
});

test('checkbox and button clicks inside the dropdown keep it open', () => {
    const { handlers, add } = fixture();
    const dropdown = add();
    handlers.click({ target: { parent: dropdown } });
    assert.equal(dropdown.open, true);
});

test('opening another dropdown closes the previous dropdown', () => {
    const { handlers, add } = fixture();
    const first = add();
    const second = add();
    handlers.toggle({ target: second });
    assert.equal(first.open, false);
    assert.equal(second.open, true);
});

test('Escape closes the dropdown and returns focus to its trigger', () => {
    const { handlers, add } = fixture();
    const dropdown = add();
    handlers.keydown({ key: 'Escape' });
    assert.equal(dropdown.open, false);
    assert.equal(dropdown.focused, true);
});

test('unrelated disclosure panels are left unchanged', () => {
    const { handlers, add } = fixture();
    const dropdown = add();
    handlers.toggle({ target: { matches: () => false, open: true } });
    assert.equal(dropdown.open, true);
});

function animatedPanel(dropdown) {
    let finish;
    const animation = { finished: new Promise((resolve) => { finish = resolve; }), cancel() {} };
    const panel = { style: { pointerEvents: '' }, animate: () => animation };
    dropdown.querySelector = (selector) => selector === 'summary'
        ? { focus() {} }
        : panel;
    return { panel, finish };
}

test('outside click waits for exit animation before hiding the panel', async () => {
    const { handlers, add } = fixture();
    const dropdown = add();
    const { panel, finish } = animatedPanel(dropdown);
    handlers.click({ target: {} });
    assert.equal(dropdown.open, true);
    assert.equal(panel.style.pointerEvents, 'none');
    finish();
    await Promise.resolve();
    assert.equal(dropdown.open, false);
    assert.equal(panel.style.pointerEvents, '');
});

test('summary click animates closing instead of immediately hiding', async () => {
    const { handlers, add } = fixture();
    const dropdown = add();
    const { finish } = animatedPanel(dropdown);
    let prevented = false;
    const summary = { parentElement: dropdown };
    handlers.click({ target: { parent: dropdown, closest: () => summary }, preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(dropdown.open, true);
    finish();
    await Promise.resolve();
    assert.equal(dropdown.open, false);
});

test('reduced motion closes immediately without animation', () => {
    const { handlers, add } = fixture(true);
    const dropdown = add();
    animatedPanel(dropdown);
    handlers.click({ target: {} });
    assert.equal(dropdown.open, false);
});

test('clicking the summary during closing cancels the pending close', async () => {
    const { handlers, add } = fixture();
    const dropdown = add();
    const { finish, panel } = animatedPanel(dropdown);
    handlers.click({ target: {} });
    handlers.click({
        target: { parent: dropdown, closest: () => ({ parentElement: dropdown }) },
        preventDefault() {},
    });
    finish();
    await Promise.resolve();
    assert.equal(dropdown.open, true);
    assert.equal(panel.style.pointerEvents, '');
});
