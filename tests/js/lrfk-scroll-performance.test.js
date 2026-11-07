import { test } from 'node:test';
import assert from 'node:assert/strict';
import { optimizeFrozenRows } from '../../resources/js/lrfk-scroll.js';

function fixture(supported = true) {
    let callback;
    let options;
    let disconnected = false;
    const rows = Array.from({ length: 582 }, () => ({ attributes: new Set(), toggleAttribute(key, enabled) { enabled ? this.attributes.add(key) : this.attributes.delete(key); }, removeAttribute(key) { this.attributes.delete(key); } }));
    const attributes = new Set();
    const viewport = { querySelectorAll: () => rows, setAttribute: (key) => attributes.add(key), removeAttribute: (key) => attributes.delete(key) };
    const window = supported ? { IntersectionObserver: class {
        constructor(fn, config) { callback = fn; options = config; }
        observe() {}
        disconnect() { disconnected = true; }
    } } : {};
    const cleanup = optimizeFrozenRows(viewport, window);
    return { rows, attributes, cleanup, deliver: (records) => callback(records), options, disconnected: () => disconnected };
}

test('only nearby rows need sticky cells, without removing any of the 582 rows', () => {
    const state = fixture();
    state.deliver(state.rows.map((target, index) => ({ target, isIntersecting: index < 20 })));
    assert.equal(state.rows.length, 582);
    assert.equal(state.rows.filter((row) => row.attributes.has('data-frozen-visible')).length, 20);
    assert.equal(state.attributes.has('data-frozen-optimized'), true);
    assert.equal(state.options.rootMargin, '800px 0px');
});

test('scrolling activates the next rows and releases offscreen rows', () => {
    const state = fixture();
    state.deliver([{ target: state.rows[0], isIntersecting: true }]);
    state.deliver([{ target: state.rows[0], isIntersecting: false }, { target: state.rows[100], isIntersecting: true }]);
    assert.equal(state.rows[0].attributes.size, 0);
    assert.equal(state.rows[100].attributes.has('data-frozen-visible'), true);
});

test('cleanup disconnects observers and restores ordinary sticky fallback', () => {
    const state = fixture();
    state.deliver([{ target: state.rows[0], isIntersecting: true }]);
    state.cleanup();
    assert.equal(state.disconnected(), true);
    assert.equal(state.attributes.size, 0);
    assert.equal(state.rows[0].attributes.size, 0);
});

test('unsupported browsers retain the regular sticky columns', () => {
    const state = fixture(false);
    assert.equal(state.attributes.size, 0);
    state.cleanup();
});
