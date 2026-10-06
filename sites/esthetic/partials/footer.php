<?php
/**
 * フッター・通知領域（電話リンクのデモ通知）・スクリプト用のデータ
 *
 * @var array<string, mixed> $page
 */
$current = (string) ($page['id'] ?? '');
$reserveHref = !empty($page['has_reserve']) ? '#reserve' : url('index#reserve');
$seasons = require SITE_DIR . '/data/seasons.php';
$siteData = [
    'schedule' => site('schedule'),
    'lastEntryMinutes' => site('last_entry_minutes'),
    'closedLabel' => site('closed_label'),
    'seasons' => $seasons,
];
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;
?>
<footer class="l-footer">
  <div class="l-footer__inner">
    <div class="l-footer__brand">
      <a class="l-footer__logo" href="<?= e(url('index')) ?>"><?php partial('logo', ['class' => 'c-logo--light']); ?></a>
      <p class="l-footer__catch">静けさを、手のひらから。</p>
    </div>
    <address class="l-footer__address">
      〒<?= e(site('address.postal_code')) ?> <?= e(site('address.region') . site('address.locality') . site('address.street')) ?><br>
      TEL <a href="<?= e(tel_href()) ?>"><?= e(site('tel')) ?></a><br>
      <?= e(site('hours_label')) ?>（最終受付 <?= e(site('last_entry')) ?>）／定休日 <?= e(site('closed_label')) ?>
    </address>
    <nav class="l-footer__nav" aria-label="フッターメニュー">
      <ul class="l-footer__list">
        <li><a href="<?= e(url('index')) ?>"<?= aria_current('index', $current) ?>>トップ</a></li>
        <?php foreach (site('nav') as $item): ?>
          <li><a href="<?= e(nav_href($item['href'], $current)) ?>"<?= aria_current($item['id'], $current) ?>><?= e($item['label']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="<?= e($reserveHref) ?>">ご予約</a></li>
      </ul>
    </nav>
  </div>
  <div class="l-footer__bottom">
    <p class="l-footer__notice" data-lint-ignore><?= e(site('demo_notice')) ?></p>
    <p class="l-footer__copy"><small>&copy; 2026 <?= e(site('name')) ?>（架空のサロン）</small></p>
  </div>
</footer>
<div class="c-toast js-toast" role="status" aria-live="polite" aria-atomic="true"></div>
<script type="application/json" id="site-data"><?= json_encode($siteData, $jsonFlags) ?></script>
</body>
</html>
