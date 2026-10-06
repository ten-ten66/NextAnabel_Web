<?php
/**
 * フッター（静的サイト版と同じマークアップ）
 *
 * @package hakuji
 */

$treatments = get_posts(['post_type' => 'treatment', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
?>
<footer class="l-footer">
  <div class="l-container l-footer__inner">
    <div class="l-footer__brand">
      <a class="l-footer__logo" href="<?php echo esc_url(home_url('/')); ?>">
        <?php get_template_part('template-parts/logo'); ?>
        <span class="l-footer__name">
          <span class="l-footer__name-ja"><?php echo esc_html(hakuji_clinic('name')); ?></span>
          <span class="l-footer__name-en" aria-hidden="true"><?php echo esc_html(hakuji_clinic('name_en')); ?></span>
        </span>
      </a>
      <address class="l-footer__address">
        〒<?php echo esc_html(hakuji_clinic('postal_code')); ?><br>
        <?php echo esc_html(hakuji_full_address()); ?><br>
        TEL <a href="<?php echo esc_url(hakuji_tel_href()); ?>"><?php echo esc_html(hakuji_clinic('tel')); ?></a>
      </address>
      <dl class="l-footer__hours">
        <div><dt>診療時間</dt><dd><?php echo esc_html(hakuji_hours_summary()); ?></dd></div>
        <div><dt>最終受付</dt><dd>診療終了の<?php echo esc_html((string) hakuji_clinic('last_entry_minutes')); ?>分前</dd></div>
        <div><dt>休診日</dt><dd><?php echo esc_html(hakuji_clinic('closed')); ?></dd></div>
      </dl>
    </div>
    <nav class="l-footer__nav" id="footer-nav" aria-label="フッターメニュー">
      <div class="l-footer__group">
        <p class="l-footer__heading">サイトマップ</p>
        <ul class="l-footer__list">
          <li><a href="<?php echo esc_url(home_url('/')); ?>">ホーム</a></li>
          <?php foreach (hakuji_nav_items() as $item) : ?>
            <li><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li>
          <?php endforeach; ?>
          <?php if ($privacy = get_privacy_policy_url()) : ?>
            <li><a href="<?php echo esc_url($privacy); ?>">プライバシーポリシー</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="l-footer__group">
        <p class="l-footer__heading">施術</p>
        <ul class="l-footer__list">
          <?php foreach ($treatments as $t) : ?>
            <li><a href="<?php echo esc_url(get_permalink($t)); ?>"><?php echo esc_html(get_the_title($t)); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </nav>
  </div>
  <div class="l-container l-footer__bottom">
    <p class="l-footer__note">当サイトの掲載内容は、医療広告ガイドラインを踏まえて作成しています。料金はすべて税込の総額です。</p>
    <p class="l-footer__copy"><small>&copy; 2026 <?php echo esc_html(hakuji_clinic('name_en')); ?>（架空のクリニックです）</small></p>
  </div>
  <div class="c-sp-bar js-sp-bar">
    <a class="c-sp-bar__item" href="<?php echo esc_url(hakuji_tel_href()); ?>"><?php echo hakuji_icon('tel'); ?>電話する</a>
    <a class="c-sp-bar__item c-sp-bar__item--primary" href="<?php echo esc_url(hakuji_contact_url()); ?>"><?php echo hakuji_icon('calendar'); ?>Webで予約する</a>
  </div>
  <div class="c-toast js-toast" role="status" aria-live="polite" aria-atomic="true"></div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
