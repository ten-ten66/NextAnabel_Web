<?php
/** @var array<string, mixed> $page */
$bodyClass = trim('p-page--' . ($page['id'] ?? 'page') . ' ' . ($page['bodyClass'] ?? ''));
// Bodoni Moda はロゴタイプと数字だけに使うため、text= で必要な字形だけを読み込む
$accentGlyphs = rawurlencode('ORVANE0123456789:–.-');
?>
<!DOCTYPE html>
<html lang="ja" data-env="<?= is_static() ? 'static' : 'server' ?>" data-build="<?= e(BUILD_ENV) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= seo_meta($page) ?>
<meta name="color-scheme" content="dark">
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+JP:wght@400;500&amp;family=Zen+Old+Mincho:wght@500;700&amp;display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@1,6..96,400&amp;text=<?= e($accentGlyphs) ?>&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<script>document.documentElement.classList.add('is-js');</script>
<script src="<?= e(asset('vendor/gsap.min.js')) ?>" defer></script>
<script src="<?= e(asset('vendor/ScrollTrigger.min.js')) ?>" defer></script>
<script src="<?= e(asset('vendor/SplitText.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="c-skip-link" href="#main">本文へスキップ</a>
<p class="c-demo-notice" data-lint-ignore>このサイトはWeb制作のサンプルとして作成した架空のクリニックです。実在の医療機関・人物とは関係ありません。</p>
