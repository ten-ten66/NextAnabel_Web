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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Murecho:wght@400;700&amp;family=IBM+Plex+Mono:wght@500&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</head>
<body>
<a class="c-skip-link" href="#main">本文へスキップ</a>
