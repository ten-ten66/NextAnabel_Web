<?php
/** ページ末尾の予約導線 */
?>
<section class="c-cta" aria-labelledby="cta-title">
  <div class="l-container c-cta__inner">
    <div class="c-cta__text">
      <p class="c-cta__en" aria-hidden="true">Reservation</p>
      <h2 class="c-cta__title" id="cta-title">ご予約・ご相談</h2>
      <p class="c-cta__lead">カウンセリングの日に、施術を受けるかどうかを決める必要はありません。費用とリスクを確かめてから、ゆっくりご検討ください。</p>
    </div>
    <div class="c-cta__actions">
      <a class="c-cta__button" href="<?php echo esc_url(hakuji_contact_url()); ?>">
        <span class="c-cta__button-label">Webで予約する</span>
        <span class="c-cta__button-note">24時間受付・2営業日以内にご連絡します</span>
        <?php echo hakuji_icon('arrow'); ?>
      </a>
      <a class="c-cta__tel" href="<?php echo esc_url(hakuji_tel_href()); ?>">
        <span class="c-cta__tel-label">お電話でのご予約</span>
        <span class="c-cta__tel-number"><?php echo esc_html(hakuji_clinic('tel')); ?></span>
        <span class="c-cta__tel-note">受付 <?php echo esc_html(hakuji_hours_summary()); ?>／休診日 <?php echo esc_html(hakuji_clinic('closed')); ?></span>
      </a>
    </div>
  </div>
</section>
