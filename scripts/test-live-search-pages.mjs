// Run from repo root: node scripts/test-live-search-pages.mjs
// Uses Laravel-rendered pages in the isolated test database; only async response timing is stubbed.
import assert from 'node:assert/strict';
import { execFileSync, spawn } from 'node:child_process';
import { mkdtempSync, readFileSync, existsSync, writeFileSync, rmSync } from 'node:fs';
import { createServer } from 'node:http';
import { join, resolve, extname } from 'node:path';
import { tmpdir, homedir } from 'node:os';
const suppliedPages = process.env.LIVE_SEARCH_PAGES_DIR;
const directory = suppliedPages || mkdtempSync(join(tmpdir(), 'glasspos-live-search-'));
if (!suppliedPages) {
console.log(execFileSync('php', ['-d', 'memory_limit=-1', 'vendor/bin/pest', 'tests/Feature/Frontend/LiveSearchPageContractFeatureTest.php', '--compact'],
  { encoding: 'utf8', env: { ...process.env, LIVE_SEARCH_EXPORT_DIR: directory } }));
}
let origin;
const publicRoot = resolve('public');
const server = createServer((req, res) => {
  const path = new URL(req.url, 'http://localhost').pathname;
  const isPage = /^\/[a-z-]+\.html$/.test(path);
  const file = isPage ? join(directory, path.slice(1)) : resolve(publicRoot, '.' + path);
  if ((!isPage && !file.startsWith(publicRoot + '/')) || !existsSync(file)) { res.writeHead(404); res.end(); return; }
  res.setHeader('Content-Type', { '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css' }[extname(file)] || 'application/octet-stream');
  res.end(isPage ? readFileSync(file, 'utf8').replaceAll('http://localhost:8000', origin).replaceAll('http://localhost', origin) : readFileSync(file));
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
origin = `http://127.0.0.1:${server.address().port}`;
// Snap Chromium exposes files in its common directory, whereas its /tmp is private.
const profileRoot = process.env.CHROMIUM_PROFILE_ROOT || (existsSync(join(homedir(), 'snap/chromium/common')) ? join(homedir(), 'snap/chromium/common') : tmpdir());
const profile = mkdtempSync(join(profileRoot, 'glasspos-live-search-'));
const browser = spawn(process.env.CHROMIUM_BIN || 'chromium', ['--headless=new', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage', '--no-first-run', '--remote-debugging-port=0', '--user-data-dir=' + profile, 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
let socket;
const evidence = [];
try {
  const until = async (fn, label) => { for (let i = 0; i < 100; i++) { if (await fn()) return; await sleep(100); } throw Error('Timeout: ' + label); };
  await until(() => existsSync(join(profile, 'DevToolsActivePort')), 'Chromium');
  const port = readFileSync(join(profile, 'DevToolsActivePort'), 'utf8').split('\n')[0];
  const tab = await (await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, { method: 'PUT' })).json();
  socket = new WebSocket(tab.webSocketDebuggerUrl);
  await new Promise(resolve => socket.addEventListener('open', resolve, { once: true }));
  let sequence = 0;
  const pending = new Map();
  socket.addEventListener('message', event => {
    const msg = JSON.parse(event.data); if (!msg.id) return;
    const task = pending.get(msg.id); pending.delete(msg.id);
    if (msg.error) task.reject(Error(JSON.stringify(msg.error))); else task.resolve(msg.result);
  });
  const call = (method, params = {}) => new Promise((resolve, reject) => { const id = ++sequence; pending.set(id, { resolve, reject }); socket.send(JSON.stringify({ id, method, params })); });
  const evaluate = async expression => {
    const result = await call('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw Error(JSON.stringify(result.exceptionDetails));
    return result.result.value;
  };
  await call('Page.enable');
  await call('Page.addScriptToEvaluateOnNewDocument', { source: readFileSync('tests/Browser/live-search-intercept.js', 'utf8') });
  const pages = [
    ['suppliers', '#supplier-search-input', '#supplier-table-body'],
    ['products', '#product-search-input', '#product-table-body'],
    ['procurement', '#procurement-search-input', '#procurement-invoice-table-body'],
    ['expenses', '#expense-search-input', '#expense-table-body'],
    ['services', '#service-search-input', '#service-table-body'],
    ['packages', '#package-search-input', '#package-table-body'],
    ['employees', '#employee-search-input', '#employee-table-body'],
    ['payrolls', '#payroll-search-input', '#payroll-table-body'],
    ['debts', '#employee-debt-search-input', '#employee-debt-table-body'],
    ['audit', '#audit-log-search-input', '#audit-log-table-body'],
    ['notes', '#admin-note-search-input', '#admin-note-table-body'],
    ['cashier-notes', '#cashier-note-search-input', '#cashier-note-list'],
    ['categories', '#expense-category-search-input', '#expense-category-table-body'],
    ['cashier-products', '[data-product-search-input]', '[data-product-search-results]', 'lookup'],
    ['procurement-create', '#nama_pt_pengirim', '[data-supplier-results]', 'lookup'],
    ['package-create', '[data-package-product-search]', '[data-package-product-results]', 'lookup'],
    ['procurement-products', '[data-procurement-product-search]', '[data-procurement-product-results]', 'lookup'],
    ['workspace-product', '[data-proof-row] [data-product-search]', '[data-proof-row] [data-product-results]', 'lookup', 'product'],
    ['workspace-service', '[data-proof-row] [data-service-search]', '[data-proof-row] [data-service-results]', 'lookup', 'service'],
    ['workspace-service-external', '[data-proof-row] [data-service-search]', '[data-proof-row] [data-service-results]', 'lookup', 'service_external'],
    ['workspace-package', '[data-proof-row] [data-package-search]', '[data-proof-row] [data-package-results]', 'lookup', 'service_store_stock'],
  ];
  const assertNavigation = async (name) => {
        const acceptedUrl = await evaluate('location.href');
        const beforeBack = await evaluate('__searchProof.requests.length');
        await evaluate("proofInput.value='pending'; proofInput.dispatchEvent(new Event('input',{bubbles:true})); history.back()");
        await until(() => evaluate(`location.href !== ${JSON.stringify(acceptedUrl)} && __searchProof.requests.length > ${beforeBack}`), name + ' Back');
        assert.equal(await evaluate('proofInput.value'), '', name + ' Back restores clear');
        await evaluate("__searchProof.respond(__searchProof.requests.length-1,'DEFAULT-BACK')");
        await sleep(350);
        assert.equal(await evaluate('__searchProof.requests.length'), beforeBack + 1, name + ' Back cancels debounce');
        await evaluate('history.forward()');
        await until(() => evaluate(`location.href === ${JSON.stringify(acceptedUrl)} && proofInput.value === 'jaya motor'`), name + ' Forward');
        await evaluate("__searchProof.respond(__searchProof.requests.length-1,'JAYA MOTOR')");
        assert.equal(await evaluate('proofInput.value'), 'jaya motor');
        assert.equal(await evaluate('document.activeElement === proofInput'), true);
  };
  for (const width of [1280, 390]) {
    await call('Emulation.setDeviceMetricsOverride', { width, height: 844, deviceScaleFactor: 1, mobile: width === 390 });
    for (const [name, inputSelector, resultSelector, kind, rowType] of pages.filter(page => (!process.env.LIVE_SEARCH_ONLY || process.env.LIVE_SEARCH_ONLY.split(',').some(prefix => page[0].startsWith(prefix))) && (!process.env.LIVE_SEARCH_NAVIGATION_ONLY || (!page[3] && page[0] !== 'categories')))) {
      const pageName = name.startsWith('workspace-') ? 'workspace' : name === 'procurement-products' ? 'procurement-create' : name;
      await call('Page.navigate', { url: `${origin}/${pageName}.html` });
      await until(() => evaluate(`document.readyState === 'complete'`), name);
      if (rowType) await evaluate(`CashierNoteWorkspace.addRow(${JSON.stringify(rowType)}).dataset.proofRow='1'`);
      await until(() => evaluate(`!!document.querySelector(${JSON.stringify(inputSelector)})`), name + ' input');
      await sleep(150);
      const initial = await evaluate('__searchProof.requests.length');
      for (let i = 0; i < initial; i++) await evaluate(`__searchProof.respond(${i}, 'DEFAULT')`);
      await sleep(50);
      assert.deepEqual(await evaluate('__searchProof.errors'), [], `${name} boot errors`);
      await evaluate(`window.proofInput = document.querySelector(${JSON.stringify(inputSelector)}); window.proofResult = document.querySelector(${JSON.stringify(resultSelector)}); if(!proofResult) throw Error('missing result container'); proofInput.focus();`);
      if (process.env.LIVE_SEARCH_NAVIGATION_ONLY) {
        for (const query of ['jaya', '', 'jaya motor']) {
          const before = await evaluate('__searchProof.requests.length');
          await evaluate(`proofInput.value=${JSON.stringify(query)}; proofInput.dispatchEvent(new Event('input',{bubbles:true}))`);
          await until(() => evaluate('__searchProof.requests.length > ' + before), name + ' accepted query');
          await evaluate(`__searchProof.respond(__searchProof.requests.length-1, ${JSON.stringify(query || 'DEFAULT')})`);
          assert.equal(await evaluate('proofInput.value'), query);
        }
        await assertNavigation(name);
        assert.deepEqual(await evaluate('__searchProof.errors'), [], name);
        evidence.push({ page: name, width, backForward: true, navigationCancelsDebounce: true });
        console.log('PASS navigation ' + name + ' ' + width);
        continue;
      }
      await call('Input.insertText', { text: 'j' }); await sleep(320);
      assert.equal(await evaluate('__searchProof.requests.length'), initial, `${name}: one character request`);
      await call('Input.insertText', { text: 'a' });
      await until(() => evaluate('__searchProof.requests.length > ' + initial), 'ja request');
      const a = await evaluate('__searchProof.requests.length - 1');
      await call('Input.insertText', { text: 'ya' });
      assert.equal(await evaluate(`__searchProof.requests[${a}].options.signal.aborted`), true, `${name}: immediate abort`);
      const snapshot = await evaluate('proofResult.innerHTML');
      const url = await evaluate('location.href');
      await evaluate(`proofInput.setSelectionRange(2, 3); __searchProof.respond(${a}, 'STALE-JA')`);
      assert.equal(await evaluate('proofInput.value'), 'jaya', `${name}: input rewound`);
      assert.equal(await evaluate('proofResult.innerHTML'), snapshot, `${name}: stale render`);
      assert.equal(await evaluate('location.href'), url, `${name}: stale URL`);
      assert.deepEqual(await evaluate('[document.activeElement === proofInput, proofInput.selectionStart, proofInput.selectionEnd]'), [true, 2, 3], `${name}: caret/focus`);
      await until(() => evaluate(`__searchProof.requests.length > ${a + 1}`), 'jaya request');
      const b = await evaluate('__searchProof.requests.length - 1');
      await evaluate(`__searchProof.respond(${b}, 'LATEST-JAYA')`);
      assert.ok(await evaluate("proofResult.textContent.includes('LATEST-JAYA')"), `${name}: latest render`);
      assert.deepEqual(await evaluate('[document.activeElement === proofInput, proofInput.selectionStart, proofInput.selectionEnd]'), [true, 2, 3]);
      // Clear while a search is pending, then deliver that obsolete response.
      await evaluate(`proofInput.value='jaya motor'; proofInput.dispatchEvent(new Event('input',{bubbles:true}));`);
      await until(() => evaluate(`__searchProof.requests.length > ${b + 1}`), 'pending clear');
      const c = await evaluate('__searchProof.requests.length - 1');
      await evaluate(`proofInput.value=''; proofInput.dispatchEvent(new Event('input',{bubbles:true})); __searchProof.respond(${c}, 'STALE-CLEAR');`);
      await sleep(200);
      assert.equal(await evaluate('proofInput.value'), '');
      assert.equal(await evaluate("proofResult.textContent.includes('STALE-CLEAR')"), false);
      if (!kind) await evaluate(`__searchProof.respond(__searchProof.requests.length - 1, 'DEFAULT-CLEAR')`);
      if (!kind) assert.ok(await evaluate("proofResult.textContent.includes('DEFAULT-CLEAR')"), `${name}: default clear`);
      // Real browser typing; old responses land before each following debounce.
      await evaluate('proofInput.focus(); proofInput.setSelectionRange(0,0); __searchProof.inputs.length=0; __searchProof.writes.length=0;');
      const history = [];
      for (const [i, char] of [...'jaya motor'].entries()) {
        await call('Input.insertText', { text: char });
        const text = 'jaya motor'.slice(0, i + 1); history.push(text);
        assert.equal(await evaluate('proofInput.value'), text);
        if (i > 1) await evaluate(`__searchProof.respond(__searchProof.requests.length - 1, 'OLD-${i}')`);
        assert.equal(await evaluate('proofInput.value'), text);
        await sleep(280);
      }
      await evaluate(`__searchProof.respond(__searchProof.requests.length - 1, 'JAYA MOTOR')`);
      assert.equal(await evaluate('proofInput.value'), 'jaya motor');
      assert.ok(await evaluate("proofResult.textContent.includes('JAYA MOTOR')"));
      assert.deepEqual(await evaluate('__searchProof.inputs.map(e=>e.value)'), history, `${name}: full input history`);
      assert.equal(await evaluate('document.activeElement === proofInput'), true);
      assert.deepEqual(await evaluate('__searchProof.writes.filter(w=>w.target)'), [], `${name}: async writes to live input`);
      assert.deepEqual(await evaluate('__searchProof.errors'), [], name);
      if (!kind && name !== 'categories') await assertNavigation(name);
      const result = { page: name, width, history, latestWins: true, immediateAbort: true, staleRejected: true, clear: true, focusCaret: true };
      evidence.push(result); console.log('PASS ' + name + ' ' + width);
    }
  }
  const evidenceFile = process.env.LIVE_SEARCH_NAVIGATION_ONLY ? '/tmp/glasspos-live-search-browser-navigation.json' : '/tmp/glasspos-live-search-browser.json';
  writeFileSync(evidenceFile, JSON.stringify(evidence, null, 2));
  console.log(`Browser GREEN: ${evidence.length} real-page/viewport checks; ${evidenceFile}`);
} finally {
  socket?.close(); browser.kill('SIGTERM'); server.close();
  await sleep(500);
  try { rmSync(profile, { recursive: true, force: true, maxRetries: 10, retryDelay: 100 }); } catch (error) { console.error('Profile cleanup:', error.message); }
  if (!suppliedPages) rmSync(directory, { recursive: true, force: true });
}
