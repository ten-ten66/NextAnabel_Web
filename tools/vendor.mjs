/**
 * npm で入れたライブラリを各サイトの assets/vendor/ に複製する。
 * 複製するファイルは sites/<site>/vendor.json に node_modules からの相対パスで列挙する。
 * CDN に依存しないのは、Artifact の CSP と GitHub Pages の両方で同じファイルを動かすため。
 */
import { copyFileSync, existsSync, mkdirSync, readFileSync, readdirSync } from 'node:fs';
import { basename, dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');

for (const site of readdirSync(join(root, 'sites'))) {
  const listFile = join(root, 'sites', site, 'vendor.json');
  if (!existsSync(listFile)) continue;
  const files = JSON.parse(readFileSync(listFile, 'utf8'));
  const dest = join(root, 'sites', site, 'assets', 'vendor');
  mkdirSync(dest, { recursive: true });
  for (const rel of files) {
    copyFileSync(join(root, 'node_modules', rel), join(dest, basename(rel)));
    console.log(`${site}: ${rel}`);
  }
}
