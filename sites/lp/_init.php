<?php
/**
 * 医療脱毛LPの初期化。各ページ（index.php・api/reserve.php）の先頭で require する。
 */

declare(strict_types=1);

define('SITE_DIR', __DIR__);
require dirname(__DIR__, 2) . '/core/bootstrap.php';
site_config(require __DIR__ . '/data/site.php');
require __DIR__ . '/lib/plan.php';
require __DIR__ . '/lib/reservation.php';
