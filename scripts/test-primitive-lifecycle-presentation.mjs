import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';

// Use the existing Chromium harness and production payment modules at literal Blueprint0018 checkpoints.
const checkpoints = [
  {name: 'A1', total: 395933, net: 0, mode: 'partial', paid: 73129, tender: 100003},
  {name: 'A4', total: 413472, net: 162586, mode: 'partial', paid: 112903, tender: 120011},
  {name: 'A5', total: 413472, net: 275489, mode: 'full', paid: 137983, tender: 150007},
  {name: 'B8', total: 427741, net: 268777, mode: 'full', paid: 158964, tender: 170003},
];
for (const point of checkpoints) {
  const payments = [];
  for (const surface of ['workspace', 'detail', 'simple']) {
    const query = new URLSearchParams({surface, mode: point.mode, total: point.total, net_paid: point.net,
      intent: point.paid, tender: point.tender, reopen: '1'}).toString();
    const output = execFileSync(process.execPath, ['scripts/test-cashier-payment-intent.mjs', query], {encoding: 'utf8'});
    const result = JSON.parse(output.trim());
    assert.equal(result.payload.paid, point.paid, `${point.name} ${surface}: credited amount`);
    assert.equal(Number(result.payload.received), surface === 'simple' ? point.paid : point.tender);
    payments.push(result.payload.paid);
    console.log(JSON.stringify({checkpoint: point.name, surface, ...result.payload}));
  }
  assert.deepEqual(payments, [point.paid, point.paid, point.paid]);
}
