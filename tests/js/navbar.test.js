import { test } from 'node:test';
import assert from 'node:assert/strict';
import { indicatorPosition } from '../../resources/js/navbar.js';

test('indicator stays aligned with a menu relative to the navigation rail', () => {
    assert.deepEqual(indicatorPosition(
        { left: 300, top: 16, width: 120, height: 44 },
        { left: 180, top: 12 },
    ), { x: 120, y: 4, width: 120, height: 44 });
});

test('indicator measurements retain fractional pixels', () => {
    assert.deepEqual(indicatorPosition(
        { left: 20.5, top: 4.5, width: 110.25, height: 44 },
        { left: 10, top: 0 },
    ), { x: 10.5, y: 4.5, width: 110.25, height: 44 });
});
