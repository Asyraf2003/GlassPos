const { test } = require('node:test');
const assert = require('node:assert/strict');
const { harness, surfaces } = require('./support/live-search-harness.cjs');
for (const surface of surfaces) {
  test(`${surface[0]}: stale response during debounce cannot render or own live input (A/E)`, async () => {
    const h = harness(surface);
    assert.equal(h.requests.length, 1, 'page must boot');
    await h.respond(0);
    h.change('ja'); h.tick();
    const old = h.requests.length - 1;
    h.change('jaya');
    assert.equal(h.requests[old].options.signal.aborted, true, 'abort immediately before debounce');
    const body = h.body.innerHTML, url = h.location.href;
    h.input.setSelectionRange(2, 3);
    await h.respond(old, 'STALE-JA');
    assert.equal(h.input.value, 'jaya');
    assert.equal(h.body.innerHTML, body);
    assert.equal(h.location.href, url);
    assert.equal(h.document.activeElement, h.input);
    assert.deepEqual([h.input.selectionStart, h.input.selectionEnd], [2, 3]);
    h.tick(); await h.respond(h.requests.length - 1, 'LATEST-JAYA');
    assert.ok(h.body.innerHTML.includes('LATEST-JAYA'), h.body.innerHTML);
    assert.equal(h.document.activeElement, h.input);
    assert.deepEqual([h.input.selectionStart, h.input.selectionEnd], [2, 3]);
  });
  test(`${surface[0]}: full typing, minimum, out of order, clear and navigation (B/C/D/F/G)`, async () => {
    const h = harness(surface);
    await h.respond(0);
    h.change(''); h.tick(); h.change('j'); h.tick();
    assert.equal(h.requests.length, 1, 'empty unchanged default and one char send no request');
    h.submit(); h.tick();
    assert.equal(h.requests.length, 1, 'one-character submit must not search');
    const values = ['ja', 'jay', 'jaya', 'jaya ', 'jaya m', 'jaya mo', 'jaya mot', 'jaya moto', 'jaya motor'];
    for (const value of values) { h.change(value); h.tick(); assert.equal(h.input.value, value); }
    const final = h.requests.length - 1;
    await h.respond(2, 'STALE-JAY'); await h.respond(1, 'STALE-JA');
    assert.equal(h.input.value, 'jaya motor');
    assert.ok(!h.body.innerHTML.includes('STALE'));
    await h.respond(final, 'JAYA MOTOR');
    assert.ok(h.body.innerHTML.includes('JAYA MOTOR'));
    assert.equal(h.input.value, 'jaya motor');
    const url = h.location.href;
    await h.fail(3);
    assert.equal(h.location.href, url);
    assert.ok(h.body.innerHTML.includes('JAYA MOTOR'));
    h.change('jaya'); h.tick(); const pending = h.requests.length - 1;
    h.change(''); await h.respond(pending, 'STALE-CLEAR'); h.tick();
    assert.equal(h.input.value, ''); assert.ok(!h.body.innerHTML.includes('STALE-CLEAR'));
    await h.respond(h.requests.length - 1, 'DEFAULT');
    assert.ok(h.body.innerHTML.includes('DEFAULT'));
    if (surface[0] === 'admin-expense-categories-table') return; // Existing page has no URL state.
    h.change('pending'); h.navigate('back'); h.tick();
    assert.equal(h.input.value, 'back');
    const params = new URL(h.requests.at(-1).url, h.location).searchParams;
    assert.equal(params.get('q') || params.get('search'), 'back');
  });
  test(`${surface[0]}: response order jay, ja, jaya accepts only jaya (D)`, async () => {
    const h = harness(surface); await h.respond(0);
    for (const query of ['ja', 'jay', 'jaya']) { h.change(query); h.tick(); }
    await h.respond(2, 'STALE-JAY'); await h.respond(1, 'STALE-JA');
    assert.equal(h.input.value, 'jaya'); assert.ok(!h.body.innerHTML.includes('STALE'));
    await h.respond(3, 'LATEST-JAYA');
    assert.ok(h.body.innerHTML.includes('LATEST-JAYA'));
    assert.equal(h.input.value, 'jaya');
  });

  test(`${surface[0]}: typing one character during clear debounce still permits safe default restoration`, async () => {
    const h = harness(surface); await h.respond(0);
    h.change('jaya'); h.tick(); await h.respond(1, 'JAYA');
    h.change(''); h.change('j'); h.tick();
    assert.equal(h.requests.length, 2, 'one character must cancel the queued default request');
    h.change(''); h.tick();
    assert.equal(h.requests.length, 3, 'clear must restore default despite cancelled earlier clear');
    await h.respond(2, 'DEFAULT');
    assert.ok(h.body.innerHTML.includes('DEFAULT'));
    assert.equal(h.input.value, '');
  });

}
test('procurement explicit reset clears the live field and invalidates pending search', async () => {
  const h = harness(surfaces.find(surface => surface[0] === 'admin-procurement-invoices-table'));
  await h.respond(0);
  h.change('jaya'); h.tick();
  h.nodes.get('procurement-reset-all-filters').dispatch('click');
  assert.equal(h.input.value, '');
  assert.equal(h.requests[1].options.signal.aborted, true);
  await h.respond(1, 'STALE-JAYA');
  assert.ok(!h.body.innerHTML.includes('STALE-JAYA'));
  await h.respond(2, 'DEFAULT');
  assert.ok(h.body.innerHTML.includes('DEFAULT'));
});
