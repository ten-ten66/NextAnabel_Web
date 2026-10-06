<?php
/**
 * 静的ビルドの設定（tools/build.php が読み込む）
 *
 * kind => 'treatment' にすると、表現チェックが施術ページの必須表示
 * （費用・回数・リスク・ダウンタイム・問い合わせ先・監修医）をすべて検査する。
 * api/ はサーバー版専用のため copy に含めない（静的版ではフォームをデモ表示にする）。
 */

return [
    'base' => 'lp',
    'profile' => 'medical',
    'compliance_data' => [],
    'pages' => static fn (): array => [
        ['src' => 'index.php', 'kind' => 'treatment'],
    ],
    'copy' => ['assets'],
];
