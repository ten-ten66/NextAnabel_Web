<?php
/**
 * 下層ページの見出し（パンくず・ページ名・リード文）
 * @var array<string, mixed> $page
 * @var string $en    英語の小見出し（装飾）
 * @var string|null $lead リード文
 */
?>
<div class="l-page-head">
  <div class="l-container">
    <?php partial('breadcrumb', ['items' => $page['breadcrumb'] ?? []]); ?>
    <div class="l-page-head__body">
      <p class="l-page-head__en" aria-hidden="true"><?= e($en ?? '') ?></p>
      <h1 class="l-page-head__title"><?= e($page['title']) ?></h1>
      <?php if (!empty($lead)): ?>
        <p class="l-page-head__lead"><?= e($lead) ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
