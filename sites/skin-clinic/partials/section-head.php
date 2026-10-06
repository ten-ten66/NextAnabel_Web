<?php
/**
 * セクション見出し（広い画面では縦書きのラベルになる）
 * @var string $en    英語の小見出し（装飾）
 * @var string $title 見出し
 * @var string $id    見出しの id（section の aria-labelledby から参照する）
 * @var string|null $level h2（既定）/ h3
 */
$tag = ($level ?? 'h2') === 'h3' ? 'h3' : 'h2';
?>
<div class="c-section-head">
  <p class="c-section-head__en" aria-hidden="true"><?= e($en) ?></p>
  <<?= $tag ?> class="c-section-head__title" id="<?= e($id) ?>"><?= e($title) ?></<?= $tag ?>>
</div>
