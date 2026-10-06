<?php
/**
 * トップページ（静的サイト版 sites/skin-clinic/index.php と同じ構成のうち、
 * 管理画面で更新する部分＝施術・お知らせと、医院情報から組み立てる部分＝診療時間・アクセスを載せる）
 *
 * @package hakuji
 */

get_header();
$treatments = array_map('hakuji_treatment', get_posts(['post_type' => 'treatment', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']));
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
    <button type="button" class="p-hero__toggle js-glaze-toggle" aria-pressed="false" hidden><?php echo hakuji_icon('pause') . hakuji_icon('play'); ?><span class="u-visually-hidden">背景の動きを一時停止</span></button>
  </section>

  <section class="l-section l-section--tint p-home-concerns" aria-labelledby="concerns-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <div class="c-section-head">
          <p class="c-section-head__en" aria-hidden="true">Concerns</p>
          <h2 class="c-section-head__title" id="concerns-title">悩みから探す</h2>
        </div>
      </div>
      <div class="l-rail__body">
        <p class="p-home-concerns__text">悩みごとに、対応する施術をまとめました。同じ悩みでも原因によって適した治療は異なるため、最終的な治療法は診察で決めます。</p>
        <?php get_template_part('template-parts/tx-filter', null, ['target' => 'home-treatments', 'treatments' => $treatments]); ?>
        <ul class="c-tx-grid" id="home-treatments">
          <?php foreach ($treatments as $t) : ?>
            <?php get_template_part('template-parts/tx-card', null, ['t' => $t]); ?>
          <?php endforeach; ?>
          <li class="c-tx-more js-filter-anchor">
            <p class="c-tx-more__title">すべての施術と料金</p>
            <p class="c-tx-more__text">施術ごとの回数の目安・ダウンタイム・リスクを一覧で比べられます。</p>
            <ul class="c-tx-more__links">
              <li><a class="c-text-link" href="<?php echo esc_url((string) get_post_type_archive_link('treatment')); ?>">施術一覧<?php echo hakuji_icon('arrow'); ?></a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <?php if ($news) : ?>
    <section class="l-section l-section--paper p-home-news" aria-labelledby="news-title">
      <div class="l-container l-rail">
        <div class="l-rail__head">
          <div class="c-section-head">
            <p class="c-section-head__en" aria-hidden="true">News</p>
            <h2 class="c-section-head__title" id="news-title">お知らせ</h2>
          </div>
        </div>
        <div class="l-rail__body">
          <ul class="c-news">
            <?php foreach ($news as $item) : ?>
              <?php $cats = get_the_category($item->ID); ?>
              <li class="c-news__item">
                <time datetime="<?php echo esc_attr(get_the_date('Y-m-d', $item)); ?>"><?php echo esc_html(get_the_date('Y.m.d', $item)); ?></time>
                <?php if ($cats) : ?>
                  <span class="c-news__category"><?php echo esc_html($cats[0]->name); ?></span>
                <?php endif; ?>
                <p class="c-news__title"><a href="<?php echo esc_url(get_permalink($item)); ?>"><?php echo esc_html(get_the_title($item)); ?></a></p>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="l-section l-section--tint p-home-access" id="access" aria-labelledby="access-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <div class="c-section-head">
          <p class="c-section-head__en" aria-hidden="true">Hours &amp; Access</p>
          <h2 class="c-section-head__title" id="access-title">診療時間・アクセス</h2>
        </div>
      </div>
      <div class="l-rail__body p-home-access__grid">
        <div class="p-home-access__hours">
          <?php get_template_part('template-parts/reception'); ?>
          <?php get_template_part('template-parts/hours-table'); ?>
        </div>
        <div class="p-home-access__place">
          <div class="p-home-access__map">
            <?php get_template_part('template-parts/access-map', null, ['prefix' => 'home-map']); ?>
          </div>
          <address class="p-home-access__address">
            〒<?php echo esc_html(hakuji_clinic('postal_code')); ?> <?php echo esc_html(hakuji_full_address()); ?>
          </address>
          <ul class="c-dash-list p-home-access__routes">
            <?php foreach ((array) hakuji_clinic('access') as $route) : ?>
              <li><?php echo esc_html($route); ?></li>
            <?php endforeach; ?>
          </ul>
          <div class="p-home-access__links">
            <a class="c-text-link" href="<?php echo esc_url(hakuji_map_url()); ?>" target="_blank" rel="noopener">地図アプリで開く<?php echo hakuji_icon('external'); ?><span class="u-visually-hidden">（新しいタブで開きます）</span></a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php get_template_part('template-parts/cta'); ?>
</main>
<?php
get_footer();
