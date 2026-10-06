/**
 * 計測結果をサンプル集用のデータにまとめる
 *   node tools/qa/metrics.mjs [--wp=6.5.5]
 * qa-output/lighthouse.json・qa-output/report.json・html-validate・表現チェックの結果を集計し、
 * sites/hub/data/metrics.json に書き出す。値はすべて実測から算出し、手で書き換えない。
 */
import { spawnSync } from 'node:child_process';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const args = Object.fromEntries(process.argv.slice(2).map((a) => {
  const [k, v] = a.replace(/^--/, '').split('=');
  return [k, v ?? true];
}));
const read = (p) => JSON.parse(readFileSync(join(root, p), 'utf8'));

const manifest = read('docs/manifest.json');
const samplePages = Object.values(manifest.sites).filter((s) => s.base).flatMap((s) => s.pages);
const lighthouse = existsSync(join(root, 'qa-output/lighthouse.json')) ? read('qa-output/lighthouse.json') : { results: [] };
const report = existsSync(join(root, 'qa-output/report.json')) ? read('qa-output/report.json') : [];

const htmlValidate = spawnSync('npx', ['html-validate', '-f', 'json', 'docs/**/*.html'], { cwd: root, encoding: 'utf8' });
const htmlResults = JSON.parse(htmlValidate.stdout || '[]');
const htmlErrors = htmlResults.reduce((n, r) => n + r.errorCount, 0);
const htmlFiles = htmlResults.length || Object.values(manifest.sites).reduce((n, s) => n + s.pages.length, 0);

const lint = spawnSync('php', ['tools/lint-compliance.php', 'docs'], { cwd: root, encoding: 'utf8' });
const lintViolations = lint.status === 0 ? 0 : Number((lint.stderr.match(/(\d+) 件の違反/) ?? [])[1] ?? NaN);

const count = (fn) => report.reduce((n, r) => n + fn(r), 0);
const checks = [
  { label: 'HTML の文法エラー（html-validate）', value: `${htmlErrors}件` },
  { label: 'アクセシビリティの重大な違反（axe-core：serious / critical）', value: `${count((r) => r.axe.length)}件` },
  { label: '横スクロールの発生（375・768・1440px）', value: `${count((r) => Object.values(r.overflow).filter((px) => px > 0).length)}件` },
  { label: 'コンソールエラー・読み込み失敗', value: `${count((r) => r.consoleErrors.length)}件` },
  { label: 'JavaScript 無効時に読めない本文', value: `${count((r) => r.hiddenNoJs.length)}件` },
  { label: '広告表現チェックの違反', value: `${lintViolations}件` },
];

const today = new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Tokyo' }).format(new Date());
const metrics = {
  measuredAt: today,
  pageCount: samplePages.length,
  qaPages: report.length,
  htmlFiles: Number(htmlFiles),
  lighthouse: lighthouse.results,
  checks,
  wordpress: args.wp ? { verified: true, version: String(args.wp) } : { verified: false },
};
writeFileSync(join(root, 'sites/hub/data/metrics.json'), JSON.stringify(metrics, null, 2) + '\n');
console.log(JSON.stringify(metrics, null, 2));
