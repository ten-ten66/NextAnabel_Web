<?php
/**
 * 静的ビルドの設定（tools/build.php が読み込む）
 *
 * kind => 'treatment' にすると、表現チェックが施術ページの必須表示
 * （費用・回数・リスク・ダウンタイム・問い合わせ先・監修医）をすべて検査する。
 * api/ はサーバー版専用のため copy に含めない（静的版ではフォームをデモ表示にする）。
 *
 * fonts_loading: 文字の多い1ページ構成のため、Webフォントは async（media="print" → all）で読み込む。
 * 既定の after-paint では、最初の描画のあとにフォントの差し替えでページ全体を組み直す処理が入り、
 * Lighthouse の TBT が 0ms → 450〜640ms、スコアが 91 → 88 に下がった（各5回計測の中央値）。
 */

return [
    'base' => 'lp',
    'profile' => 'medical',
    'compliance_data' => [],
    'pages' => static fn (): array => [
        ['src' => 'index.php', 'kind' => 'treatment'],
    ],
    'copy' => ['assets'],
    'fonts_loading' => 'async',
];
