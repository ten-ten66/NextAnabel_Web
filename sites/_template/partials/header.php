<?php
/** @var array<string, mixed> $page */
?>
<header class="l-header">
  <a class="l-header__logo" href="<?= e(url('index')) ?>"><?= e(site('name')) ?></a>
  <nav class="l-header__nav" aria-label="メインメニュー">
    <ul>
      <?php foreach (site('nav') as $item): ?>
        <li><a href="<?= e(url($item['id'])) ?>"<?= aria_current($item['id'], $page['id']) ?>><?= e($item['label']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>
</header>
