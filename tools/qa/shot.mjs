/**
 * ページのスクリーンショットと簡易チェック（横スクロール・コンソールエラー）
 *
 *   node tools/qa/shot.mjs <URL> <出力.png> [--width=1440] [--height=900] [--full]
 *                          [--no-js] [--reduced-motion] [--wait=1200]
 *
 * 結果は1行の JSON で出力する（overflowPx が 0 より大きければ横スクロールが発生している）。
 */
import { chromium } from 'playwright';

const [url, out, ...rest] = process.argv.slice(2);
if (!url || !out) {
  console.error('使い方: node tools/qa/shot.mjs <URL> <出力.png> [--width=1440] [--full] [--no-js] [--reduced-motion]');
  process.exit(1);
}
const opts = Object.fromEntries(rest.map((arg) => {
  const [key, value] = arg.replace(/^--/, '').split('=');
  return [key, value ?? true];
}));

const width = Number(opts.width ?? 1440);
const browser = await chromium.launch();
const context = await browser.newContext({
  viewport: { width, height: Number(opts.height ?? 900) },
  deviceScaleFactor: 1,
  javaScriptEnabled: !opts['no-js'],
  reducedMotion: opts['reduced-motion'] ? 'reduce' : 'no-preference',
});
const page = await context.newPage();
const errors = [];
page.on('console', (msg) => { if (msg.type() === 'error') errors.push(msg.text()); });
page.on('pageerror', (err) => errors.push(String(err)));
page.on('requestfailed', (req) => errors.push(`request failed: ${req.url()}`));

await page.goto(url, { waitUntil: 'networkidle' });
if (opts.full) await scrollThrough(page, !opts['no-js']);
await page.waitForTimeout(Number(opts.wait ?? 1200));
const overflowPx = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
await page.screenshot({ path: out, fullPage: Boolean(opts.full) });
console.log(JSON.stringify({ url, out, width, overflowPx, consoleErrors: errors }));
await browser.close();

/**
 * 全体を撮る前にページ末尾までスクロールし、loading="lazy" の画像を読み込ませてから先頭に戻る。
 * JavaScript 無効時はページ内でスクリプトを動かせないため、マウスホイールでスクロールする。
 */
async function scrollThrough(target, jsEnabled) {
  if (jsEnabled) {
    await target.evaluate(async () => {
      const step = Math.max(200, Math.floor(window.innerHeight * 0.8));
      for (let y = 0; y < document.documentElement.scrollHeight; y += step) {
        window.scrollTo(0, y);
        await new Promise((r) => setTimeout(r, 60));
      }
      window.scrollTo(0, 0);
    });
  } else {
    for (let i = 0; i < 80; i++) {
      await target.mouse.wheel(0, 700);
      await target.waitForTimeout(30);
    }
    await target.waitForLoadState('networkidle');
    await target.mouse.wheel(0, -1000000);
  }
  await target.waitForLoadState('networkidle');
}
