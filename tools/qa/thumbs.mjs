/**
 * サンプル集に載せるサムネイルの撮影
 *   node tools/qa/thumbs.mjs
 * docs/ の各サンプルのトップを PC（1440×900）とスマートフォン（390×844）で撮影し、
 * qa-output/thumbs/ に PNG で保存する（WebP への変換は tools/qa/thumbs.py）。
 */
import { chromium } from 'playwright';
import { spawn } from 'node:child_process';
import { mkdirSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const docs = join(root, 'docs');
const manifest = JSON.parse(readFileSync(join(docs, 'manifest.json'), 'utf8'));
const out = join(root, 'qa-output', 'thumbs');
mkdirSync(out, { recursive: true });
const port = 8600 + Math.floor(Math.random() * 100);
const server = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', docs], { stdio: 'ignore' });
await new Promise((r) => setTimeout(r, 800));

const browser = await chromium.launch();
for (const [site, info] of Object.entries(manifest.sites)) {
  if (!info.base) continue;
  for (const [kind, viewport] of [['desktop', { width: 1440, height: 900 }], ['mobile', { width: 390, height: 844 }]]) {
    const context = await browser.newContext({ viewport, deviceScaleFactor: kind === 'mobile' ? 2 : 1 });
    const page = await context.newPage();
    await page.goto(`http://127.0.0.1:${port}/${info.base}/index.html`, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(1800);
    await page.screenshot({ path: join(out, `${site}-${kind}.png`) });
    await context.close();
    console.log(`${site}-${kind}.png`);
  }
}
await browser.close();
server.kill();
