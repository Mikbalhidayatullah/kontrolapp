import { test } from 'node:test';
import assert from 'node:assert/strict';
import { initializeBpkFunding } from '../../resources/js/bpk-funding.js';

function setup() {
    const handlers = {};
    const boxes = [0, 1, 2].map(() => ({ checked: false, validity: '', setCustomValidity(value) { this.validity = value; } }));
    const type = { value: 'funding' };
    const fieldset = { disabled: true, classList: { toggle() {} }, querySelectorAll: () => boxes };
    const submit = {};
    const form = {
        querySelector: (selector) => selector === '[data-bpk-filter-type]' ? type : selector === '[data-bpk-funding-options]' ? fieldset : submit,
        querySelectorAll: () => boxes,
    };
    initializeBpkFunding({ addEventListener: (event, fn) => { handlers[event] = fn; } });
    const bulk = (all) => handlers.click({ target: { closest: () => ({ closest: () => form, hasAttribute: () => all }) } });
    return { boxes, type, fieldset, bulk, change: () => handlers.change({ target: { closest: () => form } }) };
}

test('BPK select all and clear all update only its own checkboxes and validation', () => {
    const state = setup();
    state.bulk(true);
    assert.equal(state.boxes.every((box) => box.checked && box.validity === ''), true);
    state.bulk(false);
    assert.equal(state.boxes.every((box) => !box.checked), true);
    assert.notEqual(state.boxes[0].validity, '');
});

test('all-data mode disables the BPK funding fields and removes validation', () => {
    const state = setup();
    state.change();
    assert.equal(state.fieldset.disabled, false);
    state.type.value = '';
    state.change();
    assert.equal(state.fieldset.disabled, true);
    assert.equal(state.boxes.every((box) => box.validity === ''), true);
});
