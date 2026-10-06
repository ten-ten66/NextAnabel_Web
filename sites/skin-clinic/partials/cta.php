<?php
/** ページ末尾の予約導線 */
?>
<section class="c-cta" aria-labelledby="cta-title">
  <div class="l-container c-cta__inner">
    <div class="c-cta__text">
      <p class="c-cta__en" aria-hidden="true">Reservation</p>
      <h2 class="c-cta__title" id="cta-title">ご予約・ご相談</h2>
      <p class="c-cta__lead">カウンセリングの日に、施術を受けるかどうかを<br class="u-br-lg">決める必要はありません。<br class="u-br-lg">費用とリスクを確かめてから、ゆっくりご検討ください。</p>
    </div>
    <div class="c-cta__actions">
      <a class="c-cta__button" href="<?= e(url('contact')) ?>">
        <span class="c-cta__button-label">Webで予約する</span>
        <span class="c-cta__button-note">24時間受付・2営業日以内にご連絡します</span>
        <?= icon('arrow') ?>
      </a>
      <a class="c-cta__tel" href="<?= e(tel_href()) ?>">
        <span class="c-cta__tel-label">お電話でのご予約</span>
        <span class="c-cta__tel-number"><?= e(site('tel')) ?></span>
        <span class="c-cta__tel-note">受付 <?= e(hours_summary()) ?>／休診日 <?= e(site('closed_label')) ?></span>
      </a>
    </div>
  </div>
</section>
