/**
 * OGP画像（1200×630）の書き出し
 *   node tools/og-image.mjs <site>
 * sites/<site>/og.html を描画して sites/<site>/assets/img/ogp.png に保存する。
 */
import { chromium } from 'playwright';
import { existsSync, statSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const site = process.argv[2];
const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const source = join(root, 'sites', site ?? '', 'og.html');
if (!site || !existsSync(source)) {
  console.error(`og.html が見つかりません: ${source}`);
  process.exit(1);
}
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1200, height: 630 }, deviceScaleFactor: 1 });
await page.goto(pathToFileURL(source).href, { waitUntil: 'networkidle' });
await page.evaluate(() => document.fonts.ready);
const out = join(root, 'sites', site, 'assets', 'img', 'ogp.png');
await page.screenshot({ path: out });
await browser.close();
// 画像1枚 200KB の予算を超えたら、256色に減色して保存し直す（Pillow を使用）
if (statSync(out).size > 200 * 1024) {
  execFileSync('python3', ['-c', 'import sys; from PIL import Image; p = sys.argv[1]; Image.open(p).convert("RGB").quantize(256, method=Image.Quantize.MEDIANCUT, dither=Image.Dither.FLOYDSTEINBERG).save(p, optimize=True)', out]);
}
console.log(`${out} (${Math.round(statSync(out).size / 1024)}KB)`);
