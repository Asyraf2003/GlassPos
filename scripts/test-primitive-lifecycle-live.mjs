import assert from 'node:assert/strict';
import {spawn, execFileSync} from 'node:child_process';
import {mkdtempSync, readFileSync, existsSync, writeFileSync} from 'node:fs';
import {join} from 'node:path';
import {tmpdir} from 'node:os';

// Start the guarded scripts/fixtures/primitive-lifecycle-live.php fixture and
// a real server on localhost8129 pointing ONLY at glasspos_slice12_browser.
const fixture = JSON.parse(readFileSync(process.env.PRIMITIVE_LIVE_MANIFEST || '/tmp/glasspos-slice12-live.json', 'utf8'));
const origin = 'http://127.0.0.1:8129';
const dir = mkdtempSync(join(tmpdir(), 'glasspos-slice12-live-'));
const db = sql => execFileSync('mariadb', ['--protocol=tcp', '--host=127.0.0.1', '--port=3319', '--user=root', '--database=glasspos_slice12_browser', '--batch', '--skip-column-names', '--execute=' + sql], {encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore']}).trim();
assert.equal(db('SELECT COUNT(*) FROM customer_payments'), '2', 'Run only against a fresh A3 browser fixture.');
const browser = spawn(process.env.CHROMIUM_BIN || 'chromium', ['--headless=new', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage', '--no-first-run', '--remote-debugging-port=0', '--user-data-dir=' + dir, 'about:blank'], {stdio: 'ignore'});
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
  await until(() => existsSync(join(dir, 'DevToolsActivePort')), 'browser');
  const port = readFileSync(join(dir, 'DevToolsActivePort'), 'utf8').split('\n')[0];
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
  await evaluate(`document.querySelector('[name=email]').value='slice12@example.test';document.querySelector('[name=password]').value='password123';document.querySelector('form').requestSubmit()`);
  await until(() => evaluate(`location.pathname !== '/login' && document.readyState === 'complete'`), 'login');
  await navigate(fixture.show);
  assert.equal(await evaluate(`document.body.innerText.includes('250.886')`), true);
  const receipts = [];
  for (const [paid, tender, count, debt] of [[112903, 120011, 3, 137983], [137983, 150007, 4, 0]]) {
    await evaluate(`document.querySelector('[data-payment-intent="pay"]').click()`);
    await until(() => evaluate(`document.querySelector('#note-payment-modal').classList.contains('show')`), 'modal');
    await sleep(400);
    await evaluate(`(() => {const p=document.querySelector('#detail_payment_amount_paid_display');p.value='${paid}';p.dispatchEvent(new Event('input',{bubbles:true}));document.querySelector('#detail-payment-open-cash').click();const t=document.querySelector('#inline_payment_amount_received_display');t.value='${tender}';t.dispatchEvent(new Event('input',{bubbles:true}));})()`);
    assert.equal(await evaluate(`document.querySelector('#detail-payment-submit-cash').disabled`), false);
    const postCount = posts.length;
    await evaluate(`document.querySelector('#detail-payment-submit-cash').click()`);
    await until(() => Number(db('SELECT COUNT(*) FROM customer_payments')) === count, 'payment commit');
    await until(() => posts.length > postCount, 'actual POST captured');
    const posted = posts.filter(post => post.url === origin + fixture.pay).at(-1);
    assert.ok(posted?.postData);
    const payload = Array.from(new URLSearchParams(posted.postData).entries());
    await navigate(fixture.show);
    assert.equal(Number(db('SELECT outstanding_rupiah FROM note_history_projection')), debt);
    const before = db('SELECT id,amount_rupiah FROM customer_payments ORDER BY id');
    const replay = await evaluate(`fetch(${JSON.stringify(fixture.pay)},{method:'POST',body:new URLSearchParams(${JSON.stringify(payload)}),credentials:'same-origin'}).then(async r=>({status:r.status,text:await r.text()}))`);
    const receipt = {paid, tender, count, debt, replayStatus: replay.status, key: payload.find(([key]) => key === 'idempotency_key')?.[1]};
    receipts.push(receipt);
    writeFileSync(join(dir, 'proof.json'), JSON.stringify({fixture, receipts}, null, 2));
    assert.equal(replay.status, 200, 'Exact captured POST must replay inside the authorized date window.');
    assert.equal(db('SELECT id,amount_rupiah FROM customer_payments ORDER BY id'), before);
    await navigate(fixture.show);
  }
  assert.equal(await evaluate(`!!document.querySelector('[data-payment-intent="pay"]')`), false);
  assert.equal(db('SELECT SUM(amount_rupiah) FROM customer_payments'), '413472');
  assert.equal(db('SELECT GROUP_CONCAT(change_rupiah ORDER BY amount_paid_rupiah) FROM customer_payment_cash_details'), '26874,7108,12024');
  const screenshot = await call('Page.captureScreenshot', {format: 'png'});
  writeFileSync(join(dir, 'live-a5.png'), Buffer.from(screenshot.data, 'base64'));
  const productRow = db("SELECT work_item_id FROM work_item_store_stock_lines WHERE product_id='primitive-p' LIMIT 1");
  const productLine = db("SELECT id FROM work_item_store_stock_lines WHERE product_id='primitive-p' LIMIT 1");
  await evaluate(`document.querySelector('[data-row-id="${productRow}"]').click();document.querySelector('#note-refund-open-button').click()`);
  await until(() => evaluate(`document.querySelector('#note-refund-modal').classList.contains('show')`), 'refund modal');
  await sleep(400);
  await evaluate(`const reason=document.querySelector('#note-refund-reason');reason.value='Slice12 live selected product refund';reason.dispatchEvent(new Event('input',{bubbles:true}))`);
  await evaluate(`const choice=document.querySelector('[data-stock-choice]');choice.value='1';choice.dispatchEvent(new Event('change',{bubbles:true}))`);
  assert.equal(await evaluate(`document.querySelector('#note-refund-submit').disabled`), false);
  await evaluate(`document.querySelector('#note-refund-submit').click()`);
  await until(() => Number(db('SELECT COALESCE(SUM(amount_rupiah),0) FROM customer_refunds')) === 142539, 'refund committed');
  await until(() => posts.some(post => post.url === origin + fixture.refund), 'refund POST captured');
  const refundPost = posts.filter(post => post.url === origin + fixture.refund).at(-1);
  assert.ok(refundPost.postData);
  const refundPayload = Array.from(new URLSearchParams(refundPost.postData).entries());
  await navigate(fixture.show);
  const refundBefore = db('SELECT * FROM customer_refunds ORDER BY id');
  const stockBefore = db('SELECT * FROM inventory_movements ORDER BY id');
  const refundReplay = await evaluate(`fetch(${JSON.stringify(fixture.refund)},{method:'POST',body:new URLSearchParams(${JSON.stringify(refundPayload)}),credentials:'same-origin'}).then(r=>r.status)`);
  assert.equal(refundReplay, 200);
  assert.equal(db('SELECT * FROM customer_refunds ORDER BY id'), refundBefore);
  assert.equal(db('SELECT * FROM inventory_movements ORDER BY id'), stockBefore);
  assert.equal(db("SELECT qty_on_hand FROM product_inventory WHERE product_id='primitive-p'"), '17');
  assert.equal(db("SELECT COUNT(*) FROM inventory_movements WHERE reversal_source_id='" + productLine + "'"), '1');
  assert.equal(db('SELECT total_rupiah FROM note_history_projection'), '270933');
  assert.equal(db('SELECT outstanding_rupiah FROM note_history_projection'), '0');
  await navigate(fixture.show);
  assert.equal(await evaluate(`!!document.querySelector('[data-refund-row="1"][data-row-id="${productRow}"]')`), false);
  const refundScreenshot = await call('Page.captureScreenshot', {format: 'png'});
  writeFileSync(join(dir, 'live-refund.png'), Buffer.from(refundScreenshot.data, 'base64'));
  receipts.push({refund: 142539, replayStatus: refundReplay, stockP: 17, currentTotal: 270933, sourceRows: Number(db('SELECT COUNT(*) FROM customer_refunds'))});
  writeFileSync(join(dir, 'proof.json'), JSON.stringify({fixture, receipts}, null, 2));
  console.log(JSON.stringify({status: 'GREEN', artifact: dir, scope: 'Live A3→A5 plus selected product refund, actual POST replay, persisted reload', receipts}));
} catch (error) {
  console.error('Live proof artifact: ' + dir);
  throw error;
} finally {
  socket?.close(); browser.kill();
}
