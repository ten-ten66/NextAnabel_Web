<?php
/**
 * セクション見出し（欧文ラベル＋和文見出し＋リード文）
 *
 * @var string $en     欧文ラベル（装飾。読み上げは和文見出しに任せる）
 * @var string $title  見出し
 * @var string $id     見出しの id（section の aria-labelledby で参照する）
 * @var string $lead   リード文（省略可）
 * @var string $tag    見出しの要素名（省略時 h2）
 * @var string $class  追加の class
 */
$tag = in_array($tag ?? 'h2', ['h1', 'h2', 'h3'], true) ? ($tag ?? 'h2') : 'h2';
$lead ??= '';
$class ??= '';
?>
<div class="c-section-head <?= e($class) ?>">
  <p class="c-section-head__en" lang="en" aria-hidden="true"><?= e($en) ?></p>
  <<?= $tag ?> class="c-section-head__title" id="<?= e($id) ?>"><?= e($title) ?></<?= $tag ?>>
  <?php if ($lead !== ''): ?>
    <p class="c-section-head__lead"><?= e($lead) ?></p>
  <?php endif; ?>
</div>
