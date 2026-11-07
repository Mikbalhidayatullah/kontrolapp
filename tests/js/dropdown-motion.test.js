import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8');

test('dropdown motion is short, subtle, and scoped to dropdown panels', () => {
    assert.match(css, /details\[data-dropdown\]\[open\] > :not\(summary\)/);
    assert.match(css, /animation: dropdown-enter 140ms/);
    assert.match(css, /translateY\(-3px\)/);
});

test('reduced motion disables panel animation and arrow transitions', () => {
    assert.match(css, /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(css, /animation: none/);
    assert.match(css, /transition: none/);
});

test('single-choice selects across all pages use progressive picker animations', () => {
    assert.match(css, /@supports \(appearance: base-select\)/);
    assert.match(css, /select:not\(\[multiple\]\):not\(\[size\]\)::picker\(select\)/);
    assert.match(css, /overlay 140ms allow-discrete/);
    assert.match(css, /@starting-style/);
    assert.match(css, /:open::picker\(select\)/);
});
