<?php
/**
 * ページが見つからない場合
 *
 * @package hakuji
 */

get_header();
?>
<main id="main">
  <?php hakuji_page_head('ページが見つかりません', 'Not Found', 'お探しのページは移動または削除された可能性があります。'); ?>
  <section class="l-section">
    <div class="l-container l-container--narrow">
      <p><a class="c-button c-button--primary" href="<?php echo esc_url(home_url('/')); ?>">トップページへ<?php echo hakuji_icon('arrow'); ?></a></p>
    </div>
  </section>
</main>
<?php
get_footer();
