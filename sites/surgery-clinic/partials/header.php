<?php
/** @var array<string, mixed> $page */
$currentId = (string) ($page['id'] ?? '');
$isTop = $currentId === 'index';
?>
<header class="l-header<?= $isTop ? ' l-header--overlay' : '' ?>" data-header>
  <div class="l-header__inner">
    <a class="c-logo l-header__logo" href="<?= e(url('index')) ?>">
      <span class="c-logo__mark" aria-hidden="true">ORVANE</span>
      <span class="c-logo__name"><?= e(site('name')) ?></span>
    </a>
    <nav class="l-header__nav" aria-label="メインメニュー">
      <ul class="l-header__list">
        <?php foreach (site('nav') as $item): ?>
          <li><a class="l-header__link" href="<?= e(url($item['href'])) ?>"<?= aria_current($item['id'], $currentId) ?>><?= e($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="l-header__actions">
      <a class="l-header__tel" href="<?= e(sc_tel_href()) ?>"><span class="l-header__tel-label">TEL</span><?= e(site('tel')) ?></a>
      <a class="c-button c-button--small l-header__cta" href="<?= e(url('counseling')) ?>"<?= aria_current('counseling', $currentId) ?>>カウンセリング予約</a>
      <a class="l-header__menu-link" href="#footer-nav">メニュー</a>
      <button class="l-header__toggle" type="button" aria-expanded="false" aria-controls="site-drawer" data-drawer-toggle>
        <span class="l-header__toggle-label" data-drawer-label>メニュー</span>
        <span class="l-header__toggle-icon" aria-hidden="true"><span></span><span></span></span>
      </button>
    </div>
  </div>
  <div class="l-drawer" id="site-drawer" hidden data-drawer>
    <div class="l-drawer__inner">
      <nav class="l-drawer__nav" aria-label="サイトメニュー">
        <ul class="l-drawer__list">
          <li><a class="l-drawer__link" href="<?= e(url('index')) ?>"<?= aria_current('index', $currentId) ?>><span class="l-drawer__num" aria-hidden="true">00</span>ホーム</a></li>
          <?php foreach (site('nav') as $i => $item): ?>
            <li><a class="l-drawer__link" href="<?= e(url($item['href'])) ?>"<?= aria_current($item['id'], $currentId) ?>><span class="l-drawer__num" aria-hidden="true"><?= e(sc_num($i + 1)) ?></span><?= e($item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <p class="l-drawer__heading">施術メニュー</p>
        <ul class="l-drawer__sub">
          <?php foreach (sc_treatments() as $t): ?>
            <li><a href="<?= e(url('treatment', ['slug' => $t['slug']])) ?>"><?= e($t['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <div class="l-drawer__foot">
        <a class="c-button c-button--block" href="<?= e(url('counseling')) ?>">カウンセリングを予約する</a>
        <a class="l-drawer__tel" href="<?= e(sc_tel_href()) ?>"><span>TEL</span><?= e(site('tel')) ?></a>
        <p class="l-drawer__hours"><?= e(site('hours_label')) ?>／休診 <?= e(site('closed_label')) ?></p>
      </div>
    </div>
  </div>
</header>
