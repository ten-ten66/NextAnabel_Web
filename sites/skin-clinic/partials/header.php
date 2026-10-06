<?php
/**
 * ヘッダー（スクロールでコンパクトになる）とモバイル用ドロワー
 * @var array<string, mixed> $page
 */
$nav = site_data('nav');
$current = (string) ($page['id'] ?? '');
$section = (string) ($page['section'] ?? $current); // 施術詳細は「施術一覧」を親として示す
?>
<span class="l-header-sentinel js-header-sentinel" aria-hidden="true"></span>
<header class="l-header js-header">
  <div class="l-header__inner">
    <a class="l-header__brand" href="<?= e(url('index')) ?>"<?= aria_current('index', $current) ?>>
      <?php partial('logo'); ?>
      <span class="l-header__name">
        <span class="l-header__name-ja"><?= e(site('name')) ?></span>
        <span class="l-header__name-en" aria-hidden="true"><?= e(site('name_en')) ?></span>
      </span>
    </a>
    <nav class="l-header__nav" aria-label="メインメニュー">
      <ul class="l-header__list">
        <?php foreach ($nav as $item): ?>
          <li><a class="l-header__link<?= $item['id'] === $section && $item['id'] !== $current ? ' is-parent' : '' ?>" href="<?= e(url($item['id'])) ?>"<?= aria_current($item['id'], $current) ?>><?= e($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="l-header__actions">
      <a class="l-header__tel" href="<?= e(tel_href()) ?>"><?= icon('tel') ?><span class="l-header__tel-number"><?= e(site('tel')) ?></span></a>
      <a class="c-button c-button--primary c-button--small l-header__cta" href="<?= e(url('contact')) ?>"<?= aria_current('contact', $current) ?>>カウンセリング予約</a>
      <a class="l-header__menu js-drawer-toggle" href="#footer-nav"><span class="l-header__menu-lines" aria-hidden="true"></span><span class="l-header__menu-label">メニュー</span></a>
    </div>
  </div>
</header>
<dialog class="l-drawer js-drawer" id="site-drawer" aria-label="メニュー">
  <div class="l-drawer__panel js-drawer-panel">
    <div class="l-drawer__head">
      <p class="l-drawer__brand"><?= e(site('name')) ?></p>
      <button type="button" class="l-drawer__close js-drawer-close"><?= icon('close') ?><span class="u-visually-hidden">メニューを閉じる</span></button>
    </div>
    <nav class="l-drawer__nav" aria-label="サイトメニュー">
      <ul class="l-drawer__list">
        <li><a class="l-drawer__link" href="<?= e(url('index')) ?>"<?= aria_current('index', $current) ?>><span class="l-drawer__ja">ホーム</span><span class="l-drawer__en" aria-hidden="true">Home</span></a></li>
        <?php foreach ($nav as $item): ?>
          <li><a class="l-drawer__link" href="<?= e(url($item['id'])) ?>"<?= aria_current($item['id'], $current) ?>><span class="l-drawer__ja"><?= e($item['label']) ?></span><span class="l-drawer__en" aria-hidden="true"><?= e($item['en']) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="l-drawer__foot">
      <a class="c-button c-button--primary c-button--block" href="<?= e(url('contact')) ?>">カウンセリングを予約する<?= icon('arrow') ?></a>
      <a class="c-button c-button--ghost c-button--block" href="<?= e(tel_href()) ?>"><?= icon('tel') ?>電話で予約する <?= e(site('tel')) ?></a>
      <p class="l-drawer__hours">診療時間 <?= e(hours_summary()) ?><br>休診日 <?= e(site('closed_label')) ?></p>
    </div>
  </div>
</dialog>
