<?php
/**
 * 固定ページ（予約フォーム・プライバシーポリシーなど）
 *
 * @package hakuji
 */

get_header();
the_post();
?>
<main id="main">
  <?php hakuji_page_head(get_the_title()); ?>
  <section class="l-section">
    <div class="l-container l-container--narrow p-page-content" id="contact-form">
      <?php the_content(); ?>
    </div>
  </section>
</main>
<?php
get_footer();
