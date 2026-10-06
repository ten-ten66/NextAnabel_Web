<?php
/**
 * 悩み別の絞り込み（JavaScript が動くときだけ表示する。動かないときは全件がそのまま見える）
 * @var string $target 絞り込む一覧の id
 */
$counts = ['all' => count(treatments())];
foreach (treatments() as $t) {
    foreach ($t['categories'] as $id) {
        $counts[$id] = ($counts[$id] ?? 0) + 1;
    }
}
$chips = ['all' => 'すべて'];
foreach (categories() as $id => $category) {
    $chips[$id] = $category['label'];
}
?>
<div class="c-filter js-filter u-js-only" data-filter-target="<?= e($target) ?>">
  <p class="c-filter__label" id="<?= e($target) ?>-filter-label">悩みから絞り込む</p>
  <div class="c-filter__chips" role="group" aria-labelledby="<?= e($target) ?>-filter-label">
    <?php foreach ($chips as $id => $label): ?>
      <button type="button" class="c-chip" data-filter="<?= e($id) ?>" data-label="<?= e($label) ?>" aria-pressed="<?= $id === 'all' ? 'true' : 'false' ?>"><?= e($label) ?><span class="c-chip__count"><?= e($counts[$id] ?? 0) ?></span></button>
    <?php endforeach; ?>
  </div>
  <p class="c-filter__status js-filter-status" aria-live="polite"></p>
</div>
