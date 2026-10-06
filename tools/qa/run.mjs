/**
 * 全ページの品質チェック
 *
 *   node tools/qa/run.mjs [--dir=docs] [--site=skin-clinic] [--no-shots]
 *
 * manifest.json に載っている全ページを 375 / 768 / 1440px で開き、次を検査する。
 *   - 横スクロールの発生
 *   - コンソールエラー・読み込み失敗
 *   - 読み込み直後（スクロール前）に透明のまま待機している本文がないか
 *   - JavaScript 無効時に本文が隠れていないか
 *   - axe-core による WCAG 2.1 AA の違反（serious / critical）
 * スクリーンショットは qa-output/screens/ に、結果は qa-output/report.json に保存する。
 */
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import { spawn } from 'node:child_process';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const args = Object.fromEntries(process.argv.slice(2).map((a) => {
  const [k, v] = a.replace(/^--/, '').split('=');
  return [k, v ?? true];
}));
const dir = join(root, args.dir ?? 'docs');
const manifest = JSON.parse(readFileSync(join(dir, 'manifest.json'), 'utf8'));
const widths = [375, 768, 1440];
const port = 8400 + Math.floor(Math.random() * 400);
const outDir = join(root, 'qa-output');
mkdirSync(join(outDir, 'screens'), { recursive: true });

const server = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', dir], { stdio: 'ignore' });
await new Promise((r) => setTimeout(r, 800));

const pages = [];
for (const [site, info] of Object.entries(manifest.sites)) {
  if (args.site && args.site !== site) continue;
  for (const page of info.pages) pages.push({ site, path: page.path });
}

// 本文（見出し・段落・リスト・表）のうち、読み込み直後に透明・不可視のものを数える
const hiddenTextProbe = () => {
  const nodes = document.querySelectorAll('main h1, main h2, main h3, main p, main li, main td, main th, main dt, main dd');
  const hidden = [];
  for (const el of nodes) {
    if (!el.textContent.trim() || el.closest('[hidden], [aria-hidden="true"], template, dialog:not([open])')) continue;
    let node = el;
    let invisible = false;
    while (node && node !== document.body) {
      const style = getComputedStyle(node);
      if (Number(style.opacity) < 0.05 || style.visibility === 'hidden') { invisible = true; break; }
      node = node.parentElement;
    }
    if (invisible) hidden.push(el.textContent.trim().slice(0, 30));
  }
  return hidden;
};

const browser = await chromium.launch();
const results = [];
let failures = 0;

for (const { site, path } of pages) {
  const url = `http://127.0.0.1:${port}/${path}`;
  const result = { site, path, overflow: {}, consoleErrors: [], hiddenAtRest: [], hiddenNoJs: [], axe: [] };

  for (const width of widths) {
    const context = await browser.newContext({ viewport: { width, height: 900 } });
    const page = await context.newPage();
    page.on('console', (m) => { if (m.type() === 'error') result.consoleErrors.push(`${width}px: ${m.text()}`); });
    page.on('pageerror', (e) => result.consoleErrors.push(`${width}px: ${e}`));
    page.on('requestfailed', (r) => result.consoleErrors.push(`${width}px: 読み込み失敗 ${r.url()}`));
    await page.goto(url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);
    result.overflow[width] = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    if (width === 1440) {
      result.hiddenAtRest = await page.evaluate(hiddenTextProbe);
      const axe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
      result.axe = axe.violations
        .filter((v) => v.impact === 'serious' || v.impact === 'critical')
        .map((v) => ({ id: v.id, impact: v.impact, nodes: v.nodes.length, help: v.help, target: v.nodes[0]?.target?.join(' ') }));
    }
    if (!args['no-shots']) {
      const name = path.replace(/\//g, '__').replace(/\.html$/, '');
      await page.screenshot({ path: join(outDir, 'screens', `${name}-${width}.png`), fullPage: true });
    }
    await context.close();
  }

  const noJs = await browser.newContext({ viewport: { width: 1440, height: 900 }, javaScriptEnabled: false });
  const noJsPage = await noJs.newPage();
  await noJsPage.goto(url, { waitUntil: 'networkidle' });
  result.hiddenNoJs = await noJsPage.evaluate(hiddenTextProbe);
  await noJs.close();

  const problems = [
    ...Object.entries(result.overflow).filter(([, px]) => px > 0).map(([w, px]) => `横スクロール ${w}px幅で${px}px`),
    ...result.consoleErrors,
    ...(result.hiddenAtRest.length ? [`読み込み直後に見えない本文 ${result.hiddenAtRest.length}件: ${result.hiddenAtRest.slice(0, 3).join(' / ')}`] : []),
    ...(result.hiddenNoJs.length ? [`JS無効時に見えない本文 ${result.hiddenNoJs.length}件: ${result.hiddenNoJs.slice(0, 3).join(' / ')}`] : []),
    ...result.axe.map((v) => `axe ${v.impact}: ${v.id}（${v.nodes}箇所）${v.target ?? ''}`),
  ];
  result.ok = problems.length === 0;
  if (!result.ok) failures++;
  results.push(result);
  console.log(`${result.ok ? 'OK  ' : 'NG  '} ${path}${problems.length ? '\n      - ' + problems.join('\n      - ') : ''}`);
}

await browser.close();
server.kill();
writeFileSync(join(outDir, 'report.json'), JSON.stringify(results, null, 2));
console.log(`\n${results.length} ページ中 ${results.length - failures} ページ合格`);
process.exit(failures ? 1 : 0);
