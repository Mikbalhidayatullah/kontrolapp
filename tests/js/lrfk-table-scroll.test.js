import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

for (const template of ['index.blade.php', 'partials/source-table.blade.php', 'partials/perjadin-table.blade.php']) {
    test(`LRFK ${template} has a bounded keyboard-accessible table viewport`, () => {
        const html = readFileSync(new URL(`../../resources/views/lrfk/${template}`, import.meta.url), 'utf8');
        assert.match(html, /class="lrfk-table-scroll[^"\n]*"/);
        assert.match(html, /tabindex="0"/);
        assert.match(html, /aria-label="Tabel LRFK/);
    });
}

test('LRFK header stays above rows while its viewport scrolls in both directions', () => {
    const css = readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8');
    assert.match(css, /\.lrfk-table-scroll\s*\{[^}]*max-height:[^}]*overflow: auto/s);
    assert.match(css, /\.lrfk-table-scroll thead\s*\{[^}]*position: sticky;[^}]*top: 0;[^}]*z-index:/s);
});
