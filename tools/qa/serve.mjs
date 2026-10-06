/**
 * 計測用の静的サーバー（GitHub Pages と同じく gzip 圧縮と短いキャッシュで配信する）
 *
 *   node tools/qa/serve.mjs <ディレクトリ> [ポート]
 *
 * php -S は圧縮しないため、Lighthouse の値が実際の公開環境より低く出る。
 * 公開先の配信条件に近づけて計測するためのサーバー。
 */
import { createServer } from 'node:http';
import { createReadStream, statSync } from 'node:fs';
import { extname, join, normalize, resolve } from 'node:path';
import { createGzip } from 'node:zlib';

const types = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.mjs': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.webp': 'image/webp',
  '.jpg': 'image/jpeg',
  '.woff2': 'font/woff2',
  '.xml': 'application/xml; charset=utf-8',
  '.txt': 'text/plain; charset=utf-8',
};
const compressible = new Set(['.html', '.css', '.js', '.mjs', '.json', '.svg', '.xml', '.txt']);

export function serve(dir, port) {
  const root = resolve(dir);
  const server = createServer((req, res) => {
    let path = decodeURIComponent(new URL(req.url, 'http://localhost').pathname);
    if (path.endsWith('/')) path += 'index.html';
    const file = normalize(join(root, path));
    if (!file.startsWith(root)) {
      res.writeHead(403).end();
      return;
    }
    let stat;
    try {
      stat = statSync(file);
    } catch {
      res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' }).end('Not Found');
      return;
    }
    if (stat.isDirectory()) {
      res.writeHead(301, { Location: `${path}/` }).end();
      return;
    }
    const ext = extname(file);
    const headers = { 'Content-Type': types[ext] ?? 'application/octet-stream', 'Cache-Control': 'max-age=600' };
    if (compressible.has(ext) && /\bgzip\b/.test(req.headers['accept-encoding'] ?? '')) {
      res.writeHead(200, { ...headers, 'Content-Encoding': 'gzip', Vary: 'Accept-Encoding' });
      createReadStream(file).pipe(createGzip({ level: 6 })).pipe(res);
      return;
    }
    res.writeHead(200, { ...headers, 'Content-Length': stat.size });
    createReadStream(file).pipe(res);
  });
  return new Promise((ok) => server.listen(port, '127.0.0.1', () => ok(server)));
}

if (import.meta.url === `file://${process.argv[1]}`) {
  const [dir = 'docs', port = '8090'] = process.argv.slice(2);
  await serve(dir, Number(port));
  console.log(`http://127.0.0.1:${port}/ （${dir} を gzip 圧縮で配信）`);
}
