<?php
/**
 * 下層ページの見出し（パンくず・ページ名・リード文）
 *
 * @var array{title: string, en?: string, lead?: string} $args
 */
?>
<div class="l-page-head">
  <div class="l-container">
    <?php hakuji_the_breadcrumbs(); ?>
    <div class="l-page-head__body">
      <?php if (!empty($args['en'])) : ?>
        <p class="l-page-head__en" aria-hidden="true"><?php echo esc_html($args['en']); ?></p>
      <?php endif; ?>
      <h1 class="l-page-head__title"><?php echo esc_html($args['title']); ?></h1>
      <?php if (!empty($args['lead'])) : ?>
        <p class="l-page-head__lead"><?php echo esc_html($args['lead']); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
