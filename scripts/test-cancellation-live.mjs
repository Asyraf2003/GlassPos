import assert from 'node:assert/strict';
import {spawn, execFileSync} from 'node:child_process';
import {mkdtempSync, readFileSync, writeFileSync} from 'node:fs';
import {join} from 'node:path';
import {tmpdir} from 'node:os';

// Use guarded scripts/fixtures/cancellation-live.php and localhost8128 only.
const fixture = JSON.parse(readFileSync('/tmp/glasspos-cancellation-live.json', 'utf8'));
const origin = 'http://127.0.0.1:8128';
const dir = mkdtempSync(join(tmpdir(), 'glasspos-cancellation-live-'));
const db = sql => execFileSync('mariadb', ['--protocol=tcp', '--host=127.0.0.1', '--port=3321', '--user=root', '--database=glasspos_cancellation_browser', '--batch', '--skip-column-names', '--execute=' + sql], {encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore']}).trim();
assert.equal(db('SELECT COUNT(*) FROM customer_payments'), '0', 'Requires fresh unpaid browser fixture.');
assert.equal(db('SELECT COUNT(*) FROM note_revisions'), '1', 'Requires fresh single-revision fixture.');
const browser = spawn(process.env.CHROMIUM_BIN || 'chromium', ['--headless=new', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage', '--no-first-run', '--remote-debugging-port=0', '--user-data-dir=' + dir, 'about:blank'], {stdio: ['ignore', 'ignore', 'pipe']});
let browserPort;
browser.stderr.on('data', chunk => {
  const match = chunk.toString().match(/DevTools listening on ws:\/\/127\.0\.0\.1:(\d+)\//);
  if (match) browserPort = match[1];
});
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const until = async (fn, label) => {
  for (let i = 0; i < 150; i++) {
    try { if (await fn()) return; } catch (error) {
      if (!/context|Context|active page/.test(error.message)) throw error;
    }
    await sleep(100);
  }
  throw Error('Timeout: ' + label);
};
let socket;
try {
  await until(() => browserPort, 'browser');
  const port = browserPort;
  const target = await (await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, {method: 'PUT'})).json();
  socket = new WebSocket(target.webSocketDebuggerUrl);
  await new Promise(resolve => socket.addEventListener('open', resolve, {once: true}));
  let sequence = 0;
  const pending = new Map();
  const posts = [];
  socket.addEventListener('message', event => {
    const message = JSON.parse(event.data);
    if (message.method === 'Network.requestWillBeSent' && message.params.request.method === 'POST') posts.push(message.params.request);
    if (!message.id) return;
    const task = pending.get(message.id); pending.delete(message.id);
    if (message.error) task.reject(Error(JSON.stringify(message.error))); else task.resolve(message.result);
  });
  const call = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence; pending.set(id, {resolve, reject}); socket.send(JSON.stringify({id, method, params}));
  });
  const evaluate = async expression => {
    const result = await call('Runtime.evaluate', {expression, returnByValue: true, awaitPromise: true});
    if (result.exceptionDetails) throw Error(JSON.stringify(result.exceptionDetails));
    return result.result.value;
  };
  const navigate = async path => {
    await call('Page.navigate', {url: origin + path});
    await until(() => evaluate(`document.readyState === 'complete' && location.pathname === ${JSON.stringify(path)}`), 'navigation ' + path);
  };
  await call('Page.enable'); await call('Network.enable');
  await call('Emulation.setDeviceMetricsOverride', {width: 1280, height: 900, deviceScaleFactor: 1, mobile: false});
  await navigate('/login');
  await evaluate(`document.querySelector('[name=email]').value='cancellation@example.test';document.querySelector('[name=password]').value='password123';document.querySelector('form').requestSubmit()`);
  await until(() => evaluate(`location.pathname !== '/login' && document.readyState === 'complete'`), 'login');
  await navigate(fixture.show);
  const receipts = [];
  const stock = () => Number(db("SELECT qty_on_hand FROM product_inventory WHERE product_id='primitive-p'"));
  const graph = () => db("SELECT CONCAT(note_state,':',current_revision_id,':',total_rupiah) FROM notes") + db('SELECT * FROM inventory_movements ORDER BY id');
  for (const [width, mobile] of [[1280, false], [390, true]]) {
    await call('Emulation.setDeviceMetricsOverride', {width, height: 844, deviceScaleFactor: 1, mobile});
    for (const action of ['cancel', 'restore']) {
      await navigate(fixture.show);
      await until(() => evaluate(`!!document.querySelector('#note-lifecycle-form')`), 'lifecycle form');
      await evaluate(`document.querySelector('#note-lifecycle details').open=true`);
      assert.equal(await evaluate(`document.querySelector('#note-lifecycle-form').checkValidity()`), false, 'Reason required');
      assert.equal(await evaluate(`document.querySelector('#note-lifecycle').getBoundingClientRect().right <= innerWidth`), true, 'Lifecycle fits viewport');
      await evaluate(`document.querySelector('#note-lifecycle-reason').value=${JSON.stringify('Browser '+action+' '+width)}`);
      const count = posts.length;
      await evaluate(`document.querySelector('#note-lifecycle-form').requestSubmit()`);
      await until(() => posts.length > count && posts.some(p=>p.url===origin+fixture[action]), 'captured '+action);
      const expected = action === 'cancel' ? 'cancelled' : 'open';
      await until(() => db('SELECT note_state FROM notes') === expected, 'committed '+action);
      await navigate(fixture.show);
      assert.equal(stock(), action === 'cancel' ? 17 : 14);
      if (action === 'cancel') assert.equal(await evaluate(`document.querySelector('[data-payment-aggregate=outstanding]').dataset.rupiah`), '0', 'Cancelled Detail has no active receivable');
      assert.equal(await evaluate(`document.body.innerText.includes(${JSON.stringify('Browser '+action+' '+width)})`), true);
      const posted = posts.filter(p=>p.url===origin+fixture[action]).at(-1);
      assert.ok(posted.postData);
      const payload = Array.from(new URLSearchParams(posted.postData).entries());
      const before = graph();
      const replay = await evaluate(`fetch(${JSON.stringify(fixture[action])},{method:'POST',headers:{Accept:'application/json'},body:new URLSearchParams(${JSON.stringify(payload)}),credentials:'same-origin'}).then(async r=>({status:r.status,body:await r.json()}))`);
      assert.equal(replay.status, 200); assert.equal(replay.body.success, true);
      assert.equal(graph(), before, 'Replay creates no effects');
      receipts.push({width, action, stock:stock(), replayStatus:replay.status});
      const shot = await call('Page.captureScreenshot', {format:'png'});
      writeFileSync(join(dir,action+'-'+width+'.png'),Buffer.from(shot.data,'base64'));
    }
  }
  assert.equal(db('SELECT COUNT(*) FROM note_revisions'), '3');
  assert.equal(db("SELECT COUNT(*) FROM note_mutation_events WHERE mutation_type='note_cancelled'"), '2');
  assert.equal(db("SELECT COUNT(*) FROM note_mutation_events WHERE mutation_type='note_restored'"), '2');
  assert.equal(db('SELECT COUNT(*) FROM customer_payments'), '0');
  assert.equal(db('SELECT COUNT(*) FROM customer_refunds'), '0');
  await navigate(fixture.show);
  await call('Page.reload');
  await until(()=>evaluate(`document.readyState==='complete' && !!document.querySelector('#note-lifecycle-form')`),'reload');
  writeFileSync(join(dir,'proof.json'),JSON.stringify({fixture,receipts,revisionCount:3},null,2));
  console.log(JSON.stringify({status:'GREEN',artifact:dir,receipts}));
} catch(error) {
  console.error('Browser artifact: '+dir); throw error;
} finally {
  socket?.close(); browser.kill();
}
