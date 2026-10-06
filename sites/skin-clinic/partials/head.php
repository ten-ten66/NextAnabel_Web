<?php
/**
 * <head> と本文の冒頭
 * @var array<string, mixed> $page
 */
?>
<!DOCTYPE html>
<html lang="ja" data-env="<?= is_static() ? 'static' : 'server' ?>" data-build="<?= e(BUILD_ENV) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= seo_meta($page) ?>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Shippori+Mincho+B1:wght@500&amp;family=Zen+Kaku+Gothic+New:wght@400;700&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<noscript><style>.u-js-only{display:none!important}</style></noscript>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</head>
<body data-page="<?= e($page['id'] ?? '') ?>"<?= !empty($page['bodyClass']) ? ' class="' . e($page['bodyClass']) . '"' : '' ?>>
<a class="c-skip-link" href="#main">本文へスキップ</a>
<aside class="c-demo-notice" aria-label="このサイトについて" data-lint-ignore>
  <p class="c-demo-notice__text">このサイトはWeb制作のサンプルとして作成した架空のクリニックです。実在の医療機関・人物とは関係ありません。</p>
</aside>
