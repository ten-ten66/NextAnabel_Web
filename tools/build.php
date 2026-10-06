<?php
/**
 * 静的サイトのビルド
 *
 *   php tools/build.php                     # demo 版を docs/ に出力（GitHub Pages 用）
 *   php tools/build.php --env=production --out=build/production
 *   php tools/build.php --site=skin-clinic  # 指定サイトのみ
 *   php tools/build.php --fonts=google      # Webフォントを Google Fonts から読む（Artifact 用）
 *
 * 各サイトの build.config.php が返す設定に従い、PHPページを別プロセスで描画して HTML を書き出す。
 * PHPの警告が1件でも出る、または表現チェック（tools/lint-compliance.php）に違反があると失敗する。
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('BUILD_MODE', 'static');
require ROOT . '/core/helpers.php';

$options = getopt('', ['env:', 'out:', 'origin:', 'site:', 'fonts:', 'no-lint']);
$fonts = in_array($options['fonts'] ?? 'self', ['self', 'google', 'none'], true) ? ($options['fonts'] ?? 'self') : 'self';
$env = ($options['env'] ?? 'demo') === 'production' ? 'production' : 'demo';
$outRel = trim((string) ($options['out'] ?? 'docs'), '/');
$origin = rtrim((string) ($options['origin'] ?? 'https://ten-ten66.github.io/NextAnabel_Web'), '/');
$only = isset($options['site']) ? array_filter(explode(',', (string) $options['site'])) : null;

$outDir = ROOT . '/' . $outRel;
if ($outRel === '' || preg_match('#(^|/)\.\.(/|$)#', $outRel) || in_array(explode('/', $outRel)[0], ['core', 'sites', 'tools', 'tests', 'wordpress', 'tokens', 'node_modules'], true)) {
    fail("出力先が不正です: {$outRel}");
}

$sites = [];
foreach (glob(ROOT . '/sites/*/build.config.php') ?: [] as $configFile) {
    $name = basename(dirname($configFile));
    // _ で始まるディレクトリ（雛形）は明示指定したときだけビルドする
    if ($only === null ? !str_starts_with($name, '_') : in_array($name, $only, true)) {
        $sites[$name] = require $configFile;
    }
}
if (!$sites) {
    fail('ビルド対象のサイトがありません。');
}

if ($only === null) {
    remove_tree($outDir);
}
if (!is_dir($outDir) && !mkdir($outDir, 0775, true)) {
    fail("出力先を作成できません: {$outDir}");
}

$manifestFile = $outDir . '/manifest.json';
$manifest = is_file($manifestFile) ? (json_decode((string) file_get_contents($manifestFile), true) ?: []) : [];
$manifest['env'] = $env;
$manifest['origin'] = $origin;
$manifest['sites'] ??= [];

$errors = [];
$started = microtime(true);

foreach ($sites as $name => $config) {
    $siteDir = ROOT . '/sites/' . $name;
    $base = trim((string) ($config['base'] ?? $name), '/');
    $dest = $outDir . ($base !== '' ? '/' . $base : '');
    if ($only !== null && $base !== '') {
        remove_tree($dest);
    }
    if (!is_dir($dest)) {
        mkdir($dest, 0775, true);
    }

    $pages = is_callable($config['pages']) ? ($config['pages'])() : $config['pages'];
    $entries = [];
    foreach ($pages as $page) {
        $src = (string) $page['src'];
        $query = (array) ($page['query'] ?? []);
        $outName = $page['out'] ?? static_filename(basename($src, '.php'), $query);
        [$html, $stderr, $code] = render_page($siteDir, $src, $query, $outName, $env, $origin);
        if ($code !== 0 || trim($stderr) !== '') {
            $errors[] = "{$name}/{$outName}: 描画に失敗しました（終了コード {$code}）\n" . trim($stderr);
            continue;
        }
        file_put_contents($dest . '/' . $outName, $html);
        $entries[] = [
            'path' => ($base !== '' ? $base . '/' : '') . $outName,
            'kind' => $page['kind'] ?? 'page',
        ];
    }

    foreach ((array) ($config['copy'] ?? ['assets']) as $item) {
        $from = $siteDir . '/' . $item;
        if (is_dir($from)) {
            copy_tree($from, $dest . '/' . $item);
        } elseif (is_file($from)) {
            copy($from, $dest . '/' . $item);
        }
    }

    if ($fonts !== 'none') {
        // 日本語Webフォントをサイト内で使う文字だけに絞る（失敗しても Google Fonts のまま続行）
        $loading = ($config['fonts_loading'] ?? 'after-paint') === 'async' ? 'async' : 'after-paint';
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(ROOT . '/tools/fonts.php') . ' ' . escapeshellarg($dest) . ' ' . $fonts . ' ' . $loading);
    }

    if ($env === 'production' && $base !== '') {
        write_sitemap($dest, $origin . '/' . $base, $entries);
    }

    $manifest['sites'][$name] = [
        'base' => $base,
        'profile' => $config['profile'] ?? 'none',
        'data' => $config['compliance_data'] ?? [],
        'pages' => $entries,
    ];
    printf("  %-16s %2d ページ\n", $name, count($entries));
}

file_put_contents($outDir . '/.nojekyll', '');
file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

if ($errors) {
    fail(implode("\n\n", $errors));
}

if (!isset($options['no-lint'])) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(ROOT . '/tools/lint-compliance.php') . ' ' . escapeshellarg($outDir), $lintCode);
    if ($lintCode !== 0) {
        fail('表現チェックに違反があります。');
    }
}

printf("ビルド完了（%s / %.1f秒）→ %s\n", $env, microtime(true) - $started, $outRel);

// ---------------------------------------------------------------------------

/**
 * @param array<string, string> $query
 * @return array{0: string, 1: string, 2: int} [HTML, 標準エラー, 終了コード]
 */
function render_page(string $siteDir, string $src, array $query, string $outName, string $env, string $origin): array
{
    $cmd = [
        PHP_BINARY,
        '-d', 'display_errors=stderr',
        '-d', 'error_reporting=' . E_ALL,
        ROOT . '/tools/render.php',
        $siteDir,
        $src,
        json_encode($query, JSON_THROW_ON_ERROR),
    ];
    $envVars = array_merge(getenv(), [
        'SITE_BUILD' => 'static',
        'SITE_ENV' => $env,
        'SITE_ORIGIN' => $origin,
        'PAGE_OUT' => $outName,
    ]);
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $siteDir, $envVars);
    if (!is_resource($proc)) {
        return ['', 'プロセスを起動できません', 1];
    }
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [$stdout, $stderr, proc_close($proc)];
}

/** @param list<array{path: string, kind: string}> $entries */
function write_sitemap(string $dest, string $baseUrl, array $entries): void
{
    $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
    foreach ($entries as $entry) {
        $file = basename($entry['path']);
        if ($file === '404.html') {
            continue;
        }
        $loc = $baseUrl . '/' . ($file === 'index.html' ? '' : $file);
        $xml[] = '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc></url>';
    }
    $xml[] = '</urlset>';
    file_put_contents($dest . '/sitemap.xml', implode("\n", $xml) . "\n");
}

function copy_tree(string $from, string $to): void
{
    if (!is_dir($to)) {
        mkdir($to, 0775, true);
    }
    foreach (scandir($from) ?: [] as $item) {
        if ($item === '.' || $item === '..' || $item === '.DS_Store') {
            continue;
        }
        $src = $from . '/' . $item;
        $dst = $to . '/' . $item;
        is_dir($src) ? copy_tree($src, $dst) : copy($src, $dst);
    }
}

function remove_tree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

function fail(string $message): never
{
    fwrite(STDERR, "ビルド失敗: {$message}\n");
    exit(1);
}
