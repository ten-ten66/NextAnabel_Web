<?php
/**
 * 施術一覧（悩み別の絞り込み付き）
 *
 * @package hakuji
 */

get_header();
$concerns = get_terms(['taxonomy' => 'concern', 'hide_empty' => true]);
$treatments = [];
while (have_posts()) {
    the_post();
    $treatments[] = hakuji_treatment(get_post());
}
?>
<main id="main">
  <?php hakuji_page_head('施術一覧', 'Treatments', '悩みごとに、対応する施術をまとめました。費用・ダウンタイム・主なリスクを一覧で比べられます。'); ?>
  <section class="l-section" aria-label="施術の一覧">
    <div class="l-container">
      <?php if (!is_wp_error($concerns) && $concerns) : ?>
        <div class="c-filter js-filter u-js-only" data-filter-target="tx-list">
          <p class="c-filter__label" id="tx-list-filter-label">悩みから絞り込む</p>
          <div class="c-filter__chips" role="group" aria-labelledby="tx-list-filter-label">
            <button type="button" class="c-chip" data-filter="all" data-label="すべて" aria-pressed="true">すべて<span class="c-chip__count"><?php echo esc_html((string) count($treatments)); ?></span></button>
            <?php foreach ($concerns as $term) : ?>
              <button type="button" class="c-chip" data-filter="<?php echo esc_attr($term->slug); ?>" data-label="<?php echo esc_attr($term->name); ?>" aria-pressed="false"><?php echo esc_html($term->name); ?><span class="c-chip__count"><?php echo esc_html((string) $term->count); ?></span></button>
            <?php endforeach; ?>
          </div>
          <p class="c-filter__status js-filter-status" aria-live="polite"></p>
        </div>
      <?php endif; ?>
      <ul class="c-tx-grid" id="tx-list">
        <?php foreach ($treatments as $t) : ?>
          <?php get_template_part('template-parts/tx-card', null, ['t' => $t]); ?>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
  <?php get_template_part('template-parts/cta'); ?>
</main>
<?php
get_footer();
