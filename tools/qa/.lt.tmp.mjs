import lighthouse from 'lighthouse';
import * as chromeLauncher from 'chrome-launcher';
import { serve } from './serve.mjs';
const [dir, path, runs = '2'] = process.argv.slice(2);
const server = await serve(dir, 8890);
for (let i = 0; i < Number(runs); i++) {
  const chrome = await chromeLauncher.launch({ chromePath: '/opt/pw-browsers/chromium', chromeFlags: ['--headless=new', '--no-sandbox', '--disable-gpu'] });
  const r = await lighthouse(`http://127.0.0.1:8890/${path}`, { port: chrome.port, output: 'json', logLevel: 'error', onlyCategories: ['performance'] });
  await chrome.kill();
  const a = r.lhr.audits;
  console.log(`== ${path} P${Math.round(r.lhr.categories.performance.score * 100)} TBT ${a['total-blocking-time'].displayValue} FCP ${a['first-contentful-paint'].displayValue}`);
  for (const t of (a['long-tasks']?.details?.items ?? []).slice(0, 8)) console.log(`   start ${Math.round(t.startTime)}ms dur ${Math.round(t.duration)}ms ${String(t.url).split('/').slice(-2).join('/')}`);
  const mw = a['mainthread-work-breakdown']?.details?.items ?? [];
  console.log('   breakdown:', mw.map((x) => `${x.groupLabel} ${Math.round(x.duration)}ms`).join(', '));
}
server.close();
