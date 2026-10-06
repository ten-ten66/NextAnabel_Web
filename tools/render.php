<?php
/**
 * 1ページを静的HTMLとして描画し、標準出力に書き出す（tools/build.php から別プロセスで呼ばれる）。
 * ページごとにプロセスを分けることで、定数やグローバル変数がページ間で混ざらない。
 *
 * 使い方: php tools/render.php <サイトのディレクトリ> <PHPファイル> <クエリのJSON>
 * 環境変数: SITE_BUILD=static SITE_ENV=demo|production SITE_ORIGIN=... PAGE_OUT=出力ファイル名
 *
 * 警告・通知が1件でも出たら終了コード 2 で終わる（ビルドを失敗させる）。
 */

declare(strict_types=1);

$siteDir = $argv[1] ?? '';
$script = $argv[2] ?? '';
$query = json_decode($argv[3] ?? '{}', true);

if (!is_dir($siteDir) || !is_file($siteDir . '/' . $script) || !is_array($query)) {
    fwrite(STDERR, "render.php: 引数が不正です\n");
    exit(1);
}

$GLOBALS['__render_warnings'] = 0;
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    fwrite(STDERR, "PHP 警告: {$message} ({$file}:{$line})\n");
    $GLOBALS['__render_warnings']++;
    return true;
});
register_shutdown_function(static function (): void {
    if ($GLOBALS['__render_warnings'] > 0) {
        exit(2);
    }
});

$_GET = $query;
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/' . $script;
$_SERVER['REQUEST_URI'] = '/' . $script . ($query ? '?' . http_build_query($query) : '');
$_SERVER['HTTP_HOST'] = 'localhost';

chdir($siteDir);
require $siteDir . '/' . $script;
