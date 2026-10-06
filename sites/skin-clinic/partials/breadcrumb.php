<?php
/**
 * パンくずリスト
 * @var list<array{label: string, href?: string}> $items
 */
if (!$items) {
    return;
}
$last = count($items) - 1;
?>
<nav class="c-breadcrumb" aria-label="パンくずリスト">
  <ol class="c-breadcrumb__list">
    <?php foreach (array_values($items) as $i => $item): ?>
      <li class="c-breadcrumb__item">
        <?php if ($i < $last && isset($item['href'])): ?>
          <a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
        <?php else: ?>
          <span aria-current="page"><?= e($item['label']) ?></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
