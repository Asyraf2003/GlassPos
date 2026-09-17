import assert from 'node:assert/strict';
import {execFileSync, spawn} from 'node:child_process';
import {mkdtempSync, readFileSync, existsSync, writeFileSync} from 'node:fs';
import {createServer} from 'node:http';
import {join, resolve, extname} from 'node:path';
import {tmpdir} from 'node:os';

// Rendered pages come from real HTTP/domain actions in the test DB. Browser actions below
// exercise production DOM/JS; submissions are captured, not sent into a rolled-back fixture.
const directory = mkdtempSync(join(tmpdir(), 'glasspos-primitive-pages-'));
console.log(execFileSync('php', ['-d', 'memory_limit=-1', 'vendor/bin/pest',
  'tests/Feature/Note/PrimitiveLifecyclePresentationContractFeatureTest.php', '--compact'],
  {encoding: 'utf8', env: {...process.env, PRIMITIVE_PRESENTATION_EXPORT_DIR: directory}}));
let origin;
const publicRoot = resolve('public');
const server = createServer((request, response) => {
  const pathname = new URL(request.url, 'http://localhost').pathname;
  const page = /^\/(a3-detail|a3-editor|a5-detail)\.html$/.test(pathname);
  const path = page ? join(directory, pathname.slice(1)) : resolve(publicRoot, '.' + pathname);
  if ((!page && !path.startsWith(publicRoot + '/')) || !existsSync(path) || request.method !== 'GET') {
    response.writeHead(404); response.end(); return;
  }
  const types = {'.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.svg': 'image/svg+xml'};
  response.setHeader('Content-Type', types[extname(path)] || 'application/octet-stream');
  response.end(page ? readFileSync(path, 'utf8').replaceAll('http://localhost:8000', origin) : readFileSync(path));
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
origin = `http://127.0.0.1:${server.address().port}`;
const profile = join(directory, 'chromium');
const browser = spawn(process.env.CHROMIUM_BIN || 'chromium', ['--headless=new', '--no-sandbox', '--disable-gpu',
  '--disable-dev-shm-usage', '--no-first-run', '--no-default-browser-check', '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank'], {stdio: 'ignore'});
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const until = async (condition, label) => {
  for (let i = 0; i < 100; i++) {
    try { if (await condition()) return; } catch (error) {
      if (!/Not attached to an active page|Execution context was destroyed|Cannot find context/.test(error.message)) throw error;
    }
    await sleep(100);
  }
  throw new Error(`Timeout: ${label}`);
};
let socket;
try {
  await until(() => existsSync(join(profile, 'DevToolsActivePort')), 'Chromium startup');
  const port = readFileSync(join(profile, 'DevToolsActivePort'), 'utf8').split('\n')[0];
  const tab = await (await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, {method: 'PUT'})).json();
  socket = new WebSocket(tab.webSocketDebuggerUrl);
  await new Promise(resolve => socket.addEventListener('open', resolve, {once: true}));
  let sequence = 0;
  const pending = new Map();
  socket.addEventListener('message', event => {
    const message = JSON.parse(event.data);
    if (!message.id) return;
    const task = pending.get(message.id); pending.delete(message.id);
    if (message.error) task.reject(Error(task.method + ': ' + JSON.stringify(message.error))); else task.resolve(message.result);
  });
  const call = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence; pending.set(id, {resolve, reject, method}); socket.send(JSON.stringify({id, method, params}));
  });
  const evaluate = async expression => {
    const result = await call('Runtime.evaluate', {expression, returnByValue: true, awaitPromise: true});
    if (result.exceptionDetails) throw Error(JSON.stringify(result.exceptionDetails));
    return result.result.value;
  };
  const navigate = async name => {
    await call('Page.navigate', {url: `${origin}/${name}.html`});
    await until(() => evaluate(`location.pathname === '/${name}.html' && document.readyState === 'complete'`), name);
  };
  await call('Page.enable');
  for (const width of [1280, 390]) {
    await call('Emulation.setDeviceMetricsOverride', {width, height: 844, deviceScaleFactor: 1, mobile: width === 390});
    await navigate('a3-detail');
    await evaluate(`document.querySelector('[data-payment-intent="pay"]').click()`);
    await until(() => evaluate(`document.querySelector('#note-payment-modal').classList.contains('show')`), 'modal opens');
    await sleep(400);
    assert.equal(await evaluate(`document.querySelector('#note-payment-modal').contains(document.activeElement)`), true, 'focus is in payment modal');
    await evaluate(`(() => { const input = document.querySelector('#detail_payment_amount_paid_display'); input.value = '112903'; input.dispatchEvent(new Event('input', {bubbles: true})); document.querySelector('#detail-payment-open-cash').click(); const tender = document.querySelector('#inline_payment_amount_received_display'); tender.value = '120011'; tender.dispatchEvent(new Event('input', {bubbles: true})); })()`);
    const money = await evaluate(`['workspace-cash-payable-text','workspace-cash-change-text','workspace-cash-remaining-text'].map(id => Number(document.getElementById(id).textContent.replace(/\\D/g,'')))`);
    assert.deepEqual(money, [112903, 7108, 137983]);
    await evaluate(`document.querySelector('#detail-payment-back-cash').click(); document.querySelector('#detail-payment-open-cash').click()`);
    assert.equal(await evaluate(`Number(document.getElementById('workspace-cash-payable-text').textContent.replace(/\\D/g,''))`), 112903);
    await evaluate(`(() => {const input=document.getElementById('inline_payment_amount_received_display');input.value='120011';input.dispatchEvent(new Event('input',{bubbles:true}));})()`);
    const bounds = await evaluate(`(() => {const r=document.querySelector('#note-payment-modal .modal-dialog').getBoundingClientRect();return {left:r.left,right:r.right,width:innerWidth};})()`);
    assert.ok(bounds.left >= 0 && bounds.right <= bounds.width + 1, 'modal fits viewport');
    const screenshot = await call('Page.captureScreenshot', {format: 'png'});
    writeFileSync(join(directory, `a4-cash-${width}.png`), Buffer.from(screenshot.data, 'base64'));
    await navigate('a5-detail');
    assert.equal(await evaluate(`document.querySelector('[data-payment-intent="settle"]') === null`), true, 'closed note has no settle action');
    const history = await call('Page.getNavigationHistory');
    await call('Page.navigateToHistoryEntry', {entryId: history.entries[history.currentIndex - 1].id});
    await until(() => evaluate(`location.pathname === '/a3-detail.html' && document.readyState === 'complete'`), 'Back');
    // Production page-freshness reloads automatically after Back; wait for that navigation.
    await until(() => evaluate(`performance.getEntriesByType('navigation')[0]?.type === 'reload' && document.readyState === 'complete'`), 'Back freshness reload');
    await sleep(300);
    await call('Page.reload');
    await sleep(500);
    assert.equal(await evaluate(`document.body.textContent.includes('250.886')`), true, 'reload retains A3 outstanding');
    const forward = await call('Page.getNavigationHistory');
    await call('Page.navigateToHistoryEntry', {entryId: forward.entries[forward.currentIndex + 1].id});
    await until(() => evaluate(`location.pathname === '/a5-detail.html' && document.readyState === 'complete' && performance.getEntriesByType('navigation')[0]?.type === 'reload'`), 'Forward freshness');
    assert.equal(await evaluate(`document.querySelector('[data-payment-intent="settle"]') === null`), true);
    console.log(JSON.stringify({width, checkpoint: 'A3/A4/A5', money, modalFocus: true, navigation: 'Back/reload/Forward', screenshot: join(directory, `a4-cash-${width}.png`)}));
  }
  console.log(`Rendered-page browser proof PASS; artifacts ${directory}`);
} finally {
  socket?.close(); browser.kill('SIGTERM'); server.close();
}
