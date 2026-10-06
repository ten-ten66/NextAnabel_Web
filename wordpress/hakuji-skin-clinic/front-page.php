<?php
/**
 * トップページ
 *
 * @package hakuji
 */

get_header();
$treatments = array_map('hakuji_treatment', get_posts(['post_type' => 'treatment', 'numberposts' => 6, 'orderby' => 'menu_order', 'order' => 'ASC']));
$news = get_posts(['post_type' => 'post', 'numberposts' => 3]);
?>
<main id="main">
  <section class="p-hero" aria-labelledby="hero-title">
    <div class="p-hero__media" aria-hidden="true">
      <img class="p-hero__image" src="<?php echo esc_url(HAKUJI_URI . '/assets/img/hero-glaze.webp'); ?>" width="1920" height="1200" alt="" fetchpriority="high">
      <canvas class="p-hero__canvas js-glaze"></canvas>
    </div>
    <div class="l-container p-hero__inner">
      <div class="p-hero__lockup">
        <p class="p-hero__catch"><span class="p-hero__catch-line">納得してから、</span><span class="p-hero__catch-line">はじめる肌治療。</span></p>
        <span class="p-hero__seal" aria-hidden="true">白磁</span>
      </div>
      <div class="p-hero__intro">
        <p class="p-hero__en" aria-hidden="true"><?php echo esc_html(hakuji_clinic('name_en')); ?></p>
        <h1 class="p-hero__title" id="hero-title"><?php echo esc_html(hakuji_clinic('name')); ?></h1>
        <p class="p-hero__sub">費用の総額・回数の目安・リスクを、<br class="u-br-md">施術の前にすべてご説明します。</p>
        <div class="p-hero__actions">
          <a class="c-button c-button--primary" href="<?php echo esc_url(hakuji_contact_url()); ?>">カウンセリングを予約する<?php echo hakuji_icon('arrow'); ?></a>
          <a class="c-button c-button--ghost" href="<?php echo esc_url((string) get_post_type_archive_link('treatment')); ?>">施術と料金を見る</a>
        </div>
        <ul class="p-hero__meta">
          <li><?php echo esc_html(hakuji_clinic('access')[0]); ?></li>
          <li>休診日 <?php echo esc_html(hakuji_clinic('closed')); ?></li>
        </ul>
      </div>
    </div>
    <button type="button" class="p-hero__toggle js-glaze-toggle" aria-pressed="false" hidden><span class="u-visually-hidden">背景の動きを一時停止</span></button>
  </section>

  <section class="l-section l-section--tint" aria-labelledby="tx-title">
    <div class="l-container">
      <div class="c-section-head">
        <p class="c-section-head__en" aria-hidden="true">Concerns</p>
        <h2 class="c-section-head__title" id="tx-title">悩みから探す</h2>
      </div>
      <ul class="c-tx-grid">
        <?php foreach ($treatments as $t) : ?>
          <?php get_template_part('template-parts/tx-card', null, ['t' => $t]); ?>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <?php if ($news) : ?>
    <section class="l-section" aria-labelledby="news-title">
      <div class="l-container">
        <div class="c-section-head">
          <p class="c-section-head__en" aria-hidden="true">News</p>
          <h2 class="c-section-head__title" id="news-title">お知らせ</h2>
        </div>
        <ul class="c-news-list">
          <?php foreach ($news as $item) : ?>
            <li class="c-news-list__item"><time datetime="<?php echo esc_attr(get_the_date('Y-m-d', $item)); ?>"><?php echo esc_html(get_the_date('Y.m.d', $item)); ?></time> <a href="<?php echo esc_url(get_permalink($item)); ?>"><?php echo esc_html(get_the_title($item)); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
  <?php endif; ?>

  <?php get_template_part('template-parts/cta'); ?>
</main>
<?php
get_footer();
