/**
 * Lighthouse 計測（モバイル・シミュレートされた低速回線）
 *
 *   node tools/qa/lighthouse.mjs [--dir=build/production]
 *
 * 架空サイトの公開版（docs/）は noindex のため SEO が満点にならない。
 * 本番を想定した production ビルド（noindex なし）を計測対象にする。
 * 結果は qa-output/lighthouse.json に保存する。
 */
import lighthouse from 'lighthouse';
import * as chromeLauncher from 'chrome-launcher';
import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const args = Object.fromEntries(process.argv.slice(2).map((a) => {
  const [k, v] = a.replace(/^--/, '').split('=');
  return [k, v ?? true];
}));
const dir = join(root, args.dir ?? 'build/production');
if (!existsSync(join(dir, 'manifest.json'))) {
  console.error(`${dir} がありません。先に php tools/build.php --env=production --out=build/production を実行してください。`);
  process.exit(1);
}
const manifest = JSON.parse(readFileSync(join(dir, 'manifest.json'), 'utf8'));
const port = 8800 + Math.floor(Math.random() * 100);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', dir], { stdio: 'ignore' });
await new Promise((r) => setTimeout(r, 800));

// 各サイトのトップと、代表的な下層ページを1つずつ計測する
const targets = [];
for (const [site, info] of Object.entries(manifest.sites)) {
  const paths = info.pages.map((p) => p.path).filter((p) => !p.endsWith('404.html'));
  const top = paths.find((p) => p.endsWith('index.html'));
  const sub = info.pages.find((p) => p.kind === 'treatment' && !p.path.endsWith('index.html'))?.path
    ?? paths.find((p) => p !== top);
  for (const path of [top, sub]) if (path) targets.push({ site, path });
}

const chrome = await chromeLauncher.launch({
  chromePath: process.env.CHROME_PATH ?? '/opt/pw-browsers/chromium',
  chromeFlags: ['--headless=new', '--no-sandbox', '--disable-gpu'],
});
const results = [];
for (const { site, path } of targets) {
  const url = `http://127.0.0.1:${port}/${path}`;
  const runnerResult = await lighthouse(url, {
    port: chrome.port,
    output: 'json',
    logLevel: 'error',
    onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'],
  });
  const { categories, audits } = runnerResult.lhr;
  const score = (key) => Math.round((categories[key]?.score ?? 0) * 100);
  const row = {
    site,
    path,
    performance: score('performance'),
    accessibility: score('accessibility'),
    bestPractices: score('best-practices'),
    seo: score('seo'),
    lcp: audits['largest-contentful-paint']?.displayValue,
    cls: audits['cumulative-layout-shift']?.displayValue,
    tbt: audits['total-blocking-time']?.displayValue,
  };
  results.push(row);
  console.log(`${path.padEnd(42)} P${row.performance} A${row.accessibility} BP${row.bestPractices} SEO${row.seo}  LCP ${row.lcp} / CLS ${row.cls} / TBT ${row.tbt}`);
}
await chrome.kill();
server.kill();
mkdirSync(join(root, 'qa-output'), { recursive: true });
writeFileSync(join(root, 'qa-output', 'lighthouse.json'), JSON.stringify({ measuredAt: new Date().toISOString(), results }, null, 2));
