<?php
/**
 * <head> と body の開始（スキップリンク・架空サイトの注記・アイコン定義）
 *
 * $page のキー（core/seo.php の説明に加えて）
 *   vendor     読み込むライブラリ ['gsap', 'swiper']
 *   bodyClass  body の class
 *
 * @var array<string, mixed> $page
 */
$vendor = (array) ($page['vendor'] ?? []);

// 欧文の装飾書体は、サイト設定に書いた文字だけを読み込む（Google Fonts の text= サブセット）
$glyphs = array_values(array_unique(mb_str_split((string) preg_replace('/\s+/u', '', (string) site('accent_glyphs')))));
sort($glyphs);
$accentFont = 'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@1,9..144,400&text='
    . rawurlencode(implode('', $glyphs)) . '&display=swap';
?>
<!DOCTYPE html>
<html lang="ja" class="no-js" data-env="<?= is_static() ? 'static' : 'server' ?>" data-build="<?= e(BUILD_ENV) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="format-detection" content="telephone=no">
<script>document.documentElement.classList.replace('no-js', 'js'); document.documentElement.classList.add('is-intro');</script>
<?= seo_meta($page) ?>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Klee+One:wght@400;600&amp;family=Zen+Kaku+Gothic+Antique:wght@400;500&amp;display=swap">
<link rel="stylesheet" href="<?= e($accentFont) ?>">
<?php if (in_array('swiper', $vendor, true)): ?>
<link rel="stylesheet" href="<?= e(asset('vendor/swiper-bundle.min.css')) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<?php if (in_array('gsap', $vendor, true)): ?>
<script src="<?= e(asset('vendor/gsap.min.js')) ?>" defer></script>
<script src="<?= e(asset('vendor/ScrollTrigger.min.js')) ?>" defer></script>
<script src="<?= e(asset('vendor/DrawSVGPlugin.min.js')) ?>" defer></script>
<?php endif; ?>
<?php if (in_array('swiper', $vendor, true)): ?>
<script src="<?= e(asset('vendor/swiper-bundle.min.js')) ?>" defer></script>
<?php endif; ?>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</head>
<body class="<?= e($page['bodyClass'] ?? '') ?>">
<?php partial('icons'); ?>
<a class="c-skip-link" href="#main">本文へスキップ</a>
<p class="c-demo-notice" data-lint-ignore><?= e(site('demo_notice')) ?></p>
