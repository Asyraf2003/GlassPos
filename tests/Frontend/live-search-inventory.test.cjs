const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { surfaces } = require('./support/live-search-harness.cjs');
const inventory = require('./support/search-inventory.json');
const transport = /\bfetch\s*\(|\bnew\s+XMLHttpRequest|\$\.\s*ajax\s*\(|\baxios[.(]/g;
function files(root) {
  return fs.readdirSync(root, { withFileTypes: true }).flatMap(entry => {
    const file = path.posix.join(root, entry.name);
    return entry.isDirectory() ? files(file) : /\.(?:js|blade\.php)$/.test(file) ? [file] : [];
  });
}
test('every app-owned async transport is audited; adding a surface or request requires inventory review', () => {
  const actual = [...files('public/assets/static/js'), ...files('resources')]
    .map(file => ({ file, asyncCalls: [...fs.readFileSync(file, 'utf8').matchAll(transport)].length }))
    .filter(row => row.asyncCalls).sort((a, b) => a.file.localeCompare(b.file));
  const expected = inventory.map(({ file, asyncCalls }) => ({ file, asyncCalls })).sort((a, b) => a.file.localeCompare(b.file));
  assert.deepEqual(actual, expected);
});
test('canonical async listings all use the shared gate and have runtime page regressions', () => {
  for (const [name] of surfaces) {
    const file = `public/assets/static/js/pages/${name}.js`;
    assert.equal(inventory.find(row => row.file === file)?.status, 'fixed');
    const source = fs.readFileSync(file, 'utf8');
    assert.match(source, /window\.LiveSearch\.bind\(/);
    assert.match(source, /searchGate\.begin\(/);
    assert.match(source, /request\.isCurrent\(\)/);
    assert.doesNotMatch(source, /(?:currentRequest|requestCounter|activeController|syncInputsFromState\(true\).*await)/);
  }
});
