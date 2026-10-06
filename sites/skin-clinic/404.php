<?php
require __DIR__ . '/_init.php';

if (!is_static()) {
    http_response_code(404);
}

$page = [
    'id' => '404',
    'title' => 'ページが見つかりません',
    'description' => 'お探しのページは、移動または削除された可能性があります。白磁スキンクリニックのトップページや施術一覧からお探しください。',
];
partial('head', compact('page'));
partial('header', compact('page'));
partial('not-found');
partial('footer', compact('page'));
