<?php
/**
 * 施術一覧（悩み別の絞り込み付き）。悩みカテゴリーのページ（taxonomy-concern.php）でも使う
 *
 * @package hakuji
 */

get_header();
$treatments = [];
while (have_posts()) {
    the_post();
    $treatments[] = hakuji_treatment(get_post());
}
?>
<main id="main">
  <?php if (is_tax('concern')) : ?>
    <?php hakuji_page_head(single_term_title('', false) . 'の施術', 'Treatments', '同じ悩みでも原因によって適した治療は異なるため、最終的な治療法は診察で決めます。'); ?>
  <?php else : ?>
    <?php hakuji_page_head('施術一覧', 'Treatments', '悩みごとに、対応する施術をまとめました。費用・ダウンタイム・主なリスクを一覧で比べられます。'); ?>
  <?php endif; ?>
  <section class="l-section" aria-label="施術の一覧">
    <div class="l-container">
      <?php if (!is_tax('concern')) : ?>
        <?php get_template_part('template-parts/tx-filter', null, ['target' => 'tx-list', 'treatments' => $treatments]); ?>
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
