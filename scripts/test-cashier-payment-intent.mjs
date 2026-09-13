import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {mkdtempSync, rmSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
const cases = [
 'surface=workspace', 'surface=detail',
 'surface=workspace&reopen=1', 'surface=detail&reopen=1',
 'surface=workspace&mode=full&total=480000&tender=500000',
 'surface=detail&mode=full&total=480000&tender=500000',
 'surface=workspace&mode=full&total=340&tender=400',
 'surface=detail&mode=full&total=340&tender=400',
 'surface=workspace&method=transfer', 'surface=detail&method=transfer',
 'surface=simple&tender=280000', 'surface=simple&mode=full&total=480000&tender=480000',
 'surface=simple&stale=1&tender=280000',
];
const profile = mkdtempSync(join(tmpdir(), 'glasspos-payment-intent-'));
try {
 for (const query of process.argv[2] ? [process.argv[2]] : cases) {
  const url = new URL(query === 'surface=external' ? '../tests/Browser/cashier-service-external.html' : '../tests/Browser/cashier-payment-intent.html', import.meta.url); url.search = query;
  const html = execFileSync(process.env.CHROMIUM_BIN || 'chromium', ['--headless=new','--no-sandbox','--disable-gpu',`--user-data-dir=${profile}`,'--allow-file-access-from-files','--virtual-time-budget=3000','--dump-dom',url.href], {encoding:'utf8',timeout:20000,stdio:['ignore','pipe','pipe']});
  const result = html.match(/<body[^>]*data-test-result="([^"]+)"/)?.[1];
  assert.equal(result, 'PASS', `${query}: ${result || 'fixture incomplete'}`);
  const payload = html.match(/<pre id="payload">([^<]+)<\/pre>/)?.[1];
  console.log(JSON.stringify({query,payload:JSON.parse(payload.replaceAll('&quot;', '"').replaceAll('&amp;', '&'))}));
 }
} finally { rmSync(profile,{recursive:true,force:true}); }
