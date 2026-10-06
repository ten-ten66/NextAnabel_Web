<?php
/**
 * 白磁スキンクリニック: 初期化。各ページの先頭で require する。
 */

declare(strict_types=1);

define('SITE_DIR', __DIR__);
require dirname(__DIR__, 2) . '/core/bootstrap.php';
site_config(require __DIR__ . '/data/site.php');
require __DIR__ . '/_helpers.php';
