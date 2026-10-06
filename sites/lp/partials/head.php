<?php
/**
 * @var array<string, mixed> $page
 * @var string|null $csrf サーバー版のみ。静的版では null
 */
?>
<!DOCTYPE html>
<html lang="ja" data-env="<?= is_static() ? 'static' : 'server' ?>" data-build="<?= e(BUILD_ENV) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?= seo_meta($page) ?>
<?php if (!empty($csrf)): ?>
<meta name="csrf-token" content="<?= e($csrf) ?>">
<?php endif; ?>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;700;900&amp;display=swap">
<?php // 明朝体はファーストビューの見出し（partials/section-fv.php）だけに使うため、その文字だけを読み込む ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Shippori+Mincho+B1:wght@600&amp;text=<?= e(rawurlencode('医療脱毛は、総額と回数を知ってから。')) ?>&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</head>
<body>
<a class="c-skip-link" href="#main">本文へスキップ</a>
<p class="c-demo-notice" data-lint-ignore>Web制作のサンプルとして作成した、架空のクリニックの広告用LPです。実在の医療機関・人物とは関係なく、料金・内容も架空です。</p>
<?php partial('icons'); ?>
