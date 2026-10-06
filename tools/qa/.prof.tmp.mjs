import { chromium } from 'playwright';
import { serve } from './serve.mjs';
const [dir, path] = process.argv.slice(2);
const server = await serve(dir, 8889);
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium', args: ['--disable-gpu'] });
const context = await browser.newContext({ viewport: { width: 412, height: 823 }, deviceScaleFactor: 1.75, isMobile: true, hasTouch: true });
const page = await context.newPage();
const cdp = await context.newCDPSession(page);
await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
await cdp.send('Profiler.enable');
await cdp.send('Profiler.setSamplingInterval', { interval: 200 });
await cdp.send('Profiler.start');
await page.goto(`http://127.0.0.1:8889/${path}`, { waitUntil: 'load' });
await page.waitForTimeout(4000);
const { profile } = await cdp.send('Profiler.stop');
// self time per function
const self = new Map();
const byId = new Map(profile.nodes.map((n) => [n.id, n]));
const dt = profile.timeDeltas; let counts = new Map();
profile.samples.forEach((id, i) => counts.set(id, (counts.get(id) || 0) + (dt[i] || 0)));
for (const [id, us] of counts) {
  const n = byId.get(id); const f = n.callFrame;
  const key = `${f.functionName || '(anon)'} ${f.url.split('/').pop()}:${f.lineNumber + 1}`;
  self.set(key, (self.get(key) || 0) + us);
}
const top = [...self.entries()].sort((a, b) => b[1] - a[1]).slice(0, 25);
for (const [k, us] of top) console.log(String(Math.round(us / 1000)).padStart(6) + 'ms  ' + k);
await browser.close(); server.close();
