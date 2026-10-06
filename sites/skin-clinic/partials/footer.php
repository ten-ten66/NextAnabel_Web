<?php
/**
 * フッター（住所・ナビゲーション・モバイル用の予約バー・通知領域）
 * @var array<string, mixed> $page
 */
$nav = site_data('nav');
$current = (string) ($page['id'] ?? '');
?>
<footer class="l-footer">
  <div class="l-container l-footer__inner">
    <div class="l-footer__brand">
      <a class="l-footer__logo" href="<?= e(url('index')) ?>">
        <?php partial('logo'); ?>
        <span class="l-footer__name">
          <span class="l-footer__name-ja"><?= e(site('name')) ?></span>
          <span class="l-footer__name-en" aria-hidden="true"><?= e(site('name_en')) ?></span>
        </span>
      </a>
      <address class="l-footer__address">
        〒<?= e(site('address.postal_code')) ?><br>
        <?= e(full_address()) ?><br>
        TEL <a href="<?= e(tel_href()) ?>"><?= e(site('tel')) ?></a>
      </address>
      <dl class="l-footer__hours">
        <div><dt>診療時間</dt><dd><?= e(hours_summary()) ?></dd></div>
        <div><dt>最終受付</dt><dd>診療終了の<?= e(site('last_entry_minutes')) ?>分前</dd></div>
        <div><dt>休診日</dt><dd><?= e(site('closed_label')) ?></dd></div>
      </dl>
    </div>
    <nav class="l-footer__nav" id="footer-nav" aria-label="フッターメニュー">
      <div class="l-footer__group">
        <p class="l-footer__heading">サイトマップ</p>
        <ul class="l-footer__list">
          <li><a href="<?= e(url('index')) ?>"<?= aria_current('index', $current) ?>>ホーム</a></li>
          <?php foreach ($nav as $item): ?>
            <li><a href="<?= e(url($item['id'])) ?>"<?= aria_current($item['id'], $current) ?>><?= e($item['label']) ?></a></li>
          <?php endforeach; ?>
          <li><a href="<?= e(url('contact')) ?>"<?= aria_current('contact', $current) ?>>ご予約・お問い合わせ</a></li>
          <li><a href="<?= e(url('privacy')) ?>"<?= aria_current('privacy', $current) ?>>プライバシーポリシー</a></li>
        </ul>
      </div>
      <div class="l-footer__group">
        <p class="l-footer__heading">施術</p>
        <ul class="l-footer__list">
          <?php foreach (treatments() as $t): ?>
            <li><a href="<?= e(url('treatment', ['slug' => $t['slug']])) ?>"<?= ($page['slug'] ?? '') === $t['slug'] ? ' aria-current="page"' : '' ?>><?= e($t['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </nav>
  </div>
  <div class="l-container l-footer__bottom">
    <p class="l-footer__note">当サイトの掲載内容は、医療広告ガイドラインを踏まえて作成しています。料金はすべて税込の総額です。</p>
    <p class="l-footer__copy"><small>&copy; 2026 <?= e(site('name_en')) ?>（架空のクリニックです）</small></p>
  </div>
  <div class="c-sp-bar js-sp-bar">
    <a class="c-sp-bar__item" href="<?= e(tel_href()) ?>"><?= icon('tel') ?>電話する</a>
    <a class="c-sp-bar__item c-sp-bar__item--primary" href="<?= e(url('contact')) ?>"><?= icon('calendar') ?>Webで予約する</a>
  </div>
  <div class="c-toast js-toast" role="status" aria-live="polite" aria-atomic="true"></div>
</footer>
</body>
</html>
