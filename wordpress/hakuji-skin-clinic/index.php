<?php
/**
 * 投稿一覧（お知らせ）とテンプレートが見つからない場合の表示
 *
 * @package hakuji
 */

get_header();
?>
<main id="main">
  <?php hakuji_page_head(is_home() ? 'お知らせ' : wp_strip_all_tags(get_the_archive_title())); ?>
  <section class="l-section">
    <div class="l-container l-container--narrow">
      <?php if (have_posts()) : ?>
        <ul class="c-news">
          <?php while (have_posts()) : the_post(); ?>
            <?php $cats = get_the_category(); ?>
            <li class="c-news__item">
              <time datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>"><?php echo esc_html(get_the_date('Y.m.d')); ?></time>
              <?php if ($cats) : ?>
                <span class="c-news__category"><?php echo esc_html($cats[0]->name); ?></span>
              <?php endif; ?>
              <p class="c-news__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></p>
            </li>
          <?php endwhile; ?>
        </ul>
        <?php the_posts_pagination(); ?>
      <?php else : ?>
        <p>まだお知らせはありません。</p>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
get_footer();
