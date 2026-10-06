<?php
/**
 * 静的ビルドの設定（tools/build.php が読み込む）
 */

return [
    'base' => '_template',
    'profile' => 'medical',
    'compliance_data' => [],
    'pages' => static fn (): array => [
        ['src' => 'index.php'],
    ],
    'copy' => ['assets'],
];
