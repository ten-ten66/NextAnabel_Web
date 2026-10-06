<footer class="l-footer">
  <div class="l-container l-footer__inner">
    <div class="l-footer__brand">
      <a class="c-logo c-logo--stack" href="<?= e(url('index')) ?>">
        <span class="c-logo__mark" aria-hidden="true">ORVANE</span>
        <span class="c-logo__name"><?= e(site('name')) ?></span>
      </a>
      <address class="l-footer__address">
        〒<?= e(site('address.postal_code')) ?><br>
        <?= e(site('address.region') . site('address.locality') . site('address.street')) ?><br>
        TEL <a href="<?= e(sc_tel_href()) ?>"><?= e(site('tel')) ?></a>
      </address>
      <p class="l-footer__hours">診療時間 <?= e(site('hours_label')) ?><br>休診日 <?= e(site('closed_label')) ?></p>
    </div>
    <nav class="l-footer__nav" id="footer-nav" aria-label="フッターメニュー">
      <h2 class="l-footer__heading">Menu</h2>
      <ul class="l-footer__list">
        <li><a href="<?= e(url('index')) ?>">ホーム</a></li>
        <?php foreach (site('nav') as $item): ?>
          <li><a href="<?= e(url($item['href'])) ?>"><?= e($item['label']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="<?= e(url('counseling')) ?>">カウンセリング予約</a></li>
        <li><a href="<?= e(url('privacy')) ?>">プライバシーポリシー</a></li>
      </ul>
    </nav>
    <div class="l-footer__treatments">
      <h2 class="l-footer__heading">Treatments</h2>
      <ul class="l-footer__list">
        <?php foreach (sc_treatments() as $t): ?>
          <li><a href="<?= e(url('treatment', ['slug' => $t['slug']])) ?>"><?= e($t['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="l-container l-footer__bottom">
    <p class="l-footer__note">手術を受けられた方には、夜間・休診日の緊急連絡先を、施術当日にお渡しする書面でご案内しています。</p>
    <p class="l-footer__copy"><small>&copy; 2026 <?= e(site('name_en')) ?> — 架空のサンプルサイト</small></p>
  </div>
</footer>
<div class="c-toast" role="status" aria-live="polite" aria-atomic="true" data-toast></div>
</body>
</html>
