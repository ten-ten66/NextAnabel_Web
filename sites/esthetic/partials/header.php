<?php
/**
 * ヘッダー・グローバルナビ・モバイル用ドロワー
 *
 * @var array<string, mixed> $page
 */
$current = (string) ($page['id'] ?? '');
// ご予約セクション（partials/reserve.php）を持つページでは、ページ内リンクにする
$reserveHref = !empty($page['has_reserve']) ? '#reserve' : url('index#reserve');
?>
<header class="l-header js-header">
  <div class="l-header__inner">
    <a class="l-header__logo" href="<?= e(url('index')) ?>"><?php partial('logo'); ?></a>
    <nav class="l-header__nav" aria-label="メインメニュー">
      <ul class="l-header__list">
        <?php foreach (site('nav') as $item): ?>
          <li><a class="l-header__link" href="<?= e(nav_href($item['href'], $current)) ?>"<?= aria_current($item['id'], $current) ?>><?= e($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <a class="c-button c-button--primary c-button--sm l-header__cta" href="<?= e($reserveHref) ?>">ご予約</a>
    <button type="button" class="l-header__toggle js-drawer-toggle" aria-expanded="false" aria-controls="site-drawer">
      <span class="l-header__toggle-lines" aria-hidden="true"><span></span><span></span></span>
      <span class="l-header__toggle-label js-drawer-label">メニュー</span>
    </button>
  </div>

  <div class="l-drawer js-drawer" id="site-drawer" hidden>
    <div class="l-drawer__panel">
      <nav class="l-drawer__nav" aria-label="サイトメニュー">
        <ul class="l-drawer__list">
          <li class="l-drawer__item" style="--i: 0">
            <a class="l-drawer__link js-drawer-link" href="<?= e(url('index')) ?>"<?= aria_current('index', $current) ?>>
              <span class="l-drawer__en" lang="en" aria-hidden="true">Top</span><span class="l-drawer__ja">トップ</span>
            </a>
          </li>
          <?php foreach (site('nav') as $i => $item): ?>
            <li class="l-drawer__item" style="--i: <?= e($i + 1) ?>">
              <a class="l-drawer__link js-drawer-link" href="<?= e(nav_href($item['href'], $current)) ?>"<?= aria_current($item['id'], $current) ?>>
                <span class="l-drawer__en" lang="en" aria-hidden="true"><?= e($item['en']) ?></span><span class="l-drawer__ja"><?= e($item['label']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <div class="l-drawer__reserve">
        <a class="c-button c-button--primary js-drawer-link" href="<?= e($reserveHref) ?>">ご予約・空き状況<svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-arrow"/></svg></a>
        <p class="l-drawer__tel">お電話 <a href="<?= e(tel_href()) ?>"><?= e(site('tel')) ?></a></p>
        <p class="l-drawer__hours"><?= e(site('hours_label')) ?>（最終受付 <?= e(site('last_entry')) ?>）／<?= e(site('closed_label')) ?>定休</p>
      </div>
    </div>
  </div>
</header>
