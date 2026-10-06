<?php
/**
 * 共通ブートストラップ
 *
 * 実行モード（BUILD_MODE）
 *   server : PHPサーバー上で動作する。フォーム送信が有効。
 *   static : tools/build.php による静的書き出し。フォームはデモ表示になる。
 *
 * 公開環境（BUILD_ENV）
 *   demo       : 架空サイトとして公開する版。全ページに noindex を付与する。
 *   production : 本番想定の版。noindex を付けない（Lighthouse 計測用）。
 */

declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));
define('CORE_DIR', __DIR__);
define('STORAGE_DIR', ROOT_DIR . '/storage');

define('BUILD_MODE', getenv('SITE_BUILD') === 'static' ? 'static' : 'server');
define('BUILD_ENV', getenv('SITE_ENV') === 'production' ? 'production' : 'demo');
define('SITE_ORIGIN', rtrim((string) (getenv('SITE_ORIGIN') ?: 'https://ten-ten66.github.io/NextAnabel_Web'), '/'));

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tokyo');

// Core\Form\Csrf → core/form/Csrf.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'Core\\Form\\';
    if (str_starts_with($class, $prefix)) {
        $file = CORE_DIR . '/form/' . substr($class, strlen($prefix)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require_once CORE_DIR . '/helpers.php';
require_once CORE_DIR . '/seo.php';
