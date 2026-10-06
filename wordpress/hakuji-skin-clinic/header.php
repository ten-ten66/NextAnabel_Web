<?php
/**
 * ヘッダー（静的サイト版 sites/skin-clinic/partials/header.php と同じマークアップ）
 *
 * @package hakuji
 */

$nav = hakuji_nav_items();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> data-env="server" data-build="demo">
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="<?php echo esc_url(HAKUJI_URI . '/assets/img/favicon.svg'); ?>" type="image/svg+xml">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="c-skip-link" href="#main">本文へスキップ</a>
<span class="l-header-sentinel js-header-sentinel" aria-hidden="true"></span>
<header class="l-header js-header">
  <div class="l-header__inner">
    <a class="l-header__brand" href="<?php echo esc_url(home_url('/')); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>>
      <?php get_template_part('template-parts/logo'); ?>
      <span class="l-header__name">
        <span class="l-header__name-ja"><?php echo esc_html(hakuji_clinic('name')); ?></span>
        <span class="l-header__name-en" aria-hidden="true"><?php echo esc_html(hakuji_clinic('name_en')); ?></span>
      </span>
    </a>
    <nav class="l-header__nav" aria-label="メインメニュー">
      <ul class="l-header__list">
        <?php foreach ($nav as $item) : ?>
          <li><a class="l-header__link" href="<?php echo esc_url($item['url']); ?>"<?php echo $item['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($item['label']); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="l-header__actions">
      <a class="l-header__tel" href="<?php echo esc_url(hakuji_tel_href()); ?>"><?php echo hakuji_icon('tel'); ?><span class="l-header__tel-number"><?php echo esc_html(hakuji_clinic('tel')); ?></span></a>
      <a class="c-button c-button--primary c-button--small l-header__cta" href="<?php echo esc_url(hakuji_contact_url()); ?>">カウンセリング予約</a>
      <a class="l-header__menu js-drawer-toggle" href="#footer-nav"><span class="l-header__menu-lines" aria-hidden="true"></span><span class="l-header__menu-label">メニュー</span></a>
    </div>
  </div>
</header>
<dialog class="l-drawer js-drawer" id="site-drawer" aria-label="メニュー">
  <div class="l-drawer__panel js-drawer-panel">
    <div class="l-drawer__head">
      <p class="l-drawer__brand"><?php echo esc_html(hakuji_clinic('name')); ?></p>
      <button type="button" class="l-drawer__close js-drawer-close"><?php echo hakuji_icon('close'); ?><span class="u-visually-hidden">メニューを閉じる</span></button>
    </div>
    <nav class="l-drawer__nav" aria-label="サイトメニュー">
      <ul class="l-drawer__list">
        <li><a class="l-drawer__link" href="<?php echo esc_url(home_url('/')); ?>"><span class="l-drawer__ja">ホーム</span><span class="l-drawer__en" aria-hidden="true">Home</span></a></li>
        <?php foreach ($nav as $item) : ?>
          <li><a class="l-drawer__link" href="<?php echo esc_url($item['url']); ?>"<?php echo $item['current'] ? ' aria-current="page"' : ''; ?>><span class="l-drawer__ja"><?php echo esc_html($item['label']); ?></span><span class="l-drawer__en" aria-hidden="true"><?php echo esc_html($item['en']); ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="l-drawer__foot">
      <a class="c-button c-button--primary c-button--block" href="<?php echo esc_url(hakuji_contact_url()); ?>">カウンセリングを予約する<?php echo hakuji_icon('arrow'); ?></a>
      <a class="c-button c-button--ghost c-button--block" href="<?php echo esc_url(hakuji_tel_href()); ?>"><?php echo hakuji_icon('tel'); ?>電話で予約する <?php echo esc_html(hakuji_clinic('tel')); ?></a>
      <p class="l-drawer__hours">診療時間 <?php echo esc_html(hakuji_hours_summary()); ?><br>休診日 <?php echo esc_html(hakuji_clinic('closed')); ?></p>
    </div>
  </div>
</dialog>
