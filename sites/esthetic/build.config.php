<?php
/**
 * 静的ビルドの設定（tools/build.php が読み込む）
 */

return [
    'base' => 'esthetic',
    'profile' => 'esthetic',
    // 表現チェックはデータ種別 treatments（医療機関の施術データ）だけを検査する。エステのメニューは記録のみ。
    'compliance_data' => ['menu' => 'data/menu.php'],
    'pages' => static fn (): array => [
        ['src' => 'index.php'],
        ['src' => 'menu.php'],
        ['src' => '404.php'],
    ],
    'copy' => ['assets'],
];
