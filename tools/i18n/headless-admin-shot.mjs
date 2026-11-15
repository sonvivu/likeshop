// Screenshot LikeShop admin dashboard (headless Chrome via CDP) with an existing PHP session.
import { writeFileSync } from 'node:fs';

const DEBUG_URL = 'http://127.0.0.1:9222';
const SESSION = 'd97jhol4dbqle3bu1g4ltdmnm1';
const TARGET = 'http://localhost:20208/admin/index/index.html';

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function getTarget() {
  for (let i = 0; i < 30; i++) {
    try {
      const res = await fetch(`${DEBUG_URL}/json/list`);
      const list = await res.json();
      const page = list.find((t) => t.type === 'page');
      if (page) return page;
    } catch {}
    await sleep(500);
  }
  throw new Error('no page target');
}

const page = await getTarget();
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((res, rej) => { ws.onopen = res; ws.onerror = rej; });

let id = 0;
const pending = new Map();
const events = [];
ws.onmessage = (ev) => {
  const msg = JSON.parse(ev.data);
  if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); }
  else if (msg.method) events.push(msg);
};
function send(method, params = {}) {
  const mid = ++id;
  return new Promise((resolve) => {
    pending.set(mid, resolve);
    ws.send(JSON.stringify({ id: mid, method, params }));
  });
}
async function shot(name) {
  const r = await send('Page.captureScreenshot', { format: 'png' });
  writeFileSync(name, Buffer.from(r.result.data, 'base64'));
  console.log('saved', name);
}
async function clickMenuText(text) {
  const expr = `(function(){var els=[...document.querySelectorAll('#LAY-system-side-menu a, .second-nav a')];var el=els.find(a=>a.textContent.trim().includes(${JSON.stringify(text)}));if(!el) return 'NOT FOUND: '+${JSON.stringify(text)};el.click();return 'clicked '+el.textContent.trim();})()`;
  const r = await send('Runtime.evaluate', { expression: expr, returnByValue: true });
  console.log(r.result?.result?.value);
}

await send('Page.enable');
await send('Network.enable');
await send('Network.setCookie', { name: 'PHPSESSID', value: SESSION, domain: 'localhost', path: '/' });
await send('Emulation.setDeviceMetricsOverride', { width: 1600, height: 1000, deviceScaleFactor: 1, mobile: false });
await send('Page.navigate', { url: TARGET });
await sleep(6000);
await shot('/tmp/admin-dash-vi.png');
await clickMenuText('Marketing');
await sleep(2500);
await shot('/tmp/admin-dash-marketing.png');
await clickMenuText('Tiếp thị liên kết');
await sleep(2500);
await shot('/tmp/admin-dash-affiliate.png');
ws.close();
process.exit(0);
