<?php
/** @var array<string, mixed> $page */
?>
<!DOCTYPE html>
<html lang="ja" data-env="<?= is_static() ? 'static' : 'server' ?>" data-build="<?= e(BUILD_ENV) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= seo_meta($page) ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;700&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</head>
<body class="<?= e($page['bodyClass'] ?? '') ?>">
<a class="c-skip-link" href="#main">本文へスキップ</a>
<p class="c-demo-notice" data-lint-ignore>このサイトはWeb制作のサンプルとして作成した架空のクリニックです。実在の医療機関・人物とは関係ありません。</p>
