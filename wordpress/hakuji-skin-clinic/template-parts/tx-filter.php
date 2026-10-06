<?php
/**
 * 悩み別の絞り込み（JavaScript が動くときだけ表示する。動かないときは全件がそのまま見える）
 *
 * @var array{target: string, treatments: list<array<string, mixed>>} $args
 */
$target = $args['target'];
$counts = ['all' => count($args['treatments'])];
foreach ($args['treatments'] as $t) {
    foreach ($t['categories'] as $slug) {
        $counts[$slug] = ($counts[$slug] ?? 0) + 1;
    }
}
$chips = ['all' => 'すべて'];
$terms = get_terms(['taxonomy' => 'concern', 'hide_empty' => true, 'orderby' => 'term_id']);
foreach (is_wp_error($terms) ? [] : $terms as $term) {
    if (isset($counts[$term->slug])) {
        $chips[$term->slug] = $term->name;
    }
}
if (count($chips) < 3) {
    return;
}
?>
<div class="c-filter js-filter u-js-only" data-filter-target="<?php echo esc_attr($target); ?>">
  <p class="c-filter__label" id="<?php echo esc_attr($target); ?>-filter-label">悩みから絞り込む</p>
  <div class="c-filter__chips" role="group" aria-labelledby="<?php echo esc_attr($target); ?>-filter-label">
    <?php foreach ($chips as $slug => $label) : ?>
      <button type="button" class="c-chip" data-filter="<?php echo esc_attr($slug); ?>" data-label="<?php echo esc_attr($label); ?>" aria-pressed="<?php echo $slug === 'all' ? 'true' : 'false'; ?>"><?php echo esc_html($label); ?><span class="c-chip__count"><?php echo esc_html((string) ($counts[$slug] ?? 0)); ?></span></button>
    <?php endforeach; ?>
  </div>
  <p class="c-filter__status js-filter-status" aria-live="polite"></p>
</div>
