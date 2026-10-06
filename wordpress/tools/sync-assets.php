<?php
/**
 * 静的サイト版（sites/skin-clinic/assets）の CSS・JavaScript・画像を、テーマの assets にコピーする
 *
 *   php wordpress/tools/sync-assets.php
 *
 * デザインの元データは静的サイト版に一本化し、テーマ側では直接編集しない（WordPress 専用の差分は assets/css/theme.css に書く）。
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$from = $root . '/sites/skin-clinic/assets';
$to = $root . '/wordpress/hakuji-skin-clinic/assets';
$files = ['css/style.css', 'js/main.js', 'js/home.js'];
foreach (glob($from . '/img/*') ?: [] as $image) {
    // OGP 画像は静的サイト版の各ページ用なので、テーマには含めない
    if (basename($image) !== 'ogp.png') {
        $files[] = 'img/' . basename($image);
    }
}
foreach ($files as $file) {
    $dest = $to . '/' . $file;
    if (!is_dir(dirname($dest)) && !mkdir(dirname($dest), 0777, true)) {
        fwrite(STDERR, "作成できません: " . dirname($dest) . "\n");
        exit(1);
    }
    if (!copy($from . '/' . $file, $dest)) {
        fwrite(STDERR, "コピーできません: {$file}\n");
        exit(1);
    }
    echo $file, "\n";
}
